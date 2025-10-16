<?php
/**
 * TRANG HỒ SƠ NGƯỜI DÙNG (PROFILE)
 */

require_once 'config/config.php';

// Bắt buộc đăng nhập
if (!isLoggedIn()) {
	redirect('login.php');
}

// Nếu là admin -> chuyển sang trang quản trị
if (isAdmin()) {
	redirect('admin/index.php');
}

// Lấy thông tin người dùng hiện tại
$user = getCurrentUser();
if (!$user) {
	// Nếu vì lý do nào đó không lấy được user, buộc đăng xuất để an toàn
	session_destroy();
	redirect('login.php');
}

// Lấy đơn hàng gần đây (nếu có bảng orders)
$orders = [];
try {
	$orders = fetchData(
		"SELECT id, order_number, total_amount, order_status, created_at
		 FROM orders
		 WHERE user_id = ?
		 ORDER BY created_at DESC
		 LIMIT 10",
		[$user['id']]
	);
} catch (Exception $e) {
	// Bảng orders có thể chưa sẵn sàng, bỏ qua phần này
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Hồ sơ - <?php echo SITE_NAME; ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<link rel="stylesheet" href="assets/css/main.css">
	<link rel="stylesheet" href="assets/css/components.css">
	<style>
		.profile-page { padding: var(--space-8) 0; background: var(--gray-50); min-height: 70vh; }
		.profile-card { background: var(--white); border-radius: var(--radius-xl); box-shadow: var(--shadow-md); padding: var(--space-6); }
		.profile-header { display:flex; align-items:center; gap: var(--space-4); margin-bottom: var(--space-4); }
		.avatar { width: 72px; height: 72px; border-radius: 50%; background: var(--gray-200); display:flex; align-items:center; justify-content:center; font-weight:700; color: var(--gray-600); }
		.profile-name { font-size: var(--text-2xl); font-weight:700; color: var(--gray-900); }
		.profile-info { display:grid; grid-template-columns: 1fr 1fr; gap: var(--space-4); }
		.info-item { background: var(--gray-50); border:1px solid var(--gray-200); border-radius: var(--radius-lg); padding: var(--space-4); }
		.orders { margin-top: var(--space-8); }
		.order-row { display:flex; justify-content:space-between; padding: var(--space-3) 0; border-bottom:1px solid var(--gray-100); }
		.order-row:last-child { border-bottom:none; }
		.badge { padding: 4px 10px; border-radius: 999px; font-size: 12px; background: var(--gray-100); color: var(--gray-700); }
		@media (max-width: 768px){ .profile-info{ grid-template-columns: 1fr; } }
	</style>
</head>
<body>
	<?php include 'includes/header.php'; ?>

	<div class="profile-page">
		<div class="container">
			<div class="profile-card">
				<div class="profile-header">
					<div class="avatar">
						<?php if (!empty($user['avatar'])): ?>
							<img src="<?php echo $user['avatar']; ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
						<?php else: ?>
							<i class="fas fa-user"></i>
						<?php endif; ?>
					</div>
					<div>
						<div class="profile-name"><?php echo htmlspecialchars($user['full_name'] ?: ($user['username'] ?? 'Tài khoản')); ?></div>
						<div style="color:var(--gray-600)"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
					</div>
					<div style="margin-left:auto">
						<a href="profile_edit.php" class="btn" style="background:var(--primary-color);color:#fff;padding:10px 14px;border-radius:12px;text-decoration:none;font-weight:600;">
							<i class="fas fa-user-edit"></i> Chỉnh sửa
						</a>
					</div>
				</div>

				<div class="profile-info">
					<div class="info-item">
						<div style="color:var(--gray-500); font-size:12px;">Số điện thoại</div>
						<div style="font-weight:600; color:var(--gray-900);">
							<?php echo htmlspecialchars($user['phone'] ?? '—'); ?>
						</div>
					</div>
					<div class="info-item">
						<div style="color:var(--gray-500); font-size:12px;">Địa chỉ</div>
						<div style="font-weight:600; color:var(--gray-900);">
							<?php echo htmlspecialchars($user['address'] ?? '—'); ?>
						</div>
					</div>
				</div>

				<div class="orders">
					<h3 style="margin-bottom:var(--space-3);">Đơn hàng gần đây</h3>
					<?php if (!empty($orders)): ?>
						<?php foreach ($orders as $o): ?>
							<div class="order-row">
								<div>#<?php echo htmlspecialchars($o['order_number']); ?> • <?php echo formatDate($o['created_at']); ?></div>
								<div class="badge"><?php echo htmlspecialchars($o['order_status']); ?></div>
								<div style="font-weight:700; color:var(--primary-color);"><?php echo formatPrice($o['total_amount']); ?></div>
							</div>
						<?php endforeach; ?>
					<?php else: ?>
						<div style="color:var(--gray-600)">Bạn chưa có đơn hàng nào.</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>

	<?php include 'includes/footer.php'; ?>
	<script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>
