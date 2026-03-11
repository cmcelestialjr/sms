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
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'station_id', 'id');
    }
}
