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

$data = json_decode(file_get_contents("php://input"), true);
$method = trim($data['method']?? '');
$amount = floatval($data['amount']?? 0);
$currency = $data['currency']?? 'USD';

if (empty($method) || $amount <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Method and amount required"]);
    exit;
}

try {
    $stmt = $conn->prepare("INSERT INTO deposits (user_id, method, amount, currency, status)
                            VALUES (?,?, 'pending')");
    $stmt->execute([$user_id, $method, $amount, $currency]);

    echo json_encode(["success" => true, "message" => "Deposit request submitted"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "DB error"]);
}
