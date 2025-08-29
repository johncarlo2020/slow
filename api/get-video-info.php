<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

class VideoInfo {
    private $processedDir;

    public function __construct() {
        $this->processedDir = '../processed/';
    }

    public function getVideoInfo() {
        try {
            $filename = $_GET['filename'] ?? '';
            
            if (empty($filename)) {
                throw new Exception('Missing filename parameter');
            }

            // Security check
            if (!$this->isValidFilename($filename)) {
                throw new Exception('Invalid filename');
            }

            $filePath = $this->processedDir . $filename;
            
            if (!file_exists($filePath)) {
                throw new Exception('Video file not found');
            }

            if (!$this->isVideoFile($filename)) {
                throw new Exception('File is not a video');
            }

            $videoInfo = [
                'filename' => $filename,
                'displayName' => $this->getDisplayName($filename),
                'path' => 'processed/' . $filename,
                'size' => filesize($filePath),
                'created' => filemtime($filePath)
            ];

            // Generate download URL
            $downloadUrl = 'processed/' . $filename;

            echo json_encode([
                'success' => true,
                'video' => $videoInfo,
                'downloadUrl' => $downloadUrl,
                'qrUrl' => $this->generateQRUrl($downloadUrl)
            ]);

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

    private function getDisplayName($filename) {
        // Remove file extension
        $name = pathinfo($filename, PATHINFO_FILENAME);
        
        // Remove common prefixes and suffixes
        $name = preg_replace('/^slowmo_pro_/', '', $name);
        $name = preg_replace('/_\d+$/', '', $name); // Remove timestamp
        
        // Replace underscores with spaces and capitalize
        $name = str_replace('_', ' ', $name);
        $name = ucwords($name);
        
        // If name is empty or too generic, use the original filename
        if (empty($name) || strlen($name) < 3) {
            $name = pathinfo($filename, PATHINFO_FILENAME);
        }
        
        return $name;
    }

    private function generateQRUrl($downloadUrl) {
        // Get current domain and protocol
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $basePath = dirname($_SERVER['REQUEST_URI']);
        
        // Remove /api from path if present
        $basePath = str_replace('/api', '', $basePath);
        
        return $protocol . '://' . $host . $basePath . '/' . $downloadUrl;
    }
}

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $videoInfo = new VideoInfo();
    $videoInfo->getVideoInfo();
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?>
