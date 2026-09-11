<div class="page-header">
    <div>
        <h1 class="page-title"><i class="bx bx-task"></i> Create Assignment</h1>
        <p class="page-subtitle">Publish a coursework brief, MCQ quiz, short-answer exercise, or project upload.</p>
    </div>
    <div>
        <a href="<?= url('/assignments') ?>" class="btn btn-outline"><i class="bx bx-arrow-back"></i> Back</a>
    </div>
</div>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <form action="<?= url('/assignments') ?>" method="POST" id="assignmentForm">
        <input type="hidden" name="_token" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label class="form-label">Assignment Title *</label>
            <input type="text" name="title" class="form-control" placeholder="e.g. Midterm Project: Database Design & Normalization" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Associated Course *</label>
                <select name="course_id" class="form-control" required>
                    <option value="">Select Course...</option>
                    <?php foreach ($courses as $c): ?>
                        <option value="<?= $c['public_id'] ?? $c['id'] ?>"><?= e($c['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Assignment Type *</label>
                <select name="assignment_type" id="assignmentTypeSelect" class="form-control" required>
                    <option value="descriptive">Descriptive (Written / Upload)</option>
                    <option value="short_answer">Short Answer</option>
                    <option value="mcq">MCQ Assignment (Auto-Graded)</option>
                </select>
            </div>
        </div>

        <!-- Submission method for Descriptive -->
        <div class="form-group" id="submissionModeGroup">
            <label class="form-label">Student Submission Method *</label>
            <div style="display: flex; gap: 1.5rem; margin-top: 0.5rem; flex-wrap: wrap;">
                <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer;">
                    <input type="radio" name="submission_mode" value="write">
                    <span>Write answer online</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer;">
                    <input type="radio" name="submission_mode" value="upload">
                    <span>Upload file</span>
                </label>
                <label style="display: flex; align-items: center; gap: 0.4rem; cursor: pointer;">
                    <input type="radio" name="submission_mode" value="both" checked>
                    <span>Write OR upload file</span>
                </label>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Maximum Marks *</label>
                <input type="number" step="0.5" name="max_marks" class="form-control" value="100" required>
            </div>
            <div class="form-group">
                <label class="form-label">Start Date & Time</label>
                <input type="datetime-local" name="start_date" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Due Date & Time *</label>
                <input type="datetime-local" name="due_date" class="form-control" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Max Attempts Allowed</label>
                <input type="number" name="max_attempts" class="form-control" value="1" min="1" max="10">
            </div>
            <div class="form-group">
                <label class="form-label">Cooldown Between Attempts (Hours)</label>
                <input type="number" name="attempt_cooldown_hours" class="form-control" value="0" min="0">
            </div>
            <div class="form-group">
                <label class="form-label">Late Submissions</label>
                <select name="late_allowed" class="form-control">
                    <option value="1">Allowed</option>
                    <option value="0">Not Allowed</option>
                </select>
            </div>
        </div>

        <div id="fileUploadSettings" style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label class="form-label">Allowed File Extensions</label>
                <input type="text" name="allowed_extensions" class="form-control" value="pdf,doc,docx,zip,txt" placeholder="e.g. pdf,doc,docx,zip">
                <small style="color: var(--text-muted); font-size: 0.8rem;">Executable files are strictly forbidden by LMS security policy.</small>
            </div>
            <div class="form-group">
                <label class="form-label">Max Size (MB)</label>
                <input type="number" name="max_file_size_mb" class="form-control" value="25" min="1" max="100">
            </div>
            <div class="form-group">
                <label class="form-label">Max Files</label>
                <input type="number" name="max_files" class="form-control" value="1" min="1" max="5">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Instructions & Rubric</label>
            <textarea name="instructions" class="form-control" rows="5" placeholder="Detailed submission guidelines, project questions, or grading rubric..."></textarea>
        </div>

        <!-- Dynamic MCQ Question Builder -->
        <div id="mcqBuilderSection" style="display: none; margin-top: 1.5rem; padding: 1.25rem; background: var(--bg-hover, #f8fafc); border: 1px solid var(--border-color); border-radius: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="bx bx-list-check"></i> Multiple Choice Questions (MCQ)
                </h3>
                <button type="button" class="btn btn-sm btn-outline" id="addMcqBtn">
                    <i class="bx bx-plus"></i> Add Question
                </button>
            </div>
            <div id="mcqQuestionsList"></div>
        </div>

        <div style="margin-top: 1.5rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
            <a href="<?= url('/assignments') ?>" class="btn btn-outline">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="bx bx-cloud-upload"></i> Publish Assignment</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const typeSelect = document.getElementById('assignmentTypeSelect');
    const submissionGroup = document.getElementById('submissionModeGroup');
    const fileSettings = document.getElementById('fileUploadSettings');
    const mcqSection = document.getElementById('mcqBuilderSection');
    const mcqList = document.getElementById('mcqQuestionsList');
    const addMcqBtn = document.getElementById('addMcqBtn');
    let qCounter = 0;

    function updateVisibility() {
        const val = typeSelect.value;
        if (val === 'mcq') {
            submissionGroup.style.display = 'none';
            fileSettings.style.display = 'none';
            mcqSection.style.display = 'block';
            if (mcqList.children.length === 0) addQuestion();
        } else if (val === 'short_answer') {
            submissionGroup.style.display = 'none';
            fileSettings.style.display = 'none';
            mcqSection.style.display = 'none';
        } else {
            submissionGroup.style.display = 'block';
            fileSettings.style.display = 'grid';
            mcqSection.style.display = 'none';
        }
    }

    function addQuestion() {
        qCounter++;
        const qId = qCounter;
        const div = document.createElement('div');
        div.className = 'card';
        div.style.marginBottom = '1rem';
        div.style.padding = '1rem';
        div.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <strong>Question ${qId}</strong>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <label style="font-size: 0.85rem;">Marks: </label>
                    <input type="number" step="0.5" name="mcq_questions[${qId}][marks]" value="1.0" style="width: 70px;" class="form-control form-control-sm" required>
                    <button type="button" class="btn btn-sm btn-outline text-danger remove-q-btn" style="padding: 0.2rem 0.5rem;"><i class="bx bx-trash"></i></button>
                </div>
            </div>
            <input type="text" name="mcq_questions[${qId}][text]" class="form-control" placeholder="Enter question statement..." required style="margin-bottom: 0.75rem;">
            <div style="font-size: 0.85rem; font-weight: 600; margin-bottom: 0.4rem;">Options (select the radio for correct answer):</div>
            <div style="display: grid; gap: 0.4rem;">
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="radio" name="mcq_questions[${qId}][correct]" value="0" checked>
                    <input type="text" name="mcq_questions[${qId}][options][0]" class="form-control form-control-sm" placeholder="Option A" required>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="radio" name="mcq_questions[${qId}][correct]" value="1">
                    <input type="text" name="mcq_questions[${qId}][options][1]" class="form-control form-control-sm" placeholder="Option B" required>
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="radio" name="mcq_questions[${qId}][correct]" value="2">
                    <input type="text" name="mcq_questions[${qId}][options][2]" class="form-control form-control-sm" placeholder="Option C">
                </div>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="radio" name="mcq_questions[${qId}][correct]" value="3">
                    <input type="text" name="mcq_questions[${qId}][options][3]" class="form-control form-control-sm" placeholder="Option D">
                </div>
            </div>
        `;
        div.querySelector('.remove-q-btn').addEventListener('click', () => div.remove());
        mcqList.appendChild(div);
    }

    addMcqBtn.addEventListener('click', addQuestion);
    typeSelect.addEventListener('change', updateVisibility);
    updateVisibility();
});
</script>
