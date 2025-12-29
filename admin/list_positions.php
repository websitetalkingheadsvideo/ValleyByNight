<?php
/**
 * List All Camarilla Positions from Database
 * Quick script to see what positions are actually in the database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

$positions_query = "SELECT position_id, name, category, importance_rank, description 
                    FROM camarilla_positions 
                    ORDER BY importance_rank ASC, category ASC, name ASC";
$positions = db_fetch_all($conn, $positions_query);

?>
<!DOCTYPE html>
<html>
<head>
    <title>All Camarilla Positions</title>
    <style>
        body { background: #1a0f0f; color: #fff; font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #666; padding: 8px; text-align: left; }
        th { background: #333; }
        tr:nth-child(even) { background: #2a1f1f; }
        .category { color: #ffc107; font-weight: bold; }
    </style>
</head>
<body>
    <h1>All Camarilla Positions in Database</h1>
    <p>Total: <?php echo count($positions); ?> positions</p>
    
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
            <?php if (empty($positions)): ?>
                <tr>
                    <td colspan="5">No positions found in database</td>
                </tr>
            <?php else: ?>
                <?php foreach ($positions as $pos): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($pos['position_id'] ?? 'N/A'); ?></td>
                        <td><strong><?php echo htmlspecialchars($pos['name'] ?? 'N/A'); ?></strong></td>
                        <td class="category"><?php echo htmlspecialchars($pos['category'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($pos['importance_rank'] ?? '—'); ?></td>
                        <td><?php echo htmlspecialchars($pos['description'] ?? '—'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <p style="margin-top: 30px;">
        <a href="camarilla_positions.php" style="color: #ffc107;">← Back to Camarilla Positions</a>
    </p>
</body>
</html>







