<?php
/**
 * TRANG ĐƠN HÀNG CỦA TÔI
 */

require_once 'config/config.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();

// Lấy danh sách đơn hàng của user
$orders = fetchData("
    SELECT o.*, 
           COUNT(oi.id) as item_count,
           SUM(oi.total) as items_total
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
", [$user['id']]);

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

// Get status color (override từ config.php để có màu đỏ cho cancelled)
function getStatusColorOverride($status) {
    switch ($status) {
        case 'pending': return '#ffc107';
        case 'confirmed': return '#17a2b8';
        case 'processing': return '#007bff';
        case 'shipped': return '#6f42c1';
        case 'delivered': return '#28a745';
        case 'cancelled': return '#dc3545'; // Màu đỏ cho đã hủy
        default: return '#6c757d';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng của tôi - <?php echo SITE_NAME; ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    
    <style>
        .my-orders-page {
            min-height: 100vh;
            background: #f8f9fa;
            padding: 2rem 0;
        }
        
        .orders-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
        }
        
        .orders-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .orders-list {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .orders-list-header {
            background: #f8f9fa;
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .order-item {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .order-number {
            font-weight: 700;
            color: #333;
            font-size: 1.1rem;
        }
        
        .order-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: white;
        }
        
        .order-total {
            font-weight: 700;
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        
        .btn-view {
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .btn-view:hover {
            background: var(--primary-dark);
            color: white;
        }
        
        .empty-orders {
            text-align: center;
            padding: 4rem 2rem;
            color: #666;
        }
        
        .empty-orders i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #ddd;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- My Orders Page -->
    <div class="my-orders-page">
        <!-- Header -->
        <div class="orders-header">
            <div class="container">
                <h1>Đơn hàng của tôi</h1>
                <p>Xem và quản lý các đơn hàng đã đặt</p>
            </div>
        </div>
        
        <div class="orders-container">
            <!-- Orders List -->
            <div class="orders-list">
                <div class="orders-list-header">
                    <h2>Danh sách đơn hàng (<?php echo count($orders); ?> đơn hàng)</h2>
                </div>
                
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $order): ?>
                        <div class="order-item">
                            <div class="order-header">
                                <div>
                                    <div class="order-number">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                                    <div class="order-date"><?php echo formatDate($order['created_at']); ?></div>
                                </div>
                                <div class="order-status" style="background-color: <?php echo getStatusColorOverride($order['order_status']); ?>">
                                    <?php echo getStatusText($order['order_status']); ?>
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <div style="color: #666; font-size: 0.9rem;">
                                        <?php echo $order['item_count']; ?> sản phẩm
                                    </div>
                                    <div class="order-total">
                                        <?php echo formatPrice($order['total_amount']); ?>
                                    </div>
                                </div>
                                
                                <div>
                                    <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn-view">
                                        <i class="fas fa-eye"></i> Xem chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-orders">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>Chưa có đơn hàng nào</h3>
                        <p>Bạn chưa có đơn hàng nào. Hãy bắt đầu mua sắm ngay!</p>
                        <a href="products.php" style="background: var(--primary-color); color: white; padding: 1rem 2rem; border-radius: 0.8rem; text-decoration: none; font-weight: 600;">
                            <i class="fas fa-shopping-cart"></i> Mua sắm ngay
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- JavaScript -->
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>