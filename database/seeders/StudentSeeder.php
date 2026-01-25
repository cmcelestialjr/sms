<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('en_PH');

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
         * Create students
         */
        for ($i = 1; $i <= 100; $i++) {

            // Pick a teacher (this defines the class)
            $teacher = $teachers->random();

            Student::create([
                'lrn_no' => $faker->unique()->numerify('##########'),
                'lastname' => $faker->lastName,
                'firstname' => $faker->firstName,
                'middlename' => $faker->optional()->firstName,
                'extname' => $faker->optional()->suffix,
                'contact_no' => $faker->optional()->phoneNumber,
                'email' => $faker->boolean(70) ? $faker->unique()->safeEmail : null,
                'qr_code' => $faker->optional()->uuid,
                'rfid_tag' => $faker->optional()->uuid,
                'sex' => $faker->randomElement(['Male', 'Female']),
                'birthdate' => $faker->date(),
                'address' => $faker->optional()->address,
                'photo' => null,
                
                // 🔑 INFO (from TEACHER)
                'school_year_id' => $teacher->school_year_id,
                'sy_from' => $teacher->sy_from,
                'sy_to' => $teacher->sy_to,                
                'level' => $teacher->level,
                'grade' => $teacher->grade,
                'section' => $teacher->section,

                'status' => 'Active',

                // 🔑 TEACHER ASSIGNMENT
                'teachers_id' => $teacher->user_id,

                // optional workflow fields
                'teacher_id_requested' => null,
                'teacher_id_approved' => null,
                'teacher_id_pending' => null,
                'user_id' => null,
            ]);
        }
    }
}
