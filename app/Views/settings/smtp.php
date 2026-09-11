<div class="page-header">
    <div>
        <h1 class="page-title">Custom SMTP Email Gateway</h1>
        <p class="page-subtitle">Configure outbound mail delivery for password resets, grade releases, and student notifications.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; max-width: 900px; margin: 0 auto;">
    <!-- SMTP Settings Form -->
    <div class="card">
        <form action="<?= url('/settings/smtp') ?>" method="POST">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">SMTP Server Host *</label>
                    <input type="text" name="smtp_host" class="form-control" value="<?= e($smtp['smtp_host'] ?? '') ?>" placeholder="e.g. smtp.mailgun.org or smtp.gmail.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Port *</label>
                    <input type="number" name="smtp_port" class="form-control" value="<?= e($smtp['smtp_port'] ?? '587') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Encryption Protocol</label>
                <select name="smtp_encryption" class="form-control">
                    <option value="tls" <?= ($smtp['smtp_encryption'] ?? '') === 'tls' ? 'selected' : '' ?>>TLS (Recommended: Port 587)</option>
                    <option value="ssl" <?= ($smtp['smtp_encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL (Port 465)</option>
                    <option value="none" <?= ($smtp['smtp_encryption'] ?? '') === 'none' ? 'selected' : '' ?>>None (Port 25)</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">SMTP Username</label>
                    <input type="text" name="smtp_username" class="form-control" value="<?= e($smtp['smtp_username'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">SMTP Password</label>
                    <input type="password" name="smtp_password" class="form-control" placeholder="•••••••• (leave blank to keep unchanged)">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Sender Name</label>
                    <input type="text" name="from_name" class="form-control" value="<?= e($smtp['from_name'] ?? 'Open LMS Notifications') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Sender Email Address</label>
                    <input type="email" name="from_email" class="form-control" value="<?= e($smtp['from_email'] ?? '') ?>" placeholder="noreply@institution.edu">
                </div>
            </div>

            <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end;">
                <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save SMTP Settings</button>
            </div>
        </form>
    </div>

    <!-- Test Email Dispatcher -->
    <div class="card" style="height: fit-content;">
        <h3 style="margin-top: 0; margin-bottom: 0.75rem;">Connection Diagnostic</h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5;">
            Validate socket connectivity and handshake with your configured SMTP server.
        </p>

        <form action="<?= url('/settings/smtp/test') ?>" method="POST">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label class="form-label">Test Recipient Email</label>
                <input type="email" name="test_email" class="form-control" placeholder="admin@domain.com" required>
            </div>

            <button type="submit" class="btn btn-outline" style="width: 100%;"><i class="bx bx-broadcast"></i> Test SMTP Server</button>
        </form>
    </div>
</div>
