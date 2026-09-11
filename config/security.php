<?php

return [
    'login_max_attempts' => 5,
    'login_lockout_seconds' => 900, // 15 minutes lockout after max attempts
    'login_progressive_delay' => true, // add progressive sleep on failed attempts
    'password_recovery_cooldown' => 300, // 5 minutes cooldown
    'password_recovery_max_attempts' => 3,
    'session_lifetime' => 7200, // 2 hours
    'session_secure' => false, // Set true in production over HTTPS
    'session_http_only' => true,
    'session_same_site' => 'Strict',
    'allowed_upload_types' => [
        'pdf' => 'application/pdf',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'txt' => 'text/plain',
        'zip' => 'application/zip',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'mp4' => 'video/mp4',
        'webm' => 'video/webm',
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
    ],
    'max_upload_size_bytes' => 50 * 1024 * 1024, // 50MB
];
