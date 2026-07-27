<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassLayout extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'school_year_id',
        'level',
        'grade',
        'section',
        'total_rows',
        'total_cols',
    ];

    public function seatAssignments()
    {
        return $this->hasMany(SeatAssignment::class, 'class_layout_id', 'id');
    }
}