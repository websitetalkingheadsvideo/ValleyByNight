<?php
/**
 * Login Process Handler
 * Authenticates user credentials securely via Supabase REST.
 */
declare(strict_types=1);

session_start();
error_reporting(2);

require_once __DIR__ . '/supabase_client.php';

// Calculate base path for redirects (works with subdirectories like /vbn/)
// For /vbn/includes/login_process.php -> /vbn/
// For /includes/login_process.php -> /
$script_path = $_SERVER['SCRIPT_NAME'];
$path_parts = explode('/', trim($script_path, '/'));
// Remove 'includes' from path parts if present (it's not the app subdirectory)
$path_parts = array_filter($path_parts, function($part) {
    return $part !== 'includes' && !preg_match('/\.php$/', $part);
});
// If first remaining part is a directory, it's the app subdirectory
$path_parts = array_values($path_parts);
if (!empty($path_parts[0])) {
    $base_path = '/' . $path_parts[0] . '/';
} else {
    $base_path = '/';
}

// SECURITY: Validate CSRF token
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || 
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid security token. Please try again.";
        header("Location: " . $base_path . "login.php");
        exit();
    }
}

// Check if login is disabled
$loginDisableFile = dirname(__DIR__) . '/config/login_disable.json';
$loginDisabled = false;
$disabledUntil = null;

if (file_exists($loginDisableFile)) {
    $config = json_decode(file_get_contents($loginDisableFile), true);
    if ($config && isset($config['disabled']) && $config['disabled'] === true) {
        $loginDisabled = true;
        $disabledUntil = $config['disabled_until'] ?? null;
        
        // Check if the disable period has expired
        if ($disabledUntil) {
            $now = time();
            $until = strtotime($disabledUntil);
            if ($now >= $until) {
                // Expired - re-enable login
                $config['disabled'] = false;
                $config['disabled_until'] = null;
                file_put_contents($loginDisableFile, json_encode($config, JSON_PRETTY_PRINT));
                $loginDisabled = false;
            }
        }
    }
}

// If login is disabled, redirect back with error
if ($loginDisabled) {
    $_SESSION['error'] = "Login is currently disabled. Please try again later.";
    header("Location: " . $base_path . "login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Username and password are required";
        header("Location: " . $base_path . "login.php");
        exit();
    }
    
    $rows = supabase_table_get('users', [
        'select' => 'id,username,password,role,email_verified',
        'username' => 'eq.' . $username,
        'limit' => '1'
    ]);
    $user = !empty($rows) ? $rows[0] : null;
    
    if ($user) {
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Check if email is verified
            if (!$user['email_verified']) {
                $_SESSION['error'] = "Please verify your email address before logging in. Check your inbox for the verification link.";
                header("Location: " . $base_path . "login.php");
                exit();
            }
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            $updateResult = supabase_rest_request(
                'PATCH',
                '/rest/v1/users',
                ['id' => 'eq.' . (string) $user['id']],
                ['last_login' => gmdate('c')],
                ['Prefer: return=minimal']
            );
            if ($updateResult['error'] !== null) {
                error_log('Failed updating last_login: ' . $updateResult['error']);
            }
            
            // Redirect to dashboard
            header("Location: " . $base_path . "index.php");
            exit();
        } else {
            // Invalid password
            $_SESSION['error'] = "Invalid username or password";
            header("Location: " . $base_path . "login.php");
            exit();
        }
    } else {
        // User not found
        $_SESSION['error'] = "Invalid username or password";
        header("Location: " . $base_path . "login.php");
        exit();
    }
} else {
    header("Location: " . $base_path . "login.php");
    exit();
}
?>