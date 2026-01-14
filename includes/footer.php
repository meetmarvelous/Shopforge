<?php
/**
 * Footer Template
 * 
 * Site footer with links, newsletter, and scripts.
 * 
 * @package ShopForge
 */

defined('SHOPFORGE_INIT') || require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

// Get categories for footer
$db = db();
$footerCategories = $db->fetchAll("SELECT name, slug FROM categories WHERE is_active = 1 ORDER BY sort_order LIMIT 6");
?>

</main>

<!-- Footer -->
<footer class="bg-gradient-to-b from-gray-900 to-gray-950 text-gray-300 mt-auto">
    <!-- Newsletter Section -->
    <div class="border-b border-gray-800">
        <div class="container mx-auto px-4 py-12">
            <div class="max-w-2xl mx-auto text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-primary-500 to-accent-500 rounded-2xl mb-6">
                    <i class="fas fa-envelope text-2xl text-white"></i>
                </div>
                <h3 class="text-2xl lg:text-3xl font-bold text-white mb-3">Subscribe to Our Newsletter</h3>
                <p class="text-gray-400 mb-6">Get the latest updates on new products and upcoming sales.</p>
                <form action="<?= url('ajax/newsletter.php') ?>" method="POST" class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto" id="newsletter-form">
                    <?= csrf_field() ?>
                    <input type="email" name="email" placeholder="Enter your email" required
                           class="flex-1 px-5 py-3.5 bg-gray-800 border border-gray-700 rounded-xl text-white placeholder-gray-500 
                                  focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition">
                    <button type="submit" class="px-6 py-3.5 bg-gradient-to-r from-primary-600 to-accent-500 text-white font-semibold rounded-xl 
                                                 hover:shadow-lg hover:shadow-primary-500/25 transition-all duration-300 whitespace-nowrap">
                        Subscribe
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Main Footer Content -->
    <div class="container mx-auto px-4 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-12">
            <!-- Brand Column -->
            <div class="lg:col-span-1">
                <a href="<?= url() ?>" class="inline-flex items-center space-x-3 mb-6">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-accent-500 rounded-xl flex items-center justify-center">
                        <i class="fas fa-store text-white"></i>
                    </div>
                    <span class="text-xl font-bold text-white"><?= e(SITE_NAME) ?></span>
                </a>
                <p class="text-gray-400 mb-6 leading-relaxed">
                    <?= e(SITE_TAGLINE) ?>. Shop with confidence and enjoy amazing deals on quality products.
                </p>
                <!-- Social Links -->
                <div class="flex space-x-3">
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-xl flex items-center justify-center text-gray-400 hover:bg-primary-600 hover:text-white transition-all">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-xl flex items-center justify-center text-gray-400 hover:bg-pink-600 hover:text-white transition-all">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-xl flex items-center justify-center text-gray-400 hover:bg-blue-500 hover:text-white transition-all">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-xl flex items-center justify-center text-gray-400 hover:bg-red-600 hover:text-white transition-all">
                        <i class="fab fa-youtube"></i>
                    </a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div>
                <h4 class="text-white font-semibold mb-5">Quick Links</h4>
                <ul class="space-y-3">
                    <li><a href="<?= url() ?>" class="text-gray-400 hover:text-primary-400 transition">Home</a></li>
                    <li><a href="<?= url('products.php') ?>" class="text-gray-400 hover:text-primary-400 transition">Shop</a></li>
                    <li><a href="<?= url('about.php') ?>" class="text-gray-400 hover:text-primary-400 transition">About Us</a></li>
                    <li><a href="<?= url('contact.php') ?>" class="text-gray-400 hover:text-primary-400 transition">Contact</a></li>
                    <li><a href="<?= url('faq.php') ?>" class="text-gray-400 hover:text-primary-400 transition">FAQs</a></li>
                </ul>
            </div>
            
            <!-- Categories -->
            <div>
                <h4 class="text-white font-semibold mb-5">Categories</h4>
                <ul class="space-y-3">
                    <?php foreach ($footerCategories as $cat): ?>
                    <li>
                        <a href="<?= url('category.php?slug=' . e($cat['slug'])) ?>" class="text-gray-400 hover:text-primary-400 transition">
                            <?= e($cat['name']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- Contact Info -->
            <div>
                <h4 class="text-white font-semibold mb-5">Contact Us</h4>
                <ul class="space-y-4">
                    <li class="flex items-start">
                        <div class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center flex-shrink-0 mr-3">
                            <i class="fas fa-map-marker-alt text-primary-400"></i>
                        </div>
                        <div>
                            <p class="text-gray-400">Lagos, Nigeria</p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <div class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center flex-shrink-0 mr-3">
                            <i class="fas fa-phone text-primary-400"></i>
                        </div>
                        <div>
                            <p class="text-gray-400"><?= e(SITE_PHONE) ?></p>
                        </div>
                    </li>
                    <li class="flex items-start">
                        <div class="w-10 h-10 bg-gray-800 rounded-lg flex items-center justify-center flex-shrink-0 mr-3">
                            <i class="fas fa-envelope text-primary-400"></i>
                        </div>
                        <div>
                            <p class="text-gray-400"><?= e(SITE_EMAIL) ?></p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Payment Methods & Copyright -->
    <div class="border-t border-gray-800">
        <div class="container mx-auto px-4 py-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                <p class="text-gray-500 text-sm text-center md:text-left">
                    &copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.
                </p>
                <!-- Payment Icons -->
                <div class="flex items-center space-x-4">
                    <span class="text-gray-500 text-sm">We accept:</span>
                    <div class="flex items-center space-x-2">
                        <div class="w-10 h-6 bg-gray-800 rounded flex items-center justify-center">
                            <i class="fab fa-cc-visa text-blue-400"></i>
                        </div>
                        <div class="w-10 h-6 bg-gray-800 rounded flex items-center justify-center">
                            <i class="fab fa-cc-mastercard text-orange-400"></i>
                        </div>
                        <div class="w-10 h-6 bg-gray-800 rounded flex items-center justify-center">
                            <i class="fab fa-cc-paypal text-blue-500"></i>
                        </div>
                        <div class="w-10 h-6 bg-gray-800 rounded flex items-center justify-center text-xs font-bold text-green-400">
                            PS
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Back to Top Button -->
<button type="button" id="back-to-top" 
        class="fixed bottom-6 right-6 w-12 h-12 bg-primary-600 text-white rounded-full shadow-lg 
               flex items-center justify-center opacity-0 invisible transition-all duration-300 hover:bg-primary-700 z-40">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileMenuPanel = document.getElementById('mobile-menu-panel');
    const mobileMenuClose = document.getElementById('mobile-menu-close');
    const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
    
    function openMobileMenu() {
        mobileMenu.classList.remove('hidden');
        setTimeout(() => {
            mobileMenuPanel.classList.remove('translate-x-full');
        }, 10);
        document.body.style.overflow = 'hidden';
    }
    
    function closeMobileMenu() {
        mobileMenuPanel.classList.add('translate-x-full');
        setTimeout(() => {
            mobileMenu.classList.add('hidden');
        }, 300);
        document.body.style.overflow = '';
    }
    
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', openMobileMenu);
    }
    if (mobileMenuClose) {
        mobileMenuClose.addEventListener('click', closeMobileMenu);
    }
    if (mobileMenuOverlay) {
        mobileMenuOverlay.addEventListener('click', closeMobileMenu);
    }
    
    // Mobile Search Toggle
    const mobileSearchToggle = document.getElementById('mobile-search-toggle');
    const mobileSearchPanel = document.getElementById('mobile-search-panel');
    
    if (mobileSearchToggle && mobileSearchPanel) {
        mobileSearchToggle.addEventListener('click', function() {
            mobileSearchPanel.classList.toggle('hidden');
            if (!mobileSearchPanel.classList.contains('hidden')) {
                mobileSearchPanel.querySelector('input').focus();
            }
        });
    }
    
    // User Dropdown Menu
    const userMenuButton = document.getElementById('user-menu-button');
    const userDropdown = document.getElementById('user-dropdown');
    
    if (userMenuButton && userDropdown) {
        userMenuButton.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
        });
        
        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target) && !userMenuButton.contains(e.target)) {
                userDropdown.classList.add('hidden');
            }
        });
    }
    
    // Back to Top Button
    const backToTop = document.getElementById('back-to-top');
    
    if (backToTop) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 500) {
                backToTop.classList.remove('opacity-0', 'invisible');
                backToTop.classList.add('opacity-100', 'visible');
            } else {
                backToTop.classList.add('opacity-0', 'invisible');
                backToTop.classList.remove('opacity-100', 'visible');
            }
        });
        
        backToTop.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }
    
    // Header Scroll Effect
    const header = document.getElementById('main-header');
    let lastScroll = 0;
    
    if (header) {
        window.addEventListener('scroll', function() {
            const currentScroll = window.scrollY;
            
            if (currentScroll > 100) {
                header.classList.add('shadow-md');
            } else {
                header.classList.remove('shadow-md');
            }
            
            lastScroll = currentScroll;
        });
    }
    
    // Newsletter Form
    const newsletterForm = document.getElementById('newsletter-form');
    
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Subscribing...';
            button.disabled = true;
            
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.reset();
                    showNotification('success', data.message || 'Successfully subscribed!');
                } else {
                    showNotification('error', data.error || 'Something went wrong.');
                }
            })
            .catch(error => {
                showNotification('error', 'Something went wrong. Please try again.');
            })
            .finally(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            });
        });
    }
    
    // Notification Toast
    window.showNotification = function(type, message) {
        const colors = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-circle',
            info: 'fa-info-circle'
        };
        
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 z-50 max-w-sm ${colors[type]} text-white px-6 py-4 rounded-xl shadow-lg flex items-center animate-slide-down`;
        toast.innerHTML = `
            <i class="fas ${icons[type]} mr-3 text-lg"></i>
            <span>${message}</span>
            <button class="ml-4 text-white/80 hover:text-white" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 5000);
    };
});

// Add to Cart AJAX
function addToCart(productId, quantity = 1) {
    fetch('<?= url('ajax/cart.php') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=add&product_id=${productId}&quantity=${quantity}&<?= CSRF_TOKEN_NAME ?>=<?= csrf_token() ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count
            const cartBadges = document.querySelectorAll('.cart-count-badge');
            cartBadges.forEach(badge => {
                badge.textContent = data.cart_count;
                badge.classList.remove('hidden');
            });
            showNotification('success', data.message || 'Added to cart!');
        } else {
            showNotification('error', data.error || 'Failed to add to cart.');
        }
    })
    .catch(error => {
        showNotification('error', 'Something went wrong. Please try again.');
    });
}

// Add to Wishlist AJAX
function addToWishlist(productId) {
    fetch('<?= url('ajax/wishlist.php') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `action=add&product_id=${productId}&<?= CSRF_TOKEN_NAME ?>=<?= csrf_token() ?>`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', data.message || 'Added to wishlist!');
        } else {
            if (data.login_required) {
                window.location.href = '<?= url('login.php') ?>';
            } else {
                showNotification('error', data.error || 'Failed to add to wishlist.');
            }
        }
    })
    .catch(error => {
        showNotification('error', 'Something went wrong. Please try again.');
    });
}
</script>

<?php if (isset($extraScripts)) echo $extraScripts; ?>
</body>
</html>
