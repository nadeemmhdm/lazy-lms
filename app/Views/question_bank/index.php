<div class="page-header">
    <div>
        <h1 class="page-title">Question Bank</h1>
        <p class="page-subtitle">Central repository of reusable exam questions across all subjects and courses.</p>
    </div>
    <div>
        <a href="<?= url('/question-bank/create') ?>" class="btn btn-primary"><i class="bx bx-plus-circle"></i> Add Question</a>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
        <!-- Filters -->
        <form action="<?= url('/question-bank') ?>" method="GET" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <select name="type" class="form-control" style="width: auto;" onchange="this.form.submit()">
                <option value="">All Types</option>
                <option value="single_choice" <?= ($filters['type'] ?? '') === 'single_choice' ? 'selected' : '' ?>>Single Choice</option>
                <option value="multiple_choice" <?= ($filters['type'] ?? '') === 'multiple_choice' ? 'selected' : '' ?>>Multiple Choice</option>
                <option value="true_false" <?= ($filters['type'] ?? '') === 'true_false' ? 'selected' : '' ?>>True / False</option>
                <option value="short_answer" <?= ($filters['type'] ?? '') === 'short_answer' ? 'selected' : '' ?>>Short Answer</option>
                <option value="numeric" <?= ($filters['type'] ?? '') === 'numeric' ? 'selected' : '' ?>>Numeric</option>
            </select>

            <select name="difficulty" class="form-control" style="width: auto;" onchange="this.form.submit()">
                <option value="">All Difficulties</option>
                <option value="easy" <?= ($filters['difficulty'] ?? '') === 'easy' ? 'selected' : '' ?>>Easy</option>
                <option value="medium" <?= ($filters['difficulty'] ?? '') === 'medium' ? 'selected' : '' ?>>Medium</option>
                <option value="hard" <?= ($filters['difficulty'] ?? '') === 'hard' ? 'selected' : '' ?>>Hard</option>
            </select>

            <input type="text" name="search" class="form-control" placeholder="Search questions..." value="<?= e($filters['search'] ?? '') ?>" style="width: 220px;">
            <button type="submit" class="btn btn-outline btn-sm"><i class="bx bx-search"></i> Search</button>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Question</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Difficulty</th>
                    <th>Marks</th>
                    <th>Author</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($questions)): ?>
                    <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 2rem;">No questions found. Add your first question to the bank!</td></tr>
                <?php else: ?>
                    <?php foreach ($questions as $q): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?= e(substr(strip_tags($q['question']), 0, 100)) ?><?= strlen($q['question']) > 100 ? '...' : '' ?></div>
                                <?php if (!empty($q['tags'])): ?>
                                    <span style="font-size: 0.75rem; color: var(--text-muted);"><i class="bx bx-purchase-tag"></i> <?= e($q['tags']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-neutral"><?= e(str_replace('_', ' ', $q['type'])) ?></span></td>
                            <td><?= e($q['category'] ?? 'General') ?></td>
                            <td>
                                <?php
                                    $diffBadge = match($q['difficulty']) {
                                        'easy' => 'badge-success',
                                        'medium' => 'badge-info',
                                        'hard' => 'badge-danger',
                                        default => 'badge-neutral'
                                    };
                                ?>
                                <span class="badge <?= $diffBadge ?>"><?= ucfirst($q['difficulty']) ?></span>
                            </td>
                            <td><strong><?= $q['marks'] ?></strong></td>
                            <td style="font-size: 0.85rem; color: var(--text-muted);"><?= e($q['author_name'] ?? 'Admin') ?></td>
                            <td style="text-align: right;">
                                <form action="<?= url('/question-bank/' . $q['id'] . '/delete') ?>" method="POST" style="display: inline;" onsubmit="return confirm('Delete this question permanently?');">
                                    <input type="hidden" name="_token" value="<?= csrf_token() ?>">
                                    <button type="submit" class="btn btn-outline btn-sm" style="color: var(--danger);" title="Delete"><i class="bx bx-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
