/**
 * MAIN JAVASCRIPT FILE
 * E-commerce Mini Shop
 */

// Global variables
let cart = JSON.parse(localStorage.getItem('cart')) || [];
let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];

// Utility functions
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
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

// Cart functions
function addToCart(productId, productData) {
    console.log('Adding to cart:', productId, productData);
    
    // Send AJAX request to server
    fetch('cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add&product_id=${productId}&quantity=1`
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            showToast(data.message, 'success');
            updateCartCounter();
    } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Có lỗi xảy ra khi thêm vào giỏ hàng', 'error');
    });
}

function removeFromCart(productId) {
    // Send AJAX request to server
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
            showToast(data.message, 'success');
            updateCartCounter();
    } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Có lỗi xảy ra khi xóa khỏi giỏ hàng', 'error');
    });
}

function updateCartCounter() {
    console.log('Updating cart counter...');
    
    // Get cart count from server
    fetch('cart.php?action=get_count')
    .then(response => {
        console.log('Counter response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Counter response data:', data);
        const counter = document.querySelector('.cart-counter');
        if (counter) {
            counter.textContent = data.count || 0;
            counter.style.display = (data.count || 0) > 0 ? 'block' : 'none';
            console.log('Counter updated:', data.count);
        } else {
            console.log('Counter element not found');
        }
    })
    .catch(error => {
        console.error('Error updating counter:', error);
    });
}

// Wishlist functions
function toggleWishlist(productId, productData) {
    // Send AJAX request to server
    fetch('wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            updateWishlistCounter();
            } else {
            showToast(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Có lỗi xảy ra khi thêm vào danh sách yêu thích', 'error');
    });
}

function updateWishlistCounter() {
    console.log('Updating wishlist counter...');
    
    // Get wishlist count from server
    fetch('wishlist.php?action=get_count')
    .then(response => {
        console.log('Wishlist counter response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Wishlist counter response data:', data);
        const counter = document.querySelector('.wishlist-counter');
        if (counter) {
            counter.textContent = data.count || 0;
            counter.style.display = (data.count || 0) > 0 ? 'block' : 'none';
            console.log('Wishlist counter updated:', data.count);
        } else {
            console.log('Wishlist counter element not found');
        }
    })
    .catch(error => {
        console.error('Error updating wishlist counter:', error);
    });
}

// Search functionality
function initSearch() {
    const searchInput = document.querySelector('.search-input');
    const searchResults = document.querySelector('.search-results');
    
    if (searchInput && searchResults) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchResults.style.display = 'none';
            return;
        }
        
            searchTimeout = setTimeout(() => {
                performSearch(query, searchResults);
            }, 300);
        });
        
        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }
}

function performSearch(query, resultsContainer) {
    // Simulate search - replace with actual API call
    const mockResults = [
        { id: 1, name: 'iPhone 15 Pro', price: 25000000, image: 'assets/images/products/iphone15.jpg' },
        { id: 2, name: 'Samsung Galaxy S24', price: 22000000, image: 'assets/images/products/galaxy-s24.jpg' },
        { id: 3, name: 'MacBook Pro M3', price: 45000000, image: 'assets/images/products/macbook-pro.jpg' }
    ];
    
    const filteredResults = mockResults.filter(product => 
        product.name.toLowerCase().includes(query.toLowerCase())
    );
    
    if (filteredResults.length > 0) {
        resultsContainer.innerHTML = filteredResults.map(product => `
            <div class="search-result-item" onclick="window.location.href='product.php?id=${product.id}'">
                <img src="${product.image}" alt="${product.name}">
                    <div class="search-result-details">
                        <h4>${product.name}</h4>
                    <p class="search-result-price">${formatPrice(product.price)}</p>
                    </div>
                </div>
            `).join('');
        resultsContainer.style.display = 'block';
    } else {
        resultsContainer.innerHTML = '<div class="search-result-item"><p>Không tìm thấy sản phẩm</p></div>';
        resultsContainer.style.display = 'block';
    }
}

// Mobile menu functionality
function initMobileMenu() {
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileMenu = document.querySelector('.mobile-menu');
    const mobileMenuClose = document.querySelector('.mobile-menu-close');
    
    if (mobileMenuToggle && mobileMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenu.classList.add('active');
            this.classList.add('active');
        });
        
        if (mobileMenuClose) {
            mobileMenuClose.addEventListener('click', function() {
                mobileMenu.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
            });
        }
        
        // Close menu when clicking outside
        mobileMenu.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                mobileMenuToggle.classList.remove('active');
            }
        });
    }
}

// Format price function
function formatPrice(price) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(price);
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize counters (only if elements exist)
    const cartCounter = document.querySelector('.cart-counter');
    const wishlistCounter = document.querySelector('.wishlist-counter');
    
    if (cartCounter) {
        // Only update counter if we're not on cart page
        if (!window.location.pathname.includes('cart.php')) {
            updateCartCounter();
        }
    }
    if (wishlistCounter) {
        updateWishlistCounter();
    }
    
    // Initialize search
    initSearch();
    
    // Initialize mobile menu
    initMobileMenu();
    
    // Add event listeners for add to cart buttons
    document.addEventListener('click', function(e) {
        if (e.target.closest('.add-to-cart-btn')) {
            const button = e.target.closest('.add-to-cart-btn');
            const productId = button.dataset.productId;
            const productData = JSON.parse(button.dataset.productData);
            
            addToCart(productId, productData);
        }
        
        if (e.target.closest('.wishlist-btn')) {
            const button = e.target.closest('.wishlist-btn');
            const productId = button.dataset.productId;
            const productData = JSON.parse(button.dataset.productData);
            
            toggleWishlist(productId, productData);
        }
    });
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
                e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Lazy loading for images
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
});

// Toast styles
const toastStyles = `
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
    
    .toast-info {
        border-left: 4px solid #3b82f6;
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
    
    .toast-info .toast-content i {
        color: #3b82f6;
    }
`;

// Inject toast styles
const styleSheet = document.createElement('style');
styleSheet.textContent = toastStyles;
document.head.appendChild(styleSheet);