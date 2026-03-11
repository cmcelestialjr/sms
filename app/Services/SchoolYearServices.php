<?php
namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductsPrice;
use App\Models\Sale;
use App\Models\SalesPayment;
use App\Models\SalesProduct;
use App\Models\SchoolYear;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SchoolYearServices
{
    public function getSchoolYear()
    {
        // Use Cache to avoid hitting the database on EVERY single scan.
        // The school year doesn't change every second!
        return Cache::remember('current_school_year_data', 3600, function () {
            $now = now();
            $currentMonth = $now->month;
            $currentYear = $now->year;

            // Determine years based on May (Month 5) cutoff
            if ($currentMonth >= 5) {
                $sy_from = $currentYear;
                $sy_to = $currentYear + 1;
            } else {
                $sy_from = $currentYear - 1;
                $sy_to = $currentYear;
            }

            // Use firstOrCreate to prevent race conditions (it handles the check-and-insert atomically)
            $schoolYear = SchoolYear::firstOrCreate(
                [
                    'sy_from' => $sy_from,
                    'sy_to' => $sy_to,
                    'school_term_id' => 1
                ],
                [
                    'date_from'        => "{$sy_from}-06-01",
                    'date_to'          => "{$sy_to}-03-31",
                    'enrollment_start' => "{$sy_from}-04-01",
                    'enrollment_end'   => "{$sy_from}-05-31",
                ]
            );

            return [
                'school_year_id' => $schoolYear->id,
                'sy_from'        => $sy_from,
                'sy_to'          => $sy_to
            ];
        });
    }
    // public function getSchoolYear()
    // {
    //     $currentMonth = date('m');
    //     if ($currentMonth >= 5) {
    //         $sy_from = date('Y');
    //         $sy_to = date('Y') + 1;
    //     } else {
    //         $sy_from = date('Y') - 1;
    //         $sy_to = date('Y');
    //     }

    //     $school_year = SchoolYear::where('sy_from', $sy_from)
    //         ->where('sy_to', $sy_to)
    //         ->where('school_term_id', 1)
    //         ->first();

    //     if(!$school_year) {
    //         $insertSchoolYear = new SchoolYear();
    //         $insertSchoolYear->sy_from = $sy_from;
    //         $insertSchoolYear->sy_to = $sy_to;
    //         $insertSchoolYear->school_term_id = 1;
    //         $insertSchoolYear->date_from = $sy_from . '-06-01';
    //         $insertSchoolYear->date_to = $sy_to . '-03-31';
    //         $insertSchoolYear->enrollment_start = $sy_from . '-04-01';
    //         $insertSchoolYear->enrollment_end = $sy_from . '-05-31';
    //         $insertSchoolYear->save();
    //         $school_year_id = $insertSchoolYear->id;
    //     }else{
    //         $school_year_id = $school_year->id;
    //     }

    //     return [
    //         'school_year_id' => $school_year_id,
    //         'sy_from' => $sy_from,
    //         'sy_to' => $sy_to
    //     ];
    // }
}