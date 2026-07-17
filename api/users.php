<?php
// ============================================================
//  إدارة المستخدمين - users.php
//  حقوق الملكية: شركة طود Tawd
// ============================================================

require_once 'config.php';

// التحقق من المصادقة
$userId = authenticate();

// التحقق من أن المستخدم هو مصمم
if (!isOwner()) {
    sendJSON(['error' => 'غير مصرح - صلاحية المصمم مطلوبة'], 403);
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // طلبات التفعيل
    if ($action === 'pending') {
        $stmt = $pdo->prepare("SELECT id, name, phone, email, role, work_number, created_at 
                               FROM users WHERE status = 'pending' AND role IN ('business', 'driver') 
                               ORDER BY created_at DESC");
        $stmt->execute();
        sendJSON($stmt->fetchAll());
    }
    
    // قائمة المحلات
    elseif ($action === 'businesses') {
        $stmt = $pdo->prepare("SELECT id, name, phone, email, status, work_number, created_at 
                               FROM users WHERE role = 'business' ORDER BY created_at DESC");
        $stmt->execute();
        sendJSON($stmt->fetchAll());
    }
    
    // قائمة السائقين
    elseif ($action === 'drivers') {
        $stmt = $pdo->prepare("SELECT id, name, phone, email, status, work_number, created_at 
                               FROM users WHERE role = 'driver' ORDER BY created_at DESC");
        $stmt->execute();
        sendJSON($stmt->fetchAll());
    }
}

elseif ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $targetId = $input['id'] ?? '';
    $newStatus = $input['status'] ?? '';
    
    if ($action === 'activate' || $action === 'suspend') {
        if (empty($targetId) || empty($newStatus)) {
            sendJSON(['error' => 'معرف المستخدم والحالة مطلوبة'], 400);
        }
        
        if (!in_array($newStatus, ['active', 'suspended'])) {
            sendJSON(['error' => 'الحالة غير صالحة'], 400);
        }
        
        $stmt = $pdo->prepare("UPDATE users SET status = ?, activated_by = ? WHERE id = ? AND role IN ('business', 'driver')");
        $stmt->execute([$newStatus, $userId, $targetId]);
        sendJSON(['success' => true]);
    }
}

elseif ($method === 'DELETE') {
    $targetId = $_GET['id'] ?? '';
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role IN ('business', 'driver')");
    $stmt->execute([$targetId]);
    sendJSON(['success' => true]);
}
?>