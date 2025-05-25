<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolTerm extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'shorten_name',
        'numeric',
        'shorten_numeric',
    ];

    public function schoolYears()
    {
        return $this->hasMany(SchoolYear::class, 'school_term_id', 'id');
    }
}
