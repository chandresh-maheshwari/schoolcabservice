<!DOCTYPE html>
<html>
<head>
    <title>Your OTP Code</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @media only screen and (max-width: 600px) {
            .email-container {
                padding: 0 !important;
            }
            .card {
                padding: 16px 4% !important;
                border-radius: 10px !important;
            }
            .otp-code {
                font-size: 24px !important;
                padding: 12px 10px !important;
                letter-spacing: 6px !important;
            }
            .header-title {
                font-size: 20px !important;
            }
            .logo-img {
                width: 48px !important;
                height: 48px !important;
            }
        }
    </style>
</head>
<body style="background: #f4f6fb; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" class="email-container" style="background: #f4f6fb; padding: 40px 0;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" class="card" style="max-width: 420px; background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(44,62,80,0.10); overflow: hidden;">
                    <tr>
                        <td align="center" style="background: linear-gradient(90deg, #2d336b 0%, #7886c7 100%); padding: 32px 0 16px 0;">
                            <!-- Logo Placeholder -->
                            <!-- <img src="{{ asset('assets/images/Tahukar Magazine logo.png') }}" alt="Logo" width="64" height="64" class="logo-img" style="border-radius: 50%; box-shadow: 0 2px 8px rgba(44,62,80,0.10); margin-bottom: 12px;"> -->
                            <h1 class="header-title" style="margin: 0; color: #fff; font-size: 28px; font-weight: 800; letter-spacing: 2px;">Verification Code</h1>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 32px 32px 0 32px;">
                            <p style="margin: 0; color: #333; font-size: 18px; font-weight: 500;">Hello,</p>
                            <p style="margin: 8px 0 0 0; color: #555; font-size: 16px;">To continue, please use the following One-Time Password (OTP) to verify your identity. This code is valid for a short period only.</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 32px 32px 0 32px;">
                            <span class="otp-code" style="display: inline-block; background: linear-gradient(90deg, #2d336b 0%, #7886c7 100%); color: #fff; font-size: 36px; font-weight: bold; letter-spacing: 12px; padding: 18px 40px; border-radius: 8px; box-shadow: 0 2px 8px rgba(44,62,80,0.10); border: none; margin-bottom: 8px;">{{ $otp }}</span>
                        </td>
                    </tr>
                    <tr></tr>
                        <td align="center" style="padding: 24px 32px 0 32px;">
                            <p style="margin: 0; color: #888; font-size: 15px;">If you did not request this code, you can safely ignore this email.<br>For your security, do not share this code with anyone.</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding: 32px 32px 32px 32px;">
                            <p style="margin: 0; color: #2f3235; font-size: 13px;">
                                &copy; {{ date('Y') }}
                                <a href="{{ config('app.site_url') }}" style="color: #7886c7; text-decoration: none; font-weight: bold;">
                                    {{ config('app.site_name') }}
                                </a>. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html> 