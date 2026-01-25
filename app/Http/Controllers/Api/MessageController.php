<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SendSmsJob;
use App\Models\Message;
use App\Models\MessageTarget;
use App\Models\Station;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Process;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $search = $request->query('search');

        $students = Message::with('targets.teacher','targets.student','targetErrors.teacher','targetErrors.student','user')
            ->when($search, function ($query, $search) {
            $query->where('message', 'like', "%$search%")
                ->orWhere('grade', 'like', "%$search%")
                ->orWhere('section', 'like', "%$search%");
        });

        if($user->id==3){
            $students->where('user_id',$user->id);
        }
        
        $students = $students->orderBy('created_at','DESC')->paginate(10);

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

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'audience_type' => 'required|string|in:student,teacher,all,students,teachers,by_grade,by_section',
            'recipients' => 'nullable|array',
            'message' => 'required|string'
        ]);
        
        $getStudents = $this->getRecipients(Student::query(), $validatedData, 'student');        
        $getTeachers = $this->getRecipients(Teacher::query(), $validatedData, 'teacher');

        try{

            $user = Auth::user();

            $message = $validatedData['message'];

            $insert = new Message;
            $insert->user_id = $user->id;
            $insert->message = $message;
            $insert->audience_type = $validatedData['audience_type'];

            if($validatedData['audience_type']=='by_grade'){
                $insert->grade = $validatedData['recipients'];
            }elseif($validatedData['audience_type']=='by_section'){
                $insert->section = $validatedData['recipients'];
            }

            $insert->save();

            $message_id = $insert->id;

            if($getStudents->count()>0){
                $this->storeRecipients($message_id, $message, $getStudents, 'student');
            }

            if($getTeachers->count()>0){
                $this->storeRecipients($message_id, $message, $getTeachers, 'teacher');
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sales statuses',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getRecipients($table, $validatedData, $type)
    {
        $user = Auth::user();
        $getRecipients = $table->where('status','Active')
            ->where('contact_no','!=','');

        if($user->role_id==3){
            if($validatedData['audience_type']=='student' && $type=='student'){
                $getRecipients->whereIn('id',$validatedData['recipients']);
            }elseif($validatedData['audience_type']=='students' && $type=='student'){
                $getRecipients->where('id','>',0)
                    ->where('teachers_id',$user->id);
            }else{
                $getRecipients->where('id',0);
            }
        }else{            
            if($validatedData['audience_type']=='student' && $type=='student'){
                $getRecipients->whereIn('id',$validatedData['recipients']);
            }elseif($validatedData['audience_type']=='teacher' && $type=='teacher'){
                $getRecipients->whereIn('id',[1]);
            }elseif($validatedData['audience_type']=='all'){
                $getRecipients->where('id','>',0);
            }elseif($validatedData['audience_type']=='students' && $type=='student'){
                $getRecipients->where('id','>',0);
            }elseif($validatedData['audience_type']=='teachers' && $type=='teacher'){
                $getRecipients->where('id','>',0);
            }elseif($validatedData['audience_type']=='by_grade' && $type=='student'){
                $getRecipients->where('grade',$validatedData['recipients']);
            }elseif($validatedData['audience_type']=='by_section' && $type=='student'){
                $getRecipients->where('section',$validatedData['recipients']);
            }else{
                $getRecipients->where('id',0);
                
            }
        }
        return $getRecipients->get();
    }

    public function resend(Request $request)
    {
        $validatedData = $request->validate([
            'id' => 'required|integer|exists:messages,id',
            'recipients' => 'required|array',
            'type' => 'required|string'
        ]);

        $query = Message::findOrFail($validatedData['id']);

        $message = $query->message;

        if($validatedData['type']=='teacher'){
            $column = 'teacher_id';
            $with = 'teacher';
        }else{
            $column = 'student_id';
            $with = 'student';
        }

        $ids = [];
        
        foreach($validatedData['recipients'] as $row){
            $ids[] = $row['id'];
        }

        $recipients = MessageTarget::with('student','teacher')->whereIn('id',$ids)->get();
        
        if($recipients->count()>0){
            foreach($recipients as $row){
                $target_id = $row->id;
                $contact_no = $row->$with?->contact_no;
                if ($contact_no) {
                    $message = str_replace(" ","_S_S_",$message);
                    $message = str_replace("%","_P_P_",$message);
                    dispatch(new SendSmsJob($target_id, $contact_no, $message));
                }
            }
        }
    }

    private function storeRecipients($message_id, $message, $data, $type)
    {
        foreach($data as $row){
            $insert = new MessageTarget;
            $insert->message_id = $message_id;
            if($type=='student'){
                $insert->student_id = $row->id;
            }else{
                $insert->teacher_id = $row->id;
            }
            $insert->status = 'Error';
            $insert->grade = $row->grade;
            $insert->section = $row->section;
            $insert->save();

            $target_id = $insert->id;
            $contact_no = $row->contact_no;

            $message = str_replace(" ","_S_S_",$message);
            $message = str_replace("%","_P_P_",$message);

            dispatch(new SendSmsJob($target_id, $contact_no, $message))->onQueue('gsmMessage');

            //run php artisan queue:work database
        }
    }
}
