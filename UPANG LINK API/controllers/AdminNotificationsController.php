<?php

class AdminNotificationsController {
    private $db;

    // Constructor to initialize database connection
    public function __construct($db) {
        $this->db = $db;
    }

    // Handle API requests
    public function handleRequest($method) {
        try {
            switch ($method) {
                case 'GET':
                    $this->getNotifications();
                    break;
                case 'POST':
                    if (!isset($_POST['user_id'], $_POST['category_id'])) {
                        $this->sendResponse(400, 'Missing required parameters: user_id and category_id.');
                        return;
                    }
                    $this->storeNotification($_POST['user_id'], $_POST['category_id']);
                    break;
                default:
                    $this->sendResponse(405, 'Method not allowed.');
            }
        } catch (Exception $e) {
            error_log("Internal server error: " . $e->getMessage() . "\n", 3, "error.log");
            $this->sendResponse(500, 'Internal server error.');
        }
    }

    // Get notifications for authenticated admin
    private function getNotifications() {
        try {
            $adminId = $this->authenticateUser();
            if (!$adminId) return;

            $stmt = $this->db->prepare("
                SELECT notification_id, user_id, title, message, is_read, created_at
                FROM notifications 
                ORDER BY created_at DESC 
                LIMIT 50
            ");
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $this->sendResponse(200, 'Notifications retrieved successfully.', ['notifications' => $notifications]);
        } catch (Exception $e) {
            error_log("Error fetching notifications: " . $e->getMessage() . "\n", 3, "error.log");
            $this->sendResponse(500, 'Error fetching notifications.');
        }
    }

    // Store a new notification
    private function storeNotification($userId, $categoryId) {
        try {
            error_log("Preparing to store notification for user_id: $userId, category_id: $categoryId\n", 3, "error.log");

            // Validate category_id exists
            $stmt = $this->db->prepare("SELECT name FROM request_types WHERE id = ?");
            $stmt->execute([$categoryId]);
            $type = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$type || !isset($type['name'])) {
                error_log("Category ID $categoryId not found.\n", 3, "error.log");
                $this->sendResponse(404, 'Category ID not found.');
                return;
            }

            $title = $type['name'];
            $message = "New request for: " . $title;

            // Insert the notification
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, title, message, is_read, created_at) 
                VALUES (?, ?, ?, 0, NOW())
            ");
            if (!$stmt->execute([$userId, $title, $message])) {
                error_log("SQL Error: " . implode(" | ", $stmt->errorInfo()) . "\n", 3, "error.log");
                $this->sendResponse(500, 'Database error while storing notification.');
                return;
            }

            $notificationId = $this->db->lastInsertId();
            error_log("Notification $notificationId stored successfully.\n", 3, "error.log");

            $this->sendResponse(201, 'Notification created successfully.', ['notification_id' => $notificationId]);
        } catch (Exception $e) {
            error_log("Error storing notification: " . $e->getMessage() . "\n", 3, "error.log");
            $this->sendResponse(500, 'Error storing notification.');
        }
    }

    // Authenticate user based on token
    private function authenticateUser() {
        try {
            $headers = apache_request_headers();
            error_log("Headers: " . print_r($headers, true) . "\n", 3, "error.log");

            $token = $headers['Authorization'] ?? $headers['authorization'] ?? null;
            if ($token) {
                $token = str_replace('Bearer ', '', $token);
            }

            if (!$token) {
                $this->sendResponse(401, 'Authorization token not provided.');
                return false;
            }

            $stmt = $this->db->prepare("SELECT user_id FROM user_sessions WHERE token = ?");
            $stmt->execute([$token]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                error_log("Invalid token: $token\n", 3, "error.log");
                $this->sendResponse(401, 'Invalid or expired token.');
                return false;
            }

            return $result['user_id'];
        } catch (Exception $e) {
            error_log("Error validating token: " . $e->getMessage() . "\n", 3, "error.log");
            $this->sendResponse(500, 'Error validating token.');
            return false;
        }
    }

    // Send JSON response
    private function sendResponse($statusCode, $message, $data = []) {
        http_response_code($statusCode);
        echo json_encode(array_merge(['status' => $statusCode, 'message' => $message], $data));
        exit;
    }
}

?>
