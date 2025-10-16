<?php
/**
 * TRANG CHI TIẾT SẢN PHẨM
 * Trang hiển thị thông tin chi tiết sản phẩm với gallery, reviews, related products
 */

require_once 'config/config.php';

// Lấy ID từ URL
$product_id = intval($_GET['id'] ?? 0);

if ($product_id <= 0) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Lấy thông tin sản phẩm
$product = fetchOne("
    SELECT p.*, 
           c.name as category_name, 
           c.slug as category_slug,
           COALESCE((SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND status = 'approved'), 0) as avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE product_id = p.id AND status = 'approved') as review_count
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.status = 'active'
", [$product_id]);

if (!$product) {
    header('HTTP/1.0 404 Not Found');
    include '404.php';
    exit;
}

// Lấy hình ảnh sản phẩm
$product_images = fetchData("
    SELECT * FROM product_images 
    WHERE product_id = ? 
    ORDER BY is_primary DESC, sort_order ASC
", [$product['id']]);

// Lấy thuộc tính sản phẩm
$product_attributes = fetchData("
    SELECT * FROM product_attributes 
    WHERE product_id = ? 
    ORDER BY attribute_name
", [$product['id']]);

// Lấy reviews
$reviews = fetchData("
    SELECT r.*, p.name as product_name 
    FROM reviews r 
    LEFT JOIN products p ON r.product_id = p.id 
    WHERE r.product_id = ? AND r.status = 'approved' 
    ORDER BY r.created_at DESC 
    LIMIT 10
", [$product['id']]);

// Lấy sản phẩm liên quan
$related_products = fetchData("
    SELECT p.*, pi.image_path 
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
    WHERE p.category_id = ? AND p.id != ? AND p.status = 'active' 
    ORDER BY RAND() 
    LIMIT 4
", [$product['category_id'], $product['id']]);

// Tăng view count (có thể lưu vào database hoặc session)
if (!isset($_SESSION['viewed_products'])) {
    $_SESSION['viewed_products'] = [];
}

if (!in_array($product['id'], $_SESSION['viewed_products'])) {
    $_SESSION['viewed_products'][] = $product['id'];
    // Có thể update view count trong database
    executeQuery("UPDATE products SET view_count = view_count + 1 WHERE id = ?", [$product['id']]);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($product['meta_description'] ?: $product['short_description']); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($product['name'] . ', ' . $product['category_name'] . ', ' . $product['brand']); ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo htmlspecialchars($product['name']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($product['short_description']); ?>">
    <meta property="og:image" content="<?php echo $product_images[0]['image_path'] ?? '/assets/images/placeholder.jpg'; ?>">
    <meta property="og:url" content="<?php echo SITE_URL . '/product/' . $product['slug']; ?>">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    
    <!-- Custom CSS for product page -->
    <style>
        .product-page {
            padding: var(--space-8) 0;
            background: var(--gray-50);
            min-height: 100vh;
        }
        
        .product-header {
            background: var(--white);
            padding: var(--space-6) 0;
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
        
        .product-main {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--space-12);
            margin-bottom: var(--space-16);
        }
        
        .product-gallery {
            position: sticky;
            top: 100px;
        }
        
        .product-main-image {
            position: relative;
            background: var(--white);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            margin-bottom: var(--space-4);
        }
        
        .product-main-image img {
            width: 100%;
            height: 500px;
            object-fit: cover;
            transition: transform var(--transition-slow);
            cursor: zoom-in;
        }
        
        .product-main-image:hover img {
            transform: scale(1.05);
        }
        
        .product-zoom-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity var(--transition-fast);
            cursor: zoom-in;
        }
        
        .product-main-image:hover .product-zoom-overlay {
            opacity: 1;
        }
        
        .zoom-icon {
            color: var(--white);
            font-size: var(--text-3xl);
        }
        
        .product-thumbnails {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            gap: var(--space-3);
        }
        
        .product-thumbnail {
            position: relative;
            background: var(--white);
            border-radius: var(--radius-lg);
            overflow: hidden;
            cursor: pointer;
            transition: all var(--transition-fast);
            border: 2px solid transparent;
        }
        
        .product-thumbnail.active {
            border-color: var(--primary-color);
        }
        
        .product-thumbnail:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .product-thumbnail img {
            width: 100%;
            height: 80px;
            object-fit: cover;
        }
        
        .product-info {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--space-8);
            box-shadow: var(--shadow-lg);
        }
        
        .product-badges {
            display: flex;
            gap: var(--space-2);
            margin-bottom: var(--space-4);
        }
        
        .product-badge {
            padding: var(--space-1) var(--space-3);
            border-radius: var(--radius-full);
            font-size: var(--text-xs);
            font-weight: 600;
            color: var(--white);
        }
        
        .product-badge.sale {
            background: var(--error-color);
        }
        
        .product-badge.new {
            background: var(--success-color);
        }
        
        .product-badge.hot {
            background: var(--accent-color);
        }
        
        .product-title {
            font-size: var(--text-3xl);
            font-weight: 700;
            margin-bottom: var(--space-4);
            color: var(--gray-900);
            line-height: 1.2;
        }
        
        .product-rating {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            margin-bottom: var(--space-6);
        }
        
        .product-stars {
            display: flex;
            gap: var(--space-1);
        }
        
        .product-stars .star {
            color: var(--warning-color);
            font-size: var(--text-lg);
        }
        
        .product-stars .star.empty {
            color: var(--gray-300);
        }
        
        .product-rating-text {
            color: var(--gray-600);
            font-size: var(--text-sm);
        }
        
        .product-price {
            margin-bottom: var(--space-6);
        }
        
        .product-price-current {
            font-size: var(--text-4xl);
            font-weight: 700;
            color: var(--primary-color);
            margin-right: var(--space-3);
        }
        
        .product-price-original {
            font-size: var(--text-xl);
            color: var(--gray-400);
            text-decoration: line-through;
        }
        
        .product-price-save {
            background: var(--error-color);
            color: var(--white);
            padding: var(--space-1) var(--space-3);
            border-radius: var(--radius-full);
            font-size: var(--text-sm);
            font-weight: 600;
            margin-left: var(--space-3);
        }
        
        .product-description {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: var(--space-6);
        }
        
        .product-attributes {
            margin-bottom: var(--space-6);
        }
        
        .product-attribute {
            display: flex;
            justify-content: space-between;
            padding: var(--space-2) 0;
            border-bottom: 1px solid var(--gray-100);
        }
        
        .product-attribute:last-child {
            border-bottom: none;
        }
        
        .product-attribute-label {
            font-weight: 500;
            color: var(--gray-700);
        }
        
        .product-attribute-value {
            color: var(--gray-600);
        }
        
        .product-actions {
            display: flex;
            gap: var(--space-4);
            margin-bottom: var(--space-6);
            flex-wrap: wrap;
        }
        
        .quantity-selector {
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
        
        .add-to-cart-btn {
            flex: 1;
            min-width: 200px;
        }
        
        .product-meta {
            display: flex;
            gap: var(--space-6);
            padding: var(--space-4);
            background: var(--gray-50);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-6);
        }
        
        .product-meta-item {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            color: var(--gray-600);
            font-size: var(--text-sm);
        }
        
        .product-meta-item i {
            color: var(--primary-color);
        }
        
        .product-tabs {
            background: var(--white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }
        
        .product-tabs-nav {
            display: flex;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .product-tab-btn {
            flex: 1;
            padding: var(--space-4) var(--space-6);
            background: none;
            border: none;
            color: var(--gray-600);
            font-weight: 500;
            cursor: pointer;
            transition: all var(--transition-fast);
            position: relative;
        }
        
        .product-tab-btn.active {
            color: var(--primary-color);
            background: var(--gray-50);
        }
        
        .product-tab-btn::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary-color);
            transform: scaleX(0);
            transition: transform var(--transition-fast);
        }
        
        .product-tab-btn.active::after {
            transform: scaleX(1);
        }
        
        .product-tab-content {
            padding: var(--space-8);
            display: none;
        }
        
        .product-tab-content.active {
            display: block;
        }
        
        .product-specifications {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-4);
        }
        
        .spec-item {
            display: flex;
            justify-content: space-between;
            padding: var(--space-3);
            background: var(--gray-50);
            border-radius: var(--radius-md);
        }
        
        .spec-label {
            font-weight: 500;
            color: var(--gray-700);
        }
        
        .spec-value {
            color: var(--gray-600);
        }
        
        .reviews-section {
            margin-top: var(--space-8);
        }
        
        .review-item {
            padding: var(--space-6);
            border: 1px solid var(--gray-200);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-4);
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-4);
        }
        
        .review-author {
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }
        
        .review-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            color: var(--white);
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .review-info h4 {
            margin: 0;
            font-size: var(--text-base);
            color: var(--gray-900);
        }
        
        .review-date {
            font-size: var(--text-sm);
            color: var(--gray-500);
        }
        
        .review-rating {
            display: flex;
            gap: var(--space-1);
        }
        
        .review-rating .star {
            color: var(--warning-color);
            font-size: var(--text-sm);
        }
        
        .review-rating .star.empty {
            color: var(--gray-300);
        }
        
        .review-content {
            color: var(--gray-700);
            line-height: 1.6;
        }
        
        .related-products {
            margin-top: var(--space-16);
        }
        
        .related-products h2 {
            text-align: center;
            margin-bottom: var(--space-12);
            font-size: var(--text-3xl);
        }
        
        .related-products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--space-6);
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .product-main {
                grid-template-columns: 1fr;
                gap: var(--space-8);
            }
            
            .product-gallery {
                position: static;
            }
            
            .product-main-image img {
                height: 300px;
            }
            
            .product-thumbnails {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .product-actions {
                flex-direction: column;
            }
            
            .add-to-cart-btn {
                min-width: auto;
            }
            
            .product-meta {
                flex-direction: column;
                gap: var(--space-3);
            }
            
            .product-tabs-nav {
                flex-direction: column;
            }
            
            .related-products-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: var(--space-4);
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Product Page -->
    <div class="product-page">
        <!-- Product Header -->
        <div class="product-header">
            <div class="container">
                <!-- Breadcrumb -->
                <nav class="breadcrumb">
                    <a href="index.php">Trang chủ</a>
                    <span class="breadcrumb-separator">/</span>
                    <a href="products.php">Sản phẩm</a>
                    <span class="breadcrumb-separator">/</span>
                    <a href="products.php?category=<?php echo $product['category_slug']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a>
                    <span class="breadcrumb-separator">/</span>
                    <span><?php echo htmlspecialchars($product['name']); ?></span>
                </nav>
            </div>
        </div>
        
        <div class="container">
            <!-- Product Main -->
            <div class="product-main">
                <!-- Product Gallery -->
                <div class="product-gallery">
                    <div class="product-main-image" id="mainImage">
                        <img src="<?php echo $product_images[0]['image_path'] ?? '/assets/images/placeholder.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             id="mainImageSrc">
                        <div class="product-zoom-overlay">
                            <i class="fas fa-search-plus zoom-icon"></i>
                        </div>
                    </div>
                    
                    <?php if (count($product_images) > 1): ?>
                        <div class="product-thumbnails">
                            <?php foreach ($product_images as $index => $image): ?>
                                <div class="product-thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                                     onclick="changeMainImage('<?php echo $image['image_path']; ?>', this)">
                                    <img src="<?php echo $image['image_path']; ?>" 
                                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Product Info -->
                <div class="product-info">
                    <!-- Badges -->
                    <div class="product-badges">
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                            <span class="product-badge sale">Sale</span>
                        <?php endif; ?>
                        <?php if ($product['featured']): ?>
                            <span class="product-badge hot">Hot</span>
                        <?php endif; ?>
                        <?php if (strtotime($product['created_at']) > strtotime('-30 days')): ?>
                            <span class="product-badge new">Mới</span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Title -->
                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <!-- Rating -->
                    <div class="product-rating">
                        <div class="product-stars">
                            <?php 
                            $avg_rating = round($product['avg_rating']);
                            for ($i = 1; $i <= 5; $i++): 
                            ?>
                                <span class="star <?php echo $i <= $avg_rating ? '' : 'empty'; ?>">
                                    <i class="fas fa-star"></i>
                                </span>
                            <?php endfor; ?>
                        </div>
                        <span class="product-rating-text">
                            <?php echo number_format($product['avg_rating'], 1); ?> 
                            (<?php echo $product['review_count']; ?> đánh giá)
                        </span>
                    </div>
                    
                    <!-- Price -->
                    <div class="product-price">
                        <span class="product-price-current">
                            <?php echo formatPrice($product['sale_price'] ?: $product['price']); ?>
                        </span>
                        <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                            <span class="product-price-original">
                                <?php echo formatPrice($product['price']); ?>
                            </span>
                            <span class="product-price-save">
                                Tiết kiệm <?php echo formatPrice($product['price'] - $product['sale_price']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Description -->
                    <?php if ($product['short_description']): ?>
                        <div class="product-description">
                            <?php echo nl2br(htmlspecialchars($product['short_description'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Attributes -->
                    <?php if (!empty($product_attributes)): ?>
                        <div class="product-attributes">
                            <?php foreach ($product_attributes as $attr): ?>
                                <div class="product-attribute">
                                    <span class="product-attribute-label"><?php echo htmlspecialchars($attr['attribute_name']); ?>:</span>
                                    <span class="product-attribute-value"><?php echo htmlspecialchars($attr['attribute_value']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Actions -->
                    <div class="product-actions">
                        <div class="quantity-selector">
                            <button class="quantity-btn" onclick="changeQuantity(-1)">-</button>
                            <input type="number" class="quantity-input" id="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                            <button class="quantity-btn" onclick="changeQuantity(1)">+</button>
                        </div>
                        
                        <button class="btn btn-primary btn-lg add-to-cart-btn" 
                                data-product-id="<?php echo $product['id']; ?>"
                                data-product-data='<?php echo json_encode($product); ?>'>
                            <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
                        </button>
                        
                        <button class="btn btn-outline btn-lg wishlist-btn" 
                                data-product-id="<?php echo $product['id']; ?>"
                                data-product-data='<?php echo json_encode($product); ?>'>
                            <i class="far fa-heart"></i> Yêu thích
                        </button>
                    </div>
                    
                    <!-- Meta Info -->
                    <div class="product-meta">
                        <div class="product-meta-item">
                            <i class="fas fa-box"></i>
                            <span>Còn <?php echo $product['stock_quantity']; ?> sản phẩm</span>
                        </div>
                        <div class="product-meta-item">
                            <i class="fas fa-shipping-fast"></i>
                            <span>Miễn phí vận chuyển</span>
                        </div>
                        <div class="product-meta-item">
                            <i class="fas fa-undo"></i>
                            <span>Đổi trả trong 7 ngày</span>
                        </div>
                        <div class="product-meta-item">
                            <i class="fas fa-shield-alt"></i>
                            <span>Bảo hành 12 tháng</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Product Tabs -->
            <div class="product-tabs">
                <div class="product-tabs-nav">
                    <button class="product-tab-btn active" onclick="showTab('description')">
                        <i class="fas fa-info-circle"></i> Mô tả
                    </button>
                    <button class="product-tab-btn" onclick="showTab('specifications')">
                        <i class="fas fa-cogs"></i> Thông số kỹ thuật
                    </button>
                    <button class="product-tab-btn" onclick="showTab('reviews')">
                        <i class="fas fa-star"></i> Đánh giá (<?php echo $product['review_count']; ?>)
                    </button>
                </div>
                
                <!-- Description Tab -->
                <div class="product-tab-content active" id="description">
                    <div class="product-description">
                        <?php echo nl2br(htmlspecialchars($product['description'] ?: 'Chưa có mô tả chi tiết.')); ?>
                    </div>
                </div>
                
                <!-- Specifications Tab -->
                <div class="product-tab-content" id="specifications">
                    <div class="product-specifications">
                        <div class="spec-item">
                            <span class="spec-label">Thương hiệu</span>
                            <span class="spec-value"><?php echo htmlspecialchars($product['brand'] ?: 'Không xác định'); ?></span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-label">Danh mục</span>
                            <span class="spec-value"><?php echo htmlspecialchars($product['category_name']); ?></span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-label">SKU</span>
                            <span class="spec-value"><?php echo htmlspecialchars($product['sku'] ?: 'N/A'); ?></span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-label">Trọng lượng</span>
                            <span class="spec-value"><?php echo $product['weight'] ? $product['weight'] . ' kg' : 'N/A'; ?></span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-label">Kích thước</span>
                            <span class="spec-value"><?php echo htmlspecialchars($product['dimensions'] ?: 'N/A'); ?></span>
                        </div>
                        <div class="spec-item">
                            <span class="spec-label">Tồn kho</span>
                            <span class="spec-value"><?php echo $product['stock_quantity']; ?> sản phẩm</span>
                        </div>
                    </div>
                </div>
                
                <!-- Reviews Tab -->
                <div class="product-tab-content" id="reviews">
                    <?php if (!empty($reviews)): ?>
                        <div class="reviews-section">
                            <?php foreach ($reviews as $review): ?>
                                <div class="review-item">
                                    <div class="review-header">
                                        <div class="review-author">
                                            <div class="review-avatar">
                                                <?php echo strtoupper(substr($review['customer_name'], 0, 1)); ?>
                                            </div>
                                            <div class="review-info">
                                                <h4><?php echo htmlspecialchars($review['customer_name']); ?></h4>
                                                <div class="review-date"><?php echo formatDate($review['created_at']); ?></div>
                                            </div>
                                        </div>
                                        <div class="review-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?php echo $i <= $review['rating'] ? '' : 'empty'; ?>">
                                                    <i class="fas fa-star"></i>
                                                </span>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <?php if ($review['title']): ?>
                                        <h5 style="margin-bottom: var(--space-2); color: var(--gray-900);"><?php echo htmlspecialchars($review['title']); ?></h5>
                                    <?php endif; ?>
                                    <div class="review-content">
                                        <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center" style="padding: var(--space-8); color: var(--gray-500);">
                            <i class="fas fa-comment-slash" style="font-size: var(--text-4xl); margin-bottom: var(--space-4);"></i>
                            <h3>Chưa có đánh giá nào</h3>
                            <p>Hãy là người đầu tiên đánh giá sản phẩm này!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Related Products -->
            <?php if (!empty($related_products)): ?>
                <div class="related-products">
                    <h2>Sản phẩm liên quan</h2>
                    <div class="related-products-grid">
                        <?php foreach ($related_products as $related): ?>
                            <div class="product-card" data-product-id="<?php echo $related['id']; ?>">
                                <div class="product-card-image">
                                    <img src="<?php echo $related['image_path'] ?: '/assets/images/placeholder.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($related['name']); ?>"
                                         loading="lazy">
                                    
                                    <?php if ($related['sale_price'] && $related['sale_price'] < $related['price']): ?>
                                        <div class="product-card-badge">Sale</div>
                                    <?php endif; ?>
                                    
                                    <div class="product-card-actions">
                                        <button class="product-card-action wishlist-btn" 
                                                data-product-id="<?php echo $related['id']; ?>"
                                                data-product-data='<?php echo json_encode($related); ?>'>
                                            <i class="far fa-heart"></i>
                                        </button>
                                        <button class="product-card-action" onclick="quickView(<?php echo $related['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="product-card-content">
                                    <h3 class="product-card-title">
                                        <a href="product.php?id=<?php echo $related['id']; ?>">
                                            <?php echo htmlspecialchars($related['name']); ?>
                                        </a>
                                    </h3>
                                    
                                    <div class="product-card-price">
                                        <span class="product-card-price-current">
                                            <?php echo formatPrice($related['sale_price'] ?: $related['price']); ?>
                                        </span>
                                        <?php if ($related['sale_price'] && $related['sale_price'] < $related['price']): ?>
                                            <span class="product-card-price-original">
                                                <?php echo formatPrice($related['price']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button class="btn btn-primary add-to-cart-btn" 
                                            data-product-id="<?php echo $related['id']; ?>"
                                            data-product-data='<?php echo json_encode($related); ?>'>
                                        <i class="fas fa-shopping-cart"></i> Thêm vào giỏ
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- JavaScript -->
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        // Change main image
        function changeMainImage(imageSrc, thumbnail) {
            document.getElementById('mainImageSrc').src = imageSrc;
            
            // Update active thumbnail
            document.querySelectorAll('.product-thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            thumbnail.classList.add('active');
        }
        
        // Quantity selector
        function changeQuantity(delta) {
            const input = document.getElementById('quantity');
            const currentValue = parseInt(input.value);
            const newValue = Math.max(1, Math.min(<?php echo $product['stock_quantity']; ?>, currentValue + delta));
            input.value = newValue;
        }
        
        // Tab switching
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.product-tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.product-tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        // Quick view function
        function quickView(productId) {
            window.location.href = `product.php?id=${productId}`;
        }
        
        // Image zoom functionality
        document.getElementById('mainImage').addEventListener('click', function() {
            const img = this.querySelector('img');
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.9);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 9999;
                cursor: zoom-out;
            `;
            
            const zoomedImg = document.createElement('img');
            zoomedImg.src = img.src;
            zoomedImg.style.cssText = `
                max-width: 90%;
                max-height: 90%;
                object-fit: contain;
            `;
            
            modal.appendChild(zoomedImg);
            document.body.appendChild(modal);
            
            modal.addEventListener('click', function() {
                document.body.removeChild(modal);
            });
        });
        
        // Initialize animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Observe elements for animation
        document.querySelectorAll('.product-card, .review-item').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>
