<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // Main station
        $station = DB::table('stations')->first();

        if (!$station) {
            throw new \Exception('No station found.');
        }

        // Students
        $students = DB::table('students')->get();

        // Date range: whole CURRENT month
        $startDate = Carbon::now()->startOfMonth();
        $endDate   = Carbon::now()->endOfMonth();

        foreach ($students as $student) {

            // Skip students without teacher
            if (!$student->teachers_id) {
                continue;
            }

            /**
             * 1️⃣ Collect SCHOOL DAYS
             */
            $schoolDays = [];
            $cursor = $startDate->copy();

            while ($cursor->lte($endDate)) {
                if (!$cursor->isWeekend()) {
                    $schoolDays[] = $cursor->toDateString();
                }
                $cursor->addDay();
            }

            /**
             * 2️⃣ Pick ABSENT DAYS (3–6 days)
             */
            $absentCount = rand(3, 6);
            $absentDays = collect($schoolDays)
                ->random(min($absentCount, count($schoolDays)))
                ->toArray();

            /**
             * 3️⃣ Insert ABSENCES
             */
            foreach ($absentDays as $absentDate) {
                DB::table('absences')->insert([
                    'student_id' => $student->id,
                    'date' => $absentDate,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            /**
             * 4️⃣ Insert ATTENDANCE for NON-ABSENT days
             */
            foreach ($schoolDays as $day) {

                // ❌ Skip absent days
                if (in_array($day, $absentDays)) {
                    continue;
                }

                $date = Carbon::parse($day);

                // IN time (6:30–8:30 AM)
                $inTime = $date->copy()->setTime(
                    rand(6, 8),
                    rand(0, 59)
                );

                DB::table('attendances')->insert([
                    'student_id' => $student->id,
                    'station_id' => $station->id,
                    'scanned_at' => $inTime,
                    'type' => 'In',
                    'method' => rand(0, 1) ? 'qr' : 'rfid',
                    'status' => 'success',
                    'message' => 'Time In recorded',

                    'school_year_id' => $student->school_year_id,
                    'sy_from' => $student->sy_from,
                    'sy_to' => $student->sy_to,

                    'level' => $student->level,
                    'grade' => $student->grade,
                    'section' => $student->section,
                    'teachers_id' => $student->teachers_id,

                    'message_status' => 'Success',
                    'created_at' => $inTime,
                    'updated_at' => $inTime,
                ]);

                // OUT time (3:30–5:30 PM)
                $outTime = $date->copy()->setTime(
                    rand(15, 17),
                    rand(0, 59)
                );

                DB::table('attendances')->insert([
                    'student_id' => $student->id,
                    'station_id' => $station->id,
                    'scanned_at' => $outTime,
                    'type' => 'Out',
                    'method' => rand(0, 1) ? 'qr' : 'rfid',
                    'status' => 'success',
                    'message' => 'Time Out recorded',

                    'school_year_id' => $student->school_year_id,
                    'sy_from' => $student->sy_from,
                    'sy_to' => $student->sy_to,

                    'level' => $student->level,
                    'grade' => $student->grade,
                    'section' => $student->section,
                    'teachers_id' => $student->teachers_id,

                    'message_status' => 'Success',
                    'created_at' => $outTime,
                    'updated_at' => $outTime,
                ]);
            }
        }
    }
}
