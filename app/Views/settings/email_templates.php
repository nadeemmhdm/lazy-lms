<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bx bx-envelope"></i> Automated Email Templates</h1>
        <p class="page-subtitle">Customize subject lines, message bodies, and trigger variables for automated system emails.</p>
    </div>
</div>

<div class="card" style="margin-bottom: 1.5rem; background: var(--bg-hover, #f8fafc); border-left: 4px solid var(--primary);">
    <h3 style="margin-top: 0; font-size: 1.05rem; margin-bottom: 0.5rem;"><i class="bx bx-code-alt"></i> Available Template Variables</h3>
    <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 0.75rem;">Insert these placeholders into subjects and bodies to dynamically populate student and course data:</p>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <code>{{user_name}}</code>
        <code>{{user_email}}</code>
        <code>{{course_name}}</code>
        <code>{{batch_name}}</code>
        <code>{{assignment_name}}</code>
        <code>{{exam_name}}</code>
        <code>{{certificate_number}}</code>
        <code>{{login_time}}</code>
        <code>{{ip_address}}</code>
        <code>{{lms_name}}</code>
    </div>
</div>

<div style="display: grid; gap: 1.5rem;">
    <?php foreach ($templates as $tmpl): ?>
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.75rem;">
                <div>
                    <strong style="font-size: 1.1rem;"><?= e($tmpl['template_key']) ?></strong>
                    <span class="badge <?= !empty($tmpl['is_enabled']) ? 'badge-success' : 'badge-danger' ?>" style="margin-left: 0.5rem;">
                        <?= !empty($tmpl['is_enabled']) ? 'Enabled' : 'Disabled' ?>
                    </span>
                </div>
            </div>

            <form action="<?= url('/settings/email-templates/' . $tmpl['id']) ?>" method="POST">
                <input type="hidden" name="_token" value="<?= csrf_token() ?>">

                <div class="form-group">
                    <label class="form-label">Email Subject Line *</label>
                    <input type="text" name="subject" class="form-control" value="<?= e($tmpl['subject']) ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label">HTML Email Body *</label>
                    <textarea name="body" class="form-control" rows="5" required><?= e($tmpl['body']) ?></textarea>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem;">
                    <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                        <input type="checkbox" name="is_enabled" value="1" <?= !empty($tmpl['is_enabled']) ? 'checked' : '' ?>>
                        <span>Enable this automated email</span>
                    </label>

                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="bx bx-save"></i> Save Template
                    </button>
                </div>
            </form>
        </div>
    <?php endforeach; ?>
</div>
