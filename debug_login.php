<?php
/**
 * Debug Login - TEMPORARY FILE - DELETE AFTER USE
 * Checks if admin user exists and verifies password
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/supabase_client.php';

// Get username from command line or default to 'admin'
$test_username = $argv[1] ?? 'admin';

echo "=== Login Debug Tool ===\n";
echo "Testing username: $test_username\n\n";

// Check if user exists
$rows = supabase_table_get('users', [
    'select' => 'id,username,role,email_verified,password',
    'username' => 'eq.' . $test_username,
    'limit' => '1'
]);
$user = !empty($rows) ? $rows[0] : null;

if ($user) {
    $password_length = isset($user['password']) ? strlen((string) $user['password']) : 0;
    echo "✓ User found!\n";
    echo "  ID: {$user['id']}\n";
    echo "  Username: {$user['username']}\n";
    echo "  Role: {$user['role']}\n";
    echo "  Email Verified: " . ($user['email_verified'] ? 'YES' : 'NO') . "\n";
    echo "  Password hash length: {$password_length} chars\n\n";
    
    if (!$user['email_verified']) {
        echo "⚠ WARNING: Email is not verified!\n";
        echo "  This will prevent login even with correct password.\n\n";
    }
    
    echo "To test password, run:\n";
    echo "  php debug_login.php '$test_username' 'your_password'\n";
    
    // If password provided as second argument, test it
    if (isset($argv[2])) {
        $test_password = $argv[2];

        if (isset($user['password']) && password_verify($test_password, (string) $user['password'])) {
            echo "\n✓ PASSWORD MATCHES!\n";
            echo "  The password is correct.\n";
            if (!$user['email_verified']) {
                echo "  But login will fail because email is not verified.\n";
            } else {
                echo "  Login should work if email is verified.\n";
            }
        } else {
            echo "\n✗ PASSWORD DOES NOT MATCH\n";
            echo "  The password you entered is incorrect.\n";
        }
    }
} else {
    echo "✗ User NOT found!\n";
    echo "  Username '$test_username' does not exist in database.\n";
    echo "  Check for typos or case sensitivity.\n\n";
    
    // List all users
    $all_users = supabase_table_get('users', [
        'select' => 'username,role',
        'order' => 'username.asc'
    ]);
    if (!empty($all_users)) {
        echo "Available users:\n";
        foreach ($all_users as $row) {
            echo "  - {$row['username']} ({$row['role']})\n";
        }
    }
}
?>

