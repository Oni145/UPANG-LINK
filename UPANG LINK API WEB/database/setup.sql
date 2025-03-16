-- Select the database
USE upang_link;

-- Drop tables if they exist to start fresh (only these tables)
DROP TABLE IF EXISTS admin_tokens;
DROP TABLE IF EXISTS admin_notifications;
DROP TABLE IF EXISTS admins;

-- Create admins table
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

-- Create admin_tokens table for authentication
CREATE TABLE IF NOT EXISTS admin_tokens (
    token CHAR(64) PRIMARY KEY,
    admin_id INT NOT NULL,
    login_time DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create admin_notifications table
CREATE TABLE IF NOT EXISTS admin_notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert a default admin user
-- Username: admin, Password: admin123 (hashed)
INSERT INTO admins (username, email, first_name, last_name, password) 
VALUES 
('admin', 'admin@phinmaed.com', 'Admin', 'User', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Insert a test admin user (the one you were trying to use)
INSERT INTO admins (username, email, first_name, last_name, password) 
VALUES 
('jede.garcia.up@phinmaed.com', 'jede.garcia.up@phinmaed.com', 'Jede', 'Garcia', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'); 