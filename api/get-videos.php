<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

class VideoManager {
    private $processedDir;

    public function __construct() {
        $this->processedDir = '../processed/';
    }

    public function getVideos() {
        try {
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 9;
            $offset = ($page - 1) * $limit;

            // Get all video files
            $allVideos = $this->getAllVideos();
            
            // Sort by creation time (newest first)
            usort($allVideos, function($a, $b) {
                return $b['created'] - $a['created'];
            });

            $totalVideos = count($allVideos);
            $totalPages = ceil($totalVideos / $limit);
            
            // Get videos for current page
            $videos = array_slice($allVideos, $offset, $limit);
            
            // Calculate stats
            $stats = $this->calculateStats($allVideos);

            echo json_encode([
                'success' => true,
                'videos' => $videos,
                'total' => $totalVideos,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => $totalPages,
                'stats' => $stats
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function getAllVideos() {
        $videos = [];
        
        if (!is_dir($this->processedDir)) {
            return $videos;
        }

        $files = scandir($this->processedDir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filePath = $this->processedDir . $file;
            
            // Only include video files
            if (!$this->isVideoFile($file) || !is_file($filePath)) continue;
            
            $videos[] = [
                'filename' => $file,
                'displayName' => $this->getDisplayName($file),
                'path' => 'processed/' . $file,
                'size' => filesize($filePath),
                'created' => filemtime($filePath)
            ];
        }
        
        return $videos;
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

    private function calculateStats($videos) {
        if (empty($videos)) {
            return [
                'totalVideos' => 0,
                'totalSize' => 0,
                'newestVideo' => null,
                'oldestVideo' => null
            ];
        }

        $totalSize = array_sum(array_column($videos, 'size'));
        $createdTimes = array_column($videos, 'created');
        
        return [
            'totalVideos' => count($videos),
            'totalSize' => $totalSize,
            'newestVideo' => max($createdTimes),
            'oldestVideo' => min($createdTimes)
        ];
    }
}

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $manager = new VideoManager();
    $manager->getVideos();
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?>
