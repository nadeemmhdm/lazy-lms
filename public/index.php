<?php

/**
 * Lazy LMS - Modern, Secure, Self-Hosted Learning Management System
 * Web Entry Point / Front Controller
 */

declare(strict_types=1);

require_once __DIR__ . '/../app/App.php';

\App\App::run();
