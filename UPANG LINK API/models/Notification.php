<?php
class Notification {
    private $conn;
    private $table_name = "notifications";

    public $notification_id;
    public $user_id;
    public $title;
    public $message;
    public $is_read;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                (user_id, title, message)
                VALUES (:user_id, :title, :message)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":message", $this->message);

        return $stmt->execute();
    }

    public function readUserNotifications($user_id) {
        $query = "SELECT * FROM " . $this->table_name . "
                WHERE user_id = ? ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt;
    }

    public function markAsRead($notification_id) {
        $query = "UPDATE " . $this->table_name . "
                SET is_read = 1
                WHERE notification_id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $notification_id);
        return $stmt->execute();
    }

    public function getUnreadCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . "
                WHERE user_id = ? AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }
} 