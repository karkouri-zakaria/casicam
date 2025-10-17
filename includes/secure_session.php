<?php
/**
 * Secure Session Configuration
 * 
 * This file configures PHP session settings with security best practices.
 * Include this file before any session_start() call.
 * 
 * Security Features:
 * - HttpOnly: Prevents JavaScript access to session cookies (XSS protection)
 * - Secure: Only transmits cookies over HTTPS (MitM protection)
 * - SameSite=Strict: Prevents CSRF attacks by not sending cookies on cross-site requests
 * - Session regeneration: Prevents session fixation attacks
 */

// Only configure if session hasn't started yet
if (session_status() === PHP_SESSION_NONE) {
    
    // Configure secure session cookie parameters
    session_set_cookie_params([
        'lifetime' => 0,              // Session cookie (expires when browser closes)
        'path' => '/',                // Available throughout domain
        'domain' => '',               // Current domain (empty = auto-detect)
        'secure' => true,             // REQUIRED: Only transmit over HTTPS
        'httponly' => true,           // REQUIRED: Not accessible via JavaScript (XSS protection)
        'samesite' => 'Strict'        // REQUIRED: Strict same-site policy (CSRF protection)
    ]);
    
    // Additional PHP session security settings
    ini_set('session.use_strict_mode', '1');          // Reject uninitialized session IDs
    ini_set('session.use_only_cookies', '1');         // Don't allow session IDs in URLs
    ini_set('session.cookie_httponly', '1');          // Redundant but ensures HttpOnly
    ini_set('session.cookie_secure', '1');            // Redundant but ensures Secure
    ini_set('session.cookie_samesite', 'Strict');     // Redundant but ensures SameSite
    ini_set('session.use_trans_sid', '0');            // Disable transparent session ID propagation
    ini_set('session.cache_limiter', 'nocache');      // Prevent caching of session pages
    ini_set('session.sid_length', '48');              // Longer session ID (more entropy)
    ini_set('session.sid_bits_per_character', '6');   // More bits per character (more entropy)
    
    // Set reasonable garbage collection
    ini_set('session.gc_maxlifetime', '1800');        // 30 minutes
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '100');             // 1% chance of GC on each request
}

/**
 * Start session with secure configuration
 */
function secure_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        
        // Regenerate session ID periodically to prevent session fixation
        // Only if session is already established (not on first request)
        if (isset($_SESSION['initiated'])) {
            // Check if session needs rotation (every 30 minutes)
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        } else {
            $_SESSION['initiated'] = true;
            $_SESSION['last_regeneration'] = time();
        }
        
        // Set additional security headers
        if (!headers_sent()) {
            // Prevent clickjacking
            header('X-Frame-Options: SAMEORIGIN');
            // Prevent MIME type sniffing
            header('X-Content-Type-Options: nosniff');
            // Enable XSS protection (for older browsers)
            header('X-XSS-Protection: 1; mode=block');
            // Referrer policy
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }
    
    return session_id();
}

/**
 * Regenerate session ID (call after login or privilege elevation)
 * 
 * @return string New session ID
 */
function secure_session_regenerate() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
        return session_id();
    }
    return null;
}

/**
 * Destroy session securely
 */
function secure_session_destroy() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Unset all session variables
        $_SESSION = [];
        
        // Delete the session cookie
        if (isset($_COOKIE[session_name()])) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        
        // Destroy the session
        session_destroy();
    }
}

/**
 * Check if session is secure (for debugging)
 * 
 * @return array Status of security settings
 */
function check_session_security() {
    $params = session_get_cookie_params();
    
    return [
        'httponly' => $params['httponly'] ? '✅' : '❌',
        'secure' => $params['secure'] ? '✅' : '❌',
        'samesite' => ($params['samesite'] === 'Strict') ? '✅' : '⚠️',
        'strict_mode' => ini_get('session.use_strict_mode') ? '✅' : '❌',
        'cookies_only' => ini_get('session.use_only_cookies') ? '✅' : '❌',
        'no_trans_sid' => !ini_get('session.use_trans_sid') ? '✅' : '❌',
        'sid_length' => ini_get('session.sid_length') >= 48 ? '✅' : '⚠️',
    ];
}
