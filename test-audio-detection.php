<?php
// Test script to check audio detection
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Audio Detection Test</h2>";

// Simulate the same logic as in the VideoProcessor class
function findBackgroundAudio() {
    $audioDir = '../audio/';
    $baseFilename = 'background_audio';
    $extensions = ['mp3', 'wav', 'aac', 'ogg', 'm4a'];
    
    echo "<p>Searching in directory: $audioDir</p>";
    
    foreach ($extensions as $ext) {
        $path = $audioDir . $baseFilename . '.' . $ext;
        echo "<p>Checking: $path - ";
        if (file_exists($path)) {
            echo "<strong style='color: green;'>FOUND!</strong></p>";
            return $path;
        } else {
            echo "<span style='color: red;'>not found</span></p>";
        }
    }
    
    // Check fallback
    $fallbackPath = '../audio/PEDRO PEDRO PEDRO   #shorts.mp3';
    echo "<p>Checking fallback: $fallbackPath - ";
    if (file_exists($fallbackPath)) {
        echo "<strong style='color: orange;'>FOUND (fallback)</strong></p>";
        return $fallbackPath;
    } else {
        echo "<span style='color: red;'>not found</span></p>";
    }
    
    return null;
}

$audioFile = findBackgroundAudio();

echo "<h3>Result:</h3>";
if ($audioFile) {
    echo "<p style='color: green;'><strong>Audio file selected: $audioFile</strong></p>";
    
    // Show file info
    if (file_exists($audioFile)) {
        $size = filesize($audioFile);
        $modified = date('Y-m-d H:i:s', filemtime($audioFile));
        echo "<p>File size: " . number_format($size) . " bytes</p>";
        echo "<p>Last modified: $modified</p>";
    }
} else {
    echo "<p style='color: red;'><strong>No audio file found!</strong></p>";
}

echo "<h3>Audio Directory Contents:</h3>";
$audioDir = '../audio/';
if (is_dir($audioDir)) {
    $files = scandir($audioDir);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $fullPath = $audioDir . $file;
            $size = is_file($fullPath) ? filesize($fullPath) : 0;
            echo "<li>$file (" . number_format($size) . " bytes)</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>Audio directory not found!</p>";
}
?>
