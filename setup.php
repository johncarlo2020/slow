<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Processor - Installation Guide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-ok { color: green; }
        .status-error { color: red; }
        .status-warning { color: orange; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Video Processor - System Check</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        function checkStatus($condition, $message, $required = true) {
                            if ($condition) {
                                echo "<p class='status-ok'>✓ $message</p>";
                                return true;
                            } else {
                                $class = $required ? 'status-error' : 'status-warning';
                                $icon = $required ? '✗' : '⚠';
                                echo "<p class='$class'>$icon $message</p>";
                                return false;
                            }
                        }

                        echo "<h5>PHP Configuration</h5>";
                        checkStatus(version_compare(PHP_VERSION, '7.0.0') >= 0, "PHP Version: " . PHP_VERSION . " (Required: 7.0+)");
                        checkStatus(extension_loaded('fileinfo'), "FileInfo extension loaded");
                        checkStatus(ini_get('file_uploads'), "File uploads enabled");
                        
                        $max_upload = ini_get('upload_max_filesize');
                        $max_post = ini_get('post_max_size');
                        checkStatus(true, "Max upload size: $max_upload", false);
                        checkStatus(true, "Max post size: $max_post", false);
                        
                        echo "<h5>Directory Permissions</h5>";
                        checkStatus(is_writable('uploads'), "uploads/ directory writable");
                        checkStatus(is_writable('processed'), "processed/ directory writable");
                        
                        echo "<h5>FFmpeg Installation</h5>";
                        $ffmpeg_check = shell_exec('ffmpeg -version 2>&1');
                        checkStatus(!empty($ffmpeg_check) && strpos($ffmpeg_check, 'ffmpeg version') !== false, "FFmpeg installed and accessible");
                        
                        if (!empty($ffmpeg_check)) {
                            preg_match('/ffmpeg version ([^\s]+)/', $ffmpeg_check, $matches);
                            if (isset($matches[1])) {
                                echo "<p class='status-ok'>FFmpeg Version: " . $matches[1] . "</p>";
                            }
                        }
                        
                        echo "<h5>Server Information</h5>";
                        echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
                        echo "<p>PHP Memory Limit: " . ini_get('memory_limit') . "</p>";
                        echo "<p>Max Execution Time: " . ini_get('max_execution_time') . " seconds</p>";
                        ?>
                        
                        <div class="mt-4">
                            <h5>Next Steps</h5>
                            <ol>
                                <li>Ensure all required items above show ✓</li>
                                <li>Install FFmpeg if not detected</li>
                                <li>Adjust PHP settings if needed</li>
                                <li><a href="index.php" class="btn btn-primary">Start Using Video Processor</a></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
