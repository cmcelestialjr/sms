<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {

       
        $user = Auth::user();

        $query = Student::query();

        if ($user->role_id == 3) {
            $query->where('teachers_id', $user->id);
        }

        $studentCounts = $query->selectRaw("
            COUNT(CASE WHEN status = 'Active' THEN 1 END) AS active_count,
            COUNT(CASE WHEN status = 'Inactive' THEN 1 END) AS inactive_count,
            COUNT(CASE WHEN status = 'Active' AND sex = 'Male' THEN 1 END) AS active_male_count,
            COUNT(CASE WHEN status = 'Active' AND sex = 'Female' THEN 1 END) AS active_female_count,
            COUNT(CASE WHEN status = 'Inactive' AND sex = 'Male' THEN 1 END) AS inactive_male_count,
            COUNT(CASE WHEN status = 'Inactive' AND sex = 'Female' THEN 1 END) AS inactive_female_count
        ")->first();

        $totalStudentsActive = $studentCounts->active_count;
        $totalStudentsInactive = $studentCounts->inactive_count;
        $totalStudentsMaleActive = $studentCounts->active_male_count;
        $totalStudentsFemaleActive = $studentCounts->active_female_count;
        $totalStudentsMaleInactive = $studentCounts->inactive_male_count;
        $totalStudentsFemaleInactive = $studentCounts->inactive_female_count;

        $query = Teacher::query();

        $teacherCounts = $query->selectRaw("
            COUNT(CASE WHEN status = 'Active' THEN 1 END) AS active_count,
            COUNT(CASE WHEN status = 'Inactive' THEN 1 END) AS inactive_count
        ")->first();

        $totalTeachersActive = $teacherCounts->active_count;
        $totalTeachersInactive = $teacherCounts->inactive_count;

        $studentsPerGrade = Student::select('grade', DB::raw('count(*) as total_students'))
            ->groupBy('grade')
            ->orderBy('grade', 'asc') 
            ->get();


        return response()->json([
            'totalStudentsActive' => $totalStudentsActive,
            'totalStudentsInactive' => $totalStudentsInactive,
            'totalStudentsMaleActive' => $totalStudentsMaleActive,
            'totalStudentsFemaleActive' => $totalStudentsFemaleActive,
            'totalStudentsMaleInactive' => $totalStudentsMaleInactive,
            'totalStudentsFemaleInactive' => $totalStudentsFemaleInactive,
            'totalTeachersActive' => $totalTeachersActive,
            'totalTeachersInactive' => $totalTeachersInactive,
            'studentsPerGrade' => $studentsPerGrade, 
        ]);
    }

    private function dummdyData()
    {
        $students = [1, 2, 3, 4, 5, 17];
        $teacherId = 2;
        $level = 'Elementary';
        $grade = '1';
        $section = 'ADC';
        $schoolYearFrom = 2025;
        $schoolYearTo = 2026;
        $message = 'Success!';
        $status = 'success';
        $messageStatus = 'Success';
        $type = 'In';
        $method = 'rfid';

        // Define weekdays for September 2025
        $dates = [
            '2025-09-01', '2025-09-02', '2025-09-03', '2025-09-04', '2025-09-05', // Week 1
            '2025-09-08', '2025-09-09', '2025-09-10', '2025-09-11', '2025-09-12', // Week 2
            '2025-09-15', '2025-09-16', '2025-09-17', '2025-09-18', '2025-09-19', // Week 3
            '2025-09-22', '2025-09-23', '2025-09-24', '2025-09-25', '2025-09-26', // Week 4
            '2025-09-29', '2025-09-30' // Week 5
        ];

        // Loop over each date and insert a record for each student
        foreach ($dates as $date) {
            foreach ($students as $studentId) {
                // Generate a random time between 7:30 AM and 8:30 AM
                $randomMinute = rand(0, 59);
                $hour = 7 + rand(0, 1); // Either 7 or 8 AM
                $minute = ($randomMinute < 30) ? 30 : $randomMinute; // Ensures time is between 7:30 AM and 8:30 AM
                
                // Format the random time for scanning (scanned_at)
                $randomTime = date('Y-m-d H:i:s', strtotime("$date $hour:$minute:00"));

                // Insert into the database
                DB::table('attendances')->insert([
                    'student_id' => $studentId,
                    'station_id' => 1, // Assuming station_id is random between 1 and 10 (adjust accordingly)
                    'scanned_at' => $randomTime,
                    'type' => $type,
                    'method' => $method,
                    'status' => $status,
                    'message' => $message,
                    'school_year_id' => null, // Nullable
                    'sy_from' => $schoolYearFrom,
                    'sy_to' => $schoolYearTo,
                    'level' => $level,
                    'grade' => $grade,
                    'section' => $section,
                    'teachers_id' => $teacherId,
                    'message_status' => $messageStatus,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

}
