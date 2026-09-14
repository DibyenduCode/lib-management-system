# SAYAK LIBRARY - Production Library Management System

**SAYAK LIBRARY** is a complete, production-ready Library Management System built using **Core PHP 8.x**, **MySQL / MariaDB**, **HTML5**, **CSS3**, **JavaScript**, **Bootstrap 5**, **AJAX**, and **PDO**.

It supports local development on **XAMPP Windows** (`http://localhost/sayak-library/`) and 100% standard **cPanel Shared Hosting** environments (no SSH, Node.js, Docker, or Composer required).

---

## Key Features & Highlights

1. **Strict 3 User Roles (RBAC)**:
   - **Super Admin**: Full system control (Website info, Books, Members, Librarians, Notices, Donations, Settings, Audit Logs).
   - **Librarian**: Daily operations (Issue/Return books, Collect Cash Fines & Renewals, Manage Physical Barcode Copies, Upload/Download PDFs, View 15+ Days Expired Members with Unreturned Books).
   - **Library Member**: Personal portal (Search catalog, Request physical books, Read PDFs in-browser, Track loans & cash receipts).

2. **Digital PDF Library & Security**:
   - PDF files are stored in `private_pdfs/` protected by `.htaccess` (`Deny from all`).
   - In-browser PDF reader (`pdf_viewer.php`) with canvas rendering.
   - **Backend Enforcement**: Member direct PDF downloads return `HTTP 403 Forbidden`. Only Super Admin & Librarian can download PDFs (`pdf_download.php`).

3. **15-Day Membership Expiry Rule & Restriction**:
   - If a member's subscription is expired for **MORE THAN 15 DAYS** (`current_date > expiry_date + 15 days`), account status shifts to `Restricted`.
   - On Member login: Shows an explicit warning screen (`ACCOUNT ACCESS RESTRICTED`) detailing expiry date, restriction date, outstanding books, and outstanding fines.
   - **Librarian Administrative Access**: Librarians and Super Admins CAN STILL access restricted member accounts to receive returned books and collect cash fines.

4. **Cash Payment Receipts**:
   - All membership renewals and fine collections are recorded as cash transactions with auto-generated transaction IDs (`SL-TXN-000001`, `SL-RCP-000001`) and printable receipts.

5. **Strict Notice Board Access**:
   - Only Super Admin can create, edit, publish, unpublish, set expiry, and delete notices. Librarians are strictly forbidden on the backend.

---

## 1. Local XAMPP Installation Guide (Windows)

### Prerequisites:
- XAMPP with PHP 8.x and MySQL / MariaDB running.

### Step 1: File Location & Junction
Copy the project folder to:
```text
C:\xampp\htdocs\sayak-library\
```
*(Or create a Directory Junction from `C:\xampp\htdocs\sayak-library` pointing to your working codebase directory).*

Expected URL:
```text
http://localhost/sayak-library/
```

### Step 2: Database Setup
1. Open phpMyAdmin: `http://localhost/phpmyadmin/`
2. Create a new database named: `sayak_library`
3. Click **Import** and upload `database.sql` located in the root directory.
4. Alternatively, import via MySQL CLI:
   ```bash
   C:\xampp\mysql\bin\mysql.exe -u root -e "SOURCE C:/xampp/htdocs/sayak-library/database.sql;"
   ```

### Step 3: Default Credentials (Local)
- **Host**: `127.0.0.1` / `localhost`
- **Database**: `sayak_library`
- **Username**: `root`
- **Password**: *(empty)*

---

## 2. Initial Super Administrator Account
 
| Role | Email Address | Password | Description |
| :--- | :--- | :--- | :--- |
| **Super Admin** | `todkkhaskel@gmail.com` | `Dibyendu@@123` | Full system administrator |

---

## 3. cPanel Shared Hosting Deployment Guide

### Step 1: Upload Files
1. Compress project root files into a `.zip` archive.
2. Log in to cPanel -> **File Manager**.
3. Upload and extract to `public_html/sayak-library/` (or `public_html/`).

### Step 2: Create cPanel MySQL Database
1. Go to cPanel -> **MySQL Database Wizard**.
2. Create database: `username_sayak_library`.
3. Create database user: `username_sayak_user` with a strong password.
4. Assign **ALL PRIVILEGES** to the user for the created database.
5. Go to phpMyAdmin in cPanel and import `database.sql`.

### Step 3: Update `includes/config.php`
Edit `includes/config.php` with your cPanel database details:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'username_sayak_library');
define('DB_USER', 'username_sayak_user');
define('DB_PASS', 'YourStrongPassword123!');
```

### Step 4: File Permissions
Ensure the following directory permissions are set in cPanel File Manager:
- Folders: `0755`
- PHP / Web Files: `0644`
- `private_pdfs/` folder: `0755` (protected by `.htaccess`)
- `uploads/covers/`, `uploads/gallery/`, `uploads/members/`: `0755` (Writable)

---

## 4. cPanel Cron Setup Instructions

To automate daily fine calculations, membership expiry updates, and 15-day restriction flagging:

1. Log in to cPanel -> **Cron Jobs**.
2. Set Common Settings: **Once Per Day (0 0 * * *)**
3. Enter Command:
   ```bash
   php /home/yourcpanelusername/public_html/sayak-library/cron/daily_cron.php >/dev/null 2>&1
   ```

---

## 5. SMTP Email Setup Instructions

1. Log in as **Super Admin** -> Go to **System Settings**.
2. Scroll to **SMTP Email Server Settings**.
3. Enter your SMTP details:
   - **SMTP Host**: e.g., `mail.sayaklibrary.org`
   - **SMTP Port**: `587` (or `465` for SSL)
   - **SMTP Username**: `no-reply@sayaklibrary.org`
   - **SMTP Password**: Your email password
   - **From Name**: `Sayak Library Administration`
   - **From Email**: `no-reply@sayaklibrary.org`
4. Save settings.

---

## Project Structure Overview

```text
sayak-library/
│
├── index.php                 # Dynamic Homepage
├── about.php                 # About Us Overview
├── our-journey.php           # Our Journey Page
├── governing-body.php        # Governing Body Page
├── governance.php            # Governance & Policy Page
├── academic-collaborations.php # Academic Partnerships
├── usership.php              # Public Member Registration Form
├── rules.php                 # Rules & Regulations
├── collections.php           # Master Book Search & Filter Catalog
├── general-collection.php    # Shortcut redirect to Category 1
├── fiction-literature.php    # Shortcut redirect to Category 2
├── higher-secondary.php      # Shortcut redirect to Category 3
├── competitive-exams.php     # Shortcut redirect to Category 4
├── book-detail.php           # Book Metadata & Request/Read Actions
├── contact.php               # Contact Form & Location Map
├── gallery.php               # Photo Gallery
├── donate.php                # Public Donation Form
├── news.php                  # News & Bulletins Listing
├── news-detail.php           # Notice Details Page
├── login.php                 # Tabbed Role Login Page
├── logout.php                # Logout Session Handler
├── pdf_stream.php            # Secure PDF Inline Stream Endpoint
├── pdf_download.php          # Secure PDF Download Endpoint (Admin/Librarian Only)
├── pdf_viewer.php            # In-Browser PDF.js Reader
│
├── admin/                    # SUPER ADMIN PORTAL
│   ├── dashboard.php
│   ├── books/
│   ├── notices/
│   ├── donations/
│   ├── memberships/
│   ├── gallery/
│   ├── settings/
│   └── audit/
│
├── librarian/                # LIBRARIAN OPERATIONAL PORTAL
│   ├── dashboard.php
│   ├── requests/
│   ├── issue/
│   ├── return/
│   ├── fines/
│   ├── memberships/
│   ├── members/
│   ├── copies/
│   ├── pdf/
│   ├── reports/
│   └── receipt.php           # Printable Cash Receipt Generator
│
├── member/                   # MEMBER PERSONAL PORTAL
│   ├── dashboard.php         # Member Overview & 15-Day Restriction Warning
│   ├── my-books.php
│   ├── requests.php
│   ├── membership.php
│   ├── notifications.php
│   └── profile.php
│
├── includes/                 # CORE APPLICATION LAYER
│   ├── config.php            # Environment & URL Configuration
│   ├── database.php          # PDO Database Singleton
│   ├── auth.php              # Session & 15-Day Rule Logic
│   ├── permissions.php       # RBAC Authorization Helpers
│   ├── functions.php          # Helper Functions & ID Generators
│   ├── notifications.php    # Dashboard Notifications
│   ├── email.php            # SMTP & Mail Helper
│   ├── audit.php            # Audit Logging Engine
│   ├── header.php           # Public Reusable Header
│   └── footer.php           # Public Reusable Footer
│
├── assets/
│   └── css/style.css        # Custom Responsive Institutional Theme
│
├── uploads/                  # PUBLIC MEDIA STORAGE
│   ├── covers/
│   ├── gallery/
│   └── members/
│
├── private_pdfs/             # PROTECTED PDF STORAGE (.htaccess Deny All)
├── cron/                     # SCHEDULED BACKGROUND JOBS
│   └── daily_cron.php
├── errors/                   # CUSTOM ERROR PAGES (404, 403, 500)
├── database.sql              # Complete Schema & Seed Data
└── README.md                 # Technical Documentation
```
