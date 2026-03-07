<?php
/**
 * Reset Admin Password / Create Admin User
 * Web page: create or reset the admin user in Supabase.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/supabase_client.php';

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['password'] ?? '');
    if (strlen($new_password) < 8) {
        $message = 'Password must be at least 8 characters.';
    } else {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        try {
            $rows = supabase_table_get('users', [
                'select' => 'id',
                'username' => 'eq.admin',
                'limit' => '1',
            ]);
        } catch (Throwable $e) {
            $message = 'Could not read users: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
        if ($message === '' && !empty($rows)) {
            $updateResult = supabase_rest_request(
                'PATCH',
                '/rest/v1/users',
                ['username' => 'eq.admin'],
                ['password' => $password_hash],
                ['Prefer: return=minimal']
            );
            if ($updateResult['error'] !== null) {
                $message = 'Update failed: ' . htmlspecialchars($updateResult['error'], ENT_QUOTES, 'UTF-8');
            }
        } elseif ($message === '') {
            $insertResult = supabase_rest_request(
                'POST',
                '/rest/v1/users',
                [],
                [
                    'username' => 'admin',
                    'email' => 'admin@local',
                    'password' => $password_hash,
                    'role' => 'admin',
                    'email_verified' => true,
                    'created_at' => gmdate('c'),
                ],
                ['Prefer: return=representation']
            );
            if ($insertResult['error'] !== null) {
                $message = 'Create failed: ' . htmlspecialchars($insertResult['error'], ENT_QUOTES, 'UTF-8');
            }
        }
        // Verify: re-fetch and test password so we only show success when login will work
        if ($message === '') {
            $check = supabase_table_get('users', [
                'select' => 'id,username,password',
                'username' => 'eq.admin',
                'limit' => '1',
            ]);
            $stored = !empty($check) ? $check[0] : null;
            if ($stored && password_verify($new_password, $stored['password'])) {
                $message = 'Admin password set. Log in with username: admin (exactly, lowercase).';
                $success = true;
            } else {
                $message = 'Password did not save correctly (Supabase may have rejected the update). Try again or check Supabase Table Editor for user "admin".';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Admin Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-white min-vh-100 d-flex align-items-center justify-content-center">
    <div class="container" style="max-width: 400px;">
        <h1 class="h4 mb-4">Create / Reset Admin</h1>
        <?php if ($message): ?>
            <div class="alert <?php echo $success ? 'alert-success' : 'alert-danger'; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <div class="mb-3">
                <label for="password" class="form-label">New password (min 8 characters)</label>
                <input type="password" class="form-control bg-dark text-white border-secondary" id="password" name="password" required minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary">Create or reset admin</button>
        </form>
        <p class="mt-3 text-white"><a href="index.php" class="text-white">← Back to app</a></p>
    </div>
</body>
</html>
