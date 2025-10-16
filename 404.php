<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Không tìm thấy trang</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gray-50);
        }
        .error-content {
            text-align: center;
            max-width: 500px;
            padding: var(--space-8);
        }
        .error-code {
            font-size: 8rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: var(--space-4);
        }
        .error-message {
            font-size: var(--text-xl);
            color: var(--gray-700);
            margin-bottom: var(--space-6);
        }
        .error-description {
            color: var(--gray-600);
            margin-bottom: var(--space-8);
        }
        .btn {
            padding: var(--space-3) var(--space-6);
            background: var(--primary-color);
            color: var(--white);
            text-decoration: none;
            border-radius: var(--radius-lg);
            font-weight: 600;
            transition: all var(--transition-fast);
        }
        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="error-page">
        <div class="error-content">
            <div class="error-code">404</div>
            <h1 class="error-message">Không tìm thấy trang</h1>
            <p class="error-description">
                Trang bạn đang tìm kiếm không tồn tại hoặc đã bị di chuyển.
            </p>
            <a href="index.php" class="btn">Về trang chủ</a>
        </div>
    </div>
</body>
</html>
