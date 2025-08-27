<?php
// Debug page to test FFmpeg functionality
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FFmpeg Debug Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .output { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h2>FFmpeg Debug Tool</h2>
                <p>This tool helps diagnose FFmpeg installation and video processing issues.</p>
                
                <?php
                class FFmpegDebugger {
                    private function detectFFmpegPath() {
                        $possiblePaths = [
                            'ffmpeg',
                            'ffmpeg.exe',
                            '/usr/bin/ffmpeg',
                            '/usr/local/bin/ffmpeg',
                            'C:\\ffmpeg\\bin\\ffmpeg.exe',
                            'D:\\ffmpeg\\bin\\ffmpeg.exe',
                            'C:\\Program Files\\ffmpeg\\bin\\ffmpeg.exe',
                            'C:\\Program Files (x86)\\ffmpeg\\bin\\ffmpeg.exe'
                        ];

                        foreach ($possiblePaths as $path) {
                            if ($this->commandExists($path)) {
                                return $path;
                            }
                        }
                        return null;
                    }

                    private function commandExists($command) {
                        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                            $return = shell_exec("where $command 2>nul");
                            return !empty($return);
                        } else {
                            $return = shell_exec("which $command");
                            return !empty($return);
                        }
                    }

                    public function runTests() {
                        echo "<div class='card mb-4'>";
                        echo "<div class='card-header'><h4>1. FFmpeg Detection</h4></div>";
                        echo "<div class='card-body'>";
                        
                        $ffmpegPath = $this->detectFFmpegPath();
                        if ($ffmpegPath) {
                            echo "<p class='success'>✓ FFmpeg found at: $ffmpegPath</p>";
                        } else {
                            echo "<p class='error'>✗ FFmpeg not found in common locations</p>";
                            echo "<p>Please install FFmpeg or add it to your system PATH</p>";
                            echo "</div></div>";
                            return;
                        }
                        
                        echo "</div></div>";

                        // Test FFmpeg version
                        echo "<div class='card mb-4'>";
                        echo "<div class='card-header'><h4>2. FFmpeg Version</h4></div>";
                        echo "<div class='card-body'>";
                        
                        $versionOutput = shell_exec("$ffmpegPath -version 2>&1");
                        if ($versionOutput) {
                            echo "<div class='output'>$versionOutput</div>";
                        } else {
                            echo "<p class='error'>✗ Could not get FFmpeg version</p>";
                        }
                        
                        echo "</div></div>";

                        // Test basic FFmpeg functionality
                        echo "<div class='card mb-4'>";
                        echo "<div class='card-header'><h4>3. Basic FFmpeg Test</h4></div>";
                        echo "<div class='card-body'>";
                        
                        $helpOutput = shell_exec("$ffmpegPath -h 2>&1");
                        if (strpos($helpOutput, 'usage:') !== false || strpos($helpOutput, 'Usage:') !== false) {
                            echo "<p class='success'>✓ FFmpeg is working correctly</p>";
                        } else {
                            echo "<p class='error'>✗ FFmpeg may not be working correctly</p>";
                            echo "<div class='output'>" . htmlspecialchars($helpOutput) . "</div>";
                        }
                        
                        echo "</div></div>";

                        // Test video duration detection with a sample
                        echo "<div class='card mb-4'>";
                        echo "<div class='card-header'><h4>4. Video Processing Test</h4></div>";
                        echo "<div class='card-body'>";
                        
                        // Check if there are any videos in uploads folder
                        $uploadsDir = '../uploads/';
                        if (is_dir($uploadsDir)) {
                            $videos = glob($uploadsDir . '*.{mp4,avi,mov,wmv,webm}', GLOB_BRACE);
                            if (!empty($videos)) {
                                $testVideo = $videos[0];
                                echo "<p>Testing with: " . basename($testVideo) . "</p>";
                                
                                // Test duration detection
                                $durationCmd = "$ffmpegPath -i \"$testVideo\" 2>&1";
                                $durationOutput = shell_exec($durationCmd);
                                
                                if (preg_match('/Duration: (\d{2}):(\d{2}):(\d{2})\.(\d{2})/', $durationOutput, $matches)) {
                                    $duration = $matches[1] * 3600 + $matches[2] * 60 + $matches[3] + $matches[4] / 100;
                                    echo "<p class='success'>✓ Duration detected: {$duration} seconds</p>";
                                } else {
                                    echo "<p class='error'>✗ Could not detect video duration</p>";
                                    echo "<div class='output'>" . htmlspecialchars($durationOutput) . "</div>";
                                }
                            } else {
                                echo "<p>No video files found in uploads folder to test with.</p>";
                                echo "<p>Upload a video file first, then run this test again.</p>";
                            }
                        } else {
                            echo "<p class='error'>✗ Uploads directory not found</p>";
                        }
                        
                        echo "</div></div>";

                        // System information
                        echo "<div class='card mb-4'>";
                        echo "<div class='card-header'><h4>5. System Information</h4></div>";
                        echo "<div class='card-body'>";
                        
                        echo "<p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>";
                        echo "<p><strong>Operating System:</strong> " . PHP_OS . "</p>";
                        echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
                        echo "<p><strong>Temp Directory:</strong> " . sys_get_temp_dir() . "</p>";
                        echo "<p><strong>Max Execution Time:</strong> " . ini_get('max_execution_time') . " seconds</p>";
                        echo "<p><strong>Memory Limit:</strong> " . ini_get('memory_limit') . "</p>";
                        echo "<p><strong>Upload Max Filesize:</strong> " . ini_get('upload_max_filesize') . "</p>";
                        
                        echo "</div></div>";
                    }
                }

                $debugger = new FFmpegDebugger();
                $debugger->runTests();
                ?>
                
                <div class="alert alert-info">
                    <h5>Troubleshooting Tips:</h5>
                    <ul>
                        <li>If FFmpeg is not found, download it from <a href="https://ffmpeg.org/download.html" target="_blank">ffmpeg.org</a></li>
                        <li>On Windows, extract FFmpeg to C:\ffmpeg\ and add C:\ffmpeg\bin to your PATH</li>
                        <li>Restart your web server after installing FFmpeg</li>
                        <li>Check that the web server user has permission to execute FFmpeg</li>
                        <li>For duration detection issues, try uploading a different video format</li>
                    </ul>
                </div>
                
                <a href="index.php" class="btn btn-primary">Back to Video Processor</a>
            </div>
        </div>
    </div>
</body>
</html>
