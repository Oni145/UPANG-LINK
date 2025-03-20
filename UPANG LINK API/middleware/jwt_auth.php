<?php
/**
 * JWT Authentication Middleware
 * 
 * This middleware handles authentication using JWT tokens or legacy tokens.
 * Include this file in API endpoints that require authentication.
 */

// Include JWT helper if not already included
if (!class_exists('JWT')) {
    require_once __DIR__ . '/../helpers/jwt_helper.php';
}

/**
 * Authenticates a user based on JWT token or legacy token
 * 
 * @return int|null The authenticated user ID or null if authentication fails
 */
function authenticate() {
    // Get authorization header
    $headers = getallheaders();
    $authorization = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    error_log("Authorization header: " . $authorization);
    
    // Check if the Authorization header exists and is in the correct format
    if (strpos($authorization, 'Bearer ') === 0) {
        $token = substr($authorization, 7);
        
        try {
            // Decode the token (JWT helper now handles both formats)
            $decoded = JWT::decode($token);
            
            // Check if user_id exists in the decoded token
            if (isset($decoded->user_id)) {
                error_log("Authenticated user ID: " . $decoded->user_id);
                return $decoded->user_id;
            }
            
            error_log("Token does not contain user_id");
            return null;
        } catch (Exception $e) {
            error_log("Token validation error: " . $e->getMessage());
            return null;
        }
    }
    
    // Check if token is provided as a URL parameter (for testing/development only)
    if (isset($_GET['token'])) {
        try {
            $decoded = JWT::decode($_GET['token']);
            
            if (isset($decoded->user_id)) {
                error_log("Authenticated user ID from URL token: " . $decoded->user_id);
                return $decoded->user_id;
            }
            
            error_log("URL token does not contain user_id");
            return null;
        } catch (Exception $e) {
            error_log("URL token validation error: " . $e->getMessage());
            return null;
        }
    }
    
    // If using session-based authentication, check for session ID
    if (isset($_COOKIE['PHPSESSID']) && session_status() === PHP_SESSION_NONE) {
        session_start();
        if (isset($_SESSION['user_id'])) {
            error_log("Authenticated user ID from session: " . $_SESSION['user_id']);
            return $_SESSION['user_id'];
        }
    }
    
    error_log("No valid authorization token provided");
    return null;
}

/**
 * Middleware to require authentication for an endpoint
 * 
 * Use this function at the beginning of an API endpoint to require authentication.
 * If authentication fails, it will return an error response and exit.
 * 
 * @return int The authenticated user ID
 */
function requireAuth() {
    $user_id = authenticate();
    
    if (!$user_id) {
        header('HTTP/1.0 401 Unauthorized');
        echo json_encode([
            'status' => 'error',
            'message' => 'Authentication required',
            'code' => 401
        ]);
        exit;
    }
    
    return $user_id;
}

/**
 * Get the full user data from a JWT token or legacy token
 * 
 * @return object|null The full decoded token data or null if not authenticated
 */
function getAuthUser() {
    $headers = getallheaders();
    $authorization = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (strpos($authorization, 'Bearer ') === 0) {
        $token = substr($authorization, 7);
        
        try {
            return JWT::decode($token);
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Check URL parameter as fallback
    if (isset($_GET['token'])) {
        try {
            return JWT::decode($_GET['token']);
        } catch (Exception $e) {
            return null;
        }
    }
    
    return null;
}
?> 