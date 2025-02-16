-- Create database
CREATE DATABASE IF NOT EXISTS upang_link;
USE upang_link;

-- Users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    student_number VARCHAR(50) UNIQUE,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('student', 'admin', 'staff') NOT NULL,
    course VARCHAR(100),
    year_level INT,
    block VARCHAR(10),
    admission_year VARCHAR(4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Admins table
CREATE TABLE admins (
    admin_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Admin tokens table
CREATE TABLE admin_tokens (
    token CHAR(64) PRIMARY KEY,
    admin_id INT NOT NULL,
    login_time DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (admin_id) REFERENCES admins(admin_id)
);

-- Auth tokens table
CREATE TABLE auth_tokens (
    token CHAR(64) PRIMARY KEY,
    user_id INT NOT NULL,
    login_time DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
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
    user_id INT NOT NULL,
    type_id INT NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'in_progress', 'completed') DEFAULT 'pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (type_id) REFERENCES request_types(type_id)
);

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

-- Requirement templates table
CREATE TABLE requirement_templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    type_id INT NOT NULL,
    requirement_name VARCHAR(100) NOT NULL,
    description TEXT,
    file_types VARCHAR(255), -- Allowed file types (e.g., "pdf,jpg,png")
    is_required BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (type_id) REFERENCES request_types(type_id)
);

-- Request requirement notes table
CREATE TABLE request_requirement_notes (
    note_id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    admin_id INT NOT NULL,
    requirement_name VARCHAR(100) NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(request_id),
    FOREIGN KEY (admin_id) REFERENCES users(user_id)
);

-- Insert default categories
INSERT INTO categories (name, description) VALUES
('Academic Documents', 'Transcripts, certificates, and other academic records'),
('Student ID', 'Student identification card and related items (1x1 ID photo required)'),
('Uniforms', 'School uniform requests'),
('Books and Modules', 'Academic materials and learning resources');

-- Insert sample request types using proper JSON arrays for the requirements field
INSERT INTO request_types (category_id, name, description, requirements, processing_time) VALUES
(1, 'Transcript of Records', 'Official academic transcript', JSON_ARRAY('Clearance form', 'Request letter'), '5-7 working days'),
(1, 'Enrollment Certificate', 'Proof of enrollment document', JSON_ARRAY('Valid student ID'), '2-3 working days'),
(2, 'New Student ID', 'First time ID request', JSON_ARRAY('1x1 ID Picture (white background, formal attire)', 'Registration Form'), '5-7 working days'),
(2, 'ID Replacement', 'Lost or damaged ID replacement', JSON_ARRAY('Affidavit of Loss', '1x1 ID Picture (white background, formal attire)'), '5-7 working days'),
(3, 'PE Uniform Request', 'Physical Education uniform set', JSON_ARRAY('Valid student ID'), '3-5 working days'),
(3, 'School Uniform Request', 'Regular school uniform set', JSON_ARRAY('Valid student ID'), '3-5 working days'),
(4, 'Course Module Request', 'Subject-specific learning materials', JSON_ARRAY('Valid student ID', 'Professor approval'), '1-2 working days');

-- Insert default admin user into the users table
INSERT INTO users (student_number, password, first_name, last_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin');

-- Insert sample student account
INSERT INTO users (student_number, password, first_name, last_name, role, course, year_level, block, admission_year) VALUES
('0001-2021-00123', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Matthew Cymon', 'Estrada', 'student', 'BSIT', 3, 'BN', '2021');

-- Ensure the requirements column is JSON type (if not already)
ALTER TABLE request_types 
MODIFY COLUMN requirements JSON;

-- Optionally update request types with detailed JSON requirements
UPDATE request_types 
SET requirements = JSON_OBJECT(
    'fields', JSON_ARRAY(
        CASE WHEN name = 'Transcript of Records' THEN 
            JSON_ARRAY(
                JSON_OBJECT('name', 'clearance_form', 'label', 'Clearance Form', 'type', 'file', 'required', true, 'allowed_types', 'pdf,jpg,png', 'description', 'Fully accomplished clearance form'),
                JSON_OBJECT('name', 'request_letter', 'label', 'Request Letter', 'type', 'file', 'required', true, 'allowed_types', 'pdf,doc,docx', 'description', 'Formal letter stating the purpose of requesting TOR'),
                JSON_OBJECT('name', 'purpose', 'label', 'Purpose', 'type', 'text', 'required', true, 'description', 'State the purpose of requesting TOR'),
                JSON_OBJECT('name', 'additional_docs', 'label', 'Additional Supporting Documents', 'type', 'file', 'required', false, 'allowed_types', 'pdf,jpg,png,doc,docx', 'description', 'Any additional documents to support your request (optional)')
            )
        WHEN name = 'ID Replacement' THEN
            JSON_ARRAY(
                JSON_OBJECT('name', 'affidavit_loss', 'label', 'Affidavit of Loss', 'type', 'file', 'required', true, 'allowed_types', 'pdf', 'description', 'Notarized affidavit of loss'),
                JSON_OBJECT('name', 'id_picture', 'label', '1x1 ID Picture', 'type', 'file', 'required', true, 'allowed_types', 'jpg,png', 'description', 'Recent 1x1 ID picture with white background'),
                JSON_OBJECT('name', 'payment_receipt', 'label', 'Payment Receipt', 'type', 'file', 'required', false, 'allowed_types', 'pdf,jpg,png', 'description', 'Receipt of payment for ID replacement (can be submitted later)'),
                JSON_OBJECT('name', 'remarks', 'label', 'Additional Remarks', 'type', 'text', 'required', false, 'description', 'Any additional information about your ID replacement request')
            )
        END
    ),
    'instructions', CASE 
        WHEN name = 'Transcript of Records' THEN 'Please ensure all required documents are complete. Additional supporting documents are optional but may help process your request faster.'
        WHEN name = 'ID Replacement' THEN 'Submit the required documents. Payment receipt can be submitted later but must be provided before ID release.'
        ELSE 'Please submit all required documents.'
    END
)
WHERE name IN ('Transcript of Records', 'ID Replacement');
