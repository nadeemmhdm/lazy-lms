<!-- Lazy LMS - Maintenance Mode -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode - Lazy LMS</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        body { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh; 
            text-align: center; 
            padding: 1.5rem; 
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .error-card { 
            max-width: 520px; 
            width: 100%;
            padding: 2.5rem; 
            background: rgba(17, 24, 39, 0.9); 
            border: 1px solid rgba(255, 255, 255, 0.1); 
            backdrop-filter: blur(16px);
            border-radius: var(--radius-lg, 12px); 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5); 
        }
        .error-icon { 
            font-size: 4.5rem; 
            color: #f59e0b; 
            margin-bottom: 1.25rem; 
            display: inline-block;
            animation: pulse 2s infinite ease-in-out;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.9; }
            50% { transform: scale(1.05); opacity: 1; }
        }
        .footer-note {
            margin-top: 2rem;
            font-size: 0.85rem;
            color: #94a3b8;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 1.25rem;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <i class="bx bx-wrench error-icon"></i>
        <h1 style="font-size: 1.85rem; font-weight: 700; margin-bottom: 0.75rem;">Maintenance Mode</h1>
        <p style="color: #cbd5e1; font-size: 1.05rem; line-height: 1.6; margin-bottom: 1.5rem;">
            <?= e($message ?? 'The LMS is temporarily undergoing scheduled maintenance. Please check back shortly.') ?>
        </p>
        <p style="font-size: 0.9rem; color: #94a3b8; margin-bottom: 1.5rem;">
            Administrators may still log in directly at <a href="<?= url('/admin') ?>" style="color: #38bdf8; text-decoration: underline;">/admin</a>.
        </p>
        <a href="<?= url('/login') ?>" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: #6366f1; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600;">
            <i class="bx bx-refresh"></i> Refresh Page
        </a>
        <div class="footer-note">
            Powered by Lazy LMS
        </div>
    </div>
</body>
</html>
