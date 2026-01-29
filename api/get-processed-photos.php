<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

class PhotoManager {
    private $processedDir;

    public function __construct() {
        $this->processedDir = '../processed/photos/';
    }

    public function getProcessedPhotos() {
        try {
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 50;
            $offset = ($page - 1) * $limit;

            // Get all photo files
            $allPhotos = $this->getAllPhotos();
            
            // Sort by creation time (newest first)
            usort($allPhotos, function($a, $b) {
                return $b['created'] - $a['created'];
            });

            $totalPhotos = count($allPhotos);
            $totalPages = ceil($totalPhotos / $limit);
            
            // Get photos for current page
            $photos = array_slice($allPhotos, $offset, $limit);

            echo json_encode([
                'success' => true,
                'photos' => $photos,
                'total' => $totalPhotos,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => $totalPages
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function getAllPhotos() {
        $photos = [];
        
        if (!is_dir($this->processedDir)) {
            return $photos;
        }

        // Supported image formats
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        $files = scandir($this->processedDir);
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $filePath = $this->processedDir . $file;
            
            if (!is_file($filePath)) {
                continue;
            }
            
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            
            if (!in_array($ext, $extensions)) {
                continue;
            }
            
            $fileSize = filesize($filePath);
            $created = filemtime($filePath);
            
            // Get image dimensions
            $dimensions = @getimagesize($filePath);
            $width = $dimensions ? $dimensions[0] : 0;
            $height = $dimensions ? $dimensions[1] : 0;
            
            $photos[] = [
                'filename' => $file,
                'path' => str_replace('../', '', $filePath),
                'size' => $fileSize,
                'sizeFormatted' => $this->formatBytes($fileSize),
                'created' => $created,
                'createdFormatted' => date('Y-m-d H:i:s', $created),
                'extension' => $ext,
                'width' => $width,
                'height' => $height,
                'dimensions' => $width && $height ? "{$width}x{$height}" : 'Unknown'
            ];
        }
        
        return $photos;
    }

    private function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

// Handle the request
$manager = new PhotoManager();
$manager->getProcessedPhotos();
