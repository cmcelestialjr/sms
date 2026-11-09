<?php

namespace App\Console\Commands;

use App\Models\Absence;
use App\Models\Attendance;
use App\Models\AttendanceDailySummary;
use App\Models\SchoolTerm;
use App\Models\SchoolYear;
use App\Models\SchoolYearStudent;
use App\Models\Station;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Models\Teacher;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SyncAttendifyCloudCommand extends Command
{
    protected $signature = 'attendify:sync-cloud {--fresh : Re-sync all records from the beginning}';
    protected $description = 'Sync local Laravel records to Supabase using per-table updated_at watermarks';

    private string $stateFile = 'supabase_sync_state.json';

    public function handle()
    {
        $state = $this->loadSyncState();

        try {
            // 1. Reference Tables
            $this->syncTable('school_terms', SchoolTerm::class, $state, fn($r) => [
                'id'              => $r->id,
                'name'            => $r->name,
                'shorten_name'    => $r->shorten_name,
                'numeric'         => $r->numeric,
                'shorten_numeric' => $r->shorten_numeric,
                'updated_at'      => optional($r->updated_at)->toIso8601String(),
            ]);

            $this->syncTable('school_years', SchoolYear::class, $state, fn($r) => [
                'id'             => $r->id,
                'sy_from'        => $r->sy_from,
                'sy_to'          => $r->sy_to,
                'school_term_id' => $r->school_term_id,
                'date_from'      => $r->date_from,
                'date_to'        => $r->date_to,
                'updated_at'     => optional($r->updated_at)->toIso8601String(),
            ]);

            $this->syncTable('teachers', Teacher::class, $state, fn($r) => [
                'id'             => $r->id,
                'user_id'        => $r->user_id,
                'id_no'          => $r->id_no,
                'lastname'       => $r->lastname,
                'firstname'      => $r->firstname,
                'middlename'     => $r->middlename,
                'extname'        => $r->extname,
                'contact_no'     => $r->contact_no,
                'email'          => $r->email,
                'position'       => $r->position,
                'school_year_id' => $r->school_year_id,
                'level'          => $r->level,
                'grade'          => $r->grade,
                'section'        => $r->section,
                'status'         => $r->status,
                'photo'          => $r->photo,
                'updated_at'     => optional($r->updated_at)->toIso8601String(),
            ]);

            $this->syncTable('stations', Station::class, $state, fn($r) => [
                'id'           => $r->id,
                'uuid'         => $r->uuid,
                'station_name' => $r->station_name,
                'ipaddress'    => $r->ipaddress,
                'location'     => $r->location,
                'updated_at'   => optional($r->updated_at)->toIso8601String(),
            ]);

            // 2. Students & Guardians
            $this->syncTable('students', Student::class, $state, fn($r) => [
                'id'             => $r->id,
                'student_id'     => $r->student_id,
                'access_code'    => $r->access_code,
                'lrn_no'         => $r->lrn_no,
                'lastname'       => $r->lastname,
                'firstname'      => $r->firstname,
                'middlename'     => $r->middlename,
                'extname'        => $r->extname,
                'contact_no'     => $r->contact_no,
                'email'          => $r->email,
                'qr_code'        => $r->qr_code,
                'rfid_tag'       => $r->rfid_tag,
                'sex'            => $r->sex,
                'birthdate'      => $r->birthdate,
                'address'        => $r->address,
                'photo'          => $r->photo,
                'school_year_id' => $r->school_year_id,
                'sy_from'        => $r->sy_from,
                'sy_to'          => $r->sy_to,
                'level'          => $r->level,
                'grade'          => $r->grade,
                'section'        => $r->section,
                'teachers_id'    => $r->teachers_id,
                'status'         => $r->status,
                'updated_at'     => optional($r->updated_at)->toIso8601String(),
            ]);

            $this->syncTable('student_guardians', StudentGuardian::class, $state, fn($r) => [
                'id'         => $r->id,
                'student_id' => $r->student_id,
                'name'       => $r->name,
                'contact_no' => $r->contact_no,
                'email'      => $r->email,
                'updated_at' => optional($r->updated_at)->toIso8601String(),
            ]);

            $this->syncTable('school_year_students', SchoolYearStudent::class, $state, fn($r) => [
                'id'             => $r->id,
                'student_id'     => $r->student_id,
                'school_year_id' => $r->school_year_id,
                'sy_from'        => $r->sy_from,
                'sy_to'          => $r->sy_to,
                'level'          => $r->level,
                'grade'          => $r->grade,
                'section'        => $r->section,
                'teacher_id'     => $r->teacher_id,
                'co_teacher_id'  => $r->co_teacher_id,
                'status'         => $r->status,
                'updated_at'     => optional($r->updated_at)->toIso8601String(),
            ]);

            // 3. Attendance Summaries, Scans, and Absences
            $this->syncTable('attendance_daily_summaries', AttendanceDailySummary::class, $state, fn($r) => [
                'id'             => $r->id,
                'student_id'     => $r->student_id,
                'date'           => $r->date,
                'actual_am_in'   => $r->actual_am_in,
                'actual_am_out'  => $r->actual_am_out,
                'actual_pm_in'   => $r->actual_pm_in,
                'actual_pm_out'  => $r->actual_pm_out,
                'school_year_id' => $r->school_year_id,
                'sy_from'        => $r->sy_from,
                'sy_to'          => $r->sy_to,
                'level'          => $r->level,
                'grade'          => $r->grade,
                'section'        => $r->section,
                'teachers_id'    => $r->teachers_id,
                'is_late'        => $r->is_late ?? 0,
                'is_undertime'   => $r->is_undertime ?? 0,
                'is_excused'     => $r->is_excused ?? 0,
                'is_absent'      => $r->is_absent,
                'remarks'        => $r->remarks,
                'updated_at'     => optional($r->updated_at)->toIso8601String(),
            ]);

            $this->syncTable('attendances', Attendance::class, $state, fn($r) => [
                'id'             => $r->id,
                'student_id'     => $r->student_id,
                'station_id'     => $r->station_id,
                'scanned_at'     => Carbon::parse($r->scanned_at)->toIso8601String(),
                'type'           => $r->type,
                'method'         => $r->method,
                'status'         => $r->status,
                'message'        => $r->message,
                'school_year_id' => $r->school_year_id,
                'sy_from'        => $r->sy_from,
                'sy_to'          => $r->sy_to,
                'level'          => $r->level,
                'grade'          => $r->grade,
                'section'        => $r->section,
                'teachers_id'    => $r->teachers_id,
                'updated_at'     => optional($r->updated_at)->toIso8601String(),
            ]);

            $this->syncTable('absences', Absence::class, $state, fn($r) => [
                'id'         => $r->id,
                'student_id' => $r->student_id,
                'date'       => $r->date,
                'updated_at' => optional($r->updated_at)->toIso8601String(),
            ]);

            $this->info('All modified tables synced to Supabase.');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Sync stopped (progress saved up to last table): ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function syncTable(string $table, string $modelClass, array &$state, callable $mapper): void
    {
        $lastSync = ($this->option('fresh') || empty($state[$table]))
            ? Carbon::createFromTimestamp(0)
            : Carbon::parse($state[$table]);

        $tableSyncStartedAt = now()->subSeconds(5);

        $records = $modelClass::where('updated_at', '>=', $lastSync)
            ->orderBy('updated_at', 'asc')
            ->get();

        if ($records->isEmpty()) {
            return;
        }

        foreach ($records->chunk(250) as $chunk) {
            $payload = $chunk->map($mapper)->values()->toArray();

            $response = Http::withHeaders([
                'apikey'        => env('SUPABASE_SERVICE_KEY'),
                'Authorization' => 'Bearer ' . env('SUPABASE_SERVICE_KEY'),
                'Content-Type'  => 'application/json',
                'Prefer'        => 'resolution=merge-duplicates',
            ])->timeout(20)->post(
                rtrim(env('SUPABASE_URL'), '/') . "/rest/v1/{$table}",
                $payload
            );

            if (!$response->successful()) {
                throw new \RuntimeException("Failed syncing [{$table}]: " . $response->body());
            }
        }

        // Save this table's watermark immediately so if a later table fails, this one won't re-run
        $state[$table] = $tableSyncStartedAt->toDateTimeString();
        Storage::disk('local')->put($this->stateFile, json_encode($state, JSON_PRETTY_PRINT));

        $this->line(" ✓ [{$table}]: Synced {$records->count()} modified row(s)");
    }

    private function loadSyncState(): array
    {
        if ($this->option('fresh') || !Storage::disk('local')->exists($this->stateFile)) {
            return [];
        }
        return json_decode(Storage::disk('local')->get($this->stateFile), true) ?? [];
    }
}