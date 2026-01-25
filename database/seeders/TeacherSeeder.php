<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('en_PH');

        /**
         * Get MOST RECENT school year
         */
        $latestSchoolYear = DB::table('school_years')
            ->orderByDesc('sy_from')
            ->first();

        if (!$latestSchoolYear) {
            throw new \Exception('No school year found. Run SchoolYearSeeder first.');
        }

        /**
         * Get users who are TEACHERS
         */
        $teacherUsers = DB::table('users')
            ->where('role_id', 3)
            ->get();

        if ($teacherUsers->isEmpty()) {
            throw new \Exception('No teacher users found. Run UserSeeder first.');
        }

        /**
         * Get last teacher id_no (for increment)
         * Expected format: YYYY-0001
         */
        $lastTeacher = DB::table('teachers')
            ->orderByDesc('id')
            ->first();

        $year = now()->year;
        $nextNumber = 1;

        if ($lastTeacher && preg_match('/\d{4}-(\d+)/', $lastTeacher->id_no, $matches)) {
            $nextNumber = intval($matches[1]) + 1;
        }

        foreach ($teacherUsers as $user) {

            $idNo = $year . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
            $nextNumber++;

            DB::table('teachers')->insert([
                // ✅ INCREMENTING ID NO
                'id_no' => $idNo,

                // Identity from users
                'lastname' => $user->lastname,
                'firstname' => $user->firstname,
                'middlename' => $user->middlename,
                'extname' => $user->extname,
                'email' => $user->email,

                // Teacher-specific fields
                'contact_no' => $faker->boolean(70) ? $faker->phoneNumber : null,
                'address' => $faker->boolean(70) ? $faker->address : null,
                'sex' => $faker->randomElement(['Male', 'Female']),
                'position' => 'Teacher',

                'sy_from' => $latestSchoolYear->sy_from,
                'sy_to' => $latestSchoolYear->sy_to,

                'level' => $faker->randomElement([
                    'Kinder',
                    'Elementary',
                    'Junior High School',
                    'Senior High School',
                ]),

                'grade' => $faker->randomElement([
                    'Kinder',
                    'Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6',
                    'Grade 7','Grade 8','Grade 9','Grade 10','Grade 11','Grade 12',
                ]),

                'section' => 'Section ' . strtoupper($faker->randomLetter),
                'status' => 'Active',
                'photo' => null,

                // Links
                'school_year_id' => $latestSchoolYear->id,
                'user_id' => $user->id,

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
