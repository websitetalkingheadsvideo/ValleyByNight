<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../includes/connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$username = $_SESSION['username'] ?? 'Guest';

// Get selected influence and level from query params
$selectedInfluence = isset($_GET['influence']) ? trim((string)$_GET['influence']) : '';
$selectedLevel = isset($_GET['level']) ? (int)$_GET['level'] : 0;

// Fetch all influence types
$influence_types_query = "SELECT influence_name, description FROM influence_types WHERE is_active = 1 ORDER BY sort_order";
$influence_types_result = mysqli_query($conn, $influence_types_query);
$influence_types = [];
while ($row = mysqli_fetch_assoc($influence_types_result)) {
    $influence_types[$row['influence_name']] = $row['description'];
}

// Fetch effects if influence and level are selected
$effects_data = null;
if ($selectedInfluence && $selectedLevel >= 1 && $selectedLevel <= 5) {
    $effects_query = "
        SELECT ie.level, ie.effects_text, it.description, it.influence_name
        FROM influence_effects_lookup ie
        JOIN influence_types it ON ie.influence_name = it.influence_name
        WHERE ie.influence_name = ? AND ie.level = ?
    ";
    $stmt = mysqli_prepare($conn, $effects_query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'si', $selectedInfluence, $selectedLevel);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            $effects_data = mysqli_fetch_assoc($result);
        } else {
            error_log("Query execution failed: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
    } else {
        error_log("Prepare failed: " . mysqli_error($conn));
    }
}

// Fetch all effects for selected influence (for level selector)
$all_levels = [];
if ($selectedInfluence) {
    $levels_query = "
        SELECT level, effects_text
        FROM influence_effects_lookup
        WHERE influence_name = ?
        ORDER BY level
    ";
    $stmt = mysqli_prepare($conn, $levels_query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $selectedInfluence);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $all_levels[$row['level']] = $row['effects_text'];
        }
        mysqli_stmt_close($stmt);
    }
}

mysqli_close($conn);

$extra_css = ['css/global.css'];
include __DIR__ . '/../../includes/header.php';
?>

<style>
        .influence-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }

        .agent-header {
            text-align: center;
            padding: 20px;
            background: rgba(26, 15, 15, 0.8);
            border-radius: 8px;
            margin-bottom: 20px;
            border-bottom: 2px solid #8b0000;
        }

        .agent-header h1 {
            color: #8b0000;
            margin: 0 0 10px 0;
            font-size: 32px;
        }

        .agent-header p {
            color: #999;
            margin: 0;
            font-size: 16px;
        }

        .lookup-panel {
            background: rgba(26, 15, 15, 0.8);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .form-group {
            flex: 1;
            min-width: 200px;
        }

        .form-group label {
            display: block;
            color: #8b0000;
            font-weight: bold;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-select {
            width: 100%;
            padding: 12px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #8b0000;
            color: #fff;
            border-radius: 4px;
            font-size: 16px;
            font-family: inherit;
            cursor: pointer;
        }

        .form-select:focus {
            outline: none;
            border-color: #a00000;
            box-shadow: 0 0 5px rgba(139, 0, 0, 0.5);
        }

        .form-select option {
            background: #1a0f0f;
            color: #fff;
        }

        .lookup-button {
            padding: 12px 30px;
            background: #8b0000;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: background 0.3s;
            align-self: flex-end;
        }

        .lookup-button:hover {
            background: #a00000;
        }

        .results-panel {
            background: rgba(26, 15, 15, 0.8);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        .results-header {
            border-bottom: 2px solid #8b0000;
            padding-bottom: 12px;
            margin-bottom: 15px;
        }

        .results-header h2 {
            color: #8b0000;
            margin: 0 0 10px 0;
            font-size: 24px;
        }

        .results-header .influence-name {
            color: #fff;
            font-size: 28px;
            font-weight: bold;
        }

        .results-header .level-badge {
            display: inline-block;
            background: #8b0000;
            color: #fff;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 18px;
            margin-left: 10px;
        }

        .effects-text {
            color: #fff !important;
            line-height: 1.8;
            font-size: 16px;
            padding: 15px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 4px;
            border-left: 4px solid #8b0000;
            min-height: 50px;
        }

        .level-selector {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            flex-wrap: wrap;
        }

        .level-button {
            padding: 10px 20px;
            background: rgba(139, 0, 0, 0.2);
            border: 1px solid #8b0000;
            color: #ccc;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .level-button:hover {
            background: rgba(139, 0, 0, 0.4);
            transform: translateY(-2px);
        }

        .level-button.active {
            background: #8b0000;
            color: #fff;
        }

        .description-text {
            color: #999;
            font-size: 14px;
            margin-top: 10px;
            margin-bottom: 10px;
            padding: 12px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
            border-left: 3px solid #666;
        }

        .no-selection {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-selection h3 {
            color: #8b0000;
            margin-bottom: 15px;
        }

        .quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .quick-link {
            padding: 8px 15px;
            background: rgba(139, 0, 0, 0.2);
            border: 1px solid #8b0000;
            border-radius: 20px;
            color: #ccc;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .quick-link:hover {
            background: rgba(139, 0, 0, 0.4);
            transform: translateY(-2px);
        }
    </style>

<div class="influence-container">
    <div class="agent-header">
        <h1>💼 Influence Agent</h1>
        <p>Look up what each level of Influence can do in Laws of the Night</p>
        <p style="font-size: 12px; margin-top: 10px; color: #666;">Reference: Laws of the Night (1st Edition), pages 581-733</p>
    </div>

    <div class="lookup-panel">
        <form method="GET" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="influence">Select Influence Type</label>
                    <select name="influence" id="influence" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Choose an Influence --</option>
                        <?php foreach ($influence_types as $name => $description): ?>
                            <option value="<?= htmlspecialchars($name) ?>" <?= $selectedInfluence === $name ? 'selected' : '' ?>>
                                <?= htmlspecialchars($name) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($selectedInfluence): ?>
                <div class="form-group">
                    <label for="level">Select Level (1-5 Traits)</label>
                    <select name="level" id="level" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Choose Level --</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= $selectedLevel === $i ? 'selected' : '' ?>>
                                Level <?= $i ?> (<?= $i ?> Trait<?= $i > 1 ? 's' : '' ?>)
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="results-panel">
        <?php if ($effects_data && isset($effects_data['effects_text']) && !empty($effects_data['effects_text'])): ?>
            <div class="results-header">
                <h2>Effects for:</h2>
                <div>
                    <span class="influence-name"><?= htmlspecialchars($effects_data['influence_name'] ?? 'Unknown') ?></span>
                    <span class="level-badge">Level <?= htmlspecialchars(isset($effects_data['level']) ? (string)$effects_data['level'] : '?') ?></span>
                </div>
            </div>
            
            <?php if (!empty($effects_data['description'])): ?>
            <div class="description-text">
                <strong>What is <?= htmlspecialchars($effects_data['influence_name']) ?> Influence?</strong><br>
                <?= htmlspecialchars($effects_data['description']) ?>
            </div>
            <?php endif; ?>
            
            <div class="effects-text">
                <strong style="color: #8b0000; display: block; margin-bottom: 10px;">Effects at Level <?= htmlspecialchars((string)$effects_data['level']) ?>:</strong>
                <?= nl2br(htmlspecialchars($effects_data['effects_text'])) ?>
            </div>
        <?php elseif ($selectedInfluence && $selectedLevel): ?>
            <div class="no-selection">
                <h3>No effects found</h3>
                <p>No effects found for <?= htmlspecialchars($selectedInfluence) ?> at level <?= $selectedLevel ?>.</p>
            </div>
        <?php else: ?>
            <div class="no-selection">
                <h3>Welcome, <?= htmlspecialchars($username) ?>!</h3>
                <p>Select an Influence type and level to see what actions you can perform.</p>
                <p style="margin-top: 20px; color: #666;">The Influence system allows vampires to manipulate mortal institutions across 15 distinct categories.</p>
                
                <div class="quick-links" style="justify-content: center; margin-top: 30px;">
                    <a href="?influence=Bureaucracy&level=3" class="quick-link">Bureaucracy Level 3</a>
                    <a href="?influence=Police&level=4" class="quick-link">Police Level 4</a>
                    <a href="?influence=Finance&level=5" class="quick-link">Finance Level 5</a>
                    <a href="?influence=Legal&level=2" class="quick-link">Legal Level 2</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

