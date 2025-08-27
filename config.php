<?php
// Configuration file for video processing system

// Database configuration (if you want to add user management or video history)
define('DB_HOST', 'localhost');
define('DB_NAME', 'video_processor');
define('DB_USER', 'root');
define('DB_PASS', '');

// File upload settings
define('MAX_FILE_SIZE', 100 * 1024 * 1024); // 100MB
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('PROCESSED_DIR', __DIR__ . '/processed/');

// Allowed video formats
define('ALLOWED_VIDEO_TYPES', [
    'video/mp4',
    'video/avi',
    'video/mov',
    'video/wmv',
    'video/webm',
    'video/quicktime'
]);

// FFmpeg settings
define('FFMPEG_PATH', 'ffmpeg'); // Adjust if FFmpeg is not in PATH
define('MAX_PROCESSING_TIME', 300); // 5 minutes

// Security settings
define('ENABLE_RATE_LIMITING', true);
define('MAX_UPLOADS_PER_HOUR', 10);

// Cleanup settings
define('AUTO_CLEANUP_ENABLED', true);
define('CLEANUP_AFTER_HOURS', 24); // Delete files after 24 hours

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// Set timezone
date_default_timezone_set('UTC');
?>
