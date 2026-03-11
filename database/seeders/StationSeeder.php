<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StationSeeder extends Seeder
{
    public function run(): void
    {
        $uuid = (string) Str::uuid();
        DB::table('stations')->updateOrInsert(
            [
                'station_name' => 'Main',
            ],
            [
                'uuid' => $uuid,
                'ipaddress' => '127.0.0.1',
                'location' => 'Main',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
