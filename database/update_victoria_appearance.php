<?php
/**
 * Update Victoria Sterling's Appearance
 * Updates the appearance field in the database with the expanded description
 */

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><title>Update Victoria Sterling Appearance</title></head><body>";
echo "<h1>Update Victoria Sterling Appearance</h1>";
echo "<pre>";

// Updated appearance from JSON file
$new_appearance = "Victoria Sterling presents an image of corporate perfection. In her early 40s at the time of her Embrace, she maintains an impeccably professional appearance. Her dark hair is always perfectly styled, her business attire is flawlessly tailored, and her posture radiates authority. She carries herself with the confidence of someone who has spent decades in boardrooms, and her piercing blue eyes miss nothing. Even in undeath, she maintains the polished exterior of a Fortune 500 executive. Physically, she stands at an average height with a lean, athletic build that suggests discipline rather than vanity—a body shaped by purpose, not indulgence. Her features are sharply defined: high cheekbones that catch shadows like architectural lines, a strong jaw that clenches only when necessary, and skin that maintains the healthy tone of someone who spent her mortal years under fluorescent office lights rather than desert sun. Her dark hair, styled in a precise bob that ends just above her shoulders, never seems to move out of place, as if even the undead stillness of her kind respects the order she demands. The most unsettling aspect, perhaps, is how her blue eyes have deepened in undeath—still analytical and commanding, but now they hold the predatory stillness of a Ventrue who has learned that true power comes not from visible dominance, but from the ability to command a room without raising a hand.";

// First, check if Victoria Sterling exists
$check_sql = "SELECT id, character_name, appearance FROM characters WHERE character_name = 'Victoria Sterling'";
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
$current_appearance = $row['appearance'];

echo "Found Victoria Sterling (ID: $character_id)\n\n";
echo "Current appearance length: " . strlen($current_appearance) . " characters\n";
echo "New appearance length: " . strlen($new_appearance) . " characters\n\n";

// Update the appearance
$update_sql = "UPDATE characters SET appearance = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $update_sql);

if (!$stmt) {
    die("Error preparing update: " . mysqli_error($conn) . "\n");
}

mysqli_stmt_bind_param($stmt, 'si', $new_appearance, $character_id);

if (mysqli_stmt_execute($stmt)) {
    $affected = mysqli_affected_rows($conn);
    if ($affected > 0) {
        echo "✓ Successfully updated Victoria Sterling's appearance!\n\n";
        
        // Verify the update
        $verify_sql = "SELECT appearance FROM characters WHERE id = ?";
        $verify_stmt = mysqli_prepare($conn, $verify_sql);
        mysqli_stmt_bind_param($verify_stmt, 'i', $character_id);
        mysqli_stmt_execute($verify_stmt);
        $verify_result = mysqli_stmt_get_result($verify_stmt);
        $verify_row = mysqli_fetch_assoc($verify_result);
        
        echo "Verification:\n";
        echo "Updated appearance (first 200 characters):\n";
        echo substr($verify_row['appearance'], 0, 200) . "...\n";
        echo "\n✓ Update confirmed!\n";
        
        mysqli_stmt_close($verify_stmt);
    } else {
        echo "No rows updated. Appearance may already match.\n";
    }
} else {
    echo "✗ Error updating appearance: " . mysqli_stmt_error($stmt) . "\n";
}

mysqli_stmt_close($stmt);
echo "</pre></body></html>";
mysqli_close($conn);
?>

