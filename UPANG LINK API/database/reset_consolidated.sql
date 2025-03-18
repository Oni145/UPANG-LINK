-- Reset consolidated script for UPANG LINK database
-- This script handles the entire database creation in the correct order

-- Drop existing database and create a new one
DROP DATABASE IF EXISTS upanglink_db;
CREATE DATABASE upanglink_db;
USE upanglink_db;

-- Users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('student', 'admin', 'superadmin') NOT NULL DEFAULT 'student',
    student_number VARCHAR(50) UNIQUE,
    birthdate DATE,
    emergency_contact VARCHAR(100),
    course VARCHAR(100),
    current_year VARCHAR(20),
    email_verified BOOLEAN DEFAULT FALSE,
    details_complete BOOLEAN DEFAULT FALSE,
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
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    requirements JSON,
    processing_time VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
);

-- Requests table
CREATE TABLE requests (
    request_id INT PRIMARY KEY AUTO_INCREMENT,
    tracking_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    type_id INT NOT NULL,
    purpose TEXT,
    status ENUM('PENDING', 'PROCESSING', 'COMPLETED', 'REJECTED') DEFAULT 'PENDING',
    admin_remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (type_id) REFERENCES request_types(type_id) ON DELETE CASCADE
);

-- Request notes table (for admin comments and additional information)
CREATE TABLE request_notes (
    note_id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    user_id INT NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
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
    FOREIGN KEY (request_id) REFERENCES requests(request_id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create request_files table
CREATE TABLE request_files (
    file_id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    field_name VARCHAR(100),
    original_name VARCHAR(255) NOT NULL COMMENT 'Original filename from user',
    file_name VARCHAR(255) NOT NULL COMMENT 'System generated filename',
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(request_id) ON DELETE CASCADE
);

-- Create table for requirement templates
CREATE TABLE requirement_templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    type_id INT NOT NULL,
    requirement_name VARCHAR(100) NOT NULL,
    description TEXT,
    file_types VARCHAR(255),
    is_required BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (type_id) REFERENCES request_types(type_id) ON DELETE CASCADE
);

-- Create table for request requirement notes
CREATE TABLE request_requirement_notes (
    note_id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    admin_id INT NOT NULL,
    requirement_name VARCHAR(100) NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(request_id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE CASCADE
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
('admin@upang.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', 1);

-- Student account
INSERT INTO users (email, password, first_name, last_name, role, email_verified) VALUES
('student@upang.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Student', 'User', 'student', 1);

-- Insert sample request types
INSERT INTO request_types (category_id, name, description, requirements, processing_time) VALUES
(1, 'Transcript of Records', 'Official academic transcript', 
 JSON_OBJECT('fields', JSON_ARRAY(
    JSON_OBJECT(
        'name', 'clearance_form',
        'label', 'Clearance Form',
        'type', 'file',
        'required', true,
        'allowed_types', 'pdf,jpg,png',
        'description', 'Fully accomplished clearance form'
    ),
    JSON_OBJECT(
        'name', 'request_letter',
        'label', 'Request Letter',
        'type', 'file',
        'required', true,
        'allowed_types', 'pdf,doc,docx',
        'description', 'Formal letter stating the purpose of requesting TOR'
    ),
    JSON_OBJECT(
        'name', 'purpose',
        'label', 'Purpose',
        'type', 'text',
        'required', true,
        'description', 'State the purpose of requesting TOR'
    )
 )), 
 '5-7 working days'),
 
(1, 'Enrollment Certificate', 'Proof of enrollment document', 
 JSON_OBJECT('fields', JSON_ARRAY(
    JSON_OBJECT(
        'name', 'student_id',
        'label', 'Student ID Number',
        'type', 'text',
        'required', true,
        'description', 'Enter your student ID number'
    ),
    JSON_OBJECT(
        'name', 'year_level',
        'label', 'Year Level',
        'type', 'dropdown',
        'required', true,
        'description', 'Select your current year level',
        'options', JSON_ARRAY('1st Year', '2nd Year', '3rd Year', '4th Year', '5th Year')
    ),
    JSON_OBJECT(
        'name', 'purpose',
        'label', 'Purpose',
        'type', 'text',
        'required', true,
        'description', 'State the purpose of requesting the Enrollment Certificate'
    )
 )),
 '2-3 working days'),
 
(2, 'New Student ID', 'First time ID request', 
 JSON_OBJECT('fields', JSON_ARRAY(
    JSON_OBJECT(
        'name', 'id_photo',
        'label', '1x1 ID Photo',
        'type', 'file',
        'required', true,
        'allowed_types', 'jpg,jpeg,png',
        'description', 'Upload a 1x1 ID photo with white background'
    ),
    JSON_OBJECT(
        'name', 'signature',
        'label', 'Signature',
        'type', 'file',
        'required', true,
        'allowed_types', 'jpg,jpeg,png,pdf',
        'description', 'Upload a clear image of your signature on white paper'
    )
 )),
 '5-7 working days'),
 
(2, 'ID Replacement', 'Lost or damaged ID replacement', 
 JSON_OBJECT('fields', JSON_ARRAY(
    JSON_OBJECT(
        'name', 'affidavit_of_loss',
        'label', 'Affidavit of Loss',
        'type', 'file',
        'required', true,
        'allowed_types', 'pdf,jpg,jpeg,png',
        'description', 'Upload a scanned copy of your Affidavit of Loss'
    ),
    JSON_OBJECT(
        'name', 'payment_receipt',
        'label', 'Payment Receipt',
        'type', 'file',
        'required', true,
        'allowed_types', 'pdf,jpg,jpeg,png',
        'description', 'Upload a scanned copy of your Payment Receipt'
    )
 )),
 '5-7 working days'),
 
(3, 'PE Uniform Request', 'Physical Education uniform set', 
 JSON_OBJECT('fields', JSON_ARRAY(
    JSON_OBJECT(
        'name', 'uniform_size',
        'label', 'PE Uniform Size',
        'type', 'dropdown',
        'required', true,
        'description', 'Please select your uniform size (check size chart for measurements)',
        'options', JSON_ARRAY('XXS (0)', 'XS (2-4)', 'S (6-8)', 'M (10-12)', 'L (14-16)', 'XL (18-20)', '2XL (22-24)', '3XL (26-28)', '4XL (30-32)')
    )
 )),
 '3-5 working days'),
 
(3, 'School Uniform Request', 'Regular school uniform set', 
 JSON_OBJECT('fields', JSON_ARRAY(
    JSON_OBJECT(
        'name', 'uniform_size',
        'label', 'School Uniform Size',
        'type', 'dropdown',
        'required', true,
        'description', 'Please select your uniform size (check size chart for measurements)',
        'options', JSON_ARRAY('XXS (0)', 'XS (2-4)', 'S (6-8)', 'M (10-12)', 'L (14-16)', 'XL (18-20)', '2XL (22-24)', '3XL (26-28)', '4XL (30-32)')
    )
 )),
 '3-5 working days'),
 
(4, 'Course Module Request', 'Subject-specific learning materials', 
 JSON_OBJECT('fields', JSON_ARRAY(
    JSON_OBJECT(
        'name', 'course_name',
        'label', 'Course Name',
        'type', 'text',
        'required', true,
        'description', 'Enter the name of the course/subject'
    )
 )),
 '1-2 working days'); 