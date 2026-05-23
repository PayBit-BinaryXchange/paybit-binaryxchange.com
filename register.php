<?php
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

// --- DB connection for Render Postgres ---
$dbUrl = getenv("DATABASE_URL");
if (!$dbUrl) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DATABASE_URL not configured']);
    exit();
}

$db = parse_url($dbUrl);
if (!isset($db['host'], $db['port'], $db['path'], $db['user'], $db['pass'])) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Invalid DATABASE_URL format']);
    exit();
}

try {
    $pdo = new PDO(
        "pgsql:host=".$db["host"].";port=".$db["port"].";dbname=".ltrim($db["path"],"/"),
        $db["user"],
        $db["pass"],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// --- Get JSON body ---
$data = json_decode(file_get_contents("php://input"), true);

if (json_last_error() !== JSON_ERROR_NONE || !$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit();
}

// --- Required fields ---
$required = ['first_name', 'last_name', 'username', 'email', 'password', 'country', 'currency', 'captcha'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "$field is required"]);
        exit();
    }
}

// --- Sanitize ---
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
$captcha    = trim($data['captcha']);

// --- Validate email ---
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// --- Validate password length ---
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit();
}

// --- Validate captcha ---
$stmt = $pdo->prepare("SELECT id FROM captchas WHERE code = ? AND created_at > NOW() - INTERVAL '10 minutes'");
$stmt->execute([$captcha]);
if ($stmt->rowCount() === 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid or expired captcha']);
    exit();
}

// --- Check duplicates ---
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
$stmt->execute([$email, $username]);
if ($stmt->rowCount() > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Email or username already exists']);
    exit();
}

// --- Insert user ---
$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

$sql = "INSERT INTO users 
    (first_name, last_name, username, email, number, password_hash, country, currency, account_types, referral) 
    VALUES 
    (:first_name, :last_name, :username, :email, :number, :password_hash, :country, :currency, :account_types, :referral)
    RETURNING id";

$stmt = $pdo->prepare($sql);

try {
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

    $row = $stmt->fetch();
    $user_id = $row['id'];

    // Delete used captcha
    $pdo->prepare("DELETE FROM captchas WHERE code = ?")->execute([$captcha]);

    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful!',
        'user_id' => $user_id
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Registration failed. Try again later.']);
}
