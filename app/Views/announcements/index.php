<div class="page-header">
    <div>
        <h1 class="page-title">Institution Announcements</h1>
        <p class="page-subtitle">Publish critical notifications, system updates, and batch-wide broadcasts.</p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('new-announcement-modal').style.display='flex'">
            <i class="bx bx-plus-circle"></i> New Announcement
        </button>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Audience / Target</th>
                    <th>Priority</th>
                    <th>Author</th>
                    <th>Date Published</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($announcements)): ?>
                    <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No announcements posted yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($announcements as $a): ?>
                        <tr>
                            <td>
                                <strong><?= e($a['title']) ?></strong>
                                <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem;">
                                    <?= e(substr(strip_tags($a['content']), 0, 100)) ?>...
                                </div>
                            </td>
                            <td><span class="badge badge-neutral"><?= e($a['target_name']) ?></span></td>
                            <td>
                                <span class="badge <?= $a['priority'] === 'high' ? 'badge-danger' : 'badge-info' ?>">
                                    <?= ucfirst($a['priority']) ?>
                                </span>
                            </td>
                            <td style="font-size: 0.85rem;"><?= e($a['author_name']) ?></td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);"><?= e($a['created_at']) ?></td>
                            <td style="text-align: right;">
                                <form action="<?= url('/announcements/' . $a['id'] . '/delete') ?>" method="POST" style="display: inline;" onsubmit="return confirm('Delete announcement?');">
                                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                    <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger);"><i class="bx bx-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- New Announcement Modal -->
<div id="new-announcement-modal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="max-width: 600px; width: 100%; margin: 1rem;">
        <h3 style="margin-top: 0; margin-bottom: 1rem;">Create Announcement</h3>
        <form action="<?= url('/announcements') ?>" method="POST">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label class="form-label">Announcement Title *</label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Schedule Change for Upcoming Holiday" required>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Target Audience</label>
                    <select name="target_type" id="target-type" class="form-control" onchange="toggleTargetId(this.value)">
                        <option value="global">All LMS Users</option>
                        <option value="batch">Specific Batch</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-control">
                        <option value="normal">Normal</option>
                        <option value="high">High (Pinned / Highlighted)</option>
                    </select>
                </div>
            </div>

            <div class="form-group" id="batch-select-group" style="display: none;">
                <label class="form-label">Select Batch</label>
                <select name="target_id" class="form-control">
                    <?php foreach ($batches as $b): ?>
                        <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Announcement Message *</label>
                <textarea name="content" class="form-control" rows="4" placeholder="Enter announcement text..." required></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('new-announcement-modal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bx bx-send"></i> Publish Announcement</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleTargetId(val) {
    document.getElementById('batch-select-group').style.display = (val === 'batch') ? 'block' : 'none';
}
</script>
