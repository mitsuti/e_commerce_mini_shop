<?php
require_once '../config/config.php';
if (!isAdmin()) { redirect('../index.php'); }

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'update_status') {
        $order_id = intval($_POST['order_id']);
        $new_status = $_POST['status'];
        
        $valid_statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($new_status, $valid_statuses)) {
            echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
            exit;
        }
        
        try {
            executeQuery("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?", [$new_status, $order_id]);
            echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thành công']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'update_payment') {
        $order_id = intval($_POST['order_id']);
        $new_payment = $_POST['payment_status'];
        
        $valid_payments = ['pending', 'paid', 'failed', 'refunded'];
        if (!in_array($new_payment, $valid_payments)) {
            echo json_encode(['success' => false, 'message' => 'Trạng thái thanh toán không hợp lệ']);
            exit;
        }
        
        try {
            executeQuery("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?", [$new_payment, $order_id]);
            echo json_encode(['success' => true, 'message' => 'Cập nhật trạng thái thanh toán thành công']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'delete_order') {
        $order_id = intval($_POST['order_id']);
        
        try {
            // Begin transaction
            executeQuery("START TRANSACTION");
            
            // Get order items to return stock
            $order_items = fetchData("SELECT product_id, quantity FROM order_items WHERE order_id = ?", [$order_id]);
            
            // Return stock for each product
            foreach ($order_items as $item) {
                executeQuery("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?", 
                           [$item['quantity'], $item['product_id']]);
            }
            
            // Delete order items
            executeQuery("DELETE FROM order_items WHERE order_id = ?", [$order_id]);
            
            // Delete order
            executeQuery("DELETE FROM orders WHERE id = ?", [$order_id]);
            
            // Commit transaction
            executeQuery("COMMIT");
            
            echo json_encode(['success' => true, 'message' => 'Xóa đơn hàng thành công']);
        } catch (Exception $e) {
            // Rollback transaction
            executeQuery("ROLLBACK");
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
}

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20; $offset = ($page - 1) * $limit;
$totalRow = fetchOne("SELECT COUNT(*) AS c FROM orders", []);
$total = (int)($totalRow['c'] ?? 0); $pages = max(1, (int)ceil($total / $limit));

$rows = fetchData("SELECT id, order_number, customer_name, customer_phone, total_amount, payment_status, order_status, created_at
                   FROM orders ORDER BY id DESC LIMIT $limit OFFSET $offset", []);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - <?php echo SITE_NAME; ?></title>
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
        .card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header { padding: 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .card-title { font-size: 18px; font-weight: 600; color: #1e293b; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .table th { background: #f8fafc; font-weight: 600; color: #475569; font-size: 14px; }
        .table tbody tr:hover { background: #f8fafc; }
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 500; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1d4ed8; }
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 24px; }
        .pagination a { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #374151; }
        .pagination .active { background: #6366f1; color: white; border-color: #6366f1; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px; margin-bottom: 32px; }
        .stat-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-value { font-size: 32px; font-weight: 700; color: #1e293b; }
        .stat-label { color: #64748b; font-size: 14px; margin-top: 4px; }
        .status-select {
            padding: 6px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
            font-size: 14px;
            min-width: 120px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .status-select:hover {
            border-color: #6366f1;
        }
        .status-select:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .btn-delete {
            padding: 8px 12px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 1000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }
        .notification.show {
            transform: translateX(0);
        }
        .notification.success {
            background: #10b981;
        }
        .notification.error {
            background: #ef4444;
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .status-select { min-width: 100px; font-size: 12px; }
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
                    <a href="dashboard.php" class="nav-link">
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
                    <a href="orders.php" class="nav-link active">
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
                <h1>Quản lý đơn hàng</h1>
                <div class="user-menu">
                    <div class="user-avatar">
                        <?php echo substr(getCurrentUser()['full_name'] ?? 'A', 0, 1); ?>
                    </div>
                    <span><?php echo getCurrentUser()['full_name'] ?? 'Admin'; ?></span>
                </div>
            </div>

            <div class="content">
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format($total); ?></div>
                        <div class="stat-label">Tổng đơn hàng</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format(fetchOne("SELECT COUNT(*) as c FROM orders WHERE payment_status = 'paid'")['c']); ?></div>
                        <div class="stat-label">Đã thanh toán</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format(fetchOne("SELECT COUNT(*) as c FROM orders WHERE order_status = 'pending'")['c']); ?></div>
                        <div class="stat-label">Chờ xử lý</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format(fetchOne("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'")['total'] ?: 0); ?> ₫</div>
                        <div class="stat-label">Tổng doanh thu</div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Danh sách đơn hàng</div>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Điện thoại</th>
                                <th>Tổng tiền</th>
                                <th>Thanh toán</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($r['order_number']); ?></td>
                                <td><?php echo htmlspecialchars($r['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($r['customer_phone']); ?></td>
                                <td><?php echo formatPrice($r['total_amount']); ?></td>
                                <td>
                                    <select class="status-select" data-order-id="<?php echo $r['id']; ?>" data-type="payment">
                                        <option value="pending" <?php echo $r['payment_status'] === 'pending' ? 'selected' : ''; ?>>Chờ thanh toán</option>
                                        <option value="paid" <?php echo $r['payment_status'] === 'paid' ? 'selected' : ''; ?>>Đã thanh toán</option>
                                        <option value="failed" <?php echo $r['payment_status'] === 'failed' ? 'selected' : ''; ?>>Thanh toán thất bại</option>
                                        <option value="refunded" <?php echo $r['payment_status'] === 'refunded' ? 'selected' : ''; ?>>Đã hoàn tiền</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="status-select" data-order-id="<?php echo $r['id']; ?>" data-type="order">
                                        <option value="pending" <?php echo $r['order_status'] === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                                        <option value="confirmed" <?php echo $r['order_status'] === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                        <option value="processing" <?php echo $r['order_status'] === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                                        <option value="shipped" <?php echo $r['order_status'] === 'shipped' ? 'selected' : ''; ?>>Đang giao</option>
                                        <option value="delivered" <?php echo $r['order_status'] === 'delivered' ? 'selected' : ''; ?>>Đã giao</option>
                                        <option value="cancelled" <?php echo $r['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                    </select>
                                </td>
                                <td><?php echo formatDate($r['created_at']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="deleteOrder(<?php echo $r['id']; ?>)" class="btn-delete" title="Xóa đơn hàng">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <?php for($i=1;$i<=$pages;$i++): ?>
                        <a class="<?php echo $i==$page?'active':''; ?>" href="orders.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        // Handle status updates
        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', function() {
                const orderId = this.dataset.orderId;
                const type = this.dataset.type;
                const newValue = this.value;
                
                // Show loading state
                const originalValue = this.value;
                this.disabled = true;
                
                // Prepare form data
                const formData = new FormData();
                formData.append('action', type === 'payment' ? 'update_payment' : 'update_status');
                formData.append('order_id', orderId);
                if (type === 'payment') {
                    formData.append('payment_status', newValue);
                } else {
                    formData.append('status', newValue);
                }
                
                // Send AJAX request
                fetch('orders.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showNotification(data.message, 'success');
                    } else {
                        showNotification(data.message, 'error');
                        this.value = originalValue; // Revert on error
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification('Có lỗi xảy ra khi cập nhật', 'error');
                    this.value = originalValue; // Revert on error
                })
                .finally(() => {
                    this.disabled = false;
                });
            });
        });
        
        // Delete order function
        function deleteOrder(orderId) {
            if (!confirm('Bạn có chắc chắn muốn xóa đơn hàng này? Hành động này không thể hoàn tác và sẽ hoàn trả lại số lượng sản phẩm vào kho.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_order');
            formData.append('order_id', orderId);
            
            fetch('orders.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Có lỗi xảy ra khi xóa đơn hàng', 'error');
            });
        }
        
        // Show notification
        function showNotification(message, type) {
            // Remove existing notifications
            document.querySelectorAll('.notification').forEach(n => n.remove());
            
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            // Show notification
            setTimeout(() => notification.classList.add('show'), 100);
            
            // Hide notification after 3 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
