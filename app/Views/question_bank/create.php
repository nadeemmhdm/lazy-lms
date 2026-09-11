<div class="page-header">
    <div>
        <h1 class="page-title">Create Question</h1>
        <p class="page-subtitle">Add a single choice, multiple answer, true/false, short answer, or numeric question.</p>
    </div>
    <div>
        <a href="<?= url('/question-bank') ?>" class="btn btn-outline"><i class="bx bx-arrow-back"></i> Back to Bank</a>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <form action="<?= url('/question-bank') ?>" method="POST" id="question-form">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label class="form-label">Question Text *</label>
            <textarea name="question" class="form-control" rows="3" required placeholder="Type the question statement..."></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Question Type</label>
                <select name="type" id="question-type" class="form-control" onchange="switchQuestionType(this.value)">
                    <option value="single_choice">Single Choice (Radio)</option>
                    <option value="multiple_choice">Multiple Choice (Checkboxes)</option>
                    <option value="true_false">True / False</option>
                    <option value="short_answer">Short Text Answer</option>
                    <option value="numeric">Numeric Answer</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Marks / Weight</label>
                <input type="number" step="0.5" name="marks" class="form-control" value="1.0" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Category</label>
                <input type="text" name="category" class="form-control" value="General" placeholder="e.g. Mathematics, Science">
            </div>

            <div class="form-group">
                <label class="form-label">Difficulty</label>
                <select name="difficulty" class="form-control">
                    <option value="easy">Easy</option>
                    <option value="medium" selected>Medium</option>
                    <option value="hard">Hard</option>
                </select>
            </div>
        </div>

        <!-- Options Container for Choice Questions -->
        <div id="options-section" style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem;">
                <label class="form-label" style="margin: 0;">Answer Options (Select correct option radio/checkbox)</label>
                <button type="button" class="btn btn-outline btn-sm" onclick="addOption()"><i class="bx bx-plus"></i> Add Option</button>
            </div>

            <div id="options-list">
                <div class="option-row" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                    <input type="radio" name="correct_answer" value="0" checked>
                    <input type="text" name="options[0]" class="form-control" placeholder="Option 1" required>
                </div>
                <div class="option-row" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                    <input type="radio" name="correct_answer" value="1">
                    <input type="text" name="options[1]" class="form-control" placeholder="Option 2" required>
                </div>
            </div>
        </div>

        <!-- Direct Answer Container for Numeric or Short Answer -->
        <div id="direct-answer-section" style="display: none; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
            <div class="form-group">
                <label class="form-label">Accepted Correct Answer *</label>
                <input type="text" name="correct_answer" id="direct-answer-input" class="form-control" placeholder="e.g. 10.5 or 'True' or correct keyword">
                <small class="form-text text-muted">For numeric questions, enter valid numbers like 10, -2, 0.75. For short answer, case-insensitive match will be applied.</small>
            </div>
        </div>

        <div class="form-group" style="margin-top: 1.5rem;">
            <label class="form-label">Explanation / Solution Note (Optional)</label>
            <textarea name="explanation" class="form-control" rows="2" placeholder="Explanation shown to students during review..."></textarea>
        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
            <a href="<?= url('/question-bank') ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save Question</button>
        </div>
    </form>
</div>

<script>
let optCount = 2;
function switchQuestionType(type) {
    const optSec = document.getElementById('options-section');
    const directSec = document.getElementById('direct-answer-section');
    const list = document.getElementById('options-list');

    if (type === 'single_choice') {
        optSec.style.display = 'block';
        directSec.style.display = 'none';
        list.innerHTML = `
            <div class="option-row" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <input type="radio" name="correct_answer" value="0" checked>
                <input type="text" name="options[0]" class="form-control" placeholder="Option 1" required>
            </div>
            <div class="option-row" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <input type="radio" name="correct_answer" value="1">
                <input type="text" name="options[1]" class="form-control" placeholder="Option 2" required>
            </div>
        `;
        optCount = 2;
    } else if (type === 'multiple_choice') {
        optSec.style.display = 'block';
        directSec.style.display = 'none';
        list.innerHTML = `
            <div class="option-row" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <input type="checkbox" name="correct_answer[]" value="0" checked>
                <input type="text" name="options[0]" class="form-control" placeholder="Option 1" required>
            </div>
            <div class="option-row" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <input type="checkbox" name="correct_answer[]" value="1">
                <input type="text" name="options[1]" class="form-control" placeholder="Option 2" required>
            </div>
        `;
        optCount = 2;
    } else if (type === 'true_false') {
        optSec.style.display = 'block';
        directSec.style.display = 'none';
        list.innerHTML = `
            <div class="option-row" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <input type="radio" name="correct_answer" value="0" checked>
                <input type="text" name="options[0]" class="form-control" value="True" readonly>
            </div>
            <div class="option-row" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                <input type="radio" name="correct_answer" value="1">
                <input type="text" name="options[1]" class="form-control" value="False" readonly>
            </div>
        `;
    } else {
        optSec.style.display = 'none';
        directSec.style.display = 'block';
    }
}

function addOption() {
    const list = document.getElementById('options-list');
    const type = document.getElementById('question-type').value;
    const inputType = type === 'multiple_choice' ? 'checkbox' : 'radio';
    const inputName = type === 'multiple_choice' ? 'correct_answer[]' : 'correct_answer';

    const div = document.createElement('div');
    div.className = 'option-row';
    div.style = 'display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;';
    div.innerHTML = `
        <input type="${inputType}" name="${inputName}" value="${optCount}">
        <input type="text" name="options[${optCount}]" class="form-control" placeholder="Option ${optCount + 1}" required>
    `;
    list.appendChild(div);
    optCount++;
}
</script>
