<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SectionController extends Controller
{
    public function index()
    {
        $sections = Teacher::select('section')
                    ->distinct()
                    ->orderBy('section', 'ASC')
                    ->pluck('section');
        return response()->json($sections);
    }
}
