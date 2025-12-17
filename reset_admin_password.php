<?php
/**
 * Reset Admin Password - TEMPORARY FILE - DELETE AFTER USE
 * Resets the admin user password
 */

require_once __DIR__ . '/includes/connect.php';

echo "=== Admin Password Reset Tool ===\n\n";

// Get new password from command line or prompt
if (isset($argv[1])) {
    $new_password = $argv[1];
} else {
    echo "Enter new password for admin user: ";
    $new_password = trim(fgets(STDIN));
}

if (empty($new_password)) {
    die("Password cannot be empty!\n");
}

// Hash the new password
$password_hash = password_hash($new_password, PASSWORD_DEFAULT);

// Update admin user password
$stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE username = 'admin'");
mysqli_stmt_bind_param($stmt, "s", $password_hash);
$result = mysqli_stmt_execute($stmt);
$affected = mysqli_affected_rows($conn);
mysqli_stmt_close($stmt);

if ($result && $affected > 0) {
    echo "✓ Admin password updated successfully!\n";
    echo "  New password: $new_password\n";
    echo "\n⚠ IMPORTANT: Delete this file after use!\n";
} else {
    echo "✗ Failed to update password.\n";
    if ($affected == 0) {
        echo "  No admin user found to update.\n";
    } else {
        echo "  Error: " . mysqli_error($conn) . "\n";
    }
}

mysqli_close($conn);
?>
