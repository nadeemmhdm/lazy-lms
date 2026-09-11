<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Account Recovery - Open LMS') ?></title>
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
        .recovery-card {
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

<div class="recovery-card">
    <div style="text-align: center; margin-bottom: 1.75rem;">
        <div style="width: 48px; height: 48px; margin: 0 auto 0.75rem; background: rgba(245, 158, 11, 0.2); color: var(--accent); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 1.6rem;">
            <i class="bx bx-key"></i>
        </div>
        <h1 style="font-size: 1.35rem; font-weight: 700; margin-bottom: 0.25rem;">Account Password Recovery</h1>
        <p style="color: #94a3b8; font-size: 0.85rem;">Answer your registered security question to reset your password.</p>
    </div>

    <?php if ($error = flash('error')): ?>
        <div class="toast toast-danger" style="margin-bottom: 1.5rem; position: static;">
            <i class="bx bx-error-circle"></i>
            <div style="flex:1; font-size: 0.88rem;"><?= e($error) ?></div>
        </div>
    <?php endif; ?>

    <form action="<?= url('/forgot-password') ?>" method="POST">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label" style="color: #e2e8f0;">Account Email *</label>
            <input type="email" name="email" class="form-control" required autofocus placeholder="name@example.com" value="<?= e(old('email')) ?>">
        </div>

        <div class="form-group">
            <label class="form-label" style="color: #e2e8f0;">Security Recovery Answer *</label>
            <input type="text" name="recovery_answer" class="form-control" required placeholder="Your answer" autocomplete="off">
            <div class="form-help" style="color: #64748b;">The answer you configured during initial setup or in your profile.</div>
        </div>

        <div style="margin-top: 1.75rem;">
            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                <i class="bx bx-check"></i> Verify Answer & Proceed
            </button>
        </div>
    </form>

    <div style="margin-top: 1.5rem; text-align: center;">
        <a href="<?= url('/login') ?>" style="font-size: 0.85rem; color: #94a3b8;"><i class="bx bx-left-arrow-alt"></i> Back to Login</a>
    </div>
</div>

</body>
</html>
