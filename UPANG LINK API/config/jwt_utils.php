<?php
require_once 'jwt_config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Validate a JWT token
 * 
 * @param string $jwt The JWT token
 * @return bool Whether the token is valid
 */
function is_jwt_valid($jwt) {
    try {
        $secret_key = JWT_SECRET;
        
        // Decode the JWT
        $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
        
        // Check if the token is expired
        $now = new DateTimeImmutable();
        if ($decoded->exp < $now->getTimestamp()) {
            error_log("Token has expired");
            return false;
        }
        
        // Token is valid
        return true;
    } catch (Exception $e) {
        error_log("JWT validation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get the data from a JWT token
 * 
 * @param string $jwt The JWT token
 * @return object|null The decoded JWT payload or null if invalid
 */
function get_jwt_data($jwt) {
    try {
        $secret_key = JWT_SECRET;
        
        // Decode the JWT
        $decoded = JWT::decode($jwt, new Key($secret_key, 'HS256'));
        
        return $decoded;
    } catch (Exception $e) {
        error_log("JWT data extraction error: " . $e->getMessage());
        return null;
    }
}
?> 