<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AbsencesJob;
use App\Jobs\AttendanceEmailJob;
use App\Jobs\SendSmsAttendanceJob;
use App\Jobs\SendSmsAttendanceJob1;
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
use App\Models\Teacher;
use App\Services\SchoolYearServices;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
        
        $query = Student::with(['attendances' => function ($query) use ($year, $month, $schoolYear) 
                {
                    $query->whereYear('scanned_at', $year)
                        ->whereMonth('scanned_at', $month)
                        ->where('school_year_id', $schoolYear);
                }
            ])
            ->with(['attendanceDailySummary' => function ($query) use ($year, $month, $schoolYear) 
                {
                    $query->whereYear('date', $year)
                        ->whereMonth('date', $month)
                        ->where('school_year_id', $schoolYear);
                }
            ])
            ->with(['absences' => function ($query) use ($year, $month) 
                {
                    $query->whereYear('date', $year)
                        ->whereMonth('date', $month);
                }
            ])
            ->where('status','Active')
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
            ->orderBy('lastname','ASC')
            ->orderBY('firstname','ASC')
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

        $total = Student::where('status','Active')
            ->where('sy_from',$sy_from)
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
                $query->whereDate('scanned_at', '>=', date('Y-m-d',strtotime($startDate)))
                    ->whereDate('scanned_at', '<=', date('Y-m-d',strtotime($endDate)));
            })
            ->when($type, function ($query, $type) {
                if($type=='InAm'){
                    $query->where('type', 'In');
                    $query->whereTime('scanned_at', '<=', '11:30:00');
                }elseif($type=='OutAm'){
                    $query->where('type', 'Out')
                        ->whereTime('scanned_at', '>', '11:30:00')
                        ->whereTime('scanned_at', '<=', '13:30:00');
                }elseif($type=='InPm'){
                    $query->where('type', 'In')
                        ->whereTime('scanned_at', '>', '11:30:00')
                        ->whereTime('scanned_at', '<=', '13:30:00');
                }elseif($type=='OutPm'){
                    $query->where('type', 'Out')
                        ->whereTime('scanned_at', '>', '13:30:00');
                }else{
                    $query->where('type', $type);
                }
            });

        if($user->role_id==3){
            $attendances->where('teachers_id',$user->id);
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
        
        $query = Student::with(['attendances' => function ($query) use ($year, $month, $schoolYear) {
                    $query->whereYear('scanned_at', $year)
                        ->whereMonth('scanned_at', $month)
                        ->where('school_year_id', $schoolYear);
                }
            ])
            ->with(['attendanceDailySummary' => function ($query) use ($year, $month, $schoolYear) {
                    $query->whereYear('date', $year)
                        ->whereMonth('date', $month)
                        ->where('school_year_id', $schoolYear);
                }
            ])
            ->with(['absences' => function ($query) use ($year, $month) 
                {
                    $query->whereYear('date', $year)
                        ->whereMonth('date', $month);
                }
            ])
            ->whereHas('schoolYearStudents', function ($q) use ($schoolYear) {
                $q->where('school_year_id', $schoolYear);
            });

        // Filter by the logged-in teacher (Assuming role_id 3 is Teacher based on your lists() method)
        if ($user->role_id == 3) {
            $query->where('teachers_id', $user->id);
        }

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
            ->orderBy('lastname','ASC')
            ->orderBy('firstname','ASC')
            ->paginate(100); 
        
        // Pagination natively wraps the results in a 'data' array
        return response()->json($students);
    }

    public function scan(Request $request, $id, $code)
    {
        // $request->validate([
        //     'id' => 'required|numeric|exists:stations,id',
        //     'code' => 'required|string',
        // ]);
        
        // $code = $request->code;
        // $id = $request->id;
        
        $station = Station::find($id);

        if(!$station){
            return response()->json(['success' => false, 'message' => 'Station not found.'], 404);
        }

        $stationIp = $request->ipaddress;
        $deviceId = $station->uuid;

        return $this->handleScan($deviceId, $stationIp, 'qr', $code);
    }

    public function scanQr(Request $request)
    {
        $request->validate([
            'qr_code' => 'required',
        ]);

        $qr_code = $request->qr_code;
        $appKey = $request->header('X-APP-KEY');
        $deviceId = $request->header('X-DEVICE-ID');
        $stationIp = $request->header('X-STATION-IP');

        if ($appKey !== env('ATTENDANCE_API_KEY')) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }
        if (!$deviceId) {
            return response()->json(['success' => false, 'message' => 'Device ID missing.'], 410);
        }
        if (!$stationIp) {
            return response()->json(['success' => false, 'message' => 'Station IP missing.'], 411);
        }

        return $this->handleScan($deviceId, $stationIp, 'qr', $qr_code);
    }

    private function handleScan($deviceId, $stationIp, $typeScan, $code)
    {
        try {
            // Start the Database Transaction
            return DB::transaction(function () use ($deviceId, $stationIp, $typeScan, $code) {
                
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
                    ->where('scanned_at', '>=', Carbon::now()->subMinutes(30))
                    ->first();

                if ($recentScan) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Duplicate scan detected. Please wait 30 minutes before scanning again.',
                    ], 409);
                }

                $scanned_at = date('Y-m-d H:i:s');

                // 4. Update Student
                // $student = $this->updateStudent($student);
                
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

                dispatch(new AbsencesJob($student->id, $scanned_at))->onQueue('absences');                

                // $this->sendSmsAttendance($attendance, $getType['message_type'], $scanned_at);

                // SMS Logic omitted for brevity, but it is safe here...
                
                // 9. Commit Transaction and Return Success

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
                $message = "{$name} {$message_type} in {$schoolName} at {$formattedTime} on {$formattedDate}";

                $emailPayload = [
                    'id' => $attendance->id,
                    'email' => $student->email ?? '', 
                    'message' => $message
                ];

                dispatch(new AttendanceEmailJob($emailPayload))->onQueue('attendance_emails');                

                // $smsPayload = [
                //     'send_sms' => true,
                //     'phone_number' => $student->contact_no ?? '', 
                //     'message' => $message
                // ];

                if (!empty($student->contact_no)) {
                    SmsQueue::create([
                        'attendance_id' => $attendance->id,
                        'phone_number' => $student->contact_no,
                        'message' => $message,
                        'status' => 'pending'
                    ]);
                }

                $attendances = [];

                if($method == 'rfid'){
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
            }); // End of DB::transaction

        } catch (\Exception $e) {
            // Return a clean JSON response to the scanning device
            return response()->json([
                'success' => false,
                'message' => 'An internal server error occurred while processing the scan.',
                // 'error' => $e->getMessage() // You can uncomment this in local development, but hide it in production
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
                        ->whereDate('scanned_at',date('Y-m-d',strtotime($scanned_at)))
                        ->orderBy('scanned_at','DESC')
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
        
        // Optional: Only if your SMS gateway cannot handle spaces
        // $message = str_replace(" ", "_", $message);

        // 3. Round-Robin Device Selection
        $devices = Device::get();

        if ($devices->isNotEmpty()) {
            $deviceCount = $devices->count();

            // Increment a counter in the cache indefinitely
            // atomic increment prevents two scans from getting the same "turn"
            $currentTurn = Cache::increment('sms_device_turn');

            // Use Modulo (%) to pick the index (0, 1, 2, 0, 1, 2...)
            $deviceIndex = $currentTurn % $deviceCount;
            $selectedDevice = $devices[$deviceIndex];

            // 4. Dispatch Job
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

        if(!$stationIp){
            return response()->json([
                'success' => false,
                'message' => 'Station IP missing.',
            ], 411);
        }       

        // Find the station by IP address
        $station = Station::firstOrCreate(
            ['uuid' => $deviceId],
            [
                'station_name' => 'Station - ' . $deviceId,
                'ipaddress' => $stationIp,
                'location' => 'Unknown',
            ]
        );

        // Detect method based on code
        // $method = str_starts_with($request->code, 'qr-') ? 'qr' : 'rfid';
 
        // If QR code, remove the 'qr-' prefix
        // $searchCode = $method === 'qr' ? substr($request->code, 3)  : $request->code;

        $method = $typeScan;
        $scannedData = $qr_code;
        //$searchCode = $typeScan=='qr' ? $scannedData[0]['rawValue'] ?? null : $scannedData;
        
        // Find the student
        $student = Student::where($method === 'qr' ? 'qr_code' : 'rfid_tag', $qr_code)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found.',
            ], 300);
        }

        // Check for duplicate scan within 30 minutes
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

        $getType = $this->getType($student,$scanned_at);

        $status = $getType['result'];
        $message = $getType['message'];
        $message_type = $getType['message_type'];
        $type = $getType['type'];
        
        // Record attendance
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

        $attendance = Attendance::with('student')->where('id',$createAttendance->id)->first();
        $shoolInfo = SchoolInfo::first();
        $schoolName = $shoolInfo ? $shoolInfo->name : 'CDEVITSolutions';

        $target_id = $createAttendance->id;
        $contact_no = $attendance->student->contact_no;
        $lastname = mb_strtoupper($student->lastname);
        $firstname = mb_strtoupper($student->firstname);        

        if (!empty($student->middlename)) {
            $middleInitial = mb_strtoupper(mb_substr($student->middlename, 0, 1)) . ".";
        }else{
            $middleInitial = '';
        }
        if (!empty($student->extname)) {
            $extname = mb_strtoupper($student->extname);
        }else{
            $extname = '';
        }

        $name = "$lastname, $firstname $extname $middleInitial";
        
        $message = $name." ".$message_type." in " . $schoolName . " at " . date('h:i:s A', strtotime($scanned_at)). " on " . date('M d, Y', strtotime($scanned_at));
        
        $message = str_replace(" ","_",$message);

        //dispatch(new SendSmsAttendanceJob($target_id, $contact_no, $message))->onQueue('gsmAttendance');

        // $deviceSequence = DeviceSequence::first();

        // if($deviceSequence){
        //     if($deviceSequence->name=='COM3'){
        //         dispatch(new SendSmsAttendanceJob1($target_id, $contact_no, $message))->onQueue('gsmAttendance1');
        //     }else{
        //         dispatch(new SendSmsAttendanceJob($target_id, $contact_no, $message))->onQueue('gsmAttendance');
        //     }
        // }else{
        //     dispatch(new SendSmsAttendanceJob($target_id, $contact_no, $message))->onQueue('gsmAttendance');
        // }

        // if($deviceSequence){
        //     if($deviceSequence->name=='COM3'){
        //         $deviceName = 'COM4';
        //     }else{
        //         $deviceName = 'COM3';
        //     }
        //     $updateDeviceSequence = DeviceSequence::find($deviceSequence->id);
        // }else{
        //     $deviceName = 'COM4';
        //     $updateDeviceSequence = new DeviceSequence;
        // }
        // $updateDeviceSequence->name = $deviceName;
        // $updateDeviceSequence->save();

        //dispatch(new AbsencesJob($student->id, $scanned_at))->onQueue('absences');

        // $attendances = Attendance::with('student')
        //     ->whereNotIn('id',[$createAttendance->id])
        //     ->where('station_id', $station->id)
        //     ->whereDate('scanned_at',date('Y-m-d',strtotime($scanned_at)))
        //     ->orderBy('scanned_at','DESC')
        //     ->limit(6)
        //     ->get();

        $attendances = [];

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
            'sy_from'        => $getSchoolYear['sy_from'],
            'sy_to'          => $getSchoolYear['sy_to'],
        ];

        // 1. Update the student directly (No need to re-query)
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

        // Get all today's logs for this student in one go
        $todayLogs = Attendance::where('student_id', $student->id)
            ->whereDate('scanned_at', $date)
            ->orderBy('scanned_at', 'DESC')
            ->get();

        $count = $todayLogs->count();
        $lastLog = $todayLogs->first();

        // 1. Check for max logs first
        if ($count >= 4) {
            return [
                'result' => 'error',
                'type' => 'Out',
                'message' => 'Maximum daily logs reached (4/4).',
                'message_type' => "has EXCEEDED logs"
            ];
        }

        // 2. If no logs, it's always an "In"
        if (!$lastLog) {
            $type = $time > '14:00:00' ? 'Out' : 'In';
            return [
                'result' => 'success',
                'type' => $type,
                'message' => 'Success!',
                'message_type' => "has LOGGED IN"
            ];
        }

        // 3. Toggle type based on the last log
        $newType = ($lastLog->type === 'In') ? 'Out' : 'In';   
        
        $newType = $time > '14:00:00' ? 'Out' : $newType;
        
        $msgType = ($newType === 'In') ? "has LOGGED IN" : "has LOGGED OUT";

        return [
            'result' => 'success',
            'type' => $newType,
            'message' => 'Success!',
            'message_type' => $msgType
        ];
    }

    private function getType1($student,$scanned_at)
    {
        $todayScan = Attendance::where('student_id', $student->id)
            ->whereDate('scanned_at',date('Y-m-d',strtotime($scanned_at)))
            ->orderBy('scanned_at','DESC')
            ->first();

        if(!$todayScan){
            return [
                'result' => 'success',
                'type' => 'In',
                'message' => 'Success!',
                'message_type' => "has LOGGED IN"
            ];
        }

        $todayScanCount = Attendance::where('student_id', $student->id)
            ->whereDate('scanned_at',date('Y-m-d',strtotime($scanned_at)))
            ->orderBy('scanned_at','DESC')
            ->count();

        if($todayScanCount>=4){
            return [
                'result' => 'error',
                'type' => 'Out',
                'message' => 'Exceed (4 Logs)',
                'message_type' => "has LOGGED OUT"
            ];
        }
        
        if($todayScan->type=="In"){
            return [
                'result' => 'success',
                'type' => 'Out',
                'message' => 'Success!',
                'message_type' => "has LOGGED OUT"
            ];
        }
        else{
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
        $dateSummary = $scannedAt->toDateString(); // Y-m-d
        $timeOnly = $scannedAt->toTimeString();   // H:i:s

        // 1. Use updateOrCreate to prevent duplicate records for the same day/student.
        // This handles the "if exists update, else create" logic atomically.
        $summary = AttendanceDailySummary::updateOrCreate(
            [
                'student_id' => $student_id,
                'date'       => $dateSummary,
            ],
            [
                // These fields are updated every time a scan occurs to ensure they are current
                'school_year_id' => $student->school_year_id,
                'sy_from'        => $student->sy_from,
                'sy_to'          => $student->sy_to,
                'level'          => $student->level,
                'grade'          => $student->grade,
                'section'        => $student->section,
                'teachers_id'    => $student->teachers_id,
                // Default fields (can be modified based on logic later)
                'is_late'        => 0,  // default value, will be updated based on conditions
                'is_undertime'   => 0,  // default value, will be updated based on conditions
                'is_excused'     => 0,  // default value, will be updated based on conditions
            ]
        );

        // 2. Determine which time slot to fill (actual_in/out)
        $updateData = [];

        // Set actual AM/PM In or Out based on the time scanned
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

        // 3. Handle 'is_late', 'is_undertime', 'is_excused'
        // Only update these fields if they have not been manually set by the teacher
        if ($type === 'In') {
            // If the student scans after 8:00 AM, mark them late (but only if not manually set to 1)
            if ($summary->is_late === 0 && $timeOnly > '08:00:00') {
                // If the student scans after 8:00 AM and is still set to the default (0), mark them as late
                $updateData['is_late'] = 1;
            }
        }

        // Prevent overwriting of manually set "late" status
        if ($summary->is_late === 1) {
            // If the student was manually marked as late (is_late = 1), do not override it
            unset($updateData['is_late']);
        }

        if ($summary->is_excused === 1) {
            // If the student was manually marked as excused (is_excused = 1), do not override it
            unset($updateData['is_excused']);
        }

        if ($summary->is_undertime === 1) {
            // If the student was manually marked as undertime (is_undertime = 1), do not override it
            unset($updateData['is_undertime']);
        }

        // 4. Only update the specific time column or status flags if needed
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
        if($checkSummary){            
            $summaryAmOut = $checkSummary->actual_am_out;
            $is_late = $checkSummary->is_late;
            $is_undertime = $checkSummary->is_undertime;
            $is_excused = $checkSummary->is_excused;
            $insertSummary = $checkSummary;
        }else{
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

        if($type=='In'){
            if($scanned_at <= '11:30:00'){
                $actual_am_in = $scanned_at_time;
            }else{
                $actual_pm_in = $scanned_at_time;
            }
        }else{
            if($scanned_at <= '13:30:00' && $summaryAmOut == ''){
                $actual_am_out = $scanned_at_time;
            }else{
                $actual_pm_out = $scanned_at_time;
            }
        }

        if($actual_am_in != ''){
            $insertSummary->actual_am_in = $actual_am_in;
        }
        if($actual_pm_in != ''){
            $insertSummary->actual_pm_in = $actual_pm_in;
        }
        if($actual_am_out != ''){
            $insertSummary->actual_am_out = $actual_am_out;
        }
        if($actual_pm_out != ''){
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

                /**
                 * 🔍 Load student
                 */
                $student = Student::findOrFail($request->student_id);

                /**
                 * 📦 Snapshot from student
                 */
                $snapshot = [
                    'school_year_id' => $student->school_year_id,
                    'sy_from'        => $student->sy_from,
                    'sy_to'          => $student->sy_to,
                    'level'          => $student->level,
                    'grade'          => $student->grade,
                    'section'        => $student->section,
                    'teachers_id'    => $student->teachers_id,
                ];
                
                /**
                 * ======================
                 * PRESENT
                 * ======================
                 */
                if ($request->status === 'present') {

                    AttendanceDailySummary::updateOrCreate( 
                        [
                            'student_id' => $student->id,
                            'date' => $request->date,
                        ],
                        array_merge($snapshot, [
                            'is_absent'    => null,
                            'is_late'      => $request->is_late ?? 0,
                            'is_undertime' => $request->is_undertime ?? 0,
                            'is_excused'   => $request->is_excused ?? 0,
                            'remarks'      => $request->remarks,
                        ])
                    );

                    // 🗑️ Remove absence record if exists
                    Absence::where('student_id', $student->id)
                        ->where('date', $request->date)
                        ->delete();
                }

                /**
                 * ======================
                 * ABSENT
                 * ======================
                 */
                
                if ($request->status === 'absent') {
                    $checkAttendance = Attendance::where('student_id', $student->id)
                        ->whereDate('scanned_at', date('Y-m-d', strtotime($request->date)))
                        ->first();

                    if($checkAttendance){
                        AttendanceDailySummary::updateOrCreate(
                            [
                                'student_id' => $student->id,
                                'date' => $request->date,
                            ],
                            array_merge($snapshot, [
                                'is_absent'    => 1,
                                'is_late'      => 0,
                                'is_undertime' => 0,
                                'is_excused'   => 0,
                                'remarks'      => $request->remarks,
                            ])
                        );
                    }else{
                        AttendanceDailySummary::where('student_id', $student->id)
                            ->where('date', $request->date)
                            ->delete();
                    }
                    
                    // ➜ Insert absence if not exists
                    Absence::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'date' => $request->date,
                        ]
                    );
                }
            });

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
