<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UsersRolesSeeder::class,
            SchoolTermSeeder::class,
            SchoolYearSeeder::class,
            UserSeeder::class,
            TeacherSeeder::class,
            StudentSeeder::class,
            SchoolYearStudentSeeder::class,
            StationSeeder::class,
            AttendanceSeeder::class,
            AttendanceDailySummarySeeder::class, 
        ]);
    }
}
