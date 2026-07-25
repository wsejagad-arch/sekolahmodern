<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized');
}

if (!extension_loaded('gd')) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Ekstensi GD belum aktif di PHP. Silakan restart Apache di XAMPP Control Panel agar perubahan php.ini berlaku.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Method Not Allowed');
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== 0) {
    header('HTTP/1.1 400 Bad Request');
    exit('Image is required');
}

$style = $_POST['style'] ?? 'formal';
$pose = $_POST['pose'] ?? 'straight';

// Load Image
$filePath = $_FILES['image']['tmp_name'];

if (!file_exists($filePath)) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('File temporary tidak ditemukan. Cek folder xampp/tmp.');
}

$info = @getimagesize($filePath);
if ($info === false) {
    // Debug info
    $error = error_get_last();
    header('HTTP/1.1 400 Bad Request');
    exit('Format file tidak dikenali sebagai gambar oleh PHP. Pastikan file valid dan Apache sudah direstart setelah perubahan php.ini.');
}
$mime = $info['mime'];

switch ($mime) {
    case 'image/jpeg':
        $image = imagecreatefromjpeg($filePath);
        break;
    case 'image/png':
        $image = imagecreatefrompng($filePath);
        break;
    case 'image/webp':
        $image = imagecreatefromwebp($filePath);
        break;
    default:
        header('HTTP/1.1 400 Bad Request');
        exit('Unsupported image type');
}

if (!$image) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Failed to load image');
}

// 1. Auto-Crop to 4:5 Portrait
$width = imagesx($image);
$height = imagesy($image);
$targetAspect = 4/5;
$currentAspect = $width / $height;

if (abs($currentAspect - $targetAspect) > 0.05) {
    if ($currentAspect > $targetAspect) {
        $newWidth = $height * $targetAspect;
        $x = ($width - $newWidth) / 2;
        $image = imagecrop($image, ['x' => $x, 'y' => 0, 'width' => $newWidth, 'height' => $height]);
    } else {
        $newHeight = $width / $targetAspect;
        $y = ($height - $newHeight) / 3; // Focus on upper part
        $image = imagecrop($image, ['x' => 0, 'y' => $y, 'width' => $width, 'height' => $newHeight]);
    }
}

$width = imagesx($image);
$height = imagesy($image);

// 2. Base Enhancements
imagefilter($image, IMG_FILTER_CONTRAST, -15); // GD contrast is inverted -255 to 255 (negative means more contrast)
imagefilter($image, IMG_FILTER_BRIGHTNESS, 5);

// 3. Style Specifics
switch ($style) {
    case 'formal':
        imagefilter($image, IMG_FILTER_GRAYSCALE); // Base for muted look
        imagefilter($image, IMG_FILTER_COLORIZE, 0, 10, 20); // Subtle blue-ish tint
        imagefilter($image, IMG_FILTER_CONTRAST, -10);
        break;
    case 'batik':
        imagefilter($image, IMG_FILTER_CONTRAST, -25);
        imagefilter($image, IMG_FILTER_BRIGHTNESS, -5);
        break;
    case 'casual':
        imagefilter($image, IMG_FILTER_COLORIZE, 10, 5, 0); // Warm tint
        break;
    case 'blazer':
        imagefilter($image, IMG_FILTER_COLORIZE, 15, 0, 10); // Soft pink tint
        imagefilter($image, IMG_FILTER_BRIGHTNESS, 10);
        break;
}

// 4. Sharpening
$matrix = [
    [-1, -1, -1],
    [-1, 16, -1],
    [-1, -1, -1]
];
$divisor = array_sum(array_map('array_sum', $matrix));
imageconvolution($image, $matrix, $divisor, 0);

// 5. Professional Vignette
$vignette = imagecreatetruecolor($width, $height);
imagealphablending($vignette, false);
imagesavealpha($vignette, true);

for ($x = 0; $x < $width; $x++) {
    for ($y = 0; $y < $height; $y++) {
        $centerX = $width / 2;
        $centerY = $height / 2;
        $distance = sqrt(pow($x - $centerX, 2) + pow($y - $centerY, 2));
        $maxDistance = sqrt(pow($centerX, 2) + pow($centerY, 2));
        $opacity = pow($distance / $maxDistance, 2.5) * 0.4;
        $alpha = (int)($opacity * 127);
        $color = imagecolorallocatealpha($vignette, 0, 0, 0, $alpha);
        imagesetpixel($vignette, $x, $y, $color);
    }
}
imagealphablending($image, true);
imagecopy($image, $vignette, 0, 0, 0, 0, $width, $height);
imagedestroy($vignette);

// 6. Skin Smoothing (Simple approach: Blur a copy and blend it)
$smooth = imagecreatetruecolor($width, $height);
imagecopy($smooth, $image, 0, 0, 0, 0, $width, $height);
imagefilter($smooth, IMG_FILTER_GAUSSIAN_BLUR);
// Blend smooth with original (50/50)
imagecopymerge($image, $smooth, 0, 0, 0, 0, $width, $height, 30);
imagedestroy($smooth);

// Output
header('Content-Type: image/jpeg');
imagejpeg($image, null, 90);
imagedestroy($image);
