<?php
/**
 * Legacy database include - MySQL removed.
 * This project uses Supabase only. Use includes/supabase_client.php for all database access.
 * This file loads .env and Supabase client; $conn is null. Any code using $conn or db_* must be migrated to Supabase.
 */
declare(strict_types=1);

error_reporting(2);

$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || strpos($trimmed, '#') === 0 || strpos($trimmed, '=') === false) {
                continue;
            }
            [$key, $value] = explode('=', $trimmed, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if ($key !== '') {
                putenv($key . '=' . $value);
            }
        }
    }
}

require_once __DIR__ . '/supabase_client.php';

/** @var null Legacy MySQL connection - no longer used. Use supabase_table_get / supabase_rest_request instead. */
$conn = null;

function _mysql_removed(string $fn): void {
    throw new RuntimeException('MySQL removed. Use Supabase: includes/supabase_client.php (supabase_table_get, supabase_rest_request). Legacy call: ' . $fn);
}

function db_begin_transaction($connection): bool {
    _mysql_removed('db_begin_transaction');
}

function db_commit($connection): bool {
    _mysql_removed('db_commit');
}

function db_rollback($connection): bool {
    _mysql_removed('db_rollback');
}

function db_transaction($connection, callable $callback) {
    _mysql_removed('db_transaction');
}

function db_select($connection, $query, $types = '', $params = []) {
    _mysql_removed('db_select');
}

function db_execute($connection, $query, $types = '', $params = []) {
    _mysql_removed('db_execute');
}

function db_fetch_one($connection, $query, $types = '', $params = []) {
    _mysql_removed('db_fetch_one');
}

function db_fetch_all($connection, $query, $types = '', $params = []) {
    _mysql_removed('db_fetch_all');
}

function db_escape($connection, $value): string {
    _mysql_removed('db_escape');
}
