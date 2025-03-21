<?php
class AdminNotifications {
    private $db;

    public function create($user_id, $title, $message) {
        try {
            error_log("Inside AdminNotifications::create()\n", 3, "error.log");
    
            $query = "INSERT INTO notifications (user_id, title, message, is_read, created_at) 
                      VALUES (:user_id, :title, :message, 0, NOW())";
            $stmt = $this->db->prepare($query);
    
            error_log("Preparing query...\n", 3, "error.log");
    
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':message', $message);
    
            $result = $stmt->execute();
            if (!$result) {
                error_log("Notification insert failed: " . implode(" | ", $stmt->errorInfo()) . "\n", 3, "error.log");
            } else {
                error_log("Notification successfully stored\n", 3, "error.log");
            }
    
            return $result;
        } catch (Exception $e) {
            error_log("Error inserting notification: " . $e->getMessage() . "\n", 3, "error.log");
            throw new Exception("Error inserting notification: " . $e->getMessage());
        }
    }
    
      
    }

