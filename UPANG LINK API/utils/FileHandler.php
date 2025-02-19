<?php

class FileHandler {
    private $uploadDir;
    private $allowedMimeTypes;
    private $maxFileSize;

    public function __construct() {
        // Set upload directory relative to the project root
        $this->uploadDir = dirname(__DIR__) . '/uploads/';
        
        // Initialize allowed MIME types
        $this->allowedMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/jpg',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];

        // Default max file size (5MB)
        $this->maxFileSize = 5 * 1024 * 1024;

        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Upload a file to the server
     * 
     * @param array $file The file data from $_FILES
     * @param string $subDirectory Optional subdirectory within uploads
     * @return string The URL of the uploaded file
     * @throws Exception if file upload fails
     */
    public function uploadFile($file, $subDirectory = '') {
        try {
            // Validate file
            $this->validateFile($file);

            // Create subdirectory if provided
            $targetDir = $this->uploadDir;
            if ($subDirectory) {
                $targetDir .= trim($subDirectory, '/') . '/';
                if (!file_exists($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '_' . time() . '.' . $extension;
            $targetPath = $targetDir . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception('Failed to move uploaded file');
            }

            // Return relative path from uploads directory
            return ($subDirectory ? $subDirectory . '/' : '') . $filename;
        } catch (Exception $e) {
            throw new Exception('File upload failed: ' . $e->getMessage());
        }
    }

    /**
     * Delete a file from the server
     * 
     * @param string $fileUrl The relative URL of the file to delete
     * @return bool True if file was deleted successfully
     */
    public function deleteFile($fileUrl) {
        if (!$fileUrl) {
            return false;
        }

        $filePath = $this->uploadDir . $fileUrl;
        if (file_exists($filePath)) {
            return unlink($filePath);
        }

        return false;
    }

    /**
     * Validate uploaded file
     * 
     * @param array $file The file data from $_FILES
     * @throws Exception if validation fails
     */
    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadErrorMessage($file['error']));
        }

        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('File size exceeds maximum limit of ' . $this->formatFileSize($this->maxFileSize));
        }

        // Verify MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            throw new Exception('File type not allowed. Allowed types: PDF, JPEG, PNG, DOC, DOCX');
        }

        // Additional security checks
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Invalid upload attempt');
        }
    }

    /**
     * Get human-readable error message for upload errors
     * 
     * @param int $errorCode The error code from $_FILES['error']
     * @return string Human-readable error message
     */
    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload';
            default:
                return 'Unknown upload error';
        }
    }

    /**
     * Format file size to human-readable string
     * 
     * @param int $bytes File size in bytes
     * @return string Formatted file size
     */
    private function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
    }

    /**
     * Set maximum allowed file size
     * 
     * @param int $size Size in bytes
     */
    public function setMaxFileSize($size) {
        $this->maxFileSize = $size;
    }

    /**
     * Set allowed MIME types
     * 
     * @param array $types Array of MIME types
     */
    public function setAllowedMimeTypes($types) {
        $this->allowedMimeTypes = $types;
    }
} 