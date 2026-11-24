<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AbsencesJob;
use App\Jobs\AttendanceEmailJob;
use App\Jobs\SendSmsAttendanceJob;
use App\Jobs\SendSmsAttendanceJob1;
use App\Jobs\SyncAttendifyCloudJob;
use App\Models\Absence;
use App\Models\Attendance;
use App\Models\AttendanceDailySummary;
use App\Models\Device;
use App\Models\DeviceSequence;
use App\Models\SchoolInfo;
use App\Models\SchoolYear;
use App\Models\SmsQueue;
use App\Models\Student;
use App\Models\Station;
use App\Models\StudentGuardian;
use App\Models\Teacher;
use App\Services\SchoolYearServices;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AttendanceController extends Controller
{
    protected $schoolYearServices;

    public function __construct(SchoolYearServices $schoolYearServices)
    {
        $this->schoolYearServices = $schoolYearServices;
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'schoolYear' => 'required|numeric|exists:school_years,id',
            'year' => 'required|numeric',
            'month' => 'required|numeric',
            'search' => 'nullable|string',
        ]);

        $schoolYear = $validated['schoolYear'];
        $year = $validated['year'];
        $month = $validated['month'];
        $search = $validated['search'];

        $query = Student::with([
            'attendances' => function ($query) use ($year, $month, $schoolYear) {
                $query->whereYear('scanned_at', $year)
                    ->whereMonth('scanned_at', $month)
                    ->where('school_year_id', $schoolYear);
            }
        ])
            ->with([
                'attendanceDailySummary' => function ($query) use ($year, $month, $schoolYear) {
                    $query->whereYear('date', $year)
                        ->whereMonth('date', $month)
                        ->where('school_year_id', $schoolYear);
                }
            ])
            ->with([
                'absences' => function ($query) use ($year, $month) {
                    $query->whereYear('date', $year)
                        ->whereMonth('date', $month);
                }
            ])
            ->where('status', 'Active')
            ->whereHas('schoolYearStudents', function ($q) use ($schoolYear) {
                $q->where('school_year_id', $schoolYear);
            });

        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('student_id', 'LIKE', "%{$search}%");
                $query->orWhere('lastname', 'LIKE', "%{$search}%");
                $query->orWhere('firstname', 'LIKE', "%{$search}%");
            });
        }

        $students = $query
            ->orderBy('lastname', 'ASC')
            ->orderBy('firstname', 'ASC')
            ->get();

        return response()->json([
            'data' => $students
        ]);
    }

    public function count(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|min:1|exists:stations,id',
            'timeNow' => 'required|string',
        ]);

        $attendanceTotals = Attendance::where('station_id', $request->id)
            ->whereDate('scanned_at', date('Y-m-d', strtotime($request->timeNow)))
            ->where('status', 'success')
            ->selectRaw('
                COUNT(CASE WHEN type = "In" THEN 1 END) as type_in,
                COUNT(CASE WHEN type = "Out" THEN 1 END) as type_out
            ')
            ->first();

        $getSchoolYear = $this->schoolYearServices->getSchoolYear();
        $sy_from = $getSchoolYear['sy_from'];

        $total = Student::where('status', 'Active')
            ->where('sy_from', $sy_from)
            ->count();

        return response()->json([
            'total' => $total,
            'loggedin' => $attendanceTotals ? $attendanceTotals->type_in : 0,
            'loggedout' => $attendanceTotals ? $attendanceTotals->type_out : 0,
        ]);
    }

    public function lists(Request $request)
    {
        $search = $request->query('search');
        $type = $request->query('type') == "" ? null : $request->query('type');
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $attendances = Attendance::with('student', 'station')
            ->when($search, function ($query, $search) {
                $query->whereHas('student', function ($q) use ($search) {
                    $q->where('firstname', 'like', "%$search%")
                        ->orWhere('lastname', 'like', "%$search%")
                        ->orWhere('student_id', 'like', "%$search%");
                });
            })
            ->when($startDate && $endDate, function ($query) use ($startDate, $endDate) {
                $query->whereDate('scanned_at', '>=', date('Y-m-d', strtotime($startDate)))
                    ->whereDate('scanned_at', '<=', date('Y-m-d', strtotime($endDate)));
            })
            ->when($type, function ($query, $type) {
                if ($type == 'InAm') {
                    $query->where('type', 'In');
                    $query->whereTime('scanned_at', '<=', '11:30:00');
                } elseif ($type == 'OutAm') {
                    $query->where('type', 'Out')
                        ->whereTime('scanned_at', '>', '11:30:00')
                        ->whereTime('scanned_at', '<=', '13:30:00');
                } elseif ($type == 'InPm') {
                    $query->where('type', 'In')
                        ->whereTime('scanned_at', '>', '11:30:00')
                        ->whereTime('scanned_at', '<=', '13:30:00');
                } elseif ($type == 'OutPm') {
                    $query->where('type', 'Out')
                        ->whereTime('scanned_at', '>', '13:30:00');
                } else {
                    $query->where('type', $type);
                }
            });

        if ($user->role_id == 3) {
            $userId = $user->id;
            $attendances->where(function ($q) use ($userId) {
                $q->where('teachers_id', $userId)
                    ->orWhereHas('student.schoolYearStudents', function ($sq) use ($userId) {
                        $sq->where('co_teacher_id', $userId)
                            ->whereColumn('school_year_students.school_year_id', 'attendances.school_year_id');
                    });
            });
        }

        $attendances = $attendances->orderBy('scanned_at', 'DESC')
            ->paginate(10);

        return response()->json([
            'data' => $attendances->items(),
            'meta' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'prev' => $attendances->previousPageUrl(),
                'next' => $attendances->nextPageUrl(),
            ]
        ]);
    }

    public function classRoster(Request $request)
    {
        $validated = $request->validate([
            'schoolYear' => 'required|numeric|exists:school_years,id',
            'year' => 'required|numeric',
            'month' => 'required|numeric',
            'search' => 'nullable|string',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $schoolYear = $validated['schoolYear'];
        $year = $validated['year'];
        $month = $validated['month'];
        $search = $validated['search'] ?? null;

        $query = Student::with([
            'attendances' => function ($query) use ($year, $month, $schoolYear) {
                $query->whereYear('scanned_at', $year)
                    ->whereMonth('scanned_at', $month)
                    ->where('school_year_id', $schoolYear);
            }
        ])
            ->with([
                'attendanceDailySummary' => function ($query) use ($year, $month, $schoolYear) {
                    $query->whereYear('date', $year)
                        ->whereMonth('date', $month)
                        ->where('school_year_id', $schoolYear);
                }
            ])
            ->with([
                'absences' => function ($query) use ($year, $month) {
                    $query->whereYear('date', $year)
                        ->whereMonth('date', $month);
                }
            ])
            ->whereHas('schoolYearStudents', function ($q) use ($schoolYear, $user) {
                $q->where('school_year_id', $schoolYear);

                if ($user->role_id == 3) {
                    $q->where(function ($sub) use ($user) {
                        $sub->where('teacher_id', $user->id)
                            ->orWhere('co_teacher_id', $user->id);
                    });
                }
            });

        // Apply Search
        if (!empty($search)) {
            $query->where(function ($query) use ($search) {
                $query->where('student_id', 'LIKE', "%{$search}%");
                $query->orWhere('lastname', 'LIKE', "%{$search}%");
                $query->orWhere('firstname', 'LIKE', "%{$search}%");
            });
        }

        // Use paginate(100) instead of get() to set a hard limit per view
        $students = $query
            ->orderBy('lastname', 'ASC')
            ->orderBy('firstname', 'ASC')
            ->paginate(100);

        // Pagination natively wraps the results in a 'data' array
        return response()->json($students);
    }

    public function scan(Request $request, $id, $code)
    {
        $station = Station::find($id);

        if (!$station) {
            return response()->json(['success' => false, 'message' => 'Station not found.'], 404);
        }

        $stationIp = $request->ipaddress;
        $deviceId = $station->uuid;
        $scanned_at = date('Y-m-d H:i:s');

        return $this->handleScan($deviceId, $stationIp, 'qr', $code, $scanned_at);
    }

    public function scanQr(Request $request)
    {
        $request->validate([
            'qr_code' => 'required',
            'scanned_at' => 'nullable|date_format:Y-m-d H:i:s',
        ]);

        $qr_code = $request->qr_code;
        $scanned_at = $request->scanned_at ?? date('Y-m-d H:i:s');
        $appKey = $request->header('X-APP-KEY');
        $deviceId = $request->header('X-DEVICE-ID');
        $stationIp = $request->header('X-STATION-IP');

        $expectedKey = env('ATTENDANCE_API_KEY', 'ILS_LNU_SCANNER_2026');

        if ($appKey !== $expectedKey) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }
        if (!$deviceId) {
            return response()->json(['success' => false, 'message' => 'Device ID missing.'], 410);
        }
        if (!$stationIp) {
            return response()->json(['success' => false, 'message' => 'Station IP missing.'], 411);
        }

        return $this->handleScan($deviceId, $stationIp, 'qr', $qr_code, $scanned_at);
    }

    private function handleScan($deviceId, $stationIp, $typeScan, $code, $scanned_at)
    {
        try {
            return DB::transaction(function () use ($deviceId, $stationIp, $typeScan, $code, $scanned_at) {

                // 1. Station Management
                $station = Station::firstOrCreate(
                    ['uuid' => $deviceId],
                    [
                        'station_name' => 'Station - ' . $deviceId,
                        'ipaddress' => $stationIp,
                        'location' => 'Unknown',
                    ]
                );

                $method = $typeScan;

                // 2. Student Lookup
                $student = Student::where($method === 'qr' ? 'qr_code' : 'rfid_tag', $code)->first();
                if (!$student) {
                    return response()->json(['success' => false, 'message' => 'Student not found.'], 300);
                }

                // 3. Global Duplicate Check (Removed station constraint to check across all devices)
                $recentScan = Attendance::where('student_id', $student->id)
                    ->where('scanned_at', '>=', Carbon::parse($scanned_at)->subMinutes(30))
                    ->first();

                if ($recentScan) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Duplicate scan detected. Please wait 30 minutes before scanning again.',
                    ], 409);
                }

                // 5. Get Attendance Type Data
                $getType = $this->getType($student, $scanned_at);

                // 6. Record Attendance
                $createAttendance = Attendance::create([
                    'student_id' => $student->id,
                    'station_id' => $station->id,
                    'type' => $getType['type'],
                    'method' => $method,
                    'status' => $getType['result'],
                    'message' => $getType['message'],
                    'scanned_at' => $scanned_at,
                    'school_year_id' => $student->school_year_id,
                    'sy_from' => $student->sy_from,
                    'sy_to' => $student->sy_to,
                    'level' => $student->level,
                    'grade' => $student->grade,
                    'section' => $student->section,
                    'teachers_id' => $student->teachers_id,
                    'message_status' => 'Pending',
                    'email_status' => 'Pending'
                ]);

                // 7. Update Daily Summary
                $this->updateDailySummary($scanned_at, $student->id, $getType['type'], $student);

                // 8. Prepare Return Data & Formatting
                $createAttendance->setRelation('student', $student);
                $attendance = $createAttendance;

                if ($attendance->student->photo) {
                    $attendance->student->photo = $attendance->student->photo
                        ? asset("storage/{$attendance->student->photo}")
                        : asset('images/no-image-icon.png');
                }

                dispatch(new AbsencesJob($student->id, $scanned_at))
                    ->onQueue('absences')
                    ->afterCommit();

                SyncAttendifyCloudJob::dispatch()
                    ->onQueue('cloud_sync')
                    ->afterCommit();

                // 9. Queue Guardian Notifications
                $message_type = $getType['message_type'];

                $shoolInfo = SchoolInfo::first();
                $schoolName = $shoolInfo ? $shoolInfo->name : 'CDEVITSolutions';

                $student = $attendance->student;

                $lastname = mb_strtoupper($student->lastname);
                $firstname = mb_strtoupper($student->firstname);
                $middleInitial = !empty($student->middlename) ? mb_strtoupper(mb_substr($student->middlename, 0, 1)) . "." : '';
                $extname = !empty($student->extname) ? mb_strtoupper($student->extname) : '';

                $name = trim("$lastname, $firstname $extname $middleInitial");

                $formattedTime = date('h:i:s A', strtotime($scanned_at));
                $formattedDate = date('M d, Y', strtotime($scanned_at));

                $trackingId = strtoupper(Str::random(5));

                $message = "{$name} {$message_type} in {$schoolName} at {$formattedTime} on {$formattedDate}. Ref: {$trackingId}";

                $guardians = StudentGuardian::where('student_id', $student->id)->get();

                if ($guardians->count() > 0) {
                    foreach ($guardians as $guardian) {
                        if (!empty($guardian->email)) {
                            $emailPayload = [
                                'id' => $attendance->id,
                                'email' => $guardian->email,
                                'message' => $message
                            ];
                            dispatch(new AttendanceEmailJob($emailPayload))->onQueue('attendance_emails');
                        }

                        if (!empty($guardian->contact_no)) {
                            SmsQueue::create([
                                'attendance_id' => $attendance->id,
                                'phone_number' => $guardian->contact_no,
                                'message' => $message,
                                'status' => 'pending'
                            ]);
                        }
                    }
                } else {
                    $emailPayload = [
                        'id' => $attendance->id,
                        'email' => $student->email ?? '',
                        'message' => $message
                    ];

                    dispatch(new AttendanceEmailJob($emailPayload))->onQueue('attendance_emails');

                    if (!empty($student->contact_no)) {
                        SmsQueue::create([
                            'attendance_id' => $attendance->id,
                            'phone_number' => $student->contact_no,
                            'message' => $message,
                            'status' => 'pending'
                        ]);
                    }
                }

                $attendances = [];

                if ($method == 'rfid') {
                    $attendances = $this->fetchRecentAttendances($station->id, $scanned_at);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Successful!',
                    'student' => $attendance,
                    'sms_payload' => [
                        'send_sms' => false,
                        'phone_number' => '',
                        'message' => ''
                    ],
                    'attendances' => $attendances
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An internal server error occurred while processing the scan.',
            ], 500);
        }
    }

    public function recentAttendances(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|min:1|exists:stations,id',
            'timeNow' => 'required|string',
        ]);

        $stationId = $request->id;
        $timeNow = $request->timeNow;

        $attendances = $this->fetchRecentAttendances($stationId, $timeNow);

        return response()->json($attendances);
    }

    private function fetchRecentAttendances($stationId, $scanned_at)
    {
        $attendances = Attendance::with('student')
            ->where('station_id', $stationId)
            ->whereDate('scanned_at', date('Y-m-d', strtotime($scanned_at)))
            ->orderBy('scanned_at', 'DESC')
            ->limit(5)
            ->get();
        return $attendances;
    }

    private function sendSmsAttendance($attendance, $message_type, $scanned_at)
    {
        $shoolInfo = SchoolInfo::first();
        $schoolName = $shoolInfo ? $shoolInfo->name : 'CDEVITSolutions';

        $student = $attendance->student;

        $lastname = mb_strtoupper($student->lastname);
        $firstname = mb_strtoupper($student->firstname);
        $middleInitial = !empty($student->middlename) ? mb_strtoupper(mb_substr($student->middlename, 0, 1)) . "." : '';
        $extname = !empty($student->extname) ? mb_strtoupper($student->extname) : '';

        $name = trim("$lastname, $firstname $extname $middleInitial");

        $formattedTime = date('h:i:s A', strtotime($scanned_at));
        $formattedDate = date('M d, Y', strtotime($scanned_at));
        $message = "{$name} {$message_type} in {$schoolName} at {$formattedTime} on {$formattedDate}";

        $devices = Device::get();

        if ($devices->isNotEmpty()) {
            $deviceCount = $devices->count();
            $currentTurn = Cache::increment('sms_device_turn');
            $deviceIndex = $currentTurn % $deviceCount;
            $selectedDevice = $devices[$deviceIndex];

            dispatch(new SendSmsAttendanceJob(
                $attendance->id,
                $student->contact_no,
                $message,
                $selectedDevice->name
            ))->onQueue('gsmAttendance');
        }
    }

    private function handleScan1($request, $typeScan)
    {
        $request->validate([
            'qr_code' => 'required',
        ]);

        $qr_code = $request->qr_code;
        $appKey = $request->header('X-APP-KEY');
        $deviceId = $request->header('X-DEVICE-ID');
        $stationIp = $request->header('X-STATION-IP');

        if ($appKey !== env('ATTENDANCE_API_KEY')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Invalid API key.',
            ], 403);
        }

        if (!$deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Device ID missing.',
            ], 410);
        }

        if (!$stationIp) {
            return response()->json([
                'success' => false,
                'message' => 'Station IP missing.',
            ], 411);
        }

        $station = Station::firstOrCreate(
            ['uuid' => $deviceId],
            [
                'station_name' => 'Station - ' . $deviceId,
                'ipaddress' => $stationIp,
                'location' => 'Unknown',
            ]
        );

        $method = $typeScan;

        $student = Student::where($method === 'qr' ? 'qr_code' : 'rfid_tag', $qr_code)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found.',
            ], 300);
        }

        $recentScan = Attendance::where('student_id', $student->id)
            ->where('scanned_at', '>=', Carbon::now()->subMinutes(30))
            ->first();

        if ($recentScan) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate scan detected. Please wait 30 minutes before scanning again.',
            ], 409);
        }

        $scanned_at = now();

        $student = $this->updateStudent($student);
        $student_id = $student->id;

        $getType = $this->getType($student, $scanned_at);

        $status = $getType['result'];
        $message = $getType['message'];
        $message_type = $getType['message_type'];
        $type = $getType['type'];

        $createAttendance = Attendance::create([
            'student_id' => $student_id,
            'station_id' => $station->id,
            'type' => $type,
            'method' => $method,
            'status' => $status,
            'message' => $message,
            'scanned_at' => $scanned_at,
            'school_year_id' => $student->school_year_id,
            'sy_from' => $student->sy_from,
            'sy_to' => $student->sy_to,
            'level' => $student->level,
            'grade' => $student->grade,
            'section' => $student->section,
            'teachers_id' => $student->teachers_id
        ]);

        $this->updateDailySummary($scanned_at, $student_id, $type, $student);

        $attendance = Attendance::with('student')->where('id', $createAttendance->id)->first();
        $shoolInfo = SchoolInfo::first();
        $schoolName = $shoolInfo ? $shoolInfo->name : 'CDEVITSolutions';

        $lastname = mb_strtoupper($student->lastname);
        $firstname = mb_strtoupper($student->firstname);

        if (!empty($student->middlename)) {
            $middleInitial = mb_strtoupper(mb_substr($student->middlename, 0, 1)) . ".";
        } else {
            $middleInitial = '';
        }
        if (!empty($student->extname)) {
            $extname = mb_strtoupper($student->extname);
        } else {
            $extname = '';
        }

        $name = "$lastname, $firstname $extname $middleInitial";

        $message = $name . " " . $message_type . " in " . $schoolName . " at " . date('h:i:s A', strtotime($scanned_at)) . " on " . date('M d, Y', strtotime($scanned_at));
        $message = str_replace(" ", "_", $message);

        if ($attendance && $attendance->student) {
            $attendance->student->photo = $attendance->student->photo
                ? asset("storage/{$attendance->student->photo}")
                : asset('images/no-image-icon.png');
        }

        return response()->json([
            'success' => true,
            'message' => 'Successful!',
            'student' => $attendance,
        ]);
    }

    public function calendar(Request $request)
    {
    }

    private function updateStudent($student)
    {
        $getSchoolYear = $this->schoolYearServices->getSchoolYear();

        $schoolYearData = [
            'school_year_id' => $getSchoolYear['school_year_id'],
            'sy_from' => $getSchoolYear['sy_from'],
            'sy_to' => $getSchoolYear['sy_to'],
        ];

        $student->school_year_id = $schoolYearData['school_year_id'];
        $student->sy_from = $schoolYearData['sy_from'];
        $student->sy_to = $schoolYearData['sy_to'];
        $student->status = 'Active';
        $student->save();

        return $student;
    }

    private function getType($student, $scanned_at)
    {
        $date = date('Y-m-d', strtotime($scanned_at));
        $time = date('H:i:s', strtotime($scanned_at));

        $todayLogs = Attendance::where('student_id', $student->id)
            ->whereDate('scanned_at', $date)
            ->orderBy('scanned_at', 'DESC')
            ->get();

        $count = $todayLogs->count();
        $lastLog = $todayLogs->first();

        if ($count >= 4) {
            return [
                'result' => 'error',
                'type' => 'Out',
                'message' => 'Maximum daily logs reached (4/4).',
                'message_type' => "has EXCEEDED logs"
            ];
        }

        if (!$lastLog) {
            $type = $time > '14:00:00' ? 'Out' : 'In';
            return [
                'result' => 'success',
                'type' => $type,
                'message' => 'Success!',
                'message_type' => "has LOGGED " . $type
            ];
        }

        $newType = ($lastLog->type === 'In') ? 'Out' : 'In';
        $msgType = ($newType === 'In') ? "has LOGGED IN" : "has LOGGED OUT";

        return [
            'result' => 'success',
            'type' => $newType,
            'message' => 'Success!',
            'message_type' => $msgType
        ];
    }

    private function getType1($student, $scanned_at)
    {
        $todayScan = Attendance::where('student_id', $student->id)
            ->whereDate('scanned_at', date('Y-m-d', strtotime($scanned_at)))
            ->orderBy('scanned_at', 'DESC')
            ->first();

        if (!$todayScan) {
            return [
                'result' => 'success',
                'type' => 'In',
                'message' => 'Success!',
                'message_type' => "has LOGGED IN"
            ];
        }

        $todayScanCount = Attendance::where('student_id', $student->id)
            ->whereDate('scanned_at', date('Y-m-d', strtotime($scanned_at)))
            ->orderBy('scanned_at', 'DESC')
            ->count();

        if ($todayScanCount >= 4) {
            return [
                'result' => 'error',
                'type' => 'Out',
                'message' => 'Exceed (4 Logs)',
                'message_type' => "has LOGGED OUT"
            ];
        }

        if ($todayScan->type == "In") {
            return [
                'result' => 'success',
                'type' => 'Out',
                'message' => 'Success!',
                'message_type' => "has LOGGED OUT"
            ];
        } else {
            return [
                'result' => 'success',
                'type' => 'In',
                'message' => 'Success!',
                'message_type' => "has LOGGED IN"
            ];
        }
    }

    private function updateDailySummary($scanned_at, $student_id, $type, $student)
    {
        $scannedAt = Carbon::parse($scanned_at);
        $dateSummary = $scannedAt->toDateString();
        $timeOnly = $scannedAt->toTimeString();

        $summary = AttendanceDailySummary::updateOrCreate(
            [
                'student_id' => $student_id,
                'date' => $dateSummary,
            ],
            [
                'school_year_id' => $student->school_year_id,
                'sy_from' => $student->sy_from,
                'sy_to' => $student->sy_to,
                'level' => $student->level,
                'grade' => $student->grade,
                'section' => $student->section,
                'teachers_id' => $student->teachers_id,
                'is_late' => 0,
                'is_undertime' => 0,
                'is_excused' => 0,
            ]
        );

        $updateData = [];

        if ($type === 'In') {
            if ($timeOnly <= '11:30:00') {
                $updateData['actual_am_in'] = $timeOnly;
            } else {
                $updateData['actual_pm_in'] = $timeOnly;
            }
        } else {
            if ($timeOnly <= '13:30:00' && empty($summary->actual_am_out)) {
                $updateData['actual_am_out'] = $timeOnly;
            } else {
                $updateData['actual_pm_out'] = $timeOnly;
            }
        }

        if ($type === 'In') {
            if ($summary->is_late === 0 && $timeOnly > '08:00:00') {
                $updateData['is_late'] = 1;
            }
        }

        if ($summary->is_late === 1) {
            unset($updateData['is_late']);
        }

        if ($summary->is_excused === 1) {
            unset($updateData['is_excused']);
        }

        if ($summary->is_undertime === 1) {
            unset($updateData['is_undertime']);
        }

        if (!empty($updateData)) {
            $summary->update($updateData);
        }
    }

    private function updateDailySummary1($scanned_at, $student_id, $type, $student)
    {
        $dateSummary = date('Y-m-d', strtotime($scanned_at));

        $checkSummary = AttendanceDailySummary::where('date', $dateSummary)
            ->where('student_id', $student_id)
            ->first();
        if ($checkSummary) {
            $summaryAmOut = $checkSummary->actual_am_out;
            $is_late = $checkSummary->is_late;
            $is_undertime = $checkSummary->is_undertime;
            $is_excused = $checkSummary->is_excused;
            $insertSummary = $checkSummary;
        } else {
            $summaryAmOut = '';
            $is_late = 0;
            $is_undertime = 0;
            $is_excused = 0;
            $insertSummary = new AttendanceDailySummary;
            $insertSummary->student_id = $student_id;
            $insertSummary->date = $dateSummary;
        }

        $scanned_at_time = date('H:i:s', strtotime($scanned_at));
        $actual_am_in = '';
        $actual_am_out = '';
        $actual_pm_in = '';
        $actual_pm_out = '';

        if ($type == 'In') {
            if ($scanned_at <= '11:30:00') {
                $actual_am_in = $scanned_at_time;
            } else {
                $actual_pm_in = $scanned_at_time;
            }
        } else {
            if ($scanned_at <= '13:30:00' && $summaryAmOut == '') {
                $actual_am_out = $scanned_at_time;
            } else {
                $actual_pm_out = $scanned_at_time;
            }
        }

        if ($actual_am_in != '') {
            $insertSummary->actual_am_in = $actual_am_in;
        }
        if ($actual_pm_in != '') {
            $insertSummary->actual_pm_in = $actual_pm_in;
        }
        if ($actual_am_out != '') {
            $insertSummary->actual_am_out = $actual_am_out;
        }
        if ($actual_pm_out != '') {
            $insertSummary->actual_pm_out = $actual_pm_out;
        }

        $insertSummary->school_year_id = $student->school_year_id;
        $insertSummary->sy_from = $student->sy_from;
        $insertSummary->sy_to = $student->sy_to;
        $insertSummary->level = $student->level;
        $insertSummary->grade = $student->grade;
        $insertSummary->section = $student->section;
        $insertSummary->teachers_id = $student->teachers_id;
        $insertSummary->is_late = $is_late;
        $insertSummary->is_undertime = $is_undertime;
        $insertSummary->is_excused = $is_excused;
        $insertSummary->save();
    }

    public function updateDaily(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required|integer',
                'date' => 'required|date',
                'status' => 'required|in:present,absent',
                'is_late' => 'nullable|integer',
                'is_undertime' => 'nullable|integer',
                'is_excused' => 'nullable|integer',
                'remarks' => 'nullable|string',
            ]);

            DB::transaction(function () use ($request) {
                $student = Student::findOrFail($request->student_id);

                $snapshot = [
                    'school_year_id' => $student->school_year_id,
                    'sy_from' => $student->sy_from,
                    'sy_to' => $student->sy_to,
                    'level' => $student->level,
                    'grade' => $student->grade,
                    'section' => $student->section,
                    'teachers_id' => $student->teachers_id,
                ];

                if ($request->status === 'present') {
                    AttendanceDailySummary::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'date' => $request->date,
                        ],
                        array_merge($snapshot, [
                            'is_absent' => null,
                            'is_late' => $request->is_late ?? 0,
                            'is_undertime' => $request->is_undertime ?? 0,
                            'is_excused' => $request->is_excused ?? 0,
                            'remarks' => $request->remarks,
                        ])
                    );

                    Absence::where('student_id', $student->id)
                        ->where('date', $request->date)
                        ->delete();
                }

                if ($request->status === 'absent') {
                    $checkAttendance = Attendance::where('student_id', $student->id)
                        ->whereDate('scanned_at', date('Y-m-d', strtotime($request->date)))
                        ->first();

                    if ($checkAttendance) {
                        AttendanceDailySummary::updateOrCreate(
                            [
                                'student_id' => $student->id,
                                'date' => $request->date,
                            ],
                            array_merge($snapshot, [
                                'is_absent' => 1,
                                'is_late' => 0,
                                'is_undertime' => 0,
                                'is_excused' => 0,
                                'remarks' => $request->remarks,
                            ])
                        );
                    } else {
                        AttendanceDailySummary::where('student_id', $student->id)
                            ->where('date', $request->date)
                            ->delete();
                    }

                    Absence::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'date' => $request->date,
                        ]
                    );
                }
            });

            SyncAttendifyCloudJob::dispatch()
                ->onQueue('cloud_sync')
                ->afterCommit();

            return response()->json([
                'message' => 'Attendance updated successfully'
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update attendance. Please try again.',
            ], 500);
        }
    }
}