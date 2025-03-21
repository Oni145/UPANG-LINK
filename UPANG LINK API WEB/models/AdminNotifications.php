<?php
class AdminNotifications {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($user_id, $title, $message) {
        try {
            $query = "INSERT INTO notifications (user_id, title, message, is_read, created_at) 
                      VALUES (:user_id, :title, :message, 0, NOW())";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':message', $message);
            return $stmt->execute();
        } catch (Exception $e) {
            throw new Exception("Error inserting notification: " . $e->getMessage());
        }
    }
}
