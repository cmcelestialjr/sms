<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassLayout;
use App\Models\SchoolYearStudent;
use App\Models\SeatAssignment;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SeatPlanController extends Controller
{
    // 1. Fetch teachers ONLY for Admins/Principals
    public function getTeachers(Request $request)
    {
        $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
        ]);

        $user = Auth::user();

        // Roles 1 & 2 (Admins/Principals)
        if (in_array($user->role_id, [1, 2])) {
            // Fetch main advisers who have at least one student assigned in this school year
            // (Keeps one entry per section in the admin dropdown; co-advisers share the same layout)
            $teachers = Teacher::whereHas('schoolYearStudents', function ($query) use ($request) {
                    $query->where('school_year_id',$request->school_year_id);
                })
                ->where('status', 'Active')
                ->orderBy('lastname', 'ASC')
                ->orderBy('firstname', 'ASC')
                ->get(['user_id', 'lastname', 'firstname']);

            return response()->json([
                'success' => true,
                'role' => $user->role_id,
                'data' => $teachers
            ]);
        }

        // Return empty array for Teachers (Role 3) so the frontend hides the dropdown
        return response()->json([
            'success' => true,
            'role' => $user->role_id,
            'data' => []
        ]);
    }

    // 2. Fetch classes based on logged-in teacher/co-adviser OR selected teacher (if Admin)
    public function getTeacherClasses(Request $request)
    {
        $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'teacher_id' => 'nullable|exists:users,id'
        ]);

        $user = Auth::user();
        $query = SchoolYearStudent::where('school_year_id',$request->school_year_id);

        if ($user->role_id == 3) {
            $userId =$user->id;
            $query->where(function ($q) use ($userId) {
                $q->where('teacher_id',$userId)
                    ->orWhere('co_teacher_id', $userId);
            });
        } elseif ($request->filled('teacher_id')) {
            $selectedTeacherId =$request->teacher_id;
            $query->where(function ($q) use ($selectedTeacherId) {
                $q->where('teacher_id',$selectedTeacherId)
                    ->orWhere('co_teacher_id', $selectedTeacherId);
            });
        }

        $classes =$query->select('level', 'grade', 'section')
            ->distinct()
            ->orderBy('level', 'ASC')
            ->orderBy('grade', 'ASC')
            ->orderBy('section', 'ASC')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $classes
        ]);
    }

    // 3. Fetch shared layout for the class (resolving co-adviser to main adviser)
    public function getLayout(Request $request)
    {
        $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'level' => 'required|string',
            'grade' => 'required|string',
            'section' => 'required|string',
            'teacher_id' => 'nullable|exists:users,id'
        ]);

        $user = Auth::user();

        $rawTeacherId = ($user->role_id == 3) ? $user->id :$request->teacher_id;

        if (!$rawTeacherId) {
            return response()->json(['success' => false, 'message' => 'Please select a teacher.'], 400);
        }

        $targetTeacherId =$this->resolveMainTeacherId(
            (int) $rawTeacherId,
            (int) $request->school_year_id,$request->level,
            $request->grade,$request->section
        );

        $layout = ClassLayout::with('seatAssignments.student')
            ->firstOrCreate(
                [
                    'teacher_id' => $targetTeacherId,
                    'school_year_id' => $request->school_year_id,
                    'level' => $request->level,
                    'grade' => $request->grade,
                    'section' => $request->section,
                ],
                [
                    'total_rows' => 5,
                    'total_cols' => 10,
                ]
            );

        return response()->json([
            'success' => true,
            'layout' => $layout
        ]);
    }

    // 4. Save shared layout for the class (resolving co-adviser to main adviser)
    public function saveLayout(Request $request)
    {
        $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'level' => 'required|string',
            'grade' => 'required|string',
            'section' => 'required|string',
            'total_rows' => 'required|integer|min:1',
            'total_cols' => 'required|integer|min:1',
            'assignments' => 'array',
            'teacher_id' => 'nullable|exists:users,id'
        ]);

        $user = Auth::user();

        $rawTeacherId = ($user->role_id == 3) ? $user->id :$request->teacher_id;

        if (!$rawTeacherId) {
            return response()->json(['success' => false, 'message' => 'Teacher ID is required.'], 400);
        }

        $targetTeacherId =$this->resolveMainTeacherId(
            (int) $rawTeacherId,
            (int) $request->school_year_id,$request->level,
            $request->grade,$request->section
        );

        DB::transaction(function () use ($request, $targetTeacherId) {$layout = ClassLayout::updateOrCreate(
                [
                    'teacher_id' => $targetTeacherId,
                    'school_year_id' => $request->school_year_id,
                    'level' => $request->level,
                    'grade' => $request->grade,
                    'section' => $request->section,
                ],
                [
                    'total_rows' => $request->total_rows,
                    'total_cols' => $request->total_cols,
                ]
            );

            SeatAssignment::where('class_layout_id', $layout->id)->delete();

            if (!empty($request->assignments)) {$assignmentData = [];
                foreach ($request->assignments as $seat) {$assignmentData[] = [
                        'class_layout_id' => $layout->id,
                        'student_id' => $seat['student_id'],
                        'row_position' => $seat['row'],
                        'col_position' => $seat['col'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                SeatAssignment::insert($assignmentData);
            }
        });

        return response()->json(['success' => true, 'message' => 'Seating plan saved successfully!']);
    }

    /**
     * Resolve a co-adviser's user_id to the class's main adviser user_id so both
     * advisers always share the same ClassLayout record.
     */
    private function resolveMainTeacherId(
        int $teacherUserId,
        int $schoolYearId,
        string $level,
        string $grade,
        string $section
    ): int {
        $enrollment = SchoolYearStudent::where('school_year_id',$schoolYearId)
            ->where('level', $level)
            ->where('grade', $grade)
            ->where('section', $section)
            ->where(function ($q) use ($teacherUserId) {
                $q->where('teacher_id',$teacherUserId)
                    ->orWhere('co_teacher_id', $teacherUserId);
            })
            ->first(['teacher_id']);

        if ($enrollment &&$enrollment->teacher_id) {
            return (int) $enrollment->teacher_id;
        }

        $teacher = Teacher::where('user_id',$teacherUserId)->first();
        if ($teacher && (int)$teacher->co_adviser === 1) {
            $mainTeacher = Teacher::where('school_year_id',$schoolYearId)
                ->where('level', $level)
                ->where('grade', $grade)
                ->where('section', $section)
                ->where(function ($q) {$q->whereNull('co_adviser')->orWhere('co_adviser', 0);
                })
                ->first(['user_id']);

            if ($mainTeacher) {
                return (int) $mainTeacher->user_id;
            }
        }

        return $teacherUserId;
    }
}