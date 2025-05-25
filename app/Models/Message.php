<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'message',
        'audience_type',
        'grade',
        'section',        
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function targets()
    {
        return $this->hasMany(MessageTarget::class, 'message_id', 'id');
    }

    public function targetErrors()
    {
        return $this->hasMany(MessageTarget::class, 'message_id', 'id')->where('status','Error');
    }
}
