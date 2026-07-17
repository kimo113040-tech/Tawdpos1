<?php
// ============================================================
//  الشيفتات - shifts.php
//  حقوق الملكية: شركة طود Tawd
// ============================================================

require_once 'config.php';
$userId = authenticate();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && $action === 'list') {
    $stmt = $pdo->prepare("SELECT * FROM shifts WHERE business_id = ? ORDER BY opened_at DESC");
    $stmt->execute([$userId]);
    sendJSON($stmt->fetchAll());
}

if ($method === 'POST' && $action === 'open') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = generateUUID();
    $stmt = $pdo->prepare("INSERT INTO shifts (id, business_id, staff_id, cashier_name, opening_cash, opened_at) 
                           VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$id, $userId, $userId, $input['cashierName'] ?? null, $input['openingCash'] ?? 0]);
    sendJSON(['success' => true, 'shiftId' => $id]);
}

if ($method === 'POST' && $action === 'close') {
    $input = json_decode(file_get_contents('php://input'), true);
    $shiftId = $input['shiftId'] ?? '';
    $closingCash = $input['closingCash'] ?? 0;
    
    // حساب المبيعات والطلبات
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_orders, SUM(total) as total_sales FROM orders WHERE shift_id = ?");
    $stmt->execute([$shiftId]);
    $stats = $stmt->fetch();
    
    $stmt = $pdo->prepare("UPDATE shifts SET 
                           closed_at = NOW(), 
                           closing_cash = ?, 
                           total_sales = ?, 
                           total_orders = ?, 
                           waste_percent = ?, 
                           waste_details = ?, 
                           production_report = ? 
                           WHERE id = ? AND business_id = ?");
    $stmt->execute([
        $closingCash,
        $stats['total_sales'] ?? 0,
        $stats['total_orders'] ?? 0,
        $input['wastePercent'] ?? 0,
        isset($input['wasteDetails']) ? json_encode($input['wasteDetails']) : null,
        isset($input['productionReport']) ? json_encode($input['productionReport']) : null,
        $shiftId,
        $userId
    ]);
    sendJSON(['success' => true]);
}
?>