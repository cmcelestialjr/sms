<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchoolTermSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('school_terms')->insert([
            // SCHOOL YEAR
            [
                'name' => 'Year',
                'shorten_name' => 'Year',
                'numeric' => '0',
                'shorten_numeric' => 'Year',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // SEMESTERS
            [
                'name' => 'First Semester',
                'shorten_name' => '1st Sem',
                'numeric' => '1',
                'shorten_numeric' => 'S1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Second Semester',
                'shorten_name' => '2nd Sem',
                'numeric' => '2',
                'shorten_numeric' => 'S2',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // TRIMESTERS
            [
                'name' => 'First Trimester',
                'shorten_name' => '1st Tri',
                'numeric' => '3',
                'shorten_numeric' => 'T1',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Second Trimester',
                'shorten_name' => '2nd Tri',
                'numeric' => '4',
                'shorten_numeric' => 'T2',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Third Trimester',
                'shorten_name' => '3rd Tri',
                'numeric' => '5',
                'shorten_numeric' => 'T3',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
