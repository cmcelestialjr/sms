<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DtrLog;
use App\Models\HolidayDate;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DtrController extends Controller
{

    public function index(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:users,id', 
            'display' => 'required|string',
            'value' => ['required', 'string', 'regex:/^\\d{4}-(0[1-9]|1[0-2])$/'],
            'start' => 'required|date',
            'end' => 'required|date',
            'duration' => 'required|string',
        ]);
        
        $groupedDtr = [];

        $user = User::find($validated['id']);

        $middlename = $user->middlename ? ' '.strtoupper(substr($user->middlename, 0, 1)) . '.' : '';
        $extname = $user->extname ? ' '.$user->extname : '';

        $name = $user->lastname.', '.$user->firstname.$middlename.$extname;

        $dtr = DtrLog::where('user_id',$validated['id'])
            ->where('date','>=',$validated['start'])
            ->where('date','<=',$validated['end'])
            ->orderBy('date', 'asc')
            ->orderBy('time', 'asc')
            ->get();

        $startOfMonth = Carbon::parse($validated['start'])->copy()->startOfMonth();
        $endOfMonth = Carbon::parse($validated['start'])->copy()->endOfMonth();
        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

        foreach ($period as $date) {
            $formattedDate = $date->format('Y-m-d');
            $dayOfWeek = $date->format('l');
            $groupedDtr[$formattedDate] = [
                'day' => $date->format('d'),
                'name' => in_array($dayOfWeek, ['Saturday', 'Sunday']) ? $dayOfWeek : '',
                'half_day' => '',
                'am_in' => null,
                'am_out' => null,
                'pm_in' => null,
                'pm_out' => null,
            ];
        }
    
        $holidays = HolidayDate::with('holiday')
            ->where(function ($query) use ($startOfMonth, $endOfMonth) {
                $query->where('date', '>=', Carbon::parse($startOfMonth)->format('Y-m-d'))
                    ->where('date', '<=', Carbon::parse($endOfMonth)->format('Y-m-d'));
            })
            // ->orWhere(function ($query) use ($startOfMonth, $endOfMonth) {
            //     $query->whereHas('holiday', function ($q) {
            //             $q->where('repeat', 'Yes');
            //         })
            //         ->whereMonth('date', $startOfMonth->month)
            //         ->whereDay('date', '>=', $startOfMonth->day)
            //         ->whereDay('date', '<=', $endOfMonth->day);
            // })
            ->get();
        if($holidays->count()>0){
            foreach($holidays as $holiday){
                $date = Carbon::parse($holiday->date)->format('Y-m-d');
                $groupedDtr[$date]['name'] = $holiday->holiday->name;
                $groupedDtr[$date]['half_day'] = $holiday->holiday->half_day;
            }
        }
        
        $dtrRaw = [];

        if($dtr->count()>0){
            foreach ($dtr as $log) {
                $date = Carbon::parse($log->date)->format('Y-m-d');
                $formattedDate = Carbon::parse($log->date)->format('F d, Y');
                $time = Carbon::parse($log->time);
                $hour = (int) $time->format('H');
                $minute = (int) $time->format('i');
                $timeStr = $time->format('h:ia');

                $dtrRaw[] = [
                    'date' => $formattedDate,
                    'time' => $timeStr,
                    'type' => $log->type
                ];

                if (!isset($groupedDtr[$date])) continue;

                $groupedDtr[$date]['name'] = 'time';

                if ($hour < 12) {
                    if ($log->type === 'in') {
                        $groupedDtr[$date]['am_in'] = $timeStr;
                    } elseif ($log->type === 'out') {
                        $groupedDtr[$date]['am_out'] = $timeStr;
                    }
                } elseif ($log->type === 'out' && ($hour < 13 || ($hour === 13 && $minute === 0))) {
                    $groupedDtr[$date]['am_out'] = $timeStr;
                } else {
                    if ($log->type === 'in') {
                        $groupedDtr[$date]['pm_in'] = $timeStr;
                    } elseif ($log->type === 'out') {
                        $groupedDtr[$date]['pm_out'] = $timeStr;
                    }
                }
            }
        }

        return response()->json([
            'data' => $groupedDtr,
            'raw' => $dtrRaw,
            'name' => $name
        ]);
    }

}
