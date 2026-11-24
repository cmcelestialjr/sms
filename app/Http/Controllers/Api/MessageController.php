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

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $search = $request->query('search');

        $messages = Message::with('targets.teacher', 'targets.student', 'targetErrors.teacher', 'targetErrors.student', 'user')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('message', 'like', "%$search%")
                        ->orWhere('grade', 'like', "%$search%")
                        ->orWhere('section', 'like', "%$search%");
                });
            });

        if ($user->role_id == 3) {
            $teamUserIds = $this->getAdvisoryTeamUserIds((int) $user->id);
            $messages->whereIn('user_id', $teamUserIds);
        }

        $messages = $messages->orderBy('created_at', 'DESC')->paginate(10);

        return response()->json([
            'data' => $messages->items(),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'prev' => $messages->previousPageUrl(),
                'next' => $messages->nextPageUrl(),
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

        try {
            $user = Auth::user();

            $message = $validatedData['message'];

            $insert = new Message;
            $insert->user_id = $user->id;
            $insert->message = $message;
            $insert->audience_type = $validatedData['audience_type'];

            if ($validatedData['audience_type'] == 'by_grade') {
                $insert->grade = is_array($validatedData['recipients'] ?? null)
                    ? implode(',', $validatedData['recipients'])
                    : ($validatedData['recipients'] ?? null);
            } elseif ($validatedData['audience_type'] == 'by_section') {
                $insert->section = is_array($validatedData['recipients'] ?? null)
                    ? implode(',', $validatedData['recipients'])
                    : ($validatedData['recipients'] ?? null);
            }

            $insert->save();

            $message_id = $insert->id;

            if ($getStudents->count() > 0) {
                $this->storeRecipients($message_id, $message, $getStudents, 'student');
            }

            if ($getTeachers->count() > 0) {
                $this->storeRecipients($message_id, $message, $getTeachers, 'teacher');
            }

            return response()->json([
                'success' => true,
                'message' => 'Message queued successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getRecipients($table, $validatedData, $type)
    {
        $user = Auth::user();
        $recipients = $validatedData['recipients'] ?? [];

        $getRecipients = $table->where('status', 'Active')
            ->where('contact_no', '!=', '');

        if ($user->role_id == 3) {
            $userId = $user->id;

            if ($validatedData['audience_type'] == 'student' && $type == 'student') {
                $getRecipients->whereIn('id', $recipients)
                    ->where(function ($q) use ($userId) {
                        $q->where('teachers_id', $userId)
                            ->orWhereHas('schoolYearStudents', function ($sq) use ($userId) {
                                $sq->whereColumn('school_year_students.school_year_id', 'students.school_year_id')
                                    ->where('co_teacher_id', $userId);
                            });
                    });
            } elseif ($validatedData['audience_type'] == 'students' && $type == 'student') {
                $getRecipients->where('id', '>', 0)
                    ->where(function ($q) use ($userId) {
                        $q->where('teachers_id', $userId)
                            ->orWhereHas('schoolYearStudents', function ($sq) use ($userId) {
                                $sq->whereColumn('school_year_students.school_year_id', 'students.school_year_id')
                                    ->where('co_teacher_id', $userId);
                            });
                    });
            } else {
                $getRecipients->where('id', 0);
            }
        } else {
            if ($validatedData['audience_type'] == 'student' && $type == 'student') {
                $getRecipients->whereIn('id', $recipients);
            } elseif ($validatedData['audience_type'] == 'teacher' && $type == 'teacher') {
                $getRecipients->whereIn('id', $recipients);
            } elseif ($validatedData['audience_type'] == 'all') {
                $getRecipients->where('id', '>', 0);
            } elseif ($validatedData['audience_type'] == 'students' && $type == 'student') {
                $getRecipients->where('id', '>', 0);
            } elseif ($validatedData['audience_type'] == 'teachers' && $type == 'teacher') {
                $getRecipients->where('id', '>', 0);
            } elseif ($validatedData['audience_type'] == 'by_grade' && $type == 'student') {
                $getRecipients->whereIn('grade', $recipients);
            } elseif ($validatedData['audience_type'] == 'by_section' && $type == 'student') {
                $getRecipients->whereIn('section', $recipients);
            } else {
                $getRecipients->where('id', 0);
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

        $formattedMessage = str_replace(" ", "_S_S_", $query->message);
        $formattedMessage = str_replace("%", "_P_P_", $formattedMessage);

        $with = $validatedData['type'] == 'teacher' ? 'teacher' : 'student';

        $ids = [];
        foreach ($validatedData['recipients'] as $row) {
            $ids[] = $row['id'];
        }

        $recipients = MessageTarget::with('student', 'teacher')->whereIn('id', $ids)->get();

        if ($recipients->count() > 0) {
            foreach ($recipients as $row) {
                $target_id = $row->id;
                $contact_no = $row->$with?->contact_no;
                if ($contact_no) {
                    dispatch(new SendSmsJob($target_id, $contact_no, $formattedMessage))->onQueue('gsmMessage');
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Resend queued.'], 200);
    }

    private function storeRecipients($message_id, $message, $data, $type)
    {
        $formattedMessage = str_replace(" ", "_S_S_", $message);
        $formattedMessage = str_replace("%", "_P_P_", $formattedMessage);

        foreach ($data as $row) {
            $insert = new MessageTarget;
            $insert->message_id = $message_id;
            if ($type == 'student') {
                $insert->student_id = $row->id;
            } else {
                $insert->teacher_id = $row->id;
            }
            $insert->status = 'Error';
            $insert->grade = $row->grade;
            $insert->section = $row->section;
            $insert->save();

            $target_id = $insert->id;
            $contact_no = $row->contact_no;

            dispatch(new SendSmsJob($target_id, $contact_no, $formattedMessage))->onQueue('gsmMessage');
        }
    }

    /**
     * Returns the user_ids of both the adviser and co-adviser sharing the same active section
     * so they can both see announcements sent to their advisory class.
     */
    private function getAdvisoryTeamUserIds(int $userId): array
    {
        $teacher = Teacher::where('user_id', $userId)->first();

        if (!$teacher) {
            return [$userId];
        }

        $teamIds = Teacher::where('school_year_id', $teacher->school_year_id)
            ->where('level', $teacher->level)
            ->where('grade', $teacher->grade)
            ->where('section', $teacher->section)
            ->where('status', 'Active')
            ->pluck('user_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->toArray();

        $teamIds[] = $userId;

        return array_values(array_unique($teamIds));
    }
}