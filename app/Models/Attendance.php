<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'message',
        'school_year_id',
        'sy_from',
        'sy_to',
        'level',
        'grade',
        'section',
        'teachers_id',
        'message_status'
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    public function station(): BelongsTo
    {
        return $this->belongsTo(Station::class, 'station_id', 'id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teachers_id', 'id');
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id', 'id');
    }
}
