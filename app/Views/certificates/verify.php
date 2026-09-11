<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification</title>
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <link rel="stylesheet" href="<?= asset('vendor/boxicons/boxicons.min.css') ?>">
</head>
<body style="background: var(--bg-body); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem;">

<div class="card" style="max-width: 550px; width: 100%; text-align: center; padding: 3rem 2rem;">
    <?php if ($certificate): ?>
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 70px; height: 70px; border-radius: 50%; background: var(--success-light); color: var(--success); font-size: 2.5rem; margin-bottom: 1.5rem;">
            <i class="bx bx-check-shield"></i>
        </div>
        <h2 style="margin: 0 0 0.5rem 0; color: var(--success);">Valid Certificate</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 2rem;">
            This certificate has been officially verified by the institution registrar.
        </p>

        <div style="background: var(--bg-hover); border-radius: var(--radius-md); padding: 1.5rem; text-align: left; display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.95rem;">
            <div>
                <strong style="color: var(--text-muted); display: block; font-size: 0.8rem;">RECIPIENT NAME</strong>
                <span style="font-weight: 600; font-size: 1.1rem;"><?= e($certificate['student_name']) ?></span>
            </div>
            <div>
                <strong style="color: var(--text-muted); display: block; font-size: 0.8rem;">COURSE GRADUATED</strong>
                <span><?= e($certificate['course_title']) ?></span>
            </div>
            <div>
                <strong style="color: var(--text-muted); display: block; font-size: 0.8rem;">DATE OF ISSUE</strong>
                <span><?= e($certificate['issue_date']) ?></span>
            </div>
            <div>
                <strong style="color: var(--text-muted); display: block; font-size: 0.8rem;">VERIFICATION CODE</strong>
                <code><?= e($certificate['certificate_code']) ?></code>
            </div>
        </div>
    <?php else: ?>
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 70px; height: 70px; border-radius: 50%; background: var(--danger-light); color: var(--danger); font-size: 2.5rem; margin-bottom: 1.5rem;">
            <i class="bx bx-x-circle"></i>
        </div>
        <h2 style="margin: 0 0 0.5rem 0; color: var(--danger);">Unrecognized Certificate</h2>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem;">
            We could not verify the certificate code <code><?= e($code) ?></code> in our records.
        </p>
    <?php endif; ?>

    <div style="margin-top: 2rem;">
        <a href="<?= url('/login') ?>" class="btn btn-outline"><i class="bx bx-log-in"></i> Go to LMS Login</a>
    </div>
</div>

</body>
</html>
