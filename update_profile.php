<?php
require 'settings.php';

$user_id = getUserIdFromToken();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$first = trim($data['first']?? '');
$last = trim($data['last']?? '');
$email = trim($data['email']?? '');
$state = trim($data['state']?? 'None');
$address = trim($data['address']?? 'None');

if (empty($first) || empty($last) || empty($email)) {
    echo json_encode(["success" => false, "message" => "Required fields missing"]);
    exit;
}

// Check email not taken by another user
$stmt = $conn->prepare("SELECT id FROM users WHERE email =? AND id!=?");
$stmt->execute([$email, $user_id]);
if ($stmt->rowCount() > 0) {
    echo json_encode(["success" => false, "message" => "Email already in use"]);
    exit;
}

try {
    $stmt = $conn->prepare("UPDATE users SET firstname=?, lastname=?, email=?, state=?, address=? WHERE id=?");
    $stmt->execute([$first, $last, $email, $state, $address, $user_id]);

    echo json_encode(["success" => true, "message" => "Profile updated successfully"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error"]);
}
