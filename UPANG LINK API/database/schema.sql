-- Create database
CREATE DATABASE IF NOT EXISTS upang_link;
USE upang_link;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_number VARCHAR(20) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    course VARCHAR(100) NOT NULL,
    year_level INT NOT NULL,
    block VARCHAR(10) NOT NULL,
    admission_year VARCHAR(4) NOT NULL,
    is_email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Request types table
CREATE TABLE IF NOT EXISTS request_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    processing_time VARCHAR(50) NOT NULL,
    fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Requirements table
CREATE TABLE IF NOT EXISTS requirements (
    id VARCHAR(36) PRIMARY KEY,
    type_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_required BOOLEAN DEFAULT TRUE,
    allowed_file_types JSON NOT NULL,
    max_file_size BIGINT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (type_id) REFERENCES request_types(id)
);

-- Requests table
CREATE TABLE IF NOT EXISTS requests (
    id VARCHAR(36) PRIMARY KEY,
    student_id INT NOT NULL,
    type_id INT NOT NULL,
    purpose TEXT NOT NULL,
    status ENUM('DRAFT', 'PENDING', 'IN_REVIEW', 'NEEDS_REVISION', 'PROCESSING', 'READY_FOR_PICKUP', 'COMPLETED', 'CANCELLED', 'REJECTED') NOT NULL DEFAULT 'PENDING',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (type_id) REFERENCES request_types(id)
);

-- Requirement submissions table
CREATE TABLE IF NOT EXISTS requirement_submissions (
    request_id VARCHAR(36) NOT NULL,
    requirement_id VARCHAR(36) NOT NULL,
    file_url VARCHAR(255) NOT NULL,
    status ENUM('PENDING', 'SUBMITTED', 'VERIFIED', 'REJECTED') NOT NULL DEFAULT 'SUBMITTED',
    remarks TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_at TIMESTAMP NULL,
    PRIMARY KEY (request_id, requirement_id),
    FOREIGN KEY (request_id) REFERENCES requests(id) ON DELETE CASCADE,
    FOREIGN KEY (requirement_id) REFERENCES requirements(id)
);

-- Password reset tokens table
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Email verification tokens table
CREATE TABLE IF NOT EXISTS email_verification_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(100) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Create indexes
CREATE INDEX idx_requests_student_id ON requests(student_id);
CREATE INDEX idx_requests_type_id ON requests(type_id);
CREATE INDEX idx_requests_status ON requests(status);
CREATE INDEX idx_requirements_type_id ON requirements(type_id);
CREATE INDEX idx_requirement_submissions_status ON requirement_submissions(status);

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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Categories table
CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE
);

-- Request notes table (for admin comments and additional information)
CREATE TABLE request_notes (
    note_id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    user_id INT NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
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
    FOREIGN KEY (request_id) REFERENCES requests(id)
);

-- Notifications table
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert default categories
INSERT INTO categories (name, description) VALUES
('Academic Documents', 'Transcripts, certificates, and other academic records'),
('Student ID', 'Student identification card and related items (1x1 ID photo required)'),
('Uniforms', 'School uniform requests'),
('Books and Modules', 'Academic materials and learning resources');

-- Insert sample request types
INSERT INTO request_types (name, description, processing_time, fee) VALUES
('Transcript of Records', 'Official academic transcript', '5-7 working days', 0.00),
('Enrollment Certificate', 'Proof of enrollment document', '2-3 working days', 0.00),
('New Student ID', 'First time ID request', '5-7 working days', 0.00),
('ID Replacement', 'Lost or damaged ID replacement', '5-7 working days', 0.00),
('PE Uniform Request', 'Physical Education uniform set', '3-5 working days', 0.00),
('School Uniform Request', 'Regular school uniform set', '3-5 working days', 0.00),
('Course Module Request', 'Subject-specific learning materials', '1-2 working days', 0.00);

-- Insert default admin user
INSERT INTO users (student_number, password_hash, first_name, last_name, course, year_level, block, admission_year) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'BSIT', 3, 'BN', '2021');

-- Insert sample student accounts with proper student ID format
INSERT INTO users (student_number, password_hash, first_name, last_name, course, year_level, block, admission_year) VALUES
('0001-2021-00123', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Matthew Cymon', 'Estrada', 'BSIT', 3, 'BN', '2021');

-- Create table for requirement templates
CREATE TABLE requirement_templates (
    template_id INT PRIMARY KEY AUTO_INCREMENT,
    type_id INT NOT NULL,
    requirement_name VARCHAR(100) NOT NULL,
    description TEXT,
    file_types VARCHAR(255), -- Allowed file types (e.g., "pdf,jpg,png")
    is_required BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (type_id) REFERENCES request_types(id)
);

-- Create table for request requirement notes
CREATE TABLE request_requirement_notes (
    note_id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    admin_id INT NOT NULL,
    requirement_name VARCHAR(100) NOT NULL,
    note TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(id),
    FOREIGN KEY (admin_id) REFERENCES users(id)
);

-- Update request types with both required and optional fields
UPDATE request_types 
SET requirements = JSON_OBJECT(
    'fields', JSON_ARRAY(
        -- For Transcript of Records
        CASE WHEN name = 'Transcript of Records' THEN 
            JSON_ARRAY(
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
                ),
                JSON_OBJECT(
                    'name', 'additional_docs',
                    'label', 'Additional Supporting Documents',
                    'type', 'file',
                    'required', false,
                    'allowed_types', 'pdf,jpg,png,doc,docx',
                    'description', 'Any additional documents to support your request (optional)'
                )
            )
        -- For ID Replacement
        WHEN name = 'ID Replacement' THEN
            JSON_ARRAY(
                JSON_OBJECT(
                    'name', 'affidavit_loss',
                    'label', 'Affidavit of Loss',
                    'type', 'file',
                    'required', true,
                    'allowed_types', 'pdf',
                    'description', 'Notarized affidavit of loss'
                ),
                JSON_OBJECT(
                    'name', 'id_picture',
                    'label', '1x1 ID Picture',
                    'type', 'file',
                    'required', true,
                    'allowed_types', 'jpg,png',
                    'description', 'Recent 1x1 ID picture with white background'
                ),
                JSON_OBJECT(
                    'name', 'payment_receipt',
                    'label', 'Payment Receipt',
                    'type', 'file',
                    'required', false,
                    'allowed_types', 'pdf,jpg,png',
                    'description', 'Receipt of payment for ID replacement (can be submitted later)'
                ),
                JSON_OBJECT(
                    'name', 'remarks',
                    'label', 'Additional Remarks',
                    'type', 'text',
                    'required', false,
                    'description', 'Any additional information about your ID replacement request'
                )
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