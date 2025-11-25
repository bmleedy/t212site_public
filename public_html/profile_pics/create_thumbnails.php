<?php
// Link image type to correct image loader and saver
// - makes it easier to add additional types later on
// - makes the function easier to read
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
        'save' => 'imagegif'
    ]
];

/**
 * @param $src - a valid file location
 * @param $dest - a valid file target
 * @param $targetWidth - desired output width
 * @param $targetHeight - desired output height or null
 */
function createThumbnail($src, $dest, $targetWidth, $targetHeight = null) {

    // 1. Load the image from the given $src
    // - see if the file actually exists
    // - check if it's of a valid image type
    // - load the image resource

    // get the type of the image
    // we need the type to determine the correct loader
    $type = exif_imagetype($src);

    // if no valid type or no handler found -> exit
    if (!$type || !IMAGE_HANDLERS[$type]) {
        return null;
    }

    // load the image with the correct loader
    $image = call_user_func(IMAGE_HANDLERS[$type]['load'], $src);

    // no image found at supplied location -> exit
    if (!$image) {
        return null;
    }


    // 2. Create a thumbnail and resize the loaded $image
    // - get the image dimensions
    // - define the output size appropriately
    // - create a thumbnail based on that size
    // - set alpha transparency for GIFs and PNGs
    // - draw the final thumbnail

    // get original image width and height
    $width = imagesx($image);
    $height = imagesy($image);

    // maintain aspect ratio when no height set
    if ($targetHeight == null) {

        // get width to height ratio
        $ratio = $width / $height;

        // if is portrait
        // use ratio to scale height to fit in square
        if ($width > $height) {
            $targetHeight = floor($targetWidth / $ratio);
        }
        // if is landscape
        // use ratio to scale width to fit in square
        else {
            $targetHeight = $targetWidth;
            $targetWidth = floor($targetWidth * $ratio);
        }
    }

    // create duplicate image based on calculated target size
    $thumbnail = imagecreatetruecolor($targetWidth, $targetHeight);

    // set transparency options for GIFs and PNGs
    if ($type == IMAGETYPE_GIF || $type == IMAGETYPE_PNG) {

        // make image transparent
        imagecolortransparent(
            $thumbnail,
            imagecolorallocate($thumbnail, 0, 0, 0)
        );

        // additional settings for PNGs
        if ($type == IMAGETYPE_PNG) {
            imagealphablending($thumbnail, false);
            imagesavealpha($thumbnail, true);
        }
    }

    // copy entire source image to duplicate image and resize
    imagecopyresampled(
        $thumbnail,
        $image,
        0, 0, 0, 0,
        $targetWidth, $targetHeight,
        $width, $height
    );


    // 3. Save the $thumbnail to disk
    // - call the correct save method
    // - set the correct quality level

    // save the duplicate version of the image to disk
    return call_user_func(
        IMAGE_HANDLERS[$type]['save'],
        $thumbnail,
        $dest,
        IMAGE_HANDLERS[$type]['quality']
    );
}


// Use the thumbnail function on each file to create thumbnails that are missing.

// this file acts on the same directory as itself.


if (is_dir('/home/u321706752/public_html/git_site/public_html/profile_pics')) {
    // if this is the test website, the test directory doesn't work
    echo "Using test directory for thumbnails.\n";
    $directory = '/home/u321706752/public_html/git_site/public_html/profile_pics';
} else {
    // if not found, use the current directory
    echo "Using current directory for thumbnails.\n";
    $directory = __DIR__;
}
$directory = __DIR__;

$FORCE_CREATE = false; // set to true to force creation of thumbnails even if they already exist

// fetch the .jpeg files in the directory
// this will return an array of file paths
$items = glob($directory . '/*.jpeg');
$existing_thumbnails = glob($directory . '/*.jpg');

echo "running create_thumbnails.php\n";
echo "Found " . count($items) . " files to process.\n";

// iterate over each file
foreach ($items as $item) {
    echo "Processing file: {$item}\n";
    // extract the filename without the path
    $path_array = explode('/', $item);
    $filename = array_pop($path_array);
    $file_base = explode('.', $filename)[0];
    
    $thumb_name = implode("/", $path_array) ."/". $file_base. '_thumbnail.jpg';
    echo "Processing thumbnail: {$thumb_name}\n";

    // check whether a thumbnail already exists
    if (is_file($thumb_name) && !$FORCE_CREATE) {
        echo "Thumbnail exists: {$thumb_name}\n";
    }
    // if not, create a thumbnail
    else {
        $result = createThumbnail($item, $thumb_name, 75);
        if ($result) {
            echo "Created thumbnail: {$thumb_name}\n";
        } else {
            echo "Failed to create thumbnail for: {$item}\n";
        }
    }
}

