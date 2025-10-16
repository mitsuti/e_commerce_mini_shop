<?php
/**
 * TRANG GIỎ HÀNG
 * Trang hiển thị và quản lý giỏ hàng
 */

require_once 'config/config.php';

// Khởi tạo session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Xử lý AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);

    // Chỉ validate product khi hành động cần product_id
    $actionsRequireProduct = ['add', 'remove', 'update'];
    if (in_array($action, $actionsRequireProduct, true)) {
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
            exit;
        }
        // Với action 'add' cần xác thực sản phẩm tồn tại để lấy giá
        if ($action === 'add') {
            $product = fetchOne("SELECT * FROM products WHERE id = ? AND status = 'active'", [$product_id]);
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
                exit;
            }
        }
    }
    
    // Lấy user_id và session_id
    $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
    $session_id = session_id();
    
    switch ($action) {
        case 'add':
            // Kiểm tra xem sản phẩm đã có trong giỏ hàng chưa
            $existing_cart = fetchOne("
                SELECT * FROM cart 
                WHERE product_id = ? AND (
                    (user_id = ? AND user_id IS NOT NULL) OR 
                    (session_id = ? AND user_id IS NULL)
                )
            ", [$product_id, $user_id, $session_id]);
            
            if ($existing_cart) {
                // Cập nhật số lượng
                executeQuery("
                    UPDATE cart 
                    SET quantity = quantity + ?, updated_at = NOW() 
                    WHERE id = ?
                ", [$quantity, $existing_cart['id']]);
            } else {
                // Thêm mới vào giỏ hàng
                executeQuery("
                    INSERT INTO cart (user_id, session_id, product_id, quantity, price, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ", [$user_id, $session_id, $product_id, $quantity, $product['sale_price'] ?: $product['price']]);
            }
            
            // Xóa sản phẩm khỏi wishlist nếu user đã đăng nhập
            if ($user_id) {
                executeQuery("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?", [$user_id, $product_id]);
            }
            
            echo json_encode(['success' => true, 'message' => 'Đã thêm vào giỏ hàng']);
            break;
            
        case 'remove':
            $deleted = executeQuery("
                DELETE FROM cart 
                WHERE product_id = ? AND (
                    (user_id = ? AND user_id IS NOT NULL) OR 
                    (session_id = ? AND user_id IS NULL)
                )
            ", [$product_id, $user_id, $session_id]);
            
            if ($deleted) {
                echo json_encode(['success' => true, 'message' => 'Đã xóa khỏi giỏ hàng']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Sản phẩm không có trong giỏ hàng']);
            }
            break;
            
        case 'update':
            $updated = executeQuery("
                UPDATE cart 
                SET quantity = ?, updated_at = NOW() 
                WHERE product_id = ? AND (
                    (user_id = ? AND user_id IS NOT NULL) OR 
                    (session_id = ? AND user_id IS NULL)
                )
            ", [$quantity, $product_id, $user_id, $session_id]);
            
            if ($updated) {
                echo json_encode(['success' => true, 'message' => 'Đã cập nhật số lượng']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Sản phẩm không có trong giỏ hàng']);
            }
            break;
            
        case 'clear':
            executeQuery("
                DELETE FROM cart 
                WHERE (user_id = ? AND user_id IS NOT NULL) OR 
                      (session_id = ? AND user_id IS NULL)
            ", [$user_id, $session_id]);
            echo json_encode(['success' => true, 'message' => 'Đã xóa tất cả sản phẩm khỏi giỏ hàng']);
            break;
            
        case 'get_count':
            $count = fetchOne("
                SELECT COALESCE(SUM(quantity), 0) as count 
                FROM cart 
                WHERE (user_id = ? AND user_id IS NOT NULL) OR 
                      (session_id = ? AND user_id IS NULL)
            ", [$user_id, $session_id]);
            echo json_encode(['success' => true, 'count' => $count['count']]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
    }
    exit;
}

// Xử lý GET request cho get_count
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_count') {
    header('Content-Type: application/json');
    $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
    $session_id = session_id();
    
    $count = fetchOne("
        SELECT COALESCE(SUM(quantity), 0) as count 
        FROM cart 
        WHERE (user_id = ? AND user_id IS NOT NULL) OR 
              (session_id = ? AND user_id IS NULL)
    ", [$user_id, $session_id]);
    
    echo json_encode(['success' => true, 'count' => $count['count']]);
    exit;
}

// Fallback: Xóa tất cả bằng GET (dùng khi JS bị chặn hoặc lỗi)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'clear_all') {
    $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
    $session_id = session_id();
    executeQuery(
        "DELETE FROM cart WHERE (user_id = ? AND user_id IS NOT NULL) OR (session_id = ? AND user_id IS NULL)",
        [$user_id, $session_id]
    );
    header('Location: cart.php');
    exit;
}

// Lấy dữ liệu giỏ hàng từ database
$cart_items = [];
$cart_total = 0;
$cart_count = 0;

// Lấy user_id (nếu đã đăng nhập) hoặc session_id
$user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
$session_id = session_id();

// Lấy sản phẩm trong giỏ hàng từ database (dùng alias để tránh trùng tên cột)
$cart_products = fetchData("
    SELECT 
        c.id                AS cart_id,
        c.product_id        AS cart_product_id,
        c.quantity          AS cart_quantity,
        c.price             AS cart_price,
        p.name              AS product_name,
        p.slug              AS product_slug,
        COALESCE(NULLIF(p.sale_price, 0), p.price) AS product_unit_price,
        p.stock_quantity    AS product_stock_quantity,
        pi.image_path       AS product_image_path
    FROM cart c
    LEFT JOIN products p ON c.product_id = p.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE p.status = 'active' AND (
        (c.user_id = ? AND c.user_id IS NOT NULL) OR 
        (c.session_id = ? AND c.user_id IS NULL)
    )
    ORDER BY c.created_at DESC
", [$user_id, $session_id]);

foreach ($cart_products as $cart_item) {
    $unitPrice = (float)($cart_item['product_unit_price'] ?? $cart_item['cart_price'] ?? 0);
    $quantity  = (int)($cart_item['cart_quantity'] ?? 0);
    $stockQty  = (int)($cart_item['product_stock_quantity'] ?? 0);
    $imagePath = !empty($cart_item['product_image_path']) ? $cart_item['product_image_path'] : 'assets/images/placeholder.jpg';
    $name      = (string)($cart_item['product_name'] ?? '');
    $slug      = (string)($cart_item['product_slug'] ?? '');

    // Fallback: nếu thiếu dữ liệu quan trọng, lấy trực tiếp từ bảng products
    if ($name === '' || $unitPrice <= 0 || $stockQty <= 0 || $imagePath === 'assets/images/placeholder.jpg') {
        $p = fetchOne("SELECT name, slug, price, sale_price, stock_quantity FROM products WHERE id = ?", [$cart_item['cart_product_id']]);
        if ($p) {
            if ($name === '') $name = $p['name'];
            if ($slug === '') $slug = $p['slug'];
            if ($unitPrice <= 0) $unitPrice = (float)($p['sale_price'] ?: $p['price']);
            if ($stockQty <= 0) $stockQty = (int)$p['stock_quantity'];
        }
    }

    $item = [
        'product_id'    => (int)$cart_item['cart_product_id'],
        'quantity'      => max($quantity, 1),
        'price'         => $unitPrice,
        'name'          => $name,
        'image'         => $imagePath,
        'slug'          => $slug,
        'stock_quantity'=> max($stockQty, 1),
        'max_quantity'  => min(max($quantity, 1), max($stockQty, 1)),
        'subtotal'      => $unitPrice * max($quantity, 1)
    ];

    $cart_items[] = $item;
    $cart_total += $item['subtotal'];
    $cart_count += $item['quantity'];
}

// Tính phí vận chuyển (0 nếu giỏ hàng trống)
$shipping_fee = $cart_count > 0 ? ($cart_total >= 500000 ? 0 : 30000) : 0;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Xem và quản lý giỏ hàng của bạn. Cập nhật số lượng, xóa sản phẩm và tiến hành thanh toán.">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    
    <!-- Custom CSS for cart page -->
    <style>
        .cart-page {
            padding: var(--space-8) 0;
            min-height: 60vh;
        }
        
        .cart-header {
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
        
        .cart-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: var(--space-8);
        }
        
        .cart-items {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--space-6);
            box-shadow: var(--shadow-md);
        }
        
        .cart-items-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-6);
        }
        
        .cart-items-title {
            font-size: var(--text-xl);
            font-weight: 600;
            color: var(--gray-900);
        }
        
        .cart-item {
            display: flex;
            gap: var(--space-4);
            padding: var(--space-6);
            border-bottom: 1px solid var(--gray-200);
            transition: all var(--transition-fast);
        }
        
        .cart-item:last-child {
            border-bottom: none;
        }
        
        .cart-item:hover {
            background: var(--gray-50);
        }
        
        .cart-item-image {
            width: 120px;
            height: 120px;
            border-radius: var(--radius-lg);
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .cart-item-details {
            flex: 1;
        }
        
        .cart-item-name {
            font-size: var(--text-lg);
            font-weight: 600;
            margin-bottom: var(--space-2);
            color: var(--gray-900);
        }
        
        .cart-item-name a {
            color: inherit;
            text-decoration: none;
            transition: color var(--transition-fast);
        }
        
        .cart-item-name a:hover {
            color: var(--primary-color);
        }
        
        .cart-item-price {
            font-size: var(--text-xl);
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: var(--space-4);
        }
        
        .cart-item-actions {
            display: flex;
            gap: var(--space-4);
            align-items: center;
        }
        
        .quantity-controls {
            display: flex;
            align-items: center;
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-md);
            overflow: hidden;
        }
        
        .quantity-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: var(--gray-100);
            color: var(--gray-700);
            cursor: pointer;
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-btn:hover {
            background: var(--gray-200);
        }
        
        .quantity-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .quantity-input {
            width: 60px;
            height: 40px;
            border: none;
            text-align: center;
            font-weight: 600;
            background: var(--white);
        }
        
        .quantity-input:focus {
            outline: none;
        }
        
        .remove-btn {
            background: var(--error-color);
            color: var(--white);
            border: none;
            padding: var(--space-2) var(--space-4);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition-fast);
            font-weight: 600;
        }
        
        .remove-btn:hover {
            background: var(--error-dark);
            transform: translateY(-1px);
        }
        
        .cart-item-total {
            font-size: var(--text-xl);
            font-weight: 700;
            color: var(--gray-900);
            text-align: right;
            min-width: 120px;
        }
        
        .cart-summary {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--space-6);
            box-shadow: var(--shadow-md);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .summary-title {
            font-size: var(--text-xl);
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: var(--space-6);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-3) 0;
            border-bottom: 1px solid var(--gray-100);
        }
        
        .summary-row:last-child {
            border-bottom: none;
        }
        
        .summary-label {
            color: var(--gray-600);
            font-weight: 500;
        }
        
        .summary-value {
            font-weight: 600;
            color: var(--gray-900);
        }
        
        .summary-total {
            font-size: var(--text-xl);
            color: var(--primary-color);
        }
        
        .coupon-section {
            margin: var(--space-6) 0;
            padding: var(--space-4);
            background: var(--gray-50);
            border-radius: var(--radius-lg);
        }
        
        .coupon-input {
            width: 100%;
            padding: var(--space-3);
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-3);
        }
        
        .coupon-btn {
            width: 100%;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            padding: var(--space-3);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition-fast);
            font-weight: 600;
        }
        
        .coupon-btn:hover {
            background: var(--primary-dark);
        }
        
        .checkout-btn {
            width: 100%;
            background: var(--primary-color);
            color: var(--white);
            border: none;
            padding: var(--space-4);
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: all var(--transition-fast);
            font-weight: 600;
            font-size: var(--text-lg);
            margin-top: var(--space-6);
        }
        
        .checkout-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .empty-cart {
            text-align: center;
            padding: var(--space-16) var(--space-8);
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-sm);
        }
        
        .empty-cart-icon {
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
        
        .empty-cart h3 {
            font-size: var(--text-2xl);
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: var(--space-3);
        }
        
        .empty-cart p {
            color: var(--gray-500);
            margin-bottom: var(--space-8);
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .empty-cart .btn {
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
        
        .empty-cart .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .cart-content {
                grid-template-columns: 1fr;
                gap: var(--space-6);
            }
            
            .cart-item {
                flex-direction: column;
                text-align: center;
            }
            
            .cart-item-image {
                width: 100%;
                height: 200px;
            }
            
            .cart-item-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Cart Page -->
    <div class="cart-page">
        <!-- Page Header -->
        <div class="cart-header">
            <div class="container">
                <!-- Breadcrumb -->
                <nav class="breadcrumb">
                    <a href="index.php">Trang chủ</a>
                    <span class="breadcrumb-separator">/</span>
                    <span>Giỏ hàng</span>
                </nav>
                
                <!-- Page Title -->
                <h1 class="page-title">Giỏ hàng của bạn</h1>
                <p class="page-subtitle">Kiểm tra và chỉnh sửa sản phẩm trước khi thanh toán</p>
            </div>
        </div>
        
        <div class="container">
            <?php if ($cart_count > 0 && !empty($cart_products)): ?>
                <div class="cart-content">
                    <!-- Cart Items -->
                    <div class="cart-items">
                        <div class="cart-items-header">
                            <h2 class="cart-items-title">Sản phẩm (<?php echo $cart_count; ?>)</h2>
                            <button class="btn btn-outline btn-sm" onclick="clearCart()">
                                <i class="fas fa-trash"></i> Xóa tất cả
                            </button>
                        </div>
                        
                        <div id="cartItemsList">
                            <?php foreach ($cart_products as $row): 
                                $productId   = (int)$row['cart_product_id'];
                                $name        = $row['product_name'] ?? '';
                                $image       = !empty($row['product_image_path']) ? $row['product_image_path'] : 'assets/images/placeholder.jpg';
                                $qty         = max(1, (int)$row['cart_quantity']);
                                $stockQty    = max(1, (int)($row['product_stock_quantity'] ?? 1));
                                $unitPrice   = (float)($row['product_unit_price'] ?? $row['cart_price'] ?? 0);
                                $lineTotal   = $unitPrice * $qty;
                            ?>
                                <div class="cart-item" data-product-id="<?php echo $productId; ?>">
                                    <div class="cart-item-image">
                                        <img src="<?php echo $image; ?>" 
                                             alt="<?php echo htmlspecialchars($name); ?>">
                                    </div>
                                    
                                    <div class="cart-item-details">
                                        <h3 class="cart-item-name" style="margin:0 0 var(--space-2);">
                                            <a href="product.php?id=<?php echo $productId; ?>" style="text-decoration:none;color:var(--gray-900);">
                                                <?php echo htmlspecialchars($name ?: 'Sản phẩm'); ?>
                                            </a>
                                        </h3>

                                        <div class="cart-item-price" style="color:var(--primary-color);font-weight:700;">
                                            <?php echo formatPrice($unitPrice); ?>
                                        </div>
                                        
                                        <div class="cart-item-actions">
                                            <div class="quantity-controls">
                                                <button class="quantity-btn" 
                                                        onclick="updateQuantity(<?php echo $productId; ?>, -1)"
                                                        <?php echo $qty <= 1 ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-minus"></i>
                                                </button>
                                                <input type="number" 
                                                       class="quantity-input" 
                                                       value="<?php echo $qty; ?>"
                                                       min="1" 
                                                       max="<?php echo $stockQty; ?>"
                                                       onchange="updateQuantity(<?php echo $productId; ?>, this.value)">
                                                <button class="quantity-btn" 
                                                        onclick="updateQuantity(<?php echo $productId; ?>, 1)"
                                                        <?php echo $qty >= $stockQty ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                            
                                            <button class="remove-btn" onclick="removeItem(<?php echo $productId; ?>)">
                                                <i class="fas fa-trash"></i> Xóa
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="cart-item-total">
                                        <?php echo formatPrice($lineTotal); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Cart Summary -->
                    <div class="cart-summary">
                        <h3 class="summary-title">Tóm tắt đơn hàng</h3>
                        
                        <div class="summary-row">
                            <span class="summary-label">Tạm tính:</span>
                            <span class="summary-value" id="subtotal"><?php echo formatPrice($cart_total); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span class="summary-label">Phí vận chuyển:</span>
                            <span class="summary-value" id="shipping">
                                <?php echo $shipping_fee > 0 ? formatPrice($shipping_fee) : 'Miễn phí'; ?>
                            </span>
                        </div>
                        
                        <div class="summary-row">
                            <span class="summary-label">Giảm giá:</span>
                            <span class="summary-value" id="discount">0 ₫</span>
                        </div>
                        
                        <div class="summary-row">
                            <span class="summary-label">Tổng cộng:</span>
                            <span class="summary-value summary-total" id="total">
                                <?php echo formatPrice($cart_total + $shipping_fee); ?>
                            </span>
                        </div>
                        
                        <div class="coupon-section">
                            <h4 style="margin-bottom: var(--space-3); color: var(--gray-700);">Mã giảm giá</h4>
                            <input type="text" class="coupon-input" placeholder="Nhập mã giảm giá" id="couponCode">
                            <button class="coupon-btn" onclick="applyCoupon()">
                                <i class="fas fa-tag"></i> Áp dụng
                            </button>
                        </div>
                        
                        <button class="checkout-btn" onclick="proceedToCheckout()">
                            <i class="fas fa-credit-card"></i> Tiến hành thanh toán
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-cart">
                    <div class="empty-cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>Giỏ hàng trống</h3>
                    <p>Bạn chưa có sản phẩm nào trong giỏ hàng. Hãy khám phá và thêm những sản phẩm bạn thích!</p>
                    <a href="products.php" class="btn">
                        <i class="fas fa-shopping-bag"></i>
                        Bắt đầu mua sắm
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
        // Update quantity
        function updateQuantity(productId, change) {
            const currentQuantity = parseInt(document.querySelector(`[data-product-id="${productId}"] .quantity-input`).value);
            let newQuantity;
            
            if (typeof change === 'number') {
                newQuantity = Math.max(1, currentQuantity + change);
            } else {
                newQuantity = Math.max(1, parseInt(change));
            }
            
            // Update UI immediately
            const quantityInput = document.querySelector(`[data-product-id="${productId}"] .quantity-input`);
            const minusBtn = document.querySelector(`[data-product-id="${productId}"] .quantity-btn:first-child`);
            const plusBtn = document.querySelector(`[data-product-id="${productId}"] .quantity-btn:last-child`);
            
            quantityInput.value = newQuantity;
            
            // Update button states
            minusBtn.disabled = newQuantity <= 1;
            plusBtn.disabled = newQuantity >= parseInt(quantityInput.max);
            
            // Send AJAX request
            fetch('cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update&product_id=${productId}&quantity=${newQuantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateCartSummary();
                    showToast(data.message, 'success');
                } else {
                    showToast(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Có lỗi xảy ra', 'error');
            });
        }
        
        // Remove item
        function removeItem(productId) {
            if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?')) {
                fetch('cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=remove&product_id=${productId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove item from UI
                        const item = document.querySelector(`[data-product-id="${productId}"]`);
                        item.style.animation = 'fadeOut 0.3s ease-out';
                        setTimeout(() => {
                            item.remove();
                            updateCartSummary();
                            
                            // Check if cart is empty
                            if (document.querySelectorAll('.cart-item').length === 0) {
                                location.reload();
                            }
                        }, 300);
                        
                        showToast(data.message, 'success');
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
        
        // Clear cart
        function clearCart() {
            if (confirm('Bạn có chắc chắn muốn xóa tất cả sản phẩm khỏi giỏ hàng?')) {
                fetch('cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=clear'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Có lỗi xảy ra khi xóa giỏ hàng', 'error');
                });
            }
        }
        
        // Apply coupon
        function applyCoupon() {
            const couponCode = document.getElementById('couponCode').value.trim();
            if (!couponCode) {
                showToast('Vui lòng nhập mã giảm giá', 'error');
                return;
            }
            
            // Simulate coupon validation
            setTimeout(() => {
                const discount = 50000; // 50k discount
                document.getElementById('discount').textContent = formatPrice(discount);
                updateTotal(-discount);
                showToast('Áp dụng mã giảm giá thành công!', 'success');
            }, 1000);
        }
        
        // Update cart summary
        function updateCartSummary() {
            // Reload page to get updated totals
            location.reload();
        }
        
        // Update total
        function updateTotal(discount = 0) {
            const subtotal = <?php echo $cart_total; ?>;
            const shipping = <?php echo $shipping_fee; ?>;
            const total = subtotal + shipping - discount;
            
            document.getElementById('total').textContent = formatPrice(total);
        }
        
        // Proceed to checkout
        function proceedToCheckout() {
            window.location.href = 'checkout.php';
        }
        
        // Format price
        function formatPrice(price) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(price);
        }
        
        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <div class="toast-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    <span>${message}</span>
                </div>
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
        
        // Add fade out animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; transform: translateX(0); }
                to { opacity: 0; transform: translateX(-100%); }
            }
            
            .toast {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                padding: 16px;
                z-index: 10000;
                transform: translateX(100%);
                transition: transform 0.3s ease;
                max-width: 300px;
            }
            
            .toast.show {
                transform: translateX(0);
            }
            
            .toast-content {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .toast-success {
                border-left: 4px solid #10b981;
            }
            
            .toast-error {
                border-left: 4px solid #ef4444;
            }
            
            .toast-content i {
                font-size: 18px;
            }
            
            .toast-success .toast-content i {
                color: #10b981;
            }
            
            .toast-error .toast-content i {
                color: #ef4444;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
