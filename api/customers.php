<?php
// ============================================================
//  العملاء - customers.php
//  حقوق الملكية: شركة طود Tawd
// ============================================================

require_once 'config.php';
$userId = authenticate();

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'list') {
    $stmt = $pdo->prepare("SELECT * FROM customer_records WHERE business_id = ? ORDER BY last_order_at DESC");
    $stmt->execute([$userId]);
    sendJSON($stmt->fetchAll());
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'search') {
    $query = $_GET['q'] ?? '';
    if (strlen($query) < 2) sendJSON([]);
    $stmt = $pdo->prepare("SELECT * FROM customer_records 
                           WHERE business_id = ? AND (customer_name LIKE ? OR phone LIKE ?) 
                           ORDER BY last_order_at DESC LIMIT 10");
    $search = '%' . $query . '%';
    $stmt->execute([$userId, $search, $search]);
    sendJSON($stmt->fetchAll());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = generateUUID();
    
    // التحقق من وجود العميل
    $stmt = $pdo->prepare("SELECT id FROM customer_records WHERE business_id = ? AND phone = ?");
    $stmt->execute([$userId, $input['phone']]);
    if ($stmt->fetch()) {
        sendJSON(['error' => 'العميل موجود بالفعل'], 409);
    }
    
    $stmt = $pdo->prepare("INSERT INTO customer_records (id, business_id, customer_name, phone, address, last_order_at) 
                           VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$id, $userId, $input['customerName'], $input['phone'], $input['address'] ?? '']);
    sendJSON(['success' => true]);
}

if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $action === 'update') {
    $input = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("UPDATE customer_records SET customer_name = ?, phone = ?, address = ? 
                           WHERE id = ? AND business_id = ?");
    $stmt->execute([$input['customerName'], $input['phone'], $input['address'] ?? '', $input['id'], $userId]);
    sendJSON(['success' => true]);
}
?>