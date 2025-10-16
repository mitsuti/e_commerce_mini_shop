# 🛍️ E-COMMERCE MINI SHOP

Một ứng dụng web thương mại điện tử hoàn chỉnh với giao diện cực kỳ đẹp mắt và hiện đại, được xây dựng bằng HTML/CSS/JavaScript và PHP thuần.

## ✨ Tính năng nổi bật

### 🎨 Giao diện xuất sắc
- **Design hiện đại**: Giao diện được thiết kế theo xu hướng 2024-2025 với animations mượt mà
- **Responsive hoàn hảo**: Tối ưu cho mọi thiết bị từ desktop đến mobile
- **Animations đẹp mắt**: Hero slider, hover effects, scroll animations, loading states
- **Color scheme chuyên nghiệp**: Sử dụng gradient, glassmorphism, và modern UI patterns

### 🛒 Chức năng E-commerce đầy đủ
- **Trang chủ**: Hero slider, featured products, categories, testimonials
- **Sản phẩm**: Danh sách với filters, search, pagination, chi tiết sản phẩm
- **Giỏ hàng**: Quản lý giỏ hàng với AJAX, coupon codes, checkout
- **Thanh toán**: Multi-step checkout với validation
- **Tài khoản**: Đăng nhập/đăng ký với security features

### 👨‍💼 Admin Panel
- **Dashboard**: Thống kê tổng quan với charts
- **Quản lý sản phẩm**: CRUD operations với image upload
- **Quản lý đơn hàng**: Xem và cập nhật trạng thái đơn hàng
- **Quản lý khách hàng**: Danh sách và thông tin khách hàng
- **Quản lý danh mục**: Tạo và quản lý categories

### 🔒 Bảo mật
- **Password hashing**: Sử dụng `password_hash()` và `password_verify()`
- **Prepared statements**: Bảo vệ khỏi SQL injection
- **CSRF protection**: Token validation cho forms
- **XSS protection**: `htmlspecialchars()` cho output
- **Input validation**: Server-side và client-side validation

## 🚀 HƯỚNG DẪN CÀI ĐẶT

### **Bước 1: Cài đặt XAMPP**
1. Tải và cài đặt XAMPP từ [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Khởi động XAMPP Control Panel
3. Bật Apache và MySQL services

### **Bước 2: Cài đặt Project**
1. Copy thư mục project vào `C:\xampp\htdocs\`
2. Đảm bảo đường dẫn: `C:\xampp\htdocs\E-COMMERCE_MINI_SHOP\`

### **Bước 3: Truy cập Website**
- **Trang chủ:** `http://localhost/E-COMMERCE_MINI_SHOP/`
- **Admin Panel:** `http://localhost/E-COMMERCE_MINI_SHOP/admin/`

**Lưu ý:** Database sẽ được tự động tạo và import dữ liệu mẫu khi bạn truy cập lần đầu.

## 👥 TÀI KHOẢN DEMO

### **Admin Account**
- **Username:** `admin`
- **Password:** `admin123`
- **Quyền:** Quản lý toàn bộ hệ thống

### **Customer Accounts**
- **Username:** `customer1`, `customer2`, `customer3`
- **Password:** `password`
- **Quyền:** Mua hàng, xem đơn hàng

## 📊 DỮ LIỆU MẪU

### **Sản phẩm (15 sản phẩm)**
- iPhone 15 Pro Max, Samsung Galaxy S24 Ultra
- MacBook Pro M3, Dell XPS 13
- Apple Watch Series 9, Samsung Galaxy Watch 6
- AirPods Pro 2, Sony WH-1000XM5
- Gaming Chair, Mechanical Keyboard, Gaming Mouse
- Monitor 4K, iPad Air 5, Surface Pro 9, MacBook Air M2

### **Danh mục (6 danh mục)**
- Điện thoại, Laptop, Đồng hồ
- Phụ kiện, Gaming, Máy tính

### **Đơn hàng (5 đơn hàng mẫu)**
- Các trạng thái: pending, confirmed, shipped, delivered
- Thanh toán: COD, Bank Transfer

### **Mã giảm giá (3 mã)**
- `SALE10`: Giảm 10%
- `FREESHIP`: Miễn phí ship
- `NEWUSER`: Giảm 50k cho khách mới

## 🛠️ CẤU TRÚC PROJECT

```
E-COMMERCE_MINI_SHOP/
├── assets/
│   ├── css/
│   │   ├── main.css
│   │   └── components.css
│   ├── js/
│   │   └── main.js
│   └── images/
├── config/
│   ├── config.php
│   └── database.php
├── database/
│   ├── schema.sql
│   └── ecommerce_db_complete.sql
├── includes/
│   ├── header.php
│   └── footer.php
├── admin/
│   ├── index.php
│   ├── dashboard.php
│   └── logout.php
├── uploads/
│   ├── products/
│   ├── banners/
│   ├── categories/
│   └── users/
├── index.php
├── products.php
├── product.php
├── cart.php
├── checkout.php
├── login.php
├── register.php
├── .htaccess
└── README.md
```

## 🎯 TÍNH NĂNG CHI TIẾT

### **Frontend**
- ✅ Responsive design (Mobile-first)
- ✅ Modern UI với animations
- ✅ Hero slider với auto-play
- ✅ Product grid với filters
- ✅ Shopping cart với AJAX
- ✅ Multi-step checkout
- ✅ User authentication
- ✅ Search với autocomplete
- ✅ Wishlist functionality
- ✅ Newsletter subscription

### **Backend**
- ✅ MVC pattern đơn giản
- ✅ Database abstraction layer
- ✅ Security best practices
- ✅ Error handling
- ✅ Session management
- ✅ File upload handling
- ✅ Email functionality
- ✅ Admin panel

### **Database**
- ✅ Normalized database design
- ✅ Foreign key constraints
- ✅ Indexes cho performance
- ✅ Sample data đầy đủ
- ✅ Backup/restore scripts

## 🚀 DEPLOYMENT

### **Local Development**
1. Sử dụng XAMPP
2. Truy cập qua `http://localhost/E-COMMERCE_MINI_SHOP/`

### **Production (AWS EC2)**
1. Upload files lên EC2 instance
2. Cài đặt LAMP stack
3. Cấu hình Apache virtual host
4. Import database
5. Cấu hình SSL certificate

## 📝 GHI CHÚ

- **PHP Version:** 7.4+ (Tested với PHP 8.2)
- **MySQL Version:** 5.7+ (Tested với MySQL 8.0)
- **Browser Support:** Chrome, Firefox, Safari, Edge
- **Mobile Support:** iOS Safari, Chrome Mobile

## 🤝 HỖ TRỢ

Nếu gặp vấn đề:
1. Kiểm tra file `TROUBLESHOOTING.md`
2. Xem Apache error logs
3. Kiểm tra PHP error logs

---

**🎉 Chúc bạn sử dụng thành công!**