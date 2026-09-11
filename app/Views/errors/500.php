<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Internal Server Error</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; text-align: center; padding: 1.5rem; background: var(--bg); }
        .error-card { max-width: 520px; padding: 2.5rem; background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); }
        .error-icon { font-size: 4rem; color: var(--danger); margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="error-card">
        <i class="bx bx-server error-icon"></i>
        <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">500 Server Error</h1>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;"><?= e($message ?? 'An internal error occurred while processing your request. The incident has been recorded in the security log.') ?></p>
        <a href="<?= url('/login') ?>" class="btn btn-primary"><i class="bx bx-home-alt"></i> Return to Safety</a>
    </div>
</body>
</html>
