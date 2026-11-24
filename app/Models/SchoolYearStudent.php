<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolYearStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'school_year_id',
        'sy_from',
        'sy_to',
        'level',
        'grade',
        'section',
        'teacher_id',
        'co_teacher_id', // nullable, co-adviser's user_id
        'status',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teacher_id', 'user_id');
    }

    public function coTeacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'co_teacher_id', 'user_id');
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id', 'id');
    }
}