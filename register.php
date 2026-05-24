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
    echo json_encode(['success' => false, 'message' => 'DATABASE_URL not set on server']);
    exit();
}

$db = parse_url($dbUrl);
if (!$db || !isset($db['host'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Invalid DATABASE_URL']);
    exit();
}

try {
    $pdo = new PDO(
        "pgsql:host=".$db["host"].";port=".($db["port"] ?? 5432).";dbname=".ltrim($db["path"],"/"),
        $db["user"] ?? '',
        $db["pass"] ?? '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit();
}

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON: '.json_last_error_msg()]);
    exit();
}

$required = ['first_name', 'last_name', 'username', 'email', 'password', 'country', 'currency', 'captcha', 'captcha_id'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "$field is required"]);
        exit();
    }
}

// Check captcha against DB
$captcha_id = trim($data['captcha_id']);
$captcha_input = trim($data['captcha']);

$stmt = $pdo->prepare("SELECT code FROM captcha_codes WHERE id = ? AND expires_at > NOW() LIMIT 1");
$stmt->execute([$captcha_id]);
$row = $stmt->fetch();

if (!$row || strtolower($captcha_input) !== strtolower($row['code'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired captcha']);
    exit();
}

// Delete captcha so it can't be reused
$pdo->prepare("DELETE FROM captcha_codes WHERE id = ?")->execute([$captcha_id]);

$first_name = trim($data['first_name']);
$last_name  = trim($data['last_name']);
$username   = trim($data['username']);
$email      = trim($data['email']);
$number     = trim($data['number'] ?? '');
$password   = $data['password'];
$country    = trim($data['country']);
$currency   = trim($data['currency']);
$account    = trim($data['account'] ?? '');
$referral   = trim($data['referral'] ?? 'None');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
    $stmt->execute([$email, $username]);
    if ($stmt->rowCount() > 0) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Email or username already exists']);
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $sql = "INSERT INTO users 
        (first_name, last_name, username, email, number, password_hash, country, currency, account_types, referral) 
        VALUES 
        (:first_name, :last_name, :username, :email, :number, :password_hash, :country, :currency, :account_types, :referral)
        RETURNING id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':first_name'    => $first_name,
        ':last_name'     => $last_name,
        ':username'      => $username,
        ':email'         => $email,
        ':number'        => $number,
        ':password_hash' => $hashedPassword,
        ':country'       => $country,
        ':currency'      => $currency,
        ':account_types' => $account,
        ':referral'      => $referral
    ]);

    $user_id = $stmt->fetchColumn();

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful!',
        'user_id' => $user_id
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}
