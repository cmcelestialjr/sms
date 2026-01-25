<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Absence;
use App\Models\Holiday;
use App\Models\HolidayDate;
use App\Models\SchoolYear;
use App\Models\SchoolYearStudent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $holidays = Holiday::when($search, function ($query, $search) {
                $query->where('date_from', 'like', "%$search%")
                    ->orWhere('date_to', 'like', "%$search%")
                    ->orWhere('name', 'like', "%$search%")
                    ->orWhere('type', 'like', "%$search%");
            })->orderBy('date_from','ASC')
            ->paginate(10);

        return response()->json([
            'data' => $holidays->items(),
            'meta' => [
                'current_page' => $holidays->currentPage(),
                'last_page' => $holidays->lastPage(),
                'prev' => $holidays->previousPageUrl(),
                'next' => $holidays->nextPageUrl(),
            ]
        ]);
    }

    public function show($id)
    {
        $query = Holiday::find($id);

        if (!$query) {
            return response()->json(['message' => 'Holiday not found'], 404);
        }

        return response()->json($query);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $user_id = $user->id;

        $request->validate([
            'name' => 'required',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'type' => 'required|string|in:Regular,Special',
            'repeat' => 'required|string|in:Yes,No',
            'half_day' => 'nullable|string|in:,am,pm',
        ]);

        $date_from = $request->date_from;
        $date_to = $request->date_to;

        $query = Holiday::create([
            'name' => $request->name,
            'date_from' => $request->date_from,
            'date_to' => $request->date_to,
            'type' => $request->type,
            'repeat' => $request->repeat,
            'half_day' => $request->half_day,
            'updated_by' => $user->id,
        ]);

        $holiday_id = $query->id;

        $this->insertDates($date_from,$date_to,$holiday_id,$user_id);

        $this->checkAbsences($holiday_id);

        return response()->json($query, 200);
    }

    public function update(Request $request, $id)
    {
        $query = Holiday::find($id);

        if (!$query) {
            return response()->json(['error' => 'Holiday not found'], 404);
        }

        $user = Auth::user();
        $user_id = $user->id;

        $request->validate([
            'name' => 'required',
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
            'type' => 'required|string|in:Regular,Special',
            'repeat' => 'required|string|in:Yes,No',
            'half_day' => 'nullable|string|in:,am,pm',
        ]);

        $date_from_old = $query->date_from;
        $date_to_old = $query->date_to;
        $date_from = $request->date_from;
        $date_to = $request->date_to;

        $query->update([
            'name' => $request->name,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'type' => $request->type,
            'repeat' => $request->repeat,
            'half_day' => $request->half_day,
            'updated_by' => $user_id,
        ]);

        $holiday_id = $query->id;

        $this->insertAbsences($date_from_old,$date_to_old,$holiday_id,$user_id);

        HolidayDate::where('holiday_id', $holiday_id)->delete();

        $this->insertDates($date_from,$date_to,$holiday_id,$user_id);

        $this->checkAbsences($holiday_id);

        return response()->json($query);
    }

    private function insertDates($date_from,$date_to,$holiday_id,$user_id)
    {
        $start = Carbon::parse($date_from);
        $end   = Carbon::parse($date_to);

        $dates = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dates[] = [
                'holiday_id' => $holiday_id,
                'date'       => $date->format('Y-m-d'),
                'updated_by' => $user_id,
                'updated_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
            ];
        }

        HolidayDate::insert($dates);
    }

    private function insertAbsences($date_from,$date_to,$holiday_id,$user_id)
    {
        $date_from = Carbon::parse($date_from);
        $date_to = Carbon::parse($date_to);

        $sy = SchoolYear::where('date_from','<=',$date_from)
            ->where('date_to','>=',$date_to)
            ->first();
        if($sy){
            $sy_from = $sy->sy_from;
            $sy_to = $sy->sy_to;

            $students = SchoolYearStudent::where('sy_from', '=', $sy_from)
                ->where('sy_to', '=', $sy_to)
                ->whereNotExists(function ($query) use ($date_from, $date_to) {
                    $query->select(DB::raw(1))
                        ->from('attendances')
                        ->whereRaw('attendances.student_id = school_year_students.student_id')
                        ->whereBetween(DB::raw('DATE(attendances.scanned_at)'), [$date_from, $date_to]);
                })
                ->pluck('student_id');

            $absences = [];            

            while ($date_from <= $date_to) {

                if ($date_from->isWeekend()) {
                    $date_from->addDay();
                    continue;
                }

                foreach ($students as $student_id) {
                    $absences[] = [
                        'student_id' => $student_id,
                        'date' => $date_from->toDateString(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (count($absences) >= 1000) {
                    Absence::insertOrIgnore($absences);
                    $absences = [];
                }

                $date_from->addDay();
            }

            if (count($absences) > 0) {
                Absence::insertOrIgnore($absences);
            }            
        }
    }

    private function checkAbsences($holiday_id)
    {
        Absence::whereIn('date', function($query) use ($holiday_id) {
                $query->select('date')
                    ->from('holiday_dates')
                    ->where('holiday_id', $holiday_id);
            })
            ->delete();
    }
}
