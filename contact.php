<?php
require_once 'config/config.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token không hợp lệ. Vui lòng thử lại.';
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $subject = sanitizeInput($_POST['subject'] ?? '');
        $message = sanitizeInput($_POST['message'] ?? '');
        
        $errors = [];
        if (empty($name)) $errors[] = 'Họ tên là bắt buộc';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ';
        }
        if (empty($subject)) $errors[] = 'Tiêu đề là bắt buộc';
        if (empty($message)) $errors[] = 'Nội dung tin nhắn là bắt buộc';
        
        if (empty($errors)) {
            // Lưu vào database (tạo bảng contact_messages nếu chưa có)
            try {
                executeQuery("
                    CREATE TABLE IF NOT EXISTS contact_messages (
                        id INT PRIMARY KEY AUTO_INCREMENT,
                        name VARCHAR(100) NOT NULL,
                        email VARCHAR(100) NOT NULL,
                        phone VARCHAR(20),
                        subject VARCHAR(200) NOT NULL,
                        message TEXT NOT NULL,
                        status ENUM('new', 'read', 'replied') DEFAULT 'new',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )
                ");
                
                executeQuery("
                    INSERT INTO contact_messages (name, email, phone, subject, message) 
                    VALUES (?, ?, ?, ?, ?)
                ", [$name, $email, $phone, $subject, $message]);
                
                $success = 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi trong thời gian sớm nhất.';
                
                // Reset form
                $name = $email = $phone = $subject = $message = '';
                
            } catch (Exception $e) {
                $error = 'Có lỗi xảy ra khi gửi tin nhắn. Vui lòng thử lại.';
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <style>
        .contact-hero { background: linear-gradient(135deg, #eef2ff, #faf5ff); padding: 64px 0; }
        .contact-hero h1 { margin: 0 0 8px 0; font-size: var(--text-4xl); color: var(--gray-900); }
        .contact-hero p { color: var(--gray-600); font-size: var(--text-lg); }
        .contact-content { padding: 56px 0; }
        .contact-grid { display: grid; grid-template-columns: 1fr 400px; gap: 48px; }
        .contact-form { background: #fff; border-radius: 16px; box-shadow: var(--shadow-md); padding: 32px; }
        .contact-info { background: #fff; border-radius: 16px; box-shadow: var(--shadow-md); padding: 32px; height: fit-content; }
        .form-group { margin-bottom: 24px; }
        .form-label { display: block; font-weight: 600; color: var(--gray-700); margin-bottom: 8px; }
        .form-label.required::after { content: ' *'; color: var(--error-color); }
        .form-input, .form-textarea { width: 100%; padding: 12px 16px; border: 2px solid var(--gray-300); border-radius: 12px; font-size: 16px; transition: all var(--transition-fast); }
        .form-input:focus, .form-textarea:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
        .form-textarea { resize: vertical; min-height: 120px; }
        .btn-submit { background: var(--primary-color); color: #fff; border: none; padding: 14px 28px; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all var(--transition-fast); }
        .btn-submit:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .info-item { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding: 16px; background: var(--gray-50); border-radius: 12px; }
        .info-icon { width: 48px; height: 48px; background: var(--primary-color); color: #fff; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .info-content h4 { margin: 0 0 4px 0; font-size: 16px; color: var(--gray-900); }
        .info-content p { margin: 0; color: var(--gray-600); }
        .map-container { margin-top: 32px; background: var(--gray-100); border-radius: 12px; height: 300px; display: flex; align-items: center; justify-content: center; color: var(--gray-500); }
        .message { margin-bottom: 24px; padding: 16px; border-radius: 12px; }
        .message.success { background: #ecfdf5; color: #065f46; border: 1px solid #10b981; }
        .message.error { background: #fef2f2; color: #991b1b; border: 1px solid #ef4444; }
        @media (max-width: 768px) { .contact-grid { grid-template-columns: 1fr; gap: 32px; } }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="contact-hero">
        <div class="container">
            <h1>Liên hệ với chúng tôi</h1>
            <p>Chúng tôi luôn sẵn sàng lắng nghe và hỗ trợ bạn</p>
        </div>
    </section>

    <section class="contact-content">
        <div class="container">
            <div class="contact-grid">
                <!-- Contact Form -->
                <div class="contact-form">
                    <h2 style="margin-top: 0; margin-bottom: 24px;">Gửi tin nhắn</h2>
                    
                    <?php if ($success): ?>
                        <div class="message success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="message error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="form-group">
                            <label class="form-label required" for="name">Họ và tên</label>
                            <input type="text" id="name" name="name" class="form-input" 
                                   value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required" for="email">Email</label>
                            <input type="email" id="email" name="email" class="form-input" 
                                   value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="phone">Số điện thoại</label>
                            <input type="tel" id="phone" name="phone" class="form-input" 
                                   value="<?php echo htmlspecialchars($phone ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required" for="subject">Tiêu đề</label>
                            <input type="text" id="subject" name="subject" class="form-input" 
                                   value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required" for="message">Nội dung tin nhắn</label>
                            <textarea id="message" name="message" class="form-textarea" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-paper-plane"></i> Gửi tin nhắn
                        </button>
                    </form>
                </div>

                <!-- Contact Info -->
                <div class="contact-info">
                    <h3 style="margin-top: 0; margin-bottom: 24px;">Thông tin liên hệ</h3>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-content">
                            <h4>Địa chỉ</h4>
                            <p>123 Đường ABC, Quận 1, TP. Hồ Chí Minh</p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="info-content">
                            <h4>Điện thoại</h4>
                            <p><?php echo SITE_PHONE; ?></p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info-content">
                            <h4>Email</h4>
                            <p><?php echo SITE_EMAIL; ?></p>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="info-content">
                            <h4>Giờ làm việc</h4>
                            <p>Thứ 2 - Thứ 6: 8:00 - 18:00<br>Thứ 7: 8:00 - 12:00</p>
                        </div>
                    </div>
                    
                    <div class="map-container">
                        <div style="text-align: center;">
                            <i class="fas fa-map" style="font-size: 48px; margin-bottom: 16px;"></i>
                            <p>Bản đồ vị trí cửa hàng</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>
