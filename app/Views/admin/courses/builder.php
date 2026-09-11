<div class="page-header">
    <div>
        <div class="breadcrumb">
            <a href="<?= url('/admin/courses') ?>">Courses</a>
            <i class="bx bx-chevron-right"></i>
            <span><?= e($course['title']) ?></span>
        </div>
        <h1 class="page-title"><?= e($course['title']) ?> <code>(<?= e($course['code']) ?>)</code></h1>
        <p class="page-subtitle"><?= e($course['description'] ?: 'Curriculum builder and lesson structuring.') ?></p>
    </div>
    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
        <button type="button" class="btn btn-primary btn-sm" data-modal-target="#add-unit-modal">
            <i class="bx bx-folder-plus"></i> Add Unit
        </button>
        <a href="<?= url('/admin/courses/' . $course['id'] . '/edit') ?>" class="btn btn-outline btn-sm"><i class="bx bx-cog"></i> Settings</a>
        <form action="<?= url('/admin/courses/' . $course['id'] . '/duplicate') ?>" method="POST" style="display:inline;">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline btn-sm" title="Clone Course"><i class="bx bx-copy"></i> Clone</button>
        </form>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 300px; gap: 1.5rem; align-items: start;">
    <!-- Main Curriculum Tree -->
    <div>
        <?php if (empty($units)): ?>
            <div class="card" style="text-align: center; padding: 3rem 1.5rem;">
                <i class="bx bx-folder-open" style="font-size: 3.5rem; color: var(--text-subtle); margin-bottom: 0.75rem;"></i>
                <h3 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">No Units in this Course Yet</h3>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;">Create your first unit module to begin structuring lessons and assessments.</p>
                <button type="button" class="btn btn-primary" data-modal-target="#add-unit-modal">
                    <i class="bx bx-folder-plus"></i> Create First Unit
                </button>
            </div>
        <?php else: ?>
            <?php foreach ($units as $index => $u): ?>
                <div class="card" style="border-left: 4px solid var(--primary); margin-bottom: 1.5rem;">
                    <!-- Unit Header -->
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem; border-bottom: 1px solid var(--border); padding-bottom: 0.85rem; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 0.65rem;">
                            <span class="badge badge-primary">Unit <?= $index + 1 ?></span>
                            <h2 style="font-size: 1.15rem; font-weight: 700; margin: 0;"><?= e($u['title']) ?></h2>
                        </div>
                        <div style="display: flex; gap: 0.35rem;">
                            <a href="<?= url('/admin/courses/' . $course['id'] . '/units/' . $u['id'] . '/lessons/create') ?>" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus"></i> Add Lesson
                            </a>
                            <a href="<?= url('/admin/assessments/create?course_id=' . $course['id'] . '&unit_id=' . $u['id']) ?>" class="btn btn-outline btn-sm">
                                <i class="bx bx-check-square"></i> Add Quiz
                            </a>
                            <form action="<?= url('/admin/courses/' . $course['id'] . '/units/' . $u['id'] . '/delete') ?>" method="POST" style="display:inline;" data-confirm="Delete this unit and all its lessons?">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger);"><i class="bx bx-trash"></i></button>
                            </form>
                        </div>
                    </div>

                    <?php if ($u['description']): ?>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem;"><?= e($u['description']) ?></p>
                    <?php endif; ?>

                    <!-- Lessons in Unit -->
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <?php if (empty($u['lessons']) && empty($u['assessments'])): ?>
                            <div style="text-align: center; color: var(--text-muted); padding: 1.25rem; background: var(--bg); border-radius: var(--radius-md); font-size: 0.88rem;">
                                No lessons created in this unit yet. Click "+ Add Lesson" above.
                            </div>
                        <?php else: ?>
                            <!-- List Lessons -->
                            <?php foreach ($u['lessons'] as $l): ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 0.85rem; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-md);">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <?php 
                                            $icon = match($l['lesson_type']) {
                                                'video' => 'bxs-video text-danger',
                                                'pdf' => 'bxs-file-pdf text-danger',
                                                'ppt' => 'bxs-slideshow text-warning',
                                                default => 'bx-file-blank'
                                            };
                                        ?>
                                        <i class="bx <?= $icon ?>" style="font-size: 1.3rem;"></i>
                                        <div>
                                            <a href="<?= url('/admin/lessons/' . $l['id']) ?>" style="font-weight: 600; color: var(--text);"><?= e($l['title']) ?></a>
                                            <span style="font-size: 0.75rem; color: var(--text-muted); margin-left: 0.5rem;">
                                                <i class="bx bx-time"></i> <?= $l['duration_minutes'] ?> mins
                                            </span>
                                            <?php if ($l['source_type']): ?>
                                                <span class="badge badge-info" style="font-size: 0.68rem; margin-left: 0.35rem;"><?= strtoupper($l['source_type']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 0.35rem;">
                                        <a href="<?= url('/admin/lessons/' . $l['id']) ?>" class="btn btn-outline btn-sm"><i class="bx bx-show"></i> View</a>
                                        <a href="<?= url('/admin/lessons/' . $l['id'] . '/edit') ?>" class="btn btn-outline btn-sm"><i class="bx bx-edit"></i> Edit</a>
                                        <form action="<?= url('/admin/lessons/' . $l['id'] . '/delete') ?>" method="POST" style="display:inline;" data-confirm="Delete this lesson?">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger);"><i class="bx bx-trash"></i></button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- List Unit Assessments -->
                            <?php foreach ($u['assessments'] as $a): ?>
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.65rem 0.85rem; background: var(--warning-light); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: var(--radius-md);">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <i class="bx bx-check-shield" style="font-size: 1.3rem; color: var(--warning);"></i>
                                        <div>
                                            <strong style="color: var(--text);"><?= e($a['title']) ?></strong>
                                            <span class="badge badge-warning" style="font-size: 0.7rem; margin-left: 0.5rem;">Unit Quiz</span>
                                            <span style="font-size: 0.78rem; color: var(--text-muted); margin-left: 0.5rem;"><?= $a['time_limit_minutes'] ?> mins &bull; <?= $a['marks'] ?> marks</span>
                                        </div>
                                    </div>
                                    <div>
                                        <a href="<?= url('/admin/assessments/' . $a['id']) ?>" class="btn btn-outline btn-sm"><i class="bx bx-edit"></i> Manage Quiz</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Final Course Assessment Section -->
        <div class="card" style="border-left: 4px solid var(--accent); margin-top: 2rem;">
            <div class="card-header">
                <div class="card-title"><i class="bx bx-award"></i> Comprehensive Final Assessment</div>
                <a href="<?= url('/admin/assessments/create?course_id=' . $course['id']) ?>" class="btn btn-outline btn-sm">
                    <i class="bx bx-plus"></i> Set Final Exam
                </a>
            </div>
            <div>
                <?php if (empty($finalAssessments)): ?>
                    <p style="font-size: 0.88rem; color: var(--text-muted);">No course-level final exam added yet.</p>
                <?php else: ?>
                    <?php foreach ($finalAssessments as $fa): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0; border-bottom: 1px solid var(--border);">
                            <div>
                                <strong><?= e($fa['title']) ?></strong>
                                <span class="badge badge-warning">Final Exam</span>
                                <div style="font-size: 0.8rem; color: var(--text-muted);"><?= $fa['time_limit_minutes'] ?> mins &bull; Passing: <?= $fa['passing_score'] ?>%</div>
                            </div>
                            <a href="<?= url('/admin/assessments/' . $fa['id']) ?>" class="btn btn-outline btn-sm"><i class="bx bx-edit"></i> Manage</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar Info & Faculty Assignment -->
    <div>
        <div class="card">
            <div class="card-header">
                <div class="card-title" style="font-size: 1rem;"><i class="bx bx-user-pin"></i> Assigned Faculty</div>
                <button type="button" class="btn btn-outline btn-sm" data-modal-target="#assign-teacher-modal"><i class="bx bx-plus"></i></button>
            </div>
            <div>
                <?php if (empty($teachers)): ?>
                    <p style="font-size: 0.85rem; color: var(--text-muted);">No faculty instructors assigned to this course.</p>
                <?php else: ?>
                    <?php foreach ($teachers as $t): ?>
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--border);">
                            <div style="font-size: 0.88rem;">
                                <strong><?= e($t['name']) ?></strong>
                                <div style="font-size: 0.78rem; color: var(--text-muted);"><?= e($t['email']) ?></div>
                            </div>
                            <form action="<?= url('/admin/courses/' . $course['id'] . '/teachers/' . $t['id'] . '/remove') ?>" method="POST" style="display:inline;">
                                <?= csrf_field() ?>
                                <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger); padding: 2px 6px;"><i class="bx bx-x"></i></button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title" style="font-size: 1rem;"><i class="bx bx-info-circle"></i> Course Meta</div>
            </div>
            <div style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 0.65rem;">
                <div>
                    <span style="color: var(--text-muted); display: block;">Status</span>
                    <span class="badge <?= $course['status'] === 'published' ? 'badge-success' : 'badge-neutral' ?>"><?= ucfirst($course['status']) ?></span>
                </div>
                <div>
                    <span style="color: var(--text-muted); display: block;">Start Date</span>
                    <strong><?= e($course['start_date'] ?? 'Immediate') ?></strong>
                </div>
                <div>
                    <span style="color: var(--text-muted); display: block;">End Date</span>
                    <strong><?= e($course['end_date'] ?? 'Self-paced') ?></strong>
                </div>
                <div>
                    <span style="color: var(--text-muted); display: block;">Completion Rule</span>
                    <code><?= e($course['completion_rules']) ?></code>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Unit -->
<div class="modal-overlay" id="add-unit-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Create Course Unit</h3>
            <button type="button" data-close-modal style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <form action="<?= url('/admin/courses/' . $course['id'] . '/units') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Unit Title *</label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Unit 1: Introduction to Web Architecture">
                </div>
                <div class="form-group">
                    <label class="form-label">Unit Summary</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief outline of concepts covered in this unit..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Create Unit</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Assign Teacher -->
<div class="modal-overlay" id="assign-teacher-modal">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3 class="modal-title">Assign Instructor to Course</h3>
            <button type="button" data-close-modal style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
        </div>
        <form action="<?= url('/admin/courses/' . $course['id'] . '/teachers') ?>" method="POST">
            <?= csrf_field() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Select Faculty Member</label>
                    <select name="teacher_id" class="form-control" required>
                        <option value="">-- Choose Faculty --</option>
                        <?php foreach ($availableTeachers as $at): ?>
                            <option value="<?= $at['id'] ?>"><?= e($at['name']) ?> (<?= e($at['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" data-close-modal>Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bx bx-plus"></i> Assign</button>
            </div>
        </form>
    </div>
</div>
