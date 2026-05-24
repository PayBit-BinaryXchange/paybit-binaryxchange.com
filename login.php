<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$dbUrl = getenv("DATABASE_URL");
if (!$dbUrl) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database URL not configured']);
    exit();
}

try {
    $db = parse_url($dbUrl);
    $pdo = new PDO(
        "pgsql:host=".$db["host"].";port=".($db["port"] ?? 5432).";dbname=".ltrim($db["path"],"/"),
        $db["user"] ?? '',
        $db["pass"] ?? '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
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
$captcha_id = trim($data['captcha_id'] ?? '');

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email and password are required']);
    exit();
}

// Captcha validation against DB
if (empty($captcha) || empty($captcha_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Captcha is required']);
    exit();
}

$stmt = $pdo->prepare("SELECT code FROM captcha_codes WHERE id = ? AND expires_at > NOW() LIMIT 1");
$stmt->execute([$captcha_id]);
$row = $stmt->fetch();

if (!$row || strcasecmp($captcha, $row['code']) !== 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired captcha']);
    exit();
}

// Delete captcha so it can't be reused
$pdo->prepare("DELETE FROM captcha_codes WHERE id = ?")->execute([$captcha_id]);

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

    // Generate token
    $token = bin2hex(random_bytes(32));
    
    // Create tokens table if it doesn't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS tokens (
        id SERIAL PRIMARY KEY,
        user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
        token VARCHAR(64) UNIQUE NOT NULL,
        created_at TIMESTAMP DEFAULT NOW()
    )");
    
    $stmt = $pdo->prepare("INSERT INTO tokens (user_id, token) VALUES (?, ?) 
                           ON CONFLICT (token) DO NOTHING");
    $stmt->execute([$user['id'], $token]);
    
    // Clean old tokens older than 7 days
    $pdo->exec("DELETE FROM tokens WHERE created_at < NOW() - INTERVAL '7 days'");

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
