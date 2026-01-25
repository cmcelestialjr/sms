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
        'lastname',
        'firstname',
        'middlename',
        'extname',
        'contact_no',
        'email',
        'school_year_id',
        'photo',
        'sy_from',
        'sy_to',
        'level',
        'grade',
        'section',
        'teachers_id',
        'user_id',
        'status',
        'address',
        'sex',
        'position',
    ];

    protected $appends = ['students_count'];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function students(): HasMany 
    {
        return $this->hasMany(Student::class, 'teachers_id', 'user_id');
    }

    public function getStudentsCountAttribute(): int 
    {
        return $this->students()->count();
    }
    
    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id', 'id');
    }
}

