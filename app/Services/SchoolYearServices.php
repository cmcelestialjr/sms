<?php
namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductsPrice;
use App\Models\Sale;
use App\Models\SalesPayment;
use App\Models\SalesProduct;
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
        return [
            'sy_from' => $sy_from,
            'sy_to' => $sy_to
        ];
    }
}