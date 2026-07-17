<?php
// ============================================================
//  إعدادات قاعدة البيانات - config.php
//  حقوق الملكية: شركة طود Tawd
// ============================================================

// ======== إعدادات الجلسة ========
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.gc_maxlifetime', 86400); // 24 ساعة

// بدء الجلسة (إذا لم تكن قد بدأت)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ======== رؤوس CORS ========
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// التعامل مع طلبات OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ======== إعدادات قاعدة البيانات (بيانات InfinityFree) ========
$host = 'sql213.infinityfree.com';
$dbname = 'if0_42417440_tawd_db';
$username = 'if0_42417440';
$password = '2026Tawd2026';

// ======== الاتصال بقاعدة البيانات ========
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'فشل الاتصال بقاعدة البيانات: ' . $e->getMessage()]);
    exit;
}

// ======== دوال مساعدة ========

function generateUUID() {
    return sprintf('%s-%s-%s-%s-%s',
        bin2hex(random_bytes(4)),
        bin2hex(random_bytes(2)),
        bin2hex(random_bytes(2)),
        bin2hex(random_bytes(2)),
        bin2hex(random_bytes(6))
    );
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function sendJSON($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function authenticate() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // التحقق من الجلسة
    if (!isset($_SESSION['user_id'])) {
        sendJSON(['error' => 'غير مصرح - يرجى تسجيل الدخول'], 401);
    }
    return $_SESSION['user_id'];
}

function isOwner() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['role']) && $_SESSION['role'] === 'owner';
}
?>