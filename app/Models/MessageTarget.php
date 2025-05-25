<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MessageTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'student_id',
        'teacher_id',
        'grade',
        'section',
        'status'
    ];

    public function message()
    {
        return $this->belongsTo(Message::class, 'message_id', 'id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id', 'id');
    }
}
