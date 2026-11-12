<?php

namespace App\Jobs;

use App\Models\Absence;
use App\Models\HolidayDate;
use App\Models\SchoolYear;
use App\Models\SchoolYearStudent;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AbsencesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Retry up to 3 times if a database lock occurs.
     */
    public int $tries = 3;

    /**
     * Wait 5 seconds before retrying.
     */
    public int $backoff = 5;

    /**
     * Prevent the worker from hanging indefinitely.
     */
    public int $timeout = 60;

    protected $student_id;
    protected $scanned_at;

    public function __construct($student_id, $scanned_at)
    {
        $this->student_id = $student_id;
        $this->scanned_at = date('Y-m-d', strtotime($scanned_at));
    }

    public function handle(): void
    {
        $scannedDate = Carbon::parse($this->scanned_at);
        $dateString = $scannedDate->toDateString();

        // 1. Always remove the absence record for the student who just scanned (Fast: < 2ms)
        Absence::where('student_id', $this->student_id)
            ->where('date', $dateString)
            ->delete();

        // 2. Skip generating absences if the scan date is a weekend
        if ($scannedDate->isWeekend()) {
            return;
        }

        // 3. Only seed the day's absences ONCE per date (prevents 500 heavy loops during morning rush)
        $dailyLockKey = "absences_seeded_{$dateString}";
        if (!Cache::add($dailyLockKey, true, now()->addHours(24))) {
            return;
        }

        // 4. Verify the date falls within an active School Year
        $sy = SchoolYear::where('date_from', '<=', $dateString)
            ->where('date_to', '>=', $dateString)
            ->first();

        if (!$sy) {
            return;
        }

        // 5. Check if $dateString is a non-repeating or repeating holiday
        $isNonRepeatingHoliday = HolidayDate::where('repeat', '!=', 'yes')
            ->whereDate('date', $dateString)
            ->exists();

        $isRepeatingHoliday = HolidayDate::where('repeat', 'yes')
            ->whereMonth('date', $scannedDate->month)
            ->whereDay('date', $scannedDate->day)
            ->exists();

        if ($isNonRepeatingHoliday || $isRepeatingHoliday) {
            Absence::where('date', $dateString)->delete();
            return;
        }

        // 6. Find enrolled students who have NOT scanned on $dateString
        $absentStudentIds = SchoolYearStudent::where('sy_from', $sy->sy_from)
            ->where('sy_to', $sy->sy_to)
            ->where('student_id', '!=', $this->student_id)
            ->whereNotExists(function ($query) use ($dateString) {
                $query->select(DB::raw(1))
                    ->from('attendances')
                    ->whereRaw('attendances.student_id = school_year_students.student_id')
                    ->whereDate('attendances.scanned_at', $dateString);
            })
            ->pluck('student_id');

        if ($absentStudentIds->isEmpty()) {
            return;
        }

        // 7. Insert absences ONLY for $dateString in safe chunks
        $now = now();
        $rows = $absentStudentIds->map(fn($studentId) => [
            'student_id' => $studentId,
            'date'       => $dateString,
            'created_at' => $now,
            'updated_at' => $now,
        ])->toArray();

        foreach (array_chunk($rows, 500) as $chunk) {
            Absence::insertOrIgnore($chunk);
        }
    }
}