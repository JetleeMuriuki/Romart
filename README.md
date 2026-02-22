#  Romart - Food Ordering System

A complete web-based food ordering and management system built with PHP, MySQL, and Bootstrap. This system allows customers to browse menu items, place orders, and track their delivery status, while administrators can manage the entire operation from a comprehensive admin panel.


## 📋 Table of Contents

- [Features](#features)
- [Demo Screenshots](#demo-screenshots)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [Technologies Used](#technologies-used)
- [Contributing](#contributing)
- [License](#license)

## Key Features

### Customer Features
-  **Modern Homepage** with featured food items
-  **Complete Menu** with advanced filtering (search, category, sort)
-  **Shopping Cart** with quantity management
-  **Secure Checkout** with multiple payment options (Cash, M-Pesa, Card)
-  **Order Tracking** with real-time status updates
-  **User Profile Management** with order history
-  **Fully Responsive** design for mobile and desktop
-  **Secure Authentication** (registration & login)

### Admin Features
-  **Dashboard** with business statistics
-  **Food Management** (Add, Edit, Delete menu items)
-  **Image Upload** for food items (local file upload)
-  **Category Management** 
-  **Order Management** with status updates
-  **Customer Overview**
-  **Search & Filter** capabilities
-  **Mobile-Friendly** admin panel

### Technical Features
-  **Secure** - PDO prepared statements, password hashing
-  **Modern UI** - Bootstrap 5 with custom styling
-  **Fast** - Optimized database queries
-  **Responsive** - Works on all devices
-  **User-Friendly** - Intuitive navigation and clean design
-  **Real-time Updates** - AJAX cart updates
-  **Session Management** - Secure user sessions

##  Demo Screenshots
I will add later i am still adding photos(lol)

##  Prerequisites

Before you begin, ensure you have the following installed:

- **XAMPP** (or similar LAMP/WAMP stack)
  - PHP 7.4 or higher
  - MySQL 5.7 or higher
  - Apache Web Server
- **Web Browser** (Chrome, Firefox, Safari, Edge)
- **Internet Connection:** Required for CDN resources (Bootstrap, Font Awesome)

##  Installation

### Step 1: Clone the Repository

```bash
git clone https://github.com/JetleeMuriuki/Romart.git
cd Romart
```

Or download the ZIP file and extract it.

### Step 2: Move to XAMPP Directory

Move the project folder to your XAMPP `htdocs` directory:

### Step 3: Start XAMPP

1. Open **XAMPP Control Panel**
2. Start **Apache** server
3. Start **MySQL** database

### Step 4: Create Database

1. Open your browser and go to: `http://localhost/phpmyadmin`
2. Click **"New"** to create a new database
3. Database name: `your_db_name`
4. Collation: `utf8_general_ci`
5. Click **"Create"**

### Step 5: Import Database Schema

1. Select the `your_db_name` database
2. Click **"Import"** tab
3. Click **"Choose File"** and select `database_schema.sql` from the project root
4. Click **"Go"** to import

**Or run the SQL manually:**
- Open the `database_schema.sql` file
- Copy all SQL code
- Paste into the SQL tab in phpMyAdmin
- Click **"Go"**

### Step 6: Configure Database Connection

Open `include/db_connect.php` and verify the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Empty for XAMPP default
define('DB_NAME', 'food_ordering_db');
```

### Step 7: Create Uploads Folder

Create the following directory structure for image uploads:

```
Romart/
└── uploads/
    └── food_images/
```
### Step 8: Access the Application

Open your web browser and navigate to:

```
http://localhost/Romart/
```

##  Default Admin Credentials

**Admin Login:**
- **Email:** `admin@romartprime.com`
- **Password:** `admin123`

** Important:** Change the admin password immediately after first login!

**Admin Panel URL:**
```
http://localhost/Romart/admin/admin.php
```

##  Configuration

### PHP Configuration (Optional)

For larger image uploads, modify `php.ini`:

```ini
upload_max_filesize = 10M
post_max_size = 10M
max_file_uploads = 20
```

Restart Apache after changes.

### Database Configuration

All database settings are in `include/db_connect.php`:

```php
define('DB_HOST', 'localhost');     // Database host
define('DB_USER', 'root');           // Database username
define('DB_PASS', '');               // Database password
define('DB_NAME', 'food_ordering_db'); // Database name
```

### Email Configuration (Optional - Future Enhancement)

Currently, email notifications are not implemented. To add email functionality:
1. Configure SMTP settings in a new `config.php` file
2. Use PHPMailer library
3. Implement email sending for order confirmations

##  Usage

### For Customers

1. **Browse Menu**
   - Visit homepage to see featured items
   - Go to "Menu" page for full catalog
   - Use search and filters to find items

2. **Place an Order**
   - Click "Add to Cart" on desired items
   - View cart by clicking cart icon
   - Adjust quantities or remove items
   - Click "Proceed to Checkout"
   - Fill in delivery information
   - Select payment method
   - Click "Place Order"

3. **Track Orders**
   - Go to "My Orders" page
   - Click "View Details" on any order
   - Check current order status

4. **Manage Profile**
   - Go to "Profile" page
   - Update personal information
   - Change password

### For Administrators

1. **Login to Admin Panel**
   ```
   http://localhost/Romart/admin/admin.php
   ```

2. **Manage Food Items**
   - Navigate to "Manage Food Items"
   - Click "Add New Item" to add menu items
   - Upload images directly from computer
   - Edit or delete existing items
   - Toggle availability status

3. **Manage Categories**
   - Navigate to "Categories"
   - Create new food categories
   - Edit or delete categories
   - Toggle active/inactive status

4. **Process Orders**
   - Navigate to "Manage Orders"
   - View all customer orders
   - Update order status (Pending → Confirmed → Preparing → Out for Delivery → Delivered)
   - View detailed order information
   - Filter orders by status
   - Search orders by customer or order number

##  Technologies Used

### Backend
- **PHP 7.4+** - Server-side scripting
- **MySQL** - Database management
- **PDO** - Database abstraction layer
- **Sessions** - User authentication

### Frontend
- **HTML5** - Markup
- **CSS3** - Styling
- **JavaScript (ES6+)** - Client-side functionality
- **Bootstrap 5.3** - Responsive framework
- **Font Awesome 6.0** - Icons
- **AJAX** - Asynchronous requests

### Development Tools
- **XAMPP** - Local development environment
- **phpMyAdmin** - Database management
- **Git** - Version control

##  Security Features

-  **Password Hashing** - Bcrypt encryption
-  **SQL Injection Prevention** - PDO prepared statements
-  **XSS Protection** - HTML entity encoding
-  **Session Management** - Secure session handling
-  **Role-Based Access Control** - Admin vs User roles
-  **Input Validation** - Server-side validation
-  **File Upload Validation** - Type and size restrictions

##  Future Enhancements

- [ ] Email notifications for orders
- [ ] SMS notifications via Twilio
- [ ] Real-time order tracking with maps
- [ ] M-Pesa payment integration
- [ ] Credit card payment gateway
- [ ] Customer reviews and ratings
- [ ] Loyalty points system
- [ ] Push notifications
- [ ] Advanced analytics dashboard
- [ ] Dark mode option
- [ ] PWA (Progressive Web App) support

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

