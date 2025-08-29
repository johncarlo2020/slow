<?php
// Test the template detection and FFmpeg command generation
$templateDir = __DIR__ . '/template/';
echo "Template directory: $templateDir\n";
echo "Template directory real path: " . realpath($templateDir) . "\n";
echo "Template directory exists: " . (is_dir($templateDir) ? 'YES' : 'NO') . "\n\n";

$possibleFiles = [
    $templateDir . 'Preview Screen V4.webp',
    $templateDir . 'Preview Screen V4.png',
    $templateDir . 'Preview Screen V4.jpg',
    $templateDir . 'Preview Screen V4.jpeg'
];

$overlayPath = null;
foreach ($possibleFiles as $file) {
    echo "Checking: $file\n";
    if (file_exists($file)) {
        $overlayPath = $file;
        echo "FOUND: $overlayPath\n";
        echo "Size: " . filesize($file) . " bytes\n";
        break;
    } else {
        echo "NOT FOUND\n";
    }
}

if ($overlayPath) {
    echo "\n=== TEMPLATE FOUND ===\n";
    echo "Path: $overlayPath\n";
    echo "Real path: " . realpath($overlayPath) . "\n";
    
    // Test basic FFmpeg command to get template info
    $ffmpegPath = 'ffmpeg';
    $cmd = sprintf('"%s" -i "%s" 2>&1', $ffmpegPath, $overlayPath);
    echo "\nTemplate info command: $cmd\n";
    $output = shell_exec($cmd);
    
    // Extract dimensions from output
    if (preg_match('/Stream.*Video.*?(\d+)x(\d+)/', $output, $matches)) {
        echo "Template dimensions: {$matches[1]}x{$matches[2]}\n";
    } else {
        echo "Could not detect template dimensions\n";
        echo "FFmpeg output: " . substr($output, 0, 500) . "\n";
    }
} else {
    echo "\n=== NO TEMPLATE FOUND ===\n";
}

// Test with a sample video if available
$uploadsDir = dirname(__DIR__) . '/uploads/';
$videos = glob($uploadsDir . '*.{mp4,MP4}', GLOB_BRACE);
if (!empty($videos)) {
    $testVideo = $videos[0];
    echo "\n=== TESTING WITH VIDEO ===\n";
    echo "Test video: $testVideo\n";
    
    // Get video info
    $cmd = sprintf('"%s" -i "%s" 2>&1', $ffmpegPath, $testVideo);
    $output = shell_exec($cmd);
    
    if (preg_match('/Stream.*Video.*?(\d+)x(\d+)/', $output, $matches)) {
        $videoWidth = $matches[1];
        $videoHeight = $matches[2];
        echo "Video dimensions: {$videoWidth}x{$videoHeight}\n";
        
        if ($overlayPath) {
            // Generate the overlay filter
            $filterComplex = sprintf(
                '[1:v]scale=%d:%d:force_original_aspect_ratio=increase,crop=%d:%d[overlay_scaled];[0:v][overlay_scaled]overlay=0:0[video_with_overlay]',
                $videoWidth, $videoHeight, $videoWidth, $videoHeight
            );
            
            echo "\nFilter complex: $filterComplex\n";
            
            // Test just the filter parsing (dry run)
            $testCmd = sprintf('"%s" -f lavfi -i testsrc=size=%dx%d:duration=1 -i "%s" -filter_complex "%s" -map "[video_with_overlay]" -t 1 -f null - 2>&1',
                $ffmpegPath, $videoWidth, $videoHeight, $overlayPath, $filterComplex);
            
            echo "\nTest command: $testCmd\n";
            $testOutput = shell_exec($testCmd);
            echo "Test output: " . substr($testOutput, 0, 800) . "\n";
        }
    }
}
?>
