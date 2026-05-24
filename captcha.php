<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *"); 

$dbUrl = getenv("DATABASE_URL");
if (!$dbUrl) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DATABASE_URL not set']);
    exit();
}

$db = parse_url($dbUrl);
try {
    $pdo = new PDO(
        "pgsql:host=".$db["host"].";port=".($db["port"] ?? 5432).";dbname=".ltrim($db["path"],"/"),
        $db["user"] ?? '',
        $db["pass"] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit();
}

// Generate captcha
$id = bin2hex(random_bytes(16));
$code = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 5));
$expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Store in DB
$stmt = $pdo->prepare("INSERT INTO captcha_codes (id, code, expires_at) VALUES (?, ?, ?)");
$stmt->execute([$id, $code, $expires]);

// Return JSON with image URL
$image_url = "https://paybit-binaryxchange-com.onrender.com/captcha_image.php?id=$id";

echo json_encode([
    'success' => true,
    'id' => $id,
    'image_url' => $image_url
]);
