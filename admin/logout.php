<?php
/**
 * ADMIN LOGOUT
 * Đăng xuất admin
 */

require_once '../config/config.php';

// Xóa tất cả session
session_destroy();

// Xóa remember token cookie nếu có
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Redirect về trang đăng nhập admin
header('Location: /admin/index.php');
exit;
?>
