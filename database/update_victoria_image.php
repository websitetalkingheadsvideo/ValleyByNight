<?php
/**
 * Update Victoria Sterling's Character Image
 * Updates the character_image field in the database
 */

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Update Victoria Sterling Character Image</title></head><body>";
echo "<h1>Update Victoria Sterling Character Image</h1>";
echo "<pre>";

$image_filename = "Victoria Sterling.png";

// First, check if Victoria Sterling exists
$check_sql = "SELECT id, character_name, character_image FROM characters WHERE character_name = 'Victoria Sterling'";
$result = mysqli_query($conn, $check_sql);

if (!$result) {
    die("Error checking character: " . mysqli_error($conn) . "\n");
}

if (mysqli_num_rows($result) == 0) {
    echo "✗ Victoria Sterling not found in database.\n";
    echo "Please import the character first using the import script.\n";
    echo "</pre></body></html>";
    mysqli_close($conn);
    exit;
}

$row = mysqli_fetch_assoc($result);
$character_id = $row['id'];
$current_image = $row['character_image'];

echo "Found Victoria Sterling (ID: $character_id)\n\n";
echo "Current character_image: '" . ($current_image ?: '(empty)') . "'\n";
echo "New character_image: '$image_filename'\n\n";

// Update the character_image
$update_sql = "UPDATE characters SET character_image = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $update_sql);

if (!$stmt) {
    die("Error preparing update: " . mysqli_error($conn) . "\n");
}

mysqli_stmt_bind_param($stmt, 'si', $image_filename, $character_id);

if (mysqli_stmt_execute($stmt)) {
    $affected = mysqli_affected_rows($conn);
    if ($affected > 0) {
        echo "✓ Successfully updated Victoria Sterling's character_image!\n\n";
        
        // Verify the update
        $verify_sql = "SELECT character_image FROM characters WHERE id = ?";
        $verify_stmt = mysqli_prepare($conn, $verify_sql);
        mysqli_stmt_bind_param($verify_stmt, 'i', $character_id);
        mysqli_stmt_execute($verify_stmt);
        $verify_result = mysqli_stmt_get_result($verify_stmt);
        $verify_row = mysqli_fetch_assoc($verify_result);
        
        echo "Verification:\n";
        echo "Character Image: '{$verify_row['character_image']}'\n";
        echo "\n✓ Update confirmed!\n";
        
        mysqli_stmt_close($verify_stmt);
    } else {
        echo "No rows updated. Image may already be set to this value.\n";
    }
} else {
    echo "✗ Error updating character_image: " . mysqli_stmt_error($stmt) . "\n";
}

mysqli_stmt_close($stmt);
echo "</pre></body></html>";
mysqli_close($conn);
?>

