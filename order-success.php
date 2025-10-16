<?php
/**
 * TRANG THÀNH CÔNG ĐẶT HÀNG
 * Hiển thị thông tin đơn hàng sau khi đặt hàng thành công
 */

require_once 'config/config.php';

// Lấy order_id từ URL
$order_id = intval($_GET['order_id'] ?? 0);

if (!$order_id) {
    header('Location: index.php');
    exit;
}

// Lấy thông tin đơn hàng
$order = fetchOne("
    SELECT o.*, u.full_name as user_name, u.email as user_email
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
", [$order_id]);

if (!$order) {
    header('Location: index.php');
    exit;
}

// Debug: Kiểm tra dữ liệu
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Lấy chi tiết sản phẩm trong đơn hàng
$order_items = fetchData("
    SELECT oi.*, pi.image_path
    FROM order_items oi
    LEFT JOIN product_images pi ON oi.product_id = pi.product_id AND pi.is_primary = 1
    WHERE oi.order_id = ?
    ORDER BY oi.id
", [$order_id]);

// Format ngày tháng
function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Cảm ơn bạn đã đặt hàng. Đơn hàng của bạn đã được xử lý thành công.">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    
    <style>
        .success-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }
        
        .success-container {
            background: white;
            border-radius: 2rem;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            overflow: hidden;
            max-width: 800px;
            width: 100%;
            margin: 0 1rem;
        }
        
        .success-header {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
        }
        
        .success-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="success" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23success)"/></svg>');
            opacity: 0.3;
        }
        
        .success-icon {
            position: relative;
            z-index: 2;
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }
        
        .success-title {
            position: relative;
            z-index: 2;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .success-subtitle {
            position: relative;
            z-index: 2;
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .success-content {
            padding: 2rem;
        }
        
        .order-info {
            background: #f8f9fa;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .order-info h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        
        .info-value {
            color: #333;
            font-weight: 500;
        }
        
        .order-items {
            margin-bottom: 2rem;
        }
        
        .order-items h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }
        
        .item-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .item-card {
            display: flex;
            align-items: center;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 1rem;
            padding: 1rem;
            transition: all 0.3s ease;
        }
        
        .item-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 0.5rem;
            object-fit: cover;
            margin-right: 1rem;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.3rem;
        }
        
        .item-sku {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.3rem;
        }
        
        .item-quantity {
            font-size: 0.9rem;
            color: #666;
        }
        
        .item-price {
            text-align: right;
            font-weight: 700;
            color: #4CAF50;
            font-size: 1.1rem;
        }
        
        .order-summary {
            background: #f8f9fa;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.8rem;
        }
        
        .summary-row:last-child {
            margin-bottom: 0;
            padding-top: 0.8rem;
            border-top: 2px solid #dee2e6;
            font-weight: 700;
            font-size: 1.1rem;
            color: #333;
        }
        
        .summary-label {
            color: #666;
        }
        
        .summary-value {
            color: #333;
            font-weight: 600;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 1rem 2rem;
            border-radius: 0.8rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.3);
            color: white;
        }
        
        .btn-outline {
            background: transparent;
            color: #4CAF50;
            border: 2px solid #4CAF50;
            padding: 1rem 2rem;
            border-radius: 0.8rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-outline:hover {
            background: #4CAF50;
            color: white;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .success-container {
                margin: 0 0.5rem;
            }
            
            .success-header {
                padding: 2rem 1rem;
            }
            
            .success-title {
                font-size: 2rem;
            }
            
            .success-content {
                padding: 1.5rem;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .item-card {
                flex-direction: column;
                text-align: center;
            }
            
            .item-image {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="success-page">
        <div class="success-container">
            <!-- Header -->
            <div class="success-header">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="success-title">Đặt hàng thành công!</h1>
                <p class="success-subtitle">Cảm ơn bạn đã mua sắm tại <?php echo SITE_NAME; ?></p>
            </div>
            
            <!-- Content -->
            <div class="success-content">
                <!-- Order Info -->
                <div class="order-info">
                    <h3><i class="fas fa-receipt"></i> Thông tin đơn hàng</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Mã đơn hàng</span>
                            <span class="info-value">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Ngày đặt</span>
                            <span class="info-value"><?php echo formatDate($order['created_at']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Trạng thái</span>
                            <span class="info-value" style="color: #4CAF50; font-weight: 600;"><?php echo ucfirst($order['order_status']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Thanh toán</span>
                            <span class="info-value" style="color: #4CAF50; font-weight: 600;"><?php echo ucfirst($order['payment_status']); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="order-items">
                    <h3><i class="fas fa-shopping-bag"></i> Sản phẩm đã đặt</h3>
                    <?php if (!empty($order_items)): ?>
                        <div class="item-list">
                            <?php foreach ($order_items as $item): ?>
                                <div class="item-card">
                                    <img src="<?php echo htmlspecialchars($item['image_path'] ?: 'assets/images/placeholder.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                         class="item-image">
                                    <div class="item-details">
                                        <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        <div class="item-sku">SKU: <?php echo htmlspecialchars($item['product_sku']); ?></div>
                                        <div class="item-quantity">Số lượng: <?php echo $item['quantity']; ?></div>
                                    </div>
                                    <div class="item-price"><?php echo formatPrice($item['total']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-items" style="text-align: center; padding: 2rem; color: #666; background: #f8f9fa; border-radius: 1rem;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 1rem; color: #ffc107;"></i>
                            <p>Không tìm thấy sản phẩm trong đơn hàng này.</p>
                            <p style="font-size: 0.9rem; margin-top: 0.5rem;">Có thể đơn hàng đã được xử lý hoặc có lỗi trong quá trình tạo đơn hàng.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Order Summary -->
                <div class="order-summary">
                    <h3><i class="fas fa-calculator"></i> Tóm tắt đơn hàng</h3>
                    <div class="summary-row">
                        <span class="summary-label">Tạm tính</span>
                        <span class="summary-value"><?php echo formatPrice($order['subtotal']); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Phí vận chuyển</span>
                        <span class="summary-value"><?php echo formatPrice($order['shipping_fee']); ?></span>
                    </div>
                    <?php if ($order['discount_amount'] > 0): ?>
                    <div class="summary-row">
                        <span class="summary-label">Giảm giá</span>
                        <span class="summary-value" style="color: #4CAF50;">-<?php echo formatPrice($order['discount_amount']); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-row">
                        <span class="summary-label">Tổng cộng</span>
                        <span class="summary-value"><?php echo formatPrice($order['total_amount']); ?></span>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <a href="index.php" class="btn-success">
                        <i class="fas fa-home"></i> Về trang chủ
                    </a>
                    <a href="products.php" class="btn-outline">
                        <i class="fas fa-shopping-bag"></i> Tiếp tục mua sắm
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        // Auto redirect sau 30 giây (optional)
        // setTimeout(() => {
        //     window.location.href = 'index.php';
        // }, 30000);
        
        // Print order function
        function printOrder() {
            window.print();
        }
        
        // Share order function
        function shareOrder() {
            if (navigator.share) {
                navigator.share({
                    title: 'Đơn hàng #<?php echo $order['order_number']; ?>',
                    text: 'Tôi vừa đặt hàng thành công tại <?php echo SITE_NAME; ?>',
                    url: window.location.href
                });
            } else {
                // Fallback for browsers that don't support Web Share API
                const url = window.location.href;
                navigator.clipboard.writeText(url).then(() => {
                    alert('Đã copy link đơn hàng vào clipboard!');
                });
            }
        }
    </script>
</body>
</html>
