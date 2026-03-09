<?php
/**
 * Legacy database include - MySQL removed.
 * This project uses Supabase only. Use includes/supabase_client.php for all database access.
 * This file loads env and Supabase client; $conn is null. Any code using $conn or db_* must be migrated to Supabase.
 */
declare(strict_types=1);

error_reporting(2);

require_once __DIR__ . '/load_env.php';
load_project_env();

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
