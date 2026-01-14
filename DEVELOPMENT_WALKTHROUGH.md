# ShopForge Development Walkthrough

> **A comprehensive guide for developers to understand, set up, and extend the ShopForge e-commerce platform.**

---

## 📋 Table of Contents

1. [Project Overview](#project-overview)
2. [Architecture Overview](#architecture-overview)
3. [Getting Started](#getting-started)
4. [Directory Structure](#directory-structure)
5. [Core Components](#core-components)
6. [Database Schema](#database-schema)
7. [Frontend Development](#frontend-development)
8. [Admin Panel Development](#admin-panel-development)
9. [Security Implementation](#security-implementation)
10. [Payment Integration](#payment-integration)
11. [Email System](#email-system)
12. [Extending the Platform](#extending-the-platform)
13. [Testing Guidelines](#testing-guidelines)
14. [Deployment Checklist](#deployment-checklist)
15. [Troubleshooting](#troubleshooting)

---

## 🎯 Project Overview

**ShopForge** is a full-featured e-commerce platform built with PHP and MySQL. It provides:

- **Customer Storefront**: Product browsing, shopping cart, checkout, and order management
- **Admin Dashboard**: Complete back-office management for products, orders, customers, and analytics
- **Security**: CSRF protection, prepared statements, password hashing, and session management
- **Payment Processing**: Paystack integration (extensible to other gateways)

### Tech Stack

| Component    | Technology                     |
| ------------ | ------------------------------ |
| Backend      | PHP 7.4+                       |
| Database     | MySQL/MariaDB with PDO         |
| Frontend CSS | Tailwind CSS                   |
| JavaScript   | Vanilla JS + jQuery (optional) |
| Icons        | Font Awesome, Heroicons        |
| Charts       | Chart.js (admin dashboard)     |
| Email        | PHPMailer (SMTP)               |
| Payment      | Paystack API                   |

---

## 🏗️ Architecture Overview

ShopForge follows a modular procedural PHP architecture with separation of concerns:

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENT BROWSER                           │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     APACHE WEB SERVER                           │
│                    (XAMPP / LAMP / WAMP)                        │
└─────────────────────────────────────────────────────────────────┘
                              │
          ┌───────────────────┼───────────────────┐
          ▼                   ▼                   ▼
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│   STOREFRONT    │  │   ADMIN PANEL   │  │   AJAX APIs     │
│   (index.php)   │  │ (admin/index)   │  │  (ajax/*.php)   │
└─────────────────┘  └─────────────────┘  └─────────────────┘
          │                   │                   │
          └───────────────────┼───────────────────┘
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      INCLUDES LAYER                             │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐    │
│  │ Database │  │ Session  │  │ Security │  │  Functions   │    │
│  │   .php   │  │   .php   │  │   .php   │  │     .php     │    │
│  └──────────┘  └──────────┘  └──────────┘  └──────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    MySQL DATABASE                               │
│                    (shopforge_db)                               │
└─────────────────────────────────────────────────────────────────┘
```

### Request Flow

1. **Request Received**: Apache routes request to appropriate PHP file
2. **Configuration Loaded**: `config.php` sets up environment constants
3. **Session Started**: `Session.php` initializes secure session handling
4. **Database Connected**: `Database.php` establishes PDO connection
5. **Authentication Checked**: `Security.php` validates user/admin access
6. **Business Logic Executed**: Main file processes request
7. **View Rendered**: HTML with Tailwind CSS sent to browser

---

## 🚀 Getting Started

### Prerequisites

- **PHP**: Version 7.4 or higher
- **MySQL/MariaDB**: Version 5.7+ or 10.3+
- **Web Server**: Apache with mod_rewrite enabled
- **Composer**: For installing PHPMailer (optional)

### Installation Steps

#### 1. Clone/Download the Repository

```bash
cd /path/to/your/webserver/htdocs
git clone https://github.com/yourusername/shopforge.git
cd shopforge
```

#### 2. Create the Database

```sql
-- Option 1: Import via command line
mysql -u root -p < db/shopforge.sql

-- Option 2: Import via phpMyAdmin
-- Navigate to phpMyAdmin > Import > Select db/shopforge.sql
```

#### 3. Configure the Application

Edit `config.php` and update the following sections:

**Database Configuration:**

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'shopforge_db');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
```

**Email Configuration (PHPMailer):**

```php
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 465);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-app-password');
```

**Payment Gateway (Paystack):**

```php
define('PAYSTACK_PUBLIC_KEY', 'pk_test_...');
define('PAYSTACK_SECRET_KEY', 'sk_test_...');
```

#### 4. Set File Permissions

```bash
# Create upload directories
mkdir -p uploads/products uploads/users uploads/banners

# Set permissions (Linux/Mac)
chmod -R 755 uploads/
chmod 644 config.php
```

#### 5. Access the Application

- **Storefront**: `http://localhost/shopforge/`
- **Admin Panel**: `http://localhost/shopforge/admin/`
- **Default Admin**: `admin@admin.com` / `Admin@123`

---

## 📁 Directory Structure

```
shopforge/
├── admin/                      # Admin panel files
│   ├── includes/               # Admin-specific includes
│   │   ├── header.php          # Admin HTML head & meta
│   │   ├── topbar.php          # Admin top navigation bar
│   │   ├── sidebar.php         # Admin sidebar navigation
│   │   └── footer.php          # Admin footer & scripts
│   ├── index.php               # Admin dashboard
│   ├── products.php            # Product management (CRUD)
│   ├── categories.php          # Category management (CRUD)
│   ├── customers.php           # Customer management
│   ├── orders.php              # Order management
│   ├── coupons.php             # Coupon/discount management
│   ├── reports.php             # Sales & analytics reports
│   ├── settings.php            # System settings
│   ├── login.php               # Admin authentication
│   └── logout.php              # Admin logout
│
├── ajax/                       # AJAX endpoint handlers
│   ├── cart.php                # Cart operations (add, update, remove)
│   └── wishlist.php            # Wishlist operations
│
├── db/                         # Database files
│   └── shopforge.sql           # Database schema & seed data
│
├── includes/                   # Core includes (shared)
│   ├── Database.php            # PDO database wrapper class
│   ├── Session.php             # Secure session management
│   ├── Security.php            # CSRF, XSS, authentication helpers
│   ├── functions.php           # Helper functions (formatting, etc.)
│   ├── header.php              # Frontend HTML head
│   ├── navbar.php              # Frontend navigation bar
│   ├── sidebar.php             # Category sidebar
│   └── footer.php              # Frontend footer & scripts
│
├── uploads/                    # User-uploaded content
│   ├── products/               # Product images
│   ├── users/                  # User avatars
│   └── banners/                # Homepage banners
│
├── config.php                  # Application configuration
├── index.php                   # Homepage (storefront)
├── product.php                 # Single product page
├── products.php                # Product listing/search
├── cart.php                    # Shopping cart page
├── checkout.php                # Checkout process
├── payment.php                 # Payment initialization
├── payment-callback.php        # Payment gateway callback
├── order-success.php           # Order confirmation page
├── login.php                   # Customer login
├── signup.php                  # Customer registration
├── verify.php                  # Email verification handler
├── forgot-password.php         # Password reset request
├── reset-password.php          # Password reset form
├── logout.php                  # Customer logout
├── .htaccess                   # Apache configuration
└── PRD.md                      # Product Requirements Document
```

---

## 🔧 Core Components

### 1. Database.php - Database Connection Class

```php
<?php
// Location: includes/Database.php

class Database {
    private static $instance = null;
    private $pdo;

    // Singleton pattern for single connection
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Get PDO connection
    public function getConnection() {
        return $this->pdo;
    }

    // Execute prepared statement
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Fetch single row
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }

    // Fetch all rows
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
}
```

**Usage Example:**

```php
$db = Database::getInstance();

// Fetch single product
$product = $db->fetch(
    "SELECT * FROM products WHERE id = ?",
    [$productId]
);

// Fetch all active categories
$categories = $db->fetchAll(
    "SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order"
);

// Insert new record
$db->query(
    "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)",
    [$userId, $productId, $quantity]
);
```

### 2. Session.php - Secure Session Management

Handles:

- Secure session configuration
- Session regeneration to prevent fixation
- Flash messages (success, error, info)
- Cart persistence

```php
<?php
// Location: includes/Session.php

class Session {
    public static function start() {
        // Configure secure session settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', !APP_DEBUG);
        ini_set('session.use_strict_mode', 1);

        session_name(SESSION_NAME);
        session_start();

        // Regenerate ID periodically
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
        } elseif (time() - $_SESSION['_created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['_created'] = time();
        }
    }

    // Flash message helpers
    public static function flash($key, $message = null) {
        if ($message !== null) {
            $_SESSION['_flash'][$key] = $message;
        } else {
            $msg = $_SESSION['_flash'][$key] ?? null;
            unset($_SESSION['_flash'][$key]);
            return $msg;
        }
    }
}
```

### 3. Security.php - Security Utilities

Provides:

- CSRF token generation and validation
- Input sanitization
- Password validation
- Rate limiting

```php
<?php
// Location: includes/Security.php

class Security {
    // Generate CSRF token
    public static function generateCSRFToken() {
        $token = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME] = $token;
        $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
        return $token;
    }

    // Validate CSRF token
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }

        // Check token expiry
        $tokenTime = $_SESSION[CSRF_TOKEN_NAME . '_time'] ?? 0;
        if (time() - $tokenTime > CSRF_TOKEN_LIFETIME) {
            return false;
        }

        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }

    // Sanitize input
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    // Check if user is authenticated
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    // Require authentication
    public static function requireAuth($redirectUrl = null) {
        if (!self::isLoggedIn()) {
            $redirect = $redirectUrl ?? BASE_URL . '/login.php';
            header("Location: $redirect");
            exit;
        }
    }
}
```

### 4. functions.php - Helper Functions

Common utilities used throughout the application:

```php
<?php
// Location: includes/functions.php

// Format currency
function formatPrice($amount) {
    $formatted = number_format($amount, 2);
    if (CURRENCY_POSITION === 'before') {
        return CURRENCY_SYMBOL . $formatted;
    }
    return $formatted . ' ' . CURRENCY_SYMBOL;
}

// Generate URL-friendly slug
function createSlug($string) {
    $slug = strtolower(trim($string));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

// Calculate cart total
function getCartTotal($cartItems) {
    $total = 0;
    foreach ($cartItems as $item) {
        $price = $item['sale_price'] ?? $item['price'];
        $total += $price * $item['quantity'];
    }
    return $total;
}

// Generate unique order number
function generateOrderNumber() {
    return 'SF-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// Get time ago string
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';

    return date('M j, Y', $time);
}
```

---

## 🗄️ Database Schema

### Entity Relationship Overview

```
┌──────────────┐       ┌──────────────┐       ┌──────────────┐
│    users     │───┐   │   products   │───────│  categories  │
└──────────────┘   │   └──────────────┘       └──────────────┘
       │           │          │    │
       │           │          │    └──────┬──────────────┐
       ▼           │          ▼           ▼              ▼
┌──────────────┐   │   ┌───────────┐  ┌─────────┐  ┌──────────┐
│   orders     │───┘   │   cart    │  │ wishlist │  │ reviews  │
└──────────────┘       └───────────┘  └─────────┘  └──────────┘
       │
       ▼
┌──────────────┐
│ order_items  │
└──────────────┘
```

### Key Tables

| Table            | Purpose                                             |
| ---------------- | --------------------------------------------------- |
| `users`          | Customer accounts                                   |
| `admins`         | Admin/staff accounts                                |
| `categories`     | Product categories (self-referencing for hierarchy) |
| `products`       | Product catalog                                     |
| `product_images` | Additional product images                           |
| `cart`           | Shopping cart items                                 |
| `wishlist`       | User wishlists                                      |
| `orders`         | Customer orders                                     |
| `order_items`    | Items within each order                             |
| `payments`       | Payment transactions                                |
| `coupons`        | Discount codes                                      |
| `reviews`        | Product reviews and ratings                         |
| `banners`        | Homepage carousel                                   |
| `settings`       | Dynamic site configuration                          |
| `activity_log`   | Admin action tracking                               |

### Important Relationships

```sql
-- Products belong to categories
products.category_id → categories.id

-- Cart/Wishlist items belong to users and products
cart.user_id → users.id
cart.product_id → products.id

-- Orders belong to users, order items belong to orders
orders.user_id → users.id
order_items.order_id → orders.id
order_items.product_id → products.id

-- Reviews link products and users
reviews.product_id → products.id
reviews.user_id → users.id
```

---

## 🎨 Frontend Development

### Page Structure

Each frontend page follows this structure:

```php
<?php
// 1. Include configuration and core files
require_once 'config.php';
require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/Session.php';
require_once INCLUDES_PATH . '/Security.php';
require_once INCLUDES_PATH . '/functions.php';

// 2. Initialize session
Session::start();

// 3. Initialize database connection
$db = Database::getInstance();

// 4. Page-specific logic here
$products = $db->fetchAll("SELECT * FROM products WHERE is_active = 1");

// 5. Include header
include INCLUDES_PATH . '/header.php';
include INCLUDES_PATH . '/navbar.php';
?>

<!-- 6. Page content -->
<main class="container mx-auto px-4 py-8">
    <!-- Your HTML here -->
</main>

<?php
// 7. Include footer
include INCLUDES_PATH . '/footer.php';
?>
```

### Tailwind CSS Usage

The project uses Tailwind CSS via CDN for styling. Key utility classes used:

```html
<!-- Card Component -->
<div
  class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow"
>
  <img src="..." class="w-full h-48 object-cover" />
  <div class="p-4">
    <h3 class="font-semibold text-lg text-gray-800">Product Name</h3>
    <p class="text-emerald-600 font-bold">₦25,000</p>
  </div>
</div>

<!-- Button Styles -->
<button
  class="bg-emerald-600 hover:bg-emerald-700 text-white font-medium py-2 px-4 rounded-lg transition-colors"
>
  Add to Cart
</button>

<!-- Form Input -->
<input
  type="text"
  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
  placeholder="Enter email"
/>
```

### AJAX Cart Operations

Cart updates are handled via AJAX without page reloads:

```javascript
// Add to cart
async function addToCart(productId, quantity = 1) {
  const response = await fetch("ajax/cart.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `action=add&product_id=${productId}&quantity=${quantity}&csrf_token=${csrfToken}`,
  });

  const data = await response.json();

  if (data.success) {
    updateCartBadge(data.cart_count);
    showNotification("Product added to cart!", "success");
  }
}

// Update cart quantity
async function updateQuantity(cartId, quantity) {
  const response = await fetch("ajax/cart.php", {
    method: "POST",
    body: `action=update&cart_id=${cartId}&quantity=${quantity}&csrf_token=${csrfToken}`,
  });

  const data = await response.json();
  if (data.success) {
    updateCartTotals(data.subtotal, data.total);
  }
}
```

---

## ⚙️ Admin Panel Development

### Admin Layout Structure

```php
<?php
// admin/products.php example structure
require_once '../config.php';
require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/Session.php';
require_once INCLUDES_PATH . '/Security.php';

Session::start();

// Admin authentication check
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
        Session::flash('error', 'Invalid request. Please try again.');
        header('Location: products.php');
        exit;
    }

    // Process form...
}

// Fetch data for display
$products = $db->fetchAll("SELECT p.*, c.name as category_name
                           FROM products p
                           LEFT JOIN categories c ON p.category_id = c.id
                           ORDER BY p.created_at DESC");

// Include admin layout
include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/topbar.php';
?>

<!-- Main content area -->
<main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
    <div class="container mx-auto px-6 py-8">
        <!-- Page content -->
    </div>
</main>

<?php include 'includes/footer.php'; ?>
```

### Admin Dashboard Widgets

The dashboard (`admin/index.php`) displays:

1. **Statistics Cards**: Total sales, orders, customers, products
2. **Sales Chart**: Monthly revenue visualization (Chart.js)
3. **Recent Orders**: Latest 5 orders table
4. **Top Products**: Best selling items
5. **Low Stock Alerts**: Products needing restock

```php
// Example: Fetching dashboard statistics
$stats = [
    'total_sales' => $db->fetch("SELECT SUM(total_amount) as total FROM orders WHERE payment_status = 'paid'")['total'] ?? 0,
    'total_orders' => $db->fetch("SELECT COUNT(*) as count FROM orders")['count'],
    'total_customers' => $db->fetch("SELECT COUNT(*) as count FROM users")['count'],
    'total_products' => $db->fetch("SELECT COUNT(*) as count FROM products WHERE is_active = 1")['count'],
];

// Monthly sales for chart
$monthlySales = $db->fetchAll("
    SELECT
        MONTH(created_at) as month,
        SUM(total_amount) as total
    FROM orders
    WHERE payment_status = 'paid'
      AND YEAR(created_at) = YEAR(CURDATE())
    GROUP BY MONTH(created_at)
    ORDER BY month
");
```

---

## 🔒 Security Implementation

### 1. CSRF Protection

All forms include CSRF tokens:

```php
<!-- In form -->
<form method="POST" action="process.php">
    <input type="hidden" name="csrf_token" value="<?= Security::generateCSRFToken() ?>">
    <!-- form fields -->
</form>

<!-- Validation in PHP -->
if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
    die('Invalid CSRF token');
}
```

### 2. SQL Injection Prevention

All queries use PDO prepared statements:

```php
// ✅ SAFE - Parameterized query
$user = $db->fetch(
    "SELECT * FROM users WHERE email = ?",
    [$_POST['email']]
);

// ❌ UNSAFE - Never do this!
$user = $db->fetch(
    "SELECT * FROM users WHERE email = '{$_POST['email']}'"
);
```

### 3. XSS Prevention

All output is escaped:

```php
// In PHP
<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>

// Helper function
<?= e($product['name']) ?>  // Using Security::sanitize() wrapper
```

### 4. Password Security

```php
// Hashing password on registration
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Verifying on login
if (password_verify($inputPassword, $user['password'])) {
    // Login successful
}
```

### 5. Rate Limiting

Login attempts are tracked to prevent brute force:

```php
// Check if account is locked
if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
    $error = 'Account temporarily locked. Try again later.';
}

// Increment failed attempts
$db->query(
    "UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?",
    [$user['id']]
);

// Lock after too many attempts
if ($user['login_attempts'] >= LOGIN_MAX_ATTEMPTS) {
    $db->query(
        "UPDATE users SET locked_until = DATE_ADD(NOW(), INTERVAL ? SECOND) WHERE id = ?",
        [LOGIN_LOCKOUT_TIME, $user['id']]
    );
}
```

---

## 💳 Payment Integration

### Paystack Integration Flow

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  Checkout   │────▶│  payment.   │────▶│   Paystack  │────▶│  callback   │
│   Page      │     │    php      │     │   Gateway   │     │    .php     │
└─────────────┘     └─────────────┘     └─────────────┘     └─────────────┘
                           │                   │                   │
                           │ Initialize        │ Redirect          │ Verify
                           │ Transaction       │ to Paystack       │ Payment
                           ▼                   ▼                   ▼
                    ┌─────────────┐     ┌─────────────┐     ┌─────────────┐
                    │ Create      │     │ Customer    │     │ Update      │
                    │ Order       │     │ Pays        │     │ Order       │
                    └─────────────┘     └─────────────┘     └─────────────┘
```

### Payment Initialization (payment.php)

```php
<?php
// Prepare Paystack payment
$reference = 'SF-' . time() . '-' . bin2hex(random_bytes(4));

$paymentData = [
    'email' => $order['shipping_email'],
    'amount' => $order['total_amount'] * 100, // Paystack uses kobo
    'reference' => $reference,
    'callback_url' => PAYSTACK_CALLBACK_URL,
    'metadata' => [
        'order_id' => $order['id'],
        'order_number' => $order['order_number']
    ]
];

// Initialize via Paystack API
$ch = curl_init('https://api.paystack.co/transaction/initialize');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($paymentData),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json'
    ]
]);

$response = json_decode(curl_exec($ch), true);

if ($response['status']) {
    // Redirect to Paystack checkout
    header('Location: ' . $response['data']['authorization_url']);
    exit;
}
```

### Payment Verification (payment-callback.php)

```php
<?php
$reference = $_GET['reference'] ?? '';

// Verify transaction
$ch = curl_init("https://api.paystack.co/transaction/verify/{$reference}");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY
    ]
]);

$response = json_decode(curl_exec($ch), true);

if ($response['status'] && $response['data']['status'] === 'success') {
    $orderId = $response['data']['metadata']['order_id'];

    // Update order status
    $db->query(
        "UPDATE orders SET payment_status = 'paid', status = 'processing',
                           payment_reference = ? WHERE id = ?",
        [$reference, $orderId]
    );

    // Record payment
    $db->query(
        "INSERT INTO payments (order_id, transaction_reference, amount, status, paid_at)
         VALUES (?, ?, ?, 'success', NOW())",
        [$orderId, $reference, $response['data']['amount'] / 100]
    );

    // Redirect to success page
    header("Location: order-success.php?order=" . $response['data']['metadata']['order_number']);
}
```

---

## 📧 Email System

### PHPMailer Configuration

```php
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendEmail($to, $subject, $body, $altBody = '') {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USERNAME;
        $mail->Password = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port = MAIL_PORT;

        // Recipients
        $mail->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed: " . $mail->ErrorInfo);
        return false;
    }
}
```

### Email Templates

Create branded email templates:

```php
function getEmailTemplate($title, $content) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; }
            .container { max-width: 600px; margin: 0 auto; }
            .header { background: #10b981; color: white; padding: 20px; text-align: center; }
            .content { padding: 30px; background: #f9fafb; }
            .button {
                display: inline-block;
                background: #10b981;
                color: white;
                padding: 12px 24px;
                text-decoration: none;
                border-radius: 6px;
            }
            .footer { padding: 20px; text-align: center; font-size: 12px; color: #6b7280; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>" . SITE_NAME . "</h1>
            </div>
            <div class='content'>
                <h2>{$title}</h2>
                {$content}
            </div>
            <div class='footer'>
                © " . date('Y') . " " . SITE_NAME . ". All rights reserved.
            </div>
        </div>
    </body>
    </html>";
}
```

---

## 🔌 Extending the Platform

### Adding New Admin Pages

1. **Create the PHP file** in `/admin/`:

```php
<?php
// admin/shipping.php
require_once '../config.php';
require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/Session.php';
require_once INCLUDES_PATH . '/Security.php';

Session::start();

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$pageTitle = 'Shipping Zones';

include 'includes/header.php';
include 'includes/sidebar.php';
include 'includes/topbar.php';
?>

<main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100">
    <div class="container mx-auto px-6 py-8">
        <h1 class="text-2xl font-semibold text-gray-800 mb-6">Shipping Zones</h1>

        <!-- Your content here -->

    </div>
</main>

<?php include 'includes/footer.php'; ?>
```

2. **Add to sidebar navigation** in `admin/includes/sidebar.php`:

```php
<a href="shipping.php" class="flex items-center px-4 py-2 text-gray-600 hover:bg-gray-100">
    <svg class="w-5 h-5 mr-3"><!-- icon --></svg>
    Shipping Zones
</a>
```

### Adding API Endpoints

Create RESTful endpoints in `/ajax/` or `/api/`:

```php
<?php
// ajax/products.php
require_once '../config.php';
require_once INCLUDES_PATH . '/Database.php';
require_once INCLUDES_PATH . '/Session.php';
require_once INCLUDES_PATH . '/Security.php';

header('Content-Type: application/json');
Session::start();

$db = Database::getInstance();
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'search':
        $query = Security::sanitize($_GET['q'] ?? '');
        $products = $db->fetchAll(
            "SELECT id, name, price, featured_image
             FROM products
             WHERE is_active = 1 AND name LIKE ?
             LIMIT 10",
            ["%{$query}%"]
        );
        echo json_encode(['success' => true, 'products' => $products]);
        break;

    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $product = $db->fetch("SELECT * FROM products WHERE id = ?", [$id]);
        echo json_encode(['success' => (bool)$product, 'product' => $product]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
```

### Creating Custom Helper Functions

Add to `includes/functions.php`:

```php
/**
 * Get product rating average
 */
function getProductRating($productId) {
    global $db;
    $result = $db->fetch(
        "SELECT AVG(rating) as average, COUNT(*) as count
         FROM reviews
         WHERE product_id = ? AND is_approved = 1",
        [$productId]
    );
    return [
        'average' => round($result['average'] ?? 0, 1),
        'count' => $result['count'] ?? 0
    ];
}

/**
 * Check if product is in stock
 */
function isInStock($product) {
    return $product['stock_quantity'] > 0;
}

/**
 * Get discount percentage
 */
function getDiscountPercentage($price, $salePrice) {
    if (!$salePrice || $salePrice >= $price) return 0;
    return round((($price - $salePrice) / $price) * 100);
}
```

---

## 🧪 Testing Guidelines

### Manual Testing Checklist

#### Customer Flow

- [ ] User registration with email verification
- [ ] Login/logout functionality
- [ ] Password reset flow
- [ ] Browse products and categories
- [ ] Add/remove items from cart
- [ ] Apply coupon codes
- [ ] Complete checkout process
- [ ] View order history

#### Admin Flow

- [ ] Admin login/logout
- [ ] CRUD operations for products
- [ ] CRUD operations for categories
- [ ] Order status updates
- [ ] Customer management
- [ ] Report generation

### Security Testing

```bash
# Test SQL Injection (should not work)
curl -X POST "http://localhost/shopforge/login.php" \
  -d "email=' OR '1'='1&password=test"

# Test XSS (should be escaped)
# Add product with name: <script>alert('XSS')</script>

# Test CSRF (should fail without valid token)
curl -X POST "http://localhost/shopforge/admin/products.php" \
  -d "action=delete&id=1"
```

### Performance Considerations

1. **Database Indexes**: Ensure proper indexes on frequently queried columns
2. **Query Optimization**: Avoid N+1 queries, use JOINs
3. **Image Optimization**: Compress uploaded images
4. **Caching**: Consider adding Redis/Memcached for sessions

---

## 🚀 Deployment Checklist

### Pre-Deployment

- [ ] Update `APP_ENV` to `'production'` in `config.php`
- [ ] Set `APP_DEBUG` to `false`
- [ ] Update database credentials for production
- [ ] Configure production email settings (SMTP)
- [ ] Update Paystack to live keys (remove test keys)
- [ ] Enable HTTPS and update `BASE_URL`
- [ ] Set secure session cookie settings

### Configuration Changes

```php
// config.php - Production settings
define('APP_ENV', 'production');
define('APP_DEBUG', false);

// Use production database
define('DB_HOST', 'production-db-host');
define('DB_NAME', 'shopforge_prod');
define('DB_USER', 'prod_user');
define('DB_PASS', 'strong_password_here');

// Live Paystack keys
define('PAYSTACK_PUBLIC_KEY', 'pk_live_...');
define('PAYSTACK_SECRET_KEY', 'sk_live_...');
define('PAYMENT_MODE', 'production');

// Secure sessions
define('SESSION_SECURE', true);
```

### Server Configuration

```apache
# .htaccess additions for production
<IfModule mod_rewrite.c>
    RewriteEngine On

    # Force HTTPS
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

    # Remove .php extension (optional)
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME}\.php -f
    RewriteRule ^(.*)$ $1.php [L]
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
    Header set Strict-Transport-Security "max-age=31536000"
</IfModule>

# Protect sensitive files
<FilesMatch "^(config\.php|\.htaccess|\.env)$">
    Order allow,deny
    Deny from all
</FilesMatch>
```

### Post-Deployment

- [ ] Test all payment flows in production
- [ ] Verify email delivery
- [ ] Check error logging is working
- [ ] Set up database backups
- [ ] Monitor server resources
- [ ] Test SSL certificate

---

## 🔧 Troubleshooting

### Common Issues

#### 1. Database Connection Failed

```
Error: SQLSTATE[HY000] [1045] Access denied for user
```

**Solution**: Verify database credentials in `config.php`

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'shopforge_db');
define('DB_USER', 'correct_username');
define('DB_PASS', 'correct_password');
```

#### 2. Email Not Sending

**Check**:

1. SMTP credentials are correct
2. Gmail: Enable "Less secure apps" or use App Password
3. Port is correct (465 for SSL, 587 for TLS)
4. Check PHP error log

```php
// Enable PHPMailer debug output
$mail->SMTPDebug = SMTP::DEBUG_SERVER;
```

#### 3. Payment Callback Not Working

**Check**:

1. Callback URL is accessible publicly
2. URL matches Paystack dashboard settings
3. SSL certificate is valid

```php
// Debug: Log callback data
file_put_contents('callback_log.txt', print_r($_REQUEST, true));
```

#### 4. Session Issues

```
Warning: session_start(): Cannot start session when headers already sent
```

**Solution**: Ensure no output before `session_start()`:

```php
<?php
// No whitespace or HTML before this
session_start();
```

#### 5. File Upload Failures

**Check**:

1. `uploads/` directory exists and is writable
2. `upload_max_filesize` in php.ini
3. `post_max_size` in php.ini

```php
// Check upload limits
echo ini_get('upload_max_filesize');
echo ini_get('post_max_size');
```

### Debug Mode

Enable detailed error messages during development:

```php
// config.php
define('APP_ENV', 'development');
define('APP_DEBUG', true);

// This will show all PHP errors
error_reporting(E_ALL);
ini_set('display_errors', '1');
```

### Logging

Check logs for issues:

```bash
# Apache error log (Linux)
tail -f /var/log/apache2/error.log

# XAMPP (Windows)
tail -f C:\xampp\apache\logs\error.log

# Application log
tail -f logs/error.log
```

---

## 📚 Additional Resources

- **Tailwind CSS Documentation**: [tailwindcss.com/docs](https://tailwindcss.com/docs)
- **PHPMailer Documentation**: [github.com/PHPMailer/PHPMailer](https://github.com/PHPMailer/PHPMailer)
- **Paystack API Reference**: [paystack.com/docs/api](https://paystack.com/docs/api)
- **Chart.js Documentation**: [chartjs.org/docs](https://www.chartjs.org/docs)
- **MySQL PDO Reference**: [php.net/manual/en/book.pdo.php](https://www.php.net/manual/en/book.pdo.php)

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/new-feature`
3. Commit changes: `git commit -am 'Add new feature'`
4. Push to branch: `git push origin feature/new-feature`
5. Submit a Pull Request

---

**Document Version**: 1.0  
**Last Updated**: January 14, 2026  
**Author**: Marvelous Ayomide Adegbiji

---

_This document is a living guide and will be updated as the project evolves._
