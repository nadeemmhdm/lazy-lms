<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= url('/admin/batches') ?>">Batches</a>
            <i class="bx bx-chevron-right"></i>
            <a href="<?= url('/admin/batches/' . $batch['id']) ?>"><?= e($batch['name']) ?></a>
            <i class="bx bx-chevron-right"></i>
            <span>Edit</span>
        </div>
        <h1 class="page-title">Edit Batch: <?= e($batch['name']) ?></h1>
    </div>
</div>

<div class="card" style="max-width: 680px;">
    <form action="<?= url('/admin/batches/' . $batch['id']) ?>" method="POST">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" value="PUT">

        <div class="form-group">
            <label class="form-label">Batch Name *</label>
            <input type="text" name="name" class="form-control" required value="<?= e($batch['name']) ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Batch Code *</label>
            <input type="text" name="code" class="form-control" required value="<?= e($batch['code']) ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"><?= e($batch['description']) ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= e($batch['start_date']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= e($batch['end_date']) ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Status *</label>
            <select name="status" class="form-control">
                <option value="active" <?= $batch['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="draft" <?= $batch['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                <option value="completed" <?= $batch['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                <option value="archived" <?= $batch['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
            </select>
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Update Batch</button>
            <a href="<?= url('/admin/batches/' . $batch['id']) ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
