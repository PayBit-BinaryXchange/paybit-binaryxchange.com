<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// DB config
$host = "localhost";
$db   = "paybit_db";
$user = "db_user";
$pass = "db_password";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get JSON
$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$captcha = $data['captcha'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit();
}

// TODO: Verify captcha properly. For now just check it's not empty
if (empty($captcha)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Captcha is required']);
    exit();
}

// Get user
$stmt = $pdo->prepare("SELECT id, first_name, last_name, username, email, number, country, currency, password_hash 
                       FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);

if ($stmt->rowCount() !== 1) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit();
}

$user = $stmt->fetch();

if (!password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
    exit();
}

// Generate simple token. In production use JWT
$token = bin2hex(random_bytes(32));

// Optionally save token to DB if you want to validate it later
// $stmt = $pdo->prepare("UPDATE users SET api_token = ? WHERE id = ?");
// $stmt->execute([$token, $user['id']]);

// Remove password before sending user data
unset($user['password_hash']);

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'token' => $token,
    'user' => $user
]);