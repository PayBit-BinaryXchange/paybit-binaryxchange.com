<?php
ini_set('display_errors', 0);
error_reporting(0);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$dbUrl = getenv("DATABASE_URL");
if (!$dbUrl) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB URL not set']);
    exit();
}

$db = parse_url($dbUrl);
$pdo = new PDO(
    "pgsql:host=".$db["host"].";port=".($db["port"] ?? 5432).";dbname=".ltrim($db["path"],"/"),
    $db["user"] ?? '',
    $db["pass"] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Get token from header
$headers = getallheaders();
$token = str_replace('Bearer ', '', $headers['Authorization'] ?? '');

// Verify token and get user_id
$stmt = $pdo->prepare("SELECT user_id FROM tokens WHERE token = ? LIMIT 1");
$stmt->execute([$token]);
$row = $stmt->fetch();
if (!$row) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$user_id = $data['user_id'] ?? 0;
$crypto = $data['crypto'] ?? '';
$address = $data['address'] ?? '';
$amount = $data['amount'] ?? 0;
$image_base64 = $data['image_base64'] ?? '';
$file_name = $data['file_name'] ?? '';

if (empty($image_base64)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No image provided']);
    exit();
}

// Create uploads folder if not exists
$uploadDir = __DIR__ . '/uploads/payment_proofs/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$filePath = $uploadDir . $file_name;
file_put_contents($filePath, base64_decode($image_base64));
$fileUrl = "https://paybit-binaryxchange-com.onrender.com/uploads/payment_proofs/" . $file_name;

// Create table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS payment_proofs (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    crypto VARCHAR(10),
    address TEXT,
    amount DECIMAL(20,8),
    proof_url TEXT,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT NOW()
)");

// Insert record
$stmt = $pdo->prepare("INSERT INTO payment_proofs (user_id, crypto, address, amount, proof_url) 
                       VALUES (?, ?, ?)");
$stmt->execute([$user_id, $crypto, $address, $amount, $fileUrl]);

// Update user has_funded
$pdo->prepare("UPDATE users SET has_funded = TRUE WHERE id = ?")->execute([$user_id]);

echo json_encode(['success' => true, 'message' => 'Proof uploaded', 'url' => $fileUrl]);
