<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
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
        $this->apkDownloadUrl = (string) env(
            'ATTENDIFY_APK_DOWNLOAD_URL',
            'https://drive.google.com/drive/folders/114SxbmO2O4KRMf529S7ms4FbW52AFgaa'
        );
        $this->guidePubmats   = $this->resolveGuidePubmats();
    }

    /**
     * Automatically pick the 4 visual guides for Student/Parent vs Teacher
     */
    public function resolveGuidePubmats(): array
    {
        $guides = [];

        // GUIDE 1: How to Download the APK (Role-Specific)
        if ($this->role === 'teacher') {
            $guides[] = [
                'step'     => 'PICTURE GUIDE 1 • HOW TO DOWNLOAD THE APP',
                'title'    => '3 Easy Steps to Download Attendify.apk',
                'filename' => '1-Attendify-Teacher-APK-Download-Guide.jpg',
                'path'     => $this->findPubmatFile([
                    'teacher-apk-download-guide',
                    'Gemini_Generated_Image_yh98g8yh98g8yh98',
                ]),
            ];
        } else {
            $guides[] = [
                'step'     => 'PICTURE GUIDE 1 • HOW TO DOWNLOAD THE APP',
                'title'    => '3 Easy Steps to Download Attendify.apk',
                'filename' => '1-Attendify-Student-Parent-APK-Download-Guide.jpg',
                'path'     => $this->findPubmatFile([
                    'student-parent-apk-download-guide',
                    'Gemini_Generated_Image_q9qey3q9qey3q9qe',
                ]),
            ];
        }

        // GUIDE 2: How to Install the APK on Android
        $guides[] = [
            'step'     => 'PICTURE GUIDE 2 • HOW TO INSTALL ON YOUR PHONE',
            'title'    => 'Step-by-Step Android Installation Guide',
            'filename' => '2-Attendify-APK-Installation-Guide.jpg',
            'path'     => $this->findPubmatFile([
                'apk-installation-guide',
                'Gemini_Generated_Image_jde2mxjde2mxjde2',
            ]),
        ];

        // GUIDE 3: How to Log In & Use the Portal (Role-Specific)
        if ($this->role === 'teacher') {
            $guides[] = [
                'step'     => 'PICTURE GUIDE 3 • HOW TO LOG IN & USE THE APP',
                'title'    => 'Teacher Portal Quick Guide',
                'filename' => '3-Attendify-Teacher-Portal-Guide.jpg',
                'path'     => $this->findPubmatFile([
                    'teacher-portal-quick-guide',
                    'Gemini_Generated_Image_3ve1iw3ve1iw3ve1',
                ]),
            ];
        } else {
            $guides[] = [
                'step'     => 'PICTURE GUIDE 3 • HOW TO LOG IN & USE THE APP',
                'title'    => 'Student & Parent Portal Quick Guide',
                'filename' => '3-Attendify-Student-Parent-Portal-Guide.jpg',
                'path'     => $this->findPubmatFile([
                    'student-parent-quick-guide',
                    'Gemini_Generated_Image_bbm01rbbm01rbbm0',
                ]),
            ];
        }

        // GUIDE 4: Profile & Changing Your 6-Digit PIN
        $guides[] = [
            'step'     => 'PICTURE GUIDE 4 • PROFILE & CHANGING YOUR PIN',
            'title'    => 'How to Change Your 6-Digit Code & View Tutorials',
            'filename' => '4-Attendify-Profile-Tab-Guide.jpg',
            'path'     => $this->findPubmatFile([
                'profile-tab-quick-guide',
                'Gemini_Generated_Image_o4xgo3o4xgo3o4xg',
            ]),
        ];

        return array_values(array_filter($guides, fn ($g) => !empty($g['path']) && is_file($g['path'])));
    }

    /**
     * Searches public/images/pubmats (and fallback folders) across .jpg, .jpeg, .png, etc.
     */
    protected function findPubmatFile(array $baseNames): ?string
    {
        $directories = [
            public_path('images/pubmats'),
            public_path('pubmats'),
            public_path('images'),
        ];

        $extensions = [
            '.jpg',
            '.jpeg',
            '.png',
            '.webp',
            '.jpg.jpeg',
            '.jpg.jpg',
            '.jpeg.jpg',
            '.jpg.png',
            '',
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            foreach ($baseNames as $base) {
                foreach ($extensions as $ext) {
                    $candidate = $dir . DIRECTORY_SEPARATOR . $base . $ext;
                    if (is_file($candidate)) {
                        return $candidate;
                    }
                }
            }
        }

        return null;
    }

    public function envelope(): Envelope
    {
        $subjectRole = $this->role === 'teacher'
            ? 'Faculty Portal'
            : 'Parent & Student Portal';

        return new Envelope(
            replyTo: [
                new Address('lnuilsedu@gmail.com', 'LNU-ILS Office'),
            ],
            subject: "LNU-ILS Attendify: Access Code & App Guide ({$subjectRole})",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.attendify_access_code',
            text: 'emails.attendify_access_code_plain',
        );
    }

    /**
     * Keep attachments empty since the pubmats are already embedded inline via $message->embed().
     * Avoiding duplicate attachments cuts email MIME size in half and lowers Gmail spam risk.
     */
    public function attachments(): array
    {
        return [];
    }
}