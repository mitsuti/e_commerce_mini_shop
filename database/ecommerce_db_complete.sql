-- =============================================
-- E-COMMERCE MINI SHOP - COMPLETE DATABASE
-- File SQL hoàn chỉnh để import vào MySQL
-- =============================================

-- Tạo database
CREATE DATABASE IF NOT EXISTS ecommerce_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecommerce_db;

-- =============================================
-- BẢNG USERS - Quản lý người dùng
-- =============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('admin', 'customer') DEFAULT 'customer',
    avatar VARCHAR(255),
    status ENUM('active', 'inactive') DEFAULT 'active',
    email_verified BOOLEAN DEFAULT FALSE,
    remember_token VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- BẢNG CATEGORIES - Danh mục sản phẩm
-- =============================================
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    image VARCHAR(255),
    parent_id INT DEFAULT NULL,
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- =============================================
-- BẢNG PRODUCTS - Sản phẩm
-- =============================================
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    short_description VARCHAR(500),
    price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) DEFAULT NULL,
    sku VARCHAR(100) UNIQUE,
    stock_quantity INT DEFAULT 0,
    min_stock_level INT DEFAULT 5,
    weight DECIMAL(8,2) DEFAULT 0,
    dimensions VARCHAR(100),
    category_id INT NOT NULL,
    brand VARCHAR(100),
    status ENUM('active', 'inactive', 'out_of_stock') DEFAULT 'active',
    featured BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    meta_title VARCHAR(200),
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- =============================================
-- BẢNG PRODUCT_IMAGES - Hình ảnh sản phẩm
-- =============================================
CREATE TABLE product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(200),
    sort_order INT DEFAULT 0,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- =============================================
-- BẢNG PRODUCT_ATTRIBUTES - Thuộc tính sản phẩm
-- =============================================
CREATE TABLE product_attributes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    attribute_name VARCHAR(100) NOT NULL,
    attribute_value VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- =============================================
-- BẢNG CART - Giỏ hàng
-- =============================================
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    session_id VARCHAR(100),
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id),
    UNIQUE KEY unique_session_cart_item (session_id, product_id)
);

-- =============================================
-- BẢNG ORDERS - Đơn hàng
-- =============================================
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    user_id INT,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(20) NOT NULL,
    shipping_address TEXT NOT NULL,
    billing_address TEXT,
    subtotal DECIMAL(10,2) NOT NULL,
    shipping_fee DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cod', 'bank_transfer', 'credit_card') DEFAULT 'cod',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    order_status ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    shipped_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- BẢNG ORDER_ITEMS - Chi tiết đơn hàng
-- =============================================
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    product_sku VARCHAR(100),
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- =============================================
-- BẢNG COUPONS - Mã giảm giá
-- =============================================
CREATE TABLE coupons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('percentage', 'fixed') NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    minimum_amount DECIMAL(10,2) DEFAULT 0,
    maximum_discount DECIMAL(10,2) DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    start_date TIMESTAMP NOT NULL,
    end_date TIMESTAMP NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- BẢNG REVIEWS - Đánh giá sản phẩm
-- =============================================
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    user_id INT,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(200),
    comment TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =============================================
-- BẢNG WISHLIST - Danh sách yêu thích
-- =============================================
CREATE TABLE wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist_item (user_id, product_id)
);

-- =============================================
-- BẢNG SETTINGS - Cài đặt hệ thống
-- =============================================
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- BẢNG BANNERS - Banner quảng cáo
-- =============================================
CREATE TABLE banners (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    image VARCHAR(255) NOT NULL,
    link VARCHAR(255),
    position ENUM('home_hero', 'home_featured', 'category_top', 'sidebar') NOT NULL,
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- TẠO INDEXES ĐỂ TỐI ƯU HIỆU SUẤT
-- =============================================
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_status ON products(status);
CREATE INDEX idx_products_featured ON products(featured);
CREATE INDEX idx_products_price ON products(price);
CREATE INDEX idx_products_created ON products(created_at);
CREATE INDEX idx_products_slug ON products(slug);

CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(order_status);
CREATE INDEX idx_orders_created ON orders(created_at);
CREATE INDEX idx_orders_number ON orders(order_number);

CREATE INDEX idx_cart_user ON cart(user_id);
CREATE INDEX idx_cart_session ON cart(session_id);

CREATE INDEX idx_reviews_product ON reviews(product_id);
CREATE INDEX idx_reviews_status ON reviews(status);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);

-- =============================================
-- INSERT DỮ LIỆU MẪU
-- =============================================

-- Insert admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, phone, role, status, email_verified) VALUES
('admin', 'admin@minishop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', '0123-456-789', 'admin', 'active', TRUE);

-- Insert customer users (password: password)
INSERT INTO users (username, email, password, full_name, phone, address, role, status, email_verified) VALUES
('customer1', 'customer1@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn A', '0987-654-321', '123 Đường ABC, Quận 1, TP.HCM', 'customer', 'active', TRUE),
('customer2', 'customer2@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Thị B', '0912-345-678', '456 Đường XYZ, Quận 2, TP.HCM', 'customer', 'active', TRUE),
('customer3', 'customer3@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lê Văn C', '0901-234-567', '789 Đường DEF, Quận 3, TP.HCM', 'customer', 'active', TRUE);

-- Insert categories
INSERT INTO categories (name, slug, description, sort_order) VALUES
('Điện thoại', 'dien-thoai', 'Điện thoại thông minh và phụ kiện', 1),
('Laptop', 'laptop', 'Máy tính xách tay và phụ kiện', 2),
('Đồng hồ', 'dong-ho', 'Đồng hồ thông minh và đồng hồ đeo tay', 3),
('Phụ kiện', 'phu-kien', 'Phụ kiện điện tử và công nghệ', 4),
('Gaming', 'gaming', 'Thiết bị gaming và phụ kiện', 5),
('Máy tính', 'may-tinh', 'Máy tính để bàn và linh kiện', 6);

-- Insert products
INSERT INTO products (name, slug, description, short_description, price, sale_price, sku, stock_quantity, category_id, brand, featured, status) VALUES
('iPhone 15 Pro Max', 'iphone-15-pro-max', 'iPhone 15 Pro Max với chip A17 Pro mạnh mẽ, camera 48MP và màn hình Super Retina XDR 6.7 inch. Thiết kế titan cao cấp, chống nước IP68, pin trâu cả ngày.', 'iPhone 15 Pro Max - Flagship mới nhất từ Apple với chip A17 Pro', 29990000, 27990000, 'IPH15PM-256', 50, 1, 'Apple', TRUE, 'active'),

('Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'Galaxy S24 Ultra với S Pen, camera 200MP và màn hình Dynamic AMOLED 2X 6.8 inch. Hiệu năng mạnh mẽ với chip Snapdragon 8 Gen 3.', 'Galaxy S24 Ultra - Điện thoại Android cao cấp với S Pen', 26990000, 24990000, 'SGS24U-512', 30, 1, 'Samsung', TRUE, 'active'),

('MacBook Pro M3', 'macbook-pro-m3', 'MacBook Pro 14 inch với chip M3, 16GB RAM và SSD 512GB. Hiệu năng vượt trội, pin trâu 18 giờ, màn hình Liquid Retina XDR tuyệt đẹp.', 'MacBook Pro M3 - Laptop chuyên nghiệp với chip M3 mạnh mẽ', 45990000, 42990000, 'MBP14-M3-512', 20, 2, 'Apple', TRUE, 'active'),

('Dell XPS 13', 'dell-xps-13', 'Dell XPS 13 với Intel Core i7, 16GB RAM và SSD 512GB. Thiết kế sang trọng, màn hình 13.4 inch 4K, pin trâu 12 giờ.', 'Dell XPS 13 - Laptop cao cấp với thiết kế sang trọng', 28990000, 26990000, 'DXPS13-I7-512', 25, 2, 'Dell', FALSE, 'active'),

('Apple Watch Series 9', 'apple-watch-series-9', 'Apple Watch Series 9 với chip S9, màn hình Always-On và nhiều tính năng sức khỏe. Theo dõi tim mạch, SpO2, giấc ngủ chính xác.', 'Apple Watch Series 9 - Đồng hồ thông minh với chip S9', 8990000, 7990000, 'AWS9-45MM', 40, 3, 'Apple', TRUE, 'active'),

('Samsung Galaxy Watch 6', 'samsung-galaxy-watch-6', 'Galaxy Watch 6 với Wear OS, theo dõi sức khỏe và pin 2 ngày. Màn hình AMOLED sắc nét, chống nước 5ATM.', 'Galaxy Watch 6 - Đồng hồ thông minh Android', 5990000, 5490000, 'SGW6-44MM', 35, 3, 'Samsung', FALSE, 'active'),

('AirPods Pro 2', 'airpods-pro-2', 'AirPods Pro thế hệ 2 với chip H2, chống ồn chủ động và âm thanh không gian. Pin trâu 6 giờ, case sạc nhanh.', 'AirPods Pro 2 - Tai nghe không dây cao cấp', 5990000, 5490000, 'APP2-USB-C', 60, 4, 'Apple', TRUE, 'active'),

('Sony WH-1000XM5', 'sony-wh-1000xm5', 'Sony WH-1000XM5 với chống ồn hàng đầu và âm thanh chất lượng cao. Pin 30 giờ, sạc nhanh 3 phút = 3 giờ nghe.', 'Sony WH-1000XM5 - Tai nghe chống ồn hàng đầu', 8990000, 7990000, 'SW1000XM5', 15, 4, 'Sony', FALSE, 'active'),

('Gaming Chair Pro', 'gaming-chair-pro', 'Ghế gaming cao cấp với đệm lưng và cổ, điều chỉnh chiều cao và màu RGB. Chất liệu da PU cao cấp, chịu lực 150kg.', 'Gaming Chair Pro - Ghế gaming chuyên nghiệp', 2999000, 2499000, 'GC-PRO-RGB', 20, 5, 'Gaming Pro', FALSE, 'active'),

('Mechanical Keyboard RGB', 'mechanical-keyboard-rgb', 'Bàn phím cơ RGB với switch Cherry MX Blue và đèn LED đa màu. Thiết kế 87 phím, dây USB-C có thể tháo rời.', 'Mechanical Keyboard RGB - Bàn phím gaming', 1999000, 1799000, 'MKB-RGB-MX', 30, 5, 'Gaming Gear', FALSE, 'active'),

('Gaming Mouse Wireless', 'gaming-mouse-wireless', 'Chuột gaming không dây với DPI cao, RGB và pin 70 giờ. Cảm biến quang học chính xác, 6 nút có thể lập trình.', 'Gaming Mouse Wireless - Chuột gaming cao cấp', 1299000, 999000, 'GM-WIRELESS-RGB', 25, 5, 'Gaming Gear', FALSE, 'active'),

('Monitor 4K 27 inch', 'monitor-4k-27-inch', 'Màn hình 4K 27 inch với tần số quét 144Hz và HDR. Màu sắc chính xác 99% sRGB, thời gian phản hồi 1ms.', 'Monitor 4K 27 inch - Màn hình gaming', 8999000, 7999000, 'MON-4K-27-144', 10, 5, 'Gaming Display', TRUE, 'active'),

('iPad Air 5', 'ipad-air-5', 'iPad Air 5 với chip M1, màn hình 10.9 inch Liquid Retina và Apple Pencil 2. Hiệu năng mạnh mẽ, pin trâu 10 giờ.', 'iPad Air 5 - Tablet cao cấp với chip M1', 15990000, 14990000, 'IPA5-M1-256', 35, 4, 'Apple', FALSE, 'active'),

('Surface Pro 9', 'surface-pro-9', 'Surface Pro 9 với Intel Core i7, 16GB RAM và SSD 512GB. Thiết kế 2-in-1, màn hình 13 inch PixelSense 120Hz.', 'Surface Pro 9 - Tablet 2-in-1 cao cấp', 32990000, 29990000, 'SP9-I7-512', 15, 2, 'Microsoft', FALSE, 'active'),

('MacBook Air M2', 'macbook-air-m2', 'MacBook Air M2 với chip M2, 8GB RAM và SSD 256GB. Thiết kế mỏng nhẹ, pin trâu 18 giờ, màn hình Liquid Retina 13.6 inch.', 'MacBook Air M2 - Laptop mỏng nhẹ với chip M2', 25990000, 23990000, 'MBA-M2-256', 28, 2, 'Apple', FALSE, 'active');

-- Insert product images (placeholder paths)
INSERT INTO product_images (product_id, image_path, alt_text, sort_order, is_primary) VALUES
(1, 'uploads/products/iphone-15-pro-max-1.jpg', 'iPhone 15 Pro Max - Mặt trước', 1, TRUE),
(1, 'uploads/products/iphone-15-pro-max-2.jpg', 'iPhone 15 Pro Max - Mặt sau', 2, FALSE),
(2, 'uploads/products/samsung-s24-ultra-1.jpg', 'Samsung Galaxy S24 Ultra - Mặt trước', 1, TRUE),
(2, 'uploads/products/samsung-s24-ultra-2.jpg', 'Samsung Galaxy S24 Ultra - Mặt sau', 2, FALSE),
(3, 'uploads/products/macbook-pro-m3-1.jpg', 'MacBook Pro M3 - Mặt trên', 1, TRUE),
(3, 'uploads/products/macbook-pro-m3-2.jpg', 'MacBook Pro M3 - Màn hình', 2, FALSE),
(4, 'uploads/products/dell-xps-13-1.jpg', 'Dell XPS 13 - Mặt trên', 1, TRUE),
(5, 'uploads/products/apple-watch-9-1.jpg', 'Apple Watch Series 9 - Mặt đồng hồ', 1, TRUE),
(6, 'uploads/products/samsung-watch-6-1.jpg', 'Samsung Galaxy Watch 6 - Mặt đồng hồ', 1, TRUE),
(7, 'uploads/products/airpods-pro-2-1.jpg', 'AirPods Pro 2 - Hộp sạc', 1, TRUE),
(8, 'uploads/products/sony-wh1000xm5-1.jpg', 'Sony WH-1000XM5 - Tai nghe', 1, TRUE),
(9, 'uploads/products/gaming-chair-1.jpg', 'Gaming Chair Pro - Ghế gaming', 1, TRUE),
(10, 'uploads/products/keyboard-rgb-1.jpg', 'Mechanical Keyboard RGB - Bàn phím', 1, TRUE),
(11, 'uploads/products/gaming-mouse-1.jpg', 'Gaming Mouse Wireless - Chuột', 1, TRUE),
(12, 'uploads/products/monitor-4k-1.jpg', 'Monitor 4K 27 inch - Màn hình', 1, TRUE),
(13, 'uploads/products/ipad-air-5-1.jpg', 'iPad Air 5 - Mặt trước', 1, TRUE),
(14, 'uploads/products/surface-pro-9-1.jpg', 'Surface Pro 9 - Mặt trước', 1, TRUE),
(15, 'uploads/products/macbook-air-m2-1.jpg', 'MacBook Air M2 - Mặt trên', 1, TRUE);

-- Insert product attributes
INSERT INTO product_attributes (product_id, attribute_name, attribute_value) VALUES
(1, 'Màn hình', '6.7 inch Super Retina XDR'),
(1, 'Chip', 'A17 Pro'),
(1, 'Camera', '48MP + 12MP + 12MP'),
(1, 'Pin', '4422 mAh'),
(1, 'Hệ điều hành', 'iOS 17'),
(2, 'Màn hình', '6.8 inch Dynamic AMOLED 2X'),
(2, 'Chip', 'Snapdragon 8 Gen 3'),
(2, 'Camera', '200MP + 50MP + 10MP + 10MP'),
(2, 'Pin', '5000 mAh'),
(2, 'Hệ điều hành', 'Android 14'),
(3, 'Màn hình', '14.2 inch Liquid Retina XDR'),
(3, 'Chip', 'Apple M3'),
(3, 'RAM', '16GB'),
(3, 'Ổ cứng', '512GB SSD'),
(3, 'Pin', '18 giờ'),
(4, 'Màn hình', '13.4 inch 4K UHD+'),
(4, 'Chip', 'Intel Core i7-1360P'),
(4, 'RAM', '16GB LPDDR5'),
(4, 'Ổ cứng', '512GB SSD'),
(4, 'Pin', '12 giờ'),
(5, 'Màn hình', '45mm Always-On Retina'),
(5, 'Chip', 'Apple S9'),
(5, 'Pin', '18 giờ'),
(5, 'Chống nước', 'WR50'),
(5, 'Hệ điều hành', 'watchOS 10'),
(6, 'Màn hình', '44mm AMOLED'),
(6, 'Chip', 'Exynos W930'),
(6, 'Pin', '2 ngày'),
(6, 'Chống nước', '5ATM'),
(6, 'Hệ điều hành', 'Wear OS 4');

-- Insert banners
INSERT INTO banners (title, description, image, link, position, sort_order, status) VALUES
('iPhone 15 Pro Max', 'Trải nghiệm công nghệ đỉnh cao với iPhone 15 Pro Max', 'uploads/banners/iphone-15-banner.jpg', '/product/iphone-15-pro-max', 'home_hero', 1, 'active'),
('MacBook Pro M3', 'Sức mạnh xử lý vượt trội với chip M3', 'uploads/banners/macbook-pro-banner.jpg', '/product/macbook-pro-m3', 'home_hero', 2, 'active'),
('Gaming Gear Sale', 'Giảm giá lên đến 30% cho tất cả phụ kiện gaming', 'uploads/banners/gaming-sale-banner.jpg', '/category/gaming', 'home_featured', 1, 'active'),
('Apple Watch Series 9', 'Theo dõi sức khỏe thông minh', 'uploads/banners/apple-watch-banner.jpg', '/product/apple-watch-series-9', 'home_hero', 3, 'active');

-- Insert settings
INSERT INTO settings (setting_key, setting_value, description) VALUES
('site_name', 'Mini Shop', 'Tên website'),
('site_description', 'Cửa hàng điện tử mini với sản phẩm chất lượng cao', 'Mô tả website'),
('currency', 'VND', 'Đơn vị tiền tệ'),
('shipping_fee', '30000', 'Phí vận chuyển'),
('free_shipping_threshold', '500000', 'Ngưỡng miễn phí vận chuyển'),
('contact_email', 'admin@minishop.com', 'Email liên hệ'),
('contact_phone', '0123-456-789', 'Số điện thoại liên hệ'),
('social_facebook', 'https://facebook.com/minishop', 'Link Facebook'),
('social_instagram', 'https://instagram.com/minishop', 'Link Instagram'),
('social_youtube', 'https://youtube.com/minishop', 'Link YouTube'),
('maintenance_mode', '0', 'Chế độ bảo trì'),
('max_upload_size', '5242880', 'Kích thước upload tối đa (bytes)'),
('allowed_image_types', 'jpg,jpeg,png,gif,webp', 'Các loại file ảnh được phép'),
('default_currency_symbol', '₫', 'Ký hiệu tiền tệ mặc định'),
('timezone', 'Asia/Ho_Chi_Minh', 'Múi giờ'),
('date_format', 'd/m/Y', 'Định dạng ngày'),
('time_format', 'H:i', 'Định dạng giờ');

-- Insert sample orders
INSERT INTO orders (order_number, user_id, customer_name, customer_email, customer_phone, shipping_address, subtotal, shipping_fee, total_amount, payment_method, payment_status, order_status) VALUES
('ORD20241201001', 2, 'Nguyễn Văn A', 'customer1@email.com', '0987-654-321', '123 Đường ABC, Quận 1, TP.HCM', 27990000, 30000, 28020000, 'cod', 'pending', 'pending'),
('ORD20241201002', 3, 'Trần Thị B', 'customer2@email.com', '0912-345-678', '456 Đường XYZ, Quận 2, TP.HCM', 42990000, 0, 42990000, 'bank_transfer', 'paid', 'confirmed'),
('ORD20241201003', 4, 'Lê Văn C', 'customer3@email.com', '0901-234-567', '789 Đường DEF, Quận 3, TP.HCM', 7990000, 30000, 8020000, 'cod', 'paid', 'shipped'),
('ORD20241201004', 2, 'Nguyễn Văn A', 'customer1@email.com', '0987-654-321', '123 Đường ABC, Quận 1, TP.HCM', 14990000, 0, 14990000, 'bank_transfer', 'paid', 'delivered'),
('ORD20241201005', 3, 'Trần Thị B', 'customer2@email.com', '0912-345-678', '456 Đường XYZ, Quận 2, TP.HCM', 2499000, 30000, 2529000, 'cod', 'pending', 'pending');

-- Insert order items
INSERT INTO order_items (order_id, product_id, product_name, product_sku, quantity, price, total) VALUES
(1, 1, 'iPhone 15 Pro Max', 'IPH15PM-256', 1, 27990000, 27990000),
(2, 3, 'MacBook Pro M3', 'MBP14-M3-512', 1, 42990000, 42990000),
(3, 5, 'Apple Watch Series 9', 'AWS9-45MM', 1, 7990000, 7990000),
(4, 13, 'iPad Air 5', 'IPA5-M1-256', 1, 14990000, 14990000),
(5, 9, 'Gaming Chair Pro', 'GC-PRO-RGB', 1, 2499000, 2499000);

-- Insert reviews
INSERT INTO reviews (product_id, user_id, customer_name, customer_email, rating, title, comment, status) VALUES
(1, 2, 'Nguyễn Văn A', 'customer1@email.com', 5, 'Sản phẩm tuyệt vời!', 'iPhone 15 Pro Max rất đẹp và mượt mà. Camera chụp ảnh rất đẹp, pin trâu cả ngày. Rất hài lòng với sản phẩm!', 'approved'),
(3, 3, 'Trần Thị B', 'customer2@email.com', 5, 'MacBook Pro M3 xuất sắc', 'MacBook Pro M3 xử lý rất nhanh, pin trâu. Màn hình đẹp, âm thanh hay. Rất hài lòng với sản phẩm!', 'approved'),
(1, NULL, 'Khách hàng', 'guest@email.com', 4, 'Tốt nhưng giá hơi cao', 'Sản phẩm chất lượng tốt nhưng giá hơi cao so với túi tiền. Camera rất đẹp, hiệu năng mạnh.', 'approved'),
(5, 4, 'Lê Văn C', 'customer3@email.com', 5, 'Apple Watch tuyệt vời', 'Apple Watch Series 9 rất tiện lợi, theo dõi sức khỏe chính xác. Pin trâu, thiết kế đẹp.', 'approved'),
(7, 2, 'Nguyễn Văn A', 'customer1@email.com', 4, 'AirPods Pro 2 tốt', 'AirPods Pro 2 âm thanh hay, chống ồn tốt. Pin trâu, sạc nhanh. Thiết kế đẹp.', 'approved'),
(9, 3, 'Trần Thị B', 'customer2@email.com', 5, 'Ghế gaming rất thoải mái', 'Ghế gaming rất thoải mái, điều chỉnh được nhiều tư thế. Màu RGB đẹp, chất liệu tốt.', 'approved');

-- Insert coupons
INSERT INTO coupons (code, name, description, type, value, minimum_amount, maximum_discount, usage_limit, start_date, end_date, status) VALUES
('SALE10', 'Giảm 10%', 'Giảm giá 10% cho đơn hàng từ 1 triệu', 'percentage', 10, 1000000, 500000, 100, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 'active'),
('FREESHIP', 'Miễn phí ship', 'Miễn phí vận chuyển cho đơn hàng từ 500k', 'fixed', 30000, 500000, 30000, 200, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 'active'),
('NEWUSER', 'Giảm 50k', 'Giảm 50k cho khách hàng mới', 'fixed', 50000, 0, 50000, 50, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 'active');

-- Insert wishlist items
INSERT INTO wishlist (user_id, product_id) VALUES
(2, 3), (2, 5), (2, 7),
(3, 1), (3, 4), (3, 8),
(4, 2), (4, 6), (4, 9);

-- =============================================
-- HOÀN THÀNH
-- =============================================

-- Hiển thị thông tin database
SELECT 'Database ecommerce_db đã được tạo thành công!' as message;
SELECT COUNT(*) as total_users FROM users;
SELECT COUNT(*) as total_products FROM products;
SELECT COUNT(*) as total_categories FROM categories;
SELECT COUNT(*) as total_orders FROM orders;
SELECT COUNT(*) as total_reviews FROM reviews;

-- Hiển thị thông tin admin
SELECT 'Admin account: username=admin, password=admin123' as admin_info;
SELECT 'Customer accounts: username=customer1,2,3, password=password' as customer_info;
