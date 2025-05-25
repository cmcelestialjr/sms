<?php

namespace App\Http\Controllers;

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
        $query = User::with('userRole','teacher','students')
            ->whereHas('teacher');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('username', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('status')){
            $status = $request->status;
            $query->whereHas('teacher', function ($q) use ($status) {
                $q->where('status', $status);
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

        return Teacher::where('lastname', 'like', "%{$query}%")
            ->orWhere('firstname', 'like', "%{$query}%")
            ->orWhere('middlename', 'like', "%{$query}%")
            ->limit(10)
            ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'lastname' => 'required|string',
            'firstname' => 'required|string',
            'extname' => 'nullable|string',
            'middlename' => 'nullable|string',
            'username' => 'required|string|unique:users',
            'password' => 'required|string',
            'role' => 'required|numeric|exists:users_roles,id',
            
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user_id = $user->id;

        $name = $request->lastname.', '.$request->firstname.' '.$request->extname.' '.$request->middlename;       

        if ($request->hasFile('newPhoto')) {
            $request->validate([
                'newPhoto' => 'required|image',
            ]);
            $photo = $request->file('newPhoto')->store('teachers', 'public');
        }else{
            $photo = $request->photo;
        }

        $insert = new User;
        $insert->name = $name;
        $insert->lastname = $request->lastname;
        $insert->firstname = $request->firstname;
        $insert->extname = $request->extname;
        $insert->middlename = $request->middlename ?? "";
        $insert->username = $request->username ?? "";
        $insert->password = Hash::make($request->password);
        $insert->role_id = $request->role;
        $insert->photo = $request->photo;
        $insert->save();

        if($request->role==3){
            $teacher_id = $insert->id;

            $request->validate([
                'status' => 'required|in:Active,Inactive',
                'contact_no' => 'required|regex:/^09\d{9}$/',
                'email' => 'nullable|email',
                'address' => 'nullable|string',
                'sex' => 'required|in:Male,Female',
                'position' => 'nullable|string',
                'level' => 'required|in:Kinder,Elementary,Junior High School,Senior High School',
                'grade' => 'required',
                'section' => 'required',
            ]);

            $getSchoolYear = $this->schoolYearServices->getSchoolYear();
            $sy_from = $getSchoolYear['sy_from'];
            $sy_to = $getSchoolYear['sy_to'];            
            
            $insert = new Teacher();
            $insert->lastname = $request->lastname;
            $insert->firstname = $request->firstname;
            $insert->extname = $request->extname ?? null;
            $insert->middlename = $request->middlename ?? null;
            $insert->contact_no = $request->contact_no ?? null;
            $insert->email = $request->email ?? null;
            $insert->address = $request->address ?? null;
            $insert->sex = $request->sex;
            $insert->position = $request->position ?? null;
            $insert->photo = $photo;
            $insert->sy_from = $sy_from;
            $insert->sy_to = $sy_to;
            $insert->level = $request->level;
            $insert->grade = $request->grade;
            $insert->section = $request->section;
            $insert->user_id = $teacher_id;
            $insert->save();
        }
        return response()->json(['message' => 'success'], 201);
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
                'status' => 'required|in:Active,Inactive',
                'contact_no' => 'required|regex:/^09\d{9}$/',
                'email' => 'nullable|string',
                'address' => 'nullable|string',
                'sex' => 'required|in:Male,Female',
                'position' => 'nullable|string',
                'level' => 'required|in:Kinder,Elementary,Junior High School,Senior High School',
                'grade' => 'required',
                'section' => 'required',
            ]);

            if($request->status=='Active'){
                $getSchoolYear = $this->schoolYearServices->getSchoolYear();
                $sy_from = $getSchoolYear['sy_from'];
                $sy_to = $getSchoolYear['sy_to'];
            }

            $check = Teacher::where('user_id',$teacher_id)->first();

            if($check){
                $insert = Teacher::find($check->id);
            }else{
                $insert = new Teacher();
            }
            
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
            if($request->status=='Active'){
                $insert->sy_from = $sy_from;
                $insert->sy_to = $sy_to;       
            }     
            $insert->level = $request->level;
            $insert->grade = $request->grade;
            $insert->section = $request->section;
            $insert->user_id = $teacher_id;
            $insert->status = $request->status;
            $insert->save();

            if($request->status=='Active'){
                Student::where('teachers_id',$teacher_id)
                    ->where('status','Active')
                    ->update([
                    'sy_from' => $sy_from,
                    'sy_to' => $sy_to,
                    'level' => $request->level,
                    'grade' => $request->grade,
                    'section' => $request->section,
                ]);
            }
        }

        return response()->json(['message' => 'User updated successfully']);
    }
}