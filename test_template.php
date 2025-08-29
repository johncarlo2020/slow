<?php
// Simple test to check template detection
$templateDir = '../template/';
$possibleFiles = [
    $templateDir . 'Preview Screen V4.webp',
    $templateDir . 'Preview Screen V4.png',
    $templateDir . 'Preview Screen V4.jpg',
    $templateDir . 'Preview Screen V4.jpeg'
];

echo "Template directory: " . realpath($templateDir) . "\n";
echo "Template directory exists: " . (is_dir($templateDir) ? 'YES' : 'NO') . "\n\n";

foreach ($possibleFiles as $file) {
    $realPath = realpath($file);
    echo "Checking: $file\n";
    echo "Real path: " . ($realPath ? $realPath : 'NOT FOUND') . "\n";
    echo "Exists: " . (file_exists($file) ? 'YES' : 'NO') . "\n";
    if (file_exists($file)) {
        echo "Size: " . filesize($file) . " bytes\n";
    }
    echo "---\n";
}

// Test absolute path
$absoluteDir = __DIR__ . '/../template/';
echo "\nAbsolute template dir: $absoluteDir\n";
echo "Absolute dir exists: " . (is_dir($absoluteDir) ? 'YES' : 'NO') . "\n";

$absoluteFiles = [
    $absoluteDir . 'Preview Screen V4.webp',
    $absoluteDir . 'Preview Screen V4.png',
    $absoluteDir . 'Preview Screen V4.jpg',
    $absoluteDir . 'Preview Screen V4.jpeg'
];

foreach ($absoluteFiles as $file) {
    echo "Absolute check: $file - " . (file_exists($file) ? 'YES' : 'NO') . "\n";
}
?>
