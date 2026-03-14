<?php
// Admin interface for managing questionnaire questions
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/supabase_client.php';

$message = null;
$error = null;

if (!empty($_POST["action"])) {
    $action = $_POST["action"];
    if ($action === "add") {
        $payload = [
            'category' => $_POST["category"] ?? '',
            'question' => $_POST["question"] ?? '',
            'answer1' => $_POST["answer1"] ?? '',
            'answer2' => $_POST["answer2"] ?? '',
            'answer3' => $_POST["answer3"] ?? '',
            'answer4' => $_POST["answer4"] ?? '',
            'clanWeight1' => $_POST["clanWeight1"] ?? '',
            'clanWeight2' => $_POST["clanWeight2"] ?? '',
            'clanWeight3' => $_POST["clanWeight3"] ?? '',
            'clanWeight4' => $_POST["clanWeight4"] ?? '',
        ];
        $result = supabase_rest_request('POST', '/rest/v1/questionnaire_questions', [], $payload, ['Prefer: return=minimal']);
        $message = ($result['error'] === null) ? "Question added successfully!" : null;
        $error = $result['error'] !== null ? "Error adding question: " . $result['error'] : null;
    } elseif ($action === "edit") {
        $id = (int)($_POST["id"] ?? 0);
        if ($id > 0) {
            $payload = [
                'category' => $_POST["category"] ?? '',
                'question' => $_POST["question"] ?? '',
                'answer1' => $_POST["answer1"] ?? '',
                'answer2' => $_POST["answer2"] ?? '',
                'answer3' => $_POST["answer3"] ?? '',
                'answer4' => $_POST["answer4"] ?? '',
                'clanWeight1' => $_POST["clanWeight1"] ?? '',
                'clanWeight2' => $_POST["clanWeight2"] ?? '',
                'clanWeight3' => $_POST["clanWeight3"] ?? '',
                'clanWeight4' => $_POST["clanWeight4"] ?? '',
            ];
            $result = supabase_rest_request('PATCH', '/rest/v1/questionnaire_questions', ['id' => 'eq.' . $id], $payload, ['Prefer: return=minimal']);
            $message = ($result['error'] === null) ? "Question updated successfully!" : null;
            $error = $result['error'] !== null ? "Error updating question: " . $result['error'] : null;
        }
    } elseif ($action === "delete") {
        $id = (int)($_POST["id"] ?? 0);
        if ($id > 0) {
            $result = supabase_rest_request('DELETE', '/rest/v1/questionnaire_questions', ['id' => 'eq.' . $id], null, ['Prefer: return=minimal']);
            $message = ($result['error'] === null) ? "Question deleted successfully!" : null;
            $error = $result['error'] !== null ? "Error deleting question: " . $result['error'] : null;
        }
    }
}

$questions_error = '';
try {
    $questions = supabase_table_get('questionnaire_questions', ['select' => 'id,question,category,answer1,answer2,answer3,answer4,clanWeight1,clanWeight2,clanWeight3,clanWeight4', 'order' => 'id.asc']);
} catch (Throwable $e) {
    error_log('questionnaire_admin: questionnaire_questions load failed: ' . $e->getMessage());
    $questions = [];
    $questions_error = $e->getMessage();
}

$extra_css = [
    'css/admin_questionnaire.css'
];
include __DIR__ . '/../includes/header.php';
?>
    <div class="admin-container">
        <div class="admin-header">
            <h1>🦇 Questionnaire Admin Panel</h1>
            <p>Manage questionnaire questions and clan scoring</p>
            <a href="../admin/admin_panel.php" style="color: #c9a96e;">← Back to Admin Panel</a>
        </div>

        <?php if ($questions_error !== ''): ?>
            <div class="alert alert-warning">Questions could not be loaded: <?php echo htmlspecialchars($questions_error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if (isset($message)): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Add New Question Form -->
        <div class="question-form">
            <h2>Add New Question</h2>
            <form method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="add">
                
                <div class="form-group mb-3">
                    <label for="category" class="form-label">Category:</label>
                    <select name="category" id="category" class="form-select" required>
                        <option value="">Select Category</option>
                        <option value="embrace">Embrace</option>
                        <option value="personality">Personality</option>
                        <option value="perspective">Perspective</option>
                        <option value="powers">Powers</option>
                        <option value="motivation">Motivation</option>
                        <option value="supernatural">Supernatural</option>
                        <option value="secrets">Secrets</option>
                        <option value="fears">Fears</option>
                        <option value="scenario">Scenario</option>
                        <option value="workplace">Workplace</option>
                        <option value="family">Family</option>
                        <option value="social">Social</option>
                        <option value="moral">Moral</option>
                        <option value="power">Power</option>
                        <option value="life">Life</option>
                    </select>
                    <div class="invalid-feedback">Please select a category.</div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="question" class="form-label">Question:</label>
                    <textarea name="question" id="question" class="form-control" required></textarea>
                    <div class="invalid-feedback">Please enter the question text.</div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="answer1" class="form-label">Answer 1:</label>
                    <input type="text" name="answer1" id="answer1" class="form-control" required>
                    <div class="invalid-feedback">Answer 1 is required.</div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="answer2" class="form-label">Answer 2:</label>
                    <input type="text" name="answer2" id="answer2" class="form-control" required>
                    <div class="invalid-feedback">Answer 2 is required.</div>
                </div>
                
                <div class="form-group mb-3">
                    <label for="answer3" class="form-label">Answer 3:</label>
                    <input type="text" name="answer3" id="answer3" class="form-control">
                </div>
                
                <div class="form-group mb-3">
                    <label for="answer4" class="form-label">Answer 4:</label>
                    <input type="text" name="answer4" id="answer4" class="form-control">
                </div>
                
                <div class="form-group mb-3">
                    <label for="clanWeight1" class="form-label">Clan Weight 1 (format: clan:points,clan:points):</label>
                    <input type="text" name="clanWeight1" id="clanWeight1" class="form-control" placeholder="ventrue:3,tremere:2">
                </div>
                
                <div class="form-group mb-3">
                    <label for="clanWeight2" class="form-label">Clan Weight 2:</label>
                    <input type="text" name="clanWeight2" id="clanWeight2" class="form-control" placeholder="tremere:3,nosferatu:2">
                </div>
                
                <div class="form-group mb-3">
                    <label for="clanWeight3" class="form-label">Clan Weight 3:</label>
                    <input type="text" name="clanWeight3" id="clanWeight3" class="form-control" placeholder="brujah:3,gangrel:2">
                </div>
                
                <div class="form-group mb-3">
                    <label for="clanWeight4" class="form-label">Clan Weight 4:</label>
                    <input type="text" name="clanWeight4" id="clanWeight4" class="form-control" placeholder="malkavian:3,nosferatu:2">
                </div>
                
                <button type="submit" class="btn btn-primary">Add Question</button>
            </form>
        </div>

        <!-- Questions List -->
        <div class="questions-table">
            <h2>Existing Questions (<?php echo count($questions); ?>)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category</th>
                        <th>Question</th>
                        <th>Answers</th>
                        <th>Clan Weights</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questions as $question):
                        $qid = $question["id"] ?? $question["ID"] ?? 0;
                        $qw1 = $question["clanweight1"] ?? $question["clanWeight1"] ?? '';
                        $qw2 = $question["clanweight2"] ?? $question["clanWeight2"] ?? '';
                        $qw3 = $question["clanweight3"] ?? $question["clanWeight3"] ?? '';
                        $qw4 = $question["clanweight4"] ?? $question["clanWeight4"] ?? '';
                    ?>
                    <tr>
                        <td><?php echo $qid; ?></td>
                        <td><?php echo ucfirst($question["category"] ?? ''); ?></td>
                        <td><?php echo substr($question["question"] ?? '', 0, 60) . "..."; ?></td>
                        <td>
                            <?php
                            $answers = array_filter([$question["answer1"] ?? '', $question["answer2"] ?? '', $question["answer3"] ?? '', $question["answer4"] ?? '']);
                            echo count($answers) . " answers";
                            ?>
                        </td>
                        <td>
                            <?php
                            $weights = array_filter([$qw1, $qw2, $qw3, $qw4]);
                            echo count($weights) . " weights";
                            ?>
                        </td>
                        <td>
                            <button class="btn btn-secondary toggle-edit-btn" data-question-id="<?php echo $qid; ?>">Edit</button>
                            <form method="POST" style="display: inline;" class="delete-question-form">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $qid; ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    
                    <!-- Edit Form (Hidden by default) -->
                    <tr id="edit-<?php echo $qid; ?>" class="edit-form">
                        <td colspan="6">
                            <form method="POST">
                                <input type="hidden" name="action" value="edit">
                                <input type="hidden" name="id" value="<?php echo $qid; ?>">
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div class="form-group">
                                        <label>Category:</label>
                                        <select name="category" required>
                                            <option value="embrace" <?php echo $question["category"] == "embrace" ? "selected" : ""; ?>>Embrace</option>
                                            <option value="personality" <?php echo $question["category"] == "personality" ? "selected" : ""; ?>>Personality</option>
                                            <option value="perspective" <?php echo $question["category"] == "perspective" ? "selected" : ""; ?>>Perspective</option>
                                            <option value="powers" <?php echo $question["category"] == "powers" ? "selected" : ""; ?>>Powers</option>
                                            <option value="motivation" <?php echo $question["category"] == "motivation" ? "selected" : ""; ?>>Motivation</option>
                                            <option value="supernatural" <?php echo $question["category"] == "supernatural" ? "selected" : ""; ?>>Supernatural</option>
                                            <option value="secrets" <?php echo $question["category"] == "secrets" ? "selected" : ""; ?>>Secrets</option>
                                            <option value="fears" <?php echo $question["category"] == "fears" ? "selected" : ""; ?>>Fears</option>
                                            <option value="scenario" <?php echo $question["category"] == "scenario" ? "selected" : ""; ?>>Scenario</option>
                                            <option value="workplace" <?php echo $question["category"] == "workplace" ? "selected" : ""; ?>>Workplace</option>
                                            <option value="family" <?php echo $question["category"] == "family" ? "selected" : ""; ?>>Family</option>
                                            <option value="social" <?php echo $question["category"] == "social" ? "selected" : ""; ?>>Social</option>
                                            <option value="moral" <?php echo $question["category"] == "moral" ? "selected" : ""; ?>>Moral</option>
                                            <option value="power" <?php echo $question["category"] == "power" ? "selected" : ""; ?>>Power</option>
                                            <option value="life" <?php echo $question["category"] == "life" ? "selected" : ""; ?>>Life</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Question:</label>
                                        <textarea name="question" required><?php echo htmlspecialchars($question["question"]); ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Answer 1:</label>
                                        <input type="text" name="answer1" value="<?php echo htmlspecialchars($question["answer1"]); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Answer 2:</label>
                                        <input type="text" name="answer2" value="<?php echo htmlspecialchars($question["answer2"]); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Answer 3:</label>
                                        <input type="text" name="answer3" value="<?php echo htmlspecialchars($question["answer3"]); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Answer 4:</label>
                                        <input type="text" name="answer4" value="<?php echo htmlspecialchars($question["answer4"]); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Clan Weight 1:</label>
                                        <input type="text" name="clanWeight1" value="<?php echo htmlspecialchars($qw1); ?>" placeholder="ventrue:3,tremere:2">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Clan Weight 2:</label>
                                        <input type="text" name="clanWeight2" value="<?php echo htmlspecialchars($qw2); ?>" placeholder="tremere:3,nosferatu:2">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Clan Weight 3:</label>
                                        <input type="text" name="clanWeight3" value="<?php echo htmlspecialchars($qw3); ?>" placeholder="brujah:3,gangrel:2">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label>Clan Weight 4:</label>
                                        <input type="text" name="clanWeight4" value="<?php echo htmlspecialchars($qw4); ?>" placeholder="malkavian:3,nosferatu:2">
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Update Question</button>
                                <button type="button" class="btn btn-secondary toggle-edit-btn" data-question-id="<?php echo $qid; ?>">Cancel</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="../js/admin_questionnaire.js"></script>
    <script src="../js/form_validation.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
