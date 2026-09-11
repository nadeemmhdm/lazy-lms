<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= url('/admin/batches') ?>">Batches</a>
            <i class="bx bx-chevron-right"></i>
            <span>Create Batch</span>
        </div>
        <h1 class="page-title">Create Academic Batch</h1>
    </div>
</div>

<div class="card" style="max-width: 680px;">
    <form action="<?= url('/admin/batches') ?>" method="POST">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label">Batch Name *</label>
            <input type="text" name="name" class="form-control" required placeholder="e.g. 2026 Morning Batch" value="<?= e(old('name')) ?>">
        </div>

        <div class="form-group">
            <label class="form-label">Batch Code *</label>
            <input type="text" name="code" class="form-control" required placeholder="e.g. BATCH-2026-AM" value="<?= e(old('code')) ?>">
            <div class="form-help">Unique identifier for this student group.</div>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Cohort notes, schedule specifics..."><?= e(old('description')) ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= e(old('start_date', date('Y-m-d'))) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= e(old('end_date')) ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Status *</label>
            <select name="status" class="form-control">
                <option value="active">Active</option>
                <option value="draft">Draft</option>
                <option value="completed">Completed</option>
                <option value="archived">Archived</option>
            </select>
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save Batch</button>
            <a href="<?= url('/admin/batches') ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
