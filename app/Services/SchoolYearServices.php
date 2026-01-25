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

class SchoolYearServices
{
    public function getSchoolYear()
    {
        $currentMonth = date('m');
        if ($currentMonth >= 5) {
            $sy_from = date('Y');
            $sy_to = date('Y') + 1;
        } else {
            $sy_from = date('Y') - 1;
            $sy_to = date('Y');
        }

        $school_year = SchoolYear::where('sy_from', $sy_from)
            ->where('sy_to', $sy_to)
            ->where('school_term_id', 1)
            ->first();

        if(!$school_year) {
            $insertSchoolYear = new SchoolYear();
            $insertSchoolYear->sy_from = $sy_from;
            $insertSchoolYear->sy_to = $sy_to;
            $insertSchoolYear->school_term_id = 1;
            $insertSchoolYear->date_from = $sy_from . '-06-01';
            $insertSchoolYear->date_to = $sy_to . '-03-31';
            $insertSchoolYear->enrollment_start = $sy_from . '-04-01';
            $insertSchoolYear->enrollment_end = $sy_from . '-05-31';
            $insertSchoolYear->save();
            $school_year_id = $insertSchoolYear->id;
        }else{
            $school_year_id = $school_year->id;
        }

        return [
            'school_year_id' => $school_year_id,
            'sy_from' => $sy_from,
            'sy_to' => $sy_to
        ];
    }
}