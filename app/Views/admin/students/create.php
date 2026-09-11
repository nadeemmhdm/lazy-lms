<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= url('/admin/students') ?>">Students</a>
            <i class="bx bx-chevron-right"></i>
            <span>Enroll Student</span>
        </div>
        <h1 class="page-title">Enroll New Student</h1>
    </div>
</div>

<div class="card" style="max-width: 680px;">
    <form action="<?= url('/admin/students') ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" required placeholder="John Doe" value="<?= e(old('name')) ?>">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Email Address (Login Username) *</label>
                <input type="email" name="email" class="form-control" required placeholder="student@example.com" value="<?= e(old('email')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Student ID (Optional)</label>
                <input type="text" name="student_id" class="form-control" placeholder="Auto-generated if left blank" value="<?= e(old('student_id')) ?>">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" placeholder="+1234567890" value="<?= e(old('phone')) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Academic Batch</label>
                <select name="batch_id" class="form-control">
                    <option value="">-- Assign Batch Later --</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= e($b['name']) ?> (<?= e($b['code']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Temporary Password *</label>
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
                <span>Force student to change password upon first login</span>
            </label>
        </div>

        <div class="form-group">
            <label class="form-label">Profile Photo</label>
            <input type="file" name="avatar" class="form-control" accept="image/*">
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary"><i class="bx bx-user-plus"></i> Enroll Student</button>
            <a href="<?= url('/admin/students') ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
