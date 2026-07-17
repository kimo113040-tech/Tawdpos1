<?php
// ============================================================
//  المصادقة - auth.php
//  حقوق الملكية: شركة طود Tawd
// ============================================================

require_once 'config.php';

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // ======== تسجيل الدخول ========
    if ($action === 'login') {
        $identifier = $input['identifier'] ?? '';
        $password = $input['password'] ?? '';
        
        if (empty($identifier) || empty($password)) {
            sendJSON(['error' => 'يجب إدخال البريد/الهاتف وكلمة المرور'], 400);
        }
        
        // البحث عن المستخدم
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? OR email = ? OR name = ? OR work_number = ?");
        $stmt->execute([$identifier, $identifier, $identifier, $identifier]);
        $user = $stmt->fetch();
        
        if (!$user) {
            sendJSON(['error' => 'المستخدم غير موجود'], 404);
        }
        
        // التحقق من كلمة المرور
        if (!verifyPassword($password, $user['password_hash'])) {
            sendJSON(['error' => 'كلمة المرور غير صحيحة'], 401);
        }
        
        // التحقق من حالة الحساب
        if ($user['status'] === 'pending') {
            sendJSON(['error' => 'الحساب في انتظار التفعيل'], 403);
        }
        if ($user['status'] === 'suspended') {
            sendJSON(['error' => 'الحساب موقوف'], 403);
        }
        
        // حفظ الجلسة
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['name'] = $user['name'];
        session_regenerate_id(true); // تجديد معرف الجلسة للأمان
        
        // إزالة المعلومات الحساسة
        $safeUser = [
            'id' => $user['id'],
            'name' => $user['name'],
            'role' => $user['role'],
            'phone' => $user['phone'],
            'status' => $user['status'],
            'theme_pref' => $user['theme_pref'],
            'work_number' => $user['work_number']
        ];
        
        sendJSON([
            'success' => true,
            'user' => $safeUser,
            'message' => 'تم تسجيل الدخول بنجاح'
        ]);
    }
    
    // ======== التسجيل ========
    elseif ($action === 'register') {
        $role = $input['role'] ?? 'customer';
        $name = trim($input['name'] ?? '');
        $phone = trim($input['phone'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $businessType = $input['businessType'] ?? null;
        
        if (empty($name) || empty($phone) || empty($password)) {
            sendJSON(['error' => 'الاسم والهاتف وكلمة المرور مطلوبة'], 400);
        }
        
        if (!preg_match('/^01[0-2,5]{1}[0-9]{8}$/', $phone)) {
            sendJSON(['error' => 'رقم الهاتف غير صحيح'], 400);
        }
        
        // التحقق من عدم تكرار الهاتف
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->fetch()) {
            sendJSON(['error' => 'رقم الهاتف مستخدم بالفعل'], 409);
        }
        
        if (!empty($email)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                sendJSON(['error' => 'البريد الإلكتروني مستخدم بالفعل'], 409);
            }
        }
        
        $id = generateUUID();
        $hashed = hashPassword($password);
        $workNumber = ($role === 'business') ? 'BIZ-' . rand(1000, 9999) : (($role === 'driver') ? 'DRV-' . rand(1000, 9999) : '');
        $status = ($role === 'customer') ? 'active' : 'pending';
        
        $stmt = $pdo->prepare("INSERT INTO users (id, role, business_type, name, phone, email, password_hash, status, work_number, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$id, $role, $businessType, $name, $phone, $email ?: null, $hashed, $status, $workNumber]);
        
        if ($role === 'business') {
            $stmt = $pdo->prepare("INSERT INTO businesses (user_id, work_number) VALUES (?, ?)");
            $stmt->execute([$id, $workNumber]);
        }
        
        if ($role === 'customer') {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $id;
            $_SESSION['role'] = $role;
            $_SESSION['name'] = $name;
            
            sendJSON([
                'success' => true,
                'user' => [
                    'id' => $id,
                    'role' => $role,
                    'name' => $name,
                    'phone' => $phone,
                    'status' => $status
                ],
                'message' => 'تم التسجيل بنجاح'
            ]);
        } else {
            sendJSON([
                'success' => true,
                'message' => 'تم التسجيل، بانتظار التفعيل من المصمم'
            ]);
        }
    }
    
    // ======== تسجيل الخروج ========
    elseif ($action === 'logout') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = array();
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
        }
        session_destroy();
        sendJSON(['success' => true, 'message' => 'تم تسجيل الخروج']);
    }
    
    // ======== إعادة تعيين كلمة المرور ========
    elseif ($action === 'resetPassword') {
        $phone = $input['phone'] ?? '';
        
        if (empty($phone)) {
            sendJSON(['error' => 'رقم الهاتف مطلوب'], 400);
        }
        
        $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $user = $stmt->fetch();
        
        if (!$user) {
            sendJSON(['error' => 'لا يوجد حساب بهذا الرقم'], 404);
        }
        
        // إنشاء كلمة مرور جديدة عشوائية
        $newPassword = 'Tawd' . rand(1000, 9999);
        $hashed = hashPassword($newPassword);
        
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE phone = ?");
        $stmt->execute([$hashed, $phone]);
        
        sendJSON([
            'success' => true,
            'message' => 'تم إعادة تعيين كلمة المرور',
            'newPassword' => $newPassword
        ]);
    }
} else {
    sendJSON(['error' => 'الطريقة غير مسموحة. استخدم POST'], 405);
}
?>