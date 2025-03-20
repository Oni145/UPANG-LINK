<?php
/**
 * JWT Helper Class
 * 
 * A class for handling JWT tokens including encoding, decoding and verification.
 * Also supports the existing token format for backward compatibility.
 */

class JWT {
    // Properties for configuration
    private static $secret_key;
    private static $token_validity;
    private static $check_expiration;
    private static $validate_signature;
    
    /**
     * Initialize the JWT configuration
     */
    private static function initConfig() {
        // Load configuration if not already loaded
        if (self::$secret_key === null) {
            $config_path = __DIR__ . '/../config/jwt_config.php';
            
            if (file_exists($config_path)) {
                $config = require $config_path;
                self::$secret_key = $config['secret_key'];
                self::$token_validity = $config['token_validity'];
                self::$check_expiration = $config['check_expiration'];
                self::$validate_signature = $config['validate_signature'];
                
                error_log("JWT config loaded from: " . $config_path);
            } else {
                // Fallback default values
                self::$secret_key = 'UPANG-LINK-DEFAULT-SECRET-KEY';
                self::$token_validity = 86400; // 24 hours
                self::$check_expiration = true;
                self::$validate_signature = true;
                
                error_log("JWT config file not found, using default values");
            }
        }
    }
    
    /**
     * Generate a new JWT token
     *
     * @param int $user_id The user ID to include in the token
     * @param array $additional_data Additional data to include in the token payload
     * @return string The generated JWT token
     */
    public static function generate($user_id, $additional_data = []) {
        // Initialize configuration
        self::initConfig();
        
        // Token header
        $header = json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]);
        
        // Current timestamp
        $current_time = time();
        
        // Token payload
        $payload = array_merge([
            'user_id' => $user_id,
            'iat' => $current_time,
            'exp' => $current_time + self::$token_validity
        ], $additional_data);
        
        $payload = json_encode($payload);
        
        // Base64Url encoding
        $base64_header = self::base64UrlEncode($header);
        $base64_payload = self::base64UrlEncode($payload);
        
        // Create signature
        $signature = hash_hmac('sha256', "$base64_header.$base64_payload", self::$secret_key, true);
        $base64_signature = self::base64UrlEncode($signature);
        
        // Create JWT token
        return "$base64_header.$base64_payload.$base64_signature";
    }
    
    /**
     * Decode and verify a JWT token or legacy token
     *
     * @param string $token The JWT token to decode or legacy token
     * @return object The decoded token payload as an object
     * @throws Exception If the token is invalid or expired
     */
    public static function decode($token) {
        try {
            // Initialize configuration
            self::initConfig();
            
            error_log("Decoding token: " . $token);
            
            // Check if it's a standard JWT token or legacy token
            if (strpos($token, '.') !== false) {
                // Standard JWT token with three parts
                return self::decodeStandardJWT($token);
            } else {
                // Legacy token format (plain string)
                return self::decodeLegacyToken($token);
            }
            
        } catch (Exception $e) {
            error_log("Token validation error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Decode a standard JWT token (header.payload.signature)
     *
     * @param string $token The JWT token to decode
     * @return object The decoded token payload as an object
     * @throws Exception If the token is invalid or expired
     */
    private static function decodeStandardJWT($token) {
        // Split the token into its parts
        $token_parts = explode('.', $token);
        
        // Check if token has three parts
        if (count($token_parts) !== 3) {
            error_log("Invalid token format: Token does not have three parts");
            throw new Exception("Invalid token format");
        }
        
        list($base64_header, $base64_payload, $base64_signature) = $token_parts;
        
        // Decode the header and payload
        $header = json_decode(self::base64UrlDecode($base64_header), true);
        $payload = json_decode(self::base64UrlDecode($base64_payload), true);
        
        // Verify the token signature if enabled
        if (self::$validate_signature) {
            $signature = self::base64UrlDecode($base64_signature);
            $expected_signature = hash_hmac('sha256', "$base64_header.$base64_payload", self::$secret_key, true);
            
            if (!hash_equals($expected_signature, $signature)) {
                error_log("Invalid token signature");
                throw new Exception("Invalid token signature");
            }
        }
        
        // Check if the token has expired if expiration check is enabled
        if (self::$check_expiration && isset($payload['exp']) && $payload['exp'] < time()) {
            error_log("Token has expired");
            throw new Exception("Token has expired");
        }
        
        // Check if the token contains a user ID
        if (!isset($payload['user_id'])) {
            error_log("Token does not contain user_id");
            throw new Exception("Invalid token: missing user_id");
        }
        
        // Create response object
        $response = new stdClass();
        $response->user_id = $payload['user_id'];
        
        // Add any additional data from the payload
        foreach ($payload as $key => $value) {
            if ($key !== 'user_id') {
                $response->$key = $value;
            }
        }
        
        error_log("Successfully decoded standard JWT token for user ID: " . $response->user_id);
        return $response;
    }
    
    /**
     * Decode a legacy token (hex string)
     * This method validates the token by checking it in the user_sessions table
     *
     * @param string $token The legacy token to decode
     * @return object The user data associated with the token
     * @throws Exception If the token is invalid or expired
     */
    private static function decodeLegacyToken($token) {
        try {
            error_log("Decoding legacy token: " . $token);
            
            // Connect to the database
            require_once __DIR__ . '/../config/Database.php';
            $database = new Database();
            $conn = $database->getConnection();
            
            // Check if the token exists in the user_sessions table
            $query = "SELECT us.*, u.email, u.first_name, u.last_name, u.role 
                     FROM user_sessions us
                     JOIN users u ON us.user_id = u.user_id
                     WHERE us.token = ? AND us.is_active = 1 AND us.expires_at > NOW()";
            
            $stmt = $conn->prepare($query);
            $stmt->execute([$token]);
            
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Create response object
                $response = new stdClass();
                $response->user_id = $row['user_id'];
                $response->email = $row['email'];
                $response->name = $row['first_name'] . ' ' . $row['last_name'];
                $response->role = $row['role'];
                
                // Parse expires_at date
                $expires_at = strtotime($row['expires_at']);
                $response->exp = $expires_at;
                
                // Update last_activity timestamp
                $updateQuery = "UPDATE user_sessions SET last_activity = NOW() WHERE token = ?";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->execute([$token]);
                
                error_log("Successfully decoded legacy token for user ID: " . $response->user_id);
                return $response;
            } else {
                error_log("Legacy token not found in database or expired");
                throw new Exception("Invalid or expired token");
            }
        } catch (Exception $e) {
            error_log("Legacy token validation error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Base64Url encode a string
     *
     * @param string $data The data to encode
     * @return string The base64url encoded string
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64Url decode a string
     *
     * @param string $data The data to decode
     * @return string The decoded string
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
    
    /**
     * Validate a token (simplified version for testing)
     * 
     * @param string $token The token to validate
     * @return bool Whether the token is valid
     */
    public static function validate($token) {
        try {
            self::decode($token);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
} 