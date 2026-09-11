<div class="page-header">
    <div>
        <h1 class="page-title">Create Assessment</h1>
        <p class="page-subtitle">Configure assessment timing, pass score criteria, and course association.</p>
    </div>
    <div>
        <a href="<?= url('/assessments') ?>" class="btn btn-outline"><i class="bx bx-arrow-back"></i> Back</a>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form action="<?= url('/assessments') ?>" method="POST">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label class="form-label">Assessment Title *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Mid-term Examination" required>
        </div>

        <div class="form-group">
            <label class="form-label">Course *</label>
            <select name="course_id" class="form-control" required>
                <option value="">Select Course...</option>
                <?php foreach ($courses as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= e($c['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Description / Instructions</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Provide exam guidelines to students..."></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Total Marks</label>
                <input type="number" step="1" name="total_marks" class="form-control" value="100" required>
            </div>
            <div class="form-group">
                <label class="form-label">Pass Marks</label>
                <input type="number" step="1" name="pass_marks" class="form-control" value="40" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Time Limit (Minutes)</label>
                <input type="number" name="time_limit" class="form-control" value="30" required>
            </div>
            <div class="form-group">
                <label class="form-label">Attempts Allowed (0 = Unlimited)</label>
                <input type="number" name="attempts_allowed" class="form-control" value="1" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; align-items: center; margin-top: 1rem;">
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="draft">Draft</option>
                    <option value="published" selected>Published</option>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;">
                <input type="checkbox" id="randomize" name="randomize_questions" value="1" checked>
                <label for="randomize" style="margin: 0; font-weight: 500;">Randomize questions for students</label>
            </div>
        </div>

        <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
            <a href="<?= url('/assessments') ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bx bx-check-circle"></i> Save & Build Questions</button>
        </div>
    </form>
</div>
