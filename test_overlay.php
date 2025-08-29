<?php
// Test just the overlay functionality
require_once 'process.php';

// Create a test video processor
$processor = new VideoProcessor();

// Test if we can detect a video info
$testVideo = '../uploads/20250829-125506-1730558400.mp4';
if (file_exists($testVideo)) {
    echo "Test video exists: $testVideo\n";
    
    // Test getVideoInfo
    try {
        $info = $processor->getVideoInfo($testVideo);
        echo "Video info: " . print_r($info, true) . "\n";
    } catch (Exception $e) {
        echo "Error getting video info: " . $e->getMessage() . "\n";
    }
} else {
    echo "Test video not found: $testVideo\n";
    
    // List available videos
    $uploadsDir = '../uploads/';
    if (is_dir($uploadsDir)) {
        $files = glob($uploadsDir . '*.mp4');
        echo "Available videos:\n";
        foreach ($files as $file) {
            echo "  - " . basename($file) . "\n";
        }
    }
}

// Test template detection from the processor class
$templateDir = '../template/';
$possibleFiles = [
    $templateDir . 'Preview Screen V4.webp',
    $templateDir . 'Preview Screen V4.png',
    $templateDir . 'Preview Screen V4.jpg',
    $templateDir . 'Preview Screen V4.jpeg'
];

$overlayPath = null;
foreach ($possibleFiles as $file) {
    if (file_exists($file)) {
        $overlayPath = $file;
        echo "Found template: $overlayPath\n";
        break;
    }
}

if (!$overlayPath) {
    echo "No template found!\n";
}
?>
