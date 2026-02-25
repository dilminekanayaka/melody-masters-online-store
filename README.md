# Melody Masters Online Store

Melody Masters is a modern, responsive e-commerce web application designed specifically for a professional musical instrument store. Built with PHP and MySQL, it offers a seamless shopping experience for musicians, from browsing high-quality instruments to secure checkout and digital downloads.

---

## Key Features

### Customer Experience
- **Interactive Product Catalog**: Browse instruments by category (Guitars, Drums, Keyboards, etc.) with advanced filtering.
- **Responsive Design**: Fully optimized for mobile, tablet, and desktop viewing.
- **Smart Shopping Cart**: Real-time cart updates with automatic shipping calculation.
- **Dynamic Shipping**: Free shipping on orders over £100, encouraging higher cart values.
- **Dual Product Types**: Support for both Physical instruments and Digital products (music sheets, software).
- **Secure Checkout**: Streamlined multi-step checkout with address validation and order tracking.
- **User Dashboard**: Manage profiles, view order history, and access digital downloads.

### 🛡️ Admin & Management
- **Centralized Dashboard**: Real-time sales KPIs, order volume charts, and low-stock alerts.
- **Inventory Management**: Full CRUD (Create, Read, Update, Delete) for products and categories.
- **Role-Based Access Control (RBAC)**:
  - **Superadmin**: Full system access, including user and staff management.
  - **Admin**: Full inventory and order management.
  - **Staff**: Order fulfillment and stock monitoring (restricted access).
- **Activity Monitoring**: Live tracking of new orders and customer registrations.

---

## Technology Stack

- **Backend**: PHP 8.x
- **Database**: MySQL / MariaDB
- **Frontend**: HTML5, CSS3 (Vanilla), JavaScript (ES6)
- **Server**: Apache (XAMPP / WAMP / MAMP)
- **Security**: Password Hashing (Bcrypt), SQL Injection protection (Prepared Statements), Session hijacking prevention.

---

## Quick Start Guide

Follow these steps to get the project running locally on your machine using XAMPP:

### 1. Prerequisites
- [XAMPP](https://www.apachefriends.org/index.html) installed.
- Git (optional).

### 2. Installation
1. Clone or download the repository into your XAMPP `htdocs` directory:
   ```bash
   cd c:\xampp\htdocs
   git clone https://github.com/dilminekanayaka/melody-masters-online-store
   ```
2. Open your browser and navigate to `http://localhost/phpmyadmin`.
3. Create a new database named `melody_masters_db`.
4. Import the consolidated SQL file:
   - Go to the **Import** tab in phpMyAdmin.
   - Choose the file: `/database/database.sql`.
   - Click **Go** to create the tables and populate seed data.

### 3. Configuration
The database connection settings are located in `includes/db.php`:
```php
$host     = "localhost";
$user     = "root";
$password = ""; 
$database = "melody_masters_db";
```

### 4. Running the App
Open your browser and go to:
[http://localhost/melody-masters-online-store](http://localhost/melody-masters-online-store)

---

## 📁 Project Structure

```text
├── admin/               # Admin dashboard and management pages
├── assets/
│   ├── css/            # Main styling and admin-specific styles
│   ├── img/            # Static images and category icons
│   └── js/             # Frontend logic and charts
├── customer/            # Customer-specific account and order pages
├── database/            # SQL schema and seed data (database.sql)
├── includes/            # Core logic, DB connection, and shared components
├── uploads/             # Dynamically uploaded product images/files
├── cart.php             # Shopping cart logic
├── checkout.php         # Secure checkout process
└── index.php            # Homepage
```

---

## 🔒 Security
- **Data Protection**: All database queries use Prepared Statements to prevent SQL Injection.
- **Authentication**: Secure password hashing using `password_hash()` and `password_verify()`.
- **System Hardening**: Sensitive directories like `includes/` are protected via role-based PHP checks and `.htaccess`.

---

## 📄 License
This project is developed for academic purposes. Detailed licensing information can be found in the [LICENSE](LICENSE) file.

