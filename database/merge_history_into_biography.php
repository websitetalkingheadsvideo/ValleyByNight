<?php
/**
 * Database Migration: Merge history into biography (biography is canonical)
 * 
 * This script merges the history field into biography, making biography the single source of truth.
 * 
 * Migration Rules:
 * - If biography is non-empty, preserve it (do not overwrite)
 * - If biography is empty/null and history has content, copy history to biography
 * - If both have content, append history to biography with separator (only when biography exists but history has additional content)
 * 
 * This script is idempotent - safe to run multiple times.
 * 
 * Run via browser: database/merge_history_into_biography.php
 * Or via CLI: php database/merge_history_into_biography.php
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Detect execution context
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    // Web execution
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header("Location: ../login.php");
        exit();
    }
    echo "<!DOCTYPE html><html><head><title>Merge History into Biography</title></head><body><pre>";
}

require_once __DIR__ . '/../includes/connect.php';

if (!$conn) {
    $error = "Database connection failed. Please check your configuration.";
    if ($is_cli) {
        echo "ERROR: {$error}\n";
        exit(1);
    } else {
        echo "<p style='color: red;'>{$error}</p></body></html>";
        exit();
    }
}

echo $is_cli ? "Starting migration: Merge history into biography\n" : "<h2>Starting migration: Merge history into biography</h2>\n";
echo $is_cli ? str_repeat("=", 60) . "\n" : "<hr>\n";

// Step 1: Check if history column exists
$check_history = mysqli_query($conn, "SHOW COLUMNS FROM characters LIKE 'history'");
$has_history = ($check_history && mysqli_num_rows($check_history) > 0);

if (!$has_history) {
    echo $is_cli ? "ℹ️  Info: 'history' column does not exist in characters table. Migration not needed.\n" : "<p>ℹ️  Info: 'history' column does not exist in characters table. Migration not needed.</p>\n";
    if (!$is_cli) {
        echo "</pre></body></html>";
    }
    exit(0);
}

echo $is_cli ? "✓ Found 'history' column in characters table\n" : "<p>✓ Found 'history' column in characters table</p>\n";

// Step 2: Count characters with history data
$count_query = "SELECT COUNT(*) as count FROM characters WHERE history IS NOT NULL AND history != '' AND TRIM(history) != ''";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$history_count = (int)($count_row['count'] ?? 0);

echo $is_cli ? "Found {$history_count} character(s) with history data\n" : "<p>Found {$history_count} character(s) with history data</p>\n";

if ($history_count === 0) {
    echo $is_cli ? "ℹ️  No characters with history data to migrate.\n" : "<p>ℹ️  No characters with history data to migrate.</p>\n";
    if (!$is_cli) {
        echo "</pre></body></html>";
    }
    exit(0);
}

// Step 3: Perform migration
// Strategy:
// 1. If biography is empty/null and history has content: copy history to biography
// 2. If biography has content and history has content: append history to biography with separator
// 3. If biography has content and history is empty: leave biography unchanged

$separator = "\n\n---\n\n";

// Case 1: biography is empty/null, history has content - copy history to biography
$update1_sql = "UPDATE characters 
                SET biography = history 
                WHERE (biography IS NULL OR biography = '' OR TRIM(biography) = '')
                AND history IS NOT NULL 
                AND history != '' 
                AND TRIM(history) != ''";

$result1 = mysqli_query($conn, $update1_sql);
if (!$result1) {
    $error = "Error in update case 1: " . mysqli_error($conn);
    if ($is_cli) {
        echo "❌ {$error}\n";
        exit(1);
    } else {
        echo "<p style='color: red;'>❌ {$error}</p></body></html>";
        exit();
    }
}
$affected1 = mysqli_affected_rows($conn);
echo $is_cli ? "✓ Case 1: Copied history to empty biography for {$affected1} character(s)\n" : "<p>✓ Case 1: Copied history to empty biography for {$affected1} character(s)</p>\n";

// Case 2: biography has content, history has content - append history to biography
// Use HEX() comparison to avoid collation mismatch (compares binary representation)
$update2_sql = "UPDATE characters 
                SET biography = CONCAT(biography, ?, history)
                WHERE biography IS NOT NULL 
                AND biography != '' 
                AND TRIM(biography) != ''
                AND history IS NOT NULL 
                AND history != '' 
                AND TRIM(history) != ''
                AND HEX(biography) != HEX(history)";

$stmt2 = mysqli_prepare($conn, $update2_sql);
if (!$stmt2) {
    $error = "Error preparing update case 2: " . mysqli_error($conn);
    if ($is_cli) {
        echo "❌ {$error}\n";
        exit(1);
    } else {
        echo "<p style='color: red;'>❌ {$error}</p></body></html>";
        exit();
    }
}

mysqli_stmt_bind_param($stmt2, 's', $separator);
if (!mysqli_stmt_execute($stmt2)) {
    $error = "Error executing update case 2: " . mysqli_stmt_error($stmt2);
    if ($is_cli) {
        echo "❌ {$error}\n";
        exit(1);
    } else {
        echo "<p style='color: red;'>❌ {$error}</p></body></html>";
        exit();
    }
}
$affected2 = mysqli_stmt_affected_rows($stmt2);
mysqli_stmt_close($stmt2);
echo $is_cli ? "✓ Case 2: Appended history to existing biography for {$affected2} character(s)\n" : "<p>✓ Case 2: Appended history to existing biography for {$affected2} character(s)</p>\n";

$total_affected = $affected1 + $affected2;
echo $is_cli ? "\n" : "<br>\n";
echo $is_cli ? "✅ Migration completed successfully!\n" : "<p><strong>✅ Migration completed successfully!</strong></p>\n";
echo $is_cli ? "Total characters updated: {$total_affected}\n" : "<p>Total characters updated: {$total_affected}</p>\n";

// Step 4: Verify migration
$verify_query = "SELECT COUNT(*) as count FROM characters 
                WHERE history IS NOT NULL 
                AND history != '' 
                AND TRIM(history) != ''
                AND (biography IS NULL OR biography = '' OR TRIM(biography) = '')";
$verify_result = mysqli_query($conn, $verify_query);
$verify_row = mysqli_fetch_assoc($verify_result);
$remaining = (int)($verify_row['count'] ?? 0);

if ($remaining > 0) {
    echo $is_cli ? "⚠️  Warning: {$remaining} character(s) still have history but empty biography after migration.\n" : "<p style='color: orange;'>⚠️  Warning: {$remaining} character(s) still have history but empty biography after migration.</p>\n";
} else {
    echo $is_cli ? "✓ Verification: All history data has been merged into biography.\n" : "<p>✓ Verification: All history data has been merged into biography.</p>\n";
}

echo $is_cli ? "\n" : "<br>\n";
echo $is_cli ? "Note: The 'history' column still exists in the table but is no longer used.\n" : "<p><em>Note: The 'history' column still exists in the table but is no longer used.</em></p>\n";
echo $is_cli ? "You can drop the column in a separate migration if desired.\n" : "<p>You can drop the column in a separate migration if desired.</p>\n";

if (!$is_cli) {
    echo "</pre></body></html>";
}
