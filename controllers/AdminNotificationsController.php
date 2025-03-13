<?php

class AdminNotificationsController {
    private $db;

    // Constructor to initialize database connection
    public function __construct($db) {
        $this->db = $db;
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
