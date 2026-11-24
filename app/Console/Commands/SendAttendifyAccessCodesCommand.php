<?php

namespace App\Console\Commands;

use App\Mail\AttendifyAccessCodeMail;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Models\Teacher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class SendAttendifyAccessCodesCommand extends Command
{
    protected $signature = 'attendify:send-access-codes
                            {--role=all : Target role to email: all, students, or teachers}
                            {--student= : Send to a specific Student ID (student_id) or LRN (lrn_no)}
                            {--teacher= : Send to a specific Teacher ID (id_no)}
                            {--grade= : Filter students/teachers by grade}
                            {--section= : Filter students/teachers by section}
                            {--only-unverified : Only email users who have not changed their initial code yet (must_change_code = 1)}
                            {--queue : Queue emails on the attendance_emails queue with staggered delays}
                            {--delay=4 : Seconds to wait between emails (prevents Gmail rate-limiting)}
                            {--limit=150 : Max emails to send per run (0 for unlimited)}
                            {--force : Re-send even if this recipient was already emailed previously}
                            {--skip-mx-check : Skip DNS MX domain verification}
                            {--dry-run : Preview who will be emailed without actually sending}';

    protected $description = 'Send 6-digit Attendify access codes safely via email with Gmail anti-spam pacing';

    private string $sentLogFile = 'attendify_sent_emails.json';
    private array $sentLog = [];
    private array $mxCache = [];
    private int $runSentCount = 0;
    private int $maxLimit = 150;
    private int $delaySeconds = 4;
    private int $consecutiveFailures = 0;

    public function handle(): int
    {
        $role = strtolower(trim((string) $this->option('role')));
        $specificStudent = $this->option('student');
        $specificTeacher = $this->option('teacher');

        if ($specificStudent && !$specificTeacher) {
            $role = 'students';
        } elseif ($specificTeacher && !$specificStudent) {
            $role = 'teachers';
        }

        if (!in_array($role, ['all', 'students', 'teachers'], true)) {
            $this->error("Invalid --role option [{$role}]. Use: all, students, or teachers.");
            return Command::FAILURE;
        }

        $this->maxLimit = max(0, (int) $this->option('limit'));
        $this->delaySeconds = max(0, (int) $this->option('delay'));
        $this->sentLog = $this->loadSentLog();

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE: No emails will actually be sent or queued.');
        }

        $totalSent = 0;
        $totalSkipped = 0;

        if (in_array($role, ['all', 'teachers'], true) && !$this->limitReached()) {
            [$sent, $skipped] = $this->processTeachers();
            $totalSent += $sent;
            $totalSkipped += $skipped;
        }

        if (in_array($role, ['all', 'students'], true) && !$this->limitReached()) {
            [$sent, $skipped] = $this->processStudents();
            $totalSent += $sent;
            $totalSkipped += $skipped;
        }

        $this->newLine();
        $this->info("Done! Emails sent/queued: {$totalSent} | Skipped: {$totalSkipped}");

        if ($this->limitReached()) {
            $this->warn("Batch limit ({$this->maxLimit}) reached. Wait a while and re-run the same command to continue safely.");
        }

        return Command::SUCCESS;
    }

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
            if ($this->limitReached() || $this->tooManyFailures()) {
                break;
            }

            $email = strtolower(trim((string) $teacher->email));

            if (!$this->isDeliverableEmail($email)) {
                $this->line(" - Skipped Teacher [{$teacher->id_no}] {$teacher->firstname} {$teacher->lastname}: Invalid or unreachable email ({$email})");
                $skipped++;
                continue;
            }

            $dedupeKey = "teacher:{$teacher->id_no}:{$email}";
            if (!$this->option('force') && isset($this->sentLog[$dedupeKey])) {
                $this->line(" - Skipped Teacher [{$teacher->id_no}] <{$email}>: Already emailed on {$this->sentLog[$dedupeKey]}");
                $skipped++;
                continue;
            }

            if (empty($teacher->access_code) && !$this->option('dry-run')) {
                $teacher->access_code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $teacher->save();
            }

            $fullName = preg_replace('/\s+/', ' ', trim("{$teacher->firstname} {$teacher->middlename} {$teacher->lastname} {$teacher->extname}"));
            $classParts = array_filter([$teacher->level, $teacher->grade, $teacher->section]);
            $classInfo = !empty($classParts) ? implode(' • ', $classParts) : 'LNU-ILS Faculty';

            $payload = [
                'role'             => 'teacher',
                'recipient_name'   => $fullName,
                'full_name'        => $fullName,
                'id_number'        => (string) $teacher->id_no,
                'lrn_no'           => null,
                'access_code'      => (string) ($teacher->access_code ?? '000000'),
                'class_info'       => $classInfo,
                'must_change_code' => (bool) ($teacher->must_change_code ?? true),
            ];

            if ($this->deliverMail($email, $payload, $dedupeKey)) {
                $prefix = $this->option('dry-run') ? ' [DRY-RUN]' : ' ✓ Sent';
                $this->line("{$prefix} to Teacher [{$teacher->id_no}] {$fullName} <{$email}>");
                $sent++;
            } else {
                $skipped++;
            }
        }

        return [$sent, $skipped];
    }

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
            if ($this->limitReached() || $this->tooManyFailures()) {
                break;
            }

            if (empty($student->access_code) && !$this->option('dry-run')) {
                $student->access_code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $student->save();
            }

            $fullName = preg_replace('/\s+/', ' ', trim("{$student->firstname} {$student->middlename} {$student->lastname} {$student->extname}"));
            $classParts = array_filter([$student->level, $student->grade, $student->section]);
            $classInfo = !empty($classParts) ? implode(' • ', $classParts) : 'LNU-ILS Student';

            $recipients = [];

            $studentEmail = strtolower(trim((string) $student->email));
            if ($this->isDeliverableEmail($studentEmail)) {
                $recipients[$studentEmail] = $fullName;
            }

            $guardians = StudentGuardian::where('student_id', $student->id)->get();
            foreach ($guardians as $guardian) {
                $gEmail = strtolower(trim((string) $guardian->email));
                if ($this->isDeliverableEmail($gEmail)) {
                    $gName = trim((string) $guardian->name);
                    $recipients[$gEmail] = !empty($gName) ? $gName : "Parent/Guardian of {$student->firstname}";
                }
            }

            if (empty($recipients)) {
                $this->line(" - Skipped Student [{$student->student_id}] {$fullName}: No valid/deliverable student or guardian email");
                $skipped++;
                continue;
            }

            foreach ($recipients as $email => $recipientName) {
                if ($this->limitReached() || $this->tooManyFailures()) {
                    break;
                }

                $dedupeKey = "student:{$student->student_id}:{$email}";
                if (!$this->option('force') && isset($this->sentLog[$dedupeKey])) {
                    $this->line(" - Skipped Student/Guardian [{$student->student_id}] <{$email}>: Already emailed on {$this->sentLog[$dedupeKey]}");
                    $skipped++;
                    continue;
                }

                $payload = [
                    'role'             => 'student',
                    'recipient_name'   => $recipientName,
                    'full_name'        => $fullName,
                    'id_number'        => (string) $student->student_id,
                    'lrn_no'           => $student->lrn_no,
                    'access_code'      => (string) ($student->access_code ?? '000000'),
                    'class_info'       => $classInfo,
                    'must_change_code' => (bool) ($student->must_change_code ?? true),
                ];

                if ($this->deliverMail($email, $payload, $dedupeKey)) {
                    $prefix = $this->option('dry-run') ? ' [DRY-RUN]' : ' ✓ Sent';
                    $this->line("{$prefix} to Student/Guardian [{$student->student_id}] {$fullName} <{$email}>");
                    $sent++;
                } else {
                    $skipped++;
                }
            }
        }

        return [$sent, $skipped];
    }

    private function deliverMail(string $email, array $payload, string $dedupeKey): bool
    {
        if ($this->option('dry-run')) {
            $this->runSentCount++;
            return true;
        }

        try {
            $mailable = new AttendifyAccessCodeMail($payload);

            if ($this->option('queue')) {
                // Stagger queued jobs so the queue worker does not blast Gmail all at once
                $delay = $this->runSentCount * $this->delaySeconds;
                Mail::to($email)->later(
                    now()->addSeconds($delay),
                    $mailable->onQueue('attendance_emails')
                );
            } else {
                // Pause between synchronous sends (with +0–2s jitter)
                if ($this->runSentCount > 0 && $this->delaySeconds > 0) {
                    sleep($this->delaySeconds + random_int(0, 2));
                }

                Mail::to($email)->send($mailable);
            }

            $this->runSentCount++;
            $this->consecutiveFailures = 0;
            $this->recordSent($dedupeKey);

            return true;
        } catch (\Throwable $e) {
            $this->consecutiveFailures++;
            $this->error(" ✗ Failed sending to <{$email}>: " . $e->getMessage());

            if ($this->tooManyFailures()) {
                $this->error('Stopping early: 5 consecutive SMTP errors detected (possible Gmail rate limit or connection drop).');
            }

            return false;
        }
    }

    /**
     * Validate syntax AND verify the email domain has a live MX/A record to prevent bounces.
     */
    private function isDeliverableEmail(string $email): bool
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if ($this->option('skip-mx-check')) {
            return true;
        }

        $domain = substr(strrchr($email, '@'), 1);
        if (empty($domain)) {
            return false;
        }

        if (!array_key_exists($domain, $this->mxCache)) {
            $this->mxCache[$domain] = checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
        }

        return $this->mxCache[$domain];
    }

    private function limitReached(): bool
    {
        return $this->maxLimit > 0 && $this->runSentCount >= $this->maxLimit;
    }

    private function tooManyFailures(): bool
    {
        return $this->consecutiveFailures >= 5;
    }

    private function loadSentLog(): array
    {
        if (!Storage::disk('local')->exists($this->sentLogFile)) {
            return [];
        }

        return json_decode(Storage::disk('local')->get($this->sentLogFile), true) ?? [];
    }

    private function recordSent(string $dedupeKey): void
    {
        $this->sentLog[$dedupeKey] = now()->toDateTimeString();
        Storage::disk('local')->put($this->sentLogFile, json_encode($this->sentLog, JSON_PRETTY_PRINT));
    }
}