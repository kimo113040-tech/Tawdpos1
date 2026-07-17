<?php
// ============================================================
//  الطلبات - orders.php
//  حقوق الملكية: شركة طود Tawd
// ============================================================

require_once 'config.php';
$userId = authenticate();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && $action === 'list') {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE business_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    sendJSON($stmt->fetchAll());
}

if ($method === 'GET' && $action === 'tracking') {
    $orderId = $_GET['orderId'] ?? '';
    $stmt = $pdo->prepare("SELECT tracking FROM orders WHERE id = ? AND business_id = ?");
    $stmt->execute([$orderId, $userId]);
    $row = $stmt->fetch();
    sendJSON($row ? json_decode($row['tracking'], true) : []);
}

if ($method === 'POST' && $action === 'create') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = generateUUID();
    $tracking = json_encode([['status' => 'pending', 'timestamp' => date('Y-m-d H:i:s'), 'note' => 'تم استلام الطلب']]);
    
    $stmt = $pdo->prepare("INSERT INTO orders (id, business_id, customer_id, customer_name, customer_phone, type, table_id, 
                           items, subtotal, discount, delivery_fee, tax, total, payment_method, status, 
                           driver_id, shift_id, customer_location, tracking, created_at, updated_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->execute([
        $id, $userId,
        $input['customerId'] ?? null,
        $input['customerName'] ?? null,
        $input['customerPhone'] ?? null,
        $input['type'],
        $input['tableId'] ?? null,
        json_encode($input['items']),
        $input['subtotal'],
        $input['discount'] ?? 0,
        $input['deliveryFee'] ?? 0,
        $input['tax'] ?? 0,
        $input['total'],
        $input['paymentMethod'] ?? 'cash',
        'pending',
        $input['driverId'] ?? null,
        $input['shiftId'] ?? null,
        isset($input['customerLocation']) ? json_encode($input['customerLocation']) : null,
        $tracking
    ]);
    sendJSON(['success' => true, 'id' => $id]);
}

if ($method === 'POST' && $action === 'updateStatus') {
    $input = json_decode(file_get_contents('php://input'), true);
    $orderId = $input['orderId'] ?? '';
    $newStatus = $input['status'] ?? '';
    $note = $input['note'] ?? '';
    
    // جلب التتبع الحالي
    $stmt = $pdo->prepare("SELECT tracking FROM orders WHERE id = ? AND business_id = ?");
    $stmt->execute([$orderId, $userId]);
    $row = $stmt->fetch();
    $tracking = $row ? json_decode($row['tracking'], true) : [];
    $tracking[] = ['status' => $newStatus, 'timestamp' => date('Y-m-d H:i:s'), 'note' => $note];
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, tracking = ?, updated_at = NOW() WHERE id = ? AND business_id = ?");
    $stmt->execute([$newStatus, json_encode($tracking), $orderId, $userId]);
    sendJSON(['success' => true]);
}
?>