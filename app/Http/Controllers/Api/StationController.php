<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Station;
use Illuminate\Http\Request;

class StationController extends Controller
{
    public function index()
    {
        // Return all stations
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

}
