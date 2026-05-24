<?php
ob_clean();
ini_set('display_errors', 0);
error_reporting(0);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'config.php';

$user_id = getUserIdFromToken();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$crypto = $data['crypto']?? '';
$address = $data['address']?? '';
$image_base64 = $data['image_base64']?? '';
$file_name = $data['file_name']?? '';

if (empty($image_base64)) {
    echo json_encode(["success" => false, "message" => "No image provided"]);
    exit;
}

$uploadDir = __DIR__. '/uploads/payment_proofs/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$filePath = $uploadDir. $file_name;
file_put_contents($filePath, base64_decode($image_base64));
$fileUrl = "https://paybit-binaryxchange-com.onrender.com/uploads/payment_proofs/". $file_name;

try {
    // Create table if not exists
    $conn->exec("CREATE TABLE IF NOT EXISTS payment_proofs (
        id SERIAL PRIMARY KEY,
        user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        crypto VARCHAR(10),
        address TEXT,
        proof_url TEXT,
        status VARCHAR(20) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT NOW()
    )");

    $stmt = $conn->prepare("INSERT INTO payment_proofs (user_id, crypto, address, proof_url, status)
                            VALUES (?,?, 'pending')");
    $stmt->execute([$user_id, $crypto, $address, $fileUrl]);

    echo json_encode(["success" => true, "message" => "Proof uploaded", "url" => $fileUrl]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "DB error"]);
}
