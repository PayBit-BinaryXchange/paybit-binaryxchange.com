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

require 'config.php'; // your PDO connection $conn

define('JWT_SECRET', 'your_strong_secret_key_here'); // use same secret as login

function getUserIdFromToken() {
    global $conn;
    $headers = getallheaders();
    if (!isset($headers['Authorization'])) {
        return false;
    }

    $authHeader = $headers['Authorization'];
    $token = str_replace('Bearer ', '', $authHeader);

    // Decode JWT - basic version. Use firebase/php-jwt in production
    $parts = explode('.', $token);
    if (count($parts)!= 3) return false;

    $payload = json_decode(base64_decode($parts[1]), true);
    if (!$payload ||!isset($payload['user_id'])) return false;

    // Verify user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id =?");
    $stmt->execute([$payload['user_id']]);
    if ($stmt->rowCount() == 0) return false;

    return $payload['user_id'];
}

