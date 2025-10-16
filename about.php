<?php
require_once 'config/config.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Giới thiệu - <?php echo SITE_NAME; ?></title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
	<link rel="stylesheet" href="assets/css/main.css">
	<link rel="stylesheet" href="assets/css/components.css">
	<style>
		.about-hero { background: linear-gradient(135deg, #eef2ff, #faf5ff); padding: 64px 0; }
		.about-hero h1 { margin: 0 0 8px 0; font-size: var(--text-4xl); color: var(--gray-900); }
		.about-hero p { color: var(--gray-600); font-size: var(--text-lg); }
		.about-content { padding: 56px 0; }
		.grid-3 { display:grid; grid-template-columns: repeat(3, 1fr); gap: 24px; }
		.card { background: #fff; border-radius: 16px; box-shadow: var(--shadow-md); padding: 24px; }
		.card h3 { margin-top:0; font-size: 20px; }
		.stats { display:grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-top: 24px; }
		.stat { background:#fff; padding:20px; border-radius:12px; text-align:center; box-shadow: var(--shadow-sm); }
		.stat .num { font-size: 24px; font-weight: 700; color: var(--primary-color); }
		.team { padding: 56px 0; background: var(--gray-50); }
		.team-grid { display:grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }
		.member { background:#fff; border-radius:16px; box-shadow: var(--shadow-sm); padding:16px; text-align:center; }
		.member .avatar { width: 96px; height:96px; border-radius:50%; background: var(--gray-200); margin: 0 auto 12px; overflow:hidden; }
		.member .avatar img { width:100%; height:100%; object-fit:cover; }
		.cta { padding: 56px 0; text-align:center; }
		.btn-primary { background: var(--primary-color); color:#fff; border:none; padding:14px 22px; border-radius:14px; font-weight:600; text-decoration:none; }
		@media (max-width: 992px){ .grid-3{ grid-template-columns: 1fr; } .team-grid{ grid-template-columns: repeat(2, 1fr);} }
		@media (max-width: 576px){ .team-grid{ grid-template-columns: 1fr;} }
	</style>
</head>
<body>
	<?php include 'includes/header.php'; ?>

	<section class="about-hero">
		<div class="container">
			<h1>Về <?php echo SITE_NAME; ?></h1>
			<p>Mini Shop – cửa hàng điện tử mini mang đến sản phẩm chất lượng, giá tốt và trải nghiệm mua sắm hiện đại.</p>
		</div>
	</section>

	<section class="about-content">
		<div class="container">
			<div class="grid-3">
				<div class="card">
					<h3><i class="fas fa-bullseye"></i> Sứ mệnh</h3>
					<p>Đưa công nghệ đến gần hơn với mọi người bằng dịch vụ tận tâm, nhanh chóng và minh bạch.</p>
				</div>
				<div class="card">
					<h3><i class="fas fa-eye"></i> Tầm nhìn</h3>
					<p>Trở thành điểm đến tin cậy cho cộng đồng yêu công nghệ, nơi mọi trải nghiệm đều đơn giản và thú vị.</p>
				</div>
				<div class="card">
					<h3><i class="fas fa-handshake"></i> Giá trị</h3>
					<p>Chính trực, chất lượng và lấy khách hàng làm trung tâm trong mọi quyết định.</p>
				</div>
			</div>

			<div class="stats">
				<div class="stat"><div class="num">5000+</div><div>Khách hàng hài lòng</div></div>
				<div class="stat"><div class="num">1200+</div><div>Sản phẩm đang bán</div></div>
				<div class="stat"><div class="num">4.8/5</div><div>Điểm đánh giá trung bình</div></div>
				<div class="stat"><div class="num">24/7</div><div>Hỗ trợ khách hàng</div></div>
			</div>
		</div>
	</section>

	<section class="team">
		<div class="container">
			<h2 style="margin-top:0;">Đội ngũ của chúng tôi</h2>
			<p style="color:var(--gray-600)">Những con người phía sau Mini Shop</p>
			<div class="team-grid">
				<div class="member">
					<div class="avatar"><img src="assets/images/placeholder.jpg" alt="Team"></div>
					<div style="font-weight:600">Trung Kiên</div>
					<small>Founder & CEO</small>
				</div>
				<div class="member">
					<div class="avatar"><img src="assets/images/placeholder.jpg" alt="Team"></div>
					<div style="font-weight:600">Minh Anh</div>
					<small>COO</small>
				</div>
				<div class="member">
					<div class="avatar"><img src="assets/images/placeholder.jpg" alt="Team"></div>
					<div style="font-weight:600">Quang Huy</div>
					<small>CTO</small>
				</div>
				<div class="member">
					<div class="avatar"><img src="assets/images/placeholder.jpg" alt="Team"></div>
					<div style="font-weight:600">Thu Trang</div>
					<small>Head of Support</small>
				</div>
			</div>
		</div>
	</section>

	<section class="cta">
		<div class="container">
			<h2 style="margin-top:0;">Cần hỗ trợ thêm?</h2>
			<p style="color:var(--gray-600)">Liên hệ với chúng tôi qua điện thoại <?php echo SITE_PHONE; ?> hoặc email <?php echo SITE_EMAIL; ?>.</p>
			<a class="btn-primary" href="contact.php"><i class="fas fa-envelope"></i> Liên hệ ngay</a>
		</div>
	</section>

	<?php include 'includes/footer.php'; ?>
	<script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>
