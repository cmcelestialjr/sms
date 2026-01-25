<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendSmsNewStudentJob;
use App\Models\Attendance;
use App\Models\SchoolYear;
use App\Models\SchoolYearStudent;
use App\Models\Student;
use App\Models\Teacher;
use App\Services\SchoolYearServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StudentController extends Controller
{
    protected $schoolYearServices;

    public function __construct(SchoolYearServices $schoolYearServices)
    {
        $this->schoolYearServices = $schoolYearServices;
    }

    public function index(Request $request)
    {
        $search = $request->query('search');

        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $students = Student::with('teacher')
            ->when($search, function ($query, $search) {
            $query->where('firstname', 'like', "%$search%")
                ->orWhere('lastname', 'like', "%$search%")
                ->orWhere('student_id', 'like', "%$search%");
        });

        if($user->role_id==3){
            if ($request->has('status')){
                if($request->status=='Approved'){
                    $students->where('teacher_id_approved',$user->id);
                }elseif($request->status=='Requested'){
                    $students->where('teacher_id_requested',$user->id);
                }elseif($request->status=='Pending'){
                    $students->where('teacher_id_pending',$user->id);
                }else{
                    $students->where('teachers_id',$user->id);
                    $status = $request->status;
                    $students->where('status', $status);
                }
            }
        }else{
            if ($request->has('status')){
                $status = $request->status;
                $students->where('status', $status);
            }
        }
        
        $students = $students->orderBy('lastname')->paginate(10);

        $students->getCollection()->transform(function ($student) {
            $student->photo = $student->photo ? asset("storage/$student->photo") : asset('images/no-image-icon.png');
            return $student;
        });

        return response()->json([
            'data' => $students->items(),
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'prev' => $students->previousPageUrl(),
                'next' => $students->nextPageUrl(),
            ]
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->get('search');

        $user = Auth::user();

        $students = Student::where(function($q) use ($query) {
            $q->where('lastname', 'like', "%{$query}%")
            ->orWhere('firstname', 'like', "%{$query}%")
            ->orWhere('middlename', 'like', "%{$query}%")
            ->orWhere('student_id', 'like', "%{$query}%");
        });
        if($request->has('f')){
            if($user->role_id==3 && $request->f=='message'){
                $students->where('teachers_id',$user->id);
            }
        }        

        $students = $students->limit(10)
            ->get();

        return $students;
    }

    public function store(Request $request)
    {
        $user = Auth::user();
            
        $request->validate([
            'search_student_id' => 'nullable|integer|exists:students,id'
        ]);

        if($request->search_student_id){
                
            $studentInfo = $this->updateStudent($request, $request->search_student_id, 'store');

            return $studentInfo;
        }

        $validated = $request->validate([
            'rfid_tag' => 'nullable|string',
            'qr_code' => 'nullable|string',
            'lrn_no' => 'required|string',
            // 'student_id' => 'required|unique:students',
            'lastname' => 'required|string',
            'firstname' => 'required|string',
            'middlename' => 'nullable|string',
            'extname' => 'nullable|string',
            'sex' => 'required|in:Male,Female',
            'contact_no' => 'required|regex:/^09\d{9}$/',
            'birthdate' => 'nullable|date',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
            'status' => 'required|in:Active,Inactive'
        ]);

        DB::beginTransaction();

        try {           

            // $student_id = $this->getStudentId();

            // $validated['student_id'] = $student_id;
            $validated['rfid_tag'] = $request->rfid_tag=='null' ? null : $request->rfid_tag;
            $validated['qr_code'] = $request->qr_code=='null' ? null : $request->qr_code;        
            $validated['middlename'] = $request->middlename=='null' ? null : $request->middlename;
            $validated['extname'] = $request->extname=='null' ? null : $request->extname;
            $validated['email'] = $request->email=='null' ? null : $request->email;
            $validated['address'] = $request->address=='null' ? null : $request->address;

            if ($request->hasFile('newPhoto')) {
                $request->validate([
                    'newPhoto' => 'required|image',
                ]);
                $validated['photo'] = $request->file('newPhoto')->store('students', 'public');
            }

            if($user->role_id<3){
                $request->validate([
                    'teachers_id' => 'required|integer|exists:users,id',
                ]);
                $teachers_id = $request->teachers_id;
                $validated['teachers_id'] = $teachers_id;
            }else{            
                $teachers_id = $user->id;
            }
            
            $teacher = Teacher::where('user_id',$teachers_id)->first();
            if($teacher){
                $level = $teacher->level;
                $grade = $teacher->grade;
                $section = $teacher->section;
            }else{
                $level = 'Elementary';
                $grade = NULL;
                $section = NULL;
            }

            $getSchoolYear = $this->schoolYearServices->getSchoolYear();
            $validated['school_year_id'] = $getSchoolYear['school_year_id'];
            $validated['sy_from'] = $getSchoolYear['sy_from'];
            $validated['sy_to'] = $getSchoolYear['sy_to'];        

            $validated['status'] = 'Active';
            $validated['level'] = $level;
            $validated['grade'] = $grade;
            $validated['section'] = $section;
            $validated['teachers_id'] = $teachers_id;

            $student = Student::create($validated);

            $this->updateSchoolYearStudent($student, 'store');

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

            $message = $name." is currently enrolled in Alangalang National School. Thank you.";
            
            $message = str_replace(" ","_",$message);

            dispatch(new SendSmsNewStudentJob($student->contact_no, $message));

            DB::commit();
            return $student;

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Failed to save student record.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try{            
            $student = $this->updateStudent($request, $id, 'update');
            DB::commit();
            return response()->json([
                'success' => true,
                'data' => $student
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Duplicate LRN',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function updateStudent($request, $id, $option)
    {
        $user = Auth::user();

        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'rfid_tag' => 'nullable|string',
            'qr_code' => 'nullable|string',
            'student_id' => 'required|unique:students,student_id,' . $id,
            'lastname' => 'required|string',
            'firstname' => 'required|string',
            'middlename' => 'nullable|string',
            'extname' => 'nullable|string',
            'sex' => 'required|in:Male,Female',
            'contact_no' => 'required|regex:/^09\d{9}$/',
            'birthdate' => 'nullable|date',
            'email' => 'nullable|string',
            'address' => 'nullable|string',
            'lrn_no' => 'required|string',
            'status' => 'required|in:Active,Inactive'
        ]);
        
        $validated['rfid_tag'] = $request->rfid_tag=='null' ? null : $request->rfid_tag;
        $validated['qr_code'] = $request->qr_code=='null' ? null : $request->qr_code;        
        $validated['middlename'] = $request->middlename=='null' ? null : $request->middlename;
        $validated['extname'] = $request->extname=='null' ? null : $request->extname;
        $validated['email'] = $request->email=='null' ? null : $request->email;
        $validated['address'] = $request->address=='null' ? null : $request->address;

        if ($request->hasFile('newPhoto')) {
            $request->validate([
                'newPhoto' => 'required|image',
            ]);
            if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                Storage::disk('public')->delete($student->photo);
            }
            $validated['photo'] = $request->file('newPhoto')->store('students', 'public');
        }

        if($validated['status']=='Active'){
            $getSchoolYear = $this->schoolYearServices->getSchoolYear();
            $validated['sy_from'] = $getSchoolYear['sy_from'];
            $validated['sy_to'] = $getSchoolYear['sy_to'];
        }

        if($user->role_id<3){      
            $request->validate([
                'teachers_id' => 'required|integer|exists:users,id',
            ]);
            
            $teacher = Teacher::where('user_id',$request->teachers_id)->first();
            
            if($teacher){
                $validated['teachers_id'] = $request->teachers_id;
                $validated['level'] = $teacher->level;
                $validated['grade'] = $teacher->grade;
                $validated['section'] = $teacher->section;
            }            
        }

        if($option=='store' && $user->role_id==3){
            $validated['teacher_id_pending'] = $student->teachers_id;
            $validated['teacher_id_requested'] = $user->id;
        }
        
        $student->update($validated);

        $this->updateSchoolYearStudent($student, 'update');

        return $student;
    }

    public function statusTotal(Request $request)
    {
        $user = Auth::user();

        $totals = DB::table('students')
                ->select('status', DB::raw('count(*) as total'));
                
        if($user->role_id==3){
            $totals->where('teachers_id',$user->id);
        }
        
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $totals->where(function ($q) use ($search) {
                $q->where('firstname', 'like', "%$search%")
                ->orWhere('lastname', 'like', "%$search%")
                ->orWhere('student_id', 'like', "%$search%");
            });
        }

        $totals = $totals->groupBy('status')
                ->get();

        $totalActive = $totals->firstWhere('status', 'Active')->total ?? 0;
        $totalInActive = $totals->firstWhere('status', 'Inactive')->total ?? 0;
        
        $totalApproved = Student::where('teacher_id_approved',$user->id)->count();
        $totalRequested = Student::where('teacher_id_requested',$user->id)->count();
        $totalPending = Student::where('teacher_id_pending',$user->id)->count();

        return response()->json([
            'totalActiveResponse' => $totalActive,
            'totalInActiveResponse' => $totalInActive,
            'totalApprovedResponse' => $totalApproved,
            'totalRequestedResponse' => $totalRequested,
            'totalPendingResponse' => $totalPending,
        ]);
    }

    public function approved(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:students,id', 
        ]);

        $user = Auth::user();

        $id = $validated['id'];

        $student = Student::findOrFail($id);
        $teacher_id_requested = $student->teacher_id_requested;

        $teacher = Teacher::where('user_id',$teacher_id_requested)->first();

        $getSchoolYear = $this->schoolYearServices->getSchoolYear();
        $sy_from = $getSchoolYear['sy_from'];
        $sy_to = $getSchoolYear['sy_to'];

        $student->sy_from = $teacher->sy_from;
        $student->sy_to = $teacher->sy_to;
        $student->level = $teacher->level;
        $student->grade = $teacher->grade;
        $student->section = $teacher->section;
        $student->teacher_id_approved = $user->id;
        $student->teachers_id = $teacher_id_requested;
        $student->teacher_id_requested = 0;
        $student->teacher_id_pending = 0;
        $student->status = 'Active';
        $student->save();

        return response()->json(['message' => 'Success', 200]);
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);

        $check = Attendance::where('student_id',$id)->first();

        if($check){
            return response()->json(['message' => 'Error! Student already have attendace.']);
        }

        SchoolYearStudent::where('student_id',$id)->delete();

        $student->delete();
        return response()->json(['message' => 'Deleted']);
    }

    private function getStudentId()
    {
        $year = now()->format('Y');

        $query = Student::where('student_id', 'LIKE', "$year-%")->orderByDesc('student_id')->first();

        $number = $query && preg_match('/\d{4}-(\d+)/', $query->student_id, $matches) ? intval($matches[1]) + 1 : 1;

        $student_id = "$year" . str_pad($number, 5, '0', STR_PAD_LEFT);

        return $student_id;
    }

    private function updateSchoolYearStudent($student, $from)
    {
        $check = SchoolYearStudent::where('student_id',$student->id)
            ->where('school_year_id',$student->school_year_id)
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
            $update->date_enrolled = date('Y-m-d');
            $update->is_transferred = 0;
        }
        $update->grade = $student->grade;
        $update->section = $student->section;
        $update->teacher_id = $student->teachers_id;
        $update->status = $student->status;

        if($from!='update' && $from!='store'){
            $update->date_enrolled = $student->date_enrolled;
            $update->is_transferred = $student->is_transferred;
        }
        
        $update->save();
    }
}
