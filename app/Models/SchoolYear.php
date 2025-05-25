<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'sy_from',
        'sy_to',
        'school_term_id',
    ];

    protected $appends = ['teachers_count', 'students_count'];

    public function term(): BelongsTo
    {
        return $this->belongsTo(SchoolTerm::class, 'school_term_id', 'id');
    }
    public function teachers()
    {
        return $this->hasMany(Teacher::where('sy_from', $this->sy_from)
                ->where('sy_to', $this->sy_to));
    }
    public function getTeachersCountAttribute(): int
    {
        return Teacher::where('sy_from', $this->sy_from)
                ->where('sy_to', $this->sy_to)
                ->count();
    }
    public function getStudentsCountAttribute(): int
    {
        return Student::where('sy_from', $this->sy_from)
                ->where('sy_to', $this->sy_to)
                ->count();
    }
}
