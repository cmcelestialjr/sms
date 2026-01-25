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
use Illuminate\Support\Facades\DB;

class AbsencesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $student_id;
    protected $scanned_at;

    public function __construct($student_id, $scanned_at)
    {
        $this->student_id = $student_id;
        $this->scanned_at = date('Y-m-d', strtotime($scanned_at));
    }

    public function handle(): void
    {
        $scanned_at = $this->scanned_at;

        $sy = SchoolYear::where('date_from', '<=', $scanned_at)
            ->where('date_to', '>=', $scanned_at)
            ->first();

        if($sy){
            $sy_from = $sy->sy_from;
            $sy_to = $sy->sy_to;
            $date_from = Carbon::parse($sy->date_from);
            $date_to = Carbon::parse($sy->date_to);

            
            // if($date_from<=$scanned_at && $date_to>=$scanned_at){
            //     DB::statement("
            //         INSERT INTO absences (student_id, date, updated_at, created_at)
            //         SELECT student_id, ?, NOW(), NOW()
            //         FROM school_year_students
            //         WHERE sy_from = ? AND sy_to = ?
            //         AND student_id NOT IN (SELECT student_id FROM absences WHERE date = ?)
            //     ", [$scanned_at, $sy_from, $sy_to, $scanned_at]);
            // }

            $students = SchoolYearStudent::where('sy_from', '=', $sy_from)
                ->where('sy_to', '=', $sy_to)
                ->whereNotExists(function ($query) use ($scanned_at) {
                    $query->select(DB::raw(1))
                        ->from('attendances')
                        ->whereRaw('attendances.student_id = school_year_students.student_id')
                        ->whereDate('attendances.scanned_at', '=', $scanned_at);
                })
                ->pluck('student_id');

            $nonRepeating = HolidayDate::where('repeat', '!=', 'yes')
                ->whereBetween('date', [$date_from, $date_to])
                ->pluck('date')
                ->toArray();

            $repeating = HolidayDate::where('repeat', 'yes')
                ->get()
                ->filter(function ($holiday) use ($date_from, $date_to) {
                    $original = Carbon::parse($holiday->date);
                    $adjusted = Carbon::create($date_from->year, $original->month, $original->day);

                    return $adjusted->between($date_from, $date_to);
                })
                ->map(function ($holiday) use ($date_from) {
                    $original = Carbon::parse($holiday->date);
                    return Carbon::create($date_from->year, $original->month, $original->day)->toDateString();
                })
                ->toArray();

            $holiday_dates = array_merge($nonRepeating, $repeating);

            $absences = [];

            while ($date_from <= $date_to) {

                if ($date_from->isWeekend()) {
                    $date_from->addDay();
                    continue;
                }

                if (in_array($date_from->toDateString(), $holiday_dates)) {
                    $date_from->addDay();
                    continue;
                }

                foreach ($students as $student_id) {
                    $absences[] = [
                        'student_id' => $student_id,
                        'date' => $date_from->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (count($absences) >= 1000) {
                    Absence::insertOrIgnore($absences);
                    $absences = [];
                }

                $date_from->addDay();
            }

            if (count($absences) > 0) {
                Absence::insertOrIgnore($absences);
            }

            Absence::whereIn('date', $holiday_dates)->delete();
        }

        Absence::where('student_id', $this->student_id)
            ->where('date', $scanned_at)
            ->delete();
    }
}