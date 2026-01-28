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
            
            // Try to find associated photo
            $photoPath = $this->findAssociatedPhoto($file);
            
            $videos[] = [
                'filename' => $file,
                'displayName' => $this->getDisplayName($file),
                'path' => 'processed/' . $file,
                'photoPath' => $photoPath,
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

    private function findAssociatedPhoto($videoFilename) {
        // Extract base name and timestamp from video filename
        // e.g., processed_video_20260128-065108_1769579477.mp4
        $photoDir = '../processed/photos/';
        
        if (!is_dir($photoDir)) {
            return null;
        }
        
        // Get all photos and try to match by timestamp pattern
        $photos = scandir($photoDir);
        
        // Extract timestamp from video filename (the part after the last underscore before extension)
        if (preg_match('/_([0-9]+)\.[^.]+$/', $videoFilename, $matches)) {
            $videoTimestamp = $matches[1];
            
            // Look for photo with similar timestamp (within a few seconds)
            foreach ($photos as $photo) {
                if ($photo === '.' || $photo === '..') continue;
                
                if (preg_match('/_([0-9]+)\.[^.]+$/', $photo, $photoMatches)) {
                    $photoTimestamp = $photoMatches[1];
                    
                    // If timestamps are within 10 seconds, consider them related
                    if (abs($videoTimestamp - $photoTimestamp) <= 10) {
                        return 'processed/photos/' . $photo;
                    }
                }
            }
        }
        
        return null;
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
