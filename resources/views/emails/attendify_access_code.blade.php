<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LNU-ILS Attendify Access Code</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: Arial, Helvetica, sans-serif; color: #1e293b;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f1f5f9; padding: 28px 14px;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width: 560px; background-color: #ffffff; border-radius: 18px; overflow: hidden; box-shadow: 0 8px 24px rgba(5, 13, 46, 0.08);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #050D2E 0%, #0D1F63 100%); padding: 28px 24px; text-align: center; border-bottom: 4px solid #F5B400;">
                            <div style="font-size: 11px; font-weight: bold; color: #F5B400; letter-spacing: 1.5px; text-transform: uppercase;">
                                Leyte Normal University • Integrated Laboratory School
                            </div>
                            <h1 style="margin: 8px 0 4px; color: #ffffff; font-size: 22px;">
                                Attendify {{ $role === 'teacher' ? 'Teacher Portal' : 'Parent & Guardian Portal' }}
                            </h1>
                            <p style="margin: 0; color: #cbd5e1; font-size: 13px;">
                                Official Mobile Portal Login Credentials
                            </p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 28px 26px;">
                            <p style="margin: 0 0 14px; font-size: 15px;">
                                Hello <strong>{{ $recipientName }}</strong>,
                            </p>

                            @if($role === 'teacher')
                                <p style="margin: 0 0 20px; font-size: 14px; line-height: 1.6; color: #475569;">
                                    Here are your login credentials for the <strong>Attendify Teacher Portal</strong> to monitor your advisory class attendance and student QR passes.
                                </p>
                            @else
                                <p style="margin: 0 0 20px; font-size: 14px; line-height: 1.6; color: #475569;">
                                    Here are the login credentials to view real-time campus attendance logs and digital QR passes for <strong>{{ $fullName }}</strong> in the <strong>Attendify App</strong>.
                                </p>
                            @endif

                            <!-- Credentials Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; margin-bottom: 20px;">
                                <tr>
                                    <td style="padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;">
                                        <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: bold;">
                                            {{ $role === 'teacher' ? 'Faculty Name' : 'Student Name' }}
                                        </div>
                                        <div style="font-size: 15px; font-weight: bold; color: #050D2E; margin-top: 2px;">
                                            {{ $fullName }}
                                        </div>
                                        <div style="font-size: 12px; color: #475569; margin-top: 2px;">
                                            {{ $classInfo }}
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 0; border-bottom: 1px solid #e2e8f0;">
                                        <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: bold;">
                                            {{ $role === 'teacher' ? 'Teacher ID Number (id_no)' : 'Student ID Number' }}
                                        </div>
                                        <div style="font-size: 17px; font-weight: bold; color: #050D2E; margin-top: 3px; font-family: monospace;">
                                            {{ $idNumber }}
                                        </div>
                                        @if(!empty($lrnNo))
                                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                                LRN: {{ $lrnNo }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding-top: 14px; text-align: center;">
                                        <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: bold; letter-spacing: 0.8px;">
                                            6-Digit Security Access Code
                                        </div>
                                        <div style="display: inline-block; margin-top: 8px; padding: 10px 24px; background-color: #050D2E; color: #F5B400; font-size: 26px; font-weight: bold; letter-spacing: 6px; border-radius: 10px; font-family: monospace;">
                                            {{ $accessCode }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            @if($mustChangeCode)
                                <div style="background-color: #fffbeb; border-left: 4px solid #F5B400; padding: 12px 14px; border-radius: 6px; font-size: 13px; color: #92400e; line-height: 1.5; margin-bottom: 18px;">
                                    <strong>First-Time Login Security Notice:</strong> For your security, the app will prompt you to replace this initial 6-digit code with your own personal 6-digit PIN when you sign in for the first time.
                                </div>
                            @endif

                            <p style="margin: 0; font-size: 12.5px; color: #64748b; line-height: 1.5;">
                                Please keep your 6-digit access code confidential. If you did not expect this email or need assistance resetting your code, please contact the LNU-ILS office.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 16px 24px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 11.5px; color: #94a3b8;">
                            © {{ date('Y') }} LNU-ILS Attendify System • Automated Notification
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>