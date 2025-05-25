<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\Request;

class StationController extends Controller
{
    public function index()
    {
        return response()->json(Station::all());
    }

    public function show($id)
    {
        $station = Station::find($id);

        if (!$station) {
            return response()->json(['message' => 'Station not found'], 404);
        }

        return response()->json($station);
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
            'station_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
        ]);

        $station = Station::create([
            'station_name' => $request->station_name,
            'location' => $request->location,
        ]);

        return response()->json($station, 201);
    }

    public function update(Request $request, $id)
    {
        $station = Station::find($id);

        if (!$station) {
            return response()->json(['error' => 'Station not found'], 404);
        }

        $request->validate([
            'station_name' => 'required|string|max:255',
            'location' => 'required|string|max:255',
        ]);

        $station->update([
            'station_name' => $request->station_name,
            'location' => $request->location,
        ]);

        return response()->json($station);
    }
}
