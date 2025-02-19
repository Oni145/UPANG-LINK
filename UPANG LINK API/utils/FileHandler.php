<?php
class FileHandler {
    private $config;
    private $upload_dir;
    private $allowed_types;
    private $max_file_size;

    public function __construct() {
        $this->config = require_once __DIR__ . '/../config/config.php';
        $this->upload_dir = __DIR__ . '/../uploads/';
        $this->allowed_types = $this->config['security']['allowed_file_types'];
        $this->max_file_size = $this->config['security']['max_file_size'];

        // Create upload directory if it doesn't exist
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
    }

    public function uploadFile($file, $subdirectory = '') {
        // Validate file
        $validation = $this->validateFile($file);
        if ($validation !== true) {
            return ['status' => 'error', 'message' => $validation];
        }

        // Create subdirectory if provided
        $target_dir = $this->upload_dir;
        if ($subdirectory) {
            $target_dir .= trim($subdirectory, '/') . '/';
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0755, true);
            }
        }

        // Generate unique filename
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
        $target_path = $target_dir . $unique_filename;

        // Compress image if it's an image file
        if (in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
            if ($this->compressImage($file['tmp_name'], $target_path, 75)) {
                return [
                    'status' => 'success',
                    'filename' => $unique_filename,
                    'path' => str_replace($this->upload_dir, '', $target_path)
                ];
            }
        } else {
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                return [
                    'status' => 'success',
                    'filename' => $unique_filename,
                    'path' => str_replace($this->upload_dir, '', $target_path)
                ];
            }
        }

        return ['status' => 'error', 'message' => 'Failed to upload file'];
    }

    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return 'Upload failed with error code: ' . $file['error'];
        }

        // Check file size
        if ($file['size'] > $this->max_file_size) {
            return 'File size exceeds limit of ' . ($this->max_file_size / 1024 / 1024) . 'MB';
        }

        // Check file type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $this->allowed_types)) {
            return 'File type not allowed. Allowed types: ' . implode(', ', $this->allowed_types);
        }

        // Verify MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed_mimes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        ];

        if (!in_array($mime_type, $allowed_mimes)) {
            return 'Invalid file type detected';
        }

        // Additional security checks
        if (preg_match('/\.(php|phtml|php3|php4|php5|php7|phar|jar)$/i', $file['name'])) {
            return 'File type not allowed for security reasons';
        }

        return true;
    }

    private function compressImage($source, $destination, $quality) {
        $info = getimagesize($source);

        if ($info['mime'] == 'image/jpeg') {
            $image = imagecreatefromjpeg($source);
        } elseif ($info['mime'] == 'image/png') {
            $image = imagecreatefrompng($source);
        } else {
            return false;
        }

        // Compress and save
        if ($info['mime'] == 'image/jpeg') {
            return imagejpeg($image, $destination, $quality);
        } elseif ($info['mime'] == 'image/png') {
            return imagepng($image, $destination, round(9 * $quality / 100));
        }

        return false;
    }

    public function deleteFile($filepath) {
        $full_path = $this->upload_dir . trim($filepath, '/');
        if (file_exists($full_path) && is_file($full_path)) {
            return unlink($full_path);
        }
        return false;
    }

    public function getFileUrl($filepath) {
        return $this->config['app']['api_url'] . '/uploads/' . trim($filepath, '/');
    }
} 