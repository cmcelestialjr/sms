<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_no',
        'lastname',
        'firstname',
        'middlename',
        'extname',
        'contact_no',
        'email',
        'address',
        'sex',
        'position',
        'school_year_id',        
        'sy_from',
        'sy_to',
        'level',
        'grade',
        'section',
        'status',
        'photo',
        'co_adviser', //null, 1
        'user_id'
    ];

    protected $appends = [
        'students_count',
        'active_students_count',
        'active_students_list',
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function students(): HasMany 
    {
        return $this->hasMany(Student::class, 'teachers_id', 'user_id');
    }

    public function activestudents(): HasMany
    {
        return $this->hasMany(Student::class, 'teachers_id', 'user_id')
            ->where('status', 'Active');
    }

    public function getStudentsCountAttribute(): int 
    {
        return $this->students()->count();
    }

    public function getActiveStudentsListAttribute()
    {
        return $this->activestudents->filter(function ($student) {
            return $student->school_year_id == $this->school_year_id
                && $student->level == $this->level
                && $student->grade == $this->grade
                && $student->section == $this->section;
        })->values();
    }

    public function getActiveStudentsCountAttribute(): int 
    {
        return $this->activestudents()->count();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id', 'id');
    }

    public function schoolYearStudents(): HasMany
    {
        return $this->hasMany(SchoolYearStudent::class, 'teacher_id', 'user_id');
    }
}

