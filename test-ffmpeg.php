<?php
// Quick FFmpeg test for web server
echo "<h3>FFmpeg Test from Web Server</h3>";

// Test 1: Check if FFmpeg is in PATH
echo "<h4>1. PATH Test</h4>";
$output = shell_exec('ffmpeg -version 2>&1');
if ($output) {
    echo "<pre style='color: green;'>✓ FFmpeg accessible via PATH:\n" . htmlspecialchars($output) . "</pre>";
} else {
    echo "<pre style='color: red;'>✗ FFmpeg not accessible via PATH</pre>";
}

// Test 2: Try direct path
echo "<h4>2. Direct Path Test</h4>";
$directPath = 'C:\\ffmpeg\\ffmpeg-8.0-essentials_build\\bin\\ffmpeg.exe';
$output2 = shell_exec("\"$directPath\" -version 2>&1");
if ($output2) {
    echo "<pre style='color: green;'>✓ FFmpeg accessible via direct path:\n" . htmlspecialchars($output2) . "</pre>";
} else {
    echo "<pre style='color: red;'>✗ FFmpeg not accessible via direct path</pre>";
}

// Test 3: Check environment variables
echo "<h4>3. Environment Variables</h4>";
echo "<p>PATH: " . htmlspecialchars($_ENV['PATH'] ?? 'Not set') . "</p>";
echo "<p>Server Environment PATH: " . htmlspecialchars(getenv('PATH')) . "</p>";

// Test 4: Try where command
echo "<h4>4. Where Command Test</h4>";
$whereOutput = shell_exec('where ffmpeg 2>&1');
if ($whereOutput && !strpos($whereOutput, 'Could not find')) {
    echo "<pre style='color: green;'>✓ FFmpeg found at:\n" . htmlspecialchars($whereOutput) . "</pre>";
} else {
    echo "<pre style='color: red;'>✗ FFmpeg not found in PATH:\n" . htmlspecialchars($whereOutput) . "</pre>";
}
?>
