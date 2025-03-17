<?php
/**
 * Authentication middleware for validating user tokens
 */
class Auth {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Validate an authentication token from the request headers
     * 
     * @return array An array containing validation results
     */
    public function validateToken() {
        // Get headers
        $headers = getallheaders();
        $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
        
        // Check if Authorization header exists and has the Bearer prefix
        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return [
                'is_valid' => false,
                'message' => 'Authentication token not found'
            ];
        }
        
        // Extract token from header
        $token = $matches[1];
        
        // Query to check if token is valid
        $query = "SELECT us.*, u.user_id, u.email_verified 
                FROM user_sessions us
                JOIN users u ON us.user_id = u.user_id
                WHERE us.token = ? AND us.is_active = 1 AND us.expires_at > NOW()";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $token);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $session = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Update last activity
                $this->updateSessionActivity($token);
                
                return [
                    'is_valid' => true,
                    'user_id' => $session['user_id'],
                    'email_verified' => $session['email_verified'],
                    'message' => 'Token is valid'
                ];
            }
            
            return [
                'is_valid' => false,
                'message' => 'Invalid or expired token'
            ];
        } catch (PDOException $e) {
            error_log("Database error in validateToken: " . $e->getMessage());
            return [
                'is_valid' => false,
                'message' => 'Database error'
            ];
        }
    }
    
    /**
     * Update the last activity timestamp for a session
     * 
     * @param string $token The session token
     * @return boolean Success or failure
     */
    private function updateSessionActivity($token) {
        $query = "UPDATE user_sessions 
                SET last_activity = CURRENT_TIMESTAMP 
                WHERE token = ?";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $token);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating session activity: " . $e->getMessage());
            return false;
        }
    }
} 