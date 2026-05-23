<?php
require 'config.php';

$admin_key = $_SERVER['HTTP_X_ADMIN_KEY'] ?? '';
if ($admin_key !== "admin123") { http_response_code(403); echo json_encode(["error" => "Forbidden"]); exit; }

$data = json_decode(file_get_contents("php://input"), true);
$proof_id = $data['id'] ?? 0;

$conn->begin_transaction();

$stmt = $conn->prepare("SELECT * FROM payment_proofs WHERE id = ? AND status = 'pending'");
$stmt->bind_param("i", $proof_id);
$stmt->execute();
$proof = $stmt->get_result()->fetch_assoc();

if (!$proof) { echo json_encode(["error" => "Invalid proof"]); exit; }

$conn->query("UPDATE payment_proofs SET status='approved' WHERE id=$proof_id");
$conn->query("UPDATE users SET balance = balance + {$proof['amount']}, has_funded=1 WHERE id={$proof['user_id']}");

$conn->commit();
echo json_encode(["success" => true]);
?>