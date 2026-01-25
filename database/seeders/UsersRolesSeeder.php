<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsersRolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users_roles')->insert([
            ['id' => 1, 'name' => 'Admin'],
            ['id' => 2, 'name' => 'Principal'],
            ['id' => 3, 'name' => 'Teacher'],
            ['id' => 4, 'name' => 'Staff'],
            ['id' => 5, 'name' => 'Student'],
        ]);
    }
}
