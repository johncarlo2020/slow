<?php
// Debug FFmpeg processing
error_reporting(E_ALL);
ini_set('display_errors', 1);

$inputFile = '/var/www/slow/uploads/video_68ae8d07c48e3_1756269831.mp4';

echo "<h3>FFmpeg Debug Test</h3>";

// Test 1: Check if input file exists
echo "<h4>1. Input File Check</h4>";
if (file_exists($inputFile)) {
    echo "<p style='color: green;'>✓ Input file exists: " . htmlspecialchars($inputFile) . "</p>";
    echo "<p>File size: " . number_format(filesize($inputFile)) . " bytes</p>";
} else {
    echo "<p style='color: red;'>✗ Input file not found: " . htmlspecialchars($inputFile) . "</p>";
    exit;
}

// Test 2: Get video info
echo "<h4>2. Video Information</h4>";
$infoCmd = 'ffmpeg -i "' . $inputFile . '" 2>&1';
$info = shell_exec($infoCmd);
echo "<pre>" . htmlspecialchars($info) . "</pre>";

// Test 3: Simple conversion test
echo "<h4>3. Simple Conversion Test</h4>";
$outputFile = '/var/www/slow/processed/test_output.mp4';
$testCmd = 'ffmpeg -i "' . $inputFile . '" -t 5 -c:v libx264 -c:a aac "' . $outputFile . '" 2>&1';
echo "<p>Command: " . htmlspecialchars($testCmd) . "</p>";
$output = shell_exec($testCmd);
echo "<pre>" . htmlspecialchars($output) . "</pre>";

if (file_exists($outputFile)) {
    echo "<p style='color: green;'>✓ Simple conversion successful</p>";
} else {
    echo "<p style='color: red;'>✗ Simple conversion failed</p>";
}

// Test 4: Check permissions
echo "<h4>4. Permission Check</h4>";
echo "<p>Processed directory permissions: " . substr(sprintf('%o', fileperms('/var/www/slow/processed')), -4) . "</p>";
echo "<p>Web server user: " . exec('whoami') . "</p>";
?>
