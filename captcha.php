<?php
session_start();
header("Content-Type: image/png");
header("Cache-Control: no-cache, must-revalidate");

// Generate 6 char random code
$code = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 6);
$_SESSION['captcha'] = $code;

// Create image
$width = 120;
$height = 40;
$image = imagecreate($width, $height);

// Colors
$bg = imagecolorallocate($image, 255, 255, 255);
$text_color = imagecolorallocate($image, 20, 20, 20);
$noise_color = imagecolorallocate($image, 200, 200, 200);

// Fill background
imagefilledrectangle($image, 0, 0, $width, $height, $bg);

// Add noise
for ($i = 0; $i < 100; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
}

// Add text
imagettftext($image, 18, rand(-10, 10), 15, 28, $text_color, __DIR__.'/arial.ttf', $code);

// Output
imagepng($image);
imagedestroy($image);
?>
