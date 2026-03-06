<?php
/**
 * Registration Process Handler
 * Validates input, creates user account, sends verification email
 */
declare(strict_types=1);

session_start();
require_once __DIR__ . '/supabase_client.php';
require_once __DIR__ . '/email_helper_simple.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php");
    exit();
}

// SECURITY: Validate CSRF token
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token']) || 
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $_SESSION['error'] = "Invalid security token. Please try again.";
    header("Location: register.php");
    exit();
}

// Get and sanitize input
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

// Validation
$errors = [];

// Username validation
if (empty($username)) {
    $errors[] = "Username is required";
} elseif (strlen($username) < 3 || strlen($username) > 50) {
    $errors[] = "Username must be between 3 and 50 characters";
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    $errors[] = "Username can only contain letters, numbers, and underscores";
}

// Email validation
if (empty($email)) {
    $errors[] = "Email is required";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format";
}

// Password validation
if (empty($password)) {
    $errors[] = "Password is required";
} elseif (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters";
}

// Confirm password
if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match";
}

// Check for existing username
if (empty($errors)) {
    $usernameRows = supabase_table_get('users', [
        'select' => 'id',
        'username' => 'eq.' . $username,
        'limit' => '1'
    ]);
    if (!empty($usernameRows)) {
        $errors[] = "Username already exists";
    }
}

// Check for existing email
if (empty($errors)) {
    $emailRows = supabase_table_get('users', [
        'select' => 'id',
        'email' => 'eq.' . $email,
        'limit' => '1'
    ]);
    if (!empty($emailRows)) {
        $errors[] = "Email already registered";
    }
}

// If validation failed, redirect back with errors
if (!empty($errors)) {
    $_SESSION['error'] = implode(". ", $errors);
    header("Location: register.php");
    exit();
}

// Generate verification token
$verification_token = bin2hex(random_bytes(32)); // 64 character hex string
$verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));

// Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Insert user into database
$insertResult = supabase_rest_request(
    'POST',
    '/rest/v1/users',
    ['select' => 'id'],
    [[
        'username' => $username,
        'email' => $email,
        'password' => $password_hash,
        'role' => 'player',
        'email_verified' => false,
        'verification_token' => $verification_token,
        'verification_expires' => $verification_expires,
        'created_at' => gmdate('c')
    ]],
    ['Prefer: return=representation']
);

if ($insertResult['error'] !== null) {
    $_SESSION['error'] = "Registration failed: " . $insertResult['error'];
    header("Location: register.php");
    exit();
}

// Send verification email
$email_sent = send_verification_email($email, $username, $verification_token);

if ($email_sent) {
    $_SESSION['success'] = "Account created! Please check your email to verify your account.";
} else {
    $_SESSION['success'] = "Account created! Email verification is temporarily unavailable. Please contact support.";
}

// Redirect to login page with success message
header("Location: login.php");
exit();
?>

