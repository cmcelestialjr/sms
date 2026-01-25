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

        $schoolYear = SchoolYear::with('term','teachers')
            ->when($search, function ($query, $search) {
                $query->where('sy_from', 'like', "%$search%")
                    ->orWhere('sy_to', 'like', "%$search%");
            });
        
        $schoolYears = $schoolYear->orderBy('sy_from','DESC')->paginate(10);

        return response()->json([
            'data' => $schoolYears->items(),
            'meta' => [
                'current_page' => $schoolYears->currentPage(),
                'last_page' => $schoolYears->lastPage(),
                'prev' => $schoolYears->previousPageUrl(),
                'next' => $schoolYears->nextPageUrl(),
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
            'sy_from' => 'required|integer|min:1000|max:9999',
            'sy_to' => 'required|integer|min:1000|max:9999|gte:sy_from',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $query = SchoolYear::create([
            'sy_from' => $request->sy_from,
            'sy_to' => $request->sy_to,
            'school_term_id' => 1,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ]);

        return response()->json($query, 200);
    }

    public function update(Request $request, $id)
    {
        $query = SchoolYear::find($id);

        if (!$query) {
            return response()->json(['error' => 'School Year not found'], 404);
        }

        $request->validate([
            'sy_from' => 'required|integer|min:1000|max:9999',
            'sy_to' => 'required|integer|min:1000|max:9999|gte:sy_from',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $query->update([
            'sy_from' => $request->sy_from,
            'sy_to' => $request->sy_to,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
        ]);

        return response()->json($query);
    }
}
