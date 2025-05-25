<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'lrn_no',
        'lastname',
        'firstname',
        'middlename',
        'extname',
        'contact_no',
        'email',
        'qr_code',
        'rfid_tag',
        'sex',
        'birthdate',
        'address',
        'photo',
        'sy_from',
        'sy_to',
        'level',
        'grade',
        'section',
        'teachers_id',
        'teacher_id_requested',
        'teacher_id_approved',
        'teacher_id_pending',
        'user_id',
        'status'
    ];

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id', 'id');
    }
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teachers_id', 'user_id');
    }
}

