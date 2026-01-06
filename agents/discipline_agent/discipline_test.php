<?php
/**
 * Discipline Agent Test Page
 * Shows a list of disciplines for a character
 */

// Ensure we're outputting HTML, not being executed as API
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Check authentication (optional - can be removed if you want public access)
if (!isset($_SESSION['user_id'])) {
    // Uncomment to require login:
    // header("Location: ../../login.php");
    // exit();
}

require_once __DIR__ . '/../../includes/connect.php';
require_once __DIR__ . '/src/DisciplineAgent.php';

// Load all discipline powers from discipline_powers table
// First get discipline_id from disciplines table, then use it to get powers
$allDisciplinePowers = [];

try {
    // Get all disciplines with their IDs
    $disc_query = "SELECT id, name FROM disciplines WHERE parent_discipline IS NULL";
    $disc_results = db_fetch_all($conn, $disc_query);
    
    if (empty($disc_results)) {
        $error = ($error ?? '') . ' No disciplines found in database.';
    } else {
        // For each discipline, get its powers using discipline_id
        foreach ($disc_results as $disc) {
            $discipline_id = (int)$disc['id'];
            $discipline_name = $disc['name'];
            
            // Query discipline_powers using the discipline_id
            $powers_query = "SELECT power_level, power_name, description, prerequisites
                             FROM discipline_powers
                             WHERE discipline_id = ?
                             ORDER BY power_level";
            $powers = db_fetch_all($conn, $powers_query, 'i', [$discipline_id]);
            
            if (!empty($powers)) {
                $allDisciplinePowers[$discipline_name] = $powers;
            }
        }
    }
} catch (Exception $e) {
    $error = ($error ?? '') . ' Error loading powers: ' . $e->getMessage();
    error_log("Discipline test page error: " . $e->getMessage());
}

// Get character ID from query parameter or use first character
$character_id = isset($_GET['character_id']) ? (int)$_GET['character_id'] : null;

// If no character ID provided, get first character
if ($character_id === null) {
    $char_result = db_fetch_one($conn, "SELECT id, character_name, clan FROM characters ORDER BY id LIMIT 1");
    if ($char_result) {
        $character_id = (int)$char_result['id'];
    }
}

$agent = null;
$result = null;
$error = null;

try {
    if ($character_id) {
        $agent = new DisciplineAgent($conn);
        $result = $agent->listCharacterDisciplines($character_id);
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Get character info if we have an ID
$character_info = null;
if ($character_id) {
    $character_info = db_fetch_one($conn, "SELECT id, character_name, clan FROM characters WHERE id = ?", 'i', [$character_id]);
}

// Get list of all characters for dropdown
$all_characters = [];
if (isset($conn)) {
    try {
        $all_characters = db_fetch_all($conn, "SELECT id, character_name, clan FROM characters ORDER BY character_name LIMIT 100");
    } catch (Exception $e) {
        $error = ($error ?? '') . ' Error loading characters: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discipline Agent Test - List Disciplines</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/discipline_test.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1 class="mb-4">🧛 Discipline Agent Test - List Disciplines</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($allDisciplinePowers) && !$error): ?>
            <div class="alert alert-warning">
                <strong>Warning:</strong> No discipline powers loaded. 
                <?php if (isset($debug_info['disciplines_found'])): ?>
                    Found <?= $debug_info['disciplines_found'] ?> disciplines in database.
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['debug']) && isset($debug_info)): ?>
            <div class="alert alert-info">
                <strong>Debug Info:</strong>
                <pre><?= print_r($debug_info, true) ?></pre>
                <strong>Loaded Powers:</strong>
                <pre><?= print_r(array_keys($allDisciplinePowers), true) ?></pre>
            </div>
        <?php endif; ?>
        
        <!-- Character Selector -->
        <div class="character-selector">
            <h3>Select Character</h3>
            <form method="GET" class="d-flex gap-2 align-items-end">
                <div class="flex-grow-1">
                    <label for="character_id" class="form-label">Character:</label>
                    <select name="character_id" id="character_id" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Select Character --</option>
                        <?php foreach ($all_characters as $char): ?>
                            <option value="<?= $char['id'] ?>" <?= $character_id == $char['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($char['character_name']) ?> (<?= htmlspecialchars($char['clan']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
        
        <?php if ($character_info && $result): ?>
            <!-- Character Info -->
            <div class="summary-box">
                <h2><?= htmlspecialchars($character_info['character_name']) ?></h2>
                <p class="mb-1"><strong>Clan:</strong> <?= htmlspecialchars($character_info['clan']) ?></p>
                <p class="mb-0"><strong>Character ID:</strong> <?= $character_info['id'] ?></p>
            </div>
            
            <!-- Summary -->
            <div class="summary-box">
                <h3>Summary</h3>
                <p><strong>Total Disciplines:</strong> <?= $result['summary']['total_disciplines'] ?></p>
                <p><strong>Total Powers:</strong> <?= $result['summary']['total_powers'] ?></p>
            </div>
            
            <!-- Disciplines List -->
            <h3 class="mb-3">Disciplines</h3>
            
            <?php if (empty($result['disciplines'])): ?>
                <div class="alert alert-info">
                    This character has no disciplines.
                </div>
            <?php else: ?>
                <?php foreach ($result['disciplines'] as $discipline): ?>
                    <div class="discipline-card">
                        <?php 
                        $discipline_name = ucwords(strtolower($discipline['discipline_name']));
                        $level = (int)$discipline['level'];
                        ?>
                        <div class="discipline-name"><?= htmlspecialchars($discipline_name) ?></div>
                        
                        <?php 
                        // Get all powers for this discipline from discipline_powers table
                        // Match by discipline name (case-insensitive)
                        $all_powers_for_discipline = [];
                        $disc_name_lower = strtolower($discipline['discipline_name']);
                        foreach ($allDisciplinePowers as $disc_name => $powers) {
                            if (strtolower($disc_name) === $disc_name_lower) {
                                $all_powers_for_discipline = $powers;
                                break;
                            }
                        }
                        
                        // Filter powers to only show those up to character's discipline level
                        $discipline_powers = [];
                        $character_discipline_level = (int)$discipline['level'];
                        foreach ($all_powers_for_discipline as $power) {
                            $power_level = (int)$power['power_level'];
                            // Only show powers at or below the character's discipline level
                            if ($power_level <= $character_discipline_level) {
                                $discipline_powers[] = $power;
                            }
                        }
                        
                        // Get character's known powers
                        $character_powers = [];
                        if (!empty($discipline['powers'])) {
                            foreach ($discipline['powers'] as $power) {
                                $character_powers[strtolower($power['power_name'])] = true;
                            }
                        }
                        ?>
                        
                        <?php if (!empty($discipline_powers)): ?>
                            <div class="mt-3">
                                <strong>Powers (Levels 1-<?= $character_discipline_level ?>):</strong>
                                <?php foreach ($discipline_powers as $power): ?>
                                    <?php 
                                    $power_name_lower = strtolower($power['power_name']);
                                    $has_power = isset($character_powers[$power_name_lower]);
                                    $power_level = (int)$power['power_level'];
                                    ?>
                                    <div class="power-item <?= $has_power ? 'known-power' : '' ?>">
                                        <div class="d-flex align-items-start">
                                            <div class="flex-grow-1">
                                                <span class="power-name">Level <?= $power_level ?>: <?= htmlspecialchars(ucwords(strtolower($power['power_name']))) ?></span>
                                                <?php if ($has_power): ?>
                                                    <span class="text-success ms-2">✓ Known</span>
                                                <?php endif; ?>
                                                <?php if (!empty($power['description'])): ?>
                                                    <div class="power-description mt-1">
                                                        <?= htmlspecialchars($power['description']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($power['prerequisites'])): ?>
                                                    <div class="opacity-75 mt-1" style="font-size: 0.85em;">
                                                        <em>Prerequisites: <?= htmlspecialchars($power['prerequisites']) ?></em>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="mt-2 no-powers-text">No powers available for Level <?= $character_discipline_level ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Raw JSON (for debugging) -->
            <details class="mt-4">
                <summary class="btn btn-outline-secondary">Show Raw JSON</summary>
                <pre class="mt-3 p-3 bg-dark border rounded json-output"><?= json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?></pre>
            </details>
            
        <?php elseif ($character_id): ?>
            <div class="alert alert-warning">
                Character ID <?= $character_id ?> not found or has no disciplines.
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                Please select a character from the dropdown above.
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

