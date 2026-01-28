<?php
// Enable error reporting for debugging but don't display errors (they corrupt JSON)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

class VideoUploader {
    private $uploadDir;
    private $photoUploadDir;
    private $allowedVideoTypes;
    private $allowedImageTypes;
    private $maxFileSize;

    public function __construct() {
        $this->uploadDir = '../uploads/';
        $this->photoUploadDir = '../uploads/photos/';
        $this->allowedVideoTypes = ['video/mp4', 'video/avi', 'video/mov', 'video/wmv', 'video/webm'];
        $this->allowedImageTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $this->maxFileSize = 100 * 1024 * 1024; // 100MB
        
        // Create upload directories if they don't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        if (!is_dir($this->photoUploadDir)) {
            mkdir($this->photoUploadDir, 0755, true);
        }
    }

    public function handleUpload() {
        try {
            // Check if this is a photo or video upload
            if (isset($_FILES['photo'])) {
                return $this->handlePhotoUpload($_FILES['photo']);
            } elseif (isset($_FILES['video'])) {
                return $this->handleVideoUpload($_FILES['video']);
            } else {
                throw new Exception('No file uploaded');
            }

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function handleVideoUpload($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload error: ' . $this->getUploadErrorMessage($file['error']));
        }

        // Validate file type
        if (!in_array($file['type'], $this->allowedVideoTypes)) {
            throw new Exception('Invalid file type. Only video files are allowed.');
        }

        // Validate file size
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('File size exceeds maximum limit of 100MB');
        }

        // Generate filename with sortable timestamp
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $timestamp = date('Ymd-His');
        $filename = 'video_' . $timestamp . '.' . $extension;
        $destination = $this->uploadDir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to save uploaded file');
        }

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Video uploaded successfully',
            'videoUrl' => 'uploads/' . $filename,
            'filename' => $filename
        ]);
    }

    private function handlePhotoUpload($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload error: ' . $this->getUploadErrorMessage($file['error']));
        }

        // Validate file type
        if (!in_array(strtolower($file['type']), $this->allowedImageTypes)) {
            throw new Exception('Invalid file type. Only JPG and PNG images are allowed.');
        }

        // Validate file size (10MB for images)
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('Photo size exceeds maximum limit of 10MB');
        }

        // Generate filename with sortable timestamp
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $timestamp = date('Ymd-His');
        $filename = 'photo_' . $timestamp . '.' . $extension;
        $destination = $this->photoUploadDir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to save uploaded photo');
        }

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Photo uploaded successfully',
            'photoUrl' => 'uploads/photos/' . $filename,
            'filename' => $filename
        ]);
    }

    private function getUploadErrorMessage($errorCode) {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File too large (exceeds upload_max_filesize)';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File too large (exceeds MAX_FILE_SIZE)';
            case UPLOAD_ERR_PARTIAL:
                return 'File only partially uploaded';
            case UPLOAD_ERR_NO_FILE:
                return 'No file uploaded';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing temporary folder';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk';
            case UPLOAD_ERR_EXTENSION:
                return 'Upload stopped by extension';
            default:
                return 'Unknown upload error';
        }
    }
}

// Handle the upload request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploader = new VideoUploader();
    $uploader->handleUpload();
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}