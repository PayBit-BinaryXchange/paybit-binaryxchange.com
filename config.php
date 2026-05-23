<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, x-admin-key");
header("Content-Type: application/json");

$host = "localhost";
$db   = "paybit";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(["error" => "DB connection failed"]));
}

function getUserIdFromToken() {
    // Use JWT or simple session. For now, pass user_id in header for demo
    return $_SERVER['HTTP_X_USER_ID'] ?? null;
}
?>