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
            $userId = $user->id;
            $query->where(function ($q) use ($userId) {
                $q->where('teachers_id', $userId)
                    ->orWhereHas('schoolYearStudents', function ($sq) use ($userId) {
                        $sq->whereColumn('school_year_students.school_year_id', 'students.school_year_id')
                            ->where('co_teacher_id', $userId);
                    });
            });
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

        $teacherQuery = Teacher::query();

        $teacherCounts = $teacherQuery->selectRaw("
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
}