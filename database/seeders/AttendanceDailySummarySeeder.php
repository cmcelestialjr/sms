<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceDailySummarySeeder extends Seeder
{
    public function run(): void
    {
        /**
         * Get attendance grouped by student + date
         */
        $records = DB::table('attendances')
            ->select(
                'student_id',
                DB::raw('DATE(scanned_at) as date'),
                DB::raw('MIN(CASE WHEN type = "In" THEN TIME(scanned_at) END) as first_in'),
                DB::raw('MAX(CASE WHEN type = "Out" THEN TIME(scanned_at) END) as last_out'),
                DB::raw('MIN(school_year_id) as school_year_id'),
                DB::raw('MIN(sy_from) as sy_from'),
                DB::raw('MIN(sy_to) as sy_to'),
                DB::raw('MIN(level) as level'),
                DB::raw('MIN(grade) as grade'),
                DB::raw('MIN(section) as section'),
                DB::raw('MIN(teachers_id) as teachers_id')
            )
            ->groupBy('student_id', DB::raw('DATE(scanned_at)'))
            ->get();

        foreach ($records as $row) {

            $amIn = $row->first_in;
            $pmOut = $row->last_out;

            // Late if AM IN > 8:00 AM
            $isLate = $amIn && $amIn > '08:00:00' ? 1 : 0;

            // Undertime if PM OUT < 4:00 PM
            $isUndertime = $pmOut && $pmOut < '16:00:00' ? 1 : 0;

            DB::table('attendance_daily_summaries')->insert([
                'student_id' => $row->student_id,
                'date' => $row->date,

                // Using same time for simplicity
                'actual_am_in' => $amIn,
                'actual_am_out' => null,
                'actual_pm_in' => null,
                'actual_pm_out' => $pmOut,

                'school_year_id' => $row->school_year_id,
                'sy_from' => $row->sy_from,
                'sy_to' => $row->sy_to,

                'level' => $row->level,
                'grade' => $row->grade,
                'section' => $row->section,
                'teachers_id' => $row->teachers_id,

                'is_late' => $isLate,
                'is_undertime' => $isUndertime,
                'is_excused' => 0,

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
