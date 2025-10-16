<?php
/**
 * HEADER COMPONENT
 * Header với navigation và search bar
 */

$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $cart_items = fetchData("SELECT COUNT(*) as count FROM cart WHERE user_id = ?", [$_SESSION['user_id']]);
    $cart_count = $cart_items[0]['count'] ?? 0;
} else {
    // Session cart count
    $cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
}
?>

<header class="header">
    <!-- Header Top -->
    <div class="header-top">
        <div class="container">
            <div class="header-top-content">
                <div class="header-top-left">
                    <span><i class="fas fa-phone"></i> <?php echo SITE_PHONE; ?></span>
                    <span><i class="fas fa-envelope"></i> <?php echo SITE_EMAIL; ?></span>
                </div>
                <div class="header-top-right">
                    <span><i class="fas fa-truck"></i> Miễn phí vận chuyển đơn hàng từ 500k</span>
                    <div class="header-top-links">
                        <?php if (isLoggedIn()): ?>
                            <?php $__user = getCurrentUser(); $__avatar = $__user['avatar'] ?? ''; ?>
                            <a href="my-orders.php" class="header-top-link">
                                <i class="fas fa-shopping-bag"></i> Đơn hàng của bạn
                            </a>
                            <a href="profile.php" class="header-top-link user-link">
                                <?php if (!empty($__avatar)): ?>
                                    <img src="<?php echo $__avatar; ?>" alt="Avatar">
                                <?php else: ?>
                                    <i class="fas fa-user"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($__user['full_name']); ?>
                            </a>
                            <a href="logout.php" class="header-top-link">
                                <i class="fas fa-sign-out-alt"></i> Đăng xuất
                            </a>
                        <?php else: ?>
                            <a href="login.php" class="header-top-link">
                                <i class="fas fa-sign-in-alt"></i> Đăng nhập
                            </a>
                            <a href="register.php" class="header-top-link">
                                <i class="fas fa-user-plus"></i> Đăng ký
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Header Main -->
    <div class="header-main">
        <div class="container">
            <div class="header-content">
                <!-- Logo -->
                <a href="index.php" class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <span><?php echo SITE_NAME; ?></span>
                </a>
                
                <!-- Search Bar -->
                <div class="search-bar">
                    <form action="search.php" method="GET" class="search-form">
                        <button type="submit" class="search-button">
                            <i class="fas fa-search"></i>
                        </button>
                        <input type="text" 
                               name="q" 
                               class="search-input" 
                               placeholder="Tìm kiếm sản phẩm..."
                               value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                               autocomplete="off">
                    </form>
                    <div class="search-results" style="display: none;">
                        <!-- Search results will be populated by JavaScript -->
                    </div>
                </div>
                
                <!-- Header Actions -->
                <div class="header-actions">
                    <!-- Wishlist -->
                    <a href="wishlist.php" class="header-action">
                        <i class="fas fa-heart header-action-icon"></i>
                        <span class="header-action-text">Yêu thích</span>
                        <span class="header-action-badge wishlist-counter" style="display: none;">0</span>
                    </a>
                    
                    <!-- Cart -->
                    <div class="header-action cart-action" style="position: relative;">
                        <a href="cart.php" class="header-action">
                            <i class="fas fa-shopping-cart header-action-icon"></i>
                            <span class="header-action-text">Giỏ hàng</span>
                            <span class="header-action-badge cart-counter" style="display: none;">0</span>
                        </a>
                        
                        <!-- Cart Dropdown -->
                        <div class="cart-dropdown" style="display: none;">
                            <div class="cart-empty">
                                <p>Giỏ hàng trống</p>
                                <a href="products.php" class="btn btn-primary">Mua sắm ngay</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mobile Menu Toggle -->
                    <button class="mobile-menu-toggle" style="display: none;">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="nav">
        <div class="container">
            <div class="nav-content">
                <!-- Categories Menu -->
                <div class="nav-categories">
                    <button class="nav-categories-toggle">
                        <i class="fas fa-bars"></i>
                        <span>Danh mục</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    
                    <div class="nav-categories-dropdown">
                        <?php
                        $categories = fetchData("
                            SELECT c.*, COUNT(p.id) as product_count 
                            FROM categories c 
                            LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active' 
                            WHERE c.status = 'active' AND c.parent_id IS NULL
                            GROUP BY c.id 
                            ORDER BY c.sort_order
                        ");
                        ?>
                        
                        <?php foreach ($categories as $category): ?>
                            <a href="products.php?category=<?php echo $category['slug']; ?>" class="nav-category-item">
                                <i class="fas fa-<?php echo getCategoryIcon($category['name']); ?>"></i>
                                <span><?php echo htmlspecialchars($category['name']); ?></span>
                                <span class="category-count">(<?php echo $category['product_count']; ?>)</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Main Navigation -->
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i> Trang chủ
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="products.php" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'products') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-th-large"></i> Sản phẩm
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#categories" class="nav-link">
                            <i class="fas fa-list"></i> Danh mục
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="about.php" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'about') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-info-circle"></i> Giới thiệu
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="contact.php" class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], 'contact') !== false ? 'active' : ''; ?>">
                            <i class="fas fa-envelope"></i> Liên hệ
                        </a>
                    </li>
                </ul>
                
                <!-- Special Offers -->
                <div class="nav-offers">
                    <a href="products.php?filter=sale" class="nav-offer">
                        <i class="fas fa-fire"></i>
                        <span>Sale 50%</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
</header>

<!-- Mobile Menu -->
<div class="mobile-menu" style="display: none;">
    <div class="mobile-menu-content">
        <div class="mobile-menu-header">
            <h3>Menu</h3>
            <button class="mobile-menu-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="mobile-menu-body">
            <div class="mobile-menu-section">
                <h4>Danh mục</h4>
                <?php foreach ($categories as $category): ?>
                    <a href="products.php?category=<?php echo $category['slug']; ?>" class="mobile-menu-item">
                        <i class="fas fa-<?php echo getCategoryIcon($category['name']); ?>"></i>
                        <span><?php echo htmlspecialchars($category['name']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="mobile-menu-section">
                <h4>Trang</h4>
                <a href="index.php" class="mobile-menu-item">
                    <i class="fas fa-home"></i>
                    <span>Trang chủ</span>
                </a>
                <a href="products.php" class="mobile-menu-item">
                    <i class="fas fa-th-large"></i>
                    <span>Sản phẩm</span>
                </a>
                <a href="about.php" class="mobile-menu-item">
                    <i class="fas fa-info-circle"></i>
                    <span>Giới thiệu</span>
                </a>
                <a href="contact.php" class="mobile-menu-item">
                    <i class="fas fa-envelope"></i>
                    <span>Liên hệ</span>
                </a>
            </div>
            
            <?php if (!isLoggedIn()): ?>
                <div class="mobile-menu-section">
                    <h4>Tài khoản</h4>
                    <a href="login.php" class="mobile-menu-item">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Đăng nhập</span>
                    </a>
                    <a href="register.php" class="mobile-menu-item">
                        <i class="fas fa-user-plus"></i>
                        <span>Đăng ký</span>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
/* Header Styles */
.header-top {
    background: var(--gray-900);
    color: var(--white);
    padding: var(--space-2) 0;
    font-size: var(--text-sm);
}

.header-top-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--space-4);
}

.header-top-left {
    display: flex;
    gap: var(--space-6);
}

.header-top-right {
    display: flex;
    gap: var(--space-4);
    align-items: center;
}

.header-top-links {
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-top-link {
    color: var(--gray-400);
    text-decoration: none;
    transition: color var(--transition-fast);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    line-height: 1;
}

.header-top-link img {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    object-fit: cover;
    display: block;
}

.header-top-link:hover {
    color: var(--white);
}

.header-main {
    padding: var(--space-4) 0;
    background: var(--white);
}

.header-content {
    display: flex;
    align-items: center;
    gap: var(--space-8);
}

.logo {
    font-family: var(--font-heading);
    font-size: var(--text-2xl);
    font-weight: 700;
    color: var(--primary-color);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: var(--space-2);
    flex-shrink: 0;
}

.logo-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    border-radius: var(--radius-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    font-size: var(--text-xl);
}

.search-bar {
    flex: 1;
    max-width: 600px;
    position: relative;
}

.search-form {
    position: relative;
    width: 100%;
}

.search-input {
    width: 100%;
    padding: var(--space-3) var(--space-4) var(--space-3) var(--space-12);
    border: 2px solid var(--gray-300);
    border-radius: var(--radius-full);
    font-size: var(--text-base);
    background: var(--gray-50);
    transition: all var(--transition-fast);
}

.search-input:focus {
    outline: none;
    border-color: var(--primary-color);
    background: var(--white);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.search-button {
    position: absolute;
    left: var(--space-3);
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--gray-500);
    cursor: pointer;
    transition: color var(--transition-fast);
}

.search-button:hover {
    color: var(--primary-color);
}

.search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-xl);
    max-height: 400px;
    overflow-y: auto;
    z-index: var(--z-dropdown);
    margin-top: var(--space-2);
}

.search-result-item {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3);
    border-bottom: 1px solid var(--gray-100);
    cursor: pointer;
    transition: background var(--transition-fast);
}

.search-result-item:hover {
    background: var(--gray-50);
}

.search-result-item img {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: var(--radius-md);
}

.search-result-details h4 {
    font-size: var(--text-sm);
    margin: 0 0 var(--space-1) 0;
    color: var(--gray-900);
}

.search-result-price {
    font-size: var(--text-sm);
    color: var(--primary-color);
    font-weight: 600;
    margin: 0;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: var(--space-4);
    flex-shrink: 0;
}

.header-action {
    position: relative;
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-3);
    border-radius: var(--radius-md);
    color: var(--gray-600);
    text-decoration: none;
    transition: all var(--transition-fast);
}

.header-action:hover {
    background: var(--gray-50);
    color: var(--primary-color);
}

.header-action-icon {
    font-size: var(--text-xl);
}

.header-action-text {
    font-size: var(--text-sm);
    font-weight: 500;
}

.header-action-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    background: var(--error-color);
    color: var(--white);
    font-size: var(--text-xs);
    font-weight: 600;
    padding: 2px 6px;
    border-radius: var(--radius-full);
    min-width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.cart-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 350px;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-xl);
    z-index: var(--z-dropdown);
    margin-top: var(--space-2);
}

.cart-empty {
    padding: var(--space-8);
    text-align: center;
}

.cart-empty p {
    margin-bottom: var(--space-4);
    color: var(--gray-600);
}

.cart-items {
    max-height: 300px;
    overflow-y: auto;
}

.cart-item {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-4);
    border-bottom: 1px solid var(--gray-100);
}

.cart-item-image {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: var(--radius-md);
}

.cart-item-details {
    flex: 1;
}

.cart-item-name {
    font-size: var(--text-sm);
    margin: 0 0 var(--space-1) 0;
    color: var(--gray-900);
}

.cart-item-price {
    font-size: var(--text-sm);
    color: var(--primary-color);
    font-weight: 600;
    margin: 0 0 var(--space-2) 0;
}

.cart-item-quantity {
    display: flex;
    align-items: center;
    gap: var(--space-2);
}

.quantity-btn {
    width: 24px;
    height: 24px;
    border: 1px solid var(--gray-300);
    background: var(--white);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: var(--text-xs);
    transition: all var(--transition-fast);
}

.quantity-btn:hover {
    background: var(--gray-50);
    border-color: var(--primary-color);
}

.quantity {
    font-size: var(--text-sm);
    font-weight: 500;
    min-width: 20px;
    text-align: center;
}

.cart-item-remove {
    background: none;
    border: none;
    color: var(--gray-400);
    cursor: pointer;
    font-size: var(--text-lg);
    padding: var(--space-1);
    transition: color var(--transition-fast);
}

.cart-item-remove:hover {
    color: var(--error-color);
}

.cart-footer {
    padding: var(--space-4);
    border-top: 1px solid var(--gray-200);
    background: var(--gray-50);
}

.cart-total {
    margin-bottom: var(--space-4);
    text-align: center;
}

.cart-actions {
    display: flex;
    gap: var(--space-2);
}

.cart-actions .btn {
    flex: 1;
    text-align: center;
    padding: var(--space-2) var(--space-3);
    font-size: var(--text-sm);
}

/* Navigation */
.nav {
    background: var(--gray-50);
    border-top: 1px solid var(--gray-200);
}

.nav-content {
    display: flex;
    align-items: center;
    gap: var(--space-8);
}

.nav-categories {
    position: relative;
}

.nav-categories-toggle {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-4) var(--space-6);
    background: var(--primary-color);
    color: var(--white);
    border: none;
    border-radius: var(--radius-md);
    cursor: pointer;
    font-weight: 500;
    transition: all var(--transition-fast);
}

.nav-categories-toggle:hover {
    background: var(--primary-dark);
}

.nav-categories-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-xl);
    min-width: 250px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all var(--transition-fast);
    z-index: var(--z-dropdown);
}

.nav-categories:hover .nav-categories-dropdown {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.nav-category-item {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3) var(--space-4);
    color: var(--gray-700);
    text-decoration: none;
    transition: all var(--transition-fast);
    border-bottom: 1px solid var(--gray-100);
}

.nav-category-item:last-child {
    border-bottom: none;
}

.nav-category-item:hover {
    background: var(--gray-50);
    color: var(--primary-color);
}

.category-count {
    margin-left: auto;
    font-size: var(--text-xs);
    color: var(--gray-500);
}

.nav-menu {
    display: flex;
    list-style: none;
    gap: var(--space-6);
    margin: 0;
    padding: 0;
}

.nav-item {
    position: relative;
}

.nav-link {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-4) 0;
    color: var(--gray-700);
    text-decoration: none;
    font-weight: 500;
    transition: color var(--transition-fast);
    position: relative;
}

.nav-link:hover,
.nav-link.active {
    color: var(--primary-color);
}

.nav-link::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background: var(--primary-color);
    transition: width var(--transition-fast);
}

.nav-link:hover::after,
.nav-link.active::after {
    width: 100%;
}

.nav-offers {
    margin-left: auto;
}

.nav-offer {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-4);
    background: linear-gradient(135deg, var(--error-color), #ff6b6b);
    color: var(--white);
    text-decoration: none;
    border-radius: var(--radius-full);
    font-weight: 600;
    font-size: var(--text-sm);
    transition: all var(--transition-fast);
}

.nav-offer:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
    color: var(--white);
}

/* Mobile Menu */
.mobile-menu-toggle {
    display: none;
    flex-direction: column;
    gap: 4px;
    background: none;
    border: none;
    cursor: pointer;
    padding: var(--space-2);
}

.mobile-menu-toggle span {
    width: 25px;
    height: 3px;
    background: var(--gray-700);
    transition: all var(--transition-fast);
}

.mobile-menu-toggle.active span:nth-child(1) {
    transform: rotate(45deg) translate(5px, 5px);
}

.mobile-menu-toggle.active span:nth-child(2) {
    opacity: 0;
}

.mobile-menu-toggle.active span:nth-child(3) {
    transform: rotate(-45deg) translate(7px, -6px);
}

.mobile-menu {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: var(--z-modal);
    opacity: 0;
    visibility: hidden;
    transition: all var(--transition-fast);
}

.mobile-menu.active {
    opacity: 1;
    visibility: visible;
}

.mobile-menu-content {
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 100%;
    background: var(--white);
    transform: translateX(100%);
    transition: transform var(--transition-fast);
    overflow-y: auto;
}

.mobile-menu.active .mobile-menu-content {
    transform: translateX(0);
}

.mobile-menu-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: var(--space-4);
    border-bottom: 1px solid var(--gray-200);
    background: var(--primary-color);
    color: var(--white);
}

.mobile-menu-close {
    background: none;
    border: none;
    color: var(--white);
    font-size: var(--text-xl);
    cursor: pointer;
}

.mobile-menu-body {
    padding: var(--space-4);
}

.mobile-menu-section {
    margin-bottom: var(--space-6);
}

.mobile-menu-section h4 {
    font-size: var(--text-lg);
    margin-bottom: var(--space-3);
    color: var(--gray-900);
}

.mobile-menu-item {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    padding: var(--space-3);
    color: var(--gray-700);
    text-decoration: none;
    border-radius: var(--radius-md);
    transition: all var(--transition-fast);
}

.mobile-menu-item:hover {
    background: var(--gray-50);
    color: var(--primary-color);
}

/* Responsive */
@media (max-width: 768px) {
    .header-top {
        display: none;
    }
    
    .header-content {
        flex-direction: column;
        gap: var(--space-4);
    }
    
    .search-bar {
        order: 2;
        max-width: none;
    }
    
    .header-actions {
        order: 1;
        justify-content: center;
    }
    
    .nav-content {
        flex-direction: column;
        gap: var(--space-4);
    }
    
    .nav-menu {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .nav-offers {
        margin-left: 0;
    }
    
    .mobile-menu-toggle {
        display: flex;
    }
    
    .cart-dropdown {
        width: 300px;
        right: -50px;
    }
}

@media (max-width: 480px) {
    .header-actions {
        gap: var(--space-2);
    }
    
    .header-action-text {
        display: none;
    }
    
    .cart-dropdown {
        width: 280px;
        right: -30px;
    }
    
    .mobile-menu-content {
        width: 100%;
    }
}
</style>

<?php
// Helper function to get category icon
function getCategoryIcon($categoryName) {
    $icons = [
        'Điện thoại' => 'mobile-alt',
        'Laptop' => 'laptop',
        'Đồng hồ' => 'clock',
        'Phụ kiện' => 'headphones',
        'Gaming' => 'gamepad',
        'Máy tính' => 'desktop',
        'Camera' => 'camera',
        'Âm thanh' => 'volume-up'
    ];
    
    return $icons[$categoryName] ?? 'box';
}
?>
