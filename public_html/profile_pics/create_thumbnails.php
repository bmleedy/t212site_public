<?php
/**
 * create_thumbnails.php - Profile Picture Thumbnail Generator
 *
 * CLI utility script that generates thumbnail images for profile pictures.
 * Creates 75px wide thumbnails maintaining aspect ratio.
 *
 * @security CLI-only execution - web access blocked
 * @security Only processes files in the script's directory (no path traversal)
 * @security Validates image types using exif_imagetype()
 *
 * Usage: php create_thumbnails.php [--force]
 *   --force  Recreate thumbnails even if they already exist
 */

// CLI-only check - block web access
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo 'Access denied. This script can only be run from the command line.';
    exit(1);
}

// Parse command line arguments
$FORCE_CREATE = in_array('--force', $argv ?? [], true);

// Link image type to correct image loader and saver
// Makes it easier to add additional types later on
const IMAGE_HANDLERS = [
    IMAGETYPE_JPEG => [
        'load' => 'imagecreatefromjpeg',
        'save' => 'imagejpeg',
        'quality' => 100
    ],
    IMAGETYPE_PNG => [
        'load' => 'imagecreatefrompng',
        'save' => 'imagepng',
        'quality' => 0
    ],
    IMAGETYPE_GIF => [
        'load' => 'imagecreatefromgif',
        'save' => 'imagegif',
        'quality' => null // GIF doesn't use quality parameter
    ]
];

/**
 * Create a thumbnail from a source image
 *
 * @param string $src Source file path (validated by exif_imagetype)
 * @param string $dest Destination file path
 * @param int $targetWidth Desired output width in pixels
 * @param int|null $targetHeight Desired output height (null = maintain aspect ratio)
 * @return bool True on success, false on failure
 */
function createThumbnail(string $src, string $dest, int $targetWidth, ?int $targetHeight = null): bool {
    // Validate source file exists and is readable
    if (!is_file($src) || !is_readable($src)) {
        echo "Error: Cannot read source file: {$src}\n";
        return false;
    }

    // Get the type of the image using EXIF data (more secure than extension)
    $type = @exif_imagetype($src);

    // Validate image type
    if ($type === false || !isset(IMAGE_HANDLERS[$type])) {
        echo "Error: Invalid or unsupported image type: {$src}\n";
        return false;
    }

    // Load the image with the correct loader
    $handler = IMAGE_HANDLERS[$type];
    $image = @call_user_func($handler['load'], $src);

    if ($image === false) {
        echo "Error: Failed to load image: {$src}\n";
        return false;
    }

    // Get original image dimensions
    $width = imagesx($image);
    $height = imagesy($image);

    if ($width === 0 || $height === 0) {
        echo "Error: Invalid image dimensions: {$src}\n";
        imagedestroy($image);
        return false;
    }

    // Maintain aspect ratio when no height set
    if ($targetHeight === null) {
        $ratio = $width / $height;

        if ($width > $height) {
            // Landscape orientation
            $targetHeight = (int)floor($targetWidth / $ratio);
        } else {
            // Portrait or square orientation
            $targetHeight = $targetWidth;
            $targetWidth = (int)floor($targetWidth * $ratio);
        }
    }

    // Ensure minimum dimensions
    $targetWidth = max(1, $targetWidth);
    $targetHeight = max(1, $targetHeight);

    // Create duplicate image based on calculated target size
    $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);

    if ($thumbnail === false) {
        echo "Error: Failed to create thumbnail canvas\n";
        imagedestroy($image);
        return false;
    }

    // Set transparency options for GIFs and PNGs
    if ($type === IMAGETYPE_GIF || $type === IMAGETYPE_PNG) {
        // Make image transparent
        imagecolortransparent(
            $thumbnail,
            imagecolorallocate($thumbnail, 0, 0, 0)
        );

        // Additional settings for PNGs
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }
    }

    // Copy and resize source image to thumbnail
    $success = imagecopyresampled(
        $thumbnail,
        $image,
        0, 0, 0, 0,
        $targetWidth, $targetHeight,
        $width, $height
    );

    if (!$success) {
        echo "Error: Failed to resample image\n";
        imagedestroy($image);
        imagedestroy($thumbnail);
        return false;
    }

    // Save the thumbnail to disk
    if ($handler['quality'] !== null) {
        $result = call_user_func($handler['save'], $thumbnail, $dest, $handler['quality']);
    } else {
        // GIF doesn't use quality parameter
        $result = call_user_func($handler['save'], $thumbnail, $dest);
    }

    // Clean up resources
    imagedestroy($image);
    imagedestroy($thumbnail);

    return (bool)$result;
}

/**
 * Validate that a filename is safe (no path traversal)
 *
 * @param string $filename The filename to validate
 * @return bool True if safe, false if potentially malicious
 */
function isValidFilename(string $filename): bool {
    // Reject any path components
    if (strpos($filename, '/') !== false || strpos($filename, '\\') !== false) {
        return false;
    }
    // Reject parent directory references
    if (strpos($filename, '..') !== false) {
        return false;
    }
    // Reject null bytes
    if (strpos($filename, "\0") !== false) {
        return false;
    }
    return true;
}

// Main script execution
echo "=== Profile Picture Thumbnail Generator ===\n";
echo "Mode: " . ($FORCE_CREATE ? "Force recreate all" : "Create missing only") . "\n";

// Use the script's directory (no external input for path)
$directory = __DIR__;
echo "Directory: {$directory}\n\n";

// Validate directory exists and is readable
if (!is_dir($directory) || !is_readable($directory)) {
    echo "Error: Cannot read directory\n";
    exit(1);
}

// Find source images (only .jpeg files)
$items = glob($directory . '/*.jpeg');

if ($items === false) {
    echo "Error: Failed to list files\n";
    exit(1);
}

echo "Found " . count($items) . " source images to process.\n\n";

$created = 0;
$skipped = 0;
$failed = 0;

// Process each file
foreach ($items as $item) {
    // Extract just the filename
    $filename = basename($item);

    // Validate filename safety
    if (!isValidFilename($filename)) {
        echo "[SKIP] Invalid filename: {$filename}\n";
        $failed++;
        continue;
    }

    // Generate thumbnail filename
    $fileBase = pathinfo($filename, PATHINFO_FILENAME);
    $thumbName = $directory . '/' . $fileBase . '_thumbnail.jpg';

    // Check whether a thumbnail already exists
    if (is_file($thumbName) && !$FORCE_CREATE) {
        echo "[SKIP] Thumbnail exists: {$fileBase}_thumbnail.jpg\n";
        $skipped++;
        continue;
    }

    // Create the thumbnail
    echo "[PROC] Creating thumbnail for: {$filename}... ";

    if (createThumbnail($item, $thumbName, 75)) {
        echo "OK\n";
        $created++;
    } else {
        echo "FAILED\n";
        $failed++;
    }
}

echo "\n=== Summary ===\n";
echo "Created: {$created}\n";
echo "Skipped: {$skipped}\n";
echo "Failed:  {$failed}\n";

exit($failed > 0 ? 1 : 0);
