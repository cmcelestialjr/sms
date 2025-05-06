<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'station_id',
        'scanned_at',
        'type',
        'method',
        'status',
        'message'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function station()
    {
        return $this->belongsTo(Station::class);
    }
}
