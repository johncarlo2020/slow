<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

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
            // Fixed parameters for automatic processing
            $startTime = 4.0;  // Always process 4-7 seconds
            $endTime = 7.0;
            $slowFactor = 0.25; // Always 4x slower
            $qualityMode = 'ultra'; // Always best quality
            $addOverlay = isset($_POST['addOverlay']) && $_POST['addOverlay'] === 'true';

            if (empty($videoUrl)) {
                throw new Exception('No video URL provided');
            }

            // Extract filename from URL
            $videoPath = '../' . $videoUrl;
            if (!file_exists($videoPath)) {
                throw new Exception('Video file not found');
            }

            // Check if video is long enough
            $duration = $this->getVideoDuration($videoPath);
            if ($duration < 7) {
                throw new Exception('Video must be at least 7 seconds long for automatic processing');
            }

            // Generate output filename
            $inputFilename = basename($videoPath);
            $outputFilename = 'slowmo_pro_' . pathinfo($inputFilename, PATHINFO_FILENAME) . '_' . time() . '.mp4';
            $outputPath = $this->outputDir . $outputFilename;

            // Process the video with overlay
            $this->createSlowMotionVideoWithOverlay($videoPath, $outputPath, $startTime, $endTime, $slowFactor, $addOverlay);

            echo json_encode([
                'success' => true,
                'message' => 'Professional slow motion video created successfully',
                'originalVideo' => $videoUrl,
                'processedVideo' => 'processed/' . $outputFilename,
                'settings' => [
                    'timeRange' => '4.0 - 7.0 seconds',
                    'slowFactor' => '4x slower',
                    'quality' => 'Ultra smooth with overlay'
                ]
            ]);

        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    private function createSlowMotionVideoWithOverlay($inputPath, $outputPath, $startTime, $endTime, $slowFactor, $addOverlay = true) {
        // Get video duration
        $duration = $this->getVideoDuration($inputPath);
        
        if ($endTime > $duration) {
            throw new Exception('End time exceeds video duration');
        }

        error_log("Creating professional slow motion with overlay: start=$startTime, end=$endTime, factor=$slowFactor, duration=$duration");

        // Path to overlay template
        $overlayPath = '../template/Preview Screen V4.png';
        if (!file_exists($overlayPath)) {
            error_log("Overlay template not found, proceeding without overlay");
            $addOverlay = false;
        }

        // Ultra-smooth slow motion with professional overlay
        try {
            $this->createUltraSmoothSlowMotionWithOverlay($inputPath, $outputPath, $startTime, $endTime, $slowFactor, $overlayPath, $addOverlay);
            return;
        } catch (Exception $e) {
            error_log("Ultra-smooth with overlay failed, trying standard: " . $e->getMessage());
        }

        // Fallback to standard method with overlay
        try {
            $this->createStandardSmoothSlowMotionWithOverlay($inputPath, $outputPath, $startTime, $endTime, $slowFactor, $overlayPath, $addOverlay);
            return;
        } catch (Exception $e) {
            error_log("Standard with overlay failed, trying without overlay: " . $e->getMessage());
        }

        // Final fallback without overlay
        $this->createStandardSmoothSlowMotion($inputPath, $outputPath, $startTime, $endTime, $slowFactor);
    }

    private function createAudioSlowFilter($slowFactor) {
        // FFmpeg atempo filter has a range of 0.5 to 100
        // For factors below 0.5, we need to chain multiple atempo filters
        
        if ($slowFactor >= 0.5) {
            // Single atempo filter is sufficient
            return sprintf("atempo=%.3f", $slowFactor);
        } else {
            // Chain multiple atempo filters for very slow motion
            // For 0.25, we use atempo=0.5,atempo=0.5 (0.5 * 0.5 = 0.25)
            if ($slowFactor >= 0.25) {
                return "atempo=0.5,atempo=" . sprintf("%.3f", $slowFactor / 0.5);
            } else if ($slowFactor >= 0.125) {
                // For 0.125, we use three atempo filters: 0.5 * 0.5 * 0.5 = 0.125
                return "atempo=0.5,atempo=0.5,atempo=" . sprintf("%.3f", $slowFactor / 0.25);
            } else {
                // For extremely slow motion, chain more filters
                return "atempo=0.5,atempo=0.5,atempo=0.5,atempo=" . sprintf("%.3f", $slowFactor / 0.125);
            }
        }
    }

    private function createUltraSmoothSlowMotionWithOverlay($inputPath, $outputPath, $startTime, $endTime, $slowFactor, $overlayPath, $addOverlay) {
        error_log("Creating ultra-smooth slow motion with professional overlay");
        
        // Get background audio file
        $audioDir = dirname(__DIR__) . '/audio/';
        $audioFiles = glob($audioDir . '*');
        $backgroundAudio = null;
        
        // Find the first audio file in the audio folder
        foreach ($audioFiles as $file) {
            if (is_file($file)) {
                $backgroundAudio = $file;
                break;
            }
        }
        
        // Get video frame rate
        $frameRateCmd = sprintf('"%s" -i "%s" 2>&1', $this->ffmpegPath, $inputPath);
        $frameRateOutput = shell_exec($frameRateCmd);
        preg_match('/(\d+(?:\.\d+)?)\s*fps/', $frameRateOutput, $matches);
        $originalFps = isset($matches[1]) ? floatval($matches[1]) : 30;
        
        // For ultra-smooth slow motion, significantly increase the target FPS
        $targetFps = min($originalFps * 4, 120); // Increase multiplier for smoother motion
        
        // Get video dimensions
        preg_match('/Stream.*Video.*?(\d{3,})x(\d{3,})/', $frameRateOutput, $dimMatches);
        $videoWidth = isset($dimMatches[1]) ? intval($dimMatches[1]) : 1920;
        $videoHeight = isset($dimMatches[2]) ? intval($dimMatches[2]) : 1080;
        
        // For very slow motion (factor < 0.5), we need to chain atempo filters
        $audioFilter = $this->createAudioSlowFilter($slowFactor);
        
        // Calculate final video duration
        $originalDuration = $this->getVideoDuration($inputPath);
        $slowMotionDuration = ($endTime - $startTime) / $slowFactor;
        $finalDuration = $originalDuration - ($endTime - $startTime) + $slowMotionDuration;
        
        // Complex filter with overlay scaled to fit video and background audio replacement
        if ($addOverlay && $backgroundAudio) {
            // Calculate how many times we need to repeat the audio
            $audioLoops = ceil($finalDuration / 9.01); // 9.01 is the duration of our audio file
            
            $filterComplex = sprintf(
                "[0:v]split=3[v1][v2][v3]; " .
                "[v1]trim=start=0:end=%.2f,setpts=PTS-STARTPTS[v1out]; " .
                "[v2]trim=start=%.2f:end=%.2f,minterpolate=fps=%.1f:mi_mode=mci:mc_mode=aobmc:me_mode=bidir:vsbmc=1:scd=none,setpts=%.2f*(PTS-STARTPTS)[v2out]; " .
                "[v3]trim=start=%.2f,setpts=PTS-STARTPTS[v3out]; " .
                "[v1out][v2out][v3out]concat=n=3:v=1:a=0[video]; " .
                "[1:v]scale=%d:%d[overlay_scaled]; " .
                "[video][overlay_scaled]overlay=0:0[vout]",
                $startTime, $startTime, $endTime, $targetFps, 1 / $slowFactor, $endTime,
                $videoWidth, $videoHeight
            );

            // Use -stream_loop for reliable audio looping with enhanced video settings
            $cmd = sprintf(
                '"%s" -stream_loop %d -i "%s" -i "%s" -i "%s" -filter_complex "%s" -map "[vout]" -map 2:a -t %.2f -c:v libx264 -preset slower -crf 14 -pix_fmt yuv420p -profile:v high -level 4.1 -bf 3 -g 60 -keyint_min 60 -sc_threshold 0 -r %.1f -c:a aac -b:a 256k -af "volume=0.8" -avoid_negative_ts make_zero -y "%s" 2>&1',
                $this->ffmpegPath, max(1, $audioLoops), $inputPath, $overlayPath, $backgroundAudio, $filterComplex, $finalDuration, $targetFps, $outputPath
            );
        } else if ($addOverlay && !$backgroundAudio) {
            // Without background audio - just scale overlay to fit video with enhanced slow motion
            $filterComplex = sprintf(
                "[0:v]split=3[v1][v2][v3]; " .
                "[v1]trim=start=0:end=%.2f,setpts=PTS-STARTPTS[v1out]; " .
                "[v2]trim=start=%.2f:end=%.2f,minterpolate=fps=%.1f:mi_mode=mci:mc_mode=aobmc:me_mode=bidir:vsbmc=1:scd=none,setpts=%.2f*(PTS-STARTPTS)[v2out]; " .
                "[v3]trim=start=%.2f,setpts=PTS-STARTPTS[v3out]; " .
                "[v1out][v2out][v3out]concat=n=3:v=1:a=0[video]; " .
                "[1:v]scale=%d:%d[overlay_scaled]; " .
                "[video][overlay_scaled]overlay=0:0[vout]; " .
                "[0:a]asplit=3[a1][a2][a3]; " .
                "[a1]atrim=start=0:end=%.2f,asetpts=PTS-STARTPTS[a1out]; " .
                "[a2]atrim=start=%.2f:end=%.2f,%s,asetpts=PTS-STARTPTS[a2out]; " .
                "[a3]atrim=start=%.2f,asetpts=PTS-STARTPTS[a3out]; " .
                "[a1out][a2out][a3out]concat=n=3:v=0:a=1[aout]",
                $startTime, $startTime, $endTime, $targetFps, 1 / $slowFactor, $endTime,
                $videoWidth, $videoHeight,
                $startTime, $startTime, $endTime, $audioFilter, $endTime
            );

            $cmd = sprintf(
                '"%s" -i "%s" -i "%s" -filter_complex "%s" -map "[vout]" -map "[aout]" -c:v libx264 -preset slower -crf 14 -pix_fmt yuv420p -profile:v high -level 4.1 -bf 3 -g 60 -keyint_min 60 -sc_threshold 0 -r %.1f -c:a aac -b:a 256k -avoid_negative_ts make_zero -y "%s" 2>&1',
                $this->ffmpegPath, $inputPath, $overlayPath, $filterComplex, $targetFps, $outputPath
            );
        } else if ($backgroundAudio) {
            // No overlay but use background audio - mute original audio and use background audio
            $filterComplex = sprintf(
                "[0:v]split=3[v1][v2][v3]; " .
                "[v1]trim=start=0:end=%.2f,setpts=PTS-STARTPTS[v1out]; " .
                "[v2]trim=start=%.2f:end=%.2f,minterpolate=fps=%.1f:mi_mode=mci:mc_mode=aobmc:me_mode=bidir:vsbmc=1:scd=none,setpts=%.2f*(PTS-STARTPTS)[v2out]; " .
                "[v3]trim=start=%.2f,setpts=PTS-STARTPTS[v3out]; " .
                "[v1out][v2out][v3out]concat=n=3:v=1:a=0[vout]",
                $startTime, $startTime, $endTime, $targetFps, 1 / $slowFactor, $endTime
            );

            // Use -stream_loop for reliable audio looping with enhanced video settings
            $cmd = sprintf(
                '"%s" -stream_loop %d -i "%s" -i "%s" -filter_complex "%s" -map "[vout]" -map 1:a -t %.2f -c:v libx264 -preset slower -crf 14 -pix_fmt yuv420p -profile:v high -level 4.1 -bf 3 -g 60 -keyint_min 60 -sc_threshold 0 -r %.1f -c:a aac -b:a 256k -af "volume=0.8" -avoid_negative_ts make_zero -y "%s" 2>&1',
                $this->ffmpegPath, max(1, $audioLoops), $inputPath, $backgroundAudio, $filterComplex, $finalDuration, $targetFps, $outputPath
            );
        } else {
            // Without overlay and without background audio - use the existing ultra smooth method
            return $this->createUltraSmoothSlowMotion($inputPath, $outputPath, $startTime, $endTime, $slowFactor);
        }

        error_log("Executing ultra-smooth with overlay command: $cmd");
        $this->executeCommand($cmd);
        
        if (!file_exists($outputPath) || filesize($outputPath) < 1000) {
            throw new Exception("Ultra-smooth with overlay processing failed");
        }
        
        error_log("Ultra-smooth slow motion with overlay completed successfully");
    }

    private function createStandardSmoothSlowMotionWithOverlay($inputPath, $outputPath, $startTime, $endTime, $slowFactor, $overlayPath, $addOverlay) {
        error_log("Creating standard smooth slow motion with overlay");
        
        // Get background audio file
        $audioDir = dirname(__DIR__) . '/audio/';
        $audioFiles = glob($audioDir . '*');
        $backgroundAudio = null;
        
        // Find the first audio file in the audio folder
        foreach ($audioFiles as $file) {
            if (is_file($file)) {
                $backgroundAudio = $file;
                break;
            }
        }
        
        // Calculate final video duration
        $originalDuration = $this->getVideoDuration($inputPath);
        $slowMotionDuration = ($endTime - $startTime) / $slowFactor;
        $finalDuration = $originalDuration - ($endTime - $startTime) + $slowMotionDuration;
        
        // Calculate how many times we need to repeat the audio for background audio cases
        $audioLoops = ceil($finalDuration / 9.01); // 9.01 is the duration of our audio file
        error_log("Final video duration: $finalDuration seconds, Audio loops needed: $audioLoops");
        
        // Get video dimensions
        $frameRateCmd = sprintf('"%s" -i "%s" 2>&1', $this->ffmpegPath, $inputPath);
        $frameRateOutput = shell_exec($frameRateCmd);
        preg_match('/Stream.*Video.*?(\d{3,})x(\d{3,})/', $frameRateOutput, $dimMatches);
        $videoWidth = isset($dimMatches[1]) ? intval($dimMatches[1]) : 1920;
        $videoHeight = isset($dimMatches[2]) ? intval($dimMatches[2]) : 1080;
        
        if ($addOverlay && $backgroundAudio) {
            $filterComplex = sprintf(
                "[0:v]split=3[v1][v2][v3]; " .
                "[v1]trim=start=0:end=%.2f,setpts=PTS-STARTPTS[v1out]; " .
                "[v2]trim=start=%.2f:end=%.2f,fps=60,setpts=%.2f*(PTS-STARTPTS)[v2out]; " .
                "[v3]trim=start=%.2f,setpts=PTS-STARTPTS[v3out]; " .
                "[v1out][v2out][v3out]concat=n=3:v=1:a=0[video]; " .
                "[1:v]scale=%d:%d[overlay_scaled]; " .
                "[video][overlay_scaled]overlay=0:0[vout]",
                $startTime, $startTime, $endTime, 1 / $slowFactor, $endTime,
                $videoWidth, $videoHeight
            );

            // Use -stream_loop for reliable audio looping with enhanced video settings
            $cmd = sprintf(
                '"%s" -stream_loop %d -i "%s" -i "%s" -i "%s" -filter_complex "%s" -map "[vout]" -map 2:a -t %.2f -c:v libx264 -preset slower -crf 14 -pix_fmt yuv420p -profile:v high -level 4.1 -bf 3 -g 60 -keyint_min 60 -sc_threshold 0 -movflags +faststart -c:a aac -b:a 256k -af "volume=0.8" -avoid_negative_ts make_zero -y "%s" 2>&1',
                $this->ffmpegPath, max(1, $audioLoops), $inputPath, $overlayPath, $backgroundAudio, $filterComplex, $finalDuration, $outputPath
            );
        } else if ($addOverlay && !$backgroundAudio) {
            // With overlay but no background audio - use original audio with slow motion processing
            $filterComplex = sprintf(
                "[0:v]split=3[v1][v2][v3]; " .
                "[v1]trim=start=0:end=%.2f,setpts=PTS-STARTPTS[v1out]; " .
                "[v2]trim=start=%.2f:end=%.2f,setpts=%.2f*(PTS-STARTPTS)[v2out]; " .
                "[v3]trim=start=%.2f,setpts=PTS-STARTPTS[v3out]; " .
                "[v1out][v2out][v3out]concat=n=3:v=1:a=0[video]; " .
                "[1:v]scale=%d:%d[overlay_scaled]; " .
                "[video][overlay_scaled]overlay=0:0[vout]; " .
                "[0:a]asplit=3[a1][a2][a3]; " .
                "[a1]atrim=start=0:end=%.2f,asetpts=PTS-STARTPTS[a1out]; " .
                "[a2]atrim=start=%.2f:end=%.2f,atempo=%.3f,asetpts=PTS-STARTPTS[a2out]; " .
                "[a3]atrim=start=%.2f,asetpts=PTS-STARTPTS[a3out]; " .
                "[a1out][a2out][a3out]concat=n=3:v=0:a=1[aout]",
                $startTime, $startTime, $endTime, 1 / $slowFactor, $endTime,
                $videoWidth, $videoHeight,
                $startTime, $startTime, $endTime, $slowFactor, $endTime
            );

            $cmd = sprintf(
                '"%s" -i "%s" -i "%s" -filter_complex "%s" -map "[vout]" -map "[aout]" -c:v libx264 -preset medium -crf 18 -pix_fmt yuv420p -movflags +faststart -c:a aac -b:a 192k -avoid_negative_ts make_zero -y "%s" 2>&1',
                $this->ffmpegPath, $inputPath, $overlayPath, $filterComplex, $outputPath
            );
        } else if ($backgroundAudio) {
            // No overlay but use background audio - mute original audio and use background audio
            $filterComplex = sprintf(
                "[0:v]split=3[v1][v2][v3]; " .
                "[v1]trim=start=0:end=%.2f,setpts=PTS-STARTPTS[v1out]; " .
                "[v2]trim=start=%.2f:end=%.2f,fps=60,setpts=%.2f*(PTS-STARTPTS)[v2out]; " .
                "[v3]trim=start=%.2f,setpts=PTS-STARTPTS[v3out]; " .
                "[v1out][v2out][v3out]concat=n=3:v=1:a=0[vout]",
                $startTime, $startTime, $endTime, 1 / $slowFactor, $endTime
            );

            // Use -stream_loop for reliable audio looping with enhanced video settings
            $cmd = sprintf(
                '"%s" -stream_loop %d -i "%s" -i "%s" -filter_complex "%s" -map "[vout]" -map 1:a -t %.2f -c:v libx264 -preset slower -crf 14 -pix_fmt yuv420p -profile:v high -level 4.1 -bf 3 -g 60 -keyint_min 60 -sc_threshold 0 -movflags +faststart -c:a aac -b:a 256k -af "volume=0.8" -avoid_negative_ts make_zero -y "%s" 2>&1',
                $this->ffmpegPath, max(1, $audioLoops), $inputPath, $backgroundAudio, $filterComplex, $finalDuration, $outputPath
            );
        } else {
            // Without overlay - use the existing standard method
            return $this->createStandardSmoothSlowMotion($inputPath, $outputPath, $startTime, $endTime, $slowFactor);
        }

        error_log("Executing standard smooth with overlay command: $cmd");
        $this->executeCommand($cmd);
        
        if (!file_exists($outputPath) || filesize($outputPath) < 1000) {
            throw new Exception("Standard smooth with overlay processing failed");
        }
        
        error_log("Standard smooth slow motion with overlay completed successfully");
    }

    private function createSlowMotionVideo($inputPath, $outputPath, $startTime, $endTime, $slowFactor, $qualityMode = 'smooth') {
        // Get video duration
        $duration = $this->getVideoDuration($inputPath);
        
        if ($endTime > $duration) {
            throw new Exception('End time exceeds video duration');
        }

        error_log("Creating smooth slow motion: start=$startTime, end=$endTime, factor=$slowFactor, duration=$duration, quality=$qualityMode");

        // Choose processing method based on quality mode
        switch ($qualityMode) {
            case 'ultra':
                try {
                    $this->createUltraSmoothSlowMotion($inputPath, $outputPath, $startTime, $endTime, $slowFactor);
                    return;
                } catch (Exception $e) {
                    error_log("Ultra-smooth method failed, trying standard: " . $e->getMessage());
                    // Fall through to smooth mode
                }
                // no break - intentional fall-through
                
            case 'smooth':
                try {
                    $this->createStandardSmoothSlowMotion($inputPath, $outputPath, $startTime, $endTime, $slowFactor);
                    return;
                } catch (Exception $e) {
                    error_log("Standard smooth method failed, trying simple: " . $e->getMessage());
                    // Fall through to fast mode
                }
                // no break - intentional fall-through
                
            case 'fast':
            default:
                $this->createSimpleSlowMotion($inputPath, $outputPath, $startTime, $endTime, $slowFactor);
                break;
        }
    }

    private function createUltraSmoothSlowMotion($inputPath, $outputPath, $startTime, $endTime, $slowFactor) {
        error_log("Attempting ultra-smooth slow motion with frame interpolation");
        
        // This method uses minterpolate filter for frame interpolation
        // Fixed to prevent frame freezing issues
        
        $slowDuration = $endTime - $startTime;
        
        // Get video frame rate
        $frameRateCmd = sprintf(
            '"%s" -i "%s" 2>&1',
            $this->ffmpegPath,
            $inputPath
        );
        $frameRateOutput = shell_exec($frameRateCmd);
        preg_match('/(\d+(?:\.\d+)?)\s*fps/', $frameRateOutput, $matches);
        $originalFps = isset($matches[1]) ? floatval($matches[1]) : 30;
        
        error_log("Original FPS: $originalFps");
        
        // Calculate target FPS for smooth slow motion
        $targetFps = min($originalFps * 2, 60); // Cap at 60fps to avoid issues
        
        // Improved filter that prevents frame freezing
        $filterComplex = sprintf(
            "[0:v]split=3[v1][v2][v3]; " .
            "[v1]trim=start=0:end=%.2f,setpts=PTS-STARTPTS[v1out]; " .
            "[v2]trim=start=%.2f:end=%.2f,minterpolate=fps=%.1f:mi_mode=mci:mc_mode=aobmc:me_mode=bidir,setpts=%.2f*(PTS-STARTPTS)[v2out]; " .
            "[v3]trim=start=%.2f,setpts=PTS-STARTPTS[v3out]; " .
            "[v1out][v2out][v3out]concat=n=3:v=1:a=0[vout]; " .
            "[0:a]asplit=3[a1][a2][a3]; " .
            "[a1]atrim=start=0:end=%.2f,asetpts=PTS-STARTPTS[a1out]; " .
            "[a2]atrim=start=%.2f:end=%.2f,atempo=%.3f,asetpts=PTS-STARTPTS[a2out]; " .
            "[a3]atrim=start=%.2f,asetpts=PTS-STARTPTS[a3out]; " .
            "[a1out][a2out][a3out]concat=n=3:v=0:a=1[aout]",
            $startTime,                    // v1 end time
            $startTime, $endTime,          // v2 start and end time
            $targetFps,                    // interpolated frame rate
            1 / $slowFactor,               // slow motion factor for video
            $endTime,                      // v3 start time
            $startTime,                    // a1 end time
            $startTime, $endTime,          // a2 start and end time
            $slowFactor,                   // tempo change for audio
            $endTime                       // a3 start time
        );

        $cmd = sprintf(
            '"%s" -i "%s" -filter_complex "%s" -map "[vout]" -map "[aout]" -c:v libx264 -preset medium -crf 20 -pix_fmt yuv420p -r %.1f -c:a aac -b:a 192k -avoid_negative_ts make_zero -y "%s" 2>&1',
            $this->ffmpegPath,
            $inputPath,
            $filterComplex,
            $targetFps,
            $outputPath
        );

        error_log("Executing ultra-smooth slow motion command: $cmd");
        $this->executeCommand($cmd);
        
        if (!file_exists($outputPath) || filesize($outputPath) < 1000) {
            throw new Exception("Ultra-smooth processing failed");
        }
        
        error_log("Ultra-smooth slow motion completed successfully");
    }

    private function createStandardSmoothSlowMotion($inputPath, $outputPath, $startTime, $endTime, $slowFactor) {
        error_log("Attempting standard smooth slow motion");
        
        // Enhanced standard method with better quality settings and ultra-smooth slow motion
        // Fixed to prevent video frame freezing and add motion smoothing
        
        $audioFilter = $this->createAudioSlowFilter($slowFactor);
        
        $filterComplex = sprintf(
            "[0:v]split=3[v1][v2][v3]; " .
            "[v1]trim=start=0:end=%.2f,setpts=PTS-STARTPTS[v1out]; " .
            "[v2]trim=start=%.2f:end=%.2f,fps=60,setpts=%.2f*(PTS-STARTPTS)[v2out]; " .
            "[v3]trim=start=%.2f,setpts=PTS-STARTPTS[v3out]; " .
            "[v1out][v2out][v3out]concat=n=3:v=1:a=0[vout]; " .
            "[0:a]asplit=3[a1][a2][a3]; " .
            "[a1]atrim=start=0:end=%.2f,asetpts=PTS-STARTPTS[a1out]; " .
            "[a2]atrim=start=%.2f:end=%.2f,%s,asetpts=PTS-STARTPTS[a2out]; " .
            "[a3]atrim=start=%.2f,asetpts=PTS-STARTPTS[a3out]; " .
            "[a1out][a2out][a3out]concat=n=3:v=0:a=1[aout]",
            $startTime,           // v1 end time
            $startTime, $endTime, // v2 start and end time
            1 / $slowFactor,      // slow motion factor for video
            $endTime,             // v3 start time
            $startTime,           // a1 end time
            $startTime, $endTime, // a2 start and end time
            $audioFilter,         // chained atempo filters for extreme slow motion
            $endTime              // a3 start time
        );

        $cmd = sprintf(
            '"%s" -i "%s" -filter_complex "%s" -map "[vout]" -map "[aout]" -c:v libx264 -preset slower -crf 14 -pix_fmt yuv420p -profile:v high -level 4.1 -bf 3 -g 60 -keyint_min 60 -sc_threshold 0 -movflags +faststart -c:a aac -b:a 256k -avoid_negative_ts make_zero -y "%s" 2>&1',
            $this->ffmpegPath,
            $inputPath,
            $filterComplex,
            $outputPath
        );

        error_log("Executing standard smooth slow motion command: $cmd");
        $this->executeCommand($cmd);
        
        if (!file_exists($outputPath) || filesize($outputPath) < 1000) {
            throw new Exception("Standard smooth processing failed");
        }
        
        error_log("Standard smooth slow motion completed successfully");
    }

    private function createSimpleSlowMotion($inputPath, $outputPath, $startTime, $endTime, $slowFactor) {
        error_log("Trying simple slow motion approach");
        
        // Much simpler and more reliable approach
        // This method processes the entire video in one pass to avoid concatenation issues
        
        $duration = $this->getVideoDuration($inputPath);
        
        // Create a single filter that handles the entire video
        // Uses select and setpts filters for better frame continuity
        
        $filterComplex = sprintf(
            "[0:v]setpts=if(between(t,%.2f,%.2f),%.2f*PTS,PTS)[vout]; " .
            "[0:a]atempo=if(between(t,%.2f,%.2f),%.3f,1.0)[aout]",
            $startTime, $endTime, 1 / $slowFactor,  // video slow motion
            $startTime, $endTime, $slowFactor       // audio tempo adjustment
        );

        // Alternative simpler approach if the above doesn't work
        $simpleFilterComplex = sprintf(
            "[0:v]trim=start=%.2f:end=%.2f,setpts=%.2f*PTS[slow]; " .
            "[0:v]trim=start=0:end=%.2f[before]; " .
            "[0:v]trim=start=%.2f[after]; " .
            "[before][slow][after]concat=n=3:v=1:a=0[vout]; " .
            "[0:a]atrim=start=%.2f:end=%.2f,atempo=%.3f[slow_audio]; " .
            "[0:a]atrim=start=0:end=%.2f[before_audio]; " .
            "[0:a]atrim=start=%.2f[after_audio]; " .
            "[before_audio][slow_audio][after_audio]concat=n=3:v=0:a=1[aout]",
            $startTime, $endTime, 1 / $slowFactor,  // slow video section
            $startTime,                              // before section end
            $endTime,                                // after section start
            $startTime, $endTime, $slowFactor,       // slow audio section
            $startTime,                              // before audio end
            $endTime                                 // after audio start
        );

        // Try the simple approach first
        $cmd = sprintf(
            '"%s" -i "%s" -filter_complex "%s" -map "[vout]" -map "[aout]" -c:v libx264 -preset fast -crf 23 -pix_fmt yuv420p -avoid_negative_ts make_zero -y "%s" 2>&1',
            $this->ffmpegPath,
            $inputPath,
            $simpleFilterComplex,
            $outputPath
        );

        error_log("Executing simple slow motion command: $cmd");
        
        try {
            $this->executeCommand($cmd);
        } catch (Exception $e) {
            error_log("Simple method failed, trying basic approach: " . $e->getMessage());
            
            // Even simpler fallback - just process the slow section and merge
            $this->createBasicSlowMotion($inputPath, $outputPath, $startTime, $endTime, $slowFactor);
            return;
        }
        
        if (!file_exists($outputPath) || filesize($outputPath) < 1000) {
            throw new Exception("Simple processing failed");
        }
        
        error_log("Simple slow motion completed successfully");
    }

    private function createBasicSlowMotion($inputPath, $outputPath, $startTime, $endTime, $slowFactor) {
        error_log("Using basic slow motion approach");
        
        // Most basic approach - just slow down the specified segment without audio complexity
        $filterComplex = sprintf(
            "[0:v]trim=start=%.2f:end=%.2f,setpts=%.2f*PTS[slow]; " .
            "[0:v]trim=start=0:end=%.2f[before]; " .
            "[0:v]trim=start=%.2f[after]; " .
            "[before][slow][after]concat=n=3:v=1[vout]",
            $startTime, $endTime, 1 / $slowFactor,
            $startTime,
            $endTime
        );

        $cmd = sprintf(
            '"%s" -i "%s" -filter_complex "%s" -map "[vout]" -map 0:a -c:v libx264 -preset fast -crf 25 -c:a copy -avoid_negative_ts make_zero -y "%s" 2>&1',
            $this->ffmpegPath,
            $inputPath,
            $filterComplex,
            $outputPath
        );

        error_log("Executing basic slow motion command: $cmd");
        $this->executeCommand($cmd);
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
?>
