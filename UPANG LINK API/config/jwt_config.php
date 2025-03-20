<?php
/**
 * JWT Configuration
 * 
 * Configuration settings for JWT authentication
 */

// In a production environment, these values should be stored in environment variables
// and loaded using getenv() or $_ENV

return [
    // Secret key for signing tokens (CHANGE THIS IN PRODUCTION!)
    'secret_key' => 'UPANG-LINK-SECRET-KEY-2023-DEVELOPMENT-KEY',
    
    // Token validity duration in seconds
    'token_validity' => 86400, // 24 hours
    
    // Whether to check token expiration
    'check_expiration' => true,
    
    // Whether to validate the signature
    'validate_signature' => true
]; 