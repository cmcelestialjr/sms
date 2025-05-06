<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|min:1|exists:stations,id',
            'timeNow' => 'required|string',
        ]);

        $attendances = Attendance::with('student')
            ->where('station_id', $request->id)
            ->whereDate('scanned_at',date('Y-m-d',strtotime($request->timeNow)))
            ->orderBy('scanned_at','DESC')
            ->limit(6)
            ->get();

        return response()->json($attendances);
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
            ], 403); // Forbidden
        }

        // Find the station by IP address
        $station = Station::find($request->id);

        if (!$station) {
            return response()->json([
                'success' => false,
                'message' => 'Station not registered.',
            ], 404);
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
            ], 404);
        }

        // Check for duplicate scan within 30 seconds
        $recentScan = Attendance::where('student_id', $student->id)
            ->where('station_id', $station->id)
            ->where('scanned_at', '>=', Carbon::now()->subSeconds(60))
            ->first();

        if ($recentScan) {
            return response()->json([
                'success' => false,
                'message' => 'Duplicate scan detected. Please wait before scanning again.',
            ]);
        }

        $scanned_at = now();

        $getType = $this->getType($student,$scanned_at);

        $status = $getType['result'];
        $message = $getType['message'];
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
        ]);

        $attendance = Attendance::with('student')->where('id',$createAttendance->id)->first();

        $attendances = Attendance::with('student')
            ->whereNotIn('id',[$createAttendance->id])
            ->where('station_id', $station->id)
            ->whereDate('scanned_at',date('Y-m-d',strtotime($scanned_at)))
            ->orderBy('scanned_at','DESC')
            ->limit(6)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Successful!',
            'student' => $attendance,
            'attendances' => $attendances
        ]);
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
                'message' => 'Success!'
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
                'message' => 'Exceed (4 Logs)'
            ];
        }
        
        if($todayScan->type=="In"){
            return [
                'result' => 'success',
                'type' => 'Out',
                'message' => 'Success!'
            ];
        }
        else{
            return [
                'result' => 'success',
                'type' => 'In',
                'message' => 'Success!'
            ];
        }

    }
}
