<div class="page-header">
    <div>
        <h1 class="page-title">Reports & Academic Analytics</h1>
        <p class="page-subtitle">Export institutional records, graduation metrics, and course progress datasets.</p>
    </div>
</div>

<div class="stat-grid" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="bx bxs-user-detail"></i></div>
        <div>
            <div class="stat-value"><?= number_format($metrics['students']) ?></div>
            <div class="stat-label">Total Enrolled Students</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="bx bx-group"></i></div>
        <div>
            <div class="stat-value"><?= number_format($metrics['batches']) ?></div>
            <div class="stat-label">Active Academic Batches</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="bx bx-book-bookmark"></i></div>
        <div>
            <div class="stat-value"><?= number_format($metrics['courses']) ?></div>
            <div class="stat-label">Published Courses</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="bx bx-certification"></i></div>
        <div>
            <div class="stat-value"><?= number_format($metrics['certificates']) ?></div>
            <div class="stat-label">Issued Certificates</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem;">
    <!-- Student Progress Report Card -->
    <div class="card">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
            <div style="width: 48px; height: 48px; border-radius: var(--radius-sm); background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="bx bxs-user-account"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.1rem;">Student Academic Census</h3>
                <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Enrollment dates, active batches, and completed courses.</p>
            </div>
        </div>
        <p style="font-size: 0.9rem; color: var(--text-color); margin-bottom: 1.5rem;">
            Exports complete roster of all students, contact information, batch assignments, registration dates, and last login timestamps.
        </p>
        <a href="<?= url('/reports/students/export') ?>" class="btn btn-primary"><i class="bx bx-download"></i> Export Students (CSV)</a>
    </div>

    <!-- Course Delivery Report Card -->
    <div class="card">
        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
            <div style="width: 48px; height: 48px; border-radius: var(--radius-sm); background: var(--success-light); color: var(--success); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                <i class="bx bx-book-open"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.1rem;">Course Completion Metrics</h3>
                <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Curriculum volume, batches assigned, and graduations.</p>
            </div>
        </div>
        <p style="font-size: 0.9rem; color: var(--text-color); margin-bottom: 1.5rem;">
            Exports all active courses, unit counts, total batch deliveries, and count of students who have completed 100% of course activities.
        </p>
        <a href="<?= url('/reports/courses/export') ?>" class="btn btn-outline"><i class="bx bx-download"></i> Export Courses (CSV)</a>
    </div>
</div>
