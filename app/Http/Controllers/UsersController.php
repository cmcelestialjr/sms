<?php

namespace App\Http\Controllers;

use App\Models\SchoolYear;
use App\Models\SchoolYearStudent;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use App\Models\UsersRole;
use App\Services\SchoolYearServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    protected $schoolYearServices;

    public function __construct(SchoolYearServices $schoolYearServices)
    {
        $this->schoolYearServices = $schoolYearServices;
    }

    public function index(Request $request)
    {
        $query = User::with('userRole','teacher');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('username', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->paginate(10);

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'prev' => $users->previousPageUrl(),
                'next' => $users->nextPageUrl(),
            ]
        ]);
    }

    public function roles()
    {
        $query = UsersRole::get();

        return response()->json([
            'data' => $query,
        ]);
    }

    public function teachers(Request $request)
    {
        $query = User::with('userRole','teacher.activestudents','students')
            ->whereHas('teacher');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('username', 'LIKE', "%{$search}%")
                    ->orWhereHas('teacher', function ($q) use ($search) {
                        $q->where(function ($q2) use ($search) {
                            $q2->where('lastname', 'LIKE', "%{$search}%")
                            ->orWhere('firstname', 'LIKE', "%{$search}%")
                            ->orWhere('middlename', 'LIKE', "%{$search}%")
                            ->orWhere('grade', 'LIKE', "%{$search}%")
                            ->orWhere('section', 'LIKE', "%{$search}%");
                        });
                    });
            });
        }

        if ($request->has('status')){
            $status = $request->status;
            $query->whereHas('teacher', function ($q) use ($status) {
                $q->where('status', $status);
            });
        }

        if($request->has('schoolYear') && !empty($request->schoolYear)) {
            $schoolYear = $request->schoolYear;
            $query->whereHas('teacher', function ($q) use ($schoolYear) {
                $q->where('school_year_id', $schoolYear);
            });
        }

        $users = $query->paginate(10);

        $users->getCollection()->transform(function ($teacher) {
            $teacher->teacher->photo = $teacher->teacher->photo ? asset("storage/".$teacher->teacher->photo) : asset('images/no-image-icon.png');
            return $teacher;
        });

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'prev' => $users->previousPageUrl(),
                'next' => $users->nextPageUrl(),
            ]
        ]);
    }

    public function teachersSearch(Request $request)
    {
        $query = $request->get('search');

        $teachers = User::where('name', 'LIKE', "%{$query}%")
            ->orWhereHas('teacher', function ($q) use ($query) {
                $q->where(function ($q2) use ($query) {
                    $q2->where('lastname', 'LIKE', "%{$query}%")
                    ->orWhere('firstname', 'LIKE', "%{$query}%")
                    ->orWhere('middlename', 'LIKE', "%{$query}%");
                });
            });
        // if($request->has('schoolYear') && !empty($request->schoolYear)) {
        //     $schoolYear = $request->schoolYear;
        //     $teachers->where('school_year_id', $schoolYear);
        // }
        
        $teachers = $teachers->limit(10)
            ->get();
        return $teachers;
    }

    public function store(Request $request)
    {
        // 1. Authenticate Request
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // 2. Consolidate Validation Rules
        $rules = [
            'lastname'   => 'required|string',
            'firstname'  => 'required|string',
            'extname'    => 'nullable|string',
            'middlename' => 'nullable|string',
            'username'   => 'required|string|unique:users',
            'password'   => 'required|string',
            'role'       => 'required|numeric|exists:users_roles,id',
        ];

        if ($request->hasFile('newPhoto')) {
            $rules['newPhoto'] = 'required|image';
        }

        // Append Teacher-specific rules if role is 3
        if ($request->role == 3) {
            $rules = array_merge($rules, [
                'id_no'          => 'required|string|unique:teachers',
                'status'         => 'required|in:Active,Inactive',
                'contact_no'     => 'required|regex:/^09\d{9}$/',
                'email'          => 'nullable|email',
                'address'        => 'nullable|string',
                'sex'            => 'required|in:Male,Female',
                'position'       => 'nullable|string',
                'level'          => 'required|in:Kinder,Elementary,Junior High School,Senior High School',
                'grade'          => 'required',
                'section'        => 'required',
                'school_year_id' => 'required|integer|exists:school_years,id',
                'co_adviser'     => 'nullable|boolean',
            ]);
        }

        $request->validate($rules);

        // 3. Handle File Upload
        if ($request->hasFile('newPhoto')) {
            $photo = $request->file('newPhoto')->store('teachers', 'public');
        } else {
            $photo = $request->photo;
        }
        
        // Normalize photo value
        $photo = $photo === 'null' ? null : $photo;

        // 4. Database Transaction
        DB::beginTransaction();

        try {
            $name = trim($request->lastname . ', ' . $request->firstname . ' ' . $request->extname . ' ' . $request->middlename);

            // Create User
            $newUser = new User();
            $newUser->name       = $name;
            $newUser->lastname   = $request->lastname;
            $newUser->firstname  = $request->firstname;
            $newUser->extname    = $request->extname;
            $newUser->middlename = $request->middlename ?? "";
            $newUser->username   = $request->username ?? "";
            $newUser->password   = Hash::make($request->password);
            $newUser->role_id    = $request->role;
            $newUser->photo      = $photo; 
            $newUser->save();

            // Create Teacher if Role is 3
            if ($request->role == 3) {
                $getSchoolYear = SchoolYear::find($request->school_year_id);

                $mainTeacher = null;
                if ($request->co_adviser) {
                    $mainTeacher = Teacher::where('school_year_id', $request->school_year_id)
                        ->where('level', $request->level)
                        ->where('grade', $request->grade)
                        ->where('section', $request->section)
                        ->where(function ($query) {
                            $query->whereNull('co_adviser')->orWhere('co_adviser', 0);
                        })->first();

                    if (!$mainTeacher) {
                        DB::rollBack();
                        return response()->json(['message' => 'Add an adviser first before adding a co-teacher for this grade and section.'], 422);
                    }
                }
                
                $newTeacher = new Teacher();
                $newTeacher->id_no          = $request->id_no ?? null;
                $newTeacher->lastname       = $request->lastname;
                $newTeacher->firstname      = $request->firstname;
                $newTeacher->extname        = $request->extname ?? null;
                $newTeacher->middlename     = $request->middlename ?? null;
                $newTeacher->contact_no     = $request->contact_no ?? null;
                $newTeacher->email          = $request->email ?? null;
                $newTeacher->address        = $request->address ?? null;
                $newTeacher->sex            = $request->sex;
                $newTeacher->position       = $request->position ?? null;
                $newTeacher->photo          = $photo;
                $newTeacher->school_year_id = $request->school_year_id;
                $newTeacher->sy_from        = $getSchoolYear->sy_from;
                $newTeacher->sy_to          = $getSchoolYear->sy_to;
                $newTeacher->level          = $request->level;
                $newTeacher->grade          = $request->grade;
                $newTeacher->section        = $request->section;
                $newTeacher->co_adviser     = $request->co_adviser ? 1 : null;
                $newTeacher->user_id        = $newUser->id;
                $newTeacher->save();

                if ($request->co_adviser && $mainTeacher) {
                    SchoolYearStudent::where('teacher_id', $mainTeacher->user_id)
                        ->where('school_year_id', $request->school_year_id)
                        ->where('level', $request->level)
                        ->where('grade', $request->grade)
                        ->where('section', $request->section)
                        ->update(['co_teacher_id' => $newUser->id]);
                }
            }

            DB::commit();
            
            return response()->json(['message' => 'success'], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Optional: Log the error for your own debugging
            // \Illuminate\Support\Facades\Log::error('Store Method Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'An error occurred while processing your request.',
                'error'   => $e->getMessage() 
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'lastname' => 'required|string',
            'firstname' => 'required|string',
            'extname' => 'nullable|string',
            'middlename' => 'nullable|string',
            'username' => 'nullable|string|unique:users,username,'.$id,
            'password' => 'nullable|string',
            'role' => 'required|numeric|exists:users_roles,id',
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user_id = $user->id;

        $checkUser = User::where('id','!=', $id)
            ->where('username',$request->username)->first();

        if ($checkUser) {
            return response()->json(['message' => 'Username already exists!'], 404);
        }

        $extname = '';
        if($request->extname){
            $extname = ' '.$request->extname;
        }

        $middlename = '';
        if ($request->middlename) {
            $middlename = ' ' . strtoupper($request->middlename[0]) . '.';
        }

        $name = $request->lastname.', '.$request->firstname.$extname.' '.$middlename;

        if ($request->hasFile('newPhoto')) {
            $request->validate([
                'newPhoto' => 'required|image',
            ]);
            $photo = $request->file('newPhoto')->store('teachers', 'public');
        }else{
            $userInfo = User::where('id',$id)->first();
            $photo = $userInfo->photo;
        }

        User::where('id',$id)
            ->update([
            'name' => $name,
            'lastname' => $request->lastname,
            'firstname' => $request->firstname,
            'extname' => $request->extname ?? "",
            'middlename' => $request->middlename ?? "",
            // 'password' => Hash::make($request->password),
            'role_id' => $request->role,
            'photo' => $photo
        ]);
        
        if($request->username){
            User::where('id',$id)
                ->update([
                'username' => $request->username,
            ]);

            if($request->password!="************"){
                User::where('id',$id)
                    ->update([
                    'password' => Hash::make($request->password)
                ]);
            }
        }

        if($request->role==3){
            $teacher_id = $id;

            $request->validate([
                'id_no' => 'required|string|unique:teachers,id_no,'.$teacher_id.',user_id',
                'status' => 'required|in:Active,Inactive',
                'contact_no' => 'required|regex:/^09\d{9}$/',
                'email' => 'nullable|string',
                'address' => 'nullable|string',
                'sex' => 'required|in:Male,Female',
                'position' => 'nullable|string',
                'level' => 'required|in:Kinder,Elementary,Junior High School,Senior High School',
                'grade' => 'required',
                'section' => 'required',
                'school_year_id' => 'required|integer|exists:school_years,id',
                'co_adviser'     => 'nullable|boolean',
            ]);

            $school_year_id = $request->school_year_id;

            $getSchoolYear = SchoolYear::find($school_year_id);
            $sy_from = $getSchoolYear->sy_from;
            $sy_to = $getSchoolYear->sy_to;

            $mainTeacher = null;
            if ($request->co_adviser) {
                $mainTeacher = Teacher::where('school_year_id', $request->school_year_id)
                    ->where('level', $request->level)
                    ->where('grade', $request->grade)
                    ->where('section', $request->section)
                    ->where('user_id', '!=', $teacher_id)
                    ->where(function ($query) {
                        $query->whereNull('co_adviser')->orWhere('co_adviser', 0);
                    })->first();

                if (!$mainTeacher) {
                    return response()->json(['message' => 'Add an adviser first before adding a co-teacher for this grade and section.'], 422);
                }
            }

            $check = Teacher::where('user_id',$teacher_id)->first();

            if($check){
                $insert = Teacher::find($check->id);
            }else{
                $insert = new Teacher();
            }
            
            $insert->id_no = $request->id_no ?? null;
            $insert->lastname = $request->lastname;
            $insert->firstname = $request->firstname;
            $insert->extname = $request->extname ?? null;
            $insert->middlename = $request->middlename ?? null;
            $insert->contact_no = $request->contact_no;
            $insert->email = $request->email ?? null;
            $insert->address = $request->address ?? null;
            $insert->photo = $photo;
            $insert->sex = $request->sex;
            $insert->position = $request->position ?? null;
            $insert->sy_from = $sy_from;
            $insert->sy_to = $sy_to; 
            $insert->level = $request->level;
            $insert->grade = $request->grade;
            $insert->section = $request->section;
            $insert->co_adviser = $request->co_adviser ? 1 : null;
            $insert->school_year_id = $request->school_year_id;
            $insert->user_id = $teacher_id;
            $insert->status = $request->status;
            $insert->save();

            if($request->status=='Active'){
                if (!$request->co_adviser) {
                    Student::where('teachers_id',$teacher_id)
                        ->where('status','Active')
                        ->update([
                            'school_year_id' => $school_year_id,
                            'sy_from' => $sy_from,
                            'sy_to' => $sy_to,
                            'level' => $request->level,
                            'grade' => $request->grade,
                            'section' => $request->section,
                        ]);

                    SchoolYearStudent::where('teacher_id',$teacher_id)
                        ->where('status','Active')
                        ->where('school_year_id', $school_year_id)
                        ->update([
                            'sy_from' => $sy_from,
                            'sy_to' => $sy_to,
                            'level' => $request->level,
                            'grade' => $request->grade,
                            'section' => $request->section,
                        ]);
                        
                    SchoolYearStudent::where('co_teacher_id', $teacher_id)
                        ->where('school_year_id', $school_year_id)
                        ->where('level', $request->level)
                        ->where('grade', $request->grade)
                        ->where('section', $request->section)
                        ->update(['co_teacher_id' => null]); 
                    
                } else if ($mainTeacher) {

                    SchoolYearStudent::where('teacher_id', $mainTeacher->user_id)
                        ->where('school_year_id', $school_year_id)
                        ->where('level', $request->level)
                        ->where('grade', $request->grade)
                        ->where('section', $request->section)
                        ->where('status', 'Active')
                        ->update([
                            'co_teacher_id' => $teacher_id
                        ]);
                }
            }
        }

        return response()->json(['message' => 'User updated successfully']);
    }
}