<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

class AssetUploader {
    private $templateDir;
    private $audioDir;
    private $allowedTemplateTypes;
    private $allowedAudioTypes;
    private $maxTemplateSize;
    private $maxAudioSize;

    public function __construct() {
        $this->templateDir = '../template/';
        $this->audioDir = '../audio/';
        $this->allowedTemplateTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/webp'];
        $this->allowedAudioTypes = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/aac', 'audio/ogg', 'audio/m4a'];
        $this->maxTemplateSize = 10 * 1024 * 1024; // 10MB
        $this->maxAudioSize = 20 * 1024 * 1024; // 20MB
        
        // Create directories if they don't exist
        if (!is_dir($this->templateDir)) {
            mkdir($this->templateDir, 0755, true);
        }
        if (!is_dir($this->audioDir)) {
            mkdir($this->audioDir, 0755, true);
        }
    }

    public function handleUpload() {
        try {
            if (!isset($_FILES['file']) || !isset($_POST['type'])) {
                throw new Exception('Missing file or type parameter');
            }

            $file = $_FILES['file'];
            $type = $_POST['type'];
            
            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Upload error: ' . $this->getUploadErrorMessage($file['error']));
            }

            if ($type === 'template') {
                $this->uploadTemplate($file);
            } elseif ($type === 'audio') {
                $this->uploadAudio($file);
            } else {
                throw new Exception('Invalid upload type. Must be "template" or "audio"');
            }

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function uploadTemplate($file) {
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedTemplateTypes)) {
            throw new Exception('Invalid template file type. Only PNG, JPG, GIF, and WebP images are allowed.');
        }

        // Validate file size
        if ($file['size'] > $this->maxTemplateSize) {
            throw new Exception('Template file size exceeds maximum limit of 10MB');
        }

        // Get file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Set standard filename (this will replace any existing template)
        $filename = 'Preview Screen V4.' . $extension;
        $destination = $this->templateDir . $filename;

        // Remove any existing template files
        $this->removeExistingFiles($this->templateDir, 'Preview Screen V4');

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to save template file');
        }

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Template uploaded successfully',
            'filename' => $filename,
            'path' => 'template/' . $filename,
            'type' => 'template'
        ]);
    }

    private function uploadAudio($file) {
        // Validate file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $this->allowedAudioTypes)) {
            throw new Exception('Invalid audio file type. Only MP3, WAV, AAC, and OGG files are allowed.');
        }

        // Validate file size
        if ($file['size'] > $this->maxAudioSize) {
            throw new Exception('Audio file size exceeds maximum limit of 20MB');
        }

        // Get file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Set standard filename (this will replace any existing audio)
        $filename = 'background_audio.' . $extension;
        $destination = $this->audioDir . $filename;

        // Remove any existing audio files
        $this->removeExistingFiles($this->audioDir, 'background_audio');

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Failed to save audio file');
        }

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Audio uploaded successfully',
            'filename' => $filename,
            'path' => 'audio/' . $filename,
            'type' => 'audio'
        ]);
    }

    private function removeExistingFiles($directory, $baseFilename) {
        $files = glob($directory . $baseFilename . '.*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
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
    $uploader = new AssetUploader();
    $uploader->handleUpload();
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?>
