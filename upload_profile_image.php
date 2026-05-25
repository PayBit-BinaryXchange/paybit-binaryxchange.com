<?php
require 'settings.php';

$user_id = getUserIdFromToken();
if (!$user_id) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

if (!isset($_FILES['profile'])) {
    echo json_encode(["success" => false, "message" => "No file uploaded"]);
    exit;
}

$file = $_FILES['profile'];
$allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
$max_size = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(["success" => false, "message" => "Only JPG and PNG allowed"]);
    exit;
}

if ($file['size'] > $max_size) {
    echo json_encode(["success" => false, "message" => "File too large. Max 5MB"]);
    exit;
}

$uploadDir = __DIR__. '/uploads/profile/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
$fileName = 'profile_'. $user_id. '_'. time(). '.'. $ext;
$filePath = $uploadDir. $fileName;

if (move_uploaded_file($file['tmp_name'], $filePath)) {
    $fileUrl = "https://paybit-binaryxchange-com.onrender.com/uploads/profile/". $fileName;

    try {
        $stmt = $conn->prepare("UPDATE users SET profilePic=? WHERE id=?");
        $stmt->execute([$fileUrl, $user_id]);

        echo json_encode(["success" => true, "message" => "Profile image updated", "url" => $fileUrl]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database error"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Upload failed"]);
}

