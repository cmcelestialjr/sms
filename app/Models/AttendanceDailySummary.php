<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceDailySummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'date',
        'actual_am_in',
        'actual_am_out',
        'actual_pm_in',
        'actual_pm_out',
        'school_year_id',
        'sy_from',
        'sy_to',
        'level',
        'grade',
        'section',
        'teachers_id',
        'is_late', //0 = no, 1 = yes
        'is_undertime', //0 = no, 1 = yes
        'is_excused', //0 = no, 1 = yes
        'is_absent', //null = present, 1 = absent
        'remarks',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
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