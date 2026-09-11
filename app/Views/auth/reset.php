<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Create New Password') ?></title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: #f8fafc;
        }
        .reset-card {
            background: rgba(17, 24, 39, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 2.25rem;
        }
        .form-control {
            background: rgba(31, 41, 55, 0.8) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
            color: #f8fafc !important;
        }
    </style>
</head>
<body>

<div class="reset-card">
    <div style="text-align: center; margin-bottom: 1.75rem;">
        <div style="width: 48px; height: 48px; margin: 0 auto 0.75rem; background: rgba(16, 185, 129, 0.2); color: var(--success); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 1.6rem;">
            <i class="bx bx-shield-plus"></i>
        </div>
        <h1 style="font-size: 1.35rem; font-weight: 700; margin-bottom: 0.25rem;">Create New Password</h1>
        <p style="color: #94a3b8; font-size: 0.85rem;">Security answer verified. Set a strong new password for your account.</p>
    </div>

    <?php if ($error = flash('error')): ?>
        <div class="toast toast-danger" style="margin-bottom: 1.5rem; position: static;">
            <i class="bx bx-error-circle"></i>
            <div style="flex:1; font-size: 0.88rem;"><?= e($error) ?></div>
        </div>
    <?php endif; ?>

    <form action="<?= url('/reset-password') ?>" method="POST">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label" style="color: #e2e8f0;">New Password *</label>
            <input type="password" name="password" class="form-control" required minlength="8" placeholder="At least 8 characters">
        </div>

        <div class="form-group">
            <label class="form-label" style="color: #e2e8f0;">Confirm New Password *</label>
            <input type="password" name="password_confirmation" class="form-control" required minlength="8" placeholder="Repeat new password">
        </div>

        <div style="margin-top: 1.75rem;">
            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                <i class="bx bx-save"></i> Save New Password
            </button>
        </div>
    </form>
</div>

</body>
</html>
