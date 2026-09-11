<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bx bx-broadcast"></i> Create Platform Notification</h1>
        <p class="page-subtitle">Publish announcements targeted to specific batches, roles, or the entire LMS audience.</p>
    </div>
    <div>
        <a href="<?= url('/platform-notifications') ?>" class="btn btn-outline"><i class="bx bx-arrow-back"></i> Back</a>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form action="<?= url('/platform-notifications') ?>" method="POST">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label class="form-label">Notification Title *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Examination Schedule Published" required>
        </div>

        <div class="form-group">
            <label class="form-label">Notification Message *</label>
            <textarea name="message" class="form-control" rows="5" placeholder="Write full announcement text..." required></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Target Audience *</label>
                <select name="target_type" id="targetTypeSelect" class="form-control" required>
                    <option value="all">Entire Platform (All Users)</option>
                    <option value="batch">Specific Batch</option>
                    <option value="teachers">Teachers Only</option>
                    <option value="students">Students Only</option>
                    <option value="user">Specific User</option>
                </select>
            </div>

            <!-- Batch selector -->
            <div class="form-group" id="batchSelectGroup" style="display: none;">
                <label class="form-label">Select Batch *</label>
                <select name="target_id" id="batchSelectInput" class="form-control">
                    <option value="">Choose Batch...</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- User selector -->
            <div class="form-group" id="userSelectGroup" style="display: none;">
                <label class="form-label">Select User *</label>
                <select name="target_user_id" id="userSelectInput" class="form-control">
                    <option value="">Choose User...</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Priority Level *</label>
                <select name="priority" class="form-control" required>
                    <option value="normal">Normal</option>
                    <option value="important">Important</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Start Date & Time</label>
                <input type="datetime-local" name="start_time" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Expiry Date & Time</label>
                <input type="datetime-local" name="expiry_time" class="form-control">
            </div>
        </div>

        <div style="display: flex; gap: 2rem; margin: 1rem 0; padding: 0.75rem 1rem; background: var(--bg-hover, #f8fafc); border-radius: 6px; border: 1px solid var(--border-color);">
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                <input type="checkbox" name="send_email" value="1" checked>
                <span><strong>Send Automated SMTP Email</strong> to target recipients</span>
            </label>
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                <input type="radio" name="status" value="published" checked>
                <span>Publish Immediately</span>
            </label>
            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-size: 0.9rem;">
                <input type="radio" name="status" value="draft">
                <span>Save as Draft</span>
            </label>
        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
            <a href="<?= url('/platform-notifications') ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bx bx-send"></i> Dispatch Notification</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const targetSelect = document.getElementById('targetTypeSelect');
    const batchGroup = document.getElementById('batchSelectGroup');
    const userGroup = document.getElementById('userSelectGroup');
    const batchInput = document.getElementById('batchSelectInput');
    const userInput = document.getElementById('userSelectInput');

    function updateTargetFields() {
        const val = targetSelect.value;
        if (val === 'batch') {
            batchGroup.style.display = 'block';
            userGroup.style.display = 'none';
            batchInput.name = 'target_id';
            userInput.name = 'unused_target_id';
        } else if (val === 'user') {
            batchGroup.style.display = 'none';
            userGroup.style.display = 'block';
            userInput.name = 'target_id';
            batchInput.name = 'unused_target_id';
        } else {
            batchGroup.style.display = 'none';
            userGroup.style.display = 'none';
        }
    }

    targetSelect.addEventListener('change', updateTargetFields);
    updateTargetFields();
});
</script>
