<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SchoolYear;
use App\Models\Station;
use Illuminate\Http\Request;

class SchoolYearController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $students = SchoolYear::with('term','teachers')
            ->when($search, function ($query, $search) {
            $query->where('sy_from', 'like', "%$search%")
                ->orWhere('sy_to', 'like', "%$search%");
        });
        
        $students = $students->orderBy('sy_from','DESC')->paginate(10);

        return response()->json([
            'data' => $students->items(),
            'meta' => [
                'current_page' => $students->currentPage(),
                'last_page' => $students->lastPage(),
                'prev' => $students->previousPageUrl(),
                'next' => $students->nextPageUrl(),
            ]
        ]);
    }

    public function show($id)
    {
        $query = SchoolYear::find($id);

        if (!$query) {
            return response()->json(['message' => 'School Year not found'], 404);
        }

        return response()->json($query);
    }

    public function lists(Request $request)
    {
        $search = $request->query('search');

        $stations = Station::when($search, function ($query, $search) {
            $query->where('station_name', 'like', "%$search%")
                ->orWhere('location', 'like', "%$search%");
        })->paginate(10);

        return response()->json([
            'data' => $stations->items(),
            'meta' => [
                'current_page' => $stations->currentPage(),
                'last_page' => $stations->lastPage(),
                'prev' => $stations->previousPageUrl(),
                'next' => $stations->nextPageUrl(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'year_from' => 'required|integer|min:1000|max:9999',
            'year_to' => 'required|integer|min:1000|max:9999|gte:year_from',
        ]);

        $query = SchoolYear::create([
            'year_from' => $request->year_from,
            'year_to' => $request->year_to,
            'school_term_id' => 1
        ]);

        return response()->json($query, 200);
    }

    public function update(Request $request, $id)
    {
        $query = SchoolYear::find($id);

        if (!$query) {
            return response()->json(['error' => 'Station not found'], 404);
        }

        $request->validate([
            'year_from' => 'required|integer|min:1000|max:9999',
            'year_to' => 'required|integer|min:1000|max:9999|gte:year_from',
        ]);

        $query->update([
            'year_from' => $request->year_from,
            'year_to' => $request->year_to,
        ]);

        return response()->json($query);
    }
}
