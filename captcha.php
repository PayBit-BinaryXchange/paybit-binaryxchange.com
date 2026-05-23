<?php
session_start();

// Prevent any output before headers
if (headers_sent()) {
    exit('Headers already sent');
}

header("Content-Type: image/png");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Generate 6 char random code, avoid confusing chars like 0/O, 1/I
$chars = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";
$code = '';
for ($i = 0; $i < 6; $i++) {
    $code .= $chars[random_int(0, strlen($chars) - 1)];
}

// Save to session
$_SESSION['captcha'] = $code;
$_SESSION['captcha_time'] = time();

// Create image
$width = 120;
$height = 40;
$image = imagecreatetruecolor($width, $height);

// Colors
$bg = imagecolorallocate($image, 255, 255, 255);
$text_color = imagecolorallocate($image, 20, 20, 20);
$line_color = imagecolorallocate($image, 180, 180, 180);

// Fill background
imagefilledrectangle($image, 0, 0, $width, $height, $bg);

// Add noise lines
for ($i = 0; $i < 4; $i++) {
    imageline($image, random_int(0, $width), random_int(0, $height), 
                     random_int(0, $width), random_int(0, $height), $line_color);
}

// Add noise dots
for ($i = 0; $i < 100; $i++) {
    imagesetpixel($image, random_int(0, $width), random_int(0, $height), $line_color);
}

// Add text
$x = 20;
$y = 12;
for ($i = 0; $i < strlen($code); $i++) {
    $char = $code[$i];
    imagestring($image, 5, $x + ($i * 15), $y + random_int(-2, 2), $char, $text_color);
}

// Output image
imagepng($image);
imagedestroy($image);
exit();
