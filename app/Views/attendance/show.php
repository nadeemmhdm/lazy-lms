<div class="page-header">
    <div>
        <h1 class="page-title"><?= e($session['title']) ?></h1>
        <p class="page-subtitle">Batch: <?= e($session['batch_name']) ?> | Date: <?= e($session['session_date']) ?></p>
    </div>
    <div>
        <a href="<?= url('/attendance') ?>" class="btn btn-outline"><i class="bx bx-arrow-back"></i> Back to Sessions</a>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form action="<?= url('/attendance/' . $session['id']) ?>" method="POST">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th style="text-align: right;">Attendance Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendance)): ?>
                        <tr><td colspan="3" style="text-align: center; color: var(--text-muted); padding: 2rem;">No students found in this batch.</td></tr>
                    <?php else: ?>
                        <?php foreach ($attendance as $a): ?>
                            <tr>
                                <td><strong><?= e($a['student_name']) ?></strong></td>
                                <td><small style="color: var(--text-muted);"><?= e($a['student_email']) ?></small></td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 0.5rem;">
                                        <label style="display: flex; align-items: center; gap: 0.25rem; font-size: 0.85rem; cursor: pointer;">
                                            <input type="radio" name="statuses[<?= $a['user_id'] ?>]" value="present" <?= $a['status'] === 'present' ? 'checked' : '' ?>> Present
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.25rem; font-size: 0.85rem; cursor: pointer;">
                                            <input type="radio" name="statuses[<?= $a['user_id'] ?>]" value="absent" <?= $a['status'] === 'absent' ? 'checked' : '' ?>> Absent
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.25rem; font-size: 0.85rem; cursor: pointer;">
                                            <input type="radio" name="statuses[<?= $a['user_id'] ?>]" value="late" <?= $a['status'] === 'late' ? 'checked' : '' ?>> Late
                                        </label>
                                        <label style="display: flex; align-items: center; gap: 0.25rem; font-size: 0.85rem; cursor: pointer;">
                                            <input type="radio" name="statuses[<?= $a['user_id'] ?>]" value="excused" <?= $a['status'] === 'excused' ? 'checked' : '' ?>> Excused
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
            <a href="<?= url('/attendance') ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save Attendance Changes</button>
        </div>
    </form>
</div>
