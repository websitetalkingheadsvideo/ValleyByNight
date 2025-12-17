<?php
/**
 * Debug Login - TEMPORARY FILE - DELETE AFTER USE
 * Checks if admin user exists and verifies password
 */

require_once __DIR__ . '/includes/connect.php';

// Get username from command line or default to 'admin'
$test_username = $argv[1] ?? 'admin';

echo "=== Login Debug Tool ===\n";
echo "Testing username: $test_username\n\n";

// Check if user exists
$user = db_fetch_one($conn,
    "SELECT id, username, role, email_verified, 
            CHAR_LENGTH(password) as password_length 
     FROM users WHERE username = ?",
    "s",
    [$test_username]
);

if ($user) {
    echo "✓ User found!\n";
    echo "  ID: {$user['id']}\n";
    echo "  Username: {$user['username']}\n";
    echo "  Role: {$user['role']}\n";
    echo "  Email Verified: " . ($user['email_verified'] ? 'YES' : 'NO') . "\n";
    echo "  Password hash length: {$user['password_length']} chars\n\n";
    
    if (!$user['email_verified']) {
        echo "⚠ WARNING: Email is not verified!\n";
        echo "  This will prevent login even with correct password.\n\n";
    }
    
    echo "To test password, run:\n";
    echo "  php debug_login.php '$test_username' 'your_password'\n";
    
    // If password provided as second argument, test it
    if (isset($argv[2])) {
        $test_password = $argv[2];
        
        // Get the actual password hash
        $hash_result = db_fetch_one($conn,
            "SELECT password FROM users WHERE username = ?",
            "s",
            [$test_username]
        );
        
        if ($hash_result && password_verify($test_password, $hash_result['password'])) {
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
    $all_users = mysqli_query($conn, "SELECT username, role FROM users ORDER BY username");
    if ($all_users && mysqli_num_rows($all_users) > 0) {
        echo "Available users:\n";
        while ($row = mysqli_fetch_assoc($all_users)) {
            echo "  - {$row['username']} ({$row['role']})\n";
        }
    }
}

mysqli_close($conn);
?>

