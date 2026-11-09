<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    protected $casts = [
        'last_active_at' => 'datetime',
    ];

    protected $appends = ['status']; 

    public function getStatusAttribute()
    {
        if (!$this->last_active_at) {
            return 'Offline';
        }

        return $this->last_active_at->diffInMinutes(now()) <= 2 ? 'Online' : 'Offline';
    }
}