<?php
require_once 'config/config.php';

// Đảm bảo session đã khởi tạo
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

// Xóa toàn bộ dữ liệu đăng nhập trong session
$_SESSION = [];

// Xóa cookie phiên nếu có
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

// Hủy phiên làm việc
session_destroy();

// Chuyển hướng về trang chủ
redirect('index.php');
