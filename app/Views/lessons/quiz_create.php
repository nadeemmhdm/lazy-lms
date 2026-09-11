<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bx bx-help-circle"></i> Create Lesson Quiz</h1>
        <p class="page-subtitle">Attach an MCQ quiz to lesson: <strong><?= e($lesson['title']) ?></strong> (<?= e($lesson['course_title']) ?>)</p>
    </div>
    <div>
        <a href="<?= url('/lessons/' . ($lesson['public_id'] ?? $lesson['id'])) ?>" class="btn btn-outline"><i class="bx bx-arrow-back"></i> Back to Lesson</a>
    </div>
</div>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <form action="<?= url('/lessons/' . ($lesson['public_id'] ?? $lesson['id']) . '/quiz/store') ?>" method="POST">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label class="form-label">Quiz Title *</label>
            <input type="text" name="title" class="form-control" value="Lesson Quiz: <?= e($lesson['title']) ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">Instructions / Notes</label>
            <textarea name="instructions" class="form-control" rows="3" placeholder="Explain passing criteria, attempt limits, or hints..."></textarea>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Passing Score (%) *</label>
                <input type="number" step="1" name="passing_score" class="form-control" value="60" min="1" max="100" required>
            </div>
            <div class="form-group">
                <label class="form-label">Max Attempts</label>
                <input type="number" name="max_attempts" class="form-control" value="3" min="1" max="10" required>
            </div>
            <div class="form-group">
                <label class="form-label">Cooldown (Minutes)</label>
                <input type="number" name="cooldown_minutes" class="form-control" value="0" min="0">
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                </select>
            </div>
        </div>

        <div style="display: flex; gap: 2rem; margin: 1rem 0; padding: 0.75rem 1rem; background: var(--bg-hover, #f8fafc); border-radius: 6px; border: 1px solid var(--border-color);">
            <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; font-size: 0.9rem;">
                <input type="checkbox" name="randomize_questions" value="1">
                <span>Randomize Questions Order</span>
            </label>
            <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer; font-size: 0.9rem;">
                <input type="checkbox" name="randomize_options" value="1">
                <span>Randomize Options Order</span>
            </label>
        </div>

        <div style="margin-top: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="bx bx-list-check"></i> Questions (MCQ Only)
                </h3>
                <button type="button" class="btn btn-sm btn-outline" id="addQuestionBtn">
                    <i class="bx bx-plus"></i> Add Question
                </button>
            </div>
            <div id="questionsContainer"></div>
        </div>

        <div style="margin-top: 2rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
            <a href="<?= url('/lessons/' . ($lesson['public_id'] ?? $lesson['id'])) ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save Lesson Quiz</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let qCounter = 0;
    const container = document.getElementById('questionsContainer');
    const addBtn = document.getElementById('addQuestionBtn');

    function addQuestion() {
        qCounter++;
        const id = qCounter;
        const div = document.createElement('div');
        div.className = 'card';
        div.style.marginBottom = '1rem';
        div.style.padding = '1rem';
        div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <strong>Question ${id}</strong>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <label style="font-size: 0.85rem;">Marks: </label>
                    <input type="number" step="0.5" name="questions[${id}][marks]" value="1.0" style="width: 70px;" class="form-control form-control-sm" required>
                    <button type="button" class="btn btn-sm btn-outline text-danger rm-btn" style="padding: 0.2rem 0.5rem;"><i class="bx bx-trash"></i></button>
                </div>
            </div>
            <input type="text" name="questions[${id}][text]" class="form-control" placeholder="Enter question statement..." required style="margin-bottom: 0.75rem;">
            <div style="font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">Options (select the radio for correct answer):</div>
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
        div.querySelector('.rm-btn').addEventListener('click', () => div.remove());
        container.appendChild(div);
    }

    addBtn.addEventListener('click', addQuestion);
    addQuestion(); // Start with 1 default question
});
</script>
