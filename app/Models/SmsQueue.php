<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsQueue extends Model
{
    use HasFactory;

    protected $table = 'sms_queues';

    protected $fillable = [
        'attendance_id',
        'phone_number',
        'message',
        'status',
        'device_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the attendance record associated with this SMS.
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Scope: Pending SMS
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Processing SMS
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope: Sent SMS
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope: Failed SMS
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}