<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SchoolYearSeeder extends Seeder
{
    public function run(): void
    {
        // Get the SCHOOL YEAR term ID (SY)
        $schoolYearTermId = DB::table('school_terms')
            ->where('shorten_name', 'Year')
            ->value('id');

        if (!$schoolYearTermId) {
            throw new \Exception('School Year term (SY) not found. Run SchoolTermSeeder first.');
        }

        $startYear = 2024;
        $currentYear = now()->year - 1;

        for ($year = $startYear; $year <= $currentYear; $year++) {
            DB::table('school_years')->insert([
                'sy_from' => $year,
                'sy_to' => $year + 1,
                'school_term_id' => $schoolYearTermId,

                // Typical PH academic calendar (adjust if needed)
                'date_from' => $year . '-06-01',
                'date_to' => ($year + 1) . '-03-31',

                'enrollment_start' => $year . '-04-01',
                'enrollment_end' => $year . '-05-31',

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
