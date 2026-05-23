<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$user_id = getUserIdFromToken();
if (!$user_id) { http_response_code(401); echo json_encode(["error" => "Unauthorized"]); exit; }

$crypto = $_POST['crypto'] ?? '';
$amount = $_POST['amount'] ?? 0;
$address = $_POST['address'] ?? '';

if (!isset($_FILES['file'])) {
    echo json_encode(["error" => "No file uploaded"]); exit;
}

$target_dir = "uploads/";
$filename = time() . "-" . basename($_FILES["file"]["name"]);
$target_file = $target_dir . $filename;

if (move_uploaded_file($_FILES["file"]["tmp_name"], $target_file)) {
    $stmt = $conn->prepare("INSERT INTO payment_proofs (user_id, crypto, amount, address, proof_url, status) VALUES (?, ?, 'pending')");
    $url = "/uploads/" . $filename;
    $stmt->bind_param("isdss", $user_id, $crypto, $amount, $address, $url);
    $stmt->execute();
    
    echo json_encode(["success" => true, "proof_url" => $url]);
} else {
    echo json_encode(["error" => "Upload failed"]);
}
?>