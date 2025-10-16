<?php
require_once '../config/config.php';
if (!isAdmin()) { redirect('../index.php'); }

try {
    // Lấy thống kê
    $stats = [
        'users' => fetchOne("SELECT COUNT(*) as count FROM users WHERE role = 'customer'")['count'],
        'products' => fetchOne("SELECT COUNT(*) as count FROM products")['count'],
        'orders' => fetchOne("SELECT COUNT(*) as count FROM orders")['count'],
        'revenue' => fetchOne("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'")['total'] ?: 0
    ];
    
    // Lấy đơn hàng gần đây
    $recent_orders = fetchData("
        SELECT o.*, u.full_name 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    
    // Lấy sản phẩm bán chạy
    $top_products = fetchData("
        SELECT p.name, SUM(oi.quantity) as total_sold 
        FROM products p 
        LEFT JOIN order_items oi ON p.id = oi.product_id 
        LEFT JOIN orders o ON oi.order_id = o.id 
        WHERE o.payment_status = 'paid' 
        GROUP BY p.id 
        ORDER BY total_sold DESC 
        LIMIT 5
    ");
    
} catch (Exception $e) {
    $error = 'Lỗi: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #334155; }
        .admin-layout { display: flex; min-height: 100vh; }
        .sidebar { width: 280px; background: #1e293b; color: white; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid #334155; }
        .sidebar-header h2 { font-size: 20px; font-weight: 700; }
        .sidebar-nav { padding: 16px 0; }
        .nav-item { margin: 4px 0; }
        .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 24px; color: #cbd5e1; text-decoration: none; transition: all 0.2s; }
        .nav-link:hover, .nav-link.active { background: #334155; color: white; }
        .nav-link i { width: 20px; text-align: center; }
        .main-content { flex: 1; margin-left: 280px; }
        .header { background: white; padding: 20px 32px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 24px; font-weight: 700; color: #1e293b; }
        .user-menu { display: flex; align-items: center; gap: 16px; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background: #6366f1; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        .content { padding: 32px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-bottom: 32px; }
        .stat-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); text-align: center; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 24px; }
        .stat-card.primary .stat-icon { background: #dbeafe; color: #1d4ed8; }
        .stat-card.success .stat-icon { background: #dcfce7; color: #166534; }
        .stat-card.warning .stat-icon { background: #fef3c7; color: #92400e; }
        .stat-card.danger .stat-icon { background: #fee2e2; color: #991b1b; }
        .stat-value { font-size: 32px; font-weight: 700; color: #1e293b; margin-bottom: 8px; }
        .stat-label { color: #64748b; font-size: 14px; }
        .content-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
        .card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { padding: 24px; border-bottom: 1px solid #e2e8f0; }
        .card-title { font-size: 18px; font-weight: 600; color: #1e293b; }
        .card-body { padding: 24px; }
        .order-item, .product-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .order-item:last-child, .product-item:last-child { border-bottom: none; }
        .order-info, .product-info { flex: 1; }
        .order-number, .product-name { font-weight: 600; color: #1e293b; margin-bottom: 4px; }
        .order-customer, .product-sold { color: #64748b; font-size: 14px; }
        .order-amount { font-weight: 600; color: #059669; }
        .error { background: #fee2e2; color: #991b1b; padding: 16px; border-radius: 8px; margin: 16px 0; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .content-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-store"></i> Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="products.php" class="nav-link">
                        <i class="fas fa-box"></i>
                        <span>Sản phẩm</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="orders.php" class="nav-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span>Đơn hàng</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="customers.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Khách hàng</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="categories.php" class="nav-link">
                        <i class="fas fa-tags"></i>
                        <span>Danh mục</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="../index.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Về trang chủ</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="../logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Đăng xuất</span>
                    </a>
                </div>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1>Dashboard</h1>
                <div class="user-menu">
                    <div class="user-avatar">
                        <?php echo substr(getCurrentUser()['full_name'] ?? 'A', 0, 1); ?>
                    </div>
                    <span><?php echo getCurrentUser()['full_name'] ?? 'Admin'; ?></span>
                </div>
            </div>

            <div class="content">
                <?php if (isset($error)): ?>
                    <div class="error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card primary">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['users']); ?></div>
                        <div class="stat-label">Khách hàng</div>
                    </div>
                    <div class="stat-card success">
                        <div class="stat-icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['products']); ?></div>
                        <div class="stat-label">Sản phẩm</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['orders']); ?></div>
                        <div class="stat-label">Đơn hàng</div>
                    </div>
                    <div class="stat-card danger">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['revenue']); ?> ₫</div>
                        <div class="stat-label">Doanh thu</div>
                    </div>
                </div>

                <!-- Content Grid -->
                <div class="content-grid">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Đơn hàng gần đây</div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recent_orders)): ?>
                                <?php foreach ($recent_orders as $order): ?>
                                    <div class="order-item">
                                        <div class="order-info">
                                            <div class="order-number">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                                            <div class="order-customer"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                        </div>
                                        <div class="order-amount"><?php echo number_format($order['total_amount']); ?> ₫</div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: #64748b; text-align: center;">Chưa có đơn hàng nào</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">Sản phẩm bán chạy</div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($top_products)): ?>
                                <?php foreach ($top_products as $product): ?>
                                    <div class="product-item">
                                        <div class="product-info">
                                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                            <div class="product-sold"><?php echo $product['total_sold']; ?> đã bán</div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p style="color: #64748b; text-align: center;">Chưa có dữ liệu bán hàng</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>
