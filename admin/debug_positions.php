<?php
/**
 * Debug Camarilla Positions
 * Shows detailed information about what's in the database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';
require_once __DIR__ . '/../includes/camarilla_positions_helper.php';

$default_night = CAMARILLA_DEFAULT_NIGHT;

// Get all positions directly from database
$all_positions = db_fetch_all($conn, "SELECT * FROM camarilla_positions ORDER BY importance_rank ASC, category ASC, name ASC");

// Get positions with holders
$positions_data = get_all_positions_with_current_holders($default_night);

// Count vacant
$vacant_count = 0;
foreach ($positions_data as $data) {
    if (!$data['current_holder']) {
        $vacant_count++;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Camarilla Positions</title>
    <style>
        body { background: #1a0f0f; color: #fff; font-family: Arial, sans-serif; padding: 20px; }
        .section { margin: 30px 0; padding: 20px; background: #2a1f1f; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #666; padding: 8px; text-align: left; font-size: 12px; }
        th { background: #333; }
        tr:nth-child(even) { background: #3a2f2f; }
        .vacant { color: #ff6b6b; }
        .filled { color: #51cf66; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-box { padding: 15px; background: #333; border-radius: 5px; }
        .stat-box h3 { margin: 0 0 10px 0; color: #ffc107; }
        .stat-box .number { font-size: 24px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 Debug: Camarilla Positions</h1>
    
    <div class="stats">
        <div class="stat-box">
            <h3>Total Positions in DB</h3>
            <div class="number"><?php echo count($all_positions); ?></div>
        </div>
        <div class="stat-box">
            <h3>Positions with Data</h3>
            <div class="number"><?php echo count($positions_data); ?></div>
        </div>
        <div class="stat-box">
            <h3>Vacant Positions</h3>
            <div class="number vacant"><?php echo $vacant_count; ?></div>
        </div>
        <div class="stat-box">
            <h3>Filled Positions</h3>
            <div class="number filled"><?php echo count($positions_data) - $vacant_count; ?></div>
        </div>
    </div>
    
    <div class="section">
        <h2>All Positions from Database (Direct Query)</h2>
        <p>This is what's directly in the <code>camarilla_positions</code> table:</p>
        <table>
            <thead>
                <tr>
                    <th>Position ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Importance Rank</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($all_positions)): ?>
                    <tr><td colspan="5" class="vacant">⚠️ NO POSITIONS FOUND IN DATABASE!</td></tr>
                <?php else: ?>
                    <?php foreach ($all_positions as $pos): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($pos['position_id'] ?? 'N/A'); ?></code></td>
                            <td><strong><?php echo htmlspecialchars($pos['name'] ?? 'N/A'); ?></strong></td>
                            <td><?php echo htmlspecialchars($pos['category'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($pos['importance_rank'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars(substr($pos['description'] ?? '—', 0, 50)); ?>...</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="section">
        <h2>Positions with Current Holders (via Helper Function)</h2>
        <p>This is what <code>get_all_positions_with_current_holders()</code> returns:</p>
        <table>
            <thead>
                <tr>
                    <th>Position ID</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Current Holder</th>
                    <th>Holder Character ID</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($positions_data)): ?>
                    <tr><td colspan="6" class="vacant">⚠️ NO POSITIONS RETURNED FROM HELPER FUNCTION!</td></tr>
                <?php else: ?>
                    <?php foreach ($positions_data as $data): 
                        $pos = $data['position'];
                        $holder = $data['current_holder'];
                    ?>
                        <tr class="<?php echo $holder ? 'filled' : 'vacant'; ?>">
                            <td><code><?php echo htmlspecialchars($pos['position_id'] ?? 'N/A'); ?></code></td>
                            <td><strong><?php echo htmlspecialchars($pos['name'] ?? 'N/A'); ?></strong></td>
                            <td><?php echo htmlspecialchars($pos['category'] ?? '—'); ?></td>
                            <td>
                                <?php if ($holder): ?>
                                    <span class="filled">✅ FILLED</span>
                                    <?php if ($holder['is_acting']): ?>
                                        <span style="color: #ffc107;">(Acting)</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="vacant">❌ VACANT</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($holder): ?>
                                    <?php echo htmlspecialchars($holder['character_name'] ?? $holder['assignment_character_id'] ?? 'Unknown'); ?>
                                <?php else: ?>
                                    <span class="vacant">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($holder && !empty($holder['character_id'])): ?>
                                    <?php echo htmlspecialchars($holder['character_id']); ?>
                                <?php elseif ($holder): ?>
                                    <span style="color: #ffc107;">No DB match (ID: <?php echo htmlspecialchars($holder['assignment_character_id'] ?? 'N/A'); ?>)</span>
                                <?php else: ?>
                                    <span class="vacant">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="section">
        <h2>Vacant Positions Only</h2>
        <p>These are the positions that show as vacant:</p>
        <table>
            <thead>
                <tr>
                    <th>Position ID</th>
                    <th>Name</th>
                    <th>Category</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $vacant_list = [];
                foreach ($positions_data as $data) {
                    if (!$data['current_holder']) {
                        $vacant_list[] = $data['position'];
                    }
                }
                ?>
                <?php if (empty($vacant_list)): ?>
                    <tr><td colspan="3" class="filled">✅ All positions are filled!</td></tr>
                <?php else: ?>
                    <?php foreach ($vacant_list as $pos): ?>
                        <tr class="vacant">
                            <td><code><?php echo htmlspecialchars($pos['position_id'] ?? 'N/A'); ?></code></td>
                            <td><strong><?php echo htmlspecialchars($pos['name'] ?? 'N/A'); ?></strong></td>
                            <td><?php echo htmlspecialchars($pos['category'] ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="section">
        <h2>Database Query Debug</h2>
        <p><strong>Default Night:</strong> <?php echo $default_night; ?></p>
        <p><strong>Query Used:</strong> <code>SELECT * FROM camarilla_positions ORDER BY importance_rank ASC, category ASC, name ASC</code></p>
        <p><strong>Rows Returned:</strong> <?php echo count($all_positions); ?></p>
        
        <?php if (count($all_positions) !== count($positions_data)): ?>
            <p class="vacant">⚠️ WARNING: Database query returned <?php echo count($all_positions); ?> positions, but helper function returned <?php echo count($positions_data); ?> positions!</p>
        <?php endif; ?>
    </div>
    
    <p style="margin-top: 30px;">
        <a href="camarilla_positions.php" style="color: #ffc107;">← Back to Camarilla Positions</a> |
        <a href="list_positions.php" style="color: #ffc107;">View Simple List</a>
    </p>
</body>
</html>







