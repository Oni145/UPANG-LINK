-- Integration SQL Script for UPANG-LINK
-- This script ensures that both the mobile API and web API can communicate properly
-- by ensuring all necessary tables exist with the correct structure

USE upang_link;

-- First, ensure the admin tables exist (these were created in setup.sql)
-- If they don't exist, create them
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    password_reset_token VARCHAR(64) DEFAULT NULL,
    password_reset_expires DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_tokens (
    token CHAR(64) PRIMARY KEY,
    admin_id INT NOT NULL,
    login_time DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    title VARCHAR(255) DEFAULT NULL,
    message TEXT NOT NULL,
    user_id INT DEFAULT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure the users table has all required fields from both APIs
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS role ENUM('student', 'admin') NOT NULL DEFAULT 'student',
ADD COLUMN IF NOT EXISTS email_verified BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS email_verification_token VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS email_token_expiry DATETIME DEFAULT NULL,
ADD COLUMN IF NOT EXISTS reset_password_token VARCHAR(255) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS reset_token_expiry DATETIME DEFAULT NULL;

-- Ensure the notifications table exists for the mobile API
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure the user_sessions table exists for the mobile API
CREATE TABLE IF NOT EXISTS user_sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    device_info TEXT,
    ip_address VARCHAR(45),
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user if not exists
INSERT IGNORE INTO admins (username, email, first_name, last_name, password) 
VALUES 
('admin', 'admin@phinmaed.com', 'Admin', 'User', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert test admin user if not exists
INSERT IGNORE INTO admins (username, email, first_name, last_name, password) 
VALUES 
('jede.garcia.up@phinmaed.com', 'jede.garcia.up@phinmaed.com', 'Jede', 'Garcia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Create a trigger to automatically create admin notifications when a new request is submitted
DELIMITER //
CREATE TRIGGER IF NOT EXISTS after_request_insert
AFTER INSERT ON requests
FOR EACH ROW
BEGIN
    -- Get the request type name
    DECLARE request_type_name VARCHAR(100);
    SELECT name INTO request_type_name FROM request_types WHERE type_id = NEW.type_id;
    
    -- Get all admin IDs
    DECLARE done INT DEFAULT FALSE;
    DECLARE admin_id_var INT;
    DECLARE admin_cursor CURSOR FOR SELECT admin_id FROM admins;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN admin_cursor;
    
    admin_loop: LOOP
        FETCH admin_cursor INTO admin_id_var;
        IF done THEN
            LEAVE admin_loop;
        END IF;
        
        -- Insert notification for each admin
        INSERT INTO admin_notifications (admin_id, title, message, user_id, is_read)
        VALUES (admin_id_var, 'New Request', CONCAT('New request for ', request_type_name, ' (', NEW.tracking_number, ')'), NEW.user_id, 0);
    END LOOP;
    
    CLOSE admin_cursor;
    
    -- Also create a notification for the user
    INSERT INTO notifications (user_id, title, message, is_read)
    VALUES (NEW.user_id, 'Request Submitted', CONCAT('Your request for ', request_type_name, ' has been submitted. Tracking number: ', NEW.tracking_number), 0);
END //
DELIMITER ;

-- Create a trigger to notify users when their request status changes
DELIMITER //
CREATE TRIGGER IF NOT EXISTS after_request_update
AFTER UPDATE ON requests
FOR EACH ROW
BEGIN
    IF NEW.status != OLD.status THEN
        -- Get the request type name
        DECLARE request_type_name VARCHAR(100);
        SELECT name INTO request_type_name FROM request_types WHERE type_id = NEW.type_id;
        
        -- Create a notification for the user
        INSERT INTO notifications (user_id, title, message, is_read)
        VALUES (NEW.user_id, 'Request Status Updated', 
                CONCAT('Your request for ', request_type_name, ' (', NEW.tracking_number, ') has been updated to ', 
                       CASE 
                           WHEN NEW.status = 'pending' THEN 'Pending'
                           WHEN NEW.status = 'approved' THEN 'Approved'
                           WHEN NEW.status = 'rejected' THEN 'Rejected'
                           WHEN NEW.status = 'in_progress' THEN 'In Progress'
                           WHEN NEW.status = 'completed' THEN 'Completed'
                           ELSE NEW.status
                       END), 0);
    END IF;
END //
DELIMITER ; 