<?php
/**
 * Authentication Bypass Helper
 * SECURITY: This file should be removed or disabled in production environments
 * 
 * WARNING: This bypass mechanism is a security risk and should only be used
 * in isolated development/testing environments. Never enable in production.
 * 
 * To disable: Set ENABLE_AUTH_BYPASS=false in environment or remove this file.
 */

function isAuthBypassEnabled() {
    // SECURITY: Only allow bypass in development environments
    $isDevelopment = getenv('APP_ENV') === 'development' || getenv('APP_ENV') === 'dev';
    $bypassEnabled = getenv('ENABLE_AUTH_BYPASS') === 'true';
    
    // Require both development environment AND explicit enable flag
    if (!$isDevelopment || !$bypassEnabled) {
        return false;
    }
    
    $bypassFile = __DIR__ . '/../config/auth_bypass.json';
    
    if (!file_exists($bypassFile)) {
        return false;
    }
    
    $config = json_decode(file_get_contents($bypassFile), true);
    if (!$config || !isset($config['enabled']) || $config['enabled'] !== true) {
        return false;
    }
    
    // Check if bypass period has expired
    if (isset($config['enabled_until'])) {
        $now = time();
        $until = strtotime($config['enabled_until']);
        if ($now >= $until) {
            // Expired - disable bypass
            $config['enabled'] = false;
            $config['enabled_until'] = null;
            file_put_contents($bypassFile, json_encode($config, JSON_PRETTY_PRINT));
            return false;
        }
    }
    
    return true;
}

function setupBypassSession() {
    // Set up mock session values for bypass mode
    // SECURITY: Only works if bypass is explicitly enabled in development
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 0; // Guest user ID
        $_SESSION['username'] = 'Guest';
        $_SESSION['role'] = 'player';
    }
}

