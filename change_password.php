<?php
require 'settings.php';

$user_id = getUserIdFromToken();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$old_pass = $data['old_password']?? '';
$new_pass = $data['new_password']?? '';

if (empty($old_pass) || empty($new_pass)) {
    echo json_encode(["success" => false, "message" => "Passwords required"]);
    exit;
}

if (strlen($new_pass) < 6) {
    echo json_encode(["success" => false, "message" => "Password must be at least 6 characters"]);
    exit;
}

try {
    // Get current password hash
    $stmt = $conn->prepare("SELECT password FROM users WHERE id =?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!password_verify($old_pass, $user['password'])) {
        echo json_encode(["success" => false, "message" => "Old password is incorrect"]);
        exit;
    }

    $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $stmt->execute([$new_hash, $user_id]);

    echo json_encode(["success" => true, "message" => "Password changed successfully"]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error"]);
}
?>
