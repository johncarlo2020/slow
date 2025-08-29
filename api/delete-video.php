<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

class VideoDeleter {
    private $processedDir;

    public function __construct() {
        $this->processedDir = '../processed/';
    }

    public function deleteVideo() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['filename'])) {
                throw new Exception('Missing filename parameter');
            }

            $filename = $input['filename'];
            
            // Validate filename (security check)
            if (!$this->isValidFilename($filename)) {
                throw new Exception('Invalid filename');
            }

            $filePath = $this->processedDir . $filename;
            
            // Check if file exists
            if (!file_exists($filePath)) {
                throw new Exception('Video file not found');
            }

            // Check if it's actually a video file
            if (!$this->isVideoFile($filename)) {
                throw new Exception('File is not a video');
            }

            // Delete the file
            if (unlink($filePath)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Video deleted successfully'
                ]);
            } else {
                throw new Exception('Failed to delete video file');
            }

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function isValidFilename($filename) {
        // Basic security checks
        if (empty($filename)) return false;
        if (strpos($filename, '..') !== false) return false; // Prevent directory traversal
        if (strpos($filename, '/') !== false) return false;  // No path separators
        if (strpos($filename, '\\') !== false) return false; // No Windows path separators
        
        return true;
    }

    private function isVideoFile($filename) {
        $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'webm', 'mkv', 'flv'];
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, $videoExtensions);
    }
}

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deleter = new VideoDeleter();
    $deleter->deleteVideo();
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?>
