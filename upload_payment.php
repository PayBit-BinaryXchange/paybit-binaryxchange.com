<?php
ob_clean();
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(["success" => true]);
    exit;
}

require 'config.php';

$user_id = getUserIdFromToken();
if (!$user_id) { 
    http_response_code(401); 
    echo json_encode(["success" => false, "message" => "Unauthorized"]); 
    exit; 
}

$crypto = $_POST['crypto'] ?? '';
$amount = $_POST['amount'] ?? 0;
$address = $_POST['address'] ?? '';

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "No file uploaded"]); 
    exit;
}

$target_dir = __DIR__ . "/uploads/";
if (!file_exists($target_dir)) {
    mkdir($target_dir, 0777, true);
}

$filename = time() . "-" . preg_replace('/[^a-zA-Z0-9._-]/', '', basename($_FILES["file"]["name"]));
$target_file = $target_dir . $filename;
$url = "https://paybit-binaryxchange-com.onrender.com/uploads/" . $filename;

if (!move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Upload failed"]);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO payment_proofs (user_id, crypto, amount, address, proof_url, status) 
                            VALUES (?, ?, 'pending')");
    $stmt->bind_param("isdss", $user_id, $crypto, $amount, $address, $url);
    $stmt->execute();
    
    $conn->prepare("UPDATE users SET has_funded = TRUE WHERE id = ?")
         ->execute([$user_id]);
    
    echo json_encode(["success" => true, "message" => "Proof uploaded", "proof_url" => $url]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "DB error"]);
}
