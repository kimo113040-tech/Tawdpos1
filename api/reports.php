<?php
// ============================================================
//  التقارير و KPI - reports.php
//  حقوق الملكية: شركة طود Tawd
// ============================================================

require_once 'config.php';
$userId = authenticate();

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // لوحة التحكم الرئيسية
    if ($action === 'dashboard') {
        $stats = [];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_orders, SUM(total) as total_revenue FROM orders WHERE business_id = ?");
        $stmt->execute([$userId]);
        $stats['orders'] = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_products FROM products WHERE business_id = ?");
        $stmt->execute([$userId]);
        $stats['products'] = $stmt->fetch()['total_products'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_customers FROM customer_records WHERE business_id = ?");
        $stmt->execute([$userId]);
        $stats['customers'] = $stmt->fetch()['total_customers'];
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as total_suppliers FROM suppliers WHERE business_id = ?");
        $stmt->execute([$userId]);
        $stats['suppliers'] = $stmt->fetch()['total_suppliers'];
        
        sendJSON($stats);
    }
    
    // مؤشرات الأداء KPI
    if ($action === 'kpi') {
        $kpi = [];
        
        // إحصائيات اليوم
        $stmt = $pdo->prepare("SELECT COUNT(*) as count, SUM(total) as total FROM orders 
                               WHERE business_id = ? AND DATE(created_at) = CURDATE()");
        $stmt->execute([$userId]);
        $kpi['today'] = $stmt->fetch();
        
        // توزيع الحالات
        $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM orders 
                               WHERE business_id = ? GROUP BY status");
        $stmt->execute([$userId]);
        $kpi['statuses'] = $stmt->fetchAll();
        
        // توزيع أنواع الطلبات
        $stmt = $pdo->prepare("SELECT type, COUNT(*) as count FROM orders 
                               WHERE business_id = ? GROUP BY type");
        $stmt->execute([$userId]);
        $kpi['types'] = $stmt->fetchAll();
        
        sendJSON($kpi);
    }
}
?>