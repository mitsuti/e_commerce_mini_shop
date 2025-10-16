-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 16, 2025 lúc 03:46 PM
-- Phiên bản máy phục vụ: 10.4.27-MariaDB
-- Phiên bản PHP: 8.2.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `ecommerce_db`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `position` enum('home_hero','home_featured','category_top','sidebar') NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `start_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `end_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `banners`
--

INSERT INTO `banners` (`id`, `title`, `description`, `image`, `link`, `position`, `sort_order`, `status`, `start_date`, `end_date`, `created_at`, `updated_at`) VALUES
(1, 'iPhone 15 Pro Max', 'Trải nghiệm công nghệ đỉnh cao với iPhone 15 Pro Max', 'uploads/banners/iphone-15-banner.jpg', '/product/iphone-15-pro-max', 'home_hero', 1, 'active', '2025-10-12 19:22:23', NULL, '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(2, 'MacBook Pro M3', 'Sức mạnh xử lý vượt trội với chip M3', 'uploads/banners/macbook-pro-banner.jpg', '/product/macbook-pro-m3', 'home_hero', 2, 'active', '2025-10-12 19:22:23', NULL, '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(3, 'Gaming Gear Sale', 'Giảm giá lên đến 30% cho tất cả phụ kiện gaming', 'uploads/banners/gaming-sale-banner.jpg', '/category/gaming', 'home_featured', 1, 'active', '2025-10-12 19:22:23', NULL, '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(4, 'Apple Watch Series 9', 'Theo dõi sức khỏe thông minh', 'uploads/banners/apple-watch-banner.jpg', '/product/apple-watch-series-9', 'home_hero', 3, 'active', '2025-10-12 19:22:23', NULL, '2025-10-12 19:22:23', '2025-10-12 19:22:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `cart`
--

CREATE TABLE `cart` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `cart`
--

INSERT INTO `cart` (`id`, `user_id`, `session_id`, `product_id`, `quantity`, `price`, `created_at`, `updated_at`) VALUES
(1, NULL, 'map0r0bca685tif8hg3es6uo9b', 1, 1, '27990000.00', '2025-10-13 03:10:04', '2025-10-13 03:10:04'),
(21, 3, 'c2ahigls8ov5ndpli76guj61k0', 1, 1, '27990000.00', '2025-10-14 15:14:40', '2025-10-14 15:14:40'),
(22, 3, 'c2ahigls8ov5ndpli76guj61k0', 3, 1, '42990000.00', '2025-10-14 15:14:41', '2025-10-14 15:14:41');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `description`, `image`, `parent_id`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Điện thoại', 'dien-thoai', 'Điện thoại thông minh và phụ kiện', NULL, NULL, 1, 'active', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(2, 'Laptop', 'laptop', 'Máy tính xách tay và phụ kiện', NULL, NULL, 2, 'active', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(3, 'Đồng hồ', 'dong-ho', 'Đồng hồ thông minh và đồng hồ đeo tay', NULL, NULL, 3, 'active', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(4, 'Phụ kiện', 'phu-kien', 'Phụ kiện điện tử và công nghệ', NULL, NULL, 4, 'active', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(5, 'Gaming', 'gaming', 'Thiết bị gaming và phụ kiện', NULL, NULL, 5, 'active', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(6, 'Máy tính', 'may-tinh', 'Máy tính để bàn và linh kiện', NULL, NULL, 6, 'active', '2025-10-12 19:22:23', '2025-10-12 19:22:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `coupons`
--

CREATE TABLE `coupons` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('percentage','fixed') NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `minimum_amount` decimal(10,2) DEFAULT 0.00,
  `maximum_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `start_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `end_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `coupons`
--

INSERT INTO `coupons` (`id`, `code`, `name`, `description`, `type`, `value`, `minimum_amount`, `maximum_discount`, `usage_limit`, `used_count`, `start_date`, `end_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'SALE10', 'Giảm 10%', 'Giảm giá 10% cho đơn hàng từ 1 triệu', 'percentage', '10.00', '1000000.00', '500000.00', 100, 0, '2023-12-31 17:00:00', '2024-12-31 16:59:59', 'active', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(2, 'FREESHIP', 'Miễn phí ship', 'Miễn phí vận chuyển cho đơn hàng từ 500k', 'fixed', '30000.00', '500000.00', '30000.00', 200, 0, '2023-12-31 17:00:00', '2024-12-31 16:59:59', 'active', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(3, 'NEWUSER', 'Giảm 50k', 'Giảm 50k cho khách hàng mới', 'fixed', '50000.00', '0.00', '50000.00', 50, 0, '2023-12-31 17:00:00', '2024-12-31 16:59:59', 'active', '2025-10-12 19:22:23', '2025-10-12 19:22:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `shipping_address` text NOT NULL,
  `billing_address` text DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `shipping_fee` decimal(10,2) DEFAULT 0.00,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cod','bank_transfer','credit_card') DEFAULT 'cod',
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `order_status` enum('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `user_id`, `customer_name`, `customer_email`, `customer_phone`, `shipping_address`, `billing_address`, `subtotal`, `shipping_fee`, `discount_amount`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `notes`, `shipped_at`, `delivered_at`, `created_at`, `updated_at`) VALUES
(1, 'ORD20241201001', 2, 'Nguyễn Văn A', 'customer1@email.com', '0987-654-321', '123 Đường ABC, Quận 1, TP.HCM', NULL, '27990000.00', '30000.00', '0.00', '28020000.00', 'cod', 'pending', 'pending', NULL, NULL, NULL, '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(2, 'ORD20241201002', 3, 'Trần Thị B', 'customer2@email.com', '0912-345-678', '456 Đường XYZ, Quận 2, TP.HCM', NULL, '42990000.00', '0.00', '0.00', '42990000.00', 'bank_transfer', 'paid', 'confirmed', NULL, NULL, NULL, '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(3, 'ORD20241201003', 4, 'Lê Văn C', 'customer3@email.com', '0901-234-567', '789 Đường DEF, Quận 3, TP.HCM', NULL, '7990000.00', '30000.00', '0.00', '8020000.00', 'cod', 'paid', 'shipped', NULL, NULL, NULL, '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(4, 'ORD20241201004', 2, 'Nguyễn Văn A', 'customer1@email.com', '0987-654-321', '123 Đường ABC, Quận 1, TP.HCM', NULL, '14990000.00', '0.00', '0.00', '14990000.00', 'bank_transfer', 'paid', 'delivered', NULL, NULL, NULL, '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(5, 'ORD20241201005', 3, 'Trần Thị B', 'customer2@email.com', '0912-345-678', '456 Đường XYZ, Quận 2, TP.HCM', NULL, '2499000.00', '30000.00', '0.00', '2529000.00', 'cod', 'pending', 'pending', NULL, NULL, NULL, '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(6, 'ORD202510155372', 1, 'Administrator', 'admin@minishop.com', '0123-456-789', 'test', 'test', '0.00', '30000.00', '0.00', '30000.00', 'cod', 'pending', 'pending', 'test', NULL, NULL, '2025-10-14 18:23:52', '2025-10-14 18:23:52'),
(7, 'ORD202510152697', 1, 'Administrator', 'admin@minishop.com', '0123-456-789', 'test', 'test', '0.00', '30000.00', '0.00', '30000.00', 'cod', 'pending', 'pending', 'test', NULL, NULL, '2025-10-14 18:26:35', '2025-10-14 18:26:35'),
(8, 'ORD202510154405', 1, 'Administrator', 'admin@minishop.com', '0123-456-789', 'test', 'test', '0.00', '30000.00', '0.00', '30000.00', 'cod', 'pending', 'pending', 'test', NULL, NULL, '2025-10-14 18:30:19', '2025-10-14 18:30:19'),
(9, 'ORD202510153453', 1, 'Administrator', 'admin@minishop.com', '0123-456-789', 'test', 't', '0.00', '30000.00', '0.00', '30000.00', 'cod', 'pending', 'pending', 'test', NULL, NULL, '2025-10-14 18:33:46', '2025-10-14 18:33:46'),
(10, 'ORD202510159879', 1, 'Administrator', 'admin@minishop.com', '0123-456-789', 'test', 't', '0.00', '30000.00', '0.00', '30000.00', 'cod', 'pending', 'pending', 'test', NULL, NULL, '2025-10-14 18:33:53', '2025-10-14 18:33:53'),
(11, 'ORD202510158539', 1, 'Administrator', 'admin@minishop.com', '0123-456-789', 'test', 't', '0.00', '30000.00', '0.00', '30000.00', 'cod', 'pending', 'pending', 'test', NULL, NULL, '2025-10-14 18:38:17', '2025-10-14 18:38:17'),
(12, 'ORD202510156789', 2, 'Trọng Gà', 'customer1@email.com', '0987-654-321', '123 Đường ABC, Quận 1, TP.HCM', '123 Đường ABC, Quận 1, TP.HCM', '0.00', '30000.00', '0.00', '30000.00', 'cod', 'pending', 'cancelled', '', NULL, NULL, '2025-10-14 18:47:46', '2025-10-15 02:22:01'),
(13, 'ORD202510158860', 2, 'Trọng Gà', 'customer1@email.com', '0987-654-321', '123 Đường ABC, Quận 1, TP.HCM', '123 Đường ABC, Quận 1, TP.HCM', '0.00', '30000.00', '0.00', '30000.00', 'cod', 'pending', 'cancelled', '', NULL, NULL, '2025-10-14 18:48:41', '2025-10-15 02:18:55'),
(17, 'ORD202510155370', 2, 'Trọng Gà', 'customer1@email.com', '0987-654-321', '123 Đường ABC, Quận 1, TP.HCM', '123 Đường ABC, Quận 1, TP.HCM', '56470000.00', '0.00', '0.00', '56470000.00', 'cod', 'pending', 'pending', '', NULL, NULL, '2025-10-15 02:26:42', '2025-10-15 02:26:42'),
(19, 'ORD202510159002', 1, 'Test User', 'test@example.com', '0123456789', 'Test Address', 'Test Address', '79969000.00', '0.00', '0.00', '79969000.00', 'cod', 'pending', 'pending', 'Test order - no redirect', NULL, NULL, '2025-10-15 02:56:09', '2025-10-15 02:56:09'),
(20, 'ORD202510150537', 2, 'Trọng Gà', 'customer1@email.com', '0987-654-321', '123 Đường ABC, Quận 1, TP.HCM', '123 Đường ABC, Quận 1, TP.HCM', '56470000.00', '0.00', '0.00', '56470000.00', 'cod', 'pending', 'pending', '', NULL, NULL, '2025-10-15 02:56:53', '2025-10-15 02:56:53'),
(21, 'ORD202510153020', 2, 'Trọng Gà', 'customer1@email.com', '0987-654-321', '123 Đường ABC, Quận 1, TP.HCM', '123 Đường ABC, Quận 1, TP.HCM', '9789000.00', '0.00', '0.00', '9789000.00', 'cod', 'pending', 'pending', '', NULL, NULL, '2025-10-15 03:11:41', '2025-10-15 03:11:41'),
(22, 'ORD202510157848', 2, 'Trọng Gà', 'customer1@email.com', '0987-654-321', '123 Đường ABC, Quận 1, TP.HCM', '123 Đường ABC, Quận 1, TP.HCM', '23990000.00', '0.00', '0.00', '23990000.00', 'cod', 'pending', 'processing', '', NULL, NULL, '2025-10-15 03:15:24', '2025-10-15 04:11:27'),
(23, 'ORD202510159311', 2, 'Trọng Gà', 'customer1@email.com', '0987-654-321', '123 Đường ABC, Quận 1, TP.HCM', '123 Đường ABC, Quận 1, TP.HCM', '7990000.00', '0.00', '0.00', '7990000.00', 'cod', 'paid', 'delivered', '', NULL, NULL, '2025-10-15 03:24:10', '2025-10-15 04:08:19');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `product_sku` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `product_sku`, `quantity`, `price`, `total`, `created_at`) VALUES
(1, 1, 1, 'iPhone 15 Pro Max', 'IPH15PM-256', 1, '27990000.00', '27990000.00', '2025-10-12 19:22:23'),
(2, 2, 3, 'MacBook Pro M3', 'MBP14-M3-512', 1, '42990000.00', '42990000.00', '2025-10-12 19:22:23'),
(3, 3, 5, 'Apple Watch Series 9', 'AWS9-45MM', 1, '7990000.00', '7990000.00', '2025-10-12 19:22:23'),
(4, 4, 13, 'iPad Air 5', 'IPA5-M1-256', 1, '14990000.00', '14990000.00', '2025-10-12 19:22:23'),
(5, 5, 9, 'Gaming Chair Pro', 'GC-PRO-RGB', 1, '2499000.00', '2499000.00', '2025-10-12 19:22:23'),
(11, 17, 3, 'MacBook Pro M3', 'MBP14-M3-512', 1, '42990000.00', '42990000.00', '2025-10-15 02:26:42'),
(12, 17, 5, 'Apple Watch Series 9', 'AWS9-45MM', 1, '7990000.00', '7990000.00', '2025-10-15 02:26:42'),
(13, 17, 7, 'AirPods Pro 2', 'APP2-USB-C', 1, '5490000.00', '5490000.00', '2025-10-15 02:26:42'),
(17, 19, 11, 'Gaming Mouse Wireless', 'GM-WIRELESS-RGB', 1, '999000.00', '999000.00', '2025-10-15 02:56:09'),
(18, 19, 4, 'Dell XPS 13', 'DXPS13-I7-512', 1, '26990000.00', '26990000.00', '2025-10-15 02:56:09'),
(19, 19, 15, 'MacBook Air M2', 'MBA-M2-256', 1, '23990000.00', '23990000.00', '2025-10-15 02:56:09'),
(20, 19, 1, 'iPhone 15 Pro Max', 'IPH15PM-256', 1, '27990000.00', '27990000.00', '2025-10-15 02:56:09'),
(21, 20, 3, 'MacBook Pro M3', 'MBP14-M3-512', 1, '42990000.00', '42990000.00', '2025-10-15 02:56:53'),
(22, 20, 5, 'Apple Watch Series 9', 'AWS9-45MM', 1, '7990000.00', '7990000.00', '2025-10-15 02:56:53'),
(23, 20, 7, 'AirPods Pro 2', 'APP2-USB-C', 1, '5490000.00', '5490000.00', '2025-10-15 02:56:53'),
(24, 21, 5, 'Apple Watch Series 9', 'AWS9-45MM', 1, '7990000.00', '7990000.00', '2025-10-15 03:11:41'),
(25, 21, 10, 'Mechanical Keyboard RGB', 'MKB-RGB-MX', 1, '1799000.00', '1799000.00', '2025-10-15 03:11:41'),
(26, 22, 15, 'MacBook Air M2', 'MBA-M2-256', 1, '23990000.00', '23990000.00', '2025-10-15 03:15:24'),
(27, 23, 5, 'Apple Watch Series 9', 'AWS9-45MM', 1, '7990000.00', '7990000.00', '2025-10-15 03:24:10');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `short_description` varchar(500) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `sale_price` decimal(10,2) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `stock_quantity` int(11) DEFAULT 0,
  `min_stock_level` int(11) DEFAULT 5,
  `weight` decimal(8,2) DEFAULT 0.00,
  `dimensions` varchar(100) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','out_of_stock') DEFAULT 'active',
  `featured` tinyint(1) DEFAULT 0,
  `view_count` int(11) DEFAULT 0,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `name`, `slug`, `description`, `short_description`, `price`, `sale_price`, `sku`, `stock_quantity`, `min_stock_level`, `weight`, `dimensions`, `category_id`, `brand`, `status`, `featured`, `view_count`, `meta_title`, `meta_description`, `created_at`, `updated_at`) VALUES
(1, 'iPhone 15 Pro Max', 'iphone-15-pro-max', 'iPhone 15 Pro Max với chip A17 Pro mạnh mẽ, camera 48MP và màn hình Super Retina XDR 6.7 inch. Thiết kế titan cao cấp, chống nước IP68, pin trâu cả ngày.', 'iPhone 15 Pro Max - Flagship mới nhất từ Apple với chip A17 Pro', '29990000.00', '27990000.00', 'IPH15PM-256', 50, 5, '0.00', NULL, 1, 'Apple', 'active', 1, 2, NULL, NULL, '2025-10-12 19:22:23', '2025-10-14 17:51:39'),
(2, 'Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'Galaxy S24 Ultra với S Pen, camera 200MP và màn hình Dynamic AMOLED 2X 6.8 inch. Hiệu năng mạnh mẽ với chip Snapdragon 8 Gen 3.', 'Galaxy S24 Ultra - Điện thoại Android cao cấp với S Pen', '26990000.00', '24990000.00', 'SGS24U-512', 30, 5, '0.00', NULL, 1, 'Samsung', 'active', 1, 3, NULL, NULL, '2025-10-12 19:22:23', '2025-10-15 02:21:01'),
(3, 'MacBook Pro M3', 'macbook-pro-m3', 'MacBook Pro 14 inch với chip M3, 16GB RAM và SSD 512GB. Hiệu năng vượt trội, pin trâu 18 giờ, màn hình Liquid Retina XDR tuyệt đẹp.', 'MacBook Pro M3 - Laptop chuyên nghiệp với chip M3 mạnh mẽ', '45990000.00', '42990000.00', 'MBP14-M3-512', 19, 5, '0.00', NULL, 2, 'Apple', 'active', 1, 1, NULL, NULL, '2025-10-12 19:22:23', '2025-10-15 04:15:43'),
(4, 'Dell XPS 13', 'dell-xps-13', 'Dell XPS 13 với Intel Core i7, 16GB RAM và SSD 512GB. Thiết kế sang trọng, màn hình 13.4 inch 4K, pin trâu 12 giờ.', 'Dell XPS 13 - Laptop cao cấp với thiết kế sang trọng', '28990000.00', '26990000.00', 'DXPS13-I7-512', 28, 5, '0.00', NULL, 2, 'Dell', 'active', 0, 1, NULL, NULL, '2025-10-12 19:22:23', '2025-10-15 04:16:02'),
(5, 'Apple Watch Series 9', 'apple-watch-series-9', 'Apple Watch Series 9 với chip S9, màn hình Always-On và nhiều tính năng sức khỏe. Theo dõi tim mạch, SpO2, giấc ngủ chính xác.', 'Apple Watch Series 9 - Đồng hồ thông minh với chip S9', '8990000.00', '7990000.00', 'AWS9-45MM', 37, 5, '0.00', NULL, 3, 'Apple', 'active', 1, 1, NULL, NULL, '2025-10-12 19:22:23', '2025-10-15 04:15:43'),
(6, 'Samsung Galaxy Watch 6', 'samsung-galaxy-watch-6', 'Galaxy Watch 6 với Wear OS, theo dõi sức khỏe và pin 2 ngày. Màn hình AMOLED sắc nét, chống nước 5ATM.', 'Galaxy Watch 6 - Đồng hồ thông minh Android', '5990000.00', '5490000.00', 'SGW6-44MM', 35, 5, '0.00', NULL, 3, 'Samsung', 'active', 0, 0, NULL, NULL, '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(7, 'AirPods Pro 2', 'airpods-pro-2', 'AirPods Pro thế hệ 2 với chip H2, chống ồn chủ động và âm thanh không gian. Pin trâu 6 giờ, case sạc nhanh.', 'AirPods Pro 2 - Tai nghe không dây cao cấp', '5990000.00', '5490000.00', 'APP2-USB-C', 59, 5, '0.00', NULL, 4, 'Apple', 'active', 1, 2, NULL, NULL, '2025-10-12 19:22:23', '2025-10-15 04:15:43'),
(8, 'Sony WH-1000XM5', 'sony-wh-1000xm5', 'Sony WH-1000XM5 với chống ồn hàng đầu và âm thanh chất lượng cao. Pin 30 giờ, sạc nhanh 3 phút = 3 giờ nghe.', 'Sony WH-1000XM5 - Tai nghe chống ồn hàng đầu', '8990000.00', '7990000.00', 'SW1000XM5', 15, 5, '0.00', NULL, 4, 'Sony', 'active', 0, 0, NULL, NULL, '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(9, 'Gaming Chair Pro', 'gaming-chair-pro', 'Ghế gaming cao cấp với đệm lưng và cổ, điều chỉnh chiều cao và màu RGB. Chất liệu da PU cao cấp, chịu lực 150kg.', 'Gaming Chair Pro - Ghế gaming chuyên nghiệp', '2999000.00', '2499000.00', 'GC-PRO-RGB', 20, 5, '0.00', NULL, 5, 'Gaming Pro', 'active', 0, 0, NULL, NULL, '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(10, 'Mechanical Keyboard RGB', 'mechanical-keyboard-rgb', 'Bàn phím cơ RGB với switch Cherry MX Blue và đèn LED đa màu. Thiết kế 87 phím, dây USB-C có thể tháo rời.', 'Mechanical Keyboard RGB - Bàn phím gaming', '1999000.00', '1799000.00', 'MKB-RGB-MX', 29, 5, '0.00', NULL, 5, 'Gaming Gear', 'active', 0, 2, NULL, NULL, '2025-10-12 19:22:23', '2025-10-15 03:11:41'),
(11, 'Gaming Mouse Wireless', 'gaming-mouse-wireless', 'Chuột gaming không dây với DPI cao, RGB và pin 70 giờ. Cảm biến quang học chính xác, 6 nút có thể lập trình.', 'Gaming Mouse Wireless - Chuột gaming cao cấp', '1299000.00', '999000.00', 'GM-WIRELESS-RGB', 28, 5, '0.00', NULL, 5, 'Gaming Gear', 'active', 0, 2, NULL, NULL, '2025-10-12 19:22:23', '2025-10-15 04:16:02'),
(12, 'Monitor 4K 27 inch', 'monitor-4k-27-inch', 'Màn hình 4K 27 inch với tần số quét 144Hz và HDR. Màu sắc chính xác 99% sRGB, thời gian phản hồi 1ms.', 'Monitor 4K 27 inch - Màn hình gaming', '8999000.00', '7999000.00', 'MON-4K-27-144', 10, 5, '0.00', NULL, 5, 'Gaming Display', 'active', 1, 1, NULL, NULL, '2025-10-12 19:22:23', '2025-10-14 17:36:36'),
(13, 'iPad Air 5', 'ipad-air-5', 'iPad Air 5 với chip M1, màn hình 10.9 inch Liquid Retina và Apple Pencil 2. Hiệu năng mạnh mẽ, pin trâu 10 giờ.', 'iPad Air 5 - Tablet cao cấp với chip M1', '15990000.00', '14990000.00', 'IPA5-M1-256', 35, 5, '0.00', NULL, 4, 'Apple', 'active', 0, 1, NULL, NULL, '2025-10-12 19:22:23', '2025-10-14 17:34:06'),
(14, 'Surface Pro 9', 'surface-pro-9', 'Surface Pro 9 với Intel Core i7, 16GB RAM và SSD 512GB. Thiết kế 2-in-1, màn hình 13 inch PixelSense 120Hz.', 'Surface Pro 9 - Tablet 2-in-1 cao cấp', '32990000.00', '29990000.00', 'SP9-I7-512', 15, 5, '0.00', NULL, 2, 'Microsoft', 'active', 0, 0, NULL, NULL, '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(15, 'MacBook Air M2', 'macbook-air-m2', 'MacBook Air M2 với chip M2, 8GB RAM và SSD 256GB. Thiết kế mỏng nhẹ, pin trâu 18 giờ, màn hình Liquid Retina 13.6 inch.', 'MacBook Air M2 - Laptop mỏng nhẹ với chip M2', '25990000.00', '23990000.00', 'MBA-M2-256', 29, 5, '0.00', NULL, 2, 'Apple', 'active', 0, 1, NULL, NULL, '2025-10-12 19:22:23', '2025-10-15 03:17:36');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_attributes`
--

CREATE TABLE `product_attributes` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `attribute_name` varchar(100) NOT NULL,
  `attribute_value` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `product_attributes`
--

INSERT INTO `product_attributes` (`id`, `product_id`, `attribute_name`, `attribute_value`, `created_at`) VALUES
(1, 1, 'Màn hình', '6.7 inch Super Retina XDR', '2025-10-12 19:22:23'),
(2, 1, 'Chip', 'A17 Pro', '2025-10-12 19:22:23'),
(3, 1, 'Camera', '48MP + 12MP + 12MP', '2025-10-12 19:22:23'),
(4, 1, 'Pin', '4422 mAh', '2025-10-12 19:22:23'),
(5, 1, 'Hệ điều hành', 'iOS 17', '2025-10-12 19:22:23'),
(6, 2, 'Màn hình', '6.8 inch Dynamic AMOLED 2X', '2025-10-12 19:22:23'),
(7, 2, 'Chip', 'Snapdragon 8 Gen 3', '2025-10-12 19:22:23'),
(8, 2, 'Camera', '200MP + 50MP + 10MP + 10MP', '2025-10-12 19:22:23'),
(9, 2, 'Pin', '5000 mAh', '2025-10-12 19:22:23'),
(10, 2, 'Hệ điều hành', 'Android 14', '2025-10-12 19:22:23'),
(11, 3, 'Màn hình', '14.2 inch Liquid Retina XDR', '2025-10-12 19:22:23'),
(12, 3, 'Chip', 'Apple M3', '2025-10-12 19:22:23'),
(13, 3, 'RAM', '16GB', '2025-10-12 19:22:23'),
(14, 3, 'Ổ cứng', '512GB SSD', '2025-10-12 19:22:23'),
(15, 3, 'Pin', '18 giờ', '2025-10-12 19:22:23'),
(16, 4, 'Màn hình', '13.4 inch 4K UHD+', '2025-10-12 19:22:23'),
(17, 4, 'Chip', 'Intel Core i7-1360P', '2025-10-12 19:22:23'),
(18, 4, 'RAM', '16GB LPDDR5', '2025-10-12 19:22:23'),
(19, 4, 'Ổ cứng', '512GB SSD', '2025-10-12 19:22:23'),
(20, 4, 'Pin', '12 giờ', '2025-10-12 19:22:23'),
(21, 5, 'Màn hình', '45mm Always-On Retina', '2025-10-12 19:22:23'),
(22, 5, 'Chip', 'Apple S9', '2025-10-12 19:22:23'),
(23, 5, 'Pin', '18 giờ', '2025-10-12 19:22:23'),
(24, 5, 'Chống nước', 'WR50', '2025-10-12 19:22:23'),
(25, 5, 'Hệ điều hành', 'watchOS 10', '2025-10-12 19:22:23'),
(26, 6, 'Màn hình', '44mm AMOLED', '2025-10-12 19:22:23'),
(27, 6, 'Chip', 'Exynos W930', '2025-10-12 19:22:23'),
(28, 6, 'Pin', '2 ngày', '2025-10-12 19:22:23'),
(29, 6, 'Chống nước', '5ATM', '2025-10-12 19:22:23'),
(30, 6, 'Hệ điều hành', 'Wear OS 4', '2025-10-12 19:22:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `product_images`
--

CREATE TABLE `product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(200) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `product_images`
--

INSERT INTO `product_images` (`id`, `product_id`, `image_path`, `alt_text`, `sort_order`, `is_primary`, `created_at`) VALUES
(19, 15, 'uploads/products/0005888_air-m2-silver_1600-68ee88ea797c8.jpeg', NULL, 0, 1, '2025-10-14 17:31:22'),
(20, 14, 'uploads/products/moi-100-microsoft-surface-pro-9-4252-5-68ee8927709f5.jpg', NULL, 0, 1, '2025-10-14 17:32:23'),
(21, 13, 'uploads/products/111887_sp866-ipad-air-5gen-68ee8949e0601.png', NULL, 0, 1, '2025-10-14 17:32:57'),
(22, 13, 'uploads/products/600_ipad_air_5_2022_xanh_5-68ee8970086e8.jpg', NULL, 1, 0, '2025-10-14 17:33:36'),
(23, 13, 'uploads/products/ipad-air-5-68ee897008cc1.webp', NULL, 2, 0, '2025-10-14 17:33:36'),
(24, 13, 'uploads/products/ipad-air-select-wifi-pink-202203-231bc333-5a96-4a94-851c-b5215c43bfa4-68ee897009537.webp', NULL, 3, 0, '2025-10-14 17:33:36'),
(25, 12, 'uploads/products/studio-display-1-68ee8a17be5cc.png', NULL, 0, 1, '2025-10-14 17:36:23'),
(26, 12, 'uploads/products/stand-mount-1-68ee8a17beef5.jpeg', NULL, 1, 0, '2025-10-14 17:36:23'),
(27, 12, 'uploads/products/studio-display-3-68ee8a17bf987.png', NULL, 2, 0, '2025-10-14 17:36:23'),
(28, 12, 'uploads/products/studio-display-4-68ee8a17c0263.png', NULL, 3, 0, '2025-10-14 17:36:23'),
(29, 12, 'uploads/products/studio-display-5-68ee8a17c090d.png', NULL, 4, 0, '2025-10-14 17:36:23'),
(30, 12, 'uploads/products/studio-display-6-68ee8a17c0f3e.png', NULL, 5, 0, '2025-10-14 17:36:23'),
(31, 12, 'uploads/products/studio-display8-68ee8a17c164d.png', NULL, 6, 0, '2025-10-14 17:36:23'),
(32, 11, 'uploads/products/61Mk3YqYHpL-_AC_SX466_-68ee8ab6dfeb0.jpg', NULL, 0, 1, '2025-10-14 17:39:02'),
(33, 11, 'uploads/products/61Jv1LbUhYL-_AC_SL1500_-68ee8ab6e0831.jpg', NULL, 1, 0, '2025-10-14 17:39:02'),
(34, 11, 'uploads/products/61PXjVHN5tL-_AC_SX466_-68ee8ab6e0e0b.jpg', NULL, 2, 0, '2025-10-14 17:39:02'),
(35, 11, 'uploads/products/71mz57pDLkL-_AC_SX466_-68ee8ab6e15de.jpg', NULL, 3, 0, '2025-10-14 17:39:02'),
(36, 11, 'uploads/products/71THYfB9NjL-_AC_SX466_-68ee8ab6e1c52.jpg', NULL, 4, 0, '2025-10-14 17:39:02'),
(37, 11, 'uploads/products/717zFh3uEgL-_AC_SX466_-68ee8ab6e22c4.jpg', NULL, 5, 0, '2025-10-14 17:39:02'),
(46, 10, 'uploads/products/81WPdVI3f4L-_AC_SX466_-68ee8b5fdaeb6-68ee8ba171491.jpg', NULL, 0, 1, '2025-10-14 17:42:57'),
(47, 10, 'uploads/products/71-4NuwwWbL-_AC_SL1500_-68ee8ba172102.jpg', NULL, 1, 0, '2025-10-14 17:42:57'),
(48, 10, 'uploads/products/71Bk2A2WmOL-_AC_SY300_SX300_QL70_FMwebp_-68ee8ba172c29.webp', NULL, 2, 0, '2025-10-14 17:42:57'),
(49, 10, 'uploads/products/71hCutbMWUL-_AC_SX466_-68ee8ba173349.jpg', NULL, 3, 0, '2025-10-14 17:42:57'),
(50, 10, 'uploads/products/71n5odOFBbL-_AC_SX466_-68ee8ba173bf4.jpg', NULL, 4, 0, '2025-10-14 17:42:57'),
(51, 10, 'uploads/products/81H9H5upxBL-_AC_SL1500_-68ee8ba1740ea.jpg', NULL, 5, 0, '2025-10-14 17:42:57'),
(52, 10, 'uploads/products/81XI5tFrKzL-_AC_SL1500_-68ee8ba174558.jpg', NULL, 6, 0, '2025-10-14 17:42:57'),
(53, 10, 'uploads/products/816tVDEvcsL-_AC_SL1500_-68ee8ba17490b.jpg', NULL, 7, 0, '2025-10-14 17:42:57'),
(54, 10, 'uploads/products/8161VszBmOL-_AC_SX466_-68ee8ba174cbc.jpg', NULL, 8, 0, '2025-10-14 17:42:57'),
(55, 9, 'uploads/products/61PfX0ZExBL-_AC_SX679_-68ee8bdd7643a.jpg', NULL, 0, 1, '2025-10-14 17:43:57'),
(56, 8, 'uploads/products/51aXvjzcukL-_AC_SX466_-68ee8c0cb7e8e.jpg', NULL, 0, 1, '2025-10-14 17:44:44'),
(57, 7, 'uploads/products/419yjKznzbL-_AC_SX466_-68ee8c280c4e7.jpg', NULL, 0, 1, '2025-10-14 17:45:12'),
(58, 6, 'uploads/products/51BhUG-Rh9L-_AC_SX466_-68ee8c7a335e5.jpg', NULL, 0, 1, '2025-10-14 17:46:34'),
(59, 5, 'uploads/products/71CDfcyZ7vL-_AC_SX466_-68ee8c9a8cd79.jpg', NULL, 0, 1, '2025-10-14 17:47:06'),
(60, 4, 'uploads/products/71cG77cSCmL-_AC_SX466_-68ee8cb99184b.jpg', NULL, 0, 1, '2025-10-14 17:47:37'),
(61, 3, 'uploads/products/61SdxEONyuL-_AC_SX522_-68ee8cd19e583.jpg', NULL, 0, 1, '2025-10-14 17:48:01'),
(62, 2, 'uploads/products/51IiDlJMCSL-_AC_SX679_-68ee8ce9916bb.jpg', NULL, 0, 1, '2025-10-14 17:48:25'),
(63, 1, 'uploads/products/616mZZm8-7L-_AC_SX679_-68ee8d725c34b.jpg', NULL, 0, 1, '2025-10-14 17:50:42'),
(64, 1, 'uploads/products/31R-wJgPw-L-_AC_-68ee8d725cd64.jpg', NULL, 1, 0, '2025-10-14 17:50:42'),
(65, 1, 'uploads/products/41hkMV349nL-_AC_-68ee8d725d6c5.jpg', NULL, 2, 0, '2025-10-14 17:50:42');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_email` varchar(100) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `title` varchar(200) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `reviews`
--

INSERT INTO `reviews` (`id`, `product_id`, `user_id`, `customer_name`, `customer_email`, `rating`, `title`, `comment`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'Nguyễn Văn A', 'customer1@email.com', 5, 'Sản phẩm tuyệt vời!', 'iPhone 15 Pro Max rất đẹp và mượt mà. Camera chụp ảnh rất đẹp, pin trâu cả ngày. Rất hài lòng với sản phẩm!', 'approved', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(2, 3, 3, 'Trần Thị B', 'customer2@email.com', 5, 'MacBook Pro M3 xuất sắc', 'MacBook Pro M3 xử lý rất nhanh, pin trâu. Màn hình đẹp, âm thanh hay. Rất hài lòng với sản phẩm!', 'approved', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(3, 1, NULL, 'Khách hàng', 'guest@email.com', 4, 'Tốt nhưng giá hơi cao', 'Sản phẩm chất lượng tốt nhưng giá hơi cao so với túi tiền. Camera rất đẹp, hiệu năng mạnh.', 'approved', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(4, 5, 4, 'Lê Văn C', 'customer3@email.com', 5, 'Apple Watch tuyệt vời', 'Apple Watch Series 9 rất tiện lợi, theo dõi sức khỏe chính xác. Pin trâu, thiết kế đẹp.', 'approved', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(5, 7, 2, 'Nguyễn Văn A', 'customer1@email.com', 4, 'AirPods Pro 2 tốt', 'AirPods Pro 2 âm thanh hay, chống ồn tốt. Pin trâu, sạc nhanh. Thiết kế đẹp.', 'approved', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(6, 9, 3, 'Trần Thị B', 'customer2@email.com', 5, 'Ghế gaming rất thoải mái', 'Ghế gaming rất thoải mái, điều chỉnh được nhiều tư thế. Màu RGB đẹp, chất liệu tốt.', 'approved', '2025-10-12 19:22:23', '2025-10-12 19:22:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Mini Shop', 'Tên website', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(2, 'site_description', 'Cửa hàng điện tử mini với sản phẩm chất lượng cao', 'Mô tả website', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(3, 'currency', 'VND', 'Đơn vị tiền tệ', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(4, 'shipping_fee', '30000', 'Phí vận chuyển', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(5, 'free_shipping_threshold', '500000', 'Ngưỡng miễn phí vận chuyển', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(6, 'contact_email', 'admin@minishop.com', 'Email liên hệ', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(7, 'contact_phone', '0123-456-789', 'Số điện thoại liên hệ', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(8, 'social_facebook', 'https://facebook.com/minishop', 'Link Facebook', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(9, 'social_instagram', 'https://instagram.com/minishop', 'Link Instagram', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(10, 'social_youtube', 'https://youtube.com/minishop', 'Link YouTube', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(11, 'maintenance_mode', '0', 'Chế độ bảo trì', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(12, 'max_upload_size', '5242880', 'Kích thước upload tối đa (bytes)', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(13, 'allowed_image_types', 'jpg,jpeg,png,gif,webp', 'Các loại file ảnh được phép', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(14, 'default_currency_symbol', '₫', 'Ký hiệu tiền tệ mặc định', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(15, 'timezone', 'Asia/Ho_Chi_Minh', 'Múi giờ', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(16, 'date_format', 'd/m/Y', 'Định dạng ngày', '2025-10-12 19:22:23', '2025-10-12 19:22:23'),
(17, 'time_format', 'H:i', 'Định dạng giờ', '2025-10-12 19:22:23', '2025-10-12 19:22:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  `avatar` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `email_verified` tinyint(1) DEFAULT 0,
  `remember_token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `address`, `role`, `avatar`, `status`, `email_verified`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@minishop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', '0123-456-789', NULL, 'admin', NULL, 'active', 1, '57f2dc04f2d71b463aa0aa05773399ef660dec2d88eef8cd45895722a0dd37d9', '2025-10-12 19:22:23', '2025-10-14 10:27:20'),
(2, 'customer1', 'customer1@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trọng Gà', '0987-654-321', '123 Đường ABC, Quận 1, TP.HCM', 'customer', 'uploads/users/yuji-itadori-4032x2901-9275-68ee2418c51bb.jpg', 'active', 1, NULL, '2025-10-12 19:22:23', '2025-10-14 10:22:58'),
(3, 'customer2', 'customer2@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Gà Trọng', '0912-345-678', '456 Đường XYZ, Quận 2, TP.HCM', 'customer', 'uploads/users/1211764-68ee67a2a8cb9.jpg', 'active', 1, NULL, '2025-10-12 19:22:23', '2025-10-14 15:09:22'),
(4, 'customer3', 'customer3@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lê Văn C', '0901-234-567', '789 Đường DEF, Quận 3, TP.HCM', 'customer', NULL, 'active', 1, NULL, '2025-10-12 19:22:23', '2025-10-12 19:22:23');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `wishlist`
--

CREATE TABLE `wishlist` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `wishlist`
--

INSERT INTO `wishlist` (`id`, `user_id`, `product_id`, `created_at`) VALUES
(7, 4, 2, '2025-10-12 19:22:23'),
(8, 4, 6, '2025-10-12 19:22:23'),
(9, 4, 9, '2025-10-12 19:22:23'),
(16, 3, 7, '2025-10-14 15:14:44'),
(19, 1, 2, '2025-10-15 02:42:55');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cart_item` (`user_id`,`product_id`),
  ADD UNIQUE KEY `unique_session_cart_item` (`session_id`,`product_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_cart_user` (`user_id`),
  ADD KEY `idx_cart_session` (`session_id`);

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Chỉ mục cho bảng `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_orders_user` (`user_id`),
  ADD KEY `idx_orders_status` (`order_status`),
  ADD KEY `idx_orders_created` (`created_at`),
  ADD KEY `idx_orders_number` (`order_number`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `idx_products_category` (`category_id`),
  ADD KEY `idx_products_status` (`status`),
  ADD KEY `idx_products_featured` (`featured`),
  ADD KEY `idx_products_price` (`price`),
  ADD KEY `idx_products_created` (`created_at`),
  ADD KEY `idx_products_slug` (`slug`);

--
-- Chỉ mục cho bảng `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `product_images`
--
ALTER TABLE `product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Chỉ mục cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_reviews_product` (`product_id`),
  ADD KEY `idx_reviews_status` (`status`);

--
-- Chỉ mục cho bảng `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_email` (`email`),
  ADD KEY `idx_users_username` (`username`);

--
-- Chỉ mục cho bảng `wishlist`
--
ALTER TABLE `wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_wishlist_item` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `cart`
--
ALTER TABLE `cart`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `coupons`
--
ALTER TABLE `coupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `product_attributes`
--
ALTER TABLE `product_attributes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT cho bảng `product_images`
--
ALTER TABLE `product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT cho bảng `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `wishlist`
--
ALTER TABLE `wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `product_attributes`
--
ALTER TABLE `product_attributes`
  ADD CONSTRAINT `product_attributes_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `product_images`
--
ALTER TABLE `product_images`
  ADD CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `wishlist`
--
ALTER TABLE `wishlist`
  ADD CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
