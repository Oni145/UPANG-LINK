<?php
class RateLimiter {
    private $db;
    private $table_name = "rate_limits";
    private $window = 3600; // 1 hour window
    private $max_requests = 1000; // Max requests per window

    public function __construct($db) {
        $this->db = $db;
        $this->createTable();
    }

    private function createTable() {
        $query = "CREATE TABLE IF NOT EXISTS " . $this->table_name . " (
            id INT PRIMARY KEY AUTO_INCREMENT,
            ip_address VARCHAR(45) NOT NULL,
            endpoint VARCHAR(255) NOT NULL,
            requests INT DEFAULT 1,
            window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_endpoint (ip_address, endpoint)
        )";

        $this->db->exec($query);
    }

    public function checkLimit($ip_address, $endpoint) {
        // Clean old records
        $this->cleanOldRecords();

        // Get current count
        $query = "SELECT * FROM " . $this->table_name . "
                WHERE ip_address = ? 
                AND endpoint = ?
                AND window_start > DATE_SUB(NOW(), INTERVAL 1 HOUR)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$ip_address, $endpoint]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            if ($record['requests'] >= $this->max_requests) {
                return false; // Rate limit exceeded
            }

            // Increment request count
            $query = "UPDATE " . $this->table_name . "
                    SET requests = requests + 1
                    WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$record['id']]);
        } else {
            // Create new record
            $query = "INSERT INTO " . $this->table_name . "
                    (ip_address, endpoint)
                    VALUES (?, ?)";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$ip_address, $endpoint]);
        }

        return true;
    }

    private function cleanOldRecords() {
        $query = "DELETE FROM " . $this->table_name . "
                WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        $this->db->exec($query);
    }

    public function getRemainingRequests($ip_address, $endpoint) {
        $query = "SELECT requests FROM " . $this->table_name . "
                WHERE ip_address = ?
                AND endpoint = ?
                AND window_start > DATE_SUB(NOW(), INTERVAL 1 HOUR)";

        $stmt = $this->db->prepare($query);
        $stmt->execute([$ip_address, $endpoint]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            return $this->max_requests - $record['requests'];
        }

        return $this->max_requests;
    }

    public function setLimit($endpoint, $requests_per_hour) {
        if ($endpoint === '*') {
            $this->max_requests = $requests_per_hour;
        } else {
            // You could store endpoint-specific limits in a separate table
            // For now, we'll just use the global limit
            $this->max_requests = $requests_per_hour;
        }
    }
} 