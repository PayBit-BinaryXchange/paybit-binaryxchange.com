<?php
session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$dsn = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');

if (!$dsn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database URL not configured']);
    exit();
}

try {
    $pdo = new PDO($dsn, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit();
}

$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$captcha = trim($data['captcha'] ?? '');

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit();
}

// Captcha validation with 2 minute expiry
if (empty($captcha) || !isset($_SESSION['captcha'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Captcha is required']);
    exit();
}

// Check if captcha expired - 120 seconds
if (isset($_SESSION['captcha_time']) && (time() - $_SESSION['captcha_time'] > 120)) {
    unset($_SESSION['captcha'], $_SESSION['captcha_time']);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Captcha expired. Refresh and try again.']);
    exit();
}

if (strcasecmp($captcha, $_SESSION['captcha']) !== 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid captcha']);
    unset($_SESSION['captcha'], $_SESSION['captcha_time']); // clear it so it can't be reused
    exit();
}

// One-time use
unset($_SESSION['captcha'], $_SESSION['captcha_time']);

try {
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

    $token = bin2hex(random_bytes(32));
    unset($user['password_hash']);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'token' => $token,
        'user' => $user
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
    exit();
}
?>
