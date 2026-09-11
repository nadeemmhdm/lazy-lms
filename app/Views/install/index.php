<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Open LMS Setup Wizard') ?></title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 2rem 1rem;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: #f8fafc;
        }
        .install-card {
            background: rgba(17, 24, 39, 0.9);
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(12px);
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 760px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }
        .install-header {
            padding: 2rem;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(255, 255, 255, 0.02);
        }
        .install-body {
            padding: 2rem;
        }
        .step-tabs {
            display: flex;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1.75rem;
            gap: 0.5rem;
            overflow-x: auto;
        }
        .tab-btn {
            background: none;
            border: none;
            color: #94a3b8;
            padding: 0.75rem 1rem;
            font-size: 0.88rem;
            font-weight: 600;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .tab-btn.active {
            color: var(--secondary);
            border-color: var(--secondary);
        }
        .form-control {
            background: rgba(31, 41, 55, 0.8) !important;
            border-color: rgba(255, 255, 255, 0.15) !important;
            color: #f8fafc !important;
        }
        .diag-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: var(--radius-md);
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>

<div class="install-card">
    <div class="install-header">
        <div class="brand-logo-icon" style="margin: 0 auto 1rem; width: 48px; height: 48px; font-size: 1.75rem;">
            <i class="bx bxs-graduation"></i>
        </div>
        <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 0.25rem;">Open LMS Setup Wizard</h1>
        <p style="color: #94a3b8; font-size: 0.9rem;">Deploy your secure, private Learning Management System in seconds.</p>
    </div>

    <div class="install-body">
        <?php if ($error = flash('error')): ?>
            <div class="toast toast-danger" style="margin-bottom: 1.5rem; position: static;">
                <i class="bx bx-error-circle"></i>
                <div style="flex:1;"><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <form action="<?= url('/install') ?>" method="POST" enctype="multipart/form-data" id="install-form">
            <?= csrf_field() ?>

            <div class="step-tabs">
                <button type="button" class="tab-btn active" data-tab="step-diag"><i class="bx bx-check-shield"></i> 1. Diagnostics</button>
                <button type="button" class="tab-btn" data-tab="step-lms"><i class="bx bx-cog"></i> 2. LMS Info</button>
                <button type="button" class="tab-btn" data-tab="step-admin"><i class="bx bx-user-check"></i> 3. Super Admin</button>
                <button type="button" class="tab-btn" data-tab="step-recovery"><i class="bx bx-key"></i> 4. Recovery</button>
                <button type="button" class="tab-btn" data-tab="step-smtp"><i class="bx bx-envelope"></i> 5. SMTP Mail</button>
            </div>

            <!-- Step 1: Diagnostics -->
            <div class="tab-content" id="step-diag">
                <h3 style="font-size: 1.1rem; margin-bottom: 1rem;">System Compatibility Check</h3>
                <?php foreach ($diagnostics as $key => $d): ?>
                    <div class="diag-item">
                        <div>
                            <strong><?= e($d['name']) ?></strong>
                            <div style="font-size: 0.8rem; color: #94a3b8;">Detected: <?= e($d['current']) ?></div>
                        </div>
                        <span class="badge <?= $d['pass'] ? 'badge-success' : 'badge-danger' ?>">
                            <i class="bx <?= $d['pass'] ? 'bx-check' : 'bx-x' ?>"></i> <?= $d['pass'] ? 'Pass' : 'Fail' ?>
                        </span>
                    </div>
                <?php endforeach; ?>

                <?php if (!$allPassed): ?>
                    <div class="toast toast-warning" style="margin-top: 1rem; position: static;">
                        <i class="bx bx-alarm-exclamation"></i>
                        <div>One or more requirements failed. Please ensure all PHP extensions are enabled before proceeding.</div>
                    </div>
                <?php else: ?>
                    <div style="margin-top: 1.5rem; text-align: right;">
                        <button type="button" class="btn btn-primary next-tab-btn" data-next="step-lms">Continue to LMS Info &rarr;</button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Step 2: LMS Info -->
            <div class="tab-content" id="step-lms" style="display: none;">
                <h3 style="font-size: 1.1rem; margin-bottom: 1rem;">LMS Branding & Preferences</h3>
                <div class="form-group">
                    <label class="form-label">LMS Portal Name *</label>
                    <input type="text" name="app_name" class="form-control" value="<?= e(old('app_name', 'Open LMS Academy')) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">LMS Description</label>
                    <textarea name="app_description" class="form-control" rows="2"><?= e(old('app_description', 'Private high-performance Learning Management System.')) ?></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Timezone *</label>
                        <input type="text" name="timezone" class="form-control" value="<?= e(old('timezone', 'UTC')) ?>" required>
                        <div class="form-help">e.g. UTC, Asia/Kolkata, America/New_York</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date Format *</label>
                        <select name="date_format" class="form-control">
                            <option value="Y-m-d">YYYY-MM-DD (2026-09-11)</option>
                            <option value="d/m/Y">DD/MM/YYYY (11/09/2026)</option>
                            <option value="m/d/Y">MM/DD/YYYY (09/11/2026)</option>
                            <option value="M j, Y">Mon D, YYYY (Sep 11, 2026)</option>
                        </select>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Time Format *</label>
                        <select name="time_format" class="form-control">
                            <option value="H:i">24-hour (14:30)</option>
                            <option value="h:i A">12-hour (02:30 PM)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Primary Brand Color</label>
                        <input type="color" name="primary_color" class="form-control" value="<?= e(old('primary_color', '#4f46e5')) ?>" style="height: 42px; padding: 4px;">
                    </div>
                </div>
                <div style="margin-top: 1.5rem; display: flex; justify-content: space-between;">
                    <button type="button" class="btn btn-outline next-tab-btn" data-next="step-diag">&larr; Back</button>
                    <button type="button" class="btn btn-primary next-tab-btn" data-next="step-admin">Next: Super Admin &rarr;</button>
                </div>
            </div>

            <!-- Step 3: Super Admin -->
            <div class="tab-content" id="step-admin" style="display: none;">
                <h3 style="font-size: 1.1rem; margin-bottom: 1rem;">Primary Super Administrator</h3>
                <div class="form-group">
                    <label class="form-label">Administrator Full Name *</label>
                    <input type="text" name="admin_name" class="form-control" value="<?= e(old('admin_name', 'System Administrator')) ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Administrator Email (Login Username) *</label>
                    <input type="email" name="admin_email" class="form-control" value="<?= e(old('admin_email', 'admin@example.com')) ?>" required>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Master Password *</label>
                        <input type="password" name="admin_password" class="form-control" required minlength="8">
                        <div class="form-help">Minimum 8 characters.</div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Master Password *</label>
                        <input type="password" name="admin_password_confirmation" class="form-control" required minlength="8">
                    </div>
                </div>
                <div style="margin-top: 1.5rem; display: flex; justify-content: space-between;">
                    <button type="button" class="btn btn-outline next-tab-btn" data-next="step-lms">&larr; Back</button>
                    <button type="button" class="btn btn-primary next-tab-btn" data-next="step-recovery">Next: Recovery &rarr;</button>
                </div>
            </div>

            <!-- Step 4: Recovery -->
            <div class="tab-content" id="step-recovery" style="display: none;">
                <h3 style="font-size: 1.1rem; margin-bottom: 0.5rem;">Account Password Recovery</h3>
                <p style="color: #94a3b8; font-size: 0.85rem; margin-bottom: 1.25rem;">
                    In case of lost credentials, your normalized recovery answer provides a secondary recovery channel. Answers are securely hashed and never stored as plaintext.
                </p>
                <div class="form-group">
                    <label class="form-label">Security Question *</label>
                    <select name="recovery_question" class="form-control" required>
                        <option value="What was the name of your first elementary school?">What was the name of your first elementary school?</option>
                        <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                        <option value="What was the model of your first car or bike?">What was the model of your first car or bike?</option>
                        <option value="In what city was your first job located?">In what city was your first job located?</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Security Answer *</label>
                    <input type="text" name="recovery_answer" class="form-control" required placeholder="Your answer (will be hashed)">
                    <div class="form-help">Answers are case-insensitive and whitespace-normalized before hashing.</div>
                </div>
                <div style="margin-top: 1.5rem; display: flex; justify-content: space-between;">
                    <button type="button" class="btn btn-outline next-tab-btn" data-next="step-admin">&larr; Back</button>
                    <button type="button" class="btn btn-primary next-tab-btn" data-next="step-smtp">Next: SMTP Configuration &rarr;</button>
                </div>
            </div>

            <!-- Step 5: SMTP Configuration -->
            <div class="tab-content" id="step-smtp" style="display: none;">
                <h3 style="font-size: 1.1rem; margin-bottom: 1rem;">SMTP Mail Server Settings</h3>
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">SMTP Host</label>
                        <input type="text" name="smtp_host" id="smtp_host" class="form-control" value="<?= e(old('smtp_host', 'localhost')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Port</label>
                        <input type="number" name="smtp_port" id="smtp_port" class="form-control" value="<?= e(old('smtp_port', 587)) ?>">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Encryption</label>
                        <select name="smtp_encryption" class="form-control">
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="none">None</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">From Email</label>
                        <input type="email" name="from_email" class="form-control" value="<?= e(old('from_email', 'noreply@example.com')) ?>">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">SMTP Username</label>
                        <input type="text" name="smtp_username" class="form-control" value="<?= e(old('smtp_username')) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">SMTP Password</label>
                        <input type="password" name="smtp_password" class="form-control" autocomplete="new-password">
                    </div>
                </div>
                <div style="margin-top: 1rem; margin-bottom: 1.5rem;">
                    <button type="button" class="btn btn-outline btn-sm" id="test-smtp-btn"><i class="bx bx-check-shield"></i> Test SMTP Connection</button>
                    <span id="smtp-test-result" style="margin-left: 0.75rem; font-size: 0.85rem;"></span>
                </div>

                <div style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1.5rem; display: flex; justify-content: space-between;">
                    <button type="button" class="btn btn-outline next-tab-btn" data-next="step-recovery">&larr; Back</button>
                    <button type="submit" class="btn btn-primary btn-lg"><i class="bx bx-rocket"></i> Complete Installation & Lock Installer</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.tab-btn, .next-tab-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        const targetId = btn.getAttribute('data-tab') || btn.getAttribute('data-next');
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.toggle('active', b.getAttribute('data-tab') === targetId);
        });
        document.querySelectorAll('.tab-content').forEach(c => {
            c.style.display = (c.id === targetId) ? 'block' : 'none';
        });
    });
});

document.getElementById('test-smtp-btn')?.addEventListener('click', async () => {
    const host = document.getElementById('smtp_host').value;
    const port = document.getElementById('smtp_port').value;
    const resultSpan = document.getElementById('smtp-test-result');
    resultSpan.innerText = 'Testing connection...';
    resultSpan.style.color = '#94a3b8';

    try {
        const res = await fetch('<?= url('/install/test-email') ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            body: JSON.stringify({smtp_host: host, smtp_port: port, _csrf_token: '<?= csrf_token() ?>'})
        });
        const data = await res.json();
        resultSpan.innerText = data.message;
        resultSpan.style.color = data.success ? '#10b981' : '#ef4444';
    } catch (err) {
        resultSpan.innerText = 'Test failed: server unreachable.';
        resultSpan.style.color = '#ef4444';
    }
});
</script>

</body>
</html>
