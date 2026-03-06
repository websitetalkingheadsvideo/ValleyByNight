<?php
/**
 * Reset Admin Password - TEMPORARY FILE - DELETE AFTER USE
 * Resets the admin user password
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/supabase_client.php';

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
$updateResult = supabase_rest_request(
    'PATCH',
    '/rest/v1/users',
    ['username' => 'eq.admin'],
    ['password' => $password_hash],
    ['Prefer: return=representation']
);

$result = $updateResult['error'] === null;
$affected = 0;
if ($result && is_array($updateResult['data'])) {
    $affected = count($updateResult['data']);
}

if ($result && $affected > 0) {
    echo "✓ Admin password updated successfully!\n";
    echo "  New password: $new_password\n";
    echo "\n⚠ IMPORTANT: Delete this file after use!\n";
} else {
    echo "✗ Failed to update password.\n";
    if ($affected == 0) {
        echo "  No admin user found to update.\n";
    } else {
        echo "  Error: " . $updateResult['error'] . "\n";
    }
}
?>
