<div class="page-header">
    <div>
        <h1 class="page-title">Branding & System Customization</h1>
        <p class="page-subtitle">Customize institution name, login artwork, themes, and regional formats without modifying code.</p>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form action="<?= url('/settings/branding') ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">

        <h3 style="margin-top: 0; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">General Appearance</h3>

        <div class="form-group">
            <label class="form-label">Institution / LMS Name *</label>
            <input type="text" name="lms_name" class="form-control" value="<?= e($settings['lms_name'] ?? 'Open LMS') ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">Institution Tagline / Description</label>
            <input type="text" name="lms_description" class="form-control" value="<?= e($settings['lms_description'] ?? 'Modern Private Learning Management Platform') ?>">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Primary Theme Color</label>
                <input type="color" name="primary_color" class="form-control" value="<?= e($settings['primary_color'] ?? '#4f46e5') ?>" style="height: 42px; padding: 0.25rem;">
            </div>
            <div class="form-group">
                <label class="form-label">Secondary Accent Color</label>
                <input type="color" name="secondary_color" class="form-control" value="<?= e($settings['secondary_color'] ?? '#06b6d4') ?>" style="height: 42px; padding: 0.25rem;">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Logo Upload</label>
                <input type="file" name="logo" class="form-control" accept="image/*">
                <?php if (!empty($settings['logo_url'])): ?>
                    <small style="color: var(--text-muted);">Current: <a href="<?= e($settings['logo_url']) ?>" target="_blank">View Logo</a></small>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">Favicon Upload</label>
                <input type="file" name="favicon" class="form-control" accept="image/*">
            </div>
        </div>

        <h3 style="margin-top: 1.5rem; margin-bottom: 1rem; border-top: 1px solid var(--border-color); padding-top: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Regional & Date Settings</h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">System Timezone</label>
                <select name="timezone" class="form-control">
                    <option value="UTC" <?= ($settings['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC</option>
                    <option value="Asia/Kolkata" <?= ($settings['timezone'] ?? '') === 'Asia/Kolkata' ? 'selected' : '' ?>>Asia/Kolkata (IST)</option>
                    <option value="America/New_York" <?= ($settings['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>America/New_York (EST)</option>
                    <option value="Europe/London" <?= ($settings['timezone'] ?? '') === 'Europe/London' ? 'selected' : '' ?>>Europe/London (GMT)</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Date Format</label>
                <select name="date_format" class="form-control">
                    <option value="Y-m-d">YYYY-MM-DD</option>
                    <option value="d/m/Y">DD/MM/YYYY</option>
                    <option value="m/d/Y">MM/DD/YYYY</option>
                </select>
            </div>
        </div>

        <h3 style="margin-top: 1.5rem; margin-bottom: 1rem; border-top: 1px solid var(--border-color); padding-top: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem;">Module Toggles</h3>

        <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem;">
            <input type="checkbox" id="show_cal" name="show_calendar" value="1" <?= ($settings['show_calendar'] ?? '1') === '1' ? 'checked' : '' ?>>
            <label for="show_cal" style="margin: 0; font-weight: 500;">Enable Calendar in Navigation Sidebar</label>
        </div>

        <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
            <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save Branding Settings</button>
        </div>
    </form>
</div>
