<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('en_PH');

        /**
         * ADMIN USER
         */
        User::firstOrCreate(
            [
                'name' => 'System Administrator',
                'username' => 'admin',
                'lastname' => 'Administrator',
                'firstname' => 'System',
                'middlename' => null,
                'extname' => null,
                'email' => 'admin@school.test',
                'email_verified_at' => now(),
                'password' => Hash::make('123'),
                'role_id' => 1,
                'photo' => null,
            ]
        );

        /**
         * TEACHERS
         */
        for ($i = 1; $i <= 10; $i++) {
            User::create([
                'name' => $faker->name,
                'username' => 'tch' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'lastname' => $faker->lastName,
                'firstname' => $faker->firstName,
                'middlename' => $faker->optional()->firstName,
                'extname' => $faker->optional()->suffix,
                'email' => $faker->unique()->safeEmail,
                'email_verified_at' => now(),
                'password' => Hash::make('123'),
                'role_id' => 3,
                'photo' => null,
            ]);
        }

        /**
         * STAFF
         */
        for ($i = 1; $i <= 5; $i++) {
            User::create([
                'name' => $faker->name,
                'username' => 'stf' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'lastname' => $faker->lastName,
                'firstname' => $faker->firstName,
                'middlename' => $faker->optional()->firstName,
                'extname' => null,                
                'email' => $faker->unique()->safeEmail,
                'email_verified_at' => now(),
                'password' => Hash::make('123'),
                'role_id' => 4,
                'photo' => null,
            ]);
        }
    }
}
