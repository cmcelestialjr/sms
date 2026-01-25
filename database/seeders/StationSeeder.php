<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StationSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('stations')->updateOrInsert(
            [
                // unique condition
                'station_name' => 'Main',
            ],
            [
                'ipaddress' => '127.0.0.1',
                'location' => 'Main',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
