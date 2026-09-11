<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; text-align: center; padding: 1.5rem; background: var(--bg); }
        .error-card { max-width: 480px; padding: 2.5rem; background: var(--bg-surface); border: 1px solid var(--border); border-radius: var(--radius-lg); box-shadow: var(--shadow-lg); }
        .error-icon { font-size: 4rem; color: var(--warning); margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="error-card">
        <i class="bx bx-error-circle error-icon"></i>
        <h1 style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;">404 Not Found</h1>
        <p style="color: var(--text-muted); margin-bottom: 1.5rem;"><?= e($message ?? 'The page or resource you are looking for does not exist.') ?></p>
        <a href="<?= url('/login') ?>" class="btn btn-primary"><i class="bx bx-home-alt"></i> Return to Portal</a>
    </div>
</body>
</html>
