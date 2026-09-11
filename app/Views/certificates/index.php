<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bx bx-certification"></i> Certificate Management</h1>
        <p class="page-subtitle">Verified credentials, batch-wise issuance, platform-wide distributions, and status revocations.</p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <button type="button" class="btn btn-outline" onclick="document.getElementById('batchModal').style.display='flex'">
            <i class="bx bx-group"></i> Issue Batch-Wise
        </button>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('singleModal').style.display='flex'">
            <i class="bx bx-plus"></i> Award Certificate
        </button>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Certificate Number</th>
                    <th>Student Name</th>
                    <th>Course & Batch</th>
                    <th>Issue Date</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($certificates)): ?>
                    <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">No certificates issued yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($certificates as $c): ?>
                        <tr>
                            <td>
                                <code><?= e($c['certificate_number'] ?? $c['certificate_code']) ?></code>
                            </td>
                            <td>
                                <strong><?= e($c['student_name']) ?></strong><br>
                                <span style="font-size: 0.8rem; color: var(--text-muted);"><?= e($c['student_email']) ?></span>
                            </td>
                            <td>
                                <?= e($c['course_title']) ?><br>
                                <span style="font-size: 0.8rem; color: var(--text-muted);"><?= e($c['batch_name'] ?? 'Direct Award') ?></span>
                            </td>
                            <td><?= date('d M Y', strtotime($c['issue_date'])) ?></td>
                            <td>
                                <span class="badge <?= ($c['status'] ?? 'issued') === 'issued' ? 'badge-success' : 'badge-danger' ?>">
                                    <?= ucfirst($c['status'] ?? 'issued') ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.35rem;">
                                    <a href="<?= url('/certificates/' . ($c['public_id'] ?? $c['id'])) ?>" class="btn btn-outline btn-sm">
                                        <i class="bx bx-show"></i> View
                                    </a>
                                    <a href="<?= url('/certificate/verify/' . ($c['verification_code'] ?? $c['certificate_code'])) ?>" target="_blank" class="btn btn-outline btn-sm">
                                        <i class="bx bx-check-shield"></i> Verify
                                    </a>
                                    <?php if (($c['status'] ?? 'issued') === 'issued'): ?>
                                        <form action="<?= url('/certificates/' . ($c['public_id'] ?? $c['id']) . '/revoke') ?>" method="POST" style="display: inline;" onsubmit="return confirm('Revoke this certificate?');">
                                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                            <button type="submit" class="btn btn-sm btn-outline text-danger" title="Revoke"><i class="bx bx-block"></i></button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?= url('/certificates/' . ($c['public_id'] ?? $c['id']) . '/restore') ?>" method="POST" style="display: inline;">
                                            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                            <button type="submit" class="btn btn-sm btn-outline text-success" title="Restore"><i class="bx bx-refresh"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Award Single Modal -->
<div id="singleModal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="width: 100%; max-width: 500px; margin: 1rem;">
        <h3 style="margin-top: 0; margin-bottom: 1rem;"><i class="bx bx-award"></i> Award Student Certificate</h3>
        <form action="<?= url('/certificates') ?>" method="POST">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label class="form-label">Student *</label>
                <select name="user_id" class="form-control" required>
                    <option value="">Select Student...</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= e($s['name']) ?> (<?= e($s['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Course *</label>
                <select name="course_id" class="form-control" required>
                    <option value="">Select Course...</option>
                    <?php foreach ($courses as $co): ?>
                        <option value="<?= $co['id'] ?>"><?= e($co['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Batch (Optional)</label>
                <select name="batch_id" class="form-control">
                    <option value="">No Specific Batch</option>
                    <?php foreach ($batches as $ba): ?>
                        <option value="<?= $ba['id'] ?>"><?= e($ba['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('singleModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bx bx-check"></i> Issue Certificate</button>
            </div>
        </form>
    </div>
</div>

<!-- Issue Batch-Wise Modal -->
<div id="batchModal" class="modal-backdrop" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="width: 100%; max-width: 500px; margin: 1rem;">
        <h3 style="margin-top: 0; margin-bottom: 1rem;"><i class="bx bx-group"></i> Issue Batch-Wise Certificates</h3>
        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;">
            Automatically generates and awards verified certificates to all active enrolled students in the selected batch.
        </p>
        <form action="<?= url('/certificates/issue-batch') ?>" method="POST">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label class="form-label">Select Target Batch *</label>
                <select name="batch_id" class="form-control" required>
                    <option value="">Choose Batch...</option>
                    <?php foreach ($batches as $ba): ?>
                        <option value="<?= $ba['id'] ?>"><?= e($ba['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Select Course Completed *</label>
                <select name="course_id" class="form-control" required>
                    <option value="">Choose Course...</option>
                    <?php foreach ($courses as $co): ?>
                        <option value="<?= $co['id'] ?>"><?= e($co['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-top: 1.5rem;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('batchModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" onclick="return confirm('Generate certificates for all active batch students?');"><i class="bx bx-send"></i> Issue to Batch</button>
            </div>
        </form>
    </div>
</div>
