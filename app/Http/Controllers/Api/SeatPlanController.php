<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClassLayout;
use App\Models\SchoolYearStudent;
use App\Models\SeatAssignment;
use App\Models\Teacher; // Ensure Teacher is imported
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SeatPlanController extends Controller
{
    // 1. NEW METHOD: Fetch teachers ONLY for Admins/Principals
    public function getTeachers(Request $request)
    {
        // 1. Validate that we are passing a school year from the frontend
        $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
        ]);

        $user = Auth::user();
        
        // Roles 1 & 2 (Admins/Principals)
        if (in_array($user->role_id, [1, 2])) {
            
            // Fetch Teachers who have AT LEAST ONE student assigned to them in this specific school year
            $teachers = Teacher::whereHas('schoolYearStudents', function ($query) use ($request) {
                    $query->where('school_year_id', $request->school_year_id);
                })
                ->where('status', 'Active') // Optional: only active teachers
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

    // 2. UPDATED: Fetch classes based on logged-in teacher OR selected teacher (if Admin)
    public function getTeacherClasses(Request $request)
    {
        $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'teacher_id' => 'nullable|exists:users,id' // Admin passes this
        ]);

        $user = Auth::user();
        $query = SchoolYearStudent::where('school_year_id', $request->school_year_id);

        if ($user->role_id == 3) {
            $query->where('teacher_id', $user->id); // Force teacher to see only their own
        } elseif ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id); // Admin filters by selected teacher
        }

        $classes = $query->select('level', 'grade', 'section')
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

    // 3. UPDATED: Fetch layout for the target teacher
    public function getLayout(Request $request)
    {
        $request->validate([
            'school_year_id' => 'required|exists:school_years,id',
            'level' => 'required|string',
            'grade' => 'required|string',
            'section' => 'required|string',
            'teacher_id' => 'nullable'
        ]);

        $user = Auth::user();
        
        // Determine whose layout to load
        $targetTeacherId = ($user->role_id == 3) ? $user->id : $request->teacher_id;

        if (!$targetTeacherId) {
            return response()->json(['success' => false, 'message' => 'Please select a teacher.'], 400);
        }

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

    // 4. UPDATED: Save layout for the target teacher
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
            'teacher_id' => 'nullable'
        ]);

        $user = Auth::user();
        
        // Determine whose layout is being saved
        $targetTeacherId = ($user->role_id == 3) ? $user->id : $request->teacher_id;

        if (!$targetTeacherId) {
            return response()->json(['success' => false, 'message' => 'Teacher ID is required.'], 400);
        }

        DB::transaction(function () use ($request, $targetTeacherId) {
            $layout = ClassLayout::updateOrCreate(
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

            if (!empty($request->assignments)) {
                $assignmentData = [];
                foreach ($request->assignments as $seat) {
                    $assignmentData[] = [
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
}