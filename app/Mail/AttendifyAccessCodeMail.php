<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AttendifyAccessCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $role;           // 'student' or 'teacher'
    public string $recipientName;  // Student/Guardian or Teacher name
    public string $fullName;       // Student or Teacher full name
    public string $idNumber;       // student_id or teacher id_no
    public ?string $lrnNo;         // Student LRN (optional)
    public string $accessCode;     // 6-digit PIN
    public string $classInfo;      // Level / Grade & Section
    public bool $mustChangeCode;
    public string $apkDownloadUrl; // Clean link used inside the button
    public array $guidePubmats;    // Role-specific pubmat paths & titles

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
        $this->apkDownloadUrl = (string) env('ATTENDIFY_APK_DOWNLOAD_URL', '#');
        $this->guidePubmats   = $this->resolveGuidePubmats();
    }

    /**
     * Automatically pick the 4 visual guides for Student/Parent vs Teacher
     */
    protected function resolveGuidePubmats(): array
    {
        $guides = [];

        // GUIDE 1: How to Download the APK (Role-Specific)
        if ($this->role === 'teacher') {
            $guides[] = [
                'step'     => 'PICTURE GUIDE 1 • HOW TO DOWNLOAD THE APP',
                'title'    => '3 Easy Steps to Download Attendify.apk',
                'filename' => '1-Attendify-Teacher-APK-Download-Guide.jpg',
                'path'     => public_path('images/pubmats/teacher-apk-download-guide.jpg'),
            ];
        } else {
            $guides[] = [
                'step'     => 'PICTURE GUIDE 1 • HOW TO DOWNLOAD THE APP',
                'title'    => '3 Easy Steps to Download Attendify.apk',
                'filename' => '1-Attendify-Student-Parent-APK-Download-Guide.jpg',
                'path'     => public_path('images/pubmats/student-parent-apk-download-guide.jpg'),
            ];
        }

        // GUIDE 2: How to Install the APK on Android
        $guides[] = [
            'step'     => 'PICTURE GUIDE 2 • HOW TO INSTALL ON YOUR PHONE',
            'title'    => 'Step-by-Step Android Installation Guide',
            'filename' => '2-Attendify-APK-Installation-Guide.jpg',
            'path'     => public_path('images/pubmats/apk-installation-guide.jpg'),
        ];

        // GUIDE 3: How to Log In & Use the Portal (Role-Specific)
        if ($this->role === 'teacher') {
            $guides[] = [
                'step'     => 'PICTURE GUIDE 3 • HOW TO LOG IN & USE THE APP',
                'title'    => 'Teacher Portal Quick Guide',
                'filename' => '3-Attendify-Teacher-Portal-Guide.jpg',
                'path'     => public_path('images/pubmats/teacher-portal-quick-guide.jpg'),
            ];
        } else {
            $guides[] = [
                'step'     => 'PICTURE GUIDE 3 • HOW TO LOG IN & USE THE APP',
                'title'    => 'Student & Parent Portal Quick Guide',
                'filename' => '3-Attendify-Student-Parent-Portal-Guide.jpg',
                'path'     => public_path('images/pubmats/student-parent-quick-guide.jpg'),
            ];
        }

        // GUIDE 4: Profile & Changing Your 6-Digit PIN
        $guides[] = [
            'step'     => 'PICTURE GUIDE 4 • PROFILE & CHANGING YOUR PIN',
            'title'    => 'How to Change Your 6-Digit Code & View Tutorials',
            'filename' => '4-Attendify-Profile-Tab-Guide.jpg',
            'path'     => public_path('images/pubmats/profile-tab-quick-guide.jpg'),
        ];

        return array_values(array_filter($guides, fn ($g) => is_file($g['path'])));
    }

    public function envelope(): Envelope
    {
        $subjectRole = $this->role === 'teacher'
            ? 'Faculty Portal'
            : 'Parent & Student Portal';

        return new Envelope(
            subject: "LNU-ILS Attendify: Your 6-Digit Code & Easy App Download Guide ({$subjectRole})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.attendify_access_code',
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        foreach ($this->guidePubmats as $guide) {
            $attachments[] = Attachment::fromPath($guide['path'])
                ->as($guide['filename'])
                ->withMime('image/jpeg');
        }

        return $attachments;
    }
}