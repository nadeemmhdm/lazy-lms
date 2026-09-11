<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 - Page Expired</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; text-align: center; padding: 1.5rem; background: var(--bg); }
        .error-card { max-width: 480px; padding: 2.5rem; background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); }
        .error-icon { font-size: 4rem; color: var(--info); margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="error-card">
        <i class="bx bx-time-five error-icon"></i>
        <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">419 Page Expired</h1>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;"><?= e($message ?? 'Your security token has expired. Please refresh the page and submit again.') ?></p>
        <button onclick="window.history.back()" class="btn btn-outline" style="margin-right:0.5rem;"><i class="bx bx-left-arrow-alt"></i> Go Back</button>
        <a href="<?= url('/login') ?>" class="btn btn-primary"><i class="bx bx-refresh"></i> Refresh Login</a>
    </div>
</body>
</html>
