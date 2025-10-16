<?php
require_once '../config/config.php';
if (!isAdmin()) { redirect('../index.php'); }

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add_category') {
        $name = trim($_POST['name']);
        $status = $_POST['status'];
        $sort_order = intval($_POST['sort_order']);
        
        // Validate data
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Tên danh mục không được để trống']);
            exit;
        }
        
        $valid_statuses = ['active', 'inactive'];
        if (!in_array($status, $valid_statuses)) {
            echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
            exit;
        }
        
        try {
            // Generate slug
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $slug = trim($slug, '-');
            
            // Check if slug already exists
            $existing = fetchOne("SELECT id FROM categories WHERE slug = ?", [$slug]);
            if ($existing) {
                $slug .= '-' . time();
            }
            
            executeQuery("INSERT INTO categories (name, slug, status, sort_order, created_at) VALUES (?, ?, ?, ?, NOW())", 
                        [$name, $slug, $status, $sort_order]);
            echo json_encode(['success' => true, 'message' => 'Thêm danh mục thành công']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'update_category') {
        $category_id = intval($_POST['category_id']);
        $name = trim($_POST['name']);
        $status = $_POST['status'];
        $sort_order = intval($_POST['sort_order']);
        
        // Validate data
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Tên danh mục không được để trống']);
            exit;
        }
        
        $valid_statuses = ['active', 'inactive'];
        if (!in_array($status, $valid_statuses)) {
            echo json_encode(['success' => false, 'message' => 'Trạng thái không hợp lệ']);
            exit;
        }
        
        try {
            // Generate slug
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $slug = trim($slug, '-');
            
            // Check if slug already exists for another category
            $existing = fetchOne("SELECT id FROM categories WHERE slug = ? AND id != ?", [$slug, $category_id]);
            if ($existing) {
                $slug .= '-' . time();
            }
            
            executeQuery("UPDATE categories SET name = ?, slug = ?, status = ?, sort_order = ?, updated_at = NOW() WHERE id = ?", 
                        [$name, $slug, $status, $sort_order, $category_id]);
            echo json_encode(['success' => true, 'message' => 'Cập nhật danh mục thành công']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'delete_category') {
        $category_id = intval($_POST['category_id']);
        
        try {
            // Check if category has products
            $product_count = fetchOne("SELECT COUNT(*) as c FROM products WHERE category_id = ?", [$category_id])['c'];
            if ($product_count > 0) {
                echo json_encode(['success' => false, 'message' => 'Không thể xóa danh mục đã có sản phẩm']);
                exit;
            }
            
            executeQuery("DELETE FROM categories WHERE id = ?", [$category_id]);
            echo json_encode(['success' => true, 'message' => 'Xóa danh mục thành công']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }
        exit;
    }
}

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20; $offset = ($page - 1) * $limit;
$totalRow = fetchOne("SELECT COUNT(*) AS c FROM categories", []);
$total = (int)($totalRow['c'] ?? 0); $pages = max(1, (int)ceil($total / $limit));

$rows = fetchData("SELECT id, name, slug, status, sort_order, created_at FROM categories ORDER BY sort_order, id DESC LIMIT $limit OFFSET $offset", []);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh mục - <?php echo SITE_NAME; ?></title>
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
        .btn { padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.2s; border: none; cursor: pointer; }
        .btn-primary { background: #6366f1; color: white; }
        .btn-primary:hover { background: #5856eb; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .table th { background: #f8fafc; font-weight: 600; color: #475569; font-size: 14px; }
        .table tbody tr:hover { background: #f8fafc; }
        .badge { padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 500; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .pagination { display: flex; justify-content: center; gap: 8px; margin-top: 24px; }
        .pagination a { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; text-decoration: none; color: #374151; }
        .pagination .active { background: #6366f1; color: white; border-color: #6366f1; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 24px; margin-bottom: 32px; }
        .stat-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat-value { font-size: 32px; font-weight: 700; color: #1e293b; }
        .stat-label { color: #64748b; font-size: 14px; margin-top: 4px; }
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .btn-edit, .btn-delete {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-edit {
            background: #3b82f6;
            color: white;
        }
        .btn-edit:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .modal-header {
            padding: 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-title {
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
        }
        .close {
            color: #64748b;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        .close:hover {
            color: #1e293b;
        }
        .modal-body {
            padding: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
        }
        .form-input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .form-select {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            cursor: pointer;
        }
        .modal-footer {
            padding: 24px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        .btn-primary:hover {
            background: #2563eb;
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 1001;
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
            .modal-content { width: 95%; margin: 10% auto; }
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
                    <a href="categories.php" class="nav-link active">
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
                <h1>Quản lý danh mục</h1>
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
                        <div class="stat-label">Tổng danh mục</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format(fetchOne("SELECT COUNT(*) as c FROM categories WHERE status = 'active'")['c']); ?></div>
                        <div class="stat-label">Đang hoạt động</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format(fetchOne("SELECT COUNT(*) as c FROM products p JOIN categories c ON p.category_id = c.id")['c']); ?></div>
                        <div class="stat-label">Sản phẩm có danh mục</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format(fetchOne("SELECT COUNT(*) as c FROM categories WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['c']); ?></div>
                        <div class="stat-label">Mới trong 30 ngày</div>
                    </div>
                </div>

                <!-- Categories Table -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Danh sách danh mục</div>
                        <button onclick="addCategory()" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Thêm danh mục
                        </button>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên danh mục</th>
                                <th>Slug</th>
                                <th>Thứ tự</th>
                                <th>Trạng thái</th>
                                <th>Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>#<?php echo $r['id']; ?></td>
                                <td><?php echo htmlspecialchars($r['name']); ?></td>
                                <td><?php echo htmlspecialchars($r['slug']); ?></td>
                                <td><?php echo (int)$r['sort_order']; ?></td>
                                <td>
                                    <span class="badge <?php echo $r['status'] === 'active' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo htmlspecialchars($r['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($r['created_at']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="editCategory(<?php echo $r['id']; ?>)" class="btn-edit" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteCategory(<?php echo $r['id']; ?>)" class="btn-delete" title="Xóa">
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
                        <a class="<?php echo $i==$page?'active':''; ?>" href="categories.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Modal -->
    <div id="categoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Thêm danh mục</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="categoryForm">
                    <input type="hidden" id="category_id" name="category_id">
                    <div class="form-group">
                        <label class="form-label" for="name">Tên danh mục *</label>
                        <input type="text" id="name" name="name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="status">Trạng thái</label>
                        <select id="status" name="status" class="form-select">
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Không hoạt động</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="sort_order">Thứ tự sắp xếp</label>
                        <input type="number" id="sort_order" name="sort_order" class="form-input" value="0" min="0">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Hủy</button>
                <button type="button" class="btn btn-primary" onclick="saveCategory()">Lưu</button>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        let categoriesData = <?php echo json_encode($rows); ?>;
        let isEditMode = false;
        
        function addCategory() {
            isEditMode = false;
            document.getElementById('modalTitle').textContent = 'Thêm danh mục';
            document.getElementById('categoryForm').reset();
            document.getElementById('category_id').value = '';
            document.getElementById('sort_order').value = '0';
            document.getElementById('categoryModal').style.display = 'block';
        }
        
        function editCategory(categoryId) {
            isEditMode = true;
            const category = categoriesData.find(c => c.id == categoryId);
            if (!category) return;
            
            document.getElementById('modalTitle').textContent = 'Sửa danh mục';
            document.getElementById('category_id').value = category.id;
            document.getElementById('name').value = category.name;
            document.getElementById('status').value = category.status;
            document.getElementById('sort_order').value = category.sort_order;
            
            document.getElementById('categoryModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('categoryModal').style.display = 'none';
        }
        
        function saveCategory() {
            const form = document.getElementById('categoryForm');
            const formData = new FormData();
            
            if (isEditMode) {
                formData.append('action', 'update_category');
                formData.append('category_id', document.getElementById('category_id').value);
            } else {
                formData.append('action', 'add_category');
            }
            
            formData.append('name', document.getElementById('name').value);
            formData.append('status', document.getElementById('status').value);
            formData.append('sort_order', document.getElementById('sort_order').value);
            
            fetch('categories.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Có lỗi xảy ra', 'error');
            });
        }
        
        function deleteCategory(categoryId) {
            if (!confirm('Bạn có chắc chắn muốn xóa danh mục này? Hành động này không thể hoàn tác.')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_category');
            formData.append('category_id', categoryId);
            
            fetch('categories.php', {
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
                showNotification('Có lỗi xảy ra khi xóa', 'error');
            });
        }
        
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
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('categoryModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>
