<?php

return [
    'name' => env('APP_NAME', 'Open LMS'),
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'timezone' => 'UTC',
    'date_format' => 'Y-m-d',
    'time_format' => 'H:i',
    'locale' => 'en',
    'fallback_locale' => 'en',
    'available_locales' => [
        'en' => 'English',
        'ml' => 'മലയാളം (Malayalam)',
        'hi' => 'हिन्दी (Hindi)',
        'ar' => 'العربية (Arabic - RTL)',
    ],
    'version' => '1.0.0',
];
