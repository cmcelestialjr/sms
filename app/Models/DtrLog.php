<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DtrLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'time',
        'type',
        'updated_at',
        'created_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
