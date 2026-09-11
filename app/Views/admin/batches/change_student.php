<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= url('/admin/batches') ?>">Batches</a>
            <i class="bx bx-chevron-right"></i>
            <span>Student Batch Transfer</span>
        </div>
        <h1 class="page-title">Change Student Batch</h1>
        <p class="page-subtitle">Transfer a student to a different academic cohort while safely preserving all historical grades and submissions.</p>
    </div>
</div>

<div class="card" style="max-width: 680px;">
    <form action="<?= url('/admin/batches/change-student') ?>" method="POST">
        <?= csrf_field() ?>

        <div class="form-group">
            <label class="form-label">Select Student *</label>
            <select name="student_id" class="form-control" required id="student-select">
                <option value="">-- Choose Student --</option>
                <?php foreach ($students as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= ($preselectedStudentId == $s['id']) ? 'selected' : '' ?>>
                        <?= e($s['name']) ?> (Current Batch: <?= e($s['current_batch_name'] ?? 'None / Unassigned') ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">New Target Batch *</label>
            <select name="new_batch_id" class="form-control" required>
                <option value="">-- Choose Target Batch --</option>
                <?php foreach ($batches as $b): ?>
                    <option value="<?= $b['id'] ?>"><?= e($b['name']) ?> (<?= e($b['code']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group" style="background: var(--bg); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border);">
            <label class="form-label" style="font-weight: 700; margin-bottom: 0.75rem;">Course Handling Policy *</label>
            <div style="display: flex; flex-direction: column; gap: 0.65rem;">
                <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer;">
                    <input type="radio" name="transition_mode" value="replace" style="margin-top: 0.25rem;">
                    <div>
                        <strong>Replace courses using new batch</strong>
                        <div style="font-size: 0.82rem; color: var(--text-muted);">Assign new batch courses exclusively; retain past completed grades in academic records.</div>
                    </div>
                </label>
                <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer;">
                    <input type="radio" name="transition_mode" value="keep" style="margin-top: 0.25rem;">
                    <div>
                        <strong>Keep existing courses</strong>
                        <div style="font-size: 0.82rem; color: var(--text-muted);">Retain previous course access alongside the administrative batch change.</div>
                    </div>
                </label>
                <label style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer;">
                    <input type="radio" name="transition_mode" value="merge" checked style="margin-top: 0.25rem;">
                    <div>
                        <strong>Merge courses (Recommended)</strong>
                        <div style="font-size: 0.82rem; color: var(--text-muted);">Ensure student retains past courses while automatically gaining access to all new batch courses.</div>
                    </div>
                </label>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Transfer Notes / Reason</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Schedule conflict, moved to evening session..."></textarea>
            <div class="form-help">Recorded in student's permanent audit history.</div>
        </div>

        <div style="display: flex; gap: 0.75rem; margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary"><i class="bx bx-check"></i> Process Batch Transfer</button>
            <a href="<?= url('/admin/batches') ?>" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>
