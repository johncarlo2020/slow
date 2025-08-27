<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

class AssetManager {
    private $templateDir;
    private $audioDir;

    public function __construct() {
        $this->templateDir = '../template/';
        $this->audioDir = '../audio/';
    }

    public function getCurrentAssets() {
        $template = $this->getFileInfo($this->templateDir, 'Preview Screen V4');
        $audio = $this->getFileInfo($this->audioDir, 'background_audio');

        echo json_encode([
            'success' => true,
            'template' => $template,
            'audio' => $audio
        ]);
    }

    private function getFileInfo($directory, $baseFilename) {
        // Look for files with the base filename and any extension
        $files = glob($directory . $baseFilename . '.*');
        
        if (empty($files)) {
            return [
                'exists' => false,
                'filename' => null,
                'path' => null,
                'size' => 0,
                'modified' => null
            ];
        }

        // Get the first (and should be only) matching file
        $file = $files[0];
        $filename = basename($file);
        $relativePath = str_replace('../', '', $file);

        return [
            'exists' => true,
            'filename' => $filename,
            'path' => $relativePath,
            'size' => filesize($file),
            'modified' => filemtime($file)
        ];
    }
}

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $manager = new AssetManager();
    $manager->getCurrentAssets();
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?>
