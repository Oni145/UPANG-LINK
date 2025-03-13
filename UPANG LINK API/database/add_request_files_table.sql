-- Create request_files table
CREATE TABLE IF NOT EXISTS request_files (
    file_id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    field_name VARCHAR(100) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES requests(request_id) ON DELETE CASCADE
); 