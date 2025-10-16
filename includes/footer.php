<?php
/**
 * FOOTER COMPONENT
 * Footer với thông tin liên hệ và links
 */

$footer_categories = fetchData("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active' 
    WHERE c.status = 'active' AND c.parent_id IS NULL
    GROUP BY c.id 
    ORDER BY c.sort_order 
    LIMIT 6
");

$footer_products = fetchData("
    SELECT p.*, pi.image_path 
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
    WHERE p.featured = 1 AND p.status = 'active' 
    ORDER BY p.created_at DESC 
    LIMIT 4
");
?>

<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <!-- Company Info -->
            <div class="footer-section">
                <div class="footer-logo">
                    <div class="logo">
                        <div class="logo-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <span><?php echo SITE_NAME; ?></span>
                    </div>
                </div>
                <p class="footer-description">
                    Cửa hàng điện tử mini với sản phẩm chất lượng cao, giá cả hợp lý. 
                    Chúng tôi cam kết mang đến trải nghiệm mua sắm tốt nhất cho khách hàng.
                </p>
                
                <div class="footer-contact">
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>123 Đường ABC, Quận 1, TP.HCM</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <span><?php echo SITE_PHONE; ?></span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <span><?php echo SITE_EMAIL; ?></span>
                    </div>
                </div>
                
                <div class="footer-social">
                    <a href="#" class="social-link facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-link instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="social-link youtube">
                        <i class="fab fa-youtube"></i>
                    </a>
                    <a href="#" class="social-link tiktok">
                        <i class="fab fa-tiktok"></i>
                    </a>
                    <a href="#" class="social-link zalo">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="footer-section">
                <h4 class="footer-title">Liên kết nhanh</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Trang chủ</a></li>
                    <li><a href="products.php">Sản phẩm</a></li>
                    <li><a href="products.php">Danh mục</a></li>
                    <li><a href="about.php">Giới thiệu</a></li>
                    <li><a href="contact.php">Liên hệ</a></li>
                    <li><a href="blog.php">Tin tức</a></li>
                </ul>
            </div>
            
            <!-- Categories -->
            <div class="footer-section">
                <h4 class="footer-title">Danh mục</h4>
                <ul class="footer-links">
                    <?php foreach ($footer_categories as $category): ?>
                        <li>
                            <a href="products.php?category=<?php echo $category['slug']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                                <span class="product-count">(<?php echo $category['product_count']; ?>)</span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Customer Service -->
            <div class="footer-section">
                <h4 class="footer-title">Hỗ trợ khách hàng</h4>
                <ul class="footer-links">
                    <li><a href="help.php">Trung tâm trợ giúp</a></li>
                    <li><a href="shipping.php">Vận chuyển</a></li>
                    <li><a href="returns.php">Đổi trả</a></li>
                    <li><a href="warranty.php">Bảo hành</a></li>
                    <li><a href="faq.php">Câu hỏi thường gặp</a></li>
                    <li><a href="size-guide.php">Hướng dẫn chọn size</a></li>
                </ul>
            </div>
            
            <!-- Featured Products -->
            <div class="footer-section">
                <h4 class="footer-title">Sản phẩm nổi bật</h4>
                <div class="footer-products">
                    <?php foreach ($footer_products as $product): ?>
                        <div class="footer-product">
                            <img src="<?php echo $product['image_path'] ?: 'assets/images/placeholder.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <div class="footer-product-info">
                                <h5>
                                    <a href="product.php?id=<?php echo $product['id']; ?>">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </a>
                                </h5>
                                <p class="footer-product-price">
                                    <?php echo formatPrice($product['sale_price'] ?: $product['price']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Newsletter -->
        <div class="footer-newsletter">
            <div class="newsletter-content">
                <div class="newsletter-text">
                    <h4>Đăng ký nhận tin</h4>
                    <p>Nhận thông tin về sản phẩm mới và ưu đãi đặc biệt</p>
                </div>
                <form class="newsletter-form" id="footerNewsletterForm">
                    <input type="email" 
                           class="newsletter-input" 
                           placeholder="Nhập email của bạn" 
                           required>
                    <button type="submit" class="newsletter-button">
                        <i class="fas fa-paper-plane"></i> Đăng ký
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Payment Methods -->
        <div class="footer-payment">
            <h5>Phương thức thanh toán</h5>
            <div class="payment-methods">
                <div class="payment-method">
                    <i class="fas fa-credit-card"></i>
                    <span>Thẻ tín dụng</span>
                </div>
                <div class="payment-method">
                    <i class="fas fa-university"></i>
                    <span>Chuyển khoản</span>
                </div>
                <div class="payment-method">
                    <i class="fas fa-money-bill-wave"></i>
                    <span>COD</span>
                </div>
                <div class="payment-method">
                    <i class="fas fa-mobile-alt"></i>
                    <span>Ví điện tử</span>
                </div>
            </div>
        </div>
        
        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <div class="footer-copyright">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Tất cả quyền được bảo lưu.</p>
                </div>
                <div class="footer-legal">
                    <a href="privacy.php">Chính sách bảo mật</a>
                    <a href="terms.php">Điều khoản sử dụng</a>
                    <a href="cookies.php">Cookie Policy</a>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button class="back-to-top" id="backToTop">
    <i class="fas fa-chevron-up"></i>
</button>

<style>
/* Footer Styles */
.footer {
    background: var(--gray-900);
    color: var(--white);
    padding: var(--space-20) 0 var(--space-8);
    position: relative;
}

.footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--primary-color), transparent);
}

.footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--space-12);
    margin-bottom: var(--space-16);
}

.footer-section h4 {
    color: var(--white);
    margin-bottom: var(--space-6);
    font-size: var(--text-lg);
    position: relative;
}

.footer-section h4::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 0;
    width: 40px;
    height: 2px;
    background: var(--primary-color);
}

.footer-logo {
    margin-bottom: var(--space-6);
}

.footer-logo .logo {
    color: var(--white);
    font-size: var(--text-2xl);
}

.footer-description {
    color: var(--gray-400);
    line-height: 1.6;
    margin-bottom: var(--space-6);
}

.footer-contact {
    margin-bottom: var(--space-6);
}

.contact-item {
    display: flex;
    align-items: center;
    gap: var(--space-3);
    margin-bottom: var(--space-3);
    color: var(--gray-400);
}

.contact-item i {
    color: var(--primary-color);
    width: 20px;
}

.footer-social {
    display: flex;
    gap: var(--space-3);
}

.social-link {
    width: 40px;
    height: 40px;
    background: var(--gray-800);
    border-radius: var(--radius-full);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    text-decoration: none;
    transition: all var(--transition-fast);
    position: relative;
    overflow: hidden;
}

.social-link::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--primary-color);
    transform: scale(0);
    transition: transform var(--transition-fast);
    border-radius: var(--radius-full);
}

.social-link:hover::before {
    transform: scale(1);
}

.social-link:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-lg);
}

.social-link i {
    position: relative;
    z-index: 1;
}

.social-link.facebook:hover::before { background: #1877f2; }
.social-link.instagram:hover::before { background: #e4405f; }
.social-link.youtube:hover::before { background: #ff0000; }
.social-link.tiktok:hover::before { background: #000000; }
.social-link.zalo:hover::before { background: #0068ff; }

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: var(--space-3);
}

.footer-links a {
    color: var(--gray-400);
    text-decoration: none;
    transition: all var(--transition-fast);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.footer-links a:hover {
    color: var(--white);
    padding-left: var(--space-2);
}

.product-count {
    font-size: var(--text-xs);
    color: var(--gray-500);
}

.footer-products {
    display: flex;
    flex-direction: column;
    gap: var(--space-4);
}

.footer-product {
    display: flex;
    gap: var(--space-3);
    align-items: center;
}

.footer-product img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: var(--radius-md);
}

.footer-product-info h5 {
    margin: 0 0 var(--space-1) 0;
    font-size: var(--text-sm);
}

.footer-product-info a {
    color: var(--gray-300);
    text-decoration: none;
    transition: color var(--transition-fast);
}

.footer-product-info a:hover {
    color: var(--white);
}

.footer-product-price {
    color: var(--primary-color);
    font-weight: 600;
    font-size: var(--text-sm);
    margin: 0;
}

/* Newsletter */
.footer-newsletter {
    background: var(--gray-800);
    border-radius: var(--radius-xl);
    padding: var(--space-8);
    margin-bottom: var(--space-12);
}

.newsletter-content {
    display: flex;
    align-items: center;
    gap: var(--space-8);
    flex-wrap: wrap;
}

.newsletter-text {
    flex: 1;
    min-width: 300px;
}

.newsletter-text h4 {
    color: var(--white);
    margin-bottom: var(--space-2);
    font-size: var(--text-xl);
}

.newsletter-text p {
    color: var(--gray-400);
    margin: 0;
}

.newsletter-form {
    display: flex;
    gap: var(--space-3);
    min-width: 300px;
}

.newsletter-input {
    flex: 1;
    padding: var(--space-3) var(--space-4);
    border: 1px solid var(--gray-600);
    border-radius: var(--radius-md);
    background: var(--gray-700);
    color: var(--white);
    font-size: var(--text-base);
}

.newsletter-input:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.newsletter-input::placeholder {
    color: var(--gray-400);
}

.newsletter-button {
    padding: var(--space-3) var(--space-6);
    background: var(--primary-color);
    color: var(--white);
    border: none;
    border-radius: var(--radius-md);
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition-fast);
    white-space: nowrap;
}

.newsletter-button:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
}

/* Payment Methods */
.footer-payment {
    margin-bottom: var(--space-12);
    text-align: center;
}

.footer-payment h5 {
    color: var(--white);
    margin-bottom: var(--space-6);
    font-size: var(--text-lg);
}

.payment-methods {
    display: flex;
    justify-content: center;
    gap: var(--space-6);
    flex-wrap: wrap;
}

.payment-method {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--space-2);
    color: var(--gray-400);
    transition: color var(--transition-fast);
}

.payment-method:hover {
    color: var(--white);
}

.payment-method i {
    font-size: var(--text-2xl);
    color: var(--primary-color);
}

.payment-method span {
    font-size: var(--text-sm);
}

/* Footer Bottom */
.footer-bottom {
    border-top: 1px solid var(--gray-800);
    padding-top: var(--space-8);
}

.footer-bottom-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--space-4);
}

.footer-copyright p {
    color: var(--gray-400);
    margin: 0;
}

.footer-legal {
    display: flex;
    gap: var(--space-6);
}

.footer-legal a {
    color: var(--gray-400);
    text-decoration: none;
    font-size: var(--text-sm);
    transition: color var(--transition-fast);
}

.footer-legal a:hover {
    color: var(--white);
}

/* Back to Top Button */
.back-to-top {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 50px;
    height: 50px;
    background: var(--primary-color);
    color: var(--white);
    border: none;
    border-radius: var(--radius-full);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: var(--text-lg);
    box-shadow: var(--shadow-lg);
    transition: all var(--transition-fast);
    z-index: var(--z-fixed);
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px);
}

.back-to-top.visible {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.back-to-top:hover {
    background: var(--primary-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-xl);
}

/* Responsive */
@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
        text-align: center;
        gap: var(--space-8);
    }
    
    .newsletter-content {
        flex-direction: column;
        text-align: center;
    }
    
    .newsletter-form {
        width: 100%;
        flex-direction: column;
    }
    
    .footer-bottom-content {
        flex-direction: column;
        text-align: center;
    }
    
    .footer-legal {
        justify-content: center;
    }
    
    .payment-methods {
        gap: var(--space-4);
    }
    
    .back-to-top {
        bottom: 1rem;
        right: 1rem;
        width: 45px;
        height: 45px;
    }
}

@media (max-width: 480px) {
    .footer {
        padding: var(--space-16) 0 var(--space-6);
    }
    
    .footer-newsletter {
        padding: var(--space-6);
    }
    
    .newsletter-text,
    .newsletter-form {
        min-width: auto;
    }
    
    .footer-legal {
        flex-direction: column;
        gap: var(--space-2);
    }
    
    .payment-methods {
        grid-template-columns: repeat(2, 1fr);
        gap: var(--space-3);
    }
}
</style>

<script>
// Back to Top Button
document.addEventListener('DOMContentLoaded', function() {
    const backToTopButton = document.getElementById('backToTop');
    
    // Show/hide button based on scroll position
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTopButton.classList.add('visible');
        } else {
            backToTopButton.classList.remove('visible');
        }
    });
    
    // Smooth scroll to top
    backToTopButton.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Footer Newsletter Form
    const footerNewsletterForm = document.getElementById('footerNewsletterForm');
    if (footerNewsletterForm) {
        footerNewsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('.newsletter-input').value;
            
            // Simulate API call
            setTimeout(() => {
                showToast('Cảm ơn bạn đã đăng ký nhận tin!', 'success');
                this.reset();
            }, 1000);
        });
    }
});
</script>
