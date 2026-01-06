<?php
/**
 * Character Questionnaire (Dynamic) — Valley by Night
 * Loads 20 random questions with defensive schema detection and shared includes.
 */

session_start();

require_once __DIR__ . '/includes/auth_bypass.php';
if (!isset($_SESSION['user_id']) && !isAuthBypassEnabled()) {
    header('Location: login.php');
    exit();
}
if (isAuthBypassEnabled() && !isset($_SESSION['user_id'])) {
    setupBypassSession();
}

include __DIR__ . '/includes/connect.php';

// Detect available columns to support prod/dev schema differences
$columnsResult = db_select($conn, 'SHOW COLUMNS FROM questionnaire_questions');
if ($columnsResult === false) {
    die('Database error: Failed to inspect questionnaire schema');
}
$available = [];
while ($row = mysqli_fetch_assoc($columnsResult)) {
    $available[strtolower($row['Field'])] = true;
}

// Build a resilient SELECT list
$selects = [];

// Always try to include id/ID
if (isset($available['id'])) {
    $selects[] = 'id';
} elseif (isset($available['ID'])) { // case-insensitivity safeguard
    $selects[] = 'ID as id';
}

// Category/subcategory when present
if (isset($available['category'])) {
    $selects[] = 'category';
}
if (isset($available['subcategory'])) {
    $selects[] = 'subcategory';
}

// Question text — prefer `question`, fallback to `question_text`
if (isset($available['question'])) {
    $selects[] = 'question as question';
} elseif (isset($available['question_text'])) {
    $selects[] = 'question_text as question';
}

// Answers (if table stores them)
for ($i = 1; $i <= 4; $i++) {
    $col = 'answer' . $i;
    if (isset($available[$col])) {
        $selects[] = $col;
    }
}

// Clan weights for scoring in JS (comma-delimited strings)
for ($i = 1; $i <= 4; $i++) {
    $col = 'clanweight' . $i; // handle case-insensitive variations
    if (isset($available[$col])) {
        $selects[] = 'clanWeight' . $i;
    } elseif (isset($available['clanWeight' . $i])) {
        $selects[] = 'clanWeight' . $i;
    }
}

// Fallback if question text not found
if (!in_array('question as question', $selects, true) && !in_array('question_text as question', $selects, true)) {
    // Provide at least id and category to avoid total failure; inform user
    die('Database error: Missing question text column (expected `question` or `question_text`)');
}

$sql = 'SELECT ' . implode(', ', $selects) . ' FROM questionnaire_questions ORDER BY RAND() LIMIT 20';
$result = db_select($conn, $sql);
if ($result === false) {
    // Log was already recorded by db_select; show friendly message
    die('Database error: Failed to load questions');
}
$questions = mysqli_fetch_all($result, MYSQLI_ASSOC);
if (empty($questions)) {
    die('No questions found in database.');
}

$extra_css = [];
include __DIR__ . '/includes/header.php';
?>
        <!-- Tracking Toggle Button (optional reveal) -->
        <button id="tracking-toggle" class="tracking-toggle btn btn-outline-danger mb-3">Show Clan Scores</button>

        <!-- Admin Debug Toggle Button (hidden by default; can be enabled via ?admin=1) -->
        <button id="admin-debug-toggle" class="admin-debug-toggle btn btn-outline-danger mb-3" style="display: none;">Admin Debug</button>

        <!-- aria-live status for async/UI updates -->
        <div id="questionnaire-status" class="visually-hidden" role="status" aria-live="polite"></div>

        <!-- Tracking Popup -->
        <div id="tracking-popup" class="tracking-popup" style="display: none;">
            <div class="tracking-header">
                <h3 class="tracking-title">Clan Tracking</h3>
                <button id="tracking-close" class="tracking-close" aria-label="Close tracking popup">&times;</button>
            </div>
            <div id="tracking-content"><!-- Clan scores populated here --></div>
        </div>

        <!-- Admin Debug Popup -->
        <div id="admin-debug-popup" class="admin-debug-popup" style="display: none;">
            <div class="admin-debug-header">
                <h3 class="admin-debug-title">Admin Debug</h3>
                <button id="admin-debug-close" class="admin-debug-close" aria-label="Close admin debug popup">&times;</button>
            </div>
            <div id="admin-debug-content" class="admin-debug-content"></div>
        </div>

        <div class="container py-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h1 class="display-4 mb-2">The Night Creates You</h1>
                    <p class="lead mb-2">Character Creation Questionnaire</p>
                    <p class="opacity-75">
                        Answer these questions to discover which vampire clan calls to your soul.
                    </p>
                </div>
            </div>

            <form id="questionnaire-form" class="questionnaire-form" novalidate>
                <!-- Progress Indicator -->
                <div class="mb-4" aria-live="polite">
                    <div class="progress mb-2" style="height: 25px;">
                        <div class="progress-bar bg-danger" role="progressbar" id="progress-fill" style="width: 5%;" aria-valuenow="5" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <div class="text-center opacity-75">
                        <span id="current-question">1</span> of <span id="total-questions">20</span>
                    </div>
                </div>

                <?php $qNum = 1; foreach ($questions as $q): ?>
                <section class="card mb-4 question-section <?php echo $qNum === 1 ? 'active' : 'd-none'; ?>" data-question="<?php echo $qNum; ?>" aria-labelledby="q<?php echo $qNum; ?>-title">
                    <div class="card-body">
                        <h2 id="q<?php echo $qNum; ?>-title" class="h4 mb-3">Question <?php echo $qNum; ?></h2>
                        <?php if (!empty($q['category'])): ?>
                        <div class="badge bg-danger mb-3" data-category="<?php echo htmlspecialchars($q['category']); ?>">
                            <?php echo ucfirst(htmlspecialchars($q['category'])); ?>
                        </div>
                        <?php endif; ?>
                        <p class="lead mb-4"><?php echo htmlspecialchars($q['question'] ?? ''); ?></p>

                        <fieldset class="answer-group">
                            <legend class="visually-hidden">Answer options</legend>
                            <div class="d-flex flex-column gap-3">
                                <?php for ($i = 1; $i <= 4; $i++): $key = 'answer' . $i; if (!empty($q[$key])): ?>
                                    <div class="form-check">
                                        <input type="radio" name="question_<?php echo $qNum; ?>" class="form-check-input" id="q<?php echo $qNum; ?>_a<?php echo $i; ?>" value="<?php echo $i; ?>">
                                        <label class="form-check-label" for="q<?php echo $qNum; ?>_a<?php echo $i; ?>">
                                            <?php echo htmlspecialchars($q[$key]); ?>
                                        </label>
                                    </div>
                                <?php endif; endfor; ?>
                            </div>
                        </fieldset>
                    </div>
                </section>
                <?php $qNum++; endforeach; ?>

                <nav class="d-flex justify-content-center gap-3 mb-4" aria-label="Questionnaire Navigation">
                    <button type="button" id="next-btn" class="btn btn-primary" disabled>Next Question</button>
                    <button type="submit" id="submit-btn" class="btn btn-primary" style="display: none;">Complete Questionnaire</button>
                </nav>
            </form>

            <section id="results-section" class="card" hidden>
                <div class="card-body text-center">
                    <h1 class="display-5 mb-4">Your Clan Has Been Revealed</h1>
                    <div class="clan-result mb-4">
                        <div class="mb-3">
                            <img id="clan-logo" src="" alt="" class="img-fluid" style="max-height: 200px;">
                        </div>
                        <h2 id="clan-name" class="h3 mb-3"></h2>
                        <p id="clan-description" class="lead mb-4"></p>
                        <div class="clan-stats">
                            <h3 class="h5 mb-3">Your Clan Scores:</h3>
                            <div id="all-clan-scores" class="all-clan-scores"></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <button id="retake-btn" class="btn btn-outline-danger" type="button">Retake Questionnaire</button>
                        <button id="create-character-btn" class="btn btn-primary" type="button">Create Character</button>
                    </div>
                </div>
            </section>
        </div>

        <script type="application/json" id="questionsData"><?php echo json_encode($questions); ?></script>
        <script src="js/questionnaire.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
