<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AttendanceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $details;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($details)
    {
        $this->details = $details;
    }

    public function build()
    {        
        return $this->subject('[LNU-ILS] Attendify: Attendance Notification')
                    ->view('emails.attendance')
                    ->with([
                        'attendanceMessage' => $this->details['message'],
                    ]);
    }
}