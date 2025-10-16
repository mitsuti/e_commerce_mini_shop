<?php
/**
 * TRANG CHECKOUT
 * Trang thanh toán đơn hàng
 */

require_once 'config/config.php';

// Kiểm tra giỏ hàng
// Kiểm tra giỏ hàng (dựa trên DB cart thay vì session)
$user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
$session_id = session_id();
$cart_products = fetchData("
    SELECT
        c.product_id,
        GREATEST(c.quantity, 1) AS cart_quantity,
        COALESCE(NULLIF(c.price, 0), COALESCE(NULLIF(p.sale_price, 0), p.price)) AS unit_price,
        p.name AS product_name,
        pi.image_path
    FROM cart c
    JOIN products p ON c.product_id = p.id AND p.status = 'active'
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE (c.user_id = ? AND c.user_id IS NOT NULL) OR (c.session_id = ? AND c.user_id IS NULL)
", [$user_id, $session_id]);

if (empty($cart_products)) {
    header('Location: cart.php');
    exit;
}

// Kiểm tra success
$order_success = false;
$success_order_id = null;

// Xử lý form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token không hợp lệ. Vui lòng thử lại.';
    } else {
        // Lấy dữ liệu form
        $customer_name = sanitizeInput($_POST['customer_name'] ?? '');
        $customer_email = sanitizeInput($_POST['customer_email'] ?? '');
        $customer_phone = sanitizeInput($_POST['customer_phone'] ?? '');
        $shipping_address = sanitizeInput($_POST['shipping_address'] ?? '');
        $billing_address = sanitizeInput($_POST['billing_address'] ?? '');
        $payment_method = sanitizeInput($_POST['payment_method'] ?? 'cod');
        $notes = sanitizeInput($_POST['notes'] ?? '');
        
        // Validation
        $errors = [];
        
        if (empty($customer_name)) $errors[] = 'Họ tên là bắt buộc';
        if (empty($customer_email) || !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ';
        }
        if (empty($customer_phone)) $errors[] = 'Số điện thoại là bắt buộc';
        if (empty($shipping_address)) $errors[] = 'Địa chỉ giao hàng là bắt buộc';
        
        if (empty($errors)) {
            try {
                // Bắt đầu transaction
                $pdo->beginTransaction();
                
                // Lấy giỏ hàng từ database
                $cart_query = "
                    SELECT c.*, p.name as product_name, p.sku as product_sku, 
                           COALESCE(NULLIF(p.sale_price, 0), p.price) as unit_price,
                           pi.image_path
                    FROM cart c
                    LEFT JOIN products p ON c.product_id = p.id
                    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
                    WHERE " . ($user_id ? "c.user_id = ?" : "c.session_id = ?") . "
                    AND p.status = 'active'
                ";
                
                $cart_items = fetchData($cart_query, [$user_id ?: $session_id]);
                
                if (empty($cart_items)) {
                    throw new Exception("Giỏ hàng trống");
                }
                
                // Tính toán tổng tiền
                $subtotal = 0;
                foreach ($cart_items as $item) {
                    $subtotal += $item['unit_price'] * $item['quantity'];
                }
                
                $shipping_fee = $subtotal >= 500000 ? 0 : 30000;
                $total_amount = $subtotal + $shipping_fee;
                
                // Tạo order number
                $order_number = 'ORD' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                // Tạo đơn hàng
                $order_sql = "
                    INSERT INTO orders (
                        order_number, user_id, customer_name, customer_email, customer_phone,
                        shipping_address, billing_address, subtotal, shipping_fee, total_amount,
                        payment_method, payment_status, order_status, notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', ?)
                ";
                
                $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
                $billing_address = $billing_address ?: $shipping_address;
                
                executeQuery($order_sql, [
                    $order_number, $user_id, $customer_name, $customer_email, $customer_phone,
                    $shipping_address, $billing_address, $subtotal, $shipping_fee, $total_amount,
                    $payment_method, $notes
                ]);
                
                $order_id = $pdo->lastInsertId();
                
                // Tạo order items
                foreach ($cart_items as $item) {
                    $order_item_sql = "
                        INSERT INTO order_items (
                            order_id, product_id, product_name, product_sku, quantity, price, total
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)
                    ";
                    
                    $total = $item['unit_price'] * $item['quantity'];
                    
                    executeQuery($order_item_sql, [
                        $order_id, $item['product_id'], $item['product_name'],
                        $item['product_sku'], $item['quantity'], $item['unit_price'], $total
                    ]);
                    
                    // Cập nhật stock
                    executeQuery(
                        "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?",
                        [$item['quantity'], $item['product_id']]
                    );
                }
                
                // Commit transaction
                $pdo->commit();
                
                // Xóa giỏ hàng từ database
                if ($user_id) {
                    executeQuery("DELETE FROM cart WHERE user_id = ?", [$user_id]);
                } else {
                    executeQuery("DELETE FROM cart WHERE session_id = ?", [$session_id]);
                }
                
                // Xóa giỏ hàng session
                unset($_SESSION['cart']);
                
                // Set success variables để hiển thị ngay tại trang
                $order_success = true;
                $success_order_id = $order_id;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Có lỗi xảy ra khi tạo đơn hàng. Vui lòng thử lại.';
                error_log("Checkout error: " . $e->getMessage());
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

// Lấy dữ liệu giỏ hàng
$cart_items = [];
$cart_total = 0;
$cart_count = 0;

foreach ($cart_products as $row) {
    $productId = (int)($row['product_id'] ?? 0);
    $quantity  = max(1, (int)($row['cart_quantity'] ?? $row['quantity'] ?? 1));
    $price     = (float)($row['unit_price'] ?? 0);
    $name      = (string)($row['product_name'] ?? '');
    $imagePath = (string)($row['image_path'] ?? '');

    // Fallback: nếu thiếu tên/giá/ảnh thì lấy trực tiếp từ bảng products
    if ($name === '' || $price <= 0 || $imagePath === '') {
        $p = fetchOne("SELECT name, price, sale_price FROM products WHERE id = ? AND status = 'active'", [$productId]);
        if ($p) {
            if ($name === '') $name = $p['name'];
            if ($price <= 0) $price = (float)($p['sale_price'] ?: $p['price']);
        }
    }

    if ($imagePath === '' || $imagePath === null) {
        $imagePath = 'assets/images/placeholder.jpg';
    }

    $subtotal = $price * $quantity;

    $cart_items[] = [
        'product_id' => $productId,
        'name'       => $name !== '' ? $name : 'Sản phẩm',
        'quantity'   => $quantity,
        'unit_price' => $price,
        'subtotal'   => $subtotal,
        'image'      => $imagePath,
    ];

    $cart_total += $subtotal;
    $cart_count += $quantity;
}

$shipping_fee = $cart_total >= 500000 ? 0 : 30000;
$total_amount = $cart_total + $shipping_fee;

// Lấy thông tin user nếu đã đăng nhập
$user_info = null;
if (isLoggedIn()) {
    $user_info = getCurrentUser();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Hoàn tất đơn hàng của bạn. Nhập thông tin giao hàng và chọn phương thức thanh toán.">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/components.css?v=<?php echo time(); ?>">
    
    <!-- Custom CSS for checkout page -->
    <style>
        /* Ensure footer displays properly */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .checkout-page {
            padding: var(--space-8) 0;
            background: var(--gray-50);
            flex: 1;
        }
        
        .checkout-header {
            background: var(--white);
            padding: var(--space-6) 0;
            margin-bottom: var(--space-8);
            box-shadow: var(--shadow-sm);
        }
        
        .checkout-title {
            font-size: var(--text-4xl);
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: var(--space-2);
        }
        
        .checkout-subtitle {
            color: var(--gray-600);
            font-size: var(--text-lg);
        }
        
        .checkout-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: var(--space-8);
        }
        
        .checkout-form {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--space-8);
            box-shadow: var(--shadow-md);
        }
        
        .form-section {
            margin-bottom: var(--space-8);
        }
        
        .form-section:last-child {
            margin-bottom: 0;
        }
        
        .section-title {
            font-size: var(--text-xl);
            font-weight: 600;
            margin-bottom: var(--space-6);
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }
        
        .section-title i {
            color: var(--primary-color);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-4);
            margin-bottom: var(--space-4);
        }
        
        .form-row.full {
            grid-template-columns: 1fr;
        }
        
        .form-group {
            margin-bottom: var(--space-4);
        }
        
        .form-label {
            display: block;
            font-size: var(--text-sm);
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: var(--space-2);
        }
        
        .form-label.required::after {
            content: ' *';
            color: var(--error-color);
        }
        
        .form-input {
            width: 100%;
            padding: var(--space-3) var(--space-4);
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-md);
            font-size: var(--text-base);
            transition: all var(--transition-fast);
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-input.error {
            border-color: var(--error-color);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .payment-methods {
            display: grid;
            gap: var(--space-3);
        }
        
        .payment-method {
            position: relative;
        }
        
        .payment-method input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .payment-method label {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            padding: var(--space-4);
            border: 2px solid var(--gray-300);
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        
        .payment-method input[type="radio"]:checked + label {
            border-color: var(--primary-color);
            background: var(--gray-50);
        }
        
        .payment-method-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            color: var(--white);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--text-lg);
        }
        
        .payment-method-info h4 {
            margin: 0 0 var(--space-1) 0;
            font-size: var(--text-base);
            color: var(--gray-900);
        }
        
        .payment-method-info p {
            margin: 0;
            font-size: var(--text-sm);
            color: var(--gray-600);
        }
        
        .order-summary {
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
            margin-bottom: var(--space-6);
            color: var(--gray-900);
        }
        
        .order-items {
            margin-bottom: var(--space-6);
        }
        
        .order-item {
            display: flex;
            gap: var(--space-3);
            padding: var(--space-3) 0;
            border-bottom: 1px solid var(--gray-100);
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .order-item-image {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-md);
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .order-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .order-item-details {
            flex: 1;
        }
        
        .order-item-name {
            font-size: var(--text-sm);
            font-weight: 500;
            margin-bottom: var(--space-1);
            color: var(--gray-900);
        }
        
        .order-item-quantity {
            font-size: var(--text-xs);
            color: var(--gray-600);
        }
        
        .order-item-price {
            font-size: var(--text-sm);
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .summary-totals {
            border-top: 1px solid var(--gray-200);
            padding-top: var(--space-4);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--space-2) 0;
        }
        
        .summary-row.total {
            font-weight: 700;
            font-size: var(--text-lg);
            color: var(--gray-900);
            border-top: 1px solid var(--gray-200);
            margin-top: var(--space-2);
            padding-top: var(--space-3);
        }
        
        .summary-label {
            color: var(--gray-600);
        }
        
        .summary-value {
            color: var(--gray-900);
            font-weight: 500;
        }
        
        .summary-total {
            color: var(--primary-color);
            font-weight: 700;
        }
        
        .place-order-btn {
            width: 100%;
            padding: var(--space-4);
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--white);
            border: none;
            border-radius: var(--radius-lg);
            font-size: var(--text-lg);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
            margin-top: var(--space-6);
        }
        
        .place-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .place-order-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Success Message Styles */
        .success-message {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 3rem 2rem;
            border-radius: 1rem;
            text-align: center;
            margin: 2rem auto;
            max-width: 1200px;
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.3);
            position: relative;
            z-index: 10;
        }
        
        .success-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            animation: bounce 2s infinite;
        }
        
        .success-message h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
        }
        
        .success-message p {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        
        .order-details {
            background: white;
            color: #333;
            padding: 2rem;
            border-radius: 1rem;
            margin-top: 2rem;
            text-align: left;
        }
        
        .order-details h3 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
        
        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
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
        
        .order-items h4 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .item-row {
            display: flex;
            align-items: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .item-thumb {
            width: 50px;
            height: 50px;
            border-radius: 0.5rem;
            object-fit: cover;
            margin-right: 1rem;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: #333;
            display: block;
            margin-bottom: 0.3rem;
        }
        
        .item-qty {
            font-size: 0.9rem;
            color: #666;
        }
        
        .item-price {
            font-weight: 700;
            color: #4CAF50;
            font-size: 1.1rem;
        }
        
        .success-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
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
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(var(--primary-rgb), 0.3);
            color: white;
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
        
        .error-message {
            background: var(--error-light);
            color: var(--error-color);
            padding: var(--space-4);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-6);
            border: 1px solid var(--error-color);
        }
        
        .progress-steps {
            display: flex;
            justify-content: center;
            margin-bottom: var(--space-8);
        }
        
        .progress-step {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            padding: var(--space-3) var(--space-6);
            background: var(--white);
            border-radius: var(--radius-full);
            margin: 0 var(--space-2);
            box-shadow: var(--shadow-sm);
            position: relative;
        }
        
        .progress-step.active {
            background: var(--primary-color);
            color: var(--white);
        }
        
        .progress-step.completed {
            background: var(--success-color);
            color: var(--white);
        }
        
        .step-number {
            width: 24px;
            height: 24px;
            border-radius: var(--radius-full);
            background: var(--gray-300);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--text-xs);
            font-weight: 600;
        }
        
        .progress-step.active .step-number {
            background: var(--white);
            color: var(--primary-color);
        }
        
        .progress-step.completed .step-number {
            background: var(--white);
            color: var(--success-color);
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .checkout-content {
                grid-template-columns: 1fr;
                gap: var(--space-6);
            }
            
            .order-summary {
                position: static;
                order: -1;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .progress-steps {
                flex-direction: column;
                gap: var(--space-2);
            }
            
            .success-actions {
                flex-direction: column;
                align-items: center;
            }
            
            .success-actions .btn-primary,
            .success-actions .btn-secondary {
                width: 100%;
                max-width: 300px;
                text-align: center;
            }
            
            .progress-step {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Checkout Page -->
    <div class="checkout-page">
        <!-- Checkout Header -->
        <div class="checkout-header">
            <div class="container">
                <h1 class="checkout-title">Thanh toán</h1>
                <p class="checkout-subtitle">Hoàn tất đơn hàng của bạn</p>
                
                <!-- Progress Steps -->
                <div class="progress-steps">
                    <div class="progress-step completed">
                        <div class="step-number">1</div>
                        <span>Giỏ hàng</span>
                    </div>
                    <div class="progress-step <?php echo $order_success ? 'completed' : 'active'; ?>">
                        <div class="step-number">2</div>
                        <span>Thanh toán</span>
                    </div>
                    <div class="progress-step <?php echo $order_success ? 'active' : ''; ?>">
                        <div class="step-number">3</div>
                        <span>Hoàn tất</span>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if ($order_success): ?>
            <!-- Success Message -->
            <div class="success-message">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2>Đặt hàng thành công!</h2>
                    <p>Cảm ơn bạn đã mua sắm tại <?php echo SITE_NAME; ?>. Đơn hàng của bạn đã được xử lý thành công.</p>
                    
                    <!-- Order Details -->
                    <?php 
                    $success_order = fetchOne("SELECT * FROM orders WHERE id = ?", [$success_order_id]);
                    $success_items = fetchData("SELECT oi.*, pi.image_path FROM order_items oi LEFT JOIN product_images pi ON oi.product_id = pi.product_id AND pi.is_primary = 1 WHERE oi.order_id = ?", [$success_order_id]);
                    ?>
                    
                    <div class="order-details">
                        <h3>Chi tiết đơn hàng</h3>
                        <div class="order-info-grid">
                            <div class="info-item">
                                <span class="info-label">Mã đơn hàng:</span>
                                <span class="info-value">#<?php echo htmlspecialchars($success_order['order_number']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Tổng tiền:</span>
                                <span class="info-value" style="color: #4CAF50; font-weight: 700; font-size: 1.2rem;"><?php echo formatPrice($success_order['total_amount']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Trạng thái:</span>
                                <span class="info-value" style="color: #4CAF50;"><?php echo ucfirst($success_order['order_status']); ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($success_items)): ?>
                            <div class="order-items">
                                <h4>Sản phẩm đã đặt:</h4>
                                <?php foreach ($success_items as $item): ?>
                                    <div class="item-row">
                                        <img src="<?php echo htmlspecialchars($item['image_path'] ?: 'assets/images/placeholder.jpg'); ?>" 
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                             class="item-thumb">
                                        <div class="item-info">
                                            <span class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></span>
                                            <span class="item-qty">x<?php echo $item['quantity']; ?></span>
                                        </div>
                                        <span class="item-price"><?php echo formatPrice($item['total']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="success-actions">
                            <a href="index.php" class="btn-primary">Về trang chủ</a>
                            <a href="order-detail.php?id=<?php echo $success_order_id; ?>" class="btn-secondary">
                                <i class="fas fa-eye"></i> Xem chi tiết đơn hàng
                            </a>
                            <a href="products.php" class="btn-secondary">Tiếp tục mua sắm</a>
                        </div>
                    </div>
                </div>
        <?php else: ?>
            <div class="container">
                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <div class="checkout-content">
                <!-- Checkout Form -->
                <form class="checkout-form" method="POST" id="checkoutForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Customer Information -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-user"></i>
                            Thông tin khách hàng
                        </h2>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label required" for="customer_name">Họ và tên</label>
                                <input type="text" 
                                       id="customer_name" 
                                       name="customer_name" 
                                       class="form-input" 
                                       value="<?php echo htmlspecialchars($user_info['full_name'] ?? ''); ?>"
                                       required>
                            </div>
                            <div class="form-group">
                                <label class="form-label required" for="customer_email">Email</label>
                                <input type="email" 
                                       id="customer_email" 
                                       name="customer_email" 
                                       class="form-input" 
                                       value="<?php echo htmlspecialchars($user_info['email'] ?? ''); ?>"
                                       required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label required" for="customer_phone">Số điện thoại</label>
                            <input type="tel" 
                                   id="customer_phone" 
                                   name="customer_phone" 
                                   class="form-input" 
                                   value="<?php echo htmlspecialchars($user_info['phone'] ?? ''); ?>"
                                   required>
                        </div>
                    </div>
                    
                    <!-- Shipping Address -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-map-marker-alt"></i>
                            Địa chỉ giao hàng
                        </h2>
                        
                        <div class="form-group">
                            <label class="form-label required" for="shipping_address">Địa chỉ giao hàng</label>
                            <textarea id="shipping_address" 
                                      name="shipping_address" 
                                      class="form-input form-textarea" 
                                      placeholder="Nhập địa chỉ chi tiết..."
                                      required><?php echo htmlspecialchars($user_info['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label" for="billing_address">Địa chỉ hóa đơn (nếu khác địa chỉ giao hàng)</label>
                            <textarea id="billing_address" 
                                      name="billing_address" 
                                      class="form-input form-textarea" 
                                      placeholder="Nhập địa chỉ hóa đơn..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-credit-card"></i>
                            Phương thức thanh toán
                        </h2>
                        
                        <div class="payment-methods">
                            <div class="payment-method">
                                <input type="radio" id="cod" name="payment_method" value="cod" checked>
                                <label for="cod">
                                    <div class="payment-method-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div class="payment-method-info">
                                        <h4>Thanh toán khi nhận hàng (COD)</h4>
                                        <p>Thanh toán bằng tiền mặt khi nhận được hàng</p>
                                    </div>
                                </label>
                            </div>
                            
                            <div class="payment-method">
                                <input type="radio" id="bank_transfer" name="payment_method" value="bank_transfer">
                                <label for="bank_transfer">
                                    <div class="payment-method-icon">
                                        <i class="fas fa-university"></i>
                                    </div>
                                    <div class="payment-method-info">
                                        <h4>Chuyển khoản ngân hàng</h4>
                                        <p>Chuyển khoản trước khi giao hàng</p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Notes -->
                    <div class="form-section">
                        <h2 class="section-title">
                            <i class="fas fa-sticky-note"></i>
                            Ghi chú đơn hàng
                        </h2>
                        
                        <div class="form-group">
                            <label class="form-label" for="notes">Ghi chú (tùy chọn)</label>
                            <textarea id="notes" 
                                      name="notes" 
                                      class="form-input form-textarea" 
                                      placeholder="Ghi chú thêm cho đơn hàng..."></textarea>
                        </div>
                    </div>
                </form>
                
                <!-- Order Summary -->
                <div class="order-summary">
                    <h3 class="summary-title">Tóm tắt đơn hàng</h3>
                    
                    <div class="order-items">
                        <?php foreach ($cart_products as $row): 
                            $productId = (int)($row['product_id'] ?? 0);
                            $qty       = max(1, (int)($row['cart_quantity'] ?? $row['quantity'] ?? 1));
                            $price     = (float)($row['unit_price'] ?? 0);
                            $name      = (string)($row['product_name'] ?? '');
                            $image     = (string)($row['image_path'] ?? '');

                            if ($name === '' || $price <= 0) {
                                $p = fetchOne("SELECT name, price, sale_price FROM products WHERE id = ? AND status = 'active'", [$productId]);
                                if ($p) {
                                    if ($name === '') $name = $p['name'];
                                    if ($price <= 0) $price = (float)($p['sale_price'] ?: $p['price']);
                                }
                            }
                            if ($image === '') {
                                $image = 'assets/images/placeholder.jpg';
                            }
                        ?>
                            <div class="order-item">
                                <div class="order-item-image">
                                    <img src="<?php echo $image; ?>" alt="<?php echo htmlspecialchars($name); ?>">
                                </div>
                                <div class="order-item-details">
                                    <div class="order-item-name"><?php echo htmlspecialchars($name); ?></div>
                                    <div class="order-item-quantity">Số lượng: <?php echo $qty; ?></div>
                                </div>
                                <div class="order-item-price">
                                    <?php echo formatPrice($price); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-totals">
                        <div class="summary-row">
                            <span class="summary-label">Tạm tính:</span>
                            <span class="summary-value"><?php echo formatPrice($cart_total); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span class="summary-label">Phí vận chuyển:</span>
                            <span class="summary-value">
                                <?php echo $shipping_fee > 0 ? formatPrice($shipping_fee) : 'Miễn phí'; ?>
                            </span>
                        </div>
                        
                        <div class="summary-row total">
                            <span class="summary-label">Tổng cộng:</span>
                            <span class="summary-value summary-total"><?php echo formatPrice($total_amount); ?></span>
                        </div>
                    </div>
                    
                    <button type="submit" form="checkoutForm" class="place-order-btn">
                        <i class="fas fa-lock"></i> Đặt hàng ngay
                    </button>
                    
                    <div class="text-center" style="margin-top: var(--space-4);">
                        <small style="color: var(--gray-500);">
                            <i class="fas fa-shield-alt"></i> Thông tin của bạn được bảo mật
                        </small>
                    </div>
                </div>
            </div>
        </div>
            </div>
        <?php endif; ?>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- JavaScript -->
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        // Form validation
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    isValid = false;
                } else {
                    field.classList.remove('error');
                }
            });
            
            // Email validation
            const emailField = document.getElementById('customer_email');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (emailField.value && !emailRegex.test(emailField.value)) {
                emailField.classList.add('error');
                isValid = false;
            }
            
            // Phone validation
            const phoneField = document.getElementById('customer_phone');
            const phoneRegex = /^[0-9+\-\s()]+$/;
            if (phoneField.value && !phoneRegex.test(phoneField.value)) {
                phoneField.classList.add('error');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                showToast('Vui lòng điền đầy đủ thông tin bắt buộc', 'error');
            } else {
                // Show loading state
                const submitBtn = this.querySelector('.place-order-btn');
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
            }
        });
        
        // Real-time validation
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.hasAttribute('required') && !this.value.trim()) {
                    this.classList.add('error');
                } else {
                    this.classList.remove('error');
                }
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('error') && this.value.trim()) {
                    this.classList.remove('error');
                }
            });
        });
        
        // Copy shipping address to billing address
        document.getElementById('shipping_address').addEventListener('input', function() {
            const billingAddress = document.getElementById('billing_address');
            if (!billingAddress.value) {
                billingAddress.value = this.value;
            }
        });
    </script>
</body>
</html>
