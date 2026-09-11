<!-- Lazy LMS - Modern, Secure, Self-Hosted Learning Management System -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Sign In - Lazy LMS') ?></title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: var(--text);
        }
        .login-card {
            background: rgba(17, 24, 39, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 2.25rem;
            color: #f8fafc;
        }
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-logo {
            width: 52px;
            height: 52px;
            margin: 0 auto 1rem;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #fff;
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
        }
        .form-control {
            background: rgba(31, 41, 55, 0.8) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
            color: #f8fafc !important;
        }
        .password-toggle-wrapper {
            position: relative;
        }
        .password-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <div class="login-logo">
            <i class="bx bxs-graduation"></i>
        </div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.35rem;"><?= e($settings['login_title'] ?? 'Welcome Back') ?></h1>
        <p style="color: #94a3b8; font-size: 0.88rem;"><?= e($settings['login_description'] ?? 'Sign in to access your courses and dashboard.') ?></p>
    </div>

    <?php if ($error = flash('error')): ?>
        <div class="toast toast-danger" style="margin-bottom: 1.5rem; position: static;">
            <i class="bx bx-error-circle"></i>
            <div style="flex:1; font-size: 0.88rem;"><?= e($error) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($success = flash('success')): ?>
        <div class="toast toast-success" style="margin-bottom: 1.5rem; position: static;">
            <i class="bx bx-check-circle"></i>
            <div style="flex:1; font-size: 0.88rem;"><?= e($success) ?></div>
        </div>
    <?php endif; ?>

    <form action="<?= url('/login') ?>" method="POST">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label" style="color: #e2e8f0;">Email / Username</label>
            <div style="position: relative;">
                <i class="bx bx-envelope" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.2rem;"></i>
                <input type="email" name="email" class="form-control" style="padding-left: 2.5rem;" required autofocus placeholder="name@example.com" value="<?= e(old('email')) ?>">
            </div>
        </div>

        <div class="form-group">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                <label class="form-label" style="color: #e2e8f0; margin-bottom: 0;">Password</label>
                <a href="<?= url('/forgot-password') ?>" style="font-size: 0.8rem; color: var(--secondary);">Forgot password?</a>
            </div>
            <div class="password-toggle-wrapper">
                <i class="bx bx-lock-alt" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 1.2rem;"></i>
                <input type="password" name="password" id="password-input" class="form-control" style="padding-left: 2.5rem; padding-right: 2.5rem;" required placeholder="••••••••">
                <button type="button" class="password-toggle-btn" id="password-toggle">
                    <i class="bx bx-hide"></i>
                </button>
            </div>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 1rem;">
            <label style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: #cbd5e1; cursor: pointer;">
                <input type="checkbox" name="remember" value="1">
                <span>Remember me (12 hours)</span>
            </label>
        </div>

        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                <i class="bx bx-log-in"></i> Sign In to Account
            </button>
        </div>
    </form>

    <div style="margin-top: 2rem; text-align: center; font-size: 0.85rem; color: #94a3b8; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 1.25rem;">
        <?= e($settings['footer_text'] ?? 'Powered by Lazy LMS') ?>
    </div>
</div>

<script>
document.getElementById('password-toggle')?.addEventListener('click', function() {
    const input = document.getElementById('password-input');
    const icon = this.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bx bx-show';
    } else {
        input.type = 'password';
        icon.className = 'bx bx-hide';
    }
});
</script>

</body>
</html>
