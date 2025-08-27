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

class AssetDeleter {
    private $templateDir;
    private $audioDir;

    public function __construct() {
        $this->templateDir = '../template/';
        $this->audioDir = '../audio/';
    }

    public function deleteAsset() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['type'])) {
                throw new Exception('Missing type parameter');
            }

            $type = $input['type'];

            if ($type === 'template') {
                $this->deleteTemplate();
            } elseif ($type === 'audio') {
                $this->deleteAudio();
            } else {
                throw new Exception('Invalid type. Must be "template" or "audio"');
            }

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function deleteTemplate() {
        $files = glob($this->templateDir . 'Preview Screen V4.*');
        
        if (empty($files)) {
            throw new Exception('No template file found to delete');
        }

        $deleted = false;
        foreach ($files as $file) {
            if (is_file($file)) {
                if (unlink($file)) {
                    $deleted = true;
                }
            }
        }

        if ($deleted) {
            echo json_encode([
                'success' => true,
                'message' => 'Template deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete template file');
        }
    }

    private function deleteAudio() {
        $files = glob($this->audioDir . 'background_audio.*');
        
        if (empty($files)) {
            throw new Exception('No audio file found to delete');
        }

        $deleted = false;
        foreach ($files as $file) {
            if (is_file($file)) {
                if (unlink($file)) {
                    $deleted = true;
                }
            }
        }

        if ($deleted) {
            echo json_encode([
                'success' => true,
                'message' => 'Audio deleted successfully'
            ]);
        } else {
            throw new Exception('Failed to delete audio file');
        }
    }
}

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deleter = new AssetDeleter();
    $deleter->deleteAsset();
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}
?>
