<?php
class AuthMiddleware {
    private $db;
    private $user;
    private $public_routes = [
        'POST' => [
            '/auth/admin/login',
            '/auth/admin/register',
            '/auth/student/login',
            '/auth/student/register',
            '/auth/student/verify-email',
            '/auth/student/resend-verification'
        ]
    ];

    public function __construct($db) {
        $this->db = $db;
        $this->user = new User($db);
    }

    public function handle($method, $uri) {
        // Convert URI to route format
        $route = '/' . implode('/', $uri);
        
        // Check if route is public
        if(isset($this->public_routes[$method]) && in_array($route, $this->public_routes[$method])) {
            return true;
        }

        // Get authorization header
        $headers = getallheaders();
        $token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : null;

        if(!$token) {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Authorization token is required'
            ]);
            return false;
        }

        // Validate session
        $session = $this->user->validateSession($token);
        if(!$session['valid']) {
            http_response_code(401);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid or expired session'
            ]);
            return false;
        }

        // Check if email is verified for student routes
        if(strpos($route, '/student/') !== false && !$session['email_verified']) {
            http_response_code(403);
            echo json_encode([
                'status' => 'error',
                'message' => 'Email verification required'
            ]);
            return false;
        }

        return true;
    }
} 