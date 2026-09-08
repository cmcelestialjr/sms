<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Station extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'station_name',
        'ipaddress',
        'location',
        'last_active_at',
    ];

    protected $appends = ['status']; 

    public function getStatusAttribute()
    {
        if (!$this->last_active_at) {
            return 'Offline';
        }

        // If the tablet checked in within the last 2 minutes, it is Online.
        $lastActive = Carbon::parse($this->last_active_at);
        return $lastActive->diffInMinutes(now()) <= 2 ? 'Online' : 'Offline';
    }
}
