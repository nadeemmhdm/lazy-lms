<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bx bx-file-blank"></i> Schedule Examination</h1>
        <p class="page-subtitle">Configure examination timing, questions, and grading criteria.</p>
    </div>
    <div>
        <a href="<?= url('/exams') ?>" class="btn btn-outline"><i class="bx bx-arrow-back"></i> Back</a>
    </div>
</div>

<div class="card" style="max-width: 950px; margin: 0 auto;">
    <form action="<?= url('/exams') ?>" method="POST" id="examCreateForm">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label class="form-label">Exam Title *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Final Examination - Full Stack Development" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Course *</label>
                <select name="course_id" class="form-control" required>
                    <option value="">Select Course...</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['public_id'] ?? $c['id'] ?>"><?= e($c['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Target Batch (Optional)</label>
                <select name="batch_id" class="form-control">
                    <option value="">All Batches Enrolled in Course</option>
                    <?php foreach ($batches as $b): ?>
                        <option value="<?= $b['public_id'] ?? $b['id'] ?>"><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Start Date & Time *</label>
                <input type="datetime-local" name="start_time" class="form-control" value="<?= date('Y-m-d\TH:i') ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">End Date & Time *</label>
                <input type="datetime-local" name="end_time" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime('+7 days')) ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Duration (Minutes) *</label>
                <input type="number" name="duration_minutes" class="form-control" value="60" min="5" max="300" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Total Marks *</label>
                <input type="number" step="1" name="total_marks" class="form-control" value="100" required>
            </div>
            <div class="form-group">
                <label class="form-label">Passing Score (%) *</label>
                <input type="number" step="1" name="passing_score" class="form-control" value="40" min="1" max="100" required>
            </div>
            <div class="form-group">
                <label class="form-label">Max Attempts</label>
                <input type="number" name="max_attempts" class="form-control" value="1" min="1" max="5">
            </div>
            <div class="form-group">
                <label class="form-label">Cooldown (Mins)</label>
                <input type="number" name="cooldown_minutes" class="form-control" value="0" min="0">
            </div>
        </div>

        <div style="display: flex; gap: 2rem; margin: 1rem 0; padding: 0.75rem 1rem; background: var(--bg-hover, #f8fafc); border-radius: 6px; border: 1px solid var(--border-color); flex-wrap: wrap;">
            <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; font-size: 0.9rem;">
                <input type="checkbox" name="randomize_questions" value="1">
                <span>Randomize Questions</span>
            </label>
            <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; font-size: 0.9rem;">
                <input type="checkbox" name="randomize_options" value="1">
                <span>Randomize MCQ Options</span>
            </label>
            <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; font-size: 0.9rem;">
                <input type="checkbox" name="auto_submit" value="1" checked>
                <span>Auto-Submit on Timer Expiry</span>
            </label>
        </div>

        <div class="form-group">
            <label class="form-label">Instructions & Exam Rules</label>
            <textarea name="instructions" class="form-control" rows="4" placeholder="Explain examination rules, permitted materials, and integrity guidelines..."></textarea>
        </div>

        <!-- Question Builder -->
        <div style="margin-top: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0; font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="bx bx-list-ol"></i> Exam Questions (MCQ, Short Answer, Descriptive)
                </h3>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" class="btn btn-sm btn-outline" onclick="addQuestion('mcq')"><i class="bx bx-plus"></i> Add MCQ</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="addQuestion('short_answer')"><i class="bx bx-plus"></i> Add Short Answer</button>
                    <button type="button" class="btn btn-sm btn-outline" onclick="addQuestion('descriptive')"><i class="bx bx-plus"></i> Add Descriptive</button>
                </div>
            </div>

            <div id="questionsContainer"></div>
        </div>

        <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
            <a href="<?= url('/exams') ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Publish Examination</button>
        </div>
    </form>
</div>

<script>
let qCounter = 0;

function addQuestion(type) {
    qCounter++;
    const id = qCounter;
    const container = document.getElementById('questionsContainer');
    const div = document.createElement('div');
    div.className = 'card';
    div.style.marginBottom = '1rem';
    div.style.padding = '1rem';

    let specificHtml = '';
    if (type === 'mcq') {
        specificHtml = `
            <div style="font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">MCQ Options (check correct answer):</div>
            <div style="display: grid; gap: 0.4rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="radio" name="questions[${id}][correct]" value="0" checked>
                    <input type="text" name="questions[${id}][options][0]" class="form-control form-control-sm" placeholder="Option A" required>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="radio" name="questions[${id}][correct]" value="1">
                    <input type="text" name="questions[${id}][options][1]" class="form-control form-control-sm" placeholder="Option B" required>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="radio" name="questions[${id}][correct]" value="2">
                    <input type="text" name="questions[${id}][options][2]" class="form-control form-control-sm" placeholder="Option C">
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="radio" name="questions[${id}][correct]" value="3">
                    <input type="text" name="questions[${id}][options][3]" class="form-control form-control-sm" placeholder="Option D">
                </div>
            </div>
        `;
    } else if (type === 'short_answer') {
        specificHtml = `
            <div style="padding: 0.5rem; background: var(--bg-hover, #f8fafc); border-radius: 4px; font-size: 0.85rem; color: var(--text-muted);">
                Student enters text answer online. Graded manually by instructor.
            </div>
        `;
    } else {
        specificHtml = `
            <div style="margin-top: 0.5rem;">
                <label style="font-size: 0.85rem; font-weight: 600;">Descriptive Submission Method:</label>
                <select name="questions[${id}][desc_mode]" class="form-control form-control-sm" style="max-width: 250px; margin-top: 0.25rem;">
                    <option value="both">Write Answer OR Upload File</option>
                    <option value="write">Write Online Only</option>
                    <option value="upload">Upload File Only</option>
                </select>
            </div>
        `;
    }

    div.innerHTML = `
        <input type="hidden" name="questions[${id}][type]" value="${type}">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
            <div>
                <strong>Question ${id}</strong> 
                <span class="badge badge-outline" style="margin-left: 0.5rem; text-transform: uppercase;">${type.replace('_', ' ')}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label style="font-size: 0.85rem;">Marks: </label>
                <input type="number" step="0.5" name="questions[${id}][marks]" value="2.0" style="width: 70px;" class="form-control form-control-sm" required>
                <button type="button" class="btn btn-sm btn-outline text-danger" style="padding: 0.2rem 0.5rem;" onclick="this.closest('.card').remove()"><i class="bx bx-trash"></i></button>
            </div>
        </div>
        <input type="text" name="questions[${id}][text]" class="form-control" placeholder="Enter question statement..." required style="margin-bottom: 0.75rem;">
        ${specificHtml}
    `;

    container.appendChild(div);
}

document.addEventListener('DOMContentLoaded', () => {
    addQuestion('mcq');
});
</script>
