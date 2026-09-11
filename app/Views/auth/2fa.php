<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Two-Factor Authentication') ?></title>
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
        .twofa-card {
            background: rgba(17, 24, 39, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(16px);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            padding: 2.25rem;
            text-align: center;
        }
        .form-control {
            background: rgba(31, 41, 55, 0.8) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
            color: #f8fafc !important;
            text-align: center;
            letter-spacing: 0.25em;
            font-size: 1.5rem !important;
            font-weight: 700;
        }
    </style>
</head>
<body>

<div class="twofa-card">
    <div style="width: 52px; height: 52px; margin: 0 auto 1rem; background: rgba(79, 70, 229, 0.2); color: var(--primary); border-radius: var(--radius-md); display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
        <i class="bx bx-shield-quarter"></i>
    </div>
    <h1 style="font-size: 1.4rem; font-weight: 700; margin-bottom: 0.5rem;">Two-Factor Verification</h1>
    <p style="color: #94a3b8; font-size: 0.88rem; margin-bottom: 1.75rem;">
        Open your Authenticator app (Google Authenticator, Microsoft Authenticator, or Authy) and enter the 6-digit code or an emergency recovery code.
    </p>

    <?php if ($error = flash('error')): ?>
        <div class="toast toast-danger" style="margin-bottom: 1.5rem; position: static;">
            <i class="bx bx-error-circle"></i>
            <div style="flex:1; font-size: 0.88rem; text-align: left;"><?= e($error) ?></div>
        </div>
    <?php endif; ?>

    <form action="<?= url('/login/2fa') ?>" method="POST">
        <?= csrf_field() ?>

        <div class="form-group">
            <input type="text" name="code" class="form-control" placeholder="000000" maxlength="16" autofocus required autocomplete="one-time-code">
            <div class="form-help" style="color: #64748b; margin-top: 0.5rem;">Enter 6-digit TOTP code or XXXX-XXXX recovery code.</div>
        </div>

        <div style="margin-top: 1.75rem;">
            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                <i class="bx bx-check-shield"></i> Verify & Continue
            </button>
        </div>
    </form>

    <div style="margin-top: 1.5rem;">
        <a href="<?= url('/logout') ?>" style="font-size: 0.85rem; color: #94a3b8;"><i class="bx bx-arrow-back"></i> Cancel and return to login</a>
    </div>
</div>

</body>
</html>
