<?php
/**
 * TRANG DANH SÁCH SẢN PHẨM
 * Trang hiển thị danh sách sản phẩm với filters và search
 */

require_once 'config/config.php';

// Lấy tham số từ URL
$page = max(1, intval($_GET['page'] ?? 1));
$category_slug = trim($_GET['category'] ?? '');
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'newest';
$min_price = floatval($_GET['min_price'] ?? 0);
$max_price = floatval($_GET['max_price'] ?? 0);
$brand = trim($_GET['brand'] ?? '');
$rating = intval($_GET['rating'] ?? 0);

// Lấy category_id từ slug nếu có
$category_id = 0;
if (!empty($category_slug)) {
    $category_info = fetchOne("SELECT id FROM categories WHERE slug = ? AND status = 'active'", [$category_slug]);
    if ($category_info) {
        $category_id = $category_info['id'];
    }
}

// Tính offset cho pagination
$offset = ($page - 1) * PRODUCTS_PER_PAGE;

// Xây dựng query
$where_conditions = ["p.status = 'active'"];
$params = [];

// Filter theo category
if ($category_id > 0) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_id;
}

// Filter theo search
if (!empty($search)) {
    $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Filter theo giá
if ($min_price > 0) {
    $where_conditions[] = "COALESCE(p.sale_price, p.price) >= ?";
    $params[] = $min_price;
}

if ($max_price > 0) {
    $where_conditions[] = "COALESCE(p.sale_price, p.price) <= ?";
    $params[] = $max_price;
}

// Filter theo brand
if (!empty($brand)) {
    $where_conditions[] = "p.brand = ?";
    $params[] = $brand;
}

// Filter theo rating
if ($rating > 0) {
    $where_conditions[] = "COALESCE((SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND status = 'approved'), 0) >= ?";
    $params[] = $rating;
}

$where_clause = implode(' AND ', $where_conditions);

// Xác định sort order
$sort_orders = [
    'newest' => 'p.created_at DESC',
    'oldest' => 'p.created_at ASC',
    'price_low' => 'COALESCE(p.sale_price, p.price) ASC',
    'price_high' => 'COALESCE(p.sale_price, p.price) DESC',
    'name_asc' => 'p.name ASC',
    'name_desc' => 'p.name DESC',
    'rating' => 'COALESCE((SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND status = \'approved\'), 0) DESC'
];

$order_by = $sort_orders[$sort] ?? $sort_orders['newest'];

// Lấy tổng số sản phẩm
$count_sql = "
    SELECT COUNT(*) as total 
    FROM products p 
    WHERE $where_clause
";
$total_products = fetchOne($count_sql, $params)['total'];

// Lấy danh sách sản phẩm
$products_sql = "
    SELECT p.*, 
           pi.image_path, 
           c.name as category_name,
           COALESCE((SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND status = 'approved'), 0) as avg_rating,
           (SELECT COUNT(*) FROM reviews WHERE product_id = p.id AND status = 'approved') as review_count
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE $where_clause
    ORDER BY $order_by
    LIMIT ? OFFSET ?
";

$params[] = PRODUCTS_PER_PAGE;
$params[] = $offset;

$products = fetchData($products_sql, $params);

// Lấy danh sách categories cho filter
$categories = fetchData("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active' 
    WHERE c.status = 'active' 
    GROUP BY c.id 
    ORDER BY c.sort_order
");

// Lấy danh sách brands cho filter
$brands = fetchData("
    SELECT DISTINCT brand, COUNT(*) as product_count 
    FROM products 
    WHERE status = 'active' AND brand IS NOT NULL AND brand != '' 
    GROUP BY brand 
    ORDER BY brand
");

// Tính pagination
$total_pages = ceil($total_products / PRODUCTS_PER_PAGE);

// Lấy category info nếu có
$category_info = null;
if ($category_id > 0) {
    $category_info = fetchOne("SELECT * FROM categories WHERE id = ?", [$category_id]);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php 
        if ($category_info) {
            echo htmlspecialchars($category_info['name']) . ' - ';
        }
        if (!empty($search)) {
            echo 'Tìm kiếm: ' . htmlspecialchars($search) . ' - ';
        }
        echo SITE_NAME . ' - Sản phẩm';
        ?>
    </title>
    <meta name="description" content="Khám phá các sản phẩm chất lượng cao với giá cả hợp lý. Tìm kiếm và lọc sản phẩm theo danh mục, giá, thương hiệu.">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/components.css">
    
    <!-- Custom CSS for products page -->
    <style>
        .products-page {
            padding: var(--space-8) 0;
            background: var(--gray-50);
            min-height: 100vh;
        }
        
        .products-header {
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
            margin-bottom: var(--space-2);
            color: var(--gray-900);
        }
        
        .page-subtitle {
            color: var(--gray-600);
            font-size: var(--text-lg);
        }
        
        .products-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: var(--space-8);
        }
        
        .products-sidebar {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--space-6);
            box-shadow: var(--shadow-md);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        
        .sidebar-section {
            margin-bottom: var(--space-8);
        }
        
        .sidebar-section:last-child {
            margin-bottom: 0;
        }
        
        .sidebar-title {
            font-size: var(--text-lg);
            font-weight: 600;
            margin-bottom: var(--space-4);
            color: var(--gray-900);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }
        
        .filter-group {
            margin-bottom: var(--space-4);
        }
        
        .filter-label {
            display: block;
            font-size: var(--text-sm);
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: var(--space-2);
        }
        
        .filter-input {
            width: 100%;
            padding: var(--space-2) var(--space-3);
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-md);
            font-size: var(--text-sm);
        }
        
        .filter-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .price-range {
            display: flex;
            gap: var(--space-2);
            align-items: center;
        }
        
        .price-range input {
            flex: 1;
        }
        
        .filter-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .filter-item {
            margin-bottom: var(--space-2);
        }
        
        .filter-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--space-2) var(--space-3);
            color: var(--gray-600);
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: all var(--transition-fast);
        }
        
        .filter-link:hover,
        .filter-link.active {
            background: var(--gray-50);
            color: var(--primary-color);
        }
        
        .filter-count {
            font-size: var(--text-xs);
            color: var(--gray-500);
            background: var(--gray-200);
            padding: 2px 6px;
            border-radius: var(--radius-full);
        }
        
        .filter-link.active .filter-count {
            background: var(--primary-color);
            color: var(--white);
        }
        
        .rating-filter {
            display: flex;
            flex-direction: column;
            gap: var(--space-2);
        }
        
        .rating-option {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            padding: var(--space-2);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: background var(--transition-fast);
        }
        
        .rating-option:hover {
            background: var(--gray-50);
        }
        
        .rating-option input[type="radio"] {
            margin: 0;
        }
        
        .rating-stars {
            display: flex;
            gap: 2px;
        }
        
        .rating-stars .star {
            color: var(--warning-color);
            font-size: var(--text-sm);
        }
        
        .rating-stars .star.empty {
            color: var(--gray-300);
        }
        
        .clear-filters {
            width: 100%;
            padding: var(--space-3);
            background: var(--gray-100);
            color: var(--gray-700);
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition-fast);
            margin-top: var(--space-4);
        }
        
        .clear-filters:hover {
            background: var(--gray-200);
        }
        
        .products-main {
            background: var(--white);
            border-radius: var(--radius-xl);
            padding: var(--space-6);
            box-shadow: var(--shadow-md);
        }
        
        .products-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-6);
            flex-wrap: wrap;
            gap: var(--space-4);
        }
        
        .products-count {
            color: var(--gray-600);
            font-size: var(--text-sm);
        }
        
        .products-sort {
            display: flex;
            align-items: center;
            gap: var(--space-3);
        }
        
        .sort-label {
            font-size: var(--text-sm);
            color: var(--gray-700);
        }
        
        .sort-select {
            padding: var(--space-2) var(--space-3);
            border: 1px solid var(--gray-300);
            border-radius: var(--radius-md);
            background: var(--white);
            font-size: var(--text-sm);
            cursor: pointer;
        }
        
        .view-toggle {
            display: flex;
            gap: var(--space-1);
        }
        
        .view-btn {
            width: 40px;
            height: 40px;
            border: 1px solid var(--gray-300);
            background: var(--white);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        
        .view-btn.active,
        .view-btn:hover {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: var(--space-6);
        }
        
        .products-grid.list-view {
            grid-template-columns: 1fr;
        }
        
        .products-grid.list-view .product-card {
            display: flex;
            flex-direction: row;
            align-items: center;
        }
        
        .products-grid.list-view .product-card-image {
            width: 200px;
            height: 200px;
            flex-shrink: 0;
        }
        
        .products-grid.list-view .product-card-content {
            flex: 1;
            padding: var(--space-4);
        }
        
        .no-products {
            text-align: center;
            padding: var(--space-16);
            color: var(--gray-500);
        }
        
        .no-products i {
            font-size: var(--text-6xl);
            margin-bottom: var(--space-4);
            color: var(--gray-300);
        }
        
        .no-products h3 {
            font-size: var(--text-2xl);
            margin-bottom: var(--space-2);
            color: var(--gray-700);
        }
        
        .no-products p {
            margin-bottom: var(--space-6);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: var(--space-2);
            margin-top: var(--space-12);
        }
        
        .pagination-btn {
            padding: var(--space-2) var(--space-3);
            border: 1px solid var(--gray-300);
            background: var(--white);
            color: var(--gray-700);
            text-decoration: none;
            border-radius: var(--radius-md);
            transition: all var(--transition-fast);
            min-width: 40px;
            text-align: center;
        }
        
        .pagination-btn:hover,
        .pagination-btn.active {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }
        
        .pagination-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination-btn:disabled:hover {
            background: var(--white);
            color: var(--gray-700);
            border-color: var(--gray-300);
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .products-layout {
                grid-template-columns: 1fr;
                gap: var(--space-6);
            }
            
            .products-sidebar {
                position: static;
                order: 2;
            }
            
            .products-main {
                order: 1;
            }
            
            .products-toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .products-sort {
                justify-content: space-between;
            }
            
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: var(--space-4);
            }
        }
        
        @media (max-width: 480px) {
            .products-grid {
                grid-template-columns: 1fr;
            }
            
            .page-title {
                font-size: var(--text-3xl);
            }
            
            .products-sidebar {
                padding: var(--space-4);
            }
            
            .products-main {
                padding: var(--space-4);
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <!-- Products Page -->
    <div class="products-page">
        <!-- Page Header -->
        <div class="products-header">
            <div class="container">
                <!-- Breadcrumb -->
                <nav class="breadcrumb">
                    <a href="index.php">Trang chủ</a>
                    <span class="breadcrumb-separator">/</span>
                    <?php if ($category_info): ?>
                        <a href="products.php">Sản phẩm</a>
                        <span class="breadcrumb-separator">/</span>
                        <span><?php echo htmlspecialchars($category_info['name']); ?></span>
                    <?php elseif (!empty($search)): ?>
                        <a href="products.php">Sản phẩm</a>
                        <span class="breadcrumb-separator">/</span>
                        <span>Tìm kiếm: <?php echo htmlspecialchars($search); ?></span>
                    <?php else: ?>
                        <span>Sản phẩm</span>
                    <?php endif; ?>
                </nav>
                
                <!-- Page Title -->
                <h1 class="page-title">
                    <?php 
                    if ($category_info) {
                        echo htmlspecialchars($category_info['name']);
                    } elseif (!empty($search)) {
                        echo 'Tìm kiếm: ' . htmlspecialchars($search);
                    } else {
                        echo 'Tất cả sản phẩm';
                    }
                    ?>
                </h1>
                
                <p class="page-subtitle">
                    <?php 
                    if ($category_info && $category_info['description']) {
                        echo htmlspecialchars($category_info['description']);
                    } else {
                        echo 'Khám phá các sản phẩm chất lượng cao với giá cả hợp lý';
                    }
                    ?>
                </p>
            </div>
        </div>
        
        <div class="container">
            <div class="products-layout">
                <!-- Sidebar -->
                <aside class="products-sidebar">
                    <form method="GET" id="filterForm">
                        <!-- Search -->
                        <div class="sidebar-section">
                            <h3 class="sidebar-title">
                                <i class="fas fa-search"></i>
                                Tìm kiếm
                            </h3>
                            <input type="text" 
                                   name="q" 
                                   class="filter-input" 
                                   placeholder="Tìm sản phẩm..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <!-- Categories -->
                        <div class="sidebar-section">
                            <h3 class="sidebar-title">
                                <i class="fas fa-th-large"></i>
                                Danh mục
                            </h3>
                            <ul class="filter-list">
                                <li class="filter-item">
                                    <a href="?" class="filter-link <?php echo $category_id == 0 ? 'active' : ''; ?>">
                                        <span>Tất cả</span>
                                        <span class="filter-count"><?php echo $total_products; ?></span>
                                    </a>
                                </li>
                                <?php foreach ($categories as $cat): ?>
                                    <li class="filter-item">
                                        <a href="?category=<?php echo $cat['id']; ?>" 
                                           class="filter-link <?php echo $category_id == $cat['id'] ? 'active' : ''; ?>">
                                            <span><?php echo htmlspecialchars($cat['name']); ?></span>
                                            <span class="filter-count"><?php echo $cat['product_count']; ?></span>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        
                        <!-- Price Range -->
                        <div class="sidebar-section">
                            <h3 class="sidebar-title">
                                <i class="fas fa-dollar-sign"></i>
                                Khoảng giá
                            </h3>
                            <div class="price-range">
                                <input type="number" 
                                       name="min_price" 
                                       class="filter-input" 
                                       placeholder="Từ"
                                       value="<?php echo $min_price > 0 ? $min_price : ''; ?>">
                                <span>-</span>
                                <input type="number" 
                                       name="max_price" 
                                       class="filter-input" 
                                       placeholder="Đến"
                                       value="<?php echo $max_price > 0 ? $max_price : ''; ?>">
                            </div>
                        </div>
                        
                        <!-- Brands -->
                        <?php if (!empty($brands)): ?>
                            <div class="sidebar-section">
                                <h3 class="sidebar-title">
                                    <i class="fas fa-tags"></i>
                                    Thương hiệu
                                </h3>
                                <ul class="filter-list">
                                    <?php foreach ($brands as $brand_item): ?>
                                        <li class="filter-item">
                                            <a href="?brand=<?php echo urlencode($brand_item['brand']); ?>" 
                                               class="filter-link <?php echo $brand === $brand_item['brand'] ? 'active' : ''; ?>">
                                                <span><?php echo htmlspecialchars($brand_item['brand']); ?></span>
                                                <span class="filter-count"><?php echo $brand_item['product_count']; ?></span>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Rating -->
                        <div class="sidebar-section">
                            <h3 class="sidebar-title">
                                <i class="fas fa-star"></i>
                                Đánh giá
                            </h3>
                            <div class="rating-filter">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                    <label class="rating-option">
                                        <input type="radio" 
                                               name="rating" 
                                               value="<?php echo $i; ?>"
                                               <?php echo $rating == $i ? 'checked' : ''; ?>>
                                        <div class="rating-stars">
                                            <?php for ($j = 1; $j <= 5; $j++): ?>
                                                <span class="star <?php echo $j <= $i ? '' : 'empty'; ?>">
                                                    <i class="fas fa-star"></i>
                                                </span>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="rating-text"><?php echo $i; ?> sao trở lên</span>
                                    </label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <!-- Hidden fields -->
                        <input type="hidden" name="category" value="<?php echo $category_id; ?>">
                        <input type="hidden" name="sort" value="<?php echo $sort; ?>">
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: var(--space-4);">
                            <i class="fas fa-filter"></i> Áp dụng bộ lọc
                        </button>
                        
                        <button type="button" class="clear-filters" onclick="clearFilters()">
                            <i class="fas fa-times"></i> Xóa bộ lọc
                        </button>
                    </form>
                </aside>
                
                <!-- Main Content -->
                <main class="products-main">
                    <!-- Toolbar -->
                    <div class="products-toolbar">
                        <div class="products-count">
                            Hiển thị <?php echo count($products); ?> trong <?php echo $total_products; ?> sản phẩm
                        </div>
                        
                        <div class="products-sort">
                            <span class="sort-label">Sắp xếp theo:</span>
                            <select class="sort-select" onchange="changeSort(this.value)">
                                <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                                <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Cũ nhất</option>
                                <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Giá thấp đến cao</option>
                                <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Giá cao đến thấp</option>
                                <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Tên A-Z</option>
                                <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Tên Z-A</option>
                                <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Đánh giá cao</option>
                            </select>
                        </div>
                        
                        <div class="view-toggle">
                            <button class="view-btn active" onclick="changeView('grid')" data-view="grid">
                                <i class="fas fa-th"></i>
                            </button>
                            <button class="view-btn" onclick="changeView('list')" data-view="list">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Products Grid -->
                    <?php if (!empty($products)): ?>
                        <div class="products-grid" id="productsGrid">
                            <?php foreach ($products as $product): ?>
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
                                                <?php 
                                                $avg_rating = round($product['avg_rating']);
                                                for ($i = 1; $i <= 5; $i++): 
                                                ?>
                                                    <span class="star <?php echo $i <= $avg_rating ? '' : 'empty'; ?>">
                                                        <i class="fas fa-star"></i>
                                                    </span>
                                                <?php endfor; ?>
                                            </div>
                                            <span class="product-card-reviews">(<?php echo $product['review_count']; ?> đánh giá)</span>
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
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                       class="pagination-btn">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                <?php endif; ?>
                                
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                if ($start_page > 1): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" 
                                       class="pagination-btn">1</a>
                                    <?php if ($start_page > 2): ?>
                                        <span class="pagination-btn" style="border: none; background: none;">...</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                                       class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($end_page < $total_pages): ?>
                                    <?php if ($end_page < $total_pages - 1): ?>
                                        <span class="pagination-btn" style="border: none; background: none;">...</span>
                                    <?php endif; ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" 
                                       class="pagination-btn"><?php echo $total_pages; ?></a>
                                <?php endif; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                       class="pagination-btn">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="no-products">
                            <i class="fas fa-search"></i>
                            <h3>Không tìm thấy sản phẩm nào</h3>
                            <p>Hãy thử điều chỉnh bộ lọc hoặc tìm kiếm với từ khóa khác</p>
                            <a href="products.php" class="btn btn-primary">
                                <i class="fas fa-refresh"></i> Xem tất cả sản phẩm
                            </a>
                        </div>
                    <?php endif; ?>
                </main>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- JavaScript -->
    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        // View Toggle
        function changeView(view) {
            const grid = document.getElementById('productsGrid');
            const buttons = document.querySelectorAll('.view-btn');
            
            buttons.forEach(btn => btn.classList.remove('active'));
            document.querySelector(`[data-view="${view}"]`).classList.add('active');
            
            if (view === 'list') {
                grid.classList.add('list-view');
            } else {
                grid.classList.remove('list-view');
            }
            
            // Save preference
            localStorage.setItem('productView', view);
        }
        
        // Load saved view preference
        const savedView = localStorage.getItem('productView') || 'grid';
        if (savedView === 'list') {
            changeView('list');
        }
        
        // Sort Change
        function changeSort(sort) {
            const url = new URL(window.location);
            url.searchParams.set('sort', sort);
            window.location.href = url.toString();
        }
        
        // Clear Filters
        function clearFilters() {
            const url = new URL(window.location);
            url.search = '';
            window.location.href = url.toString();
        }
        
        // Filter Form Auto Submit
        document.getElementById('filterForm').addEventListener('change', function() {
            // Debounce the form submission
            clearTimeout(this.submitTimeout);
            this.submitTimeout = setTimeout(() => {
                this.submit();
            }, 500);
        });
        
        // Price Range Input
        const priceInputs = document.querySelectorAll('input[name="min_price"], input[name="max_price"]');
        priceInputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value && !isNaN(this.value)) {
                    document.getElementById('filterForm').submit();
                }
            });
        });
        
        // Rating Filter
        const ratingInputs = document.querySelectorAll('input[name="rating"]');
        ratingInputs.forEach(input => {
            input.addEventListener('change', function() {
                document.getElementById('filterForm').submit();
            });
        });
        
        // Quick View Function
        function quickView(productId) {
            window.location.href = `product.php?id=${productId}`;
        }
        
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
        
        // Observe product cards
        document.querySelectorAll('.product-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    </script>
</body>
</html>
