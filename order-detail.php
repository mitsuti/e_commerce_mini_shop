<?php
/**
 * TRANG CHI TIẾT ĐƠN HÀNG
 * Hiển thị chi tiết đơn hàng của user
 */

require_once 'config/config.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$order_id = intval($_GET['id'] ?? 0);

if (!$order_id) {
    header('Location: my-orders.php');
    exit;
}

// Lấy thông tin đơn hàng
$order = fetchOne("
    SELECT o.*, u.full_name as user_name, u.email as user_email
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = ? AND o.user_id = ?
", [$order_id, $user['id']]);

if (!$order) {
    header('Location: my-orders.php');
    exit;
}

// Lấy chi tiết sản phẩm trong đơn hàng
$order_items = fetchData("
    SELECT oi.*, pi.image_path
    FROM order_items oi
    LEFT JOIN product_images pi ON oi.product_id = pi.product_id AND pi.is_primary = 1
    WHERE oi.order_id = ?
    ORDER BY oi.id
", [$order_id]);

// Get status text
function getStatusText($status) {
    switch ($status) {
        case 'pending': return 'Chờ xử lý';
        case 'confirmed': return 'Đã xác nhận';
        case 'processing': return 'Đang xử lý';
        case 'shipped': return 'Đã giao hàng';
        case 'delivered': return 'Đã nhận hàng';
        case 'cancelled': return 'Đã hủy';
        default: return ucfirst($status);
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?php echo $order['order_number']; ?> - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Xem chi tiết đơn hàng của bạn">
    
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
        .order-detail-page {
            min-height: 100vh;
            background: #f8f9fa;
            padding: 2rem 0;
        }
        
        .order-detail-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .order-header {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .order-header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .order-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
        }
        
        .order-status {
            padding: 0.5rem 1.5rem;
            border-radius: 2rem;
            font-size: 1rem;
            font-weight: 600;
            color: white;
        }
        
        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
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
        
        .order-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .order-items-section {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .item-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .item-card {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            border-radius: 1rem;
            padding: 1rem;
            transition: all 0.3s ease;
        }
        
        .item-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
            color: var(--primary-color);
            font-size: 1.1rem;
        }
        
        .order-summary-section {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            height: fit-content;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .summary-row:last-child {
            margin-bottom: 0;
            padding-top: 1rem;
            border-top: 2px solid #e9ecef;
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
        
        .order-actions {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .btn-primary {
            background: var(--primary-color);
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
        
        .btn-primary:hover {
            background: var(--primary-dark);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 1rem 2rem;
            border-radius: 0.8rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-secondary:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
            border: 2px solid #dc3545;
            padding: 1rem 2rem;
            border-radius: 0.8rem;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn-danger:hover {
            background: #c82333;
            border-color: #c82333;
            color: white;
            transform: translateY(-2px);
        }
        
        .breadcrumb {
            margin-bottom: 2rem;
        }
        
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .order-content {
                grid-template-columns: 1fr;
            }
            
            .order-header-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .order-info-grid {
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
            
            .order-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Order Detail Page -->
    <div class="order-detail-page">
        <div class="order-detail-container">
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="index.php">Trang chủ</a> / 
                <a href="my-orders.php">Đơn hàng của tôi</a> / 
                <span>Chi tiết đơn hàng</span>
            </div>
            
            <!-- Order Header -->
            <div class="order-header">
                <div class="order-header-top">
                    <div class="order-number">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                    <div class="order-status" style="background-color: <?php echo getStatusColor($order['order_status']); ?>">
                        <?php echo getStatusText($order['order_status']); ?>
                    </div>
                </div>
                
                <div class="order-info-grid">
                    <div class="info-item">
                        <span class="info-label">Ngày đặt</span>
                        <span class="info-value"><?php echo formatDate($order['created_at']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Thanh toán</span>
                        <span class="info-value"><?php echo ucfirst($order['payment_status']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phương thức</span>
                        <span class="info-value"><?php echo ucfirst($order['payment_method']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tổng tiền</span>
                        <span class="info-value"><?php echo formatPrice($order['total_amount']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Order Content -->
            <div class="order-content">
                <!-- Order Items -->
                <div class="order-items-section">
                    <h2 class="section-title">
                        <i class="fas fa-shopping-bag"></i>
                        Sản phẩm đã đặt
                    </h2>
                    
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
                        <p>Không có sản phẩm nào trong đơn hàng này.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Order Summary -->
                <div class="order-summary-section">
                    <h2 class="section-title">
                        <i class="fas fa-calculator"></i>
                        Tóm tắt đơn hàng
                    </h2>
                    
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
                    
                    <!-- Customer Info -->
                    <h3 class="section-title" style="margin-top: 2rem;">
                        <i class="fas fa-user"></i>
                        Thông tin khách hàng
                    </h3>
                    
                    <div class="info-item" style="margin-bottom: 1rem;">
                        <span class="info-label">Họ tên</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></span>
                    </div>
                    
                    <div class="info-item" style="margin-bottom: 1rem;">
                        <span class="info-label">Email</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                    </div>
                    
                    <div class="info-item" style="margin-bottom: 1rem;">
                        <span class="info-label">Số điện thoại</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['customer_phone']); ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Địa chỉ giao hàng</span>
                        <span class="info-value"><?php echo htmlspecialchars($order['shipping_address']); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Order Actions -->
            <div class="order-actions">
                <a href="my-orders.php" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Quay lại
                </a>
                
                <?php if ($order['order_status'] === 'pending'): ?>
                    <button onclick="cancelOrder(<?php echo $order['id']; ?>)" class="btn-danger">
                        <i class="fas fa-times"></i> Hủy đơn hàng
                    </button>
                <?php endif; ?>
                
                <?php if ($order['order_status'] === 'delivered'): ?>
                    <a href="order-detail.php?id=<?php echo $order['id']; ?>&reorder=1" class="btn-primary">
                        <i class="fas fa-redo"></i> Đặt lại
                    </a>
                <?php endif; ?>
                
                <a href="products.php" class="btn-primary">
                    <i class="fas fa-shopping-cart"></i> Tiếp tục mua sắm
                </a>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- JavaScript -->
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        // Print order function
        function printOrder() {
            window.print();
        }
        
        // Share order function
        function shareOrder() {
            if (navigator.share) {
                navigator.share({
                    title: 'Đơn hàng #<?php echo $order['order_number']; ?>',
                    text: 'Tôi vừa xem chi tiết đơn hàng tại <?php echo SITE_NAME; ?>',
                    url: window.location.href
                });
            } else {
                const url = window.location.href;
                navigator.clipboard.writeText(url).then(() => {
                    alert('Đã copy link đơn hàng vào clipboard!');
                });
            }
        }
        
        // Cancel order function
        function cancelOrder(orderId) {
            if (confirm('Bạn có chắc chắn muốn hủy đơn hàng này? Hành động này không thể hoàn tác.')) {
                // Show loading
                const button = event.target;
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang hủy...';
                button.disabled = true;
                
                fetch('cancel-order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Đơn hàng đã được hủy thành công!');
                        window.location.href = 'my-orders.php';
                    } else {
                        alert('Lỗi: ' + (data.message || 'Không thể hủy đơn hàng'));
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi hủy đơn hàng');
                    button.innerHTML = originalText;
                    button.disabled = false;
                });
            }
        }
    </script>
</body>
</html>
