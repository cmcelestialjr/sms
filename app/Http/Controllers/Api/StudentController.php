<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendSmsNewStudentJob;
use App\Models\Attendance;
use App\Models\SchoolInfo;
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

        $schoolYear = $request->schoolYear;
        $role_id = $user->role_id;
        $user_id = $user->id;

        $adviserIds = [$user_id];
        if ($role_id == 3) {
            $resolved = $this->resolveAdviser((int) $user_id);
            $adviserIds = array_values(array_unique([$user_id, $resolved['main_teacher_id']]));
        }

        $students = Student::with('teacher')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('firstname', 'like', "%$search%")
                        ->orWhere('lastname', 'like', "%$search%")
                        ->orWhere('middlename', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%$search%")
                        ->orWhere('lrn_no', 'like', "%$search%")
                        ->orWhere('grade', 'like', "%$search%")
                        ->orWhere('section', 'like', "%$search%");
                });
            })
            ->whereHas('schoolYearStudents', function ($q) use ($schoolYear, $role_id, $user_id) {
                if (!empty($schoolYear)) {
                    $q->where('school_year_id', $schoolYear);
                }

                if ($role_id == 3) {
                    $q->where(function ($sub) use ($user_id) {
                        $sub->where('teacher_id', $user_id)
                            ->orWhere('co_teacher_id', $user_id);
                    });
                }
            });

        if ($role_id == 3) {
            if ($request->has('status')) {
                if ($request->status == 'Approved') {
                    $students->whereIn('teacher_id_approved', $adviserIds);
                } elseif ($request->status == 'Requested') {
                    $students->whereIn('teacher_id_requested', $adviserIds);
                } elseif ($request->status == 'Pending') {
                    $students->whereIn('teacher_id_pending', $adviserIds);
                } else {
                    $status = $request->status;
                    $students->where('status', $status);
                }
            }
        } else {
            if ($request->has('status')) {
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
        $search = $request->get('search');

        $user = Auth::user();

        $students = Student::with('teacher')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('firstname', 'like', "%$search%")
                        ->orWhere('lastname', 'like', "%$search%")
                        ->orWhere('middlename', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%$search%")
                        ->orWhere('lrn_no', 'like', "%$search%")
                        ->orWhere('grade', 'like', "%$search%")
                        ->orWhere('section', 'like', "%$search%");
                });
            });

        if ($request->has('f')) {
            if ($user->role_id == 3 && $request->f == 'message') {
                $userId = $user->id;
                $students->whereHas('schoolYearStudents', function ($q) use ($userId) {
                    $q->whereColumn('school_year_students.school_year_id', 'students.school_year_id')
                        ->where(function ($sub) use ($userId) {
                            $sub->where('teacher_id', $userId)
                                ->orWhere('co_teacher_id', $userId);
                        });
                });
            }
        }

        if ($request->has('schoolYear') && !empty($request->schoolYear)) {
            $schoolYear = $request->schoolYear;
            $students->where('school_year_id', $schoolYear);
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

        if ($request->search_student_id) {
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
            $student_id = $this->getStudentId();

            $validated['student_id'] = $student_id;
            $validated['rfid_tag'] = $request->rfid_tag == 'null' ? null : $request->rfid_tag;
            $validated['qr_code'] = $request->qr_code == 'null' ? null : $request->qr_code;
            $validated['middlename'] = $request->middlename == 'null' ? null : $request->middlename;
            $validated['extname'] = $request->extname == 'null' ? null : $request->extname;
            $validated['email'] = $request->email == 'null' ? null : $request->email;
            $validated['address'] = $request->address == 'null' ? null : $request->address;

            if ($request->hasFile('newPhoto')) {
                $request->validate([
                    'newPhoto' => 'required|image',
                ]);
                $validated['photo'] = $request->file('newPhoto')->store('students', 'public');
            }

            if ($user->role_id < 3) {
                $request->validate([
                    'teachers_id' => 'required|integer|exists:users,id',
                ]);
                $teachers_id = $request->teachers_id;
            } else {
                $teachers_id = $user->id;
            }

            $resolved = $this->resolveAdviser((int) $teachers_id);
            $teacher = $resolved['teacher'];
            $teachers_id = $resolved['main_teacher_id'];

            if ($teacher) {
                $level = $teacher->level;
                $grade = $teacher->grade;
                $section = $teacher->section;
                $school_year_id = $teacher->school_year_id;
                $sy_from = $teacher->sy_from;
                $sy_to = $teacher->sy_to;
            } else {
                $level = 'Elementary';
                $grade = null;
                $section = null;

                $getSchoolYear = $this->schoolYearServices->getSchoolYear();
                $school_year_id = $getSchoolYear['school_year_id'];
                $sy_from = $getSchoolYear['sy_from'];
                $sy_to = $getSchoolYear['sy_to'];
            }

            $validated['school_year_id'] = $school_year_id;
            $validated['sy_from'] = $sy_from;
            $validated['sy_to'] = $sy_to;

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
            } else {
                $middleInitial = '';
            }
            if (!empty($student->extname)) {
                $extname = mb_strtoupper($student->extname);
            } else {
                $extname = '';
            }

            $name = "$lastname,$firstname $extname$middleInitial";

            $shoolInfo = SchoolInfo::first();$schoolName = $shoolInfo ? $shoolInfo->name : 'CDEVITSolutions';

            $message =$name . " is currently enrolled in " . $schoolName . ". Thank you.";

            $message = str_replace(" ", "_", $message);

            dispatch(new SendSmsNewStudentJob($student->contact_no,$message))->onQueue('gsmNewStudent');

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

    public function update(Request $request,$id)
    {
        DB::beginTransaction();
        try {
            $student =$this->updateStudent($request,$id, 'update');
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

    private function updateStudent($request, $id,$option)
    {
        $user = Auth::user();

        $student = Student::findOrFail($id);

        $validated =$request->validate([
            'rfid_tag' => 'nullable|string',
            'qr_code' => 'nullable|string',
            'student_id' => 'required|unique:students,student_id,' . $id,
            'lastname' => 'required|string',
            'firstname' => 'required|string',
            'middlename' => 'nullable|string',
            'extname' => 'nullable|string',
            'sex' => 'required|in:Male,Female',
            'contact_no' => 'required|regex:/^09\d{9}$/',
            'birthdate' => 'nullable',
            'email' => 'nullable|string',
            'address' => 'nullable|string',
            'lrn_no' => 'required|string|unique:students,lrn_no,' . $id,
            'status' => 'required|in:Active,Inactive'
        ]);

        $validated['birthdate'] = (empty($request->birthdate) ||$request->birthdate == 'null')
            ? null
            : date('Y-m-d', strtotime($request->birthdate));$validated['rfid_tag'] = $request->rfid_tag == 'null' ? null : $request->rfid_tag;
        $validated['qr_code'] = $request->qr_code == 'null' ? null : $request->qr_code;
        $validated['middlename'] = $request->middlename == 'null' ? null : $request->middlename;
        $validated['extname'] = $request->extname == 'null' ? null : $request->extname;
        $validated['email'] = $request->email == 'null' ? null : $request->email;
        $validated['address'] = $request->address == 'null' ? null : $request->address;

        if ($request->hasFile('newPhoto')) {$request->validate([
                'newPhoto' => 'required|image',
            ]);
            if ($student->photo && Storage::disk('public')->exists($student->photo)) {
                Storage::disk('public')->delete($student->photo);
            }
            $validated['photo'] =$request->file('newPhoto')->store('students', 'public');
        }

        if ($user->role_id < 3) {$request->validate([
                'teachers_id' => 'required|integer|exists:users,id',
            ]);

            $resolved = $this->resolveAdviser((int)$request->teachers_id);
            $teacher =$resolved['teacher'];

            if ($teacher) {
                $validated['teachers_id'] =$resolved['main_teacher_id'];
                if ($validated['status'] == 'Active') {
                    $validated['school_year_id'] =$teacher->school_year_id;
                    $validated['sy_from'] =$teacher->sy_from;
                    $validated['sy_to'] =$teacher->sy_to;
                    $validated['level'] =$teacher->level;
                    $validated['grade'] =$teacher->grade;
                    $validated['section'] =$teacher->section;
                }
            }
        }

        if ($option == 'store' && $user->role_id == 3) {$resolvedRequester = $this->resolveAdviser((int)$user->id);
            $validated['teacher_id_pending'] =$student->teachers_id;
            $validated['teacher_id_requested'] =$resolvedRequester['main_teacher_id'];
        }

        $student->update($validated);

        $this->updateSchoolYearStudent($student, 'update');

        return $student;
    }

    public function statusTotal(Request $request)
    {
        $user = Auth::user();
        $schoolYear =$request->schoolYear;
        $role_id =$user->role_id;
        $user_id =$user->id;

        $adviserIds = [$user_id];
        if ($role_id == 3) {
            $resolved =$this->resolveAdviser((int) $user_id);$adviserIds = array_values(array_unique([$user_id,$resolved['main_teacher_id']]));
        }

        $totals = Student::select('status', DB::raw('count(*) as total'))
            ->whereHas('schoolYearStudents', function ($q) use ($schoolYear, $role_id,$user_id) {
                if (!empty($schoolYear)) {
                    $q->where('school_year_id',$schoolYear);
                }

                if ($role_id == 3) {$q->where(function ($sub) use ($user_id) {
                        $sub->where('teacher_id',$user_id)
                            ->orWhere('co_teacher_id', $user_id);
                    });
                }
            });

        $totals =$totals->groupBy('status')
            ->get();

        $totalActive =$totals->firstWhere('status', 'Active')->total ?? 0;
        $totalInActive =$totals->firstWhere('status', 'Inactive')->total ?? 0;

        $totalApproved = Student::whereIn('teacher_id_approved',$adviserIds)->count();
        $totalRequested = Student::whereIn('teacher_id_requested',$adviserIds)->count();
        $totalPending = Student::whereIn('teacher_id_pending',$adviserIds)->count();

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
        $validated =$request->validate([
            'id' => 'required|integer|exists:students,id',
        ]);

        $user = Auth::user();
        $id =$validated['id'];

        $student = Student::findOrFail($id);$resolved = $this->resolveAdviser((int)$student->teacher_id_requested);
        $teacher =$resolved['teacher'];

        if (!$teacher) {
            return response()->json(['message' => 'Requested adviser record not found.'], 422);
        }

        DB::transaction(function () use ($student,$teacher, $resolved,$user) {
            $student->school_year_id =$teacher->school_year_id;
            $student->sy_from =$teacher->sy_from;
            $student->sy_to =$teacher->sy_to;
            $student->level =$teacher->level;
            $student->grade =$teacher->grade;
            $student->section =$teacher->section;
            $student->teacher_id_approved =$user->id;
            $student->teachers_id = $resolved['main_teacher_id'];$student->teacher_id_requested = 0;
            $student->teacher_id_pending = 0;
            $student->status = 'Active';$student->save();

            $this->updateSchoolYearStudent($student, 'update');
        });

        return response()->json(['message' => 'Success'], 200);
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);

        $check = Attendance::where('student_id',$id)->first();

        if ($check) {
            return response()->json(['message' => 'Error! Student already have attendace.']);
        }

        SchoolYearStudent::where('student_id', $id)->delete();

        $student->delete();
        return response()->json(['message' => 'Deleted']);
    }

    private function getStudentId()
    {
        $year = now()->format('Y');

        $lastStudent = Student::where('student_id', 'LIKE', "$year%")
            ->selectRaw("student_id, CAST(SUBSTRING(student_id, 5) AS UNSIGNED) as num")
            ->orderByDesc('num')
            ->first();

        $number = $lastStudent ? $lastStudent->num + 1 : 1;

        return $year . str_pad($number, 5, '0', STR_PAD_LEFT);
    }

    private function updateSchoolYearStudent($student,$from)
    {
        $fetchTeacher = Teacher::where('school_year_id',$student->school_year_id)
            ->where('level', $student->level)
            ->where('grade', $student->grade)
            ->where('section', $student->section)
            ->where('co_adviser', 1)
            ->where('status', 'Active')
            ->first();

        $check = SchoolYearStudent::where('student_id',$student->id)
            ->where('school_year_id', $student->school_year_id)
            ->where('sy_from', $student->sy_from)
            ->where('sy_to', $student->sy_to)
            ->first();

        if ($check) {
            $update = SchoolYearStudent::find($check->id);
        } else {
            $update = new SchoolYearStudent();
            $update->student_id =$student->id;
            $update->school_year_id =$student->school_year_id;
            $update->sy_from =$student->sy_from;
            $update->sy_to =$student->sy_to;
            $update->date_enrolled = date('Y-m-d');$update->is_transferred = 0;
        }

        $update->level =$student->level;
        $update->grade =$student->grade;
        $update->section =$student->section;
        $update->teacher_id =$student->teachers_id;
        $update->co_teacher_id = $fetchTeacher ? $fetchTeacher->user_id : null;
        $update->status =$student->status;

        if ($from != 'update' &&$from != 'store') {
            $update->date_enrolled =$student->date_enrolled;
            $update->is_transferred =$student->is_transferred;
        }

        $update->save();
    }

    private function resolveAdviser(int $teacherUserId): array
    {
        $teacher = Teacher::where('user_id',$teacherUserId)->first();

        if (!$teacher) {
            return ['teacher' => null, 'main_teacher_id' => $teacherUserId];
        }

        if ((int) $teacher->co_adviser === 1) {
            $mainTeacher = Teacher::where('school_year_id',$teacher->school_year_id)
                ->where('level', $teacher->level)
                ->where('grade', $teacher->grade)
                ->where('section', $teacher->section)
                ->where(function ($q) {$q->whereNull('co_adviser')->orWhere('co_adviser', 0);
                })
                ->first();

            if ($mainTeacher) {
                return ['teacher' => $mainTeacher, 'main_teacher_id' =>$mainTeacher->user_id];
            }
        }

        return ['teacher' => $teacher, 'main_teacher_id' =>$teacher->user_id];
    }
}