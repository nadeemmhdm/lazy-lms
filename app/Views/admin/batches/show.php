<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= url('/admin/batches') ?>">Batches</a>
            <i class="bx bx-chevron-right"></i>
            <span><?= e($batch['name']) ?></span>
        </div>
        <h1 class="page-title"><?= e($batch['name']) ?> <code>(<?= e($batch['code']) ?>)</code></h1>
        <p class="page-subtitle"><?= e($batch['description'] ?: 'No description provided.') ?></p>
    </div>
    <div style="display: flex; gap: 0.5rem;">
        <a href="<?= url('/admin/batches/' . $batch['id'] . '/export') ?>" class="btn btn-outline btn-sm"><i class="bx bx-download"></i> Export Roster CSV</a>
        <a href="<?= url('/admin/batches/' . $batch['id'] . '/edit') ?>" class="btn btn-outline btn-sm"><i class="bx bx-edit"></i> Edit</a>
    </div>
</div>

<div class="tabs-container">
    <div style="display: flex; gap: 0.5rem; border-bottom: 1px solid var(--border); margin-bottom: 1.5rem;">
        <button class="btn btn-sm tab-nav-btn active" data-tab="students"><i class="bx bxs-user-detail"></i> Enrolled Students (<?= count($students) ?>)</button>
        <button class="btn btn-sm tab-nav-btn" data-tab="courses"><i class="bx bx-book-bookmark"></i> Assigned Courses (<?= count($courses) ?>)</button>
        <button class="btn btn-sm tab-nav-btn" data-tab="teachers"><i class="bx bx-user-pin"></i> Faculty Teachers (<?= count($teachers) ?>)</button>
    </div>

    <!-- Students Tab -->
    <div class="tab-pane active" data-tab-content="students">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.1rem; font-weight: 600;">Student Roster</h3>
            <button type="button" class="btn btn-primary btn-sm" data-modal-target="#add-student-modal">
                <i class="bx bx-user-plus"></i> Add Student to Batch
            </button>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Email</th>
                            <th>Enrolled Date</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No students in this batch yet. Click "Add Student" above.</td></tr>
                        <?php else: ?>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td><strong><a href="<?= url('/admin/students/' . $s['id']) ?>"><?= e($s['name']) ?></a></strong></td>
                                    <td><code><?= e($s['student_id'] ?: 'N/A') ?></code></td>
                                    <td><?= e($s['email']) ?></td>
                                    <td style="font-size: 0.85rem; color: var(--text-muted);"><?= e($s['enrolled_at']) ?></td>
                                    <td style="text-align: right;">
                                        <form action="<?= url('/admin/batches/' . $batch['id'] . '/students/' . $s['id'] . '/remove') ?>" method="POST" style="display:inline;" data-confirm="Remove this student from the batch?">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger);"><i class="bx bx-trash"></i> Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Courses Tab -->
    <div class="tab-pane" data-tab-content="courses">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.1rem; font-weight: 600;">Batch Assigned Courses</h3>
            <button type="button" class="btn btn-primary btn-sm" data-modal-target="#add-course-modal">
                <i class="bx bx-plus"></i> Assign Course to Batch
            </button>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Course Title</th>
                            <th>Course Code</th>
                            <th>Status</th>
                            <th>Assigned Date</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($courses)): ?>
                            <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No courses assigned to this batch yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($courses as $c): ?>
                                <tr>
                                    <td><strong><a href="<?= url('/admin/courses/' . $c['id']) ?>"><?= e($c['title']) ?></a></strong></td>
                                    <td><code><?= e($c['code']) ?></code></td>
                                    <td><span class="badge badge-success"><?= ucfirst($c['status']) ?></span></td>
                                    <td style="font-size: 0.85rem; color: var(--text-muted);"><?= e($c['assigned_at']) ?></td>
                                    <td style="text-align: right;">
                                        <form action="<?= url('/admin/batches/' . $batch['id'] . '/courses/' . $c['id'] . '/remove') ?>" method="POST" style="display:inline;" data-confirm="Remove this course from the batch?">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger);"><i class="bx bx-trash"></i> Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Teachers Tab -->
    <div class="tab-pane" data-tab-content="teachers">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <h3 style="font-size: 1.1rem; font-weight: 600;">Faculty Teachers Assigned</h3>
            <button type="button" class="btn btn-primary btn-sm" data-modal-target="#add-teacher-modal">
                <i class="bx bx-user-plus"></i> Assign Faculty
            </button>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Teacher Name</th>
                            <th>Email</th>
                            <th>Assigned Date</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teachers)): ?>
                            <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 2rem;">No teachers assigned to this batch yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($teachers as $t): ?>
                                <tr>
                                    <td><strong><?= e($t['name']) ?></strong></td>
                                    <td><?= e($t['email']) ?></td>
                                    <td style="font-size: 0.85rem; color: var(--text-muted);"><?= e($t['assigned_at']) ?></td>
                                    <td style="text-align: right;">
                                        <form action="<?= url('/admin/batches/' . $batch['id'] . '/teachers/' . $t['id'] . '/remove') ?>" method="POST" style="display:inline;" data-confirm="Remove this teacher from the batch?">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger);"><i class="bx bx-trash"></i> Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Student -->
<div class="modal-overlay" id="add-student-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Enroll Student in Batch</h3>
            <button type="button" data-close-modal style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <form action="<?= url('/admin/batches/' . $batch['id'] . '/students') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Select Student</label>
                    <select name="student_id" class="form-control" required>
                        <option value="">-- Choose Student --</option>
                        <?php foreach ($availableStudents as $as): ?>
                            <option value="<?= $as['id'] ?>"><?= e($as['name']) ?> (<?= e($as['student_id'] ?: $as['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bx bx-plus"></i> Enroll Student</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Course -->
<div class="modal-overlay" id="add-course-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Assign Course to Batch</h3>
            <button type="button" data-close-modal style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <form action="<?= url('/admin/batches/' . $batch['id'] . '/courses') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Select Course</label>
                    <select name="course_id" class="form-control" required>
                        <option value="">-- Choose Course --</option>
                        <?php foreach ($availableCourses as $ac): ?>
                            <option value="<?= $ac['id'] ?>"><?= e($ac['title']) ?> (<?= e($ac['code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bx bx-plus"></i> Assign Course</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Add Teacher -->
<div class="modal-overlay" id="add-teacher-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Assign Faculty to Batch</h3>
            <button type="button" data-close-modal style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <form action="<?= url('/admin/batches/' . $batch['id'] . '/teachers') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Select Faculty</label>
                    <select name="teacher_id" class="form-control" required>
                        <option value="">-- Choose Teacher --</option>
                        <?php foreach ($availableTeachers as $at): ?>
                            <option value="<?= $at['id'] ?>"><?= e($at['name']) ?> (<?= e($at['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bx bx-plus"></i> Assign Teacher</button>
            </div>
        </form>
    </div>
</div>
