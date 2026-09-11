<?php

return [
    'default' => 'smtp',
    'mailers' => [
        'smtp' => [
            'host' => env('SMTP_HOST', 'localhost'),
            'port' => env('SMTP_PORT', 587),
            'encryption' => env('SMTP_ENCRYPTION', 'tls'),
            'username' => env('SMTP_USERNAME', ''),
            'password' => env('SMTP_PASSWORD', ''),
            'timeout' => 15,
        ],
    ],
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Open LMS'),
    ],
];
