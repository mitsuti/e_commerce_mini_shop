<?php
/**
 * TRANG DANH SÁCH YÊU THÍCH
 * Trang hiển thị danh sách sản phẩm yêu thích của người dùng
 */

require_once 'config/config.php';

// Kiểm tra đăng nhập
if (!isLoggedIn()) {
    header('Location: login.php?redirect=wishlist.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Xử lý thêm/xóa sản phẩm khỏi wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action']) && isset($_POST['product_id'])) {
        $product_id = intval($_POST['product_id']);
        $action = $_POST['action'];
        
        if ($action === 'add') {
            // Kiểm tra xem sản phẩm đã có trong wishlist chưa
            $exists = fetchOne("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?", [$user_id, $product_id]);
            
            if (!$exists) {
                executeQuery("INSERT INTO wishlist (user_id, product_id, created_at) VALUES (?, ?, NOW())", [$user_id, $product_id]);
                echo json_encode(['success' => true, 'message' => 'Đã thêm sản phẩm vào danh sách yêu thích!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Sản phẩm đã có trong danh sách yêu thích!']);
            }
        } elseif ($action === 'remove') {
            executeQuery("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?", [$user_id, $product_id]);
            echo json_encode(['success' => true, 'message' => 'Đã xóa sản phẩm khỏi danh sách yêu thích!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Thiếu thông tin cần thiết']);
    }
    exit;
}

// Xử lý GET request cho get_count
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_count') {
    header('Content-Type: application/json');
    $count = fetchOne("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?", [$user_id]);
    echo json_encode(['success' => true, 'count' => $count['count']]);
    exit;
}

// Lấy danh sách sản phẩm yêu thích
$wishlist_items = fetchData("
    SELECT w.*, 
           p.*, 
           pi.image_path,
           c.name as category_name,
           COALESCE((SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND status = 'approved'), 0) as avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE product_id = p.id AND status = 'approved') as review_count
    FROM wishlist w
    JOIN products p ON w.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE w.user_id = ? AND p.status = 'active'
    ORDER BY w.created_at DESC
", [$user_id]);

$total_items = count($wishlist_items);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách yêu thích - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Xem danh sách sản phẩm yêu thích của bạn.">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    
    <!-- Custom CSS for wishlist page -->
    <style>
        .wishlist-page {
            padding: var(--space-8) 0;
            min-height: 60vh;
        }
        
        .wishlist-header {
            background: var(--white);
            padding: var(--space-8) 0;
            margin-bottom: var(--space-8);
            box-shadow: var(--shadow-sm);
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            margin-bottom: var(--space-4);
            font-size: var(--text-sm);
        }
        
        .breadcrumb a {
            color: var(--gray-600);
            text-decoration: none;
            transition: color var(--transition-fast);
        }
        
        .breadcrumb a:hover {
            color: var(--primary-color);
        }
        
        .breadcrumb-separator {
            color: var(--gray-400);
        }
        
        .page-title {
            font-size: var(--text-4xl);
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: var(--space-2);
        }
        
        .page-subtitle {
            color: var(--gray-600);
            font-size: var(--text-lg);
        }
        
        .wishlist-stats {
            display: flex;
            align-items: center;
            gap: var(--space-6);
            margin-bottom: var(--space-8);
            padding: var(--space-6);
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-sm);
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: var(--text-xl);
        }
        
        .stat-content h3 {
            font-size: var(--text-2xl);
            font-weight: 700;
            color: var(--gray-900);
            margin: 0;
        }
        
        .stat-content p {
            color: var(--gray-600);
            margin: 0;
            font-size: var(--text-sm);
        }
        
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: var(--space-6);
        }
        
        .wishlist-item {
            background: var(--white);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all var(--transition-fast);
            position: relative;
        }
        
        .wishlist-item:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .wishlist-item-image {
            position: relative;
            height: 250px;
            overflow: hidden;
        }
        
        .wishlist-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform var(--transition-normal);
        }
        
        .wishlist-item:hover .wishlist-item-image img {
            transform: scale(1.05);
        }
        
        .wishlist-item-badge {
            position: absolute;
            top: var(--space-3);
            right: var(--space-3);
            background: var(--error-color);
            color: var(--white);
            padding: var(--space-1) var(--space-3);
            border-radius: var(--radius-full);
            font-size: var(--text-xs);
            font-weight: 600;
        }
        
        .wishlist-item-actions {
            position: absolute;
            top: var(--space-3);
            left: var(--space-3);
            display: flex;
            gap: var(--space-2);
        }
        
        .wishlist-action-btn {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-700);
            cursor: pointer;
            transition: all var(--transition-fast);
            backdrop-filter: blur(10px);
        }
        
        .wishlist-action-btn:hover {
            background: var(--white);
            color: var(--primary-color);
            transform: scale(1.1);
        }
        
        .wishlist-action-btn.remove {
            color: var(--error-color);
        }
        
        .wishlist-action-btn.remove:hover {
            background: var(--error-color);
            color: var(--white);
        }
        
        .wishlist-item-content {
            padding: var(--space-6);
        }
        
        .wishlist-item-category {
            color: var(--primary-color);
            font-size: var(--text-sm);
            font-weight: 500;
            margin-bottom: var(--space-2);
        }
        
        .wishlist-item-title {
            font-size: var(--text-lg);
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--space-3);
            line-height: 1.4;
        }
        
        .wishlist-item-title a {
            color: inherit;
            text-decoration: none;
            transition: color var(--transition-fast);
        }
        
        .wishlist-item-title a:hover {
            color: var(--primary-color);
        }
        
        .wishlist-item-price {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            margin-bottom: var(--space-4);
        }
        
        .wishlist-item-price-current {
            font-size: var(--text-xl);
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .wishlist-item-price-original {
            font-size: var(--text-base);
            color: var(--gray-500);
            text-decoration: line-through;
        }
        
        .wishlist-item-rating {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            margin-bottom: var(--space-4);
        }
        
        .wishlist-item-stars {
            display: flex;
            gap: 2px;
        }
        
        .wishlist-item-stars .star {
            color: var(--warning-color);
            font-size: var(--text-sm);
        }
        
        .wishlist-item-stars .star.empty {
            color: var(--gray-300);
        }
        
        .wishlist-item-reviews {
            color: var(--gray-500);
            font-size: var(--text-sm);
        }
        
        .wishlist-item-buttons {
            display: flex;
            gap: var(--space-3);
        }
        
        .wishlist-item-btn {
            flex: 1;
            padding: var(--space-3) var(--space-4);
            border: none;
            border-radius: var(--radius-lg);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-2);
        }
        
        .wishlist-item-btn.primary {
            background: var(--primary-color);
            color: var(--white);
        }
        
        .wishlist-item-btn.primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .wishlist-item-btn.secondary {
            background: var(--gray-100);
            color: var(--gray-700);
        }
        
        .wishlist-item-btn.secondary:hover {
            background: var(--gray-200);
        }
        
        .empty-wishlist {
            text-align: center;
            padding: var(--space-16) var(--space-8);
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-sm);
        }
        
        .empty-wishlist-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--gray-100), var(--gray-200));
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-6);
            color: var(--gray-400);
            font-size: var(--text-5xl);
        }
        
        .empty-wishlist h3 {
            font-size: var(--text-2xl);
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: var(--space-3);
        }
        
        .empty-wishlist p {
            color: var(--gray-500);
            margin-bottom: var(--space-8);
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .empty-wishlist .btn {
            padding: var(--space-4) var(--space-8);
            background: var(--primary-color);
            color: var(--white);
            text-decoration: none;
            border-radius: var(--radius-lg);
            font-weight: 600;
            transition: all var(--transition-fast);
            display: inline-flex;
            align-items: center;
            gap: var(--space-2);
        }
        
        .empty-wishlist .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .wishlist-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: var(--space-4);
            }
            
            .wishlist-stats {
                flex-direction: column;
                text-align: center;
                gap: var(--space-4);
            }
            
            .page-title {
                font-size: var(--text-3xl);
            }
        }
        
        @media (max-width: 480px) {
            .wishlist-grid {
                grid-template-columns: 1fr;
            }
            
            .wishlist-item-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <div class="wishlist-page">
        <!-- Page Header -->
        <div class="wishlist-header">
            <div class="container">
                <!-- Breadcrumb -->
                <nav class="breadcrumb">
                    <a href="index.php">Trang chủ</a>
                    <span class="breadcrumb-separator">/</span>
                    <span>Danh sách yêu thích</span>
                </nav>
                
                <!-- Page Title -->
                <h1 class="page-title">Danh sách yêu thích</h1>
                <p class="page-subtitle">Các sản phẩm bạn đã thêm vào danh sách yêu thích</p>
            </div>
        </div>
        
        <div class="container">
            <!-- Success/Error Messages -->
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Wishlist Stats -->
            <div class="wishlist-stats">
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $total_items; ?></h3>
                        <p>Sản phẩm yêu thích</p>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="stat-content">
                        <h3>0</h3>
                        <p>Đã thêm vào giỏ</p>
                    </div>
                </div>
                
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-content">
                        <h3><?php echo $total_items; ?></h3>
                        <p>Đã xem gần đây</p>
                    </div>
                </div>
            </div>
            
            <!-- Wishlist Items -->
            <?php if (!empty($wishlist_items)): ?>
                <div class="wishlist-grid">
                    <?php foreach ($wishlist_items as $item): ?>
                        <div class="wishlist-item" data-product-id="<?php echo $item['product_id']; ?>">
                            <div class="wishlist-item-image">
                                <img src="<?php echo $item['image_path'] ?: 'assets/images/placeholder.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     loading="lazy">
                                
                                <?php if ($item['sale_price'] && $item['sale_price'] < $item['price']): ?>
                                    <div class="wishlist-item-badge">Sale</div>
                                <?php endif; ?>
                                
                                <div class="wishlist-item-actions">
                                    <button class="wishlist-action-btn remove" 
                                            onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)"
                                            title="Xóa khỏi danh sách yêu thích">
                                        <i class="fas fa-heart-broken"></i>
                                    </button>
                                    <button class="wishlist-action-btn" 
                                            onclick="quickView(<?php echo $item['product_id']; ?>)"
                                            title="Xem nhanh">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="wishlist-item-content">
                                <div class="wishlist-item-category">
                                    <?php echo htmlspecialchars($item['category_name']); ?>
                                </div>
                                
                                <h3 class="wishlist-item-title">
                                    <a href="product.php?id=<?php echo $item['product_id']; ?>">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                </h3>
                                
                                <div class="wishlist-item-price">
                                    <span class="wishlist-item-price-current">
                                        <?php echo formatPrice($item['sale_price'] ?: $item['price']); ?>
                                    </span>
                                    <?php if ($item['sale_price'] && $item['sale_price'] < $item['price']): ?>
                                        <span class="wishlist-item-price-original">
                                            <?php echo formatPrice($item['price']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="wishlist-item-rating">
                                    <div class="wishlist-item-stars">
                                        <?php
                                        $rating = round($item['avg_rating']);
                                        for ($i = 1; $i <= 5; $i++):
                                        ?>
                                            <span class="star <?php echo $i <= $rating ? '' : 'empty'; ?>">
                                                <i class="fas fa-star"></i>
                                            </span>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="wishlist-item-reviews">
                                        (<?php echo $item['review_count']; ?> đánh giá)
                                    </span>
                                </div>
                                
                                <div class="wishlist-item-buttons">
                                    <a href="product.php?id=<?php echo $item['product_id']; ?>" 
                                       class="wishlist-item-btn primary">
                                        <i class="fas fa-eye"></i>
                                        Xem chi tiết
                                    </a>
                                    <button class="wishlist-item-btn secondary add-to-cart-btn"
                                            data-product-id="<?php echo $item['product_id']; ?>"
                                            data-product-data='<?php echo json_encode($item); ?>'>
                                        <i class="fas fa-shopping-cart"></i>
                                        Thêm vào giỏ
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-wishlist">
                    <div class="empty-wishlist-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Danh sách yêu thích trống</h3>
                    <p>Bạn chưa có sản phẩm nào trong danh sách yêu thích. Hãy khám phá và thêm những sản phẩm bạn thích!</p>
                    <a href="products.php" class="btn">
                        <i class="fas fa-shopping-bag"></i>
                        Khám phá sản phẩm
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- JavaScript -->
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        // Remove from wishlist
        function removeFromWishlist(productId) {
            if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi danh sách yêu thích?')) {
                // Send AJAX request
                fetch('wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remove&product_id=${productId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove item from DOM
                        const item = document.querySelector(`[data-product-id="${productId}"]`);
                        if (item) {
                            item.remove();
                        }
                        
                        // Update wishlist counter
                        updateWishlistCounter();
                        
                        // Show success message
                        showToast(data.message, 'success');
                        
                        // If no items left, show empty state
                        const itemsList = document.querySelector('.wishlist-items');
                        if (itemsList && itemsList.children.length === 0) {
                            location.reload();
                        }
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Có lỗi xảy ra khi xóa sản phẩm', 'error');
                });
            }
        }
        
        // Quick view function
        function quickView(productId) {
            window.location.href = `product.php?id=${productId}`;
        }
        
        // Không gắn listener tại đây để tránh trùng với listener chung trong assets/js/main.js
        
        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, 3000);
        }
    </script>
</body>
</html>
