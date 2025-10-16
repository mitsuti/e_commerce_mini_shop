<?php
/**
 * TRANG CHỈNH SỬA HỒ SƠ NGƯỜI DÙNG
 */

require_once 'config/config.php';

// Yêu cầu đăng nhập
if (!isLoggedIn()) {
	redirect('login.php');
}

// Nếu là admin, cho phép nhưng có thể chỉnh ở trang riêng; vẫn giữ lại để admin có thể đổi tên/ảnh
$user = getCurrentUser();
if (!$user) {
	session_destroy();
	redirect('login.php');
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	// CSRF
	if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
		$error = 'Token không hợp lệ. Vui lòng thử lại.';
	} else {
		$full_name = trim($_POST['full_name'] ?? '');
		if ($full_name === '') {
			$error = 'Họ tên không được để trống.';
		} elseif (mb_strlen($full_name) > 100) {
			$error = 'Họ tên tối đa 100 ký tự.';
		}

		$avatarPath = $user['avatar'] ?? '';
		if (empty($error) && isset($_FILES['avatar']) && !empty($_FILES['avatar']['name'])) {
			$newPath = uploadFile($_FILES['avatar'], 'users');
			if ($newPath) {
				// Xóa ảnh cũ nếu có
				if (!empty($avatarPath)) {
					deleteFile($avatarPath);
				}
				$avatarPath = $newPath;
			} else {
				$error = 'Tải ảnh thất bại. Vui lòng chọn ảnh JPG/PNG/GIF/WebP dưới 5MB.';
			}
		}

		if (empty($error)) {
			try {
				executeQuery(
					"UPDATE users SET full_name = ?, avatar = ?, updated_at = NOW() WHERE id = ?",
					[$full_name, $avatarPath, $user['id']]
				);
				$success = 'Cập nhật hồ sơ thành công!';
				// Refresh thông tin user
				$user = getCurrentUser();
			} catch (Exception $e) {
				$error = 'Có lỗi xảy ra khi cập nhật. Vui lòng thử lại.';
			}
		}
	}
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Chỉnh sửa hồ sơ - <?php echo SITE_NAME; ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<link rel="stylesheet" href="assets/css/main.css">
	<link rel="stylesheet" href="assets/css/components.css">
	<style>
		.edit-page { padding: var(--space-8) 0; background: var(--gray-50); min-height: 70vh; }
		.edit-card { background: var(--white); border-radius: var(--radius-xl); box-shadow: var(--shadow-md); padding: var(--space-6); max-width: 720px; margin: 0 auto; }
		.form-row { display:grid; grid-template-columns: 160px 1fr; gap: var(--space-6); align-items:center; }
		.avatar-lg { width: 120px; height: 120px; border-radius: 50%; background: var(--gray-200); overflow:hidden; display:flex; align-items:center; justify-content:center; }
		.avatar-lg img { width:100%; height:100%; object-fit:cover; }
		.actions { display:flex; gap: var(--space-3); margin-top: var(--space-6); }
		.btn-primary { background: var(--primary-color); color:#fff; border:none; padding:12px 18px; border-radius:12px; font-weight:600; cursor:pointer; }
		.btn-outline { background:#fff; border:2px solid var(--gray-300); padding:12px 18px; border-radius:12px; font-weight:600; cursor:pointer; }
		.message { margin-bottom:16px; padding:12px 16px; border-radius:10px; }
		.message.success { background:#ecfdf5; color:#065f46; border:1px solid #10b981; }
		.message.error { background:#fef2f2; color:#991b1b; border:1px solid #ef4444; }
		@media (max-width: 768px){ .form-row{ grid-template-columns:1fr; } }
	</style>
</head>
<body>
	<?php include 'includes/header.php'; ?>
	<div class="edit-page">
		<div class="container">
			<div class="edit-card">
				<h1 style="margin-top:0;">Chỉnh sửa hồ sơ</h1>
				<p style="color:var(--gray-600); margin-top:4px;">Cập nhật tên hiển thị và ảnh đại diện của bạn.</p>

				<?php if ($success): ?><div class="message success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
				<?php if ($error): ?><div class="message error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>

				<form method="POST" enctype="multipart/form-data">
					<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

					<div class="form-row" style="margin-bottom:24px;">
						<div class="avatar-lg">
							<?php if (!empty($user['avatar'])): ?>
								<img id="avatarPreview" src="<?php echo $user['avatar']; ?>" alt="Avatar">
							<?php else: ?>
								<img id="avatarPreview" src="assets/images/placeholder.jpg" alt="Avatar">
							<?php endif; ?>
						</div>
						<div>
							<label class="form-label" for="avatar">Ảnh đại diện</label>
							<input class="form-input" type="file" id="avatar" name="avatar" accept="image/*">
							<small style="color:var(--gray-500); display:block; margin-top:6px;">Hỗ trợ JPG, PNG, GIF, WebP. Tối đa 5MB.</small>
						</div>
					</div>

					<div class="form-group" style="margin-bottom:16px;">
						<label class="form-label required" for="full_name">Họ và tên</label>
						<input class="form-input" type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
					</div>

					<div class="actions">
						<button type="submit" class="btn-primary"><i class="fas fa-save"></i> Lưu thay đổi</button>
						<a class="btn-outline" href="profile.php"><i class="fas fa-arrow-left"></i> Quay lại hồ sơ</a>
					</div>
				</form>
			</div>
		</div>
	</div>

	<?php include 'includes/footer.php'; ?>
	<script>
		const input = document.getElementById('avatar');
		const preview = document.getElementById('avatarPreview');
		if (input) {
			input.addEventListener('change', (e) => {
				const file = e.target.files && e.target.files[0];
				if (file) {
					const url = URL.createObjectURL(file);
					preview.src = url;
				}
			});
		}
	</script>
	<script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>
