<?php

namespace App\Console\Commands;

use App\Mail\AttendifyAccessCodeMail;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Models\Teacher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendAttendifyAccessCodesCommand extends Command
{
    protected $signature = 'attendify:send-access-codes
                            {--role=all : Target role to email: all, students, or teachers}
                            {--student= : Send to a specific Student ID (student_id) or LRN (lrn_no)}
                            {--teacher= : Send to a specific Teacher ID (id_no)}
                            {--grade= : Filter students/teachers by grade}
                            {--section= : Filter students/teachers by section}
                            {--only-unverified : Only email users who have not changed their initial code yet (must_change_code = 1)}
                            {--queue : Queue emails on the attendance_emails queue instead of sending synchronously}';

    protected $description = 'Send 6-digit Attendify access codes via email to Students/Guardians and Teachers';

    public function handle(): int
    {
        $role = strtolower(trim((string) $this->option('role')));
        $specificStudent = $this->option('student');
        $specificTeacher = $this->option('teacher');

        // If a specific --student or --teacher flag was passed, narrow the role automatically
        if ($specificStudent && !$specificTeacher) {
            $role = 'students';
        } elseif ($specificTeacher && !$specificStudent) {
            $role = 'teachers';
        }

        if (!in_array($role, ['all', 'students', 'teachers'], true)) {
            $this->error("Invalid --role option [{$role}]. Use: all, students, or teachers.");
            return Command::FAILURE;
        }

        $totalSent = 0;
        $totalSkipped = 0;

        if (in_array($role, ['all', 'teachers'], true)) {
            [$sent, $skipped] = $this->processTeachers();
            $totalSent += $sent;
            $totalSkipped += $skipped;
        }

        if (in_array($role, ['all', 'students'], true)) {
            [$sent, $skipped] = $this->processStudents();
            $totalSent += $sent;
            $totalSkipped += $skipped;
        }

        $this->newLine();
        $this->info("Done! Emails sent/queued: {$totalSent} | Skipped (no valid email): {$totalSkipped}");

        return Command::SUCCESS;
    }

    /**
     * Send access codes to Teachers
     */
    private function processTeachers(): array
    {
        $query = Teacher::query();

        if ($teacherIdNo = $this->option('teacher')) {
            $query->where('id_no', trim($teacherIdNo));
        }

        if ($grade = $this->option('grade')) {
            $query->where('grade', trim($grade));
        }

        if ($section = $this->option('section')) {
            $query->where('section', trim($section));
        }

        if ($this->option('only-unverified')) {
            $query->where('must_change_code', true);
        }

        $teachers = $query->orderBy('lastname')->get();

        if ($teachers->isEmpty()) {
            $this->warn('No matching teacher records found.');
            return [0, 0];
        }

        $this->info("Processing {$teachers->count()} teacher(s)...");
        $sent = 0;
        $skipped = 0;

        foreach ($teachers as $teacher) {
            $email = trim((string) $teacher->email);

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->line(" - Skipped Teacher [{$teacher->id_no}] {$teacher->firstname} {$teacher->lastname}: No valid email");
                $skipped++;
                continue;
            }

            // Ensure teacher has an access_code
            if (empty($teacher->access_code)) {
                $teacher->access_code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $teacher->save();
            }

            $fullName = trim("{$teacher->firstname} {$teacher->middlename} {$teacher->lastname} {$teacher->extname}");
            $fullName = preg_replace('/\s+/', ' ', $fullName);

            $classParts = array_filter([$teacher->level, $teacher->grade, $teacher->section]);
            $classInfo = !empty($classParts) ? implode(' • ', $classParts) : 'LNU-ILS Faculty';

            $payload = [
                'role'             => 'teacher',
                'recipient_name'   => $fullName,
                'full_name'        => $fullName,
                'id_number'        => (string) $teacher->id_no,
                'lrn_no'           => null,
                'access_code'      => (string) $teacher->access_code,
                'class_info'       => $classInfo,
                'must_change_code' => (bool) ($teacher->must_change_code ?? true),
            ];

            if ($this->deliverMail($email, $payload)) {
                $this->line(" ✓ Sent to Teacher [{$teacher->id_no}] {$fullName} <{$email}>");
                $sent++;
            } else {
                $skipped++;
            }
        }

        return [$sent, $skipped];
    }

    /**
     * Send access codes to Students and/or their Guardians
     */
    private function processStudents(): array
    {
        $query = Student::query();

        if ($studentIdentifier = $this->option('student')) {
            $cleanId = trim($studentIdentifier);
            $query->where(function ($q) use ($cleanId) {
                $q->where('student_id', $cleanId)
                  ->orWhere('lrn_no', $cleanId);
            });
        }

        if ($grade = $this->option('grade')) {
            $query->where('grade', trim($grade));
        }

        if ($section = $this->option('section')) {
            $query->where('section', trim($section));
        }

        if ($this->option('only-unverified')) {
            $query->where('must_change_code', true);
        }

        $students = $query->orderBy('lastname')->get();

        if ($students->isEmpty()) {
            $this->warn('No matching student records found.');
            return [0, 0];
        }

        $this->info("Processing {$students->count()} student(s)...");
        $sent = 0;
        $skipped = 0;

        foreach ($students as $student) {
            // Ensure student has an access_code
            if (empty($student->access_code)) {
                $student->access_code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $student->save();
            }

            $fullName = trim("{$student->firstname} {$student->middlename} {$student->lastname} {$student->extname}");
            $fullName = preg_replace('/\s+/', ' ', $fullName);

            $classParts = array_filter([$student->level, $student->grade, $student->section]);
            $classInfo = !empty($classParts) ? implode(' • ', $classParts) : 'LNU-ILS Student';

            // Collect valid emails from both `students.email` and `student_guardians.email`
            $recipients = [];

            $studentEmail = strtolower(trim((string) $student->email));
            if (!empty($studentEmail) && filter_var($studentEmail, FILTER_VALIDATE_EMAIL)) {
                $recipients[$studentEmail] = $fullName;
            }

            $guardians = StudentGuardian::where('student_id', $student->id)->get();
            foreach ($guardians as $guardian) {
                $gEmail = strtolower(trim((string) $guardian->email));
                if (!empty($gEmail) && filter_var($gEmail, FILTER_VALIDATE_EMAIL)) {
                    $gName = trim((string) $guardian->name);
                    $recipients[$gEmail] = !empty($gName) ? $gName : "Parent/Guardian of {$student->firstname}";
                }
            }

            if (empty($recipients)) {
                $this->line(" - Skipped Student [{$student->student_id}] {$fullName}: No valid student or guardian email");
                $skipped++;
                continue;
            }

            foreach ($recipients as $email => $recipientName) {
                $payload = [
                    'role'             => 'student',
                    'recipient_name'   => $recipientName,
                    'full_name'        => $fullName,
                    'id_number'        => (string) $student->student_id,
                    'lrn_no'           => $student->lrn_no,
                    'access_code'      => (string) $student->access_code,
                    'class_info'       => $classInfo,
                    'must_change_code' => (bool) ($student->must_change_code ?? true),
                ];

                if ($this->deliverMail($email, $payload)) {
                    $this->line(" ✓ Sent to Student/Guardian [{$student->student_id}] {$fullName} <{$email}>");
                    $sent++;
                } else {
                    $skipped++;
                }
            }
        }

        return [$sent, $skipped];
    }

    /**
     * Send or queue the Mailable
     */
    private function deliverMail(string $email, array $payload): bool
    {
        try {
            $mailable = new AttendifyAccessCodeMail($payload);

            if ($this->option('queue')) {
                Mail::to($email)->queue($mailable->onQueue('attendance_emails'));
            } else {
                Mail::to($email)->send($mailable);
            }

            return true;
        } catch (\Throwable $e) {
            $this->error(" ✗ Failed sending to <{$email}>: " . $e->getMessage());
            return false;
        }
    }
}