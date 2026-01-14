# Product Requirements Document (PRD)

## ShopForge - E-Commerce Platform

---

### Document Information

| Field              | Value                                                                                                                                                                                 |
| ------------------ | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Project Name**   | ShopForge                                                                                                                                                                             |
| **Version**        | 1.0                                                                                                                                                                                   |
| **Date Created**   | January 14, 2026                                                                                                                                                                      |
| **Core Stack**     | PHP, MySQL, Tailwind CSS                                                                                                                                                              |
| **Optional Stack** | Paystack API, PHPMailer, TCPDF, jQuery, Chart.js, CKEditor, Font Awesome, DataTables, Moment.js _(All technologies except PHP, MySQL, and Tailwind are optional and interchangeable)_ |
| **Author**         | Marvelous Ayomide Adegbiji                                                                                                                                                            |

---

## 1. Executive Summary

**ShopForge** is a full-featured e-commerce platform built with PHP and MySQL, featuring a customer-facing storefront and a comprehensive admin dashboard. The system supports product categorization, shopping cart functionality, payment gateway integration, user authentication with email verification, and robust admin tools for managing products, categories, users, and sales.

---

## 2. System Architecture

### 2.1 Frontend (Customer-Facing)

- **Framework**: Tailwind CSS (Required)
- **Styling**: Custom CSS with responsive design
- **JavaScript**: Any JS library for AJAX interactions (jQuery recommended)
- **Layout**: Top navigation with sidebar for categories

### 2.2 Backend (Server-Side)

- **Language**: PHP 7.x+ (Required)
- **Database**: MySQL/MariaDB with PDO (Required)
- **Email**: Any email library with SMTP support (PHPMailer recommended)
- **PDF Generation**: Any PDF library for reports (TCPDF recommended)

### 2.3 Third-Party Integrations

- **Payment Gateway**: Any payment gateway (Paystack, Stripe, Flutterwave, etc.)
- **Email Service**: SMTP (configurable)
- **Social Features**: Facebook Comments Plugin or any comment system
- **Security**: reCAPTCHA or any CAPTCHA solution (optional)

> **Note**: All technologies, frameworks, and dependencies except **PHP**, **MySQL**, and **Tailwind CSS** are optional. You are free to add, replace, or remove any technology to make the system work better for your use case.

---

## 3. Feature List

### 3.1 Customer Features

#### 3.1.1 User Authentication

| Feature               | Description                                  |
| --------------------- | -------------------------------------------- |
| User Registration     | Email, password, first name, last name       |
| Email Verification    | Activation link sent via email               |
| User Login            | Email and password authentication            |
| Password Reset        | Reset link sent via email                    |
| Session Management    | Secure session handling                      |
| reCAPTCHA Protection  | Spam prevention on signup (optional)         |
| Social Login          | OAuth with Google, Facebook, etc. (optional) |
| Two-Factor Auth (2FA) | Enhanced account security (optional)         |
| Remember Me           | Persistent login sessions                    |

#### 3.1.2 Product Browsing

| Feature               | Description                                   |
| --------------------- | --------------------------------------------- |
| Homepage              | Image carousel + Featured products            |
| Category Navigation   | Sidebar with all categories                   |
| Category Filtering    | View products by category                     |
| Product Search        | Search by product name with highlighting      |
| Advanced Search       | Filter by price range, category, rating, etc. |
| Product Details       | Full product page with description            |
| Product Views Counter | Daily view count tracking                     |
| Image Zoom            | Magnify product images                        |
| Related Products      | Show similar items on product page            |
| Recently Viewed       | Track and display user's browsing history     |
| Product Comparison    | Compare multiple products side-by-side        |

#### 3.1.3 Shopping Cart

| Feature             | Description                          |
| ------------------- | ------------------------------------ |
| Add to Cart         | Add products with quantity selection |
| View Cart           | Display all cart items               |
| Update Quantity     | Increase/decrease item quantity      |
| Remove Item         | Delete items from cart               |
| Cart Persistence    | Cart tied to user account            |
| Cart Badge          | Navbar cart item counter             |
| Save for Later      | Move items to wishlist from cart     |
| Apply Coupon/Promo  | Discount code functionality          |
| Shipping Calculator | Estimate shipping costs              |

#### 3.1.4 Wishlist

| Feature         | Description                              |
| --------------- | ---------------------------------------- |
| Add to Wishlist | Save products for later                  |
| View Wishlist   | Display all saved items                  |
| Move to Cart    | Transfer wishlist items to shopping cart |
| Share Wishlist  | Share wishlist via link or social media  |

#### 3.1.5 Checkout & Payment

| Feature                  | Description                                       |
| ------------------------ | ------------------------------------------------- |
| Payment Integration      | Payment gateway checkout (Paystack, Stripe, etc.) |
| Sandbox Mode             | Testing environment for development               |
| Production Mode          | Live payments (configurable)                      |
| Order Recording          | Save transaction to database                      |
| Payment ID Tracking      | Store transaction reference                       |
| Multiple Payment Options | Card, bank transfer, mobile money, etc.           |
| Order Confirmation       | Email receipt upon successful payment             |
| Invoice Generation       | Downloadable PDF invoice                          |

#### 3.1.6 Order Management

| Feature               | Description                                                 |
| --------------------- | ----------------------------------------------------------- |
| Order Status Tracking | View order status (Pending, Processing, Shipped, Delivered) |
| Order History         | View all past orders                                        |
| Order Details         | View items, totals, and shipping info                       |
| Order Cancellation    | Cancel orders before processing                             |
| Reorder               | Quickly reorder previous purchases                          |
| Delivery Tracking     | Real-time shipment tracking integration                     |

#### 3.1.7 User Profile

| Feature          | Description                      |
| ---------------- | -------------------------------- |
| View Profile     | Display user information         |
| Edit Profile     | Update personal information      |
| Profile Photo    | Upload/change profile picture    |
| Address Book     | Save multiple shipping addresses |
| Change Password  | Update account password          |
| Account Deletion | GDPR-compliant account removal   |

#### 3.1.8 Reviews & Ratings

| Feature           | Description                             |
| ----------------- | --------------------------------------- |
| Product Reviews   | Write and submit product reviews        |
| Star Ratings      | 1-5 star rating system                  |
| Review Photos     | Upload images with reviews              |
| Helpful Votes     | Mark reviews as helpful                 |
| Verified Purchase | Badge for reviews from confirmed buyers |

#### 3.1.9 Notifications

| Feature             | Description                                  |
| ------------------- | -------------------------------------------- |
| Email Notifications | Order updates, shipping alerts, etc.         |
| Newsletter          | Subscribe to marketing emails                |
| Stock Alerts        | Notify when out-of-stock items are available |
| Price Drop Alerts   | Notify on wishlist item price changes        |

---

### 3.2 Admin Features

#### 3.2.1 Dashboard

| Feature              | Description                      |
| -------------------- | -------------------------------- |
| Total Sales Overview | Display total revenue            |
| Product Count        | Number of products in system     |
| User Count           | Total registered users           |
| Today's Sales        | Revenue generated today          |
| Monthly Sales Chart  | Bar chart with yearly comparison |
| Year Selection       | Filter chart by year             |
| Real-time Analytics  | Live visitor and sales tracking  |
| Top Selling Products | Best performers dashboard widget |
| Low Stock Alerts     | Products that need restocking    |

#### 3.2.2 Product Management

| Feature               | Description                             |
| --------------------- | --------------------------------------- |
| Product List          | View all products with filters          |
| Add Product           | Create new products                     |
| Edit Product          | Modify product details                  |
| Delete Product        | Remove products                         |
| Product Photos        | Upload multiple product images          |
| Rich Text Description | WYSIWYG editor for product descriptions |
| Category Assignment   | Assign products to categories           |
| Daily View Counter    | Track product popularity                |
| Bulk Actions          | Edit/delete multiple products at once   |
| Import/Export         | CSV import and export functionality     |
| Product Variants      | Size, color, and other variations       |
| SEO Settings          | Meta titles, descriptions, and slugs    |

#### 3.2.3 Inventory Management

| Feature             | Description                                |
| ------------------- | ------------------------------------------ |
| Stock Tracking      | Track stock quantities per product         |
| Low Stock Alerts    | Notifications when stock is low            |
| Out of Stock Status | Automatic status update when stock is zero |
| Stock History       | Track stock changes over time              |
| Bulk Stock Update   | Update multiple product quantities at once |
| SKU Management      | Unique identifiers for inventory           |

#### 3.2.4 Category Management

| Feature          | Description                |
| ---------------- | -------------------------- |
| Category List    | View all categories        |
| Add Category     | Create new categories      |
| Edit Category    | Modify category details    |
| Delete Category  | Remove categories          |
| URL Slugs        | SEO-friendly category URLs |
| Category Images  | Upload category thumbnails |
| Subcategories    | Nested category hierarchy  |
| Category Sorting | Drag-and-drop ordering     |

#### 3.2.5 User Management

| Feature            | Description                                       |
| ------------------ | ------------------------------------------------- |
| User List          | View all registered users                         |
| Add User           | Create new user accounts                          |
| Edit User          | Modify user information                           |
| Delete User        | Remove user accounts                              |
| User Photo         | Upload/change user photos                         |
| Account Activation | Manually activate accounts                        |
| View Cart          | View user's shopping cart                         |
| User Roles         | Role-based access control (Admin, Manager, Staff) |
| User Activity Log  | Track user actions and login history              |
| Email Users        | Send bulk or individual emails to users           |

#### 3.2.6 Order Management

| Feature             | Description                                     |
| ------------------- | ----------------------------------------------- |
| Order List          | View all orders                                 |
| Order Details       | View items and customer info                    |
| Update Order Status | Change order status (Processing, Shipped, etc.) |
| Order Notes         | Add internal notes to orders                    |
| Refund Processing   | Handle returns and refunds                      |
| Shipping Labels     | Generate and print shipping labels              |
| Order Filtering     | Filter by status, date, customer                |

#### 3.2.7 Sales & Reports

| Feature             | Description                             |
| ------------------- | --------------------------------------- |
| Sales History       | View all transactions                   |
| Transaction Details | View items in each order                |
| Date Range Filter   | Filter sales by date                    |
| Print Report        | Generate PDF sales report               |
| Export Reports      | Download reports as CSV/Excel           |
| Revenue Analytics   | Detailed revenue breakdown              |
| Customer Analytics  | Customer acquisition and retention data |
| Product Performance | Best and worst selling products         |

#### 3.2.8 Marketing & Promotions

| Feature           | Description                               |
| ----------------- | ----------------------------------------- |
| Coupon Management | Create and manage discount codes          |
| Flash Sales       | Time-limited promotions                   |
| Banner Management | Homepage carousel and promotional banners |
| Email Campaigns   | Marketing email integration               |
| Affiliate Program | Partner referral system                   |

#### 3.2.9 Settings & Configuration

| Feature           | Description                       |
| ----------------- | --------------------------------- |
| Store Settings    | Business name, logo, contact info |
| Payment Settings  | Configure payment gateways        |
| Shipping Settings | Shipping zones and rates          |
| Email Templates   | Customize transactional emails    |
| Tax Settings      | Tax rates and rules               |
| Currency Settings | Multi-currency support            |

---

## 4. Configuration Requirements

### 4.1 Database Configuration (`includes/conn.php`)

```php
private $server = "mysql:host=localhost;dbname=YOUR_DATABASE";
private $username = "YOUR_USERNAME";
private $password = "YOUR_PASSWORD";
```

### 4.2 Email Configuration

```php
$mail->Host = 'YOUR_SMTP_HOST';           // e.g., smtp.gmail.com
$mail->Username = 'YOUR_EMAIL';            // e.g., your@email.com
$mail->Password = 'YOUR_APP_PASSWORD';     // SMTP password
$mail->SMTPSecure = 'ssl';                 // ssl or tls
$mail->Port = 465;                         // 465 for SSL, 587 for TLS
```

### 4.3 Payment Gateway Configuration

Configure your chosen payment gateway with appropriate API keys and webhook URLs.

### 4.4 URL Configuration

Update base URLs for activation links, password reset, and asset paths as needed.

### 4.5 Optional: reCAPTCHA Configuration

Configure site key and secret key for CAPTCHA protection on forms.

---

## 5. Security Features

| Feature                  | Implementation               |
| ------------------------ | ---------------------------- |
| Password Hashing         | bcrypt via `password_hash()` |
| SQL Injection Prevention | PDO Prepared Statements      |
| XSS Prevention           | Output escaping/sanitization |
| CSRF Protection          | Token-based form validation  |
| Session Management       | Secure PHP Sessions          |
| SMTP Security            | SSL/TLS encryption           |
| Admin Access Control     | Role-based permissions       |
| Email Verification       | Random activation codes      |
| Rate Limiting            | Prevent brute force attacks  |
| Input Validation         | Server-side data validation  |

---

## 6. File Structure

> **⚠️ Important**: This file structure is a **suggested guideline** and is **not strict**. You are free to adjust, reorganize, add, or remove files and directories as needed during development. The structure may evolve based on project requirements and personal preference.

```
shopforge/
├── admin/                     # Admin panel
│   ├── includes/              # Admin includes (header, navbar, etc.)
│   ├── home.php               # Dashboard
│   ├── products.php           # Product management
│   ├── inventory.php          # Inventory management
│   ├── category.php           # Category management
│   ├── users.php              # User management
│   ├── orders.php             # Order management
│   ├── sales.php              # Sales history
│   ├── reports.php            # Analytics and reports
│   ├── coupons.php            # Coupon management
│   ├── settings.php           # System settings
│   └── sales_print.php        # PDF report generation
├── includes/                  # Frontend includes
│   ├── conn.php               # Database connection
│   ├── session.php            # Session handling
│   ├── functions.php          # Helper functions
│   ├── header.php             # HTML head
│   ├── navbar.php             # Navigation bar
│   ├── sidebar.php            # Category sidebar
│   └── footer.php             # Footer
├── assets/                    # Static assets
│   ├── css/                   # Stylesheets
│   ├── js/                    # JavaScript files
│   ├── images/                # Site images
│   └── fonts/                 # Custom fonts
├── uploads/                   # User-uploaded content
│   ├── products/              # Product images
│   ├── users/                 # User profile photos
│   └── temp/                  # Temporary uploads
├── db/
│   └── shopforge.sql          # Database schema
├── vendor/                    # Third-party libraries
├── api/                       # API endpoints (optional)
├── index.php                  # Homepage
├── login.php                  # User login
├── signup.php                 # Registration form
├── register.php               # Registration processing
├── profile.php                # User profile
├── product.php                # Product details
├── category.php               # Category products
├── search.php                 # Search results
├── cart.php                   # Shopping cart
├── checkout.php               # Checkout page
├── wishlist.php               # Wishlist page
├── orders.php                 # Order history
├── reset.php                  # Password reset
├── config.php                 # Configuration file
└── logout.php                 # Logout
```

> **Note**: Feel free to add additional directories such as `helpers/`, `middleware/`, `templates/`, `hooks/`, or any other structure that fits your development workflow.

---

## 7. Default Credentials

### Admin Account

- **Email**: admin@admin.com
- **Password**: _(Set during installation or check with system administrator)_
- **Access**: Full admin panel access

---

## 8. Dependencies

> **Note**: All dependencies except **PHP**, **MySQL**, and **Tailwind CSS** are optional. You can substitute, add, or remove any dependency to better suit your project needs.

| Dependency    | Purpose               | Required |
| ------------- | --------------------- | -------- |
| PHP           | Server-side scripting | ✅ Yes   |
| MySQL/MariaDB | Database              | ✅ Yes   |
| Tailwind CSS  | CSS framework         | ✅ Yes   |
| PHPMailer     | Email sending         | ❌ No    |
| TCPDF         | PDF generation        | ❌ No    |
| jQuery        | JavaScript library    | ❌ No    |
| Chart.js      | Dashboard charts      | ❌ No    |
| CKEditor      | Rich text editor      | ❌ No    |
| Font Awesome  | Icons                 | ❌ No    |
| DataTables    | Table enhancement     | ❌ No    |
| Moment.js     | Date handling         | ❌ No    |
| Swiper.js     | Image carousels       | ❌ No    |
| Alpine.js     | Lightweight JS        | ❌ No    |

---

## 9. Installation Steps

1. **Clone/Download Repository**

   ```bash
   git clone https://github.com/yourusername/shopforge.git
   ```

2. **Import Database**

   ```bash
   mysql -u root -p < db/shopforge.sql
   ```

3. **Configure Database Connection**

   - Edit `includes/conn.php`
   - Update database name, username, password

4. **Configure Email Settings**

   - Edit email configuration file
   - Update SMTP credentials

5. **Configure Payment Gateway**

   - Add your payment gateway API credentials
   - Set up webhook URLs

6. **Set File Permissions**

   - Ensure `uploads/` directory is writable
   - Chmod 755 for directories, 644 for files

7. **Run Application**

   - Access via your web server
   - Login with default admin credentials
   - Change password immediately

---

## 10. Support & Contact

**Developer:**

Marvelous Ayomide Adegbiji

- **Portfolio**: https://marvelbyte.vercel.app/
- **Bio**: https://bio.link/meetmarvelous
- **LinkedIn**: https://www.linkedin.com/in/meetmarvelous
- **GitHub**: https://github.com/meetmarvelous
- **Facebook**: https://www.facebook.com/meetmarvelous

---

_This document is a living document and will be updated as the project evolves._
