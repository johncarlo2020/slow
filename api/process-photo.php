<?php
// Headers are set in process.php when this file is included
// Only set headers if called directly
if (basename($_SERVER['PHP_SELF']) === 'process-photo.php') {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST');
    header('Access-Control-Allow-Headers: Content-Type');
}

class PhotoProcessor {
    private $templatePath;
    private $photoUploadDir;
    private $outputDir;

    public function __construct() {
        $this->templatePath = '../photo-template/template.png';
        $this->photoUploadDir = '../uploads/photos/';
        $this->outputDir = '../processed/photos/';
        
        // Create output directory if it doesn't exist
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    public function processPhoto($photoUrl) {
        try {
            // Extract filename from URL
            $photoPath = '../' . $photoUrl;
            if (!file_exists($photoPath)) {
                throw new Exception('Photo file not found: ' . $photoPath);
            }

            if (!file_exists($this->templatePath)) {
                throw new Exception('Template file not found: ' . $this->templatePath);
            }

            // Generate output filename
            $inputFilename = basename($photoPath);
            $outputFilename = 'processed_' . pathinfo($inputFilename, PATHINFO_FILENAME) . '_' . time() . '.png';
            $outputPath = $this->outputDir . $outputFilename;

            // Process the photo with template
            $this->compositePhotoWithTemplate($photoPath, $outputPath);

            return [
                'success' => true,
                'message' => 'Photo processed successfully',
                'originalPhoto' => $photoUrl,
                'processedPhoto' => 'processed/photos/' . $outputFilename,
                'filename' => $outputFilename
            ];

        } catch (Exception $e) {
            throw new Exception('Photo processing failed: ' . $e->getMessage());
        }
    }

    private function compositePhotoWithTemplate($photoPath, $outputPath) {
        // Load template
        $template = imagecreatefrompng($this->templatePath);
        if (!$template) {
            throw new Exception('Failed to load template');
        }

        // Enable alpha blending for transparency
        imagealphablending($template, true);
        imagesavealpha($template, true);

        // Get template dimensions
        $templateWidth = imagesx($template);
        $templateHeight = imagesy($template);

        // error_log("Template dimensions: {$templateWidth}x{$templateHeight}");

        // Load user photo
        $photoExt = strtolower(pathinfo($photoPath, PATHINFO_EXTENSION));
        if ($photoExt === 'jpg' || $photoExt === 'jpeg') {
            $userPhoto = imagecreatefromjpeg($photoPath);
        } elseif ($photoExt === 'png') {
            $userPhoto = imagecreatefrompng($photoPath);
        } else {
            throw new Exception('Unsupported photo format');
        }

        if (!$userPhoto) {
            throw new Exception('Failed to load user photo');
        }

        // Get user photo dimensions
        $photoWidth = imagesx($userPhoto);
        $photoHeight = imagesy($userPhoto);

        // error_log("User photo dimensions: {$photoWidth}x{$photoHeight}");

        // Create a new base image (this will be the bottom layer)
        $finalImage = imagecreatetruecolor($templateWidth, $templateHeight);
        
        // Fill with transparent background
        $transparent = imagecolorallocatealpha($finalImage, 0, 0, 0, 127);
        imagefill($finalImage, 0, 0, $transparent);
        imagealphablending($finalImage, true);
        imagesavealpha($finalImage, true);

        // Calculate dimensions and positions for left and right sections
        // Template is 1800x1200 with transparent photo areas on left and right
        // The photo areas where images should appear (behind the decorations)
        
        // Define the areas where photos should be placed (left and right)
        // Increased width to fill more of the transparent area
        $sectionWidth = 900;   // Width of each photo section (fills entire half)
        $sectionHeight = 1050; // Height of each photo section (increased from 900)
        $leftX = 0;            // X position for left photo (start at edge)
        $rightX = 900;         // X position for right photo (no gap in center)
        $photoY = 75;          // Y position for both photos (top margin)

        // Resize and place user photo on LEFT side (bottom layer)
        $resizedLeft = $this->resizeAndCrop($userPhoto, $sectionWidth, $sectionHeight);
        imagecopy($finalImage, $resizedLeft, $leftX, $photoY, 0, 0, $sectionWidth, $sectionHeight);
        imagedestroy($resizedLeft);

        // Resize and place user photo on RIGHT side (bottom layer)
        $resizedRight = $this->resizeAndCrop($userPhoto, $sectionWidth, $sectionHeight);
        imagecopy($finalImage, $resizedRight, $rightX, $photoY, 0, 0, $sectionWidth, $sectionHeight);
        imagedestroy($resizedRight);

        // Now overlay the template WITH decorations on top
        imagecopy($finalImage, $template, 0, 0, 0, 0, $templateWidth, $templateHeight);

        // Save the result
        imagepng($finalImage, $outputPath, 9);

        // Clean up
        imagedestroy($template);
        imagedestroy($userPhoto);
        imagedestroy($finalImage);

        // error_log("Photo processed successfully: " . $outputPath);
    }

    private function resizeAndCrop($image, $targetWidth, $targetHeight) {
        $srcWidth = imagesx($image);
        $srcHeight = imagesy($image);

        // Calculate aspect ratios
        $srcRatio = $srcWidth / $srcHeight;
        $targetRatio = $targetWidth / $targetHeight;

        // Determine dimensions to cover the target area
        if ($srcRatio > $targetRatio) {
            // Source is wider - fit to height
            $tempHeight = $targetHeight;
            $tempWidth = round($targetHeight * $srcRatio);
        } else {
            // Source is taller - fit to width
            $tempWidth = $targetWidth;
            $tempHeight = round($targetWidth / $srcRatio);
        }

        // Create temporary image at calculated size
        $tempImage = imagecreatetruecolor($tempWidth, $tempHeight);
        imagecopyresampled($tempImage, $image, 0, 0, 0, 0, $tempWidth, $tempHeight, $srcWidth, $srcHeight);

        // Create final image and crop to exact size
        $finalImage = imagecreatetruecolor($targetWidth, $targetHeight);
        
        // Calculate crop position (center crop)
        $cropX = round(($tempWidth - $targetWidth) / 2);
        $cropY = round(($tempHeight - $targetHeight) / 2);

        // Copy cropped portion
        imagecopy($finalImage, $tempImage, 0, 0, $cropX, $cropY, $targetWidth, $targetHeight);

        // Clean up temporary image
        imagedestroy($tempImage);

        return $finalImage;
    }
}

// Handle direct API call (optional - mainly used by process.php)
// Only execute this if the file is accessed directly, not when included
if (basename($_SERVER['SCRIPT_FILENAME']) === 'process-photo.php') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['photoUrl'])) {
        try {
            $processor = new PhotoProcessor();
            $result = $processor->processPhoto($_POST['photoUrl']);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}