-- Create database
CREATE DATABASE IF NOT EXISTS upang_link;
USE upang_link;

-- Users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('student', 'admin') NOT NULL,
    email_verified BOOLEAN DEFAULT FALSE,
    email_verification_token VARCHAR(255),
    email_token_expiry DATETIME,
    reset_password_token VARCHAR(255),
    reset_token_expiry DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User sessions table
CREATE TABLE user_sessions (
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
);

-- Categories table
CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE
);

-- Request types table
CREATE TABLE request_types (
    type_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    requirements JSON,
    processing_time VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- Requests table
CREATE TABLE requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    tracking_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    type_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'in_progress', 'completed') DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (type_id) REFERENCES request_types(type_id)
);




-- Create admins table
CREATE TABLE IF NOT EXISTS admins (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role VARCHAR(50) NOT NULL,
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












-- Request notes table (for admin comments and additional information)
CREATE TABLE request_notes (
    note_id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    user_id INT NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(request_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Required documents table (for tracking required document submissions)
CREATE TABLE required_documents (
    document_id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_verified BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (request_id) REFERENCES requests(request_id)
);

-- Notifications table
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Create request_files table
CREATE TABLE IF NOT EXISTS request_files (
    file_id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    original_name VARCHAR(255) NOT NULL COMMENT 'Original filename from user',
    file_name VARCHAR(255) NOT NULL COMMENT 'System generated filename',
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(request_id) ON DELETE CASCADE
);

-- Insert default categories
INSERT INTO categories (name, description) VALUES
('Academic Documents', 'Transcripts, certificates, and other academic records'),
('Student ID', 'Student identification card and related items (1x1 ID photo required)'),
('Uniforms', 'School uniform requests'),
('Books and Modules', 'Academic materials and learning resources');

-- Insert user accounts
-- Admin account
INSERT INTO users (email, password, first_name, last_name, role, email_verified) VALUES
('jede.garcia.up@phinmaed.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jede', 'Garcia', 'admin', 1);

-- Student account
INSERT INTO users (email, password, first_name, last_name, role, email_verified) VALUES
('jerickogarcia0@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jericko', 'Garcia', 'student', 1);

-- Insert sample request types
INSERT INTO request_types (category_id, name, description, requirements, processing_time) VALUES
(1, 'Transcript of Records', 'Official academic transcript', 
 JSON_OBJECT('required_docs', JSON_ARRAY('Clearance form', 'Request letter')), 
 '5-7 working days'),
(1, 'Enrollment Certificate', 'Proof of enrollment document', 
 JSON_OBJECT('required_docs', JSON_ARRAY('Valid student ID')), 
 '2-3 working days'),
(2, 'New Student ID', 'First time ID request', 
 JSON_OBJECT('required_docs', JSON_ARRAY('1x1 ID Picture (white background, formal attire)', 'Registration Form')), 
 '5-7 working days'),
(2, 'ID Replacement', 'Lost or damaged ID replacement', 
 JSON_OBJECT('required_docs', JSON_ARRAY('Affidavit of Loss', '1x1 ID Picture (white background, formal attire)')), 
 '5-7 working days'),
(3, 'PE Uniform Request', 'Physical Education uniform set', 
 JSON_OBJECT('required_docs', JSON_ARRAY('Valid student ID')), 
 '3-5 working days'),
(3, 'School Uniform Request', 'Regular school uniform set', 
 JSON_OBJECT('required_docs', JSON_ARRAY('Valid student ID')), 
 '3-5 working days'),
(4, 'Course Module Request', 'Subject-specific learning materials', 
 JSON_OBJECT('required_docs', JSON_ARRAY('Valid student ID', 'Professor approval')), 
 '1-2 working days'); 