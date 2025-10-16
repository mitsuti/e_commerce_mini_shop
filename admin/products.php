<?php
require_once '../config/config.php';
if (!isAdmin()) { redirect('../index.php'); }

// Xử lý AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = $action === 'edit' ? intval($_POST['id']) : null;
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $sale_price = floatval($_POST['sale_price']);
        $stock_quantity = intval($_POST['stock_quantity']);
        $category_id = intval($_POST['category_id']);
        $status = $_POST['status'];
        
        if ($action === 'add') {
            // Tạo slug từ tên
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-');
            
            executeQuery("INSERT INTO products (name, slug, description, price, sale_price, stock_quantity, category_id, status, created_at) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())", 
                         [$name, $slug, $description, $price, $sale_price, $stock_quantity, $category_id, $status]);
            
            // Lấy ID sản phẩm vừa tạo
            $product_id = fetchOne("SELECT LAST_INSERT_ID() as id")['id'];
            
            // Upload và lưu ảnh chính
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === 0) {
                $main_image = uploadFile($_FILES['main_image'], 'products');
                if ($main_image) {
                    executeQuery("INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, 1, 0)", 
                                 [$product_id, $main_image]);
                }
            }
            
            // Upload và lưu ảnh mô tả
            if (isset($_FILES['description_images']) && is_array($_FILES['description_images']['name'])) {
                $sort_order = 1;
                for ($i = 0; $i < count($_FILES['description_images']['name']); $i++) {
                    if ($_FILES['description_images']['error'][$i] === 0) {
                        $file = [
                            'name' => $_FILES['description_images']['name'][$i],
                            'type' => $_FILES['description_images']['type'][$i],
                            'tmp_name' => $_FILES['description_images']['tmp_name'][$i],
                            'error' => $_FILES['description_images']['error'][$i],
                            'size' => $_FILES['description_images']['size'][$i]
                        ];
                        $uploaded = uploadFile($file, 'products');
                        if ($uploaded) {
                            executeQuery("INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, 0, ?)", 
                                         [$product_id, $uploaded, $sort_order++]);
                        }
                    }
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Thêm sản phẩm thành công']);
        } else {
            // Tạo slug từ tên
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $slug = preg_replace('/-+/', '-', $slug);
            $slug = trim($slug, '-');
            
            executeQuery("UPDATE products SET name = ?, slug = ?, description = ?, price = ?, sale_price = ?, stock_quantity = ?, category_id = ?, status = ? WHERE id = ?", 
                         [$name, $slug, $description, $price, $sale_price, $stock_quantity, $category_id, $status, $id]);
            
            // Xóa ảnh cũ nếu có ảnh mới
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === 0) {
                // Xóa ảnh chính cũ
                executeQuery("DELETE FROM product_images WHERE product_id = ? AND is_primary = 1", [$id]);
                
                // Thêm ảnh chính mới
                $main_image = uploadFile($_FILES['main_image'], 'products');
                if ($main_image) {
                    executeQuery("INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, 1, 0)", 
                                 [$id, $main_image]);
                }
            }
            
            // Xóa và thêm lại ảnh mô tả nếu có
            if (isset($_FILES['description_images']) && is_array($_FILES['description_images']['name'])) {
                // Xóa ảnh mô tả cũ
                executeQuery("DELETE FROM product_images WHERE product_id = ? AND is_primary = 0", [$id]);
                
                $sort_order = 1;
                for ($i = 0; $i < count($_FILES['description_images']['name']); $i++) {
                    if ($_FILES['description_images']['error'][$i] === 0) {
                        $file = [
                            'name' => $_FILES['description_images']['name'][$i],
                            'type' => $_FILES['description_images']['type'][$i],
                            'tmp_name' => $_FILES['description_images']['tmp_name'][$i],
                            'error' => $_FILES['description_images']['error'][$i],
                            'size' => $_FILES['description_images']['size'][$i]
                        ];
                        $uploaded = uploadFile($file, 'products');
                        if ($uploaded) {
                            executeQuery("INSERT INTO product_images (product_id, image_path, is_primary, sort_order) VALUES (?, ?, 0, ?)", 
                                         [$id, $uploaded, $sort_order++]);
                        }
                    }
                }
            }
            
            echo json_encode(['success' => true, 'message' => 'Cập nhật sản phẩm thành công']);
        }
        exit;
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        
        // Xóa ảnh sản phẩm trước
        $images = fetchData("SELECT image_path FROM product_images WHERE product_id = ?", [$id]);
        foreach ($images as $img) {
            if ($img['image_path'] && file_exists('../' . $img['image_path'])) {
                unlink('../' . $img['image_path']);
            }
        }
        executeQuery("DELETE FROM product_images WHERE product_id = ?", [$id]);
        
        // Xóa sản phẩm
        executeQuery("DELETE FROM products WHERE id = ?", [$id]);
        echo json_encode(['success' => true, 'message' => 'Xóa sản phẩm thành công']);
        exit;
    }
    
    if ($action === 'get') {
        $id = intval($_POST['id']);
        $product = fetchOne("SELECT * FROM products WHERE id = ?", [$id]);
        if ($product) {
            // Lấy ảnh sản phẩm
            $images = fetchData("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC", [$id]);
            $product['images'] = $images;
            echo json_encode(['success' => true, 'product' => $product]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm']);
        }
        exit;
    }
}

// Lấy dữ liệu
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20; $offset = ($page - 1) * $limit;
$totalRow = fetchOne("SELECT COUNT(*) AS c FROM products", []);
$total = (int)($totalRow['c'] ?? 0); $pages = max(1, (int)ceil($total / $limit));

$rows = fetchData("SELECT p.id, p.name, p.price, p.sale_price, p.stock_quantity, p.status, c.name AS category,
                   (SELECT image_path FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) AS main_image
                   FROM products p LEFT JOIN categories c ON p.category_id = c.id
                   ORDER BY p.id DESC LIMIT $limit OFFSET $offset", []);

$categories = fetchData("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name", []);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm - <?php echo SITE_NAME; ?></title>
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
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 5% auto; padding: 0; width: 90%; max-width: 800px; border-radius: 12px; overflow: hidden; }
        .modal-header { padding: 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .modal-title { font-size: 20px; font-weight: 600; color: #1e293b; }
        .close { background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; }
        .modal-body { padding: 24px; max-height: 70vh; overflow-y: auto; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 500; color: #374151; }
        .form-input, .form-select, .form-textarea { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 14px; }
        .form-textarea { height: 100px; resize: vertical; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .file-upload { border: 2px dashed #d1d5db; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.2s; }
        .file-upload:hover { border-color: #6366f1; background: #f8fafc; }
        .file-upload.dragover { border-color: #6366f1; background: #eff6ff; }
        .file-preview { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 12px; margin-top: 12px; }
        .file-preview-item { position: relative; border-radius: 8px; overflow: hidden; }
        .file-preview-item img { width: 100%; height: 80px; object-fit: cover; }
        .file-preview-item .remove { position: absolute; top: 4px; right: 4px; background: #ef4444; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer; font-size: 12px; }
        .btn-group { display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }
        .product-image { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .form-row { grid-template-columns: 1fr; }
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
                    <a href="products.php" class="nav-link active">
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
                <h1>Quản lý sản phẩm</h1>
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
                        <div class="stat-label">Tổng sản phẩm</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format(fetchOne("SELECT COUNT(*) as c FROM products WHERE status = 'active'")['c']); ?></div>
                        <div class="stat-label">Đang bán</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format(fetchOne("SELECT COUNT(*) as c FROM products WHERE stock_quantity <= 5")['c']); ?></div>
                        <div class="stat-label">Sắp hết hàng</div>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">Danh sách sản phẩm</div>
                        <a href="#" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Thêm sản phẩm
                        </a>
                    </div>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Hình ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th>Danh mục</th>
                                <th>Giá</th>
                                <th>Tồn kho</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r): ?>
                            <tr>
                                <td>
                                    <?php if ($r['main_image']): ?>
                                        <img src="../<?php echo htmlspecialchars($r['main_image']); ?>" alt="" class="product-image">
                                    <?php else: ?>
                                        <div class="product-image" style="background: #f3f4f6; display: flex; align-items: center; justify-content: center; color: #9ca3af;">
                                            <i class="fas fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($r['name']); ?></td>
                                <td><?php echo htmlspecialchars($r['category'] ?? '—'); ?></td>
                                <td><?php echo formatPrice($r['sale_price'] ?: $r['price']); ?></td>
                                <td><?php echo (int)$r['stock_quantity']; ?></td>
                                <td>
                                    <span class="badge <?php echo $r['status'] === 'active' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo htmlspecialchars($r['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick="editProduct(<?php echo $r['id']; ?>)" class="btn" style="padding: 6px 12px; background: #f1f5f9; color: #475569; margin-right: 8px;">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteProduct(<?php echo $r['id']; ?>)" class="btn" style="padding: 6px 12px; background: #fee2e2; color: #991b1b;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <?php for($i=1;$i<=$pages;$i++): ?>
                        <a class="<?php echo $i==$page?'active':''; ?>" href="products.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Thêm/Sửa Sản phẩm -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Thêm sản phẩm mới</h3>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="productForm" enctype="multipart/form-data">
                    <input type="hidden" id="productId" name="id">
                    <input type="hidden" name="action" id="formAction" value="add">
                    
                    <div class="form-group">
                        <label class="form-label">Tên sản phẩm *</label>
                        <input type="text" name="name" id="productName" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Mô tả</label>
                        <textarea name="description" id="productDescription" class="form-textarea" placeholder="Mô tả chi tiết sản phẩm..."></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Giá gốc (₫) *</label>
                            <input type="number" name="price" id="productPrice" class="form-input" min="0" step="1000" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Giá khuyến mãi (₫)</label>
                            <input type="number" name="sale_price" id="productSalePrice" class="form-input" min="0" step="1000">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Số lượng tồn kho *</label>
                            <input type="number" name="stock_quantity" id="productStock" class="form-input" min="0" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Danh mục *</label>
                            <select name="category_id" id="productCategory" class="form-select" required>
                                <option value="">Chọn danh mục</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Trạng thái *</label>
                        <select name="status" id="productStatus" class="form-select" required>
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Tạm dừng</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Ảnh chính *</label>
                        <div class="file-upload" onclick="document.getElementById('mainImageInput').click()">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 24px; color: #6b7280; margin-bottom: 8px;"></i>
                            <p>Nhấn để chọn ảnh chính hoặc kéo thả vào đây</p>
                            <input type="file" id="mainImageInput" name="main_image" accept="image/*" style="display: none;">
                        </div>
                        <div id="mainImagePreview" class="file-preview"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Ảnh mô tả</label>
                        <div class="file-upload" onclick="document.getElementById('descriptionImagesInput').click()">
                            <i class="fas fa-images" style="font-size: 24px; color: #6b7280; margin-bottom: 8px;"></i>
                            <p>Nhấn để chọn nhiều ảnh mô tả hoặc kéo thả vào đây</p>
                            <input type="file" id="descriptionImagesInput" name="description_images[]" accept="image/*" multiple style="display: none;">
                        </div>
                        <div id="descriptionImagesPreview" class="file-preview"></div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" onclick="closeModal()">Hủy</button>
                        <button type="submit" class="btn btn-primary">Lưu sản phẩm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        // Modal functions
        function openModal() {
            document.getElementById('productModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            resetForm();
        }
        
        function resetForm() {
            document.getElementById('productForm').reset();
            document.getElementById('modalTitle').textContent = 'Thêm sản phẩm mới';
            document.getElementById('formAction').value = 'add';
            document.getElementById('productId').value = '';
            document.getElementById('mainImagePreview').innerHTML = '';
            document.getElementById('descriptionImagesPreview').innerHTML = '';
        }
        
        // Add product
        document.querySelector('.btn-primary').addEventListener('click', function(e) {
            e.preventDefault();
            openModal();
        });
        
        // Edit product
        function editProduct(id) {
            // Fetch product data and populate form
            fetch('products.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get&id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('modalTitle').textContent = 'Sửa sản phẩm';
                    document.getElementById('formAction').value = 'edit';
                    document.getElementById('productId').value = data.product.id;
                    document.getElementById('productName').value = data.product.name;
                    document.getElementById('productDescription').value = data.product.description || '';
                    document.getElementById('productPrice').value = data.product.price;
                    document.getElementById('productSalePrice').value = data.product.sale_price || '';
                    document.getElementById('productStock').value = data.product.stock_quantity;
                    document.getElementById('productCategory').value = data.product.category_id;
                    document.getElementById('productStatus').value = data.product.status;
                    
                    // Show existing images
                    if (data.product.images) {
                        const mainImage = data.product.images.find(img => img.is_primary == 1);
                        const descriptionImages = data.product.images.filter(img => img.is_primary == 0);
                        
                        if (mainImage) {
                            showMainImagePreview('../' + mainImage.image_path);
                        }
                        if (descriptionImages.length > 0) {
                            showDescriptionImagesPreview(descriptionImages.map(img => '../' + img.image_path));
                        }
                    }
                    
                    openModal();
                }
            });
        }
        
        // Delete product
        function deleteProduct(id) {
            if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                fetch('products.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Lỗi: ' + data.message);
                    }
                });
            }
        }
        
        // File upload previews
        document.getElementById('mainImageInput').addEventListener('change', function(e) {
            if (e.target.files[0]) {
                showMainImagePreview(URL.createObjectURL(e.target.files[0]));
            }
        });
        
        document.getElementById('descriptionImagesInput').addEventListener('change', function(e) {
            const files = Array.from(e.target.files);
            showDescriptionImagesPreview(files.map(file => URL.createObjectURL(file)));
        });
        
        function showMainImagePreview(src) {
            const preview = document.getElementById('mainImagePreview');
            preview.innerHTML = `
                <div class="file-preview-item">
                    <img src="${src}" alt="Preview">
                    <button type="button" class="remove" onclick="removeMainImage()">&times;</button>
                </div>
            `;
        }
        
        function showDescriptionImagesPreview(images) {
            const preview = document.getElementById('descriptionImagesPreview');
            preview.innerHTML = images.map((src, index) => `
                <div class="file-preview-item">
                    <img src="${src}" alt="Preview ${index + 1}">
                    <button type="button" class="remove" onclick="removeDescriptionImage(${index})">&times;</button>
                </div>
            `).join('');
        }
        
        function removeMainImage() {
            document.getElementById('mainImageInput').value = '';
            document.getElementById('mainImagePreview').innerHTML = '';
        }
        
        function removeDescriptionImage(index) {
            // This would need more complex handling for multiple files
            document.getElementById('descriptionImagesPreview').innerHTML = '';
            document.getElementById('descriptionImagesInput').value = '';
        }
        
        // Form submission
        document.getElementById('productForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('products.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeModal();
                    location.reload();
                } else {
                    alert('Lỗi: ' + data.message);
                }
            });
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('productModal');
            if (e.target === modal) {
                closeModal();
            }
        });
    </script>
</body>
</html>
