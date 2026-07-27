<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeatAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_layout_id',
        'student_id',
        'row_position',
        'col_position',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }
}