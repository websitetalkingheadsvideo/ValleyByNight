<?php
/**
 * Add Missing Camarilla Positions
 * Script to add the newly discovered positions to the database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

// Define ALL standard Camarilla positions from all_camarilla_positions.txt
// This ensures all positions exist in the database, skipping any that already exist
$positions_to_add = [
    // Leadership Positions
    [
        'position_id' => 'prince',
        'name' => 'Prince',
        'category' => 'Leadership',
        'description' => 'The ruler of the domain. The Prince has ultimate authority over all Kindred within their domain and is responsible for enforcing the Traditions.',
        'importance_rank' => 1
    ],
    [
        'position_id' => 'seneschal',
        'name' => 'Seneschal',
        'category' => 'Leadership',
        'description' => 'The Seneschal is the Prince\'s second-in-command and chief administrator. They handle the day-to-day administration of the domain when the Prince is unavailable, manage court logistics, and serve as the Prince\'s primary advisor.',
        'importance_rank' => 2
    ],
    // Law Enforcement Positions
    [
        'position_id' => 'sheriff',
        'name' => 'Sheriff',
        'category' => 'Law Enforcement',
        'description' => 'The Sheriff is the enforcer of the Prince\'s will and domain security. They investigate violations of the Traditions, maintain order, and protect the domain from threats.',
        'importance_rank' => 3
    ],
    [
        'position_id' => 'scourge',
        'name' => 'Scourge',
        'category' => 'Law Enforcement',
        'description' => 'The Scourge hunts down unauthorized vampires, caitiff, and threats to the Masquerade. This position is responsible for eliminating those who threaten the domain\'s security and the Traditions.',
        'importance_rank' => 6
    ],
    // Social Positions
    [
        'position_id' => 'harpy',
        'name' => 'Harpy',
        'category' => 'Social',
        'description' => 'The Harpy maintains social standing and spreads information and gossip throughout the domain. They track prestige, manage social hierarchies, and serve as the court\'s social arbiter.',
        'importance_rank' => 4
    ],
    [
        'position_id' => 'keeper_of_elysium',
        'name' => 'Keeper of Elysium',
        'category' => 'Social',
        'description' => 'The Keeper of Elysium maintains and protects Elysium locations, ensuring they remain safe havens where violence is forbidden. This position is responsible for the security and sanctity of designated Elysium spaces.',
        'importance_rank' => 5
    ],
    // Clan Representative Positions - Primogen Council
    // Note: Standardized on [clan]_primogen pattern with "Clan Representative" category
    // Run cleanup_duplicate_primogen.php first to remove primogen_[clan] duplicates
    [
        'position_id' => 'brujah_primogen',
        'name' => 'Brujah Primogen',
        'category' => 'Clan Representative',
        'description' => 'The Brujah Primogen represents the Brujah clan on the Primogen Council, speaking for clan interests in domain governance.',
        'importance_rank' => 7
    ],
    [
        'position_id' => 'gangrel_primogen',
        'name' => 'Gangrel Primogen',
        'category' => 'Clan Representative',
        'description' => 'The Gangrel Primogen represents the Gangrel clan on the Primogen Council, speaking for clan interests in domain governance.',
        'importance_rank' => 7
    ],
    [
        'position_id' => 'malkavian_primogen',
        'name' => 'Malkavian Primogen',
        'category' => 'Clan Representative',
        'description' => 'The Malkavian Primogen represents the Malkavian clan on the Primogen Council, speaking for clan interests in domain governance.',
        'importance_rank' => 7
    ],
    [
        'position_id' => 'nosferatu_primogen',
        'name' => 'Nosferatu Primogen',
        'category' => 'Clan Representative',
        'description' => 'The Nosferatu Primogen represents the Nosferatu clan on the Primogen Council, speaking for clan interests in domain governance.',
        'importance_rank' => 7
    ],
    [
        'position_id' => 'toreador_primogen',
        'name' => 'Toreador Primogen',
        'category' => 'Clan Representative',
        'description' => 'The Toreador Primogen represents the Toreador clan on the Primogen Council, speaking for clan interests in domain governance.',
        'importance_rank' => 7
    ],
    [
        'position_id' => 'tremere_primogen',
        'name' => 'Tremere Primogen',
        'category' => 'Clan Representative',
        'description' => 'The Tremere Primogen represents the Tremere clan on the Primogen Council, speaking for clan interests in domain governance.',
        'importance_rank' => 7
    ],
    [
        'position_id' => 'ventrue_primogen',
        'name' => 'Ventrue Primogen',
        'category' => 'Clan Representative',
        'description' => 'The Ventrue Primogen represents the Ventrue clan on the Primogen Council, speaking for clan interests in domain governance.',
        'importance_rank' => 7
    ],
    // Support Positions
    [
        'position_id' => 'talon',
        'name' => 'Talon',
        'category' => 'Support',
        'description' => 'The Talon is an assistant to the Harpy, helping to maintain social standing records and spread information throughout the domain. Multiple Talons may exist.',
        'importance_rank' => 8
    ],
    [
        'position_id' => 'whip',
        'name' => 'Whip',
        'category' => 'Support',
        'description' => 'The Whip is a clan representative who enforces clan will, serving as a secondary representative to the Primogen. This is an optional position that may exist per clan.',
        'importance_rank' => 9
    ]
];

$added = [];
$skipped = [];
$errors = [];

foreach ($positions_to_add as $position) {
    try {
        // Check if position already exists
        $check_query = "SELECT position_id FROM camarilla_positions WHERE position_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, 's', $position['position_id']);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_fetch_assoc($check_result)) {
            $skipped[] = $position['name'] . ' (already exists)';
            continue;
        }
        
        // Insert new position
        $query = "INSERT INTO camarilla_positions (position_id, name, category, description, importance_rank) 
                 VALUES (?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
        }
        
        mysqli_stmt_bind_param($stmt, 'ssssi', 
            $position['position_id'],
            $position['name'],
            $position['category'],
            $position['description'],
            $position['importance_rank']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $added[] = $position['name'];
        } else {
            throw new Exception('Failed to execute: ' . mysqli_stmt_error($stmt));
        }
        
        mysqli_stmt_close($stmt);
        
    } catch (Exception $e) {
        $errors[] = $position['name'] . ': ' . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Missing Positions - Results</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #1a0f0f; color: #fff; padding: 20px; }
        .success { color: #28a745; }
        .warning { color: #ffc107; }
        .error { color: #dc3545; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add Missing Camarilla Positions - Results</h1>
        
        <?php if (!empty($added)): ?>
            <div class="alert alert-success">
                <h3>Successfully Added (<?php echo count($added); ?>):</h3>
                <ul>
                    <?php foreach ($added as $name): ?>
                        <li class="success"><?php echo htmlspecialchars($name); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($skipped)): ?>
            <div class="alert alert-warning">
                <h3>Skipped (<?php echo count($skipped); ?>):</h3>
                <ul>
                    <?php foreach ($skipped as $name): ?>
                        <li class="warning"><?php echo htmlspecialchars($name); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <h3>Errors (<?php echo count($errors); ?>):</h3>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li class="error"><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <a href="camarilla_positions.php" class="btn btn-primary">Back to Positions</a>
    </div>
</body>
</html>

