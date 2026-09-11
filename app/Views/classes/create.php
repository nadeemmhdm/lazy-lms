<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bx bx-calendar-plus"></i> Schedule Live Class</h1>
        <p class="page-subtitle">Schedule a live online video lecture or seminar for your batch.</p>
    </div>
    <div>
        <a href="<?= url('/classes') ?>" class="btn btn-outline"><i class="bx bx-arrow-back"></i> Back</a>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form action="<?= url('/classes') ?>" method="POST">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label class="form-label">Class Title *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Live Q&A: Full-Stack Architecture Review" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Course *</label>
                <select name="course_id" class="form-control" required>
                    <option value="">Select Course...</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['public_id'] ?? $c['id'] ?>"><?= e($c['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Batch *</label>
                <select name="batch_id" class="form-control" required>
                    <option value="">Select Batch...</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?= $b['public_id'] ?? $b['id'] ?>"><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Date *</label>
                <input type="date" name="class_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Start Time *</label>
                <input type="time" name="start_time" class="form-control" value="09:00" required>
            </div>
            <div class="form-group">
                <label class="form-label">End Time *</label>
                <input type="time" name="end_time" class="form-control" value="10:30" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Live Meeting URL (Zoom / Google Meet / Microsoft Teams) *</label>
            <input type="url" name="meeting_url" class="form-control" placeholder="https://meet.google.com/xyz-abcd-efg or https://zoom.us/j/..." required>
            <small style="color: var(--text-muted); font-size: 0.8rem;">Meeting URL is strictly protected and only accessible to enrolled batch participants.</small>
        </div>

        <div class="form-group">
            <label class="form-label">Session Agenda / Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Topics to cover, prerequisites, software or books required..."></textarea>
        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
            <a href="<?= url('/classes') ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bx bx-calendar-check"></i> Schedule Class</button>
        </div>
    </form>
</div>
