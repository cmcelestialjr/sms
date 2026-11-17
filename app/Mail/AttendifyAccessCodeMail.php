<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AttendifyAccessCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $role;          // 'student' or 'teacher'
    public string $recipientName; // Student/Guardian or Teacher name
    public string $fullName;      // Student or Teacher full name
    public string $idNumber;      // student_id (e.g. 20260001) or teacher id_no
    public ?string $lrnNo;        // Student LRN (optional)
    public string $accessCode;    // 6-digit PIN
    public string $classInfo;     // Level / Grade & Section
    public bool $mustChangeCode;

    public function __construct(array $data)
    {
        $this->role           = $data['role'];
        $this->recipientName  = $data['recipient_name'];
        $this->fullName       = $data['full_name'];
        $this->idNumber       = $data['id_number'];
        $this->lrnNo          = $data['lrn_no'] ?? null;
        $this->accessCode     = $data['access_code'];
        $this->classInfo      = $data['class_info'] ?? 'LNU-ILS';
        $this->mustChangeCode = (bool) ($data['must_change_code'] ?? true);
    }

    public function envelope(): Envelope
    {
        $subjectRole = $this->role === 'teacher'
            ? 'Faculty Portal'
            : 'Parent & Student Portal';

        return new Envelope(
            subject: "LNU-ILS Attendify: Your 6-Digit Access Code ({$subjectRole})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.attendify_access_code',
        );
    }
}