<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bx bx-wrench"></i> Platform Maintenance Mode</h1>
        <p class="page-subtitle">Temporarily pause student and teacher access while performing system updates or maintenance.</p>
    </div>
</div>

<div class="card" style="max-width: 700px; margin: 0 auto;">
    <div style="text-align: center; padding: 2rem 1rem;">
        <?php if ($isMaintenance): ?>
            <div style="width: 80px; height: 80px; border-radius: 50%; background: #fee2e2; color: #ef4444; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem auto; font-size: 2.5rem;">
                <i class="bx bx-error-alt"></i>
            </div>
            <h2 style="color: #ef4444; margin-bottom: 0.5rem;">Maintenance Mode is Currently ACTIVE</h2>
            <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 2rem auto; line-height: 1.6;">
                Students and teachers will see the 503 Maintenance Screen when visiting the portal. Administrators continue to have unrestricted access to the administration dashboard.
            </p>
        <?php else: ?>
            <div style="width: 80px; height: 80px; border-radius: 50%; background: #dcfce7; color: #22c55e; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem auto; font-size: 2.5rem;">
                <i class="bx bx-check-shield"></i>
            </div>
            <h2 style="color: #22c55e; margin-bottom: 0.5rem;">LMS is Fully Operational</h2>
            <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto 2rem auto; line-height: 1.6;">
                The LMS portal is active. Students and teachers can access courses, take exams, and submit assignments normally.
            </p>
        <?php endif; ?>

        <form action="<?= url('/settings/maintenance/toggle') ?>" method="POST">
            <input type="hidden" name="_token" value="<?= csrf_token() ?>">
            <button type="submit" class="btn <?= $isMaintenance ? 'btn-success' : 'btn-danger' ?> btn-lg" onclick="return confirm('<?= $isMaintenance ? 'Restore public LMS access for students and teachers?' : 'Enable maintenance mode? Students and teachers will be temporarily blocked from LMS content.' ?>');">
                <i class="bx <?= $isMaintenance ? 'bx-power-off' : 'bx-wrench' ?>"></i>
                <?= $isMaintenance ? 'Disable Maintenance Mode' : 'Enable Maintenance Mode' ?>
            </button>
        </form>
    </div>
</div>
