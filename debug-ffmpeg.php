<?php
// Debug FFmpeg processing
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>FFmpeg Debug</title>
    <style>
        body {
            background: #C82026;
            color: white;
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
        }
        pre {
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="template/eco logo@3x.png" alt="Logo" style="height: 40px; margin-bottom: 10px;">
        </div>
<?php

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
    </div>
</body>
</html>
