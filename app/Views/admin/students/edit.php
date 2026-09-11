<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= url('/admin/students') ?>">Students</a>
            <i class="bx bx-chevron-right"></i>
            <a href="<?= url('/admin/students/' . $student['id']) ?>"><?= e($student['name']) ?></a>
            <i class="bx bx-chevron-right"></i>
            <span>Edit</span>
        </div>
        <h1 class="page-title">Edit Student Profile</h1>
    </div>
</div>

<div class="card" style="max-width: 680px;">
    <form action="<?= url('/admin/students/' . $student['id']) ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">

        <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= e($student['name']) ?>">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Email Address *</label>
                <input type="email" name="email" class="form-control" required value="<?= e($student['email']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Student ID</label>
                <input type="text" name="student_id" class="form-control" value="<?= e($student['student_id']) ?>">
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?= e($student['phone']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Status *</label>
                <select name="status" class="form-control">
                    <option value="active" <?= $student['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="archived" <?= $student['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                    <option value="suspended" <?= $student['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Reset Password (leave blank to keep current)</label>
            <input type="password" name="new_password" class="form-control" minlength="8" placeholder="Enter new password if resetting">
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                <input type="checkbox" name="force_password_change" value="1" <?= $student['force_password_change'] ? 'checked' : '' ?>>
                <span>Require password change on next login</span>
            </label>
        </div>

        <div class="form-group">
            <label class="form-label">Update Profile Photo</label>
            <input type="file" name="avatar" class="form-control" accept="image/*">
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save Changes</button>
            <a href="<?= url('/admin/students/' . $student['id']) ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
