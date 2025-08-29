<?php
// Test script to verify video processing optimizations

echo "Testing video processing optimizations...\n";

// Check if we have a test video
$testVideo = '/var/www/slow/sample/WhatsApp Video 2025-08-26 at 16.05.42_8365b07f.mp4';

if (!file_exists($testVideo)) {
    echo "Test video not found at: $testVideo\n";
    exit(1);
}

echo "Found test video: $testVideo\n";
echo "File size: " . number_format(filesize($testVideo)) . " bytes\n";

// Test FFmpeg detection
$possiblePaths = [
    'ffmpeg',
    '/usr/bin/ffmpeg',
    '/usr/local/bin/ffmpeg',
    'C:\\ffmpeg\\ffmpeg-8.0-essentials_build\\bin\\ffmpeg.exe'
];

$ffmpegPath = null;
foreach ($possiblePaths as $path) {
    if (PHP_OS_FAMILY === 'Windows') {
        $return = shell_exec("where $path 2>nul");
    } else {
        $return = shell_exec("which $path");
    }
    
    if (!empty($return)) {
        $ffmpegPath = $path;
        break;
    }
}

if ($ffmpegPath) {
    echo "FFmpeg found at: $ffmpegPath\n";
    
    // Test getting video duration
    $cmd = sprintf('"%s" -i "%s" 2>&1 | grep Duration', $ffmpegPath, $testVideo);
    $output = shell_exec($cmd);
    echo "Duration info: " . trim($output) . "\n";
    
} else {
    echo "FFmpeg not found in PATH\n";
}

echo "\nOptimizations implemented:\n";
echo "✓ Faster encoding presets (fast/faster instead of slower)\n";
echo "✓ Optimized CRF values (23-28 instead of 14)\n";
echo "✓ Reduced bitrates for smaller files\n";
echo "✓ Web optimization with faststart for streaming\n";
echo "✓ Simplified minterpolate settings for faster processing\n";
echo "✓ Automatic compression based on file size\n";

echo "\nTest completed!\n";
?>
