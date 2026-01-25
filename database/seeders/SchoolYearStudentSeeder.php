<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchoolYearStudentSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * Get ACTIVE teachers
         */
        $teachers = DB::table('teachers')
            ->where('status', 'Active')
            ->get();

        if ($teachers->isEmpty()) {
            throw new \Exception('No teachers found. Run TeacherSeeder first.');
        }

        /**
         * Get all students
         */
        $students = DB::table('students')->get();

        foreach ($students as $student) {

            /**
             * Pick a teacher (random or based on your own rule)
             * Here: RANDOM teacher
             */
            $teacher = $teachers->random();

            DB::table('school_year_students')->insert([
                'student_id' => $student->id,
                
                // ✅ INFO COMES FROM TEACHER
                'school_year_id' => $teacher->school_year_id,
                'sy_from' => $teacher->sy_from,
                'sy_to' => $teacher->sy_to,                
                'level' => $teacher->level,
                'grade' => $teacher->grade,
                'section' => $teacher->section,

                // teacher is stored as USER ID
                'teacher_id' => $teacher->user_id,

                'status' => 'Active',
                'date_enrolled' => now()->toDateString(),
                'is_transferred' => 0,
                'out_date' => null,
                'out_type' => null,

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
