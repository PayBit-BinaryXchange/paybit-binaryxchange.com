<?php
session_start();

// Turn off error display so it doesn't break the image
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Clear any accidental output
if (ob_get_length()) ob_clean();

// Check GD is available
if (!extension_loaded('gd')) {
    header('Content-Type: text/plain');
    exit('GD extension not loaded on server');
}

header("Content-Type: image/png");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Generate 6 char code, avoid 0/O, 1/I
$chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
$code = '';
for ($i = 0; $i < 6; $i++) {
    $code .= $chars[random_int(0, strlen($chars) - 1)];
}

$_SESSION['captcha'] = $code;
$_SESSION['captcha_time'] = time();

// Create image
$width = 140;
$height = 45;
$image = imagecreatetruecolor($width, $height);
if (!$image) {
    exit();
}

// Colors
$bg = imagecolorallocate($image, 255, 255, 255);
$text_color = imagecolorallocate($image, 30, 30, 30);
$line_color = imagecolorallocate($image, 180, 180, 180);

imagefilledrectangle($image, 0, 0, $width, $height, $bg);

// Noise lines
for ($i = 0; $i < 4; $i++) {
    imageline($image, random_int(0, $width), random_int(0, $height), 
                     random_int(0, $width), random_int(0, $height), $line_color);
}

// Noise dots
for ($i = 0; $i < 80; $i++) {
    imagesetpixel($image, random_int(0, $width), random_int(0, $height), $line_color);
}

// Text using built-in font so no .ttf needed
$x = 15;
$y = 15;
for ($i = 0; $i < strlen($code); $i++) {
    $char = $code[$i];
    imagestring($image, 5, $x + ($i * 18), $y + random_int(-3, 3), $char, $text_color);
}

// Output
imagepng($image);
imagedestroy($image);
exit();

