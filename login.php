<?php
/**
 * TRANG ĐĂNG NHẬP
 * Trang đăng nhập cho khách hàng
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
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        if (empty($username) || empty($password)) {
            $error = 'Vui lòng điền đầy đủ thông tin.';
        } else {
            // Tìm user theo username hoặc email
            $user = fetchOne("
                SELECT * FROM users 
                WHERE (username = ? OR email = ?) AND status = 'active'
            ", [$username, $username]);
            
            if ($user && password_verify($password, $user['password'])) {
                // Đăng nhập thành công
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['full_name'];
                
                // Remember me
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 days
                    
                    // Lưu token vào database (có thể tạo bảng remember_tokens)
                    executeQuery("
                        UPDATE users SET remember_token = ? WHERE id = ?
                    ", [$token, $user['id']]);
                }
                
                // Redirect
                $redirect = $_GET['redirect'] ?? 'index.php';
                header("Location: $redirect");
                exit;
            } else {
                $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
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
    <title>Đăng nhập - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Đăng nhập vào tài khoản của bạn để mua sắm và quản lý đơn hàng.">
    
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
            max-width: 400px;
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
        
        .form-group {
            margin-bottom: var(--space-6);
        }
        
        .form-label {
            display: block;
            font-size: var(--text-sm);
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: var(--space-2);
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
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-6);
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }
        
        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
        }
        
        .remember-me label {
            font-size: var(--text-sm);
            color: var(--gray-600);
            cursor: pointer;
        }
        
        .forgot-password {
            color: var(--primary-color);
            text-decoration: none;
            font-size: var(--text-sm);
            font-weight: 500;
            transition: color var(--transition-fast);
        }
        
        .forgot-password:hover {
            color: var(--primary-dark);
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
            
            .form-options {
                flex-direction: column;
                gap: var(--space-3);
                align-items: flex-start;
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
                    <h1 class="auth-title">Đăng nhập</h1>
                    <p class="auth-subtitle">Chào mừng bạn quay trở lại!</p>
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
                
                <form class="auth-form" method="POST" id="loginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="username">Tên đăng nhập hoặc Email</label>
                        <div class="input-group">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" 
                                   id="username" 
                                   name="username" 
                                   class="form-input" 
                                   placeholder="Nhập tên đăng nhập hoặc email"
                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                   required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">Mật khẩu</label>
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
                    </div>
                    
                    <div class="form-options">
                        <div class="remember-me">
                            <input type="checkbox" id="remember" name="remember">
                            <label for="remember">Ghi nhớ đăng nhập</label>
                        </div>
                        <a href="#" class="forgot-password">Quên mật khẩu?</a>
                    </div>
                    
                    <button type="submit" class="auth-button">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập
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
                    <p>Chưa có tài khoản?</p>
                    <a href="register.php" class="auth-link">
                        <i class="fas fa-user-plus"></i> Đăng ký ngay
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
        
        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                showToast('Vui lòng điền đầy đủ thông tin', 'error');
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('.auth-button');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang đăng nhập...';
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
        document.getElementById('username').focus();
    </script>
</body>
</html>
