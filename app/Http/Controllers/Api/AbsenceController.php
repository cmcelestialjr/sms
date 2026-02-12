<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\AbsencesJob;
use App\Jobs\SendSmsAttendanceJob;
use App\Jobs\SendSmsAttendanceJob1;
use App\Models\Attendance;
use App\Models\DeviceSequence;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\Station;
use App\Models\Teacher;
use App\Models\User;
use App\Services\SchoolYearServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;

class AbsenceController extends Controller
{
    protected $schoolYearServices;

    public function __construct(SchoolYearServices $schoolYearServices)
    {
        $this->schoolYearServices = $schoolYearServices;
    }

    public function index(Request $request)
    {
        $search = $request->query('search');
        $grade = $request->query('grade') == "" ? null : $request->query('grade');
        $section = $request->query('section') == "" ? null : $request->query('section');
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');
        $school_year_id = $request->query('schoolYear');
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        
        $role_id = $user->role_id;
        $user_id = $user->id;
        
        if($startDate && $endDate){
            $startDate = date('Y-m-d', strtotime($startDate));
            $endDate = date('Y-m-d', strtotime($endDate));
        }else{
            $startDate = date('Y-01-01');
            $endDate = date('Y-12-31');
        }
        

        $absences = Student::with(['schoolYearStudents' => function ($q) use ($school_year_id, $role_id, $user_id, $grade, $section) {
                $q->where('school_year_id', $school_year_id);

                if ($role_id == 3) {
                    $q->where('teacher_id', $user_id);
                }
                if ($grade) {
                    $q->where('grade', $grade);
                }
                if ($section) {
                    $q->where('section', $section);
                }
            },
            'absences' => function ($q) use ($startDate, $endDate) {
                $q->whereBetween('date',[$startDate,$endDate]);
            }])
        ->whereHas('absences', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('date',[$startDate,$endDate]);
        })
        ->whereHas('schoolYearStudents', function ($q) use ($school_year_id, $role_id, $user_id, $grade, $section) {
            $q->where('school_year_id', $school_year_id);

            if ($role_id == 3) {
                $q->where('teacher_id', $user_id);
            }
            if ($grade) {
                $q->where('grade', $grade);
            }
            if ($section) {
                $q->where('section', $section);
            }
        })
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'like', "%$search%")
                ->orWhere('lastname', 'like', "%$search%")
                ->orWhere('student_id', 'like', "%$search%");
            });
        })
        ->orderBy('lastname')->paginate(10);
        
        return response()->json([
            'data' => $absences->items(),
            'meta' => [
                'current_page' => $absences->currentPage(),
                'last_page' => $absences->lastPage(),
                'prev' => $absences->previousPageUrl(),
                'next' => $absences->nextPageUrl(),
            ]
        ]);
    }
}
