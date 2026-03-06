<?php
/**
 * Role Verification Helper
 * SECURITY: Verifies user role against database to prevent session tampering
 * 
 * This function should be called after session_start() and database connection
 * to ensure the user's role in the session matches what's in the database.
 * 
 * Usage:
 *   require_once 'includes/verify_role.php';
 *   $user_role = verifyUserRole($conn, $user_id);
 *   $is_admin = ($user_role === 'admin' || $user_role === 'storyteller');
 */

/**
 * Verify and update user role from database
 * 
 * @param mixed $conn Database connection (legacy compatibility, not used)
 * @param int $user_id User ID from session
 * @return string Verified user role (defaults to 'player' if invalid)
 */
function verifyUserRole($conn, $user_id) {
    // Get role from session first
    $user_role = $_SESSION['role'] ?? 'player';
    
    // SECURITY: Verify role against database to prevent session tampering
    // Only verify if user is logged in (not guest/bypass)
    if ($user_id > 0) {
        require_once __DIR__ . '/auth_bypass.php';
        require_once __DIR__ . '/supabase_client.php';
        
        // Skip verification if auth bypass is enabled (development mode)
        if (!isAuthBypassEnabled()) {
            $rows = supabase_table_get('users', [
                'select' => 'role',
                'id' => 'eq.' . (string) $user_id,
                'limit' => '1',
            ]);
            $db_user = !empty($rows) ? $rows[0] : null;
            
            if ($db_user) {
                // Override session role with database role for security
                $user_role = $db_user['role'];
                $_SESSION['role'] = $user_role;
            } else {
                // User not found in database - invalid session, default to player
                $user_role = 'player';
                $_SESSION['role'] = 'player';
            }
        }
    }
    
    return $user_role;
}

/**
 * Check if user has admin or storyteller privileges
 * 
 * @param string $user_role User role to check
 * @return bool True if user is admin or storyteller
 */
function isAdminUser($user_role) {
    return ($user_role === 'admin' || $user_role === 'storyteller');
}
