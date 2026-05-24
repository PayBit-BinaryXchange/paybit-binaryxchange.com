<?php
ini_set('display_errors', 0);
error_reporting(0);

$dbUrl = getenv("DATABASE_URL");
$db = parse_url($dbUrl);
$pdo = new PDO(
    "pgsql:host=".$db["host"].";port=".($db["port"] ?? 5432).";dbname=".ltrim($db["path"],"/"),
    $db["user"] ?? '',
    $db["pass"] ?? ''
);

$id = $_GET['id'] ?? '';
$stmt = $pdo->prepare("SELECT code FROM captcha_codes WHERE id = ? AND expires_at > NOW() LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    exit();
}

$code = $row['code'];

// Generate image
$width = 120;
$height = 40;
$image = imagecreate($width, $height);
$bg = imagecolorallocate($image, 255, 255, 255);
$text = imagecolorallocate($image, 0, 0, 0);
$noise = imagecolorallocate($image, 100, 100, 100);

// Noise
for ($i = 0; $i < 50; $i++) {
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $noise);
}

imagestring($image, 5, 20, 12, $code, $text);

header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
