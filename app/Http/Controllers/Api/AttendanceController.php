<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendSmsAttendanceJob;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Station;
use App\Models\Teacher;
use App\Services\SchoolYearServices;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    protected $schoolYearServices;

    public function __construct(SchoolYearServices $schoolYearServices)
    {
        $this->schoolYearServices = $schoolYearServices;
    }

    public function index(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|min:1|exists:stations,id',
            'timeNow' => 'required|string',
        ]);

        // $attendances = Attendance::with('student')
        //     ->where('station_id', $request->id)
        //     ->whereDate('scanned_at',date('Y-m-d',strtotime($request->timeNow)))
        //     ->orderBy('scanned_at','DESC')
        //     ->limit(6)
        //     ->get();

        $attendances = [];

        return response()->json($attendances);
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
                $query->where('type', $type);
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

    public function scan(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'code' => 'required|string',
            'api_key' => 'required|string',
        ]);

        if ($request->api_key !== env('ATTENDANCE_API_KEY')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access. Invalid API key.',
            ], 403);
        }

        // Find the station by IP address
        $station = Station::find($request->id);

        if (!$station) {
            return response()->json([
                'success' => false,
                'message' => 'Station not registered.',
            ]);
        }

        // Detect method based on code
        $method = str_starts_with($request->code, 'qr-') ? 'qr' : 'rfid';

        // If QR code, remove the 'qr-' prefix
        $searchCode = $method === 'qr' 
            ? substr($request->code, 3) 
            : $request->code;

        // Find the student
        $student = Student::where($method === 'qr' ? 'qr_code' : 'rfid_tag', $searchCode)->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found.',
            ]);
        }

        // Check for duplicate scan within 30 minutes
        $recentScan = Attendance::where('student_id', $student->id)
            ->where('station_id', $station->id)
            ->where('scanned_at', '>=', Carbon::now()->subMinutes(30))
            ->first();

        if ($recentScan) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate scan detected. Please wait 30 minutes before scanning again.',
            ]);
        }

        $scanned_at = now();

        $student = $this->updateStudent($student);

        $getType = $this->getType($student,$scanned_at);

        $status = $getType['result'];
        $message = $getType['message'];
        $message_type = $getType['message_type'];
        $type = $getType['type'];
        
        // Record attendance
        $createAttendance = Attendance::create([
            'student_id' => $student->id,
            'station_id' => $station->id,
            'type' => $type,
            'method' => $method,
            'status' => $status,
            'message' => $message,
            'scanned_at' => $scanned_at,
            'sy_from' => $student->sy_from,
            'sy_to' => $student->sy_to,
            'level' => $student->level,
            'grade' => $student->grade,
            'section' => $student->section,
            'teachers_id' => $student->teachers_id
        ]);

        $attendance = Attendance::with('student')->where('id',$createAttendance->id)->first();

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
        
        $message = $name." ".$message_type." in Alangalang National School at " . date('h:i:s A', strtotime($scanned_at)). " on " . date('M d, Y', strtotime($scanned_at));
        
        $message = str_replace(" ","_",$message);

        dispatch(new SendSmsAttendanceJob($target_id, $contact_no, $message));

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
            'attendances' => $attendances
        ]);
    }

    private function updateStudent($student)
    {
        $getSchoolYear = $this->schoolYearServices->getSchoolYear();
        $sy_from = $getSchoolYear['sy_from'];
        $sy_to = $getSchoolYear['sy_to'];

        $student = Student::find($student->id);
        $student->sy_from = $sy_from;
        $student->sy_to = $sy_to;
        $student->status = 'Active';
        $student->save();
        
        $teacher = Teacher::where('user_id',$student->teachers_id)
            ->first();

        if($teacher!=null){
            $teacher = Teacher::find($teacher->id);
            $teacher->sy_from = $sy_from;
            $teacher->sy_to = $sy_to;
            $teacher->save();
        }
        
        return $student;
    }

    private function getType($student,$scanned_at)
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
}
