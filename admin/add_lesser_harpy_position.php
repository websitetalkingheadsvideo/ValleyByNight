<?php
/**
 * Add Lesser Harpy Position
 * Creates Lesser Harpy position in the Camarilla positions table
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/../includes/connect.php';

$position_id = 'lesser_harpy';
$position_name = 'Lesser Harpy';
$category = 'Court';
$description = 'The lesser Harpies serve the Court Harpy and share in some of the power of that position. In most situations, the lesser Harpies act as coterie, unless the Court Harpy has intentionally appointed Kindred who hate each other. Lesser Harpies can remove temporary Status just as the head harpy removes permanent Status. Many harpies keep several lesser harpies in their service, empowering them to act as a proxy. A harpy can only be in so many places at once, and having a trusted set of eyes and ears around while in meetings is very handy.';
$importance_rank = 6; // Lower than Court Harpy but still important

echo "<h1>Add Lesser Harpy Position</h1>\n";
echo "<pre>\n";

try {
    // Step 1: Check if Lesser Harpy position already exists
    echo "Step 1: Checking if Lesser Harpy position exists...\n";
    $position_check = db_fetch_one($conn, "SELECT position_id, name FROM camarilla_positions WHERE position_id = ?", "s", [$position_id]);
    
    if ($position_check) {
        echo "  ⚠️  Lesser Harpy position already exists (ID: {$position_check['position_id']})\n";
        echo "  Position: {$position_check['name']}\n";
        echo "  No changes made.\n";
    } else {
        echo "  Position doesn't exist. Creating it...\n";
        
        // Step 2: Insert the position
        $insert_position = "INSERT INTO camarilla_positions (position_id, name, category, description, importance_rank) 
                           VALUES (?, ?, ?, ?, ?)";
        $result = db_execute($conn, $insert_position, "ssssi", [
            $position_id,
            $position_name,
            $category,
            $description,
            $importance_rank
        ]);
        
        if ($result === false) {
            throw new Exception("Failed to create position: " . mysqli_error($conn));
        }
        
        echo "  ✅ Created Lesser Harpy position\n";
        echo "  Position ID: $position_id\n";
        echo "  Name: $position_name\n";
        echo "  Category: $category\n";
        echo "  Importance Rank: $importance_rank\n";
        echo "  Description: " . substr($description, 0, 100) . "...\n";
    }
    
    echo "\n✅ Success!\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "</pre>\n";
echo "<a href='camarilla_positions.php' class='btn btn-primary'>Back to Positions</a>\n";
mysqli_close($conn);
?>
