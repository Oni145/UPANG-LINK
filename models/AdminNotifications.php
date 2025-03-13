<?php

class AdminNotifications {
    private $db;

    // Constructor
    public function __construct($db) {
        $this->db = $db;
    }

    // Method to create a new notification
    public function create($user_id, $message) {
        try {
            $query = "INSERT INTO admin_notifications (user_id, message, is_read) VALUES (:user_id, :message, 0)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':message', $message);

            return $stmt->execute(); // Return true on success, false on failure
        } catch (Exception $e) {
            throw new Exception("Error inserting notification: " . $e->getMessage());
        }
    }
}
?>
