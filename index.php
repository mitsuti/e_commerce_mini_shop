<?php
/**
 * TRANG CHỦ - E-COMMERCE MINI SHOP
 * Trang chủ với hero section đẹp mắt và animations
 */

require_once 'config/config.php';

// Lấy dữ liệu cho trang chủ
$featured_products = fetchData("
    SELECT p.*, pi.image_path, c.name as category_name 
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.featured = 1 AND p.status = 'active' 
    ORDER BY p.created_at DESC 
    LIMIT 8
");

// Lấy 3 sản phẩm nổi bật cho banner (random mỗi lần load)
$banner_products = fetchData("
    SELECT p.id, p.name, p.description, p.price, p.sale_price, pi.image_path, c.name as category_name
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' AND pi.image_path IS NOT NULL
    ORDER BY RAND() 
    LIMIT 3
");

$new_products = fetchData("
    SELECT p.*, pi.image_path, c.name as category_name 
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 'active' 
    ORDER BY p.created_at DESC 
    LIMIT 8
");

$categories = fetchData("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active' 
    WHERE c.status = 'active' 
    GROUP BY c.id 
    ORDER BY c.sort_order 
    LIMIT 6
");

$banners = fetchData("
    SELECT * FROM banners 
    WHERE status = 'active' AND position = 'home_hero' 
    ORDER BY sort_order 
    LIMIT 3
");

$testimonials = fetchData("
    SELECT r.*, p.name as product_name 
    FROM reviews r 
    LEFT JOIN products p ON r.product_id = p.id 
    WHERE r.status = 'approved' 
    ORDER BY r.created_at DESC 
    LIMIT 6
");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Cửa hàng điện tử mini</title>
    <meta name="description" content="Cửa hàng điện tử mini với sản phẩm chất lượng cao, giá cả hợp lý. Điện thoại, laptop, đồng hồ và phụ kiện công nghệ.">
    <meta name="keywords" content="điện thoại, laptop, đồng hồ, phụ kiện, công nghệ, mua sắm online">
    
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
    
    <!-- Custom CSS for homepage -->
    <style>
        /* Hero Section với animations đặc biệt */
        .hero-slider {
            position: relative;
            height: 650px;
            overflow: hidden;
            border-radius: 0 0 2rem 2rem;
            margin-top: 0;
            padding-top: 2rem;
        }
        
        .hero-slides {
            display: flex;
            height: 100%;
            transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
        
        .hero-slide {
            min-width: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero-slide::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="10" cy="60" r="0.5" fill="rgba(255,255,255,0.05)"/><circle cx="90" cy="40" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
            animation: float 20s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
        }
        
        .hero-slide-content {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
            color: white;
        }
        
        .hero-slide-text {
            animation: slideInLeft 1s ease-out;
        }
        
        .hero-slide-image {
            animation: slideInRight 1s ease-out;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 500px;
            overflow: hidden;
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .hero-slide h1 {
            font-size: 3.2rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.2;
            background: linear-gradient(45deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            padding-top: 1rem;
            margin-top: 0;
        }
        
        .hero-slide p {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .hero-price {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        
        .hero-price .sale-price {
            font-size: 2.5rem !important;
            font-weight: 800 !important;
            color: #ff6b6b !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .hero-price .original-price {
            font-size: 1.3rem !important;
            text-decoration: line-through !important;
            opacity: 0.7 !important;
        }
        
        .hero-price .discount-badge {
            background: linear-gradient(45deg, #ff6b6b, #ff8e8e) !important;
            color: white !important;
            padding: 0.4rem 1rem !important;
            border-radius: 1.5rem !important;
            font-size: 0.9rem !important;
            font-weight: 600 !important;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .hero-price .price {
            font-size: 2.5rem !important;
            font-weight: 800 !important;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .hero-slide img {
            max-width: 100%;
            max-height: 400px;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: center;
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px rgba(0,0,0,0.3);
            transform: perspective(1000px) rotateY(-5deg) rotateX(5deg);
            transition: transform 0.3s ease;
            background: rgba(255,255,255,0.05);
            padding: 1.5rem;
            display: block;
            margin: 0 auto;
        }
        
        .hero-slide img:hover {
            transform: perspective(1000px) rotateY(0deg) rotateX(0deg) scale(1.05);
        }
        
        .hero-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.25);
            backdrop-filter: blur(15px);
            border: 2px solid rgba(255,255,255,0.3);
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 3;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .hero-nav:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-50%) scale(1.1);
        }
        
        .hero-nav.prev {
            left: 2rem;
        }
        
        .hero-nav.next {
            right: 2rem;
        }
        
        .hero-dots {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 1rem;
            z-index: 3;
        }
        
        .hero-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .hero-dot.active {
            background: white;
            transform: scale(1.2);
        }
        
        /* Flash Sale Section */
        .flash-sale {
            background: linear-gradient(135deg, #ff4757, #ff6b6b, #ffa502);
            color: white;
            padding: 4rem 0;
            position: relative;
            overflow: hidden;
            margin-top: 2rem;
            border-radius: 2rem 2rem 0 0;
        }
        
        .flash-sale::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="fire" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="2" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23fire)"/></svg>');
            animation: flicker 2s ease-in-out infinite alternate;
        }
        
        @keyframes flicker {
            0% { opacity: 0.3; }
            100% { opacity: 0.6; }
        }
        
        .countdown {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            margin: 2.5rem 0;
            flex-wrap: wrap;
        }
        
        .countdown-item {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(20px);
            padding: 1.5rem 1rem;
            border-radius: 1.5rem;
            text-align: center;
            min-width: 100px;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .countdown-item:hover {
            transform: translateY(-5px);
            background: rgba(255,255,255,0.25);
        }
        
        .countdown-number {
            font-size: 2.5rem;
            font-weight: 800;
            display: block;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .countdown-label {
            font-size: 0.9rem;
            font-weight: 500;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Floating Elements */
        .floating-element {
            position: absolute;
            animation: float 6s ease-in-out infinite;
        }
        
        .floating-element:nth-child(2) {
            animation-delay: -2s;
        }
        
        .floating-element:nth-child(3) {
            animation-delay: -4s;
        }
        
        /* Parallax Effect */
        .parallax-section {
            background-attachment: fixed;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
        
        /* Glassmorphism Cards */
        .glass-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1rem;
            padding: 2rem;
            transition: all 0.3s ease;
        }
        
        .glass-card:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-5px);
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .hero-slide-content {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 2rem;
            }
            
            .hero-slide h1 {
                font-size: 2.5rem;
            }
            
            .hero-nav {
                width: 50px;
                height: 50px;
                font-size: 1.25rem;
            }
            
            .hero-nav.prev {
                left: 1rem;
            }
            
            .hero-nav.next {
                right: 1rem;
            }
            
            .countdown {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            
            .countdown-item {
                min-width: 60px;
                padding: 0.75rem;
            }
            
            .countdown-number {
                font-size: 1.5rem;
            }
        }
        
        /* Enhanced Responsive Design */
        @media (max-width: 768px) {
            .hero-slider {
                height: 500px;
                padding-top: 1rem;
            }
            
            .hero-slide-content {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }
            
            .hero-slide-image {
                height: 300px;
            }
            
            .hero-slide img {
                max-height: 280px;
                padding: 1rem;
            }
            
            .hero-slide h1 {
                font-size: 2.5rem;
                margin-bottom: 1rem;
            }
            
            .hero-slide p {
                font-size: 1.1rem;
                margin-bottom: 1rem;
            }
            
            .hero-price .sale-price {
                font-size: 2rem !important;
            }
            
            .hero-price .price {
                font-size: 2rem !important;
            }
            
            .hero-price .original-price {
                font-size: 1.1rem !important;
            }
            
            .hero-price .discount-badge {
                font-size: 0.8rem !important;
                padding: 0.3rem 0.8rem !important;
            }
            
            .hero-actions {
                flex-direction: column;
                gap: 1rem;
            }
            
            .hero-nav {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .hero-nav.prev {
                left: 1rem;
            }
            
            .hero-nav.next {
                right: 1rem;
            }
            
            .flash-sale {
                padding: 3rem 0;
            }
            
            .flash-sale h2 {
                font-size: 2rem !important;
            }
            
            .countdown {
                gap: 1rem;
                margin: 2rem 0;
            }
            
            .countdown-item {
                min-width: 80px;
                padding: 1rem 0.5rem;
            }
            
            .countdown-number {
                font-size: 2rem;
            }
            
            .countdown-label {
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 480px) {
            .hero-slide h1 {
                font-size: 2rem;
            }
            
            .hero-slide-image {
                height: 250px;
            }
            
            .hero-slide img {
                max-height: 230px;
                padding: 0.8rem;
            }
            
            .countdown {
                gap: 0.5rem;
            }
            
            .countdown-item {
                min-width: 70px;
                padding: 0.8rem 0.3rem;
            }
            
            .countdown-number {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Hero Slider -->
    <section class="hero-slider">
        <div class="hero-slides" id="heroSlides">
            <?php if (!empty($banner_products)): ?>
                <?php foreach ($banner_products as $index => $product): ?>
                    <?php 
                    $finalPrice = $product['sale_price'] > 0 ? $product['sale_price'] : $product['price'];
                    $discountPercent = $product['sale_price'] > 0 ? round((($product['price'] - $product['sale_price']) / $product['price']) * 100) : 0;
                    ?>
                    <div class="hero-slide">
                        <div class="container">
                            <div class="hero-slide-content">
                                <div class="hero-slide-text">
                                    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                                    <p><?php echo htmlspecialchars($product['description'] ?: 'Sản phẩm chất lượng cao với giá cả hợp lý'); ?></p>
                                    
                                    <!-- Price Display -->
                                    <div class="hero-price" style="margin-bottom: 2rem;">
                                        <?php if ($product['sale_price'] > 0): ?>
                                            <span class="sale-price" style="font-size: 2rem; font-weight: 700; color: #ff6b6b;"><?php echo number_format($finalPrice); ?>₫</span>
                                            <span class="original-price" style="font-size: 1.2rem; text-decoration: line-through; opacity: 0.7; margin-left: 1rem;"><?php echo number_format($product['price']); ?>₫</span>
                                            <span class="discount-badge" style="background: #ff6b6b; color: white; padding: 0.3rem 0.8rem; border-radius: 1rem; font-size: 0.9rem; margin-left: 1rem;">-<?php echo $discountPercent; ?>%</span>
                                        <?php else: ?>
                                            <span class="price" style="font-size: 2rem; font-weight: 700;"><?php echo number_format($finalPrice); ?>₫</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="hero-actions">
                                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-lg">
                                            <i class="fas fa-shopping-cart"></i> Mua ngay
                                        </a>
                                        <a href="products.php" class="btn btn-outline btn-lg" style="color: white; border-color: white;">
                                            <i class="fas fa-eye"></i> Xem tất cả
                                        </a>
                                    </div>
                                </div>
                                <div class="hero-slide-image">
                                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <div class="floating-element" style="top: 10%; right: 10%; font-size: 2rem; opacity: 0.3;">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div class="floating-element" style="top: 60%; left: 5%; font-size: 1.5rem; opacity: 0.2;">
                                        <i class="fas fa-heart"></i>
                                    </div>
                                    <div class="floating-element" style="bottom: 20%; right: 20%; font-size: 1.8rem; opacity: 0.25;">
                                        <i class="fas fa-gem"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Default hero slide -->
                <div class="hero-slide">
                    <div class="container">
                        <div class="hero-slide-content">
                            <div class="hero-slide-text">
                                <h1>Khám phá công nghệ mới nhất</h1>
                                <p>Trải nghiệm những sản phẩm công nghệ hàng đầu với chất lượng vượt trội và giá cả hợp lý nhất thị trường.</p>
                                <div class="hero-actions">
                                    <a href="products.php" class="btn btn-primary btn-lg">
                                        <i class="fas fa-shopping-cart"></i> Mua sắm ngay
                                    </a>
                                    <a href="#categories" class="btn btn-outline btn-lg" style="color: white; border-color: white;">
                                        <i class="fas fa-th-large"></i> Danh mục
                                    </a>
                                </div>
                            </div>
                            <div class="hero-slide-image">
                                <img src="assets/images/hero-product.jpg" alt="Sản phẩm công nghệ">
                                <div class="floating-element" style="top: 10%; right: 10%; font-size: 2rem; opacity: 0.3;">
                                    <i class="fas fa-mobile-alt"></i>
                                </div>
                                <div class="floating-element" style="top: 60%; left: 5%; font-size: 1.5rem; opacity: 0.2;">
                                    <i class="fas fa-laptop"></i>
                                </div>
                                <div class="floating-element" style="bottom: 20%; right: 20%; font-size: 1.8rem; opacity: 0.25;">
                                    <i class="fas fa-headphones"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Navigation -->
        <button class="hero-nav prev" onclick="changeSlide(-1)">
            <i class="fas fa-chevron-left"></i>
        </button>
        <button class="hero-nav next" onclick="changeSlide(1)">
            <i class="fas fa-chevron-right"></i>
        </button>
        
        <!-- Dots -->
        <div class="hero-dots" id="heroDots">
            <?php for ($i = 0; $i < max(1, count($banner_products)); $i++): ?>
                <button class="hero-dot <?php echo $i === 0 ? 'active' : ''; ?>" onclick="currentSlide(<?php echo $i + 1; ?>)"></button>
            <?php endfor; ?>
        </div>
    </section>
    
    <!-- Flash Sale Section -->
    <section class="flash-sale">
        <div class="container">
            <div class="text-center">
                <h2 style="font-size: 2.5rem; margin-bottom: 1rem; position: relative; z-index: 2;">
                    <i class="fas fa-bolt"></i> FLASH SALE
                </h2>
                <p style="font-size: 1.25rem; margin-bottom: 2rem; position: relative; z-index: 2;">
                    Giảm giá lên đến 50% - Chỉ trong thời gian có hạn!
                </p>
                
                <div class="countdown" id="countdown">
                    <div class="countdown-item">
                        <span class="countdown-number" id="days">00</span>
                        <span class="countdown-label">Ngày</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-number" id="hours">00</span>
                        <span class="countdown-label">Giờ</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-number" id="minutes">00</span>
                        <span class="countdown-label">Phút</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-number" id="seconds">00</span>
                        <span class="countdown-label">Giây</span>
                    </div>
                </div>
                
                <a href="products.php?filter=sale" class="btn btn-primary btn-xl" style="background: white; color: #ff6b6b; position: relative; z-index: 2;">
                    <i class="fas fa-fire"></i> Mua ngay kẻo hết
                </a>
            </div>
        </div>
    </section>
    
    <!-- Categories Section -->
    <section class="categories" id="categories">
        <div class="container">
            <div class="categories-header">
                <h2 class="categories-title">Danh mục sản phẩm</h2>
                <p class="categories-subtitle">Khám phá các danh mục sản phẩm đa dạng với chất lượng cao</p>
            </div>
            
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                    <div class="category-card" onclick="window.location.href='products.php?category=<?php echo $category['slug']; ?>'">
                        <div class="category-icon">
                            <?php
                            $icons = [
                                'Điện thoại' => 'fas fa-mobile-alt',
                                'Laptop' => 'fas fa-laptop',
                                'Đồng hồ' => 'fas fa-clock',
                                'Phụ kiện' => 'fas fa-headphones',
                                'Gaming' => 'fas fa-gamepad'
                            ];
                            $icon = $icons[$category['name']] ?? 'fas fa-box';
                            ?>
                            <i class="<?php echo $icon; ?>"></i>
                        </div>
                        <h3 class="category-name"><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p class="category-count"><?php echo $category['product_count']; ?> sản phẩm</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Featured Products -->
    <section class="featured-products">
        <div class="container">
            <div class="featured-header">
                <h2 class="featured-title">Sản phẩm nổi bật</h2>
                <p class="featured-subtitle">Những sản phẩm được yêu thích nhất với chất lượng vượt trội</p>
            </div>
            
            <div class="products-grid">
                <?php foreach ($featured_products as $product): ?>
                    <div class="product-card" data-product-id="<?php echo $product['id']; ?>">
                        <div class="product-card-image">
                            <img src="<?php echo $product['image_path'] ?: 'assets/images/placeholder.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 loading="lazy">
                            
                            <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                <div class="product-card-badge">Sale</div>
                            <?php endif; ?>
                            
                            <div class="product-card-actions">
                                <button class="product-card-action wishlist-btn" 
                                        data-product-id="<?php echo $product['id']; ?>"
                                        data-product-data='<?php echo json_encode($product); ?>'>
                                    <i class="far fa-heart"></i>
                                </button>
                                <button class="product-card-action" onclick="quickView(<?php echo $product['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="product-card-content">
                            <h3 class="product-card-title">
                                <a href="product.php?id=<?php echo $product['id']; ?>">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </h3>
                            
                            <div class="product-card-price">
                                <span class="product-card-price-current">
                                    <?php echo formatPrice($product['sale_price'] ?: $product['price']); ?>
                                </span>
                                <?php if ($product['sale_price'] && $product['sale_price'] < $product['price']): ?>
                                    <span class="product-card-price-original">
                                        <?php echo formatPrice($product['price']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-card-rating">
                                <div class="product-card-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= 4 ? '' : 'empty'; ?>">
                                            <i class="fas fa-star"></i>
                                        </span>
                                    <?php endfor; ?>
                                </div>
                                <span class="product-card-reviews">(24 đánh giá)</span>
                            </div>
                            
                            <button class="btn btn-primary add-to-cart-btn" 
                                    data-product-id="<?php echo $product['id']; ?>"
                                    data-product-data='<?php echo json_encode($product); ?>'>
                                <i class="fas fa-shopping-cart"></i> Thêm vào giỏ
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center" style="margin-top: 3rem;">
                <a href="products.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-th-large"></i> Xem tất cả sản phẩm
                </a>
            </div>
        </div>
    </section>
    
    <!-- Testimonials -->
    <section class="testimonials">
        <div class="container">
            <div class="testimonials-header">
                <h2 class="testimonials-title">Khách hàng nói gì</h2>
                <p class="testimonials-subtitle">Những phản hồi chân thực từ khách hàng đã tin tưởng sử dụng dịch vụ</p>
            </div>
            
            <div class="testimonials-grid">
                <?php foreach ($testimonials as $testimonial): ?>
                    <div class="testimonial-card">
                        <div class="testimonial-avatar">
                            <?php echo strtoupper(substr($testimonial['customer_name'], 0, 1)); ?>
                        </div>
                        <div class="testimonial-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?php echo $i <= $testimonial['rating'] ? '' : 'empty'; ?>">
                                    <i class="fas fa-star"></i>
                                </span>
                            <?php endfor; ?>
                        </div>
                        <p class="testimonial-content">"<?php echo htmlspecialchars($testimonial['comment']); ?>"</p>
                        <h4 class="testimonial-author"><?php echo htmlspecialchars($testimonial['customer_name']); ?></h4>
                        <p class="testimonial-role">Khách hàng</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <!-- Newsletter -->
    <section class="newsletter">
        <div class="container">
            <div class="newsletter-content">
                <h2 class="newsletter-title">Đăng ký nhận tin</h2>
                <p class="newsletter-subtitle">Nhận thông tin về sản phẩm mới và ưu đãi đặc biệt</p>
                
                <form class="newsletter-form" id="newsletterForm">
                    <input type="email" class="newsletter-input" placeholder="Nhập email của bạn" required>
                    <button type="submit" class="newsletter-button">
                        <i class="fas fa-paper-plane"></i> Đăng ký
                    </button>
                </form>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- JavaScript -->
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        // Hero Slider
        let currentSlideIndex = 0;
        const slides = document.querySelectorAll('.hero-slide');
        const totalSlides = slides.length;
        
        function showSlide(index) {
            const slidesContainer = document.getElementById('heroSlides');
            slidesContainer.style.transform = `translateX(-${index * 100}%)`;
            
            // Update dots
            document.querySelectorAll('.hero-dot').forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
        }
        
        function changeSlide(direction) {
            currentSlideIndex += direction;
            if (currentSlideIndex >= totalSlides) currentSlideIndex = 0;
            if (currentSlideIndex < 0) currentSlideIndex = totalSlides - 1;
            showSlide(currentSlideIndex);
        }
        
        function currentSlide(index) {
            currentSlideIndex = index - 1;
            showSlide(currentSlideIndex);
        }
        
        // Auto slide
        setInterval(() => {
            changeSlide(1);
        }, 5000);
        
        // Countdown Timer
        function updateCountdown() {
            const now = new Date().getTime();
            // Set end time to end of current month
            const endOfMonth = new Date();
            endOfMonth.setMonth(endOfMonth.getMonth() + 1);
            endOfMonth.setDate(1);
            endOfMonth.setHours(0, 0, 0, 0);
            
            const timeLeft = endOfMonth - now;
            
            if (timeLeft > 0) {
                const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                
                document.getElementById('days').textContent = days.toString().padStart(2, '0');
                document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
                document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
                document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
            } else {
                // Sale ended
                document.getElementById('days').textContent = '00';
                document.getElementById('hours').textContent = '00';
                document.getElementById('minutes').textContent = '00';
                document.getElementById('seconds').textContent = '00';
            }
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
        
        // Newsletter Form
        document.getElementById('newsletterForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('.newsletter-input').value;
            
            // Simulate API call
            setTimeout(() => {
                showToast('Cảm ơn bạn đã đăng ký nhận tin!', 'success');
                this.reset();
            }, 1000);
        });
        
        // Quick View Function
        function quickView(productId) {
            // This will be implemented in product.js
            window.location.href = `product.php?id=${productId}`;
        }
        
        // Initialize animations on scroll
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
        document.querySelectorAll('.category-card, .product-card, .testimonial-card').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>
