<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include Pusher helper if available
$pusherHelper = null;
if (file_exists(__DIR__ . '/../classes/PusherHelper.php')) {
    try {
        require_once __DIR__ . '/../classes/PusherHelper.php';
        $pusherHelper = new PusherHelper();
    } catch (Exception $e) {
        error_log('Pusher initialization failed: ' . $e->getMessage());
    }
}

// Include PhotoProcessor
require_once __DIR__ . '/process-photo.php';

class VideoProcessor {
    private $uploadsDir;
    private $outputDir;
    private $ffmpegPath;

    public function __construct() {
        $this->uploadsDir = '../uploads/';
        $this->outputDir = '../processed/';
        // Try to detect FFmpeg path - you may need to adjust this
        $this->ffmpegPath = $this->detectFFmpegPath();
        
        // Create output directory if it doesn't exist
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    private function detectFFmpegPath() {
        $possiblePaths = [
            'ffmpeg', // If it's in PATH
            'ffmpeg.exe', // Windows with .exe
            'C:\\ffmpeg\\ffmpeg-8.0-essentials_build\\bin\\ffmpeg.exe', // Our installation
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            'C:\\ffmpeg\\bin\\ffmpeg.exe',
            'D:\\ffmpeg\\bin\\ffmpeg.exe',
            'C:\\Program Files\\ffmpeg\\bin\\ffmpeg.exe',
            'C:\\Program Files (x86)\\ffmpeg\\bin\\ffmpeg.exe'
        ];

        foreach ($possiblePaths as $path) {
            if ($this->commandExists($path)) {
                error_log("FFmpeg found at: $path");
                return $path;
            }
        }

        error_log("FFmpeg not found in any of the expected locations");
        return 'ffmpeg'; // Default fallback
    }

    private function commandExists($command) {
        // For Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $return = shell_exec("where $command 2>nul");
            return !empty($return);
        } else {
            // For Unix/Linux
            $return = shell_exec("which $command");
            return !empty($return);
        }
    }

    public function processVideo() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }

            $videoUrl = $_POST['videoUrl'] ?? '';
            $photoUrl = $_POST['photoUrl'] ?? '';
            $addBackgroundMusic = isset($_POST['addBackgroundMusic']) ? $_POST['addBackgroundMusic'] === 'true' : true;

            if (empty($videoUrl)) {
                throw new Exception('No video URL provided');
            }

            // Process photo if provided
            $processedPhotoUrl = null;
            if (!empty($photoUrl)) {
                // error_log('Processing photo: ' . $photoUrl);
                $photoProcessor = new PhotoProcessor();
                $photoResult = $photoProcessor->processPhoto($photoUrl);
                
                if ($photoResult['success']) {
                    $processedPhotoUrl = $photoResult['processedPhoto'];
                    // error_log('Photo processed successfully: ' . $processedPhotoUrl);
                } else {
                    // error_log('Photo processing failed: ' . $photoResult['message']);
                }
            }

            // Extract filename from URL
            $videoPath = '../' . $videoUrl;
            if (!file_exists($videoPath)) {
                throw new Exception('Video file not found');
            }

            // Generate output filename
            $inputFilename = basename($videoPath);
            $outputFilename = 'processed_' . pathinfo($inputFilename, PATHINFO_FILENAME) . '_' . time() . '.mp4';
            $outputPath = $this->outputDir . $outputFilename;

            // Process the video with background music only
            $this->addBackgroundMusicOnly($videoPath, $outputPath, $addBackgroundMusic);

            $responseData = [
                'success' => true,
                'message' => 'Background music added successfully',
                'originalVideo' => $videoUrl,
                'processedVideo' => 'processed/' . $outputFilename,
                'processedPhoto' => $processedPhotoUrl,
                'settings' => [
                    'mode' => 'Background Music Only',
                    'backgroundMusic' => $addBackgroundMusic ? 'Added' : 'Skipped',
                    'photoProcessed' => $processedPhotoUrl ? 'Yes' : 'No'
                ]
            ];

            // Trigger Pusher event for real-time gallery update
            global $pusherHelper;
            if ($pusherHelper) {
                $pusherData = [
                    'video' => [
                        'filename' => $outputFilename,
                        'path' => 'processed/' . $outputFilename,
                        'size' => file_exists($outputPath) ? filesize($outputPath) : 0,
                        'created' => time()
                    ]
                ];
                
                // Add photo data if photo was processed
                if ($processedPhotoUrl) {
                    $photoPath = '../' . $processedPhotoUrl;
                    $pusherData['photo'] = [
                        'filename' => basename($processedPhotoUrl),
                        'path' => $processedPhotoUrl,
                        'size' => file_exists($photoPath) ? filesize($photoPath) : 0,
                        'created' => time()
                    ];
                }
                
                $pusherHelper->triggerVideoProcessed($pusherData);
            }

            echo json_encode($responseData);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function addBackgroundMusicOnly($inputPath, $outputPath, $addBackgroundMusic = true) {
        error_log("Adding background music only (no template, no slow motion)");
        
        if (!$addBackgroundMusic) {
            // If no background music requested, just copy the original video
            copy($inputPath, $outputPath);
            error_log("No background music requested, copied original video");
            return;
        }
        
        // Get background audio file
        $audioDir = dirname(__DIR__) . '/audio/';
        $backgroundAudio = null;
        
        // Find audio files in the audio folder
        $audioExtensions = ['mp3', 'wav', 'aac', 'm4a', 'flac'];
        foreach ($audioExtensions as $ext) {
            $audioFiles = glob($audioDir . '*.' . $ext);
            if (!empty($audioFiles)) {
                $backgroundAudio = $audioFiles[0]; // Use the first audio file found
                break;
            }
        }
        
        if (!$backgroundAudio) {
            error_log("No background audio found in audio folder, copying original video");
            copy($inputPath, $outputPath);
            return;
        }
        
        error_log("Using background audio: " . $backgroundAudio);
        
        // Get video duration
        $videoDuration = $this->getVideoDuration($inputPath);
        
        // Get audio duration
        $audioInfo = $this->getVideoInfo($backgroundAudio);
        $audioDuration = isset($audioInfo['duration']) ? $audioInfo['duration'] : 10; // Default to 10 seconds if can't determine
        
        // Calculate how many times to loop the audio to match video duration
        $audioLoops = max(1, ceil($videoDuration / $audioDuration));
        
        error_log("Video duration: {$videoDuration}s, Audio duration: {$audioDuration}s, Audio loops: {$audioLoops}");
        
        // Build FFmpeg command to replace original audio with background music
        $cmd = sprintf(
            '"%s" -i "%s" -stream_loop %d -i "%s" -c:v copy -map 0:v -map 1:a -t %.2f -c:a aac -b:a 128k -ar 44100 -af "volume=0.7" -shortest -y "%s" 2>&1',
            $this->ffmpegPath,
            $inputPath,
            $audioLoops - 1, // -stream_loop uses 0-based counting
            $backgroundAudio,
            $videoDuration,
            $outputPath
        );
        
        error_log("Background music FFmpeg command: " . $cmd);
        
        $output = shell_exec($cmd);
        error_log("Background music FFmpeg output: " . substr($output, 0, 1000));
        
        // Check for errors in output
        if (strpos($output, 'Error') !== false || strpos($output, 'Invalid') !== false) {
            error_log("FFmpeg error detected, trying fallback method");
            $this->addBackgroundMusicFallback($inputPath, $outputPath, $backgroundAudio, $videoDuration);
            return;
        }
        
        if (!file_exists($outputPath) || filesize($outputPath) < 1000) {
            error_log("Primary method failed, trying fallback method");
            $this->addBackgroundMusicFallback($inputPath, $outputPath, $backgroundAudio, $videoDuration);
            return;
        }
        
        error_log("Successfully added background music: " . $outputPath);
    }
    
    private function addBackgroundMusicFallback($inputPath, $outputPath, $backgroundAudio, $videoDuration) {
        error_log("Using fallback method for background music");
        
        // Simpler approach - mix original audio with background music
        $cmd = sprintf(
            '"%s" -i "%s" -i "%s" -filter_complex "[0:a][1:a]amix=inputs=2:duration=first:dropout_transition=2[audio]" -map 0:v -map "[audio]" -c:v copy -c:a aac -b:a 128k -ar 44100 -t %.2f -y "%s" 2>&1',
            $this->ffmpegPath,
            $inputPath,
            $backgroundAudio,
            $videoDuration,
            $outputPath
        );
        
        error_log("Fallback FFmpeg command: " . $cmd);
        
        $output = shell_exec($cmd);
        error_log("Fallback FFmpeg output: " . substr($output, 0, 1000));
        
        if (!file_exists($outputPath) || filesize($outputPath) < 1000) {
            // Last resort - just copy the original video
            error_log("All methods failed, copying original video");
            copy($inputPath, $outputPath);
        } else {
            error_log("Fallback method succeeded");
        }
    }

    private function getVideoInfo($filePath) {
        error_log("Getting video info for: $filePath");
        
        $cmd = sprintf('"%s" -i "%s" 2>&1', $this->ffmpegPath, $filePath);
        $output = shell_exec($cmd);
        
        $info = [];
        
        // Extract dimensions
        if (preg_match('/(\d{2,4})x(\d{2,4})/', $output, $matches)) {
            $info['width'] = intval($matches[1]);
            $info['height'] = intval($matches[2]);
            error_log("Video dimensions: {$info['width']}x{$info['height']}");
        }
        
        // Extract duration
        if (preg_match('/Duration: (\d{2}):(\d{2}):(\d{2})\.(\d{2})/', $output, $matches)) {
            $hours = intval($matches[1]);
            $minutes = intval($matches[2]);
            $seconds = intval($matches[3]);
            $centiseconds = intval($matches[4]);
            $info['duration'] = $hours * 3600 + $minutes * 60 + $seconds + $centiseconds / 100;
            error_log("Video duration: {$info['duration']} seconds");
        } else {
            // Fallback - try to get duration using the same method as getVideoDuration
            try {
                $info['duration'] = $this->getVideoDuration($filePath);
                error_log("Video duration (fallback): {$info['duration']} seconds");
            } catch (Exception $e) {
                error_log("Could not determine duration: " . $e->getMessage());
                $info['duration'] = 10; // Default fallback
            }
        }
        
        return $info;
    }

    private function getVideoDuration($videoPath) {
        error_log("Attempting to get duration for: $videoPath");
        error_log("Using FFmpeg path: " . $this->ffmpegPath);
        
        // Method 1: Use ffprobe if available (more reliable)
        $ffprobePath = str_replace('ffmpeg', 'ffprobe', $this->ffmpegPath);
        $cmd = sprintf(
            '"%s" -v quiet -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 "%s" 2>&1',
            $ffprobePath,
            $videoPath
        );
        
        error_log("Trying ffprobe command: $cmd");
        $output = trim(shell_exec($cmd));
        error_log("FFprobe output: $output");
        
        if (is_numeric($output) && $output > 0) {
            error_log("Duration found via ffprobe: $output seconds");
            return floatval($output);
        }
        
        // Method 2: Use ffmpeg with different output parsing
        $cmd = sprintf(
            '"%s" -i "%s" 2>&1',
            $this->ffmpegPath,
            $videoPath
        );
        
        error_log("Trying ffmpeg command: $cmd");
        $output = shell_exec($cmd);
        error_log("FFmpeg raw output: " . substr($output, 0, 1000) . "...");
        
        // Try different duration formats
        $patterns = [
            '/Duration: (\d{2}):(\d{2}):(\d{2})\.(\d{2})/',
            '/Duration: (\d{2}):(\d{2}):(\d{2})\.(\d{1})/',
            '/Duration: (\d{1,2}):(\d{2}):(\d{2})\.(\d{2})/',
            '/Duration: (\d{1,2}):(\d{2}):(\d{2})/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $output, $matches)) {
                $hours = intval($matches[1]);
                $minutes = intval($matches[2]);
                $seconds = intval($matches[3]);
                $fraction = isset($matches[4]) ? intval($matches[4]) / pow(10, strlen($matches[4])) : 0;
                
                $duration = $hours * 3600 + $minutes * 60 + $seconds + $fraction;
                error_log("Duration found via ffmpeg pattern: $duration seconds");
                return $duration;
            }
        }
        
        // Method 3: Use a simpler ffmpeg command
        $cmd = sprintf(
            '"%s" -i "%s" -f null - 2>&1',
            $this->ffmpegPath,
            $videoPath
        );
        
        error_log("Trying simple ffmpeg command: $cmd");
        $output = shell_exec($cmd);
        error_log("Simple FFmpeg output: " . substr($output, 0, 500) . "...");
        
        if (preg_match('/time=(\d{2}):(\d{2}):(\d{2})\.(\d{2})/', $output, $matches)) {
            $hours = intval($matches[1]);
            $minutes = intval($matches[2]);
            $seconds = intval($matches[3]);
            $centiseconds = intval($matches[4]);
            
            $duration = $hours * 3600 + $minutes * 60 + $seconds + $centiseconds / 100;
            error_log("Duration found via simple ffmpeg: $duration seconds");
            return $duration;
        }
        
        error_log("Failed to get duration for: $videoPath");
        error_log("FFmpeg path used: " . $this->ffmpegPath);
        error_log("File exists: " . (file_exists($videoPath) ? 'yes' : 'no'));
        error_log("File size: " . (file_exists($videoPath) ? filesize($videoPath) : 'N/A') . " bytes");
        
        throw new Exception('Could not determine video duration. Please ensure the video file is valid and FFmpeg is properly installed.');
    }

    private function executeCommand($command) {
        $output = shell_exec($command);
        
        // Log the command and output for debugging
        error_log("Command executed: $command");
        error_log("Command output: $output");
        
        // Check for common error indicators
        $errorIndicators = [
            'Error',
            'error',
            'Invalid',
            'invalid',
            'No such file',
            'Permission denied',
            'Cannot',
            'cannot',
            'Failed',
            'failed'
        ];
        
        foreach ($errorIndicators as $indicator) {
            if (strpos($output, $indicator) !== false) {
                error_log("FFmpeg command failed: $command");
                error_log("Error output: $output");
                throw new Exception("Video processing failed: $output");
            }
        }
        
        return $output;
    }
}

// Handle the processing request
$processor = new VideoProcessor();
$processor->processVideo();