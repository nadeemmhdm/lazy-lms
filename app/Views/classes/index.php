<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bx bx-video"></i> Scheduled Live Classes</h1>
        <p class="page-subtitle">Interactive classes, live lectures, and video workshops (Zoom, Google Meet, Microsoft Teams).</p>
    </div>
    <div>
        <?php if ($user['role'] !== 'student'): ?>
            <a href="<?= url('/classes/create') ?>" class="btn btn-primary"><i class="bx bx-plus"></i> Schedule Class</a>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Class Title</th>
                    <th>Course & Batch</th>
                    <th>Teacher</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th style="text-align: right;">Meeting Link</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($classes)): ?>
                    <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2.5rem;">No scheduled live classes found.</td></tr>
                <?php else: ?>
                    <?php foreach ($classes as $c): ?>
                        <tr>
                            <td>
                                <strong><?= e($c['title']) ?></strong>
                                <?php if (!empty($c['description'])): ?>
                                    <div style="font-size: 0.8rem; color: var(--text-muted);"><?= e($c['description']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= e($c['course_title']) ?><br>
                                <span style="font-size: 0.8rem; color: var(--text-muted);"><?= e($c['batch_name']) ?></span>
                            </td>
                            <td><?= e($c['teacher_name'] ?? 'Instructor') ?></td>
                            <td style="font-size: 0.85rem;">
                                <strong><?= date('d M Y', strtotime($c['class_date'])) ?></strong><br>
                                <span style="color: var(--text-muted);"><?= date('h:i A', strtotime($c['start_time'])) ?> - <?= date('h:i A', strtotime($c['end_time'])) ?></span>
                            </td>
                            <td>
                                <span class="badge <?= $c['status'] === 'scheduled' ? 'badge-primary' : ($c['status'] === 'completed' ? 'badge-success' : 'badge-danger') ?>">
                                    <?= ucfirst($c['status']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <?php if (!empty($c['meeting_url']) && $c['status'] === 'scheduled'): ?>
                                    <a href="<?= e($c['meeting_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm">
                                        <i class="bx bx-video-recording"></i> Join Live Meeting
                                    </a>
                                <?php elseif ($c['status'] === 'cancelled'): ?>
                                    <span style="color: var(--danger); font-size: 0.85rem;">Cancelled</span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 0.85rem;">Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
