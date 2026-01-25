<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'school_id',
        'school_head',
        'principal',
    ];
    
}
