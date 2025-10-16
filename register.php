<?php
/**
 * TRANG ĐĂNG KÝ
 * Trang đăng ký tài khoản mới
 */

require_once 'config/config.php';

// Redirect nếu đã đăng nhập
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

// Xử lý form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token không hợp lệ. Vui lòng thử lại.';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $full_name = sanitizeInput($_POST['full_name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $agree_terms = isset($_POST['agree_terms']);
        
        // Validation
        $errors = [];
        
        if (empty($username)) {
            $errors[] = 'Tên đăng nhập là bắt buộc';
        } elseif (strlen($username) < 3) {
            $errors[] = 'Tên đăng nhập phải có ít nhất 3 ký tự';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới';
        }
        
        if (empty($email)) {
            $errors[] = 'Email là bắt buộc';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ';
        }
        
        if (empty($password)) {
            $errors[] = 'Mật khẩu là bắt buộc';
        } elseif (strlen($password) < 6) {
            $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Mật khẩu xác nhận không khớp';
        }
        
        if (empty($full_name)) {
            $errors[] = 'Họ tên là bắt buộc';
        }
        
        if (empty($phone)) {
            $errors[] = 'Số điện thoại là bắt buộc';
        }
        
        if (!$agree_terms) {
            $errors[] = 'Bạn phải đồng ý với điều khoản sử dụng';
        }
        
        // Kiểm tra username và email đã tồn tại
        if (empty($errors)) {
            $existing_user = fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
            if ($existing_user) {
                $errors[] = 'Tên đăng nhập hoặc email đã được sử dụng';
            }
        }
        
        if (empty($errors)) {
            try {
                // Tạo tài khoản mới
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                $sql = "
                    INSERT INTO users (username, email, password, full_name, phone, role, status, email_verified, created_at) 
                    VALUES (?, ?, ?, ?, ?, 'customer', 'active', 0, NOW())
                ";
                
                executeQuery($sql, [$username, $email, $hashed_password, $full_name, $phone]);
                
                $success = 'Đăng ký thành công! Bạn có thể đăng nhập ngay bây giờ.';
                
                // Redirect to login after 3 seconds
                header("refresh:3;url=login.php");
                
            } catch (Exception $e) {
                $error = 'Có lỗi xảy ra khi tạo tài khoản. Vui lòng thử lại.';
                error_log("Registration error: " . $e->getMessage());
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
    <title>Đăng ký - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Tạo tài khoản mới để mua sắm và quản lý đơn hàng dễ dàng.">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    
    <!-- Custom CSS for auth pages -->
    <style>
        .auth-page {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: var(--space-8) 0;
            position: relative;
            overflow: hidden;
        }
        
        .auth-page::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .auth-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            padding: 0 var(--space-4);
        }
        
        .auth-card {
            background: var(--white);
            border-radius: var(--radius-2xl);
            padding: var(--space-8);
            box-shadow: var(--shadow-2xl);
            position: relative;
            overflow: hidden;
        }
        
        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: var(--space-8);
        }
        
        .auth-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-4);
            color: var(--white);
            font-size: var(--text-3xl);
        }
        
        .auth-title {
            font-size: var(--text-3xl);
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: var(--space-2);
        }
        
        .auth-subtitle {
            color: var(--gray-600);
            font-size: var(--text-base);
        }
        
        .auth-form {
            margin-bottom: var(--space-6);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-4);
            margin-bottom: var(--space-4);
        }
        
        .form-group {
            margin-bottom: var(--space-4);
        }
        
        .form-label {
            display: block;
            font-size: var(--text-sm);
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: var(--space-2);
        }
        
        .form-label.required::after {
            content: ' *';
            color: var(--error-color);
        }
        
        .form-input {
            width: 100%;
            padding: var(--space-4) var(--space-4) var(--space-4) var(--space-12);
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-lg);
            font-size: var(--text-base);
            transition: all var(--transition-fast);
            background: var(--white);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-input.error {
            border-color: var(--error-color);
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: var(--space-4);
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
            font-size: var(--text-lg);
        }
        
        .password-strength {
            margin-top: var(--space-2);
        }
        
        .strength-bar {
            height: 4px;
            background: var(--gray-200);
            border-radius: var(--radius-full);
            overflow: hidden;
            margin-bottom: var(--space-1);
        }
        
        .strength-fill {
            height: 100%;
            transition: all var(--transition-fast);
            border-radius: var(--radius-full);
        }
        
        .strength-fill.weak {
            width: 33%;
            background: var(--error-color);
        }
        
        .strength-fill.medium {
            width: 66%;
            background: var(--warning-color);
        }
        
        .strength-fill.strong {
            width: 100%;
            background: var(--success-color);
        }
        
        .strength-text {
            font-size: var(--text-xs);
            color: var(--gray-500);
        }
        
        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: var(--space-3);
            margin-bottom: var(--space-6);
        }
        
        .terms-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
            margin-top: 2px;
        }
        
        .terms-checkbox label {
            font-size: var(--text-sm);
            color: var(--gray-600);
            line-height: 1.5;
            cursor: pointer;
        }
        
        .terms-checkbox a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .terms-checkbox a:hover {
            text-decoration: underline;
        }
        
        .auth-button {
            width: 100%;
            padding: var(--space-4);
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            border: none;
            border-radius: var(--radius-lg);
            font-size: var(--text-lg);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
            position: relative;
            overflow: hidden;
        }
        
        .auth-button:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left var(--transition-normal);
        }
        
        .auth-button:hover:before {
            left: 100%;
        }
        
        .auth-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .auth-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .auth-divider {
            display: flex;
            align-items: center;
            margin: var(--space-6) 0;
        }
        
        .auth-divider::before,
        .auth-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gray-300);
        }
        
        .auth-divider span {
            padding: 0 var(--space-4);
            color: var(--gray-500);
            font-size: var(--text-sm);
        }
        
        .auth-footer {
            text-align: center;
        }
        
        .auth-footer p {
            color: var(--gray-600);
            margin-bottom: var(--space-4);
        }
        
        .auth-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color var(--transition-fast);
        }
        
        .auth-link:hover {
            color: var(--primary-dark);
        }
        
        .error-message {
            background: var(--error-light);
            color: var(--error-color);
            padding: var(--space-4);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-6);
            border: 1px solid var(--error-color);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }
        
        .success-message {
            background: var(--success-light);
            color: var(--success-color);
            padding: var(--space-4);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-6);
            border: 1px solid var(--success-color);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }
        
        .social-login {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-3);
            margin-bottom: var(--space-6);
        }
        
        .social-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-2);
            padding: var(--space-3);
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-lg);
            background: var(--white);
            color: var(--gray-700);
            text-decoration: none;
            font-weight: 500;
            transition: all var(--transition-fast);
        }
        
        .social-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-1px);
        }
        
        .social-btn.facebook:hover {
            border-color: #1877f2;
            color: #1877f2;
        }
        
        .social-btn.google:hover {
            border-color: #db4437;
            color: #db4437;
        }
        
        /* Mobile Responsive */
        @media (max-width: 480px) {
            .auth-container {
                padding: 0 var(--space-3);
            }
            
            .auth-card {
                padding: var(--space-6);
            }
            
            .auth-title {
                font-size: var(--text-2xl);
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .social-login {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-logo">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <h1 class="auth-title">Đăng ký</h1>
                    <p class="auth-subtitle">Tạo tài khoản mới để bắt đầu mua sắm</p>
                </div>
                
                <?php if ($error): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form class="auth-form" method="POST" id="registerForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="full_name">Họ và tên</label>
                            <div class="input-group">
                                <i class="fas fa-user input-icon"></i>
                                <input type="text" 
                                       id="full_name" 
                                       name="full_name" 
                                       class="form-input" 
                                       placeholder="Nhập họ và tên"
                                       value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>"
                                       required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required" for="phone">Số điện thoại</label>
                            <div class="input-group">
                                <i class="fas fa-phone input-icon"></i>
                                <input type="tel" 
                                       id="phone" 
                                       name="phone" 
                                       class="form-input" 
                                       placeholder="Nhập số điện thoại"
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="username">Tên đăng nhập</label>
                        <div class="input-group">
                            <i class="fas fa-at input-icon"></i>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   class="form-input" 
                                   placeholder="Nhập tên đăng nhập"
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                   required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label required" for="email">Email</label>
                        <div class="input-group">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="form-input" 
                                   placeholder="Nhập địa chỉ email"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="password">Mật khẩu</label>
                            <div class="input-group">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       class="form-input" 
                                       placeholder="Nhập mật khẩu"
                                       required>
                                <button type="button" 
                                        class="password-toggle" 
                                        onclick="togglePassword('password')"
                                        style="position: absolute; right: var(--space-4); top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--gray-500); cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength" id="passwordStrength" style="display: none;">
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <div class="strength-text" id="strengthText"></div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required" for="confirm_password">Xác nhận mật khẩu</label>
                            <div class="input-group">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       class="form-input" 
                                       placeholder="Nhập lại mật khẩu"
                                       required>
                                <button type="button" 
                                        class="password-toggle" 
                                        onclick="togglePassword('confirm_password')"
                                        style="position: absolute; right: var(--space-4); top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--gray-500); cursor: pointer;">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="terms-checkbox">
                        <input type="checkbox" id="agree_terms" name="agree_terms" required>
                        <label for="agree_terms">
                            Tôi đồng ý với <a href="/terms" target="_blank">Điều khoản sử dụng</a> 
                            và <a href="/privacy" target="_blank">Chính sách bảo mật</a>
                        </label>
                    </div>
                    
                    <button type="submit" class="auth-button">
                        <i class="fas fa-user-plus"></i> Đăng ký
                    </button>
                </form>
                
                <div class="auth-divider">
                    <span>Hoặc</span>
                </div>
                
                <div class="social-login">
                    <a href="#" class="social-btn facebook">
                        <i class="fab fa-facebook-f"></i>
                        <span>Facebook</span>
                    </a>
                    <a href="#" class="social-btn google">
                        <i class="fab fa-google"></i>
                        <span>Google</span>
                    </a>
                </div>
                
                <div class="auth-footer">
                    <p>Đã có tài khoản?</p>
                    <a href="login.php" class="auth-link">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập ngay
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const toggle = input.parentNode.querySelector('.password-toggle i');
            
            if (input.type === 'password') {
                input.type = 'text';
                toggle.classList.remove('fa-eye');
                toggle.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                toggle.classList.remove('fa-eye-slash');
                toggle.classList.add('fa-eye');
            }
        }
        
        // Password strength checker
        function checkPasswordStrength(password) {
            const strengthBar = document.getElementById('passwordStrength');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            if (password.length === 0) {
                strengthBar.style.display = 'none';
                return;
            }
            
            strengthBar.style.display = 'block';
            
            let score = 0;
            let feedback = '';
            
            // Length check
            if (password.length >= 6) score++;
            if (password.length >= 8) score++;
            
            // Character variety checks
            if (/[a-z]/.test(password)) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;
            
            // Update UI
            strengthFill.className = 'strength-fill';
            
            if (score < 3) {
                strengthFill.classList.add('weak');
                feedback = 'Mật khẩu yếu';
            } else if (score < 5) {
                strengthFill.classList.add('medium');
                feedback = 'Mật khẩu trung bình';
            } else {
                strengthFill.classList.add('strong');
                feedback = 'Mật khẩu mạnh';
            }
            
            strengthText.textContent = feedback;
        }
        
        // Password confirmation check
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const confirmInput = document.getElementById('confirm_password');
            
            if (confirmPassword.length > 0) {
                if (password === confirmPassword) {
                    confirmInput.classList.remove('error');
                } else {
                    confirmInput.classList.add('error');
                }
            }
        }
        
        // Event listeners
        document.getElementById('password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
            checkPasswordMatch();
        });
        
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const fullName = document.getElementById('full_name').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const agreeTerms = document.getElementById('agree_terms').checked;
            
            let isValid = true;
            
            // Clear previous errors
            document.querySelectorAll('.form-input').forEach(input => {
                input.classList.remove('error');
            });
            
            // Validation
            if (!username || username.length < 3) {
                document.getElementById('username').classList.add('error');
                isValid = false;
            }
            
            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                document.getElementById('email').classList.add('error');
                isValid = false;
            }
            
            if (!password || password.length < 6) {
                document.getElementById('password').classList.add('error');
                isValid = false;
            }
            
            if (password !== confirmPassword) {
                document.getElementById('confirm_password').classList.add('error');
                isValid = false;
            }
            
            if (!fullName) {
                document.getElementById('full_name').classList.add('error');
                isValid = false;
            }
            
            if (!phone) {
                document.getElementById('phone').classList.add('error');
                isValid = false;
            }
            
            if (!agreeTerms) {
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                showToast('Vui lòng điền đầy đủ và chính xác thông tin', 'error');
            } else {
                // Show loading state
                const submitBtn = this.querySelector('.auth-button');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang tạo tài khoản...';
            }
        });
        
        // Real-time validation
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value.trim()) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('error') && this.value.trim()) {
                    this.classList.remove('error');
                }
            });
        });
        
        // Auto-focus first input
        document.getElementById('full_name').focus();
    </script>
</body>
</html>
