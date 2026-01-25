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
        'school_year_id',
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

    protected static function booted()
    {
        static::creating(function ($student) {
            if (!$student->student_id) {
                $year = now()->year;

                $last = self::where('student_id', 'LIKE', "$year%")
                    ->lockForUpdate()
                    ->orderByDesc('student_id')
                    ->first();

                $number = $last
                    ? intval(substr($last->student_id, 4)) + 1
                    : 1;

                $student->student_id = $year .  str_pad($number, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id', 'id');
    }
    public function attendanceDailySummary(): HasMany
    {
        return $this->hasMany(AttendanceDailySummary::class, 'student_id', 'id');
    }
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'teachers_id', 'user_id');
    }
    public function schoolYearStudents()
    {
        return $this->hasMany(SchoolYearStudent::class, 'student_id', 'id');
    }
    public function absences()
    {
        return $this->hasMany(Absence::class, 'student_id', 'id');
    }
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id', 'id');
    }
}

