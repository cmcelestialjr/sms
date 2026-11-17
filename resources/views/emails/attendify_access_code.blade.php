<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LNU-ILS Attendify Access Code & Easy App Guide</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: Arial, Helvetica, sans-serif; color: #1e293b;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f1f5f9; padding: 24px 10px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 640px; background-color: #ffffff; border-radius: 18px; overflow: hidden; box-shadow: 0 8px 24px rgba(5, 13, 46, 0.08);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #050D2E 0%, #0D1F63 100%); padding: 28px 24px; text-align: center; border-bottom: 5px solid #F5B400;">
                            <div style="font-size: 12px; font-weight: bold; color: #F5B400; letter-spacing: 1.4px; text-transform: uppercase;">
                                Leyte Normal University • Integrated Laboratory School
                            </div>
                            <h1 style="margin: 8px 0 6px; color: #ffffff; font-size: 24px;">
                                Attendify Mobile App
                            </h1>
                            <p style="margin: 0; color: #e2e8f0; font-size: 14px;">
                                {{ $role === 'teacher' ? 'Faculty Login Code & Easy Installation Guide' : 'Parent, Guardian & Student Login Code & Easy App Guide' }}
                            </p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 28px 24px;">
                            <p style="margin: 0 0 14px; font-size: 16px;">
                                Good day, <strong>{{ $recipientName }}</strong>!
                            </p>

                            @if($role === 'teacher')
                                <p style="margin: 0 0 22px; font-size: 15px; line-height: 1.6; color: #334155;">
                                    Welcome to the <strong>LNU-ILS Attendify App</strong>. Below is your official <strong>Teacher ID Number</strong>, your <strong>6-Digit Security Code</strong>, and an easy step-by-step guide to download and open the app on your Android phone.
                                </p>
                            @else
                                <p style="margin: 0 0 22px; font-size: 15px; line-height: 1.6; color: #334155;">
                                    Welcome to the <strong>LNU-ILS Attendify App</strong>! Below are the login details for <strong>{{ $fullName }}</strong> so you can easily check daily school arrival (Morning IN) and dismissal (Afternoon OUT) on your phone.
                                </p>
                            @endif

                            <!-- PART 1: CREDENTIALS BOX -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border: 2px solid #cbd5e1; border-radius: 16px; padding: 20px; margin-bottom: 22px;">
                                <tr>
                                    <td style="padding-bottom: 12px; border-bottom: 1px solid #e2e8f0;">
                                        <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: bold;">
                                            {{ $role === 'teacher' ? 'Teacher Name' : 'Student Name' }}
                                        </div>
                                        <div style="font-size: 17px; font-weight: bold; color: #050D2E; margin-top: 3px;">
                                            {{ $fullName }}
                                        </div>
                                        <div style="font-size: 13px; color: #475569; margin-top: 2px;">
                                            {{ $classInfo }}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 14px 0; border-bottom: 1px solid #e2e8f0;">
                                        <div style="font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: bold;">
                                            {{ $role === 'teacher' ? 'Step 1 Login: Your Teacher ID Number' : 'Step 1 Login: Student ID Number' }}
                                        </div>
                                        <div style="font-size: 20px; font-weight: bold; color: #050D2E; margin-top: 4px; font-family: monospace;">
                                            {{ $idNumber }}
                                        </div>
                                        @if(!empty($lrnNo))
                                            <div style="font-size: 13px; color: #475569; margin-top: 3px;">
                                                Or LRN: <strong>{{ $lrnNo }}</strong>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top: 16px; text-align: center;">
                                        <div style="font-size: 12px; color: #050D2E; text-transform: uppercase; font-weight: bold; letter-spacing: 0.8px;">
                                            Step 2 Login: Your 6-Digit Access Code
                                        </div>
                                        <div style="display: inline-block; margin-top: 10px; padding: 12px 28px; background-color: #050D2E; color: #F5B400; font-size: 28px; font-weight: bold; letter-spacing: 8px; border-radius: 12px; font-family: monospace;">
                                            {{ $accessCode }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            @if($mustChangeCode)
                                <div style="background-color: #fffbeb; border-left: 5px solid #F5B400; padding: 14px 16px; border-radius: 8px; font-size: 14px; color: #92400e; line-height: 1.5; margin-bottom: 26px;">
                                    <strong>First-Time Login Tip:</strong> When you log in for the very first time using the code above, the app will ask you to type your own easy-to-remember <strong>6-digit PIN</strong> for future logins.
                                </div>
                            @endif

                            <!-- PART 2: EASY DOWNLOAD BUTTON & SIMPLE STEPS -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #050D2E; border-radius: 16px; padding: 24px 20px; margin-bottom: 26px; text-align: center; border: 2px solid #F5B400;">
                                <tr>
                                    <td>
                                        <div style="font-size: 12px; font-weight: bold; color: #F5B400; letter-spacing: 1px; text-transform: uppercase;">
                                            Start Here • Official Android App
                                        </div>
                                        <h2 style="margin: 6px 0 10px; color: #ffffff; font-size: 20px;">
                                            How to Download the Attendify App
                                        </h2>
                                        <p style="margin: 0 0 18px; color: #e2e8f0; font-size: 14.5px; line-height: 1.5;">
                                            Using your Android phone, simply tap the gold button below to open the official LNU-ILS App Folder:
                                        </p>

                                        <!-- Big Friendly Download Button (Hides Raw Link) -->
                                        <a href="{{ $apkDownloadUrl }}" target="_blank" style="display: inline-block; background-color: #F5B400; color: #050D2E; font-size: 16px; font-weight: bold; text-decoration: none; padding: 15px 28px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.25);">
                                            📥 TAP HERE TO DOWNLOAD ATTENDIFY APP
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Easy-to-Read 3 Steps Box for Seniors, Parents & Students -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 14px; padding: 18px 20px; margin-bottom: 28px;">
                                <tr>
                                    <td>
                                        <div style="font-size: 15px; font-weight: bold; color: #050D2E; margin-bottom: 12px;">
                                            Easy Instructions After You Tap the Gold Button:
                                        </div>

                                        <p style="margin: 0 0 12px; font-size: 14.5px; line-height: 1.55; color: #1e293b;">
                                            <strong>1. Find the App File:</strong> Inside the folder that opens, look for the file named <strong>Attendify.apk</strong>.
                                        </p>
                                        <p style="margin: 0 0 12px; font-size: 14.5px; line-height: 1.55; color: #1e293b;">
                                            <strong>2. Start the Download:</strong> Tap the <strong>three dots (⋮)</strong> beside <strong>Attendify.apk</strong> (or press and hold the file with your finger) and tap <strong>"Download"</strong>.
                                        </p>
                                        <p style="margin: 0 0 12px; font-size: 14.5px; line-height: 1.55; color: #1e293b;">
                                            <strong>3. Tap "Download anyway":</strong> Google Drive may show a message saying it cannot scan large files. The LNU-ILS app is 100% safe—just tap the blue <strong>"Download anyway"</strong> button.
                                        </p>
                                        <p style="margin: 0; font-size: 14.5px; line-height: 1.55; color: #1e293b;">
                                            <strong>4. Open & Install:</strong> Once downloaded, open <strong>Attendify.apk</strong> and tap <strong>"Install"</strong> (if your phone asks permission, turn on <em>"Allow from this source"</em> and tap <em>"Scan app"</em>).
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- PART 3: VISUAL PICTURE GUIDES -->
                            @if(!empty($guidePubmats))
                                <div style="border-top: 2px solid #e2e8f0; padding-top: 22px;">
                                    <div style="font-size: 12px; font-weight: bold; color: #050D2E; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 4px;">
                                        Picture Guides (Step-by-Step)
                                    </div>
                                    <h2 style="margin: 0 0 6px; font-size: 18px; color: #050D2E;">
                                        Follow the Pictures Below on Your Phone
                                    </h2>
                                    <p style="margin: 0 0 18px; font-size: 14px; color: #475569; line-height: 1.5;">
                                        <em>Tip: If the pictures look small on your screen, you can also open the full-size pictures attached at the very bottom of this email to zoom in clearly.</em>
                                    </p>

                                    @foreach($guidePubmats as $guide)
                                        <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; overflow: hidden; margin-bottom: 18px;">
                                            <tr>
                                                <td style="background-color: #050D2E; padding: 10px 14px;">
                                                    <div style="font-size: 11px; color: #F5B400; font-weight: bold; letter-spacing: 0.8px;">
                                                        {{ $guide['step'] }}
                                                    </div>
                                                    <div style="font-size: 14px; color: #ffffff; font-weight: bold; margin-top: 2px;">
                                                        {{ $guide['title'] }}
                                                    </div>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding: 8px; text-align: center; background-color: #0f172a;">
                                                    <img src="{{ $message->embed($guide['path']) }}"
                                                         alt="{{ $guide['title'] }}"
                                                         style="width: 100%; max-width: 600px; height: auto; display: block; border-radius: 8px; margin: 0 auto;" />
                                                </td>
                                            </tr>
                                        </table>
                                    @endforeach
                                </div>
                            @endif

                            <!-- Need Help Box -->
                            <div style="background-color: #f1f5f9; border-radius: 12px; padding: 14px 16px; margin-top: 12px; font-size: 13.5px; color: #334155; line-height: 1.5;">
                                <strong>Need Help Installing?</strong> Feel free to ask your child's Homeroom Adviser or visit the <strong>LNU-ILS Office</strong> (Email: <strong>lnuilsedu@gmail.com</strong> • Trunkline loc. 350) and we will gladly assist you!
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 16px 24px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b;">
                            © {{ date('Y') }} LNU-ILS Attendify System • School ID: 600082
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>