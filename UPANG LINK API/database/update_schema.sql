USE upang_link;

-- Add rejection_reason to requests table
ALTER TABLE requests 
ADD COLUMN rejection_reason TEXT NULL,
ADD COLUMN handled_by INT NULL,
ADD COLUMN request_token VARCHAR(100) UNIQUE NULL,
ADD FOREIGN KEY (handled_by) REFERENCES users(user_id);

-- Create request status history table
CREATE TABLE request_status_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'in_progress', 'completed') NOT NULL,
    changed_by INT NOT NULL,
    reason TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(request_id),
    FOREIGN KEY (changed_by) REFERENCES users(user_id)
);

-- Create request tokens table for tracking
CREATE TABLE request_tokens (
    token_id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    token VARCHAR(100) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    FOREIGN KEY (request_id) REFERENCES requests(request_id)
);

-- Add trigger to track request status changes
DELIMITER //
CREATE TRIGGER after_request_status_update
AFTER UPDATE ON requests
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO request_status_history (request_id, status, changed_by, reason)
        VALUES (NEW.request_id, NEW.status, NEW.handled_by, 
            CASE 
                WHEN NEW.status = 'rejected' THEN NEW.rejection_reason
                ELSE NULL
            END
        );
    END IF;
END //
DELIMITER ; 