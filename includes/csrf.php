<?php
/**
 * CSRF Protection Library
 * Provides token generation and validation for form submissions
 */

/**
 * Generate a CSRF token and store it in the session
 * @return string The generated token
 */
function csrf_generate_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Generate a random token
    $token = bin2hex(random_bytes(32));
    
    // Store in session with timestamp
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
    
    return $token;
}

/**
 * Get the current CSRF token, generating one if it doesn't exist
 * @return string The CSRF token
 */
function csrf_get_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if token exists and is not expired (30 minutes)
    if (isset($_SESSION['csrf_token']) && isset($_SESSION['csrf_token_time'])) {
        $token_age = time() - $_SESSION['csrf_token_time'];
        if ($token_age < 1800) { // 30 minutes
            return $_SESSION['csrf_token'];
        }
    }
    
    // Generate new token if expired or doesn't exist
    return csrf_generate_token();
}

/**
 * Validate a CSRF token from POST request
 * @param bool $die_on_failure Whether to terminate script on validation failure
 * @return bool True if valid, false otherwise
 */
function csrf_validate_token($die_on_failure = true) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if token exists in session
    if (!isset($_SESSION['csrf_token'])) {
        if ($die_on_failure) {
            http_response_code(403);
            die('CSRF validation failed: No token in session. Please refresh the page and try again.');
        }
        return false;
    }
    
    // Check if token was submitted
    $submitted_token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    
    if (empty($submitted_token)) {
        if ($die_on_failure) {
            http_response_code(403);
            die('CSRF validation failed: No token submitted. Please ensure you are submitting the form correctly.');
        }
        return false;
    }
    
    // Validate token matches
    if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
        if ($die_on_failure) {
            http_response_code(403);
            die('CSRF validation failed: Invalid token. This may be a cross-site request forgery attack.');
        }
        return false;
    }
    
    // Check token age (30 minutes max)
    if (isset($_SESSION['csrf_token_time'])) {
        $token_age = time() - $_SESSION['csrf_token_time'];
        if ($token_age > 1800) {
            if ($die_on_failure) {
                http_response_code(403);
                die('CSRF validation failed: Token expired. Please refresh the page and try again.');
            }
            return false;
        }
    }
    
    return true;
}

/**
 * Generate HTML for a hidden CSRF token field
 * @return string HTML input field
 */
function csrf_token_field() {
    $token = csrf_get_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Regenerate CSRF token (useful after successful form submission)
 */
function csrf_regenerate_token() {
    return csrf_generate_token();
}
