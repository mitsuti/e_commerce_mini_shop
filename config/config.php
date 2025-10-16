<?php
/**
 * File cấu hình chung cho ứng dụng E-commerce
 */

// Bắt đầu session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Cấu hình timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình site
define('SITE_NAME', 'Mini Shop');
define('SITE_URL', 'http://localhost/e_commerce_mini_shop');
define('SITE_EMAIL', 'admin@minishop.com');
define('SITE_PHONE', '0123-456-789');

// Cấu hình upload
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB (không còn dùng để chặn, giữ để tham chiếu)
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Cấu hình pagination
define('PRODUCTS_PER_PAGE', 12);
define('ORDERS_PER_PAGE', 10);

// Cấu hình security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600); // 1 hour

// Include database config
require_once __DIR__ . '/database.php';

// Hàm helper để tạo CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// Hàm helper để verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Hàm helper để sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Hàm helper để format tiền
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . ' ₫';
}

// Hàm helper để format ngày
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

// Hàm helper để lấy màu status
function getStatusColor($status) {
    switch ($status) {
        case 'pending': return '#ffc107';
        case 'confirmed': return '#17a2b8';
        case 'processing': return '#007bff';
        case 'shipped': return '#6f42c1';
        case 'delivered': return '#28a745';
        case 'cancelled': return '#dc3545';
        default: return '#6c757d';
    }
}

// Hàm helper để redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Hàm helper để check login
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Hàm helper để check admin
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Hàm helper để get current user
function getCurrentUser() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM users WHERE id = ?";
    return fetchOne($sql, [$user_id]);
}

// Hàm helper để tạo slug
function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    $string = preg_replace('/[\s-]+/', '-', $string);
    return trim($string, '-');
}

// Hàm helper để upload file
function uploadFile($file, $directory = 'products') {
    if (!isset($file['tmp_name'])) {
        return false;
    }

    // Kiểm tra lỗi upload từ PHP
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return false;
    }

    // Kích thước hợp lệ (> 0). Giới hạn tối đa tùy cấu hình server (upload_max_filesize, post_max_size)
    if (($file['size'] ?? 0) <= 0) {
        return false;
    }

    // Thư mục lưu (web path) và đường dẫn tuyệt đối trên filesystem
    $uploadDir = rtrim(UPLOAD_PATH, '/\\') . '/' . trim($directory, '/\\') . '/';
    $rootPath = dirname(__DIR__) . DIRECTORY_SEPARATOR; // project root
    $fsUploadDir = $rootPath . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $uploadDir);

    if (!is_dir($fsUploadDir)) {
        if (!mkdir($fsUploadDir, 0755, true) && !is_dir($fsUploadDir)) {
            return false;
        }
    }

    // Kiểm tra extension
    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if ($extension === '' || !in_array($extension, ALLOWED_EXTENSIONS, true)) {
        return false;
    }

    // Tạo tên file an toàn
    $baseName = pathinfo($file['name'], PATHINFO_FILENAME);
    $safeBase = preg_replace('/[^a-zA-Z0-9_-]/', '-', $baseName);
    $filename = $safeBase . '-' . uniqid() . '.' . $extension;

    // Đường dẫn tuyệt đối để move_uploaded_file
    $fsTarget = rtrim($fsUploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

    if (!is_uploaded_file($file['tmp_name'])) {
        return false;
    }

    if (move_uploaded_file($file['tmp_name'], $fsTarget)) {
        // Trả về đường dẫn web để lưu DB/hiển thị
        return $uploadDir . $filename;
    }

    return false;
}

// Hàm helper để delete file
function deleteFile($filepath) {
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return true;
}

// Auto-generate CSRF token
generateCSRFToken();
?>

