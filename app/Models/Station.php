<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Station extends Model
{
    use HasFactory;

    protected $fillable = [
        'station_name',
        'ipaddress',
        'location',
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
