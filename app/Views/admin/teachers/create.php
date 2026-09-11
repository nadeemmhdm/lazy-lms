<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= url('/admin/teachers') ?>">Teachers</a>
            <i class="bx bx-chevron-right"></i>
            <span>Register Faculty</span>
        </div>
        <h1 class="page-title">Register Faculty Member</h1>
    </div>
</div>

<div class="card" style="max-width: 680px;">
    <form action="<?= url('/admin/teachers') ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" required placeholder="Professor Jane Smith" value="<?= e(old('name')) ?>">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Email Address (Login Username) *</label>
                <input type="email" name="email" class="form-control" required placeholder="teacher@example.com" value="<?= e(old('email')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" placeholder="+1234567890" value="<?= e(old('phone')) ?>">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Initial Password *</label>
                <input type="password" name="password" class="form-control" required minlength="8" placeholder="At least 8 characters">
            </div>
            <div class="form-group">
                <label class="form-label">Account Status *</label>
                <select name="status" class="form-control">
                    <option value="active">Active</option>
                    <option value="archived">Archived</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="force_password_change" value="1" checked>
                <span>Require password change on first login</span>
            </label>
        </div>

        <div class="form-group">
            <label class="form-label">Profile Photo</label>
            <input type="file" name="avatar" class="form-control" accept="image/*">
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary"><i class="bx bx-user-plus"></i> Save Faculty Member</button>
            <a href="<?= url('/admin/teachers') ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
