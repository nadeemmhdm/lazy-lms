<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= url('/admin/teachers') ?>">Teachers</a>
            <i class="bx bx-chevron-right"></i>
            <a href="<?= url('/admin/teachers/' . $teacher['id']) ?>"><?= e($teacher['name']) ?></a>
            <i class="bx bx-chevron-right"></i>
            <span>Edit</span>
        </div>
        <h1 class="page-title">Edit Faculty Profile</h1>
    </div>
</div>

<div class="card" style="max-width: 680px;">
    <form action="<?= url('/admin/teachers/' . $teacher['id']) ?>" method="POST" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">

        <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= e($teacher['name']) ?>">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Email Address *</label>
                <input type="email" name="email" class="form-control" required value="<?= e($teacher['email']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="text" name="phone" class="form-control" value="<?= e($teacher['phone']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Status *</label>
            <select name="status" class="form-control">
                <option value="active" <?= $teacher['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="archived" <?= $teacher['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                <option value="suspended" <?= $teacher['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Reset Password (leave empty to keep current)</label>
            <input type="password" name="new_password" class="form-control" minlength="8" placeholder="Enter new password if changing">
        </div>

        <div class="form-group">
            <label class="form-label">Update Avatar</label>
            <input type="file" name="avatar" class="form-control" accept="image/*">
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save Changes</button>
            <a href="<?= url('/admin/teachers/' . $teacher['id']) ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
