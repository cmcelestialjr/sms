<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'lastname',
        'firstname',
        'middlename',
        'extname',
        'email',
        'qr_code',
        'rfid_tag',
        'sex',
        'birthdate',
        'address',
        'photo'
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}

