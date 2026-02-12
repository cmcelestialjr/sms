<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GradeController extends Controller
{
    public function index()
    {
        $grades = Teacher::select('grade')
                    ->distinct()
                    ->orderBy('grade', 'ASC')
                    ->pluck('grade');

        return response()->json($grades);
    }
}
