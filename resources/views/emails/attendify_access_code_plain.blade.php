LEYTE NORMAL UNIVERSITY - INTEGRATED LABORATORY SCHOOL (LNU-ILS)

Attendify Mobile App - Official Login Credentials & Quick Guide

====================================================================

Good day, {{ $recipientName }}!

@if($role === 'teacher')
Welcome to the LNU-ILS Attendify App. Below is your official Teacher ID Number, your 6-Digit Security Code, and an easy step-by-step guide to download and open the app on your Android phone (currently available for Android phones only in the meantime).
@else
Welcome to the LNU-ILS Attendify App! Below are the login details for {{ $fullName }} so you can easily check daily school arrival (Morning IN) and dismissal (Afternoon OUT) on your Android phone (currently available for Android phones only in the meantime).
@endif


--------------------------------------------------------------------

YOUR ATTENDIFY LOGIN CREDENTIALS

--------------------------------------------------------------------

@if($role === 'teacher')
Teacher Name: {{ $fullName }}
@else
Student Name: {{ $fullName }}
@endif

Class / Assignment: {{ $classInfo }}

@if($role === 'teacher')
Step 1 Login (Teacher ID Number): {{ $idNumber }}
@else
Step 1 Login (Student ID Number): {{ $idNumber }}
@endif

Step 2 Login (6-Digit Access Code): {{ $accessCode }}

@if($mustChangeCode)

FIRST-TIME LOGIN SECURITY NOTICE:

When you log in for the very first time using the code above, the app will ask you to type your own easy-to-remember 6-digit PIN for future logins.
@endif


--------------------------------------------------------------------

HOW TO DOWNLOAD & INSTALL THE ATTENDIFY APP (ANDROID PHONES ONLY)

--------------------------------------------------------------------

PLEASE NOTE: The Attendify app (Attendify.apk) is currently available for ANDROID PHONES ONLY in the meantime (not yet available for iPhone / iOS devices).


Official LNU-ILS App Folder Link:

{{ $apkDownloadUrl }}


Easy Instructions After Opening the Link Above (Android Only):

1. Find the App File: Inside the folder that opens, look for the file named Attendify.apk.

2. Start the Download: Tap the three dots beside Attendify.apk (or press and hold the file) and tap "Download".

3. Tap "Download anyway": Google Drive may show a message saying it cannot scan large files. The LNU-ILS app is 100% safe—just tap "Download anyway".

4. Open & Install: Once downloaded, open Attendify.apk on your Android phone and tap "Install" (if your phone asks permission, turn on "Allow from this source" and tap "Scan app").

(Tip: Open the HTML version of this email to view the step-by-step picture guides.)


--------------------------------------------------------------------

NEED HELP?

Please keep your 6-digit access code confidential. If you need assistance installing the app or resetting your code, feel free to ask your child's Homeroom Adviser or contact the LNU-ILS Office:

Email: lnuilsedu@gmail.com

Trunkline: (053) 888-0929 / (053) 888-0855 loc. 350

====================================================================

(c) {{ date('Y') }} LNU-ILS Attendify System • School ID: 600082