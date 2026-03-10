<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>School Admin Credentials</title>
</head>
<body style="margin:0;padding:0;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;color:#111827;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f5f7fb;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" width="640"
                    style="max-width:640px;background:#ffffff;border-radius:12px;overflow:hidden;border:1px solid #e5e7eb;">
                    <tr>
                        <td style="padding:18px 22px;background:#2d336b;color:#ffffff;">
                            <div style="font-size:18px;font-weight:700;line-height:24px;">School Cab Services</div>
                            <div style="font-size:13px;opacity:.9;line-height:18px;margin-top:4px;">School Admin Access</div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:22px;">
                            <div style="font-size:16px;font-weight:700;line-height:22px;margin:0 0 8px 0;">
                                {{ $schoolName }} - Login Credentials
                            </div>
                            <div style="font-size:13px;line-height:20px;color:#374151;margin:0 0 18px 0;">
                                Below are your school admin login details. Keep this email safe and do not share it with anyone.
                            </div>

                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%"
                                style="border:1px solid #e5e7eb;border-radius:10px;">
                                <tr>
                                    <td style="padding:14px 16px;background:#f9fafb;border-bottom:1px solid #e5e7eb;">
                                        <div style="font-size:12px;color:#6b7280;">Login URL</div>
                                        <div style="font-size:13px;font-weight:600;margin-top:4px;">
                                            <a href="{{ $loginUrl }}" style="color:#2563eb;text-decoration:none;">{{ $loginUrl }}</a>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:14px 16px;">
                                        <div style="font-size:12px;color:#6b7280;">Password</div>
                                        <div style="font-size:14px;font-weight:700;margin-top:4px;color:#111827;">{{ $password }}</div>
                                    </td>
                                </tr>
                            </table>

                            <div style="font-size:12px;line-height:18px;color:#6b7280;margin-top:12px;">
                                Login using your registered email address and the password above.
                            </div>

                            <div style="margin-top:18px;">
                                <a href="{{ $loginUrl }}"
                                    style="display:inline-block;background:#2d336b;color:#ffffff;text-decoration:none;padding:10px 14px;border-radius:8px;font-size:13px;font-weight:700;">
                                    Open Admin Login
                                </a>
                            </div>

                            <div style="font-size:12px;line-height:18px;color:#6b7280;margin-top:16px;">
                                Note: This login will work only on your school URL. If you try to login on a different school's URL, access will be denied.
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:14px 22px;background:#f9fafb;border-top:1px solid #e5e7eb;">
                            <div style="font-size:11px;line-height:16px;color:#6b7280;">
                                If you did not request this, please contact support.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
