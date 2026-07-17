<?php
// ============================================================
//  المنتجات والمخزون والموردين - business.php
//  حقوق الملكية: شركة طود Tawd
// ============================================================

require_once 'config.php';
$userId = authenticate();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// --- المنتجات ---
if ($method === 'GET' && $action === 'products') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE business_id = ? ORDER BY name");
    $stmt->execute([$userId]);
    sendJSON($stmt->fetchAll());
}

if ($method === 'POST' && $action === 'product') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = generateUUID();
    $stmt = $pdo->prepare("INSERT INTO products (id, business_id, name, price, cost, stock_qty, min_stock_alert, barcode, category_id, image_url, recipe_id, is_active) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $id, $userId,
        $input['name'], $input['price'],
        $input['cost'] ?? 0,
        $input['stockQty'] ?? 0,
        $input['minStockAlert'] ?? 0,
        $input['barcode'] ?? null,
        $input['categoryId'] ?? null,
        $input['imageUrl'] ?? null,
        $input['recipeId'] ?? null,
        $input['isActive'] ?? 1
    ]);
    sendJSON(['success' => true, 'id' => $id]);
}

if ($method === 'PUT' && $action === 'product') {
    $input = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("UPDATE products SET 
                           name = ?, price = ?, cost = ?, stock_qty = ?, min_stock_alert = ?, 
                           barcode = ?, category_id = ?, image_url = ?, recipe_id = ?, is_active = ? 
                           WHERE id = ? AND business_id = ?");
    $stmt->execute([
        $input['name'], $input['price'], $input['cost'],
        $input['stockQty'], $input['minStockAlert'],
        $input['barcode'], $input['categoryId'], $input['imageUrl'],
        $input['recipeId'], $input['isActive'],
        $input['id'], $userId
    ]);
    sendJSON(['success' => true]);
}

if ($method === 'DELETE' && $action === 'product') {
    $id = $_GET['id'] ?? '';
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ? AND business_id = ?");
    $stmt->execute([$id, $userId]);
    sendJSON(['success' => true]);
}

// --- المكونات ---
if ($method === 'GET' && $action === 'ingredients') {
    $stmt = $pdo->prepare("SELECT * FROM ingredients WHERE business_id = ? ORDER BY name");
    $stmt->execute([$userId]);
    sendJSON($stmt->fetchAll());
}

if ($method === 'POST' && $action === 'ingredient') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = generateUUID();
    $stmt = $pdo->prepare("INSERT INTO ingredients (id, business_id, name, unit, stock_qty, min_stock_alert, cost_per_unit, supplier_id, expiry_date) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $id, $userId,
        $input['name'], $input['unit'],
        $input['stockQty'] ?? 0,
        $input['minStockAlert'] ?? 0,
        $input['costPerUnit'] ?? 0,
        $input['supplierId'] ?? null,
        $input['expiryDate'] ?? null
    ]);
    sendJSON(['success' => true]);
}

if ($method === 'DELETE' && $action === 'ingredient') {
    $id = $_GET['id'] ?? '';
    $stmt = $pdo->prepare("DELETE FROM ingredients WHERE id = ? AND business_id = ?");
    $stmt->execute([$id, $userId]);
    sendJSON(['success' => true]);
}

// --- الفئات ---
if ($method === 'GET' && $action === 'categories') {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE business_id = ? ORDER BY name");
    $stmt->execute([$userId]);
    sendJSON($stmt->fetchAll());
}

// --- الموردين ---
if ($method === 'GET' && $action === 'suppliers') {
    $stmt = $pdo->prepare("SELECT * FROM suppliers WHERE business_id = ? ORDER BY name");
    $stmt->execute([$userId]);
    sendJSON($stmt->fetchAll());
}

if ($method === 'POST' && $action === 'supplier') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = generateUUID();
    $stmt = $pdo->prepare("INSERT INTO suppliers (id, business_id, name, phone, notes, balance) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id, $userId, $input['name'], $input['phone'] ?? '', $input['notes'] ?? '', $input['balance'] ?? 0]);
    sendJSON(['success' => true]);
}

if ($method === 'DELETE' && $action === 'supplier') {
    $id = $_GET['id'] ?? '';
    $stmt = $pdo->prepare("DELETE FROM suppliers WHERE id = ? AND business_id = ?");
    $stmt->execute([$id, $userId]);
    sendJSON(['success' => true]);
}

// --- الطاولات ---
if ($method === 'GET' && $action === 'tables') {
    $stmt = $pdo->prepare("SELECT * FROM tables WHERE business_id = ? ORDER BY name");
    $stmt->execute([$userId]);
    sendJSON($stmt->fetchAll());
}

// --- الريسبي ---
if ($method === 'GET' && $action === 'recipes') {
    $stmt = $pdo->prepare("SELECT * FROM recipes WHERE business_id = ?");
    $stmt->execute([$userId]);
    sendJSON($stmt->fetchAll());
}

// --- التقارير الأساسية ---
if ($method === 'GET' && $action === 'dashboard') {
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
    
    sendJSON($stats);
}
?>