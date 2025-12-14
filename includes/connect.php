<?php 
error_reporting(2);
//session_start();

// PHP 8+ Compatibility: Disable strict mode to maintain PHP 7.x behavior
// In PHP 8, mysqli throws exceptions by default, but we want to maintain backward compatibility
// by checking return values instead of catching exceptions everywhere
if (PHP_VERSION_ID >= 80000) {
    // Disable exception throwing - use traditional error checking
    mysqli_report(MYSQLI_REPORT_OFF);
} else {
    // PHP 7.x: Use traditional error reporting
    mysqli_report(MYSQLI_REPORT_OFF);
}

// Load .env file if it exists (for easier development)
// .env file takes priority over system environment variables
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    // Clear system DB environment variables first to ensure .env takes priority
    $dbVars = ['DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_NAME'];
    foreach ($dbVars as $var) {
        if (getenv($var)) {
            putenv("$var"); // Unset system variable
        }
    }
    
    // Now load from .env file
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        // Parse KEY=VALUE format
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Remove quotes if present
            $value = trim($value, '"\'');
            // Set from .env file (overrides any remaining system variables)
            putenv("$key=$value");
        }
    }
}

// Database configuration - Load from environment variables for security
// Set these in your .env file or server environment:
// DB_HOST=vdb5.pit.pair.com
// DB_USER=working_64
// DB_PASSWORD=your_password_here
// DB_NAME=working_vbn

$servername = getenv('DB_HOST') ?: "vdb5.pit.pair.com";
$username = getenv('DB_USER') ?: "working_64";
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME') ?: "working_vbn";

// Require password to be set via environment variable
if ($password === false || $password === '') {
    error_log("SECURITY ERROR: Database password not configured. Set DB_PASSWORD environment variable.");
    die("Database configuration error. Please contact the administrator.");
}

// Create connection
try {
    $conn = mysqli_connect($servername, $username, $password, $dbname);
} catch (mysqli_sql_exception $e) {
    error_log("Database connection exception: " . $e->getMessage());
    $conn = false;
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    $conn = false;
}

// Check connection
if (!$conn) {
    // If database doesn't exist, try to create it
    try {
        $conn = mysqli_connect($servername, $username, $password);
        if ($conn) {
            $create_db = "CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            try {
                if (mysqli_query($conn, $create_db)) {
                    mysqli_select_db($conn, $dbname);
                } else {
                    die("Error creating database: " . mysqli_error($conn));
                }
            } catch (mysqli_sql_exception $e) {
                die("Error creating database: " . $e->getMessage());
            }
        } else {
            die("Connection failed: " . mysqli_connect_error());
        }
    } catch (mysqli_sql_exception $e) {
        die("Connection failed: " . $e->getMessage());
    } catch (Exception $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Set character set to utf8mb4 for proper Unicode support
if ($conn && !mysqli_set_charset($conn, "utf8mb4")) {
    error_log("Error setting charset utf8mb4: " . mysqli_error($conn));
}

/**
 * Transaction Helper Functions
 * These functions provide a clean interface for transaction management
 */

/**
 * Begin a database transaction
 * @param mysqli $connection Database connection
 * @return bool Success status
 */
function db_begin_transaction($connection) {
    return mysqli_begin_transaction($connection, MYSQLI_TRANS_START_READ_WRITE);
}

/**
 * Commit a database transaction
 * @param mysqli $connection Database connection
 * @return bool Success status
 */
function db_commit($connection) {
    return mysqli_commit($connection);
}

/**
 * Rollback a database transaction
 * @param mysqli $connection Database connection
 * @return bool Success status
 */
function db_rollback($connection) {
    return mysqli_rollback($connection);
}

/**
 * Execute a transaction with automatic rollback on error
 * @param mysqli $connection Database connection
 * @param callable $callback Function containing transaction operations
 * @return mixed Returns callback result on success
 * @throws Exception On transaction failure
 */
function db_transaction($connection, callable $callback) {
    if (!db_begin_transaction($connection)) {
        throw new Exception("Failed to begin transaction: " . mysqli_error($connection));
    }
    
    try {
        $result = $callback($connection);
        
        if (!db_commit($connection)) {
            throw new Exception("Failed to commit transaction: " . mysqli_error($connection));
        }
        
        return $result;
    } catch (Exception $e) {
        db_rollback($connection);
        throw $e;
    }
}

/**
 * Prepared Statement Helper Functions
 * These functions simplify prepared statement usage
 */

/**
 * Execute a prepared SELECT query and return results
 * @param mysqli $connection Database connection
 * @param string $query SQL query with ? placeholders
 * @param string $types Parameter types (i=integer, d=double, s=string, b=blob)
 * @param array $params Parameters to bind
 * @return mysqli_result|false Query result or false on error
 */
function db_select($connection, $query, $types = "", $params = []) {
    $stmt = mysqli_prepare($connection, $query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . mysqli_error($connection) . " Query: " . $query);
        return false;
    }
    
    if (!empty($types) && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Execute failed: " . mysqli_stmt_error($stmt) . " Query: " . $query);
        mysqli_stmt_close($stmt);
        return false;
    }
    
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

/**
 * Execute a prepared INSERT/UPDATE/DELETE query
 * @param mysqli $connection Database connection
 * @param string $query SQL query with ? placeholders
 * @param string $types Parameter types (i=integer, d=double, s=string, b=blob)
 * @param array $params Parameters to bind
 * @return int|false Insert ID for INSERT, affected rows for UPDATE/DELETE, false on error
 */
function db_execute($connection, $query, $types = "", $params = []) {
    $stmt = mysqli_prepare($connection, $query);
    
    if (!$stmt) {
        error_log("Prepare failed: " . mysqli_error($connection) . " Query: " . $query);
        return false;
    }
    
    if (!empty($types) && !empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Execute failed: " . mysqli_stmt_error($stmt) . " Query: " . $query);
        mysqli_stmt_close($stmt);
        return false;
    }
    
    // Return insert ID for INSERT operations, affected rows for UPDATE/DELETE
    $insert_id = mysqli_stmt_insert_id($stmt);
    $affected_rows = mysqli_stmt_affected_rows($stmt);
    
    mysqli_stmt_close($stmt);
    
    // If this was an INSERT, return the insert ID, otherwise return affected rows
    return $insert_id > 0 ? $insert_id : $affected_rows;
}

/**
 * Execute a prepared query and return a single row
 * @param mysqli $connection Database connection
 * @param string $query SQL query with ? placeholders
 * @param string $types Parameter types (i=integer, d=double, s=string, b=blob)
 * @param array $params Parameters to bind
 * @return array|null Single row as associative array or null if no results
 */
function db_fetch_one($connection, $query, $types = "", $params = []) {
    $result = db_select($connection, $query, $types, $params);
    
    if (!$result) {
        return null;
    }
    
    $row = mysqli_fetch_assoc($result);
    mysqli_free_result($result);
    
    return $row;
}

/**
 * Execute a prepared query and return all rows
 * @param mysqli $connection Database connection
 * @param string $query SQL query with ? placeholders
 * @param string $types Parameter types (i=integer, d=double, s=string, b=blob)
 * @param array $params Parameters to bind
 * @return array Array of rows as associative arrays
 */
function db_fetch_all($connection, $query, $types = "", $params = []) {
    $result = db_select($connection, $query, $types, $params);
    
    if (!$result) {
        return [];
    }
    
    $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_free_result($result);
    
    return $rows;
}

/**
 * Escape and sanitize user input (for cases where prepared statements can't be used)
 * WARNING: Always prefer prepared statements when possible
 * @param mysqli $connection Database connection
 * @param string $value Value to escape
 * @return string Escaped value
 */
function db_escape($connection, $value) {
    return mysqli_real_escape_string($connection, $value);
}
?>