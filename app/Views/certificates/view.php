<div style="max-width: 900px; margin: 0 auto; padding: 2rem 0;">
    <div style="display: flex; justify-content: flex-end; gap: 0.5rem; margin-bottom: 1rem;" class="no-print">
        <button type="button" class="btn btn-outline" onclick="window.print()"><i class="bx bx-printer"></i> Print / Save as PDF</button>
        <a href="<?= url('/certificates') ?>" class="btn btn-primary"><i class="bx bx-arrow-back"></i> Back</a>
    </div>

    <!-- Certificate Border Design -->
    <div class="card" style="border: 8px double var(--primary); padding: 4rem 3rem; text-align: center; background: var(--bg-card); position: relative; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
        <div style="font-size: 3rem; color: var(--primary); margin-bottom: 0.5rem;">
            <i class="bx bxs-award"></i>
        </div>

        <h1 style="font-family: Georgia, serif; font-size: 2.75rem; letter-spacing: 2px; text-transform: uppercase; margin: 0.5rem 0; color: var(--text-color);">
            Certificate of Completion
        </h1>

        <p style="font-size: 1.1rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 2rem;">
            This is proudly presented to
        </p>

        <h2 style="font-family: Georgia, serif; font-size: 2.5rem; margin: 0; color: var(--primary); border-bottom: 2px solid var(--border-color); display: inline-block; padding-bottom: 0.5rem; min-width: 350px;">
            <?= e($certificate['student_name']) ?>
        </h2>

        <p style="font-size: 1.1rem; line-height: 1.8; color: var(--text-color); max-width: 650px; margin: 2rem auto;">
            For successfully completing all mandatory units, lessons, assignments, and assessments for the curriculum course:
        </p>

        <h3 style="font-size: 1.75rem; margin: 0.5rem 0 2rem; color: var(--text-color);">
            <?= e($certificate['course_title']) ?> <span style="font-size: 1rem; color: var(--text-muted);">(<?= e($certificate['course_code']) ?>)</span>
        </h3>

        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 3rem; padding: 0 2rem;">
            <div style="text-align: left;">
                <div style="font-size: 0.85rem; color: var(--text-muted);">Issue Date:</div>
                <div style="font-weight: 600; font-size: 1rem;"><?= e($certificate['issue_date']) ?></div>
                <div style="font-size: 0.8rem; color: var(--text-muted); margin-top: 0.25rem;">Verification Code: <code><?= e($certificate['certificate_code']) ?></code></div>
            </div>

            <!-- Signature block -->
            <div style="text-align: right;">
                <div style="border-bottom: 1px solid var(--text-color); width: 200px; margin-bottom: 0.5rem;"></div>
                <div style="font-weight: 600; font-size: 1rem;">Academic Dean / Director</div>
                <div style="font-size: 0.8rem; color: var(--text-muted);">Authorized Signature</div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .app-sidebar, .app-topbar, .no-print { display: none !important; }
    .app-main { margin: 0 !important; padding: 0 !important; }
    body { background: #fff !important; }
}
</style>
