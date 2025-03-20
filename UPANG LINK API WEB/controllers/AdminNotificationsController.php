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
            // Debugging: Log the incoming method and input
            error_log("Handling method: " . $method);
            error_log("Request body: " . file_get_contents("php://input"));
    
            switch ($method) {
                case 'GET':
                    $this->getNotifications();
                    break;
    
                case 'POST':
                    // Handle storing a notification with user_id and category_id
                    $this->storeNotification($_POST['user_id'] ?? null, $_POST['category_id'] ?? null);
                    break;
    
                case 'PUT':
                    // Check if the request body contains JSON
                    $inputData = json_decode(file_get_contents("php://input"), true);  // Decode JSON input
    
                    // Debugging: Log the parsed input
                    error_log("Parsed PUT data: " . print_r($inputData, true));
    
                    // Check if the action is provided in the body
                    if (isset($inputData['action']) && $inputData['action'] === 'mark_all_read') {
                        // No need for user_id in the body for this action
                        $this->markAsAllRead();
                    } else {
                        $this->sendResponse(400, 'Invalid action or missing parameters.');
                    }
                    break;
    
                default:
                    $this->sendResponse(405, 'Method not allowed.');
            }
        } catch (Exception $e) {
            // Debugging: Log exception details
            error_log('Error: ' . $e->getMessage());
            $this->sendResponse(500, 'Internal server error: ' . $e->getMessage());
        }
    }
    
    
    
    

    // Get notifications for authenticated admin
    private function getNotifications() {
        try {
            $adminId = $this->authenticateUser();
            if (!$adminId) return;
    
            // Fetch all notifications (limit 50) with full details
            $stmt = $this->db->prepare("
                SELECT notification_id, user_id, title, message, is_read, created_at
                FROM notifications
                ORDER BY created_at DESC
                LIMIT 50
            ");
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            if (empty($notifications)) {
                $this->sendResponse(200, 'No notifications found.', ['notifications' => []]);
            } else {
                $this->sendResponse(200, 'Notifications retrieved successfully.', ['notifications' => $notifications]);
            }
        } catch (Exception $e) {
            $this->sendResponse(500, 'Error fetching notifications: ' . $e->getMessage());
        }
    }
    

    // Store a new notification
    private function storeNotification($userId, $categoryId) {
        try {
            // Get the requested category name
            $stmt = $this->db->prepare("SELECT name FROM request_types WHERE category_id = ?");
            $stmt->execute([$categoryId]);
            $type = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$type) {
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
            $stmt->execute([$userId, $title, $message]);

            $notificationId = $this->db->lastInsertId();

            $this->sendResponse(201, 'Notification created successfully.', ['notification_id' => $notificationId]);
        } catch (Exception $e) {
            $this->sendResponse(500, 'Error storing notification: ' . $e->getMessage());
        }
    }

    // Mark all notifications as read
    private function markAsAllRead() {
        try {
            // Authenticate the user using the token
            $userId = $this->authenticateUser();
            if (!$userId) {
                $this->sendResponse(403, 'Unauthorized access. Please log in.');
                return;
            }
    
            // Get the action parameter from the PUT request body
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (!isset($data['action']) || $data['action'] !== 'mark_all_read') {
                $this->sendResponse(400, 'Invalid action parameter.');
                return;
            }
    
            // Fetch all unread notifications by notification_id
            $stmt = $this->db->prepare("SELECT notification_id FROM notifications WHERE is_read = 0");
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
            if (empty($notifications)) {
                $this->sendResponse(404, 'No unread notifications found.');
                return;
            }
    
            // Extract notification IDs
            $notificationIds = array_column($notifications, 'notification_id');
    
            // Update all unread notifications to 'read'
            $placeholders = str_repeat('?,', count($notificationIds) - 1) . '?';
            $updateStmt = $this->db->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id IN ($placeholders)");
            $updateStmt->execute($notificationIds);
    
            $affectedRows = $updateStmt->rowCount();
            $this->sendResponse(200, "$affectedRows notifications marked as read.");
        } catch (Exception $e) {
            $this->sendResponse(500, 'Error updating notifications: ' . $e->getMessage());
        }
    }
    
    
    

    // Authenticate user based on token
    private function authenticateUser() {
        try {
            $headers = apache_request_headers();
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
                $this->sendResponse(401, 'Invalid or expired token.');
                return false;
            }

            return $result['user_id'];
        } catch (Exception $e) {
            $this->sendResponse(500, 'Error validating token: ' . $e->getMessage());
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
