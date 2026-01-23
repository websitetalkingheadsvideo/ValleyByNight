<?php
/**
 * Character Data Quality Blockers
 * 
 * Identifies missing/empty fields that prevent accurate character summaries.
 * Read-only tool - does not modify data.
 */
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../login.php");
    exit();
}

require_once __DIR__ . '/../../includes/connect.php';

// Check database connection
if (!$conn) {
    die("Database connection failed. Please check your configuration.");
}

// Page-specific CSS
$extra_css = ['css/admin_panel.css'];
include __DIR__ . '/../../includes/header.php';

// Fields to check for missing data
$critical_fields = [
    'biography' => 'Biography',
    'appearance' => 'Appearance',
    'histories' => 'Histories',
    'history' => 'History',
    'concept' => 'Concept',
    'nature' => 'Nature',
    'demeanor' => 'Demeanor'
];

// Initialize results
$results = [];
$stats = [
    'total_scanned' => 0,
    'with_missing_data' => 0,
    'field_counts' => []
];

// Process search if submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    try {
        // Check which history column exists
        $history_column = null;
        $columns_check = db_fetch_all($conn, "SHOW COLUMNS FROM characters LIKE 'histories'");
        if (!empty($columns_check)) {
            $history_column = 'histories';
        } else {
            $columns_check = db_fetch_all($conn, "SHOW COLUMNS FROM characters LIKE 'history'");
            if (!empty($columns_check)) {
                $history_column = 'history';
            }
        }
        
        // Build base select fields
        $select_fields = ['c.id', 'c.character_name', 'c.biography', 'c.appearance', 'c.concept', 'c.nature', 'c.demeanor'];
        if ($history_column) {
            $select_fields[] = 'c.' . $history_column;
        }
        
        // Use subqueries for counts to avoid Cartesian product performance issues
        $query = "SELECT " . implode(', ', $select_fields) . ",
                    (SELECT COUNT(*) FROM character_abilities WHERE character_id = c.id) as abilities_count,
                    (SELECT COUNT(*) FROM character_disciplines WHERE character_id = c.id) as disciplines_count,
                    (SELECT COUNT(*) FROM character_backgrounds WHERE character_id = c.id) as backgrounds_count
                  FROM characters c
                  ORDER BY c.id";
        
        // Query all characters with counts in one go
        $characters = db_fetch_all($conn, $query);
        
        if ($characters === false) {
            throw new Exception("Query failed: " . mysqli_error($conn));
        }
    
    $stats['total_scanned'] = count($characters);
    
    foreach ($characters as $char) {
        $missing_fields = [];
        
        // Check each critical field
        foreach ($critical_fields as $field => $label) {
            // Skip if field doesn't exist in result (e.g., histories vs history)
            if (!array_key_exists($field, $char)) {
                continue;
            }
            
            $value = $char[$field] ?? null;
            $is_missing = false;
            
            // Check for NULL, empty string, or whitespace-only
            if ($value === null || $value === '' || trim($value) === '') {
                $is_missing = true;
            }
            // Check for empty JSON arrays/objects
            elseif (in_array($field, ['histories', 'history']) && 
                    (trim($value) === '[]' || trim($value) === '{}')) {
                $is_missing = true;
            }
            
            if ($is_missing) {
                $missing_fields[] = $label;
                
                // Track field counts
                if (!isset($stats['field_counts'][$label])) {
                    $stats['field_counts'][$label] = 0;
                }
                $stats['field_counts'][$label]++;
            }
        }
        
        // Check related tables for missing data (from single query)
        if ((int)$char['abilities_count'] === 0) {
            $missing_fields[] = 'Abilities';
            if (!isset($stats['field_counts']['Abilities'])) {
                $stats['field_counts']['Abilities'] = 0;
            }
            $stats['field_counts']['Abilities']++;
        }
        
        if ((int)$char['disciplines_count'] === 0) {
            $missing_fields[] = 'Disciplines';
            if (!isset($stats['field_counts']['Disciplines'])) {
                $stats['field_counts']['Disciplines'] = 0;
            }
            $stats['field_counts']['Disciplines']++;
        }
        
        if ((int)$char['backgrounds_count'] === 0) {
            $missing_fields[] = 'Backgrounds';
            if (!isset($stats['field_counts']['Backgrounds'])) {
                $stats['field_counts']['Backgrounds'] = 0;
            }
            $stats['field_counts']['Backgrounds']++;
        }
        
        // Only add to results if missing fields found
        if (!empty($missing_fields)) {
            $stats['with_missing_data']++;
            $results[] = [
                'id' => (int)$char['id'],
                'name' => $char['character_name'] ?? 'Unknown',
                'missing_fields' => $missing_fields
            ];
        }
    }
    
    // Sort field counts by frequency (descending)
    arsort($stats['field_counts']);
    } catch (Exception $e) {
        $error_message = "Error: " . htmlspecialchars($e->getMessage());
    }
}

?>

<div class="admin-panel-container container-fluid py-4 px-3 px-md-4">
    <div class="row">
        <div class="col-12">
            <div class="card bg-dark border-danger mb-4">
                <div class="card-header bg-danger text-white">
                    <h1 class="h3 mb-0">Character Data Quality Blockers</h1>
                </div>
                <div class="card-body">
                    <p class="text-white mb-4">
                        This tool identifies missing or empty fields that prevent accurate character summaries.
                        Missing data includes: NULL values, empty strings, whitespace-only content, and empty JSON arrays/objects.
                    </p>
                    
                    <form method="POST" action="">
                        <button type="submit" name="search" class="btn btn-danger">
                            Search
                        </button>
                    </form>
                </div>
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <strong>Error:</strong> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search']) && !isset($error_message)): ?>
                <!-- Summary Panel -->
                <div class="card bg-dark border-warning mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h2 class="h4 mb-0">Summary</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="text-white">
                                    <strong>Total Characters Scanned:</strong><br>
                                    <span class="h5"><?php echo number_format($stats['total_scanned']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-white">
                                    <strong>With Missing Data:</strong><br>
                                    <span class="h5 text-warning"><?php echo number_format($stats['with_missing_data']); ?></span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="text-white">
                                    <strong>Complete Records:</strong><br>
                                    <span class="h5 text-success"><?php echo number_format($stats['total_scanned'] - $stats['with_missing_data']); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($stats['field_counts'])): ?>
                            <hr class="border-secondary">
                            <h5 class="text-white mb-3">Top Missing Fields (Top 5)</h5>
                            <div class="row">
                                <?php 
                                $top_fields = array_slice($stats['field_counts'], 0, 5, true);
                                foreach ($top_fields as $field => $count): 
                                ?>
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <span class="badge bg-danger me-2"><?php echo number_format($count); ?></span>
                                        <span class="text-white"><?php echo htmlspecialchars($field); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Results Table -->
                <?php if (!empty($results)): ?>
                    <div class="card bg-dark border-danger">
                        <div class="card-header bg-danger text-white">
                            <h2 class="h4 mb-0">Characters with Missing Data</h2>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-dark table-hover table-striped mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Character ID</th>
                                            <th>Character Name</th>
                                            <th>Missing Fields</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($results as $result): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars((string)$result['id']); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($result['name']); ?></strong>
                                                </td>
                                                <td>
                                                    <?php foreach ($result['missing_fields'] as $field): ?>
                                                        <span class="badge bg-danger me-1 mb-1"><?php echo htmlspecialchars($field); ?></span>
                                                    <?php endforeach; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])): ?>
                    <div class="alert alert-success">
                        <strong>Success!</strong> No characters with missing data found. All scanned characters have complete records.
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
include __DIR__ . '/../../includes/footer.php';
?>
