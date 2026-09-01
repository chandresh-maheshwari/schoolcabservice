<?php

return [
    'mobile_otp_mail' => [
        'secret' => env('LARAVEL_OTP_MAIL_SECRET', ''),
    ],
    'frontend_api' => [
    'key' => env('FRONTEND_API_KEY'),
],
    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY', ''),
    ],
    'node_backend' => [
        'base_url' => env('NODE_BACKEND_URL', 'http://127.0.0.1:3000'),
    ],
];
