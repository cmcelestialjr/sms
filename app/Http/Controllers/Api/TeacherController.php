<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolYearStudent;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\SchoolYearServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TeacherController extends Controller
{
    protected $schoolYearServices;

    public function __construct(SchoolYearServices $schoolYearServices)
    {
        $this->schoolYearServices = $schoolYearServices;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_no' => 'required|string|max:255|unique:users,id_no',
            'lastname' => 'required|string|max:255',
            'firstname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'extname' => 'nullable|string|max:255',
            'contact_no' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'sy_from' => 'nullable|integer',
            'sy_to' => 'nullable|integer',
            'level' => 'nullable|string|max:255',
            'grade' => 'nullable|string|max:255',
            'section' => 'nullable|string|max:255',
            'teachers_id' => 'nullable|integer|exists:users,id', 
            'user_id' => 'nullable|integer|exists:users,id',
            'status' => 'required|in:Active,Inactive',
            'contact_no' => 'required|regex:/^09\d{9}$/',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try{ 
            
            $getSchoolYear = $this->schoolYearServices->getSchoolYear();
            $validated['school_year_id'] = $getSchoolYear['school_year_id'];
            $validated['sy_from'] = $getSchoolYear['sy_from'];
            $validated['sy_to'] = $getSchoolYear['sy_to'];

            if ($request->hasFile('newPhoto')) {
                $request->validate([
                    'newPhoto' => 'required|image',
                ]);
                $validated['photo'] = $request->file('newPhoto')->store('teachers', 'public');
            }

            $teacher = Teacher::create($validated);

            DB::commit();

            return response()->json($teacher, 201);
        
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating teacher.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);

        $validated = $request->validate([
            'lastname' => 'required|string|max:255',
            'firstname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'extname' => 'nullable|string|max:255',
            'contact_no' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'sy_from' => 'nullable|integer',
            'sy_to' => 'nullable|integer',
            'level' => 'nullable|string|max:255',
            'grade' => 'nullable|string|max:255',
            'section' => 'nullable|string|max:255',
            'teachers_id' => 'nullable|integer|exists:users,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'status' => 'required|in:Active,Inactive'
        ]);

        DB::beginTransaction();

        try{    

            if($validated['status']=='Active'){
                $getSchoolYear = $this->schoolYearServices->getSchoolYear();
                $validated['school_year_id'] = $getSchoolYear['school_year_id'];
                $validated['sy_from'] = $getSchoolYear['sy_from'];
                $validated['sy_to'] = $getSchoolYear['sy_to'];
            }

            $teacher->update($validated);

            DB::commit();

            return response()->json($teacher);
        
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating teacher.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function statusTotal(Request $request)
    {
        $totals = DB::table('teachers')
                ->select('status', DB::raw('count(*) as total'));

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $totals->where(function ($q) use ($search) {
                $q->where('firstname', 'like', "%$search%")
                ->orWhere('lastname', 'like', "%$search%")
                ->orWhere('middlename', 'like', "%$search%");
            });
        }

        $totals = $totals->groupBy('status')
                ->get();

        $totalActive = $totals->firstWhere('status', 'Active')->total ?? 0;
        $totalInActive = $totals->firstWhere('status', 'Inactive')->total ?? 0;

        return response()->json([
            'totalActiveResponse' => $totalActive,
            'totalInActiveResponse' => $totalInActive,
        ]);
    }

    public function transfer(Request $request)
    {
        
        $validated = $request->validate([
            'id' => 'required|integer|exists:users,id',
            'students' => 'required|string', 
        ]);

        $studentsArray = explode(',', $request->students);
        
        if (count($studentsArray) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'At least one student ID is required.',
            ], 400);
        }

        $validatedStudents = array_map('intval', $studentsArray);

        $teacher = Teacher::where('user_id',$validated['id'])->first();

        $getSchoolYear = $this->schoolYearServices->getSchoolYear();
        $school_year_id = $getSchoolYear['school_year_id'];
        $sy_from = $getSchoolYear['sy_from'];
        $sy_to = $getSchoolYear['sy_to'];

        Student::whereIn('id', $validatedStudents)
            ->update(['teachers_id' => $validated['id'],
                'school_year_id' => $school_year_id,
                'sy_from' => $sy_from,
                'sy_to' => $sy_to,
                'level' => $teacher->level,
                'grade' => $teacher->grade,
                'section' => $teacher->section,
                'status' => 'Active'
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Success',
        ], 200);
    }

    private function updateSchoolYearStudent($student)
    {
        $check = SchoolYearStudent::where('student_id',$student->id)
            ->where('sy_from',$student->sy_from)
            ->where('sy_to',$student->sy_to)
            ->where('level',$student->level)
            ->first();
        if($check){
            $update = SchoolYearStudent::find($check->id);
        }else{
            $update = new SchoolYearStudent();
            $update->student_id = $student->id;
            $update->school_year_id = $student->school_year_id;
            $update->sy_from = $student->sy_from;
            $update->sy_to = $student->sy_to;
            $update->level = $student->level;
        }
        $update->grade = $student->grade;
        $update->section = $student->section;
        $update->teacher_id = $student->teachers_id;
        $update->save();
    }

}
