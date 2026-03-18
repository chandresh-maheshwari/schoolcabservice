<?php

return [
    'mobile_otp_mail' => [
        'secret' => env('LARAVEL_OTP_MAIL_SECRET', ''),
    ],
    'frontend_api' => [
        'key' => env('FRONTEND_API_KEY', ''),
    ],
];
