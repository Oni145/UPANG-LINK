<?php

class AdminNotificationsController {
    private $db;

    // Constructor to initialize database connection
    public function __construct($db) {
        $this->db = $db;
    }

    // Method to handle API requests
    public function handleRequest($method, $params = []) {
        switch ($method) {
            case 'GET':
                $this->getNotifications();
                break;
            case 'POST':
                // Handle notification creation if needed
                if (isset($_POST['user_id']) && isset($_POST['category_id'])) {
                    $this->storeNotification($_POST['user_id'], $_POST['category_id']);
                } else {
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'Missing required parameters'
                    ]);
                }
                break;
            default:
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Method not supported'
                ]);
        }
    }

    // Method to get admin notifications
    public function getNotifications() {
        try {
            // Get admin ID from the token
            $headers = apache_request_headers();
            $token = null;
            
            if (isset($headers['Authorization'])) {
                $token = str_replace('Bearer ', '', $headers['Authorization']);
            } elseif (isset($headers['authorization'])) {
                $token = str_replace('Bearer ', '', $headers['authorization']);
            }
            
            if (!$token) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Authorization token not provided'
                ]);
                return;
            }
            
            // Get admin ID from token
            $stmt = $this->db->prepare("SELECT admin_id FROM admin_tokens WHERE token = ?");
            $stmt->execute([$token]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid or expired token'
                ]);
                return;
            }
            
            $adminId = $result['admin_id'];
            
            // Get notifications for this admin
            $stmt = $this->db->prepare("SELECT * FROM admin_notifications WHERE admin_id = ? ORDER BY created_at DESC LIMIT 50");
            $stmt->execute([$adminId]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'status' => 'success',
                'notifications' => $notifications
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error fetching notifications: ' . $e->getMessage()
            ]);
        }
    }

    // Method to store a new notification
    public function storeNotification($userId, $categoryId) {
        try {
            // Fetch the name of the requested item using category_id from the request_types table
            $stmt = $this->db->prepare("SELECT name FROM request_types WHERE category_id = ?");
            $stmt->execute([$categoryId]);
            $type = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($type) {
                // Use the fetched name as the message for the notification
                $requestedItemTitle = $type['name'];

                // Insert the notification into admin_notifications table
                $stmt = $this->db->prepare("INSERT INTO admin_notifications (message, admin_id, user_id, is_read) VALUES (?, ?, ?, 0)");
                $stmt->execute([$requestedItemTitle, $userId, $userId]); // Assuming admin_id is the same as user_id for now

                // Get the ID of the newly inserted notification
                $notificationId = $this->db->lastInsertId();

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Notification created successfully',
                    'notification_id' => $notificationId
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Category ID not found in request_types table'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error storing notification: ' . $e->getMessage()
            ]);
        }
    }
}

?>
