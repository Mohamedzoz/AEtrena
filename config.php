<?php
date_default_timezone_set('Africa/Cairo');
// ─── إعدادات البيئة ───────────────────────────
$host_name = $_SERVER['HTTP_HOST'] ?? 'localhost';
$is_local = (strpos($host_name, 'localhost') !== false || strpos($host_name, '127.0.0.1') !== false || strpos($host_name, '192.168.') === 0 || strpos($host_name, '::1') !== false);

$configs = [
    'local' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'dbname'   => 'aeterna_erp',
        'username' => 'root',
        'password' => '',
    ],
    'production' => [
        'host'     => 'sql113.infinityfree.com',
        'port'     => 3306,
        'dbname'   => 'if0_41872581_aeterna_erp',
        'username' => 'if0_41872581',
        'password' => 'Mrcooltop94',
    ]
];

$db_config = $is_local ? $configs['local'] : $configs['production'];

// ─── محاولة الاتصال ─────────────────────────────
try {
    $dsn = "mysql:host={$db_config['host']};port={$db_config['port']};dbname={$db_config['dbname']};charset=utf8mb4";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_PERSISTENT         => true,
    ];

    $pdo = new PDO($dsn, $db_config['username'], $db_config['password'], $options);

    // ─── مزامنة الوقت بدقة مع القاهرة (PHP & MySQL) ───
    $now = new DateTime();
    $mins = $now->getOffset() / 60;
    $sgn = ($mins < 0 ? -1 : 1);
    $mins = abs($mins);
    $hrs = floor($mins / 60);
    $mins -= $hrs * 60;
    $offset = sprintf('%+03d:%02d', $hrs*$sgn, $mins);
    $pdo->exec("SET time_zone='$offset';");

} catch (PDOException $e) {
    // إظهار رسالة خطأ بسيطة وجميلة
    $error_msg = $is_local ? $e->getMessage() : "تعذر الاتصال بقاعدة البيانات حالياً، يرجى المحاولة لاحقاً أو مراجعة بيانات السيرفر الخارجي.";
    
    die("
    <div style='
        display: flex; 
        justify-content: center; 
        align-items: center; 
        height: 100vh; 
        background-color: #f8fafc; 
        font-family: \"Segoe UI\", Tahoma, Geneva, Verdana, sans-serif;
        direction: rtl;
    '>
        <div style='
            background: #ffffff;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            max-width: 450px;
            width: 90%;
            border-top: 5px solid #e74c3c;
            text-align: center;
        '>
            <div style='font-size: 50px; margin-bottom: 15px;'>⚠️</div>
            <h3 style='color: #2c3e50; margin-bottom: 10px;'>خطأ في النظام</h3>
            <p style='color: #7f8c8d; line-height: 1.6; margin-bottom: 0;'>
                " . htmlspecialchars($error_msg) . "
            </p>
            
            " . ($is_local ? "
            <div style='margin-top: 20px; background: #fdf2f2; padding: 12px; border-radius: 8px; text-align: right; font-size: 13px; color: #c0392b;'>
                <strong>Debug Info (Local):</strong><br>
                Host: {$db_config['host']}<br>
                DB: {$db_config['dbname']}
            </div>
            " : "") . "
        </div>
    </div>
    ");
}

// Start session securely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit;
    }

    // ── Server-side Auto-Logout ──────────────────────
    global $pdo;
    $timeout_minutes = (int)(getSetting('session_timeout', 30));
    $timeout_seconds = $timeout_minutes * 60;

    if (isset($_SESSION['last_activity'])) {
        $idle = time() - $_SESSION['last_activity'];
        if ($idle > $timeout_seconds) {
            // Log the auto-logout
            try {
                $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $_SESSION['user_id'],
                    'تسجيل خروج تلقائي',
                    "تم تسجيل الخروج تلقائياً بعد {$timeout_minutes} دقيقة من عدم النشاط.",
                    $_SERVER['REMOTE_ADDR'] ?? 'Unknown'
                ]);
            } catch(Exception $e) {}
            session_unset();
            session_destroy();
            header("Location: index.php?reason=timeout");
            exit;
        }
    }
    // Refresh last activity timestamp
    $_SESSION['last_activity'] = time();
}

// ─── نظام الصلاحيات الديناميكي (Dynamic RBAC) ─────────────────

/**
 * دالة التحقق من الصلاحية (Capability Check)
 * تقوم بالتحقق من قاعدة البيانات إذا كان للمستخدم صلاحية معينة
 */
$user_permissions_cache = null;

function can($capability) {
    global $pdo, $user_permissions_cache;

    if (!isset($_SESSION['user_id'])) return false;
    
    // Admin always has access
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin') return true;

    // Fetch permissions if not already cached for this request
    if ($user_permissions_cache === null) {
        $user_permissions_cache = [];
        try {
            // Get permissions based on the user's role_id
            $stmt = $pdo->prepare("
                SELECT p.slug 
                FROM permissions p
                JOIN role_permissions rp ON p.id = rp.permission_id
                JOIN users u ON u.role_id = rp.role_id
                WHERE u.id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $user_permissions_cache = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return false;
        }
    }

    // Special case for global access
    if (in_array('*', $user_permissions_cache)) return true;
    
    return in_array($capability, $user_permissions_cache);
}

// Returns true if the current user is Admin (super-admin with all permissions)
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Admin';
}

// Returns true if current user is Admin OR Manager (management-level access)
function isManagerOrAdmin() {
    return isAdmin() || (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'Manager');
}

// Returns true if current user has one of the given roles OR is Admin
function hasRole(array $roles) {
    if (!isset($_SESSION['user_role'])) return false;
    if ($_SESSION['user_role'] === 'Admin') return true;
    return in_array($_SESSION['user_role'], $roles);
}

// System Logging Function
function logActivity($action, $details = '') {
    global $pdo;
    $user_id = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $ip]);
    } catch (Exception $e) {
        // Fail silently
    }
}

// Move to Trash (Soft Delete Wrapper)
function moveToTrash($table_name, $original_id) {
    global $pdo;
    try {
        // Fetch the row data
        $stmt = $pdo->prepare("SELECT * FROM `$table_name` WHERE id = ?");
        $stmt->execute([$original_id]);
        $data = $stmt->fetch();
        
        if ($data) {
            $json_data = json_encode($data, JSON_UNESCAPED_UNICODE);
            $user_id = $_SESSION['user_id'] ?? null;
            
            // Insert into trash table
            $stmt = $pdo->prepare("INSERT INTO trash (table_name, original_id, data_json, deleted_by) VALUES (?, ?, ?, ?)");
            $stmt->execute([$table_name, $original_id, $json_data, $user_id]);
            
            // Log the deletion
            logActivity("حذف من $table_name", "تم حذف سجل رقم $original_id ونقله لسلة المهملات.");
        }
    } catch (Exception $e) {
        // Fail silently
    }
}

// System Settings Helper with Global Cache
$settings_cache = null;

function getSetting($key, $default = '') {
    global $pdo, $settings_cache;
    
    // Lazy load all settings once per request
    if ($settings_cache === null) {
        $settings_cache = [];
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
            while ($row = $stmt->fetch()) {
                $settings_cache[$row['setting_key']] = $row['setting_value'];
            }
        } catch(Exception $e) {
            // If table doesn't exist yet, just keep empty array
        }
    }
    
    return isset($settings_cache[$key]) && $settings_cache[$key] !== '' ? $settings_cache[$key] : $default;
}

/**
 * Global Date Formatter (DD-MM-YYYY)
 */
function formatDate($date) {
    if (!$date || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') return '-';
    return date('d-m-Y', strtotime($date));
}

/**
 * Global Date & Time Formatter
 */
function formatDateTime($date) {
    if (!$date || $date == '0000-00-00 00:00:00') return '-';
    return date('d-m-Y | h:i A', strtotime($date));
}

/**
 * Global Date Parser (Converts DD-MM-YYYY to YYYY-MM-DD)
 */
function parseDate($dateStr) {
    if (!$dateStr) return null;
    // Check if it's already YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) return $dateStr;
    // Try to parse DD-MM-YYYY
    $d = DateTime::createFromFormat('d-m-Y', $dateStr);
    return $d ? $d->format('Y-m-d') : date('Y-m-d', strtotime($dateStr));
}

/**
 * Returns the asset path with a cache-busting version string
 */
function asset($path) {
    if (!$path) return '';
    // Use filemtime if file exists, otherwise use a default version
    $version = '1.0';
    $real_path = __DIR__ . '/' . $path;
    if (file_exists($real_path)) {
        $version = filemtime($real_path);
    }
    return $path . '?v=' . $version;
}

/**
 * Sanitizes a string to be used as a directory name
 */
function sanitizeDirName($name) {
    if (!$name) return 'General';
    // Remove illegal characters for folder names
    $name = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $name);
    return trim($name);
}

/**
 * Generates and ensures the existence of a customer/project specific upload directory
 */
function getUploadPath($customer_id, $project_id = null) {
    global $pdo;
    
    // Fetch Customer Name
    $stmt = $pdo->prepare("SELECT name FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer_name = $stmt->fetchColumn() ?: 'Unknown_Customer';
    $customer_dir = sanitizeDirName($customer_name);
    
    $project_dir = 'General';
    if ($project_id) {
        // Fetch Project Name
        $stmt = $pdo->prepare("SELECT title FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);
        $project_title = $stmt->fetchColumn();
        if ($project_title) {
            $project_dir = sanitizeDirName($project_title);
        }
    }
    
    $path = "uploads/$customer_dir/$project_dir/";
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }
    
    return $path;
}

// Notifications System
require_once 'notifications_lib.php';
?>
