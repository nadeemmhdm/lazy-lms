<div class="page-header">
    <div>
        <h1 class="page-title">New Attendance Session</h1>
        <p class="page-subtitle">Create a class attendance roll-call session for a batch.</p>
    </div>
    <div>
        <a href="<?= url('/attendance') ?>" class="btn btn-outline"><i class="bx bx-arrow-back"></i> Back</a>
    </div>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <form action="<?= url('/attendance') ?>" method="POST">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label class="form-label">Batch *</label>
            <select name="batch_id" class="form-control" required>
                <option value="">Select Batch...</option>
                <?php foreach ($batches as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= e($b['name']) ?> (<?= e($b['code']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Session Title *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Lecture 04: Operating Systems Discussion" required>
        </div>

        <div class="form-group">
            <label class="form-label">Session Date *</label>
            <input type="date" name="session_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
            <a href="<?= url('/attendance') ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bx bx-check"></i> Initialize Attendance</button>
        </div>
    </form>
</div>
