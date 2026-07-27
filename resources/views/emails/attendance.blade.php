<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Attendify: Student Attendance Notification</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f7f6; font-family: Arial, Helvetica, sans-serif; -webkit-font-smoothing: antialiased;">

    <!-- Wrapper -->
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f7f6; padding: 40px 20px;">
        <tr>
            <td align="center">
                
                <!-- Main Email Container -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                    
                    <!-- Header Section -->
                    <tr>
                        <td align="center" style="padding: 30px 20px; border-bottom: 3px solid #003366;">
                            <img src="https://drive.google.com/uc?export=view&id=1hkre2DzvdkEQ__RYNfcTXCznTP1NEvtK" alt="Leyte Normal University" style="height: 70px; width: auto; display: block;"/>
                        </td>
                    </tr>

                    <!-- Body Section -->
                    <tr>
                        <td style="padding: 40px 40px 30px 40px; color: #333333; line-height: 1.6; font-size: 15px;">
                            <p style="margin-top: 0;">Dear Parent / Guardian,</p>
                            <p>Good day.</p>
                            <p>This is an automated attendance update from **Attendify** regarding your student's campus activity.</p>

                            <!-- Attendance Activity Box -->
                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 25px 0; background-color: #f9fbfd; border-left: 4px solid #003366; border-radius: 4px;">
                                <tr>
                                    <td style="padding: 20px; color: #003366; font-family: Arial, sans-serif; font-size: 16px; font-weight: bold; line-height: 1.5;">
                                        {{ $attendanceMessage }}
                                    </td>
                                </tr>
                            </table>

                            <p style="font-size: 13px; color: #666666; margin-top: 20px;">
                                *If you notice any discrepancies with this log, please coordinate immediately with the school administration.*
                            </p>
                        </td>
                    </tr>

                    <!-- Footer Section -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 25px 40px; text-align: center; border-top: 1px solid #eeeeee;">
                            <p style="margin: 0 0 10px 0; font-size: 12px; color: #888888;">
                                This is a system-generated tracking alert. Please do not reply directly to this email.
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #555555; line-height: 1.5;">
                                <strong>Integrated Laboratory School (ILS)</strong><br>
                                Leyte Normal University<br>
                                <span style="color: #888888; font-size: 11px;">Powered by Attendify: Smart Student Management with QR Tracking & Instant Alerts</span>
                            </p>
                        </td>
                    </tr>

                </table>
                <!-- End Main Email Container -->

            </td>
        </tr>
    </table>
    <!-- End Wrapper -->

</body>
</html>