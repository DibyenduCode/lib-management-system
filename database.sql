-- ============================================================
-- SAYAK LIBRARY - DATABASE SCHEMA & SAMPLE SEED DATA
-- Target DBMS: MySQL 8.x / MariaDB
-- Database Name: sayak_library
-- ============================================================

-- (Optional for local CLI) If creating database outside cPanel, uncomment the next 2 lines:
-- CREATE DATABASE IF NOT EXISTS `sayak_library` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `sayak_library`;

SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- Table: roles
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `role_name` VARCHAR(50) NOT NULL UNIQUE,
  `role_code` VARCHAR(20) NOT NULL UNIQUE,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `role_name`, `role_code`, `description`) VALUES
(1, 'Super Admin', 'SUPER_ADMIN', 'Highest-level administrator with full access'),
(2, 'Librarian', 'LIBRARIAN', 'Library staff managing daily operations'),
(3, 'Library Member', 'MEMBER', 'Registered library user');

-- ------------------------------------------------------------
-- Table: system_settings
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('library_name', 'DAKSHINESWAR SHAYAK LIBRARY'),
('established_year', '1996'),
('registration_no', 'S/87920 of 1997-1998'),
('address', '11, Nepal Chandra Chatterjee Street, Ariadaha, Kolkata - 700057'),
('phone', '7595929232, 8420011218'),
('email', 'dakshineswarshayak1997@gmail.com'),
('opening_hours', 'Monday - Saturday: 9:00 AM - 7:00 PM | Sunday: Closed'),
('fine_per_day', '5.00'),
('grace_period_days', '2'),
('max_books_allowed', '3'),
('issue_duration_days', '14'),
('social_facebook', 'https://facebook.com/sayaklibrary'),
('social_twitter', 'https://twitter.com/sayaklibrary'),
('social_instagram', 'https://instagram.com/sayaklibrary'),
('social_youtube', 'https://youtube.com/@sayaklibrary'),
('phone_secondary', '8420011218'),
('email_support', 'dakshineswarshayak1997@gmail.com'),
('map_embed_url', 'https://maps.google.com/maps?q=Dakshineswar+Shayak+Library,+11,+Nepal+Chandra+Chatterjee+St,+Ariadaha,+Kolkata,+West+Bengal+700057&output=embed'),
('google_maps_link', 'https://maps.app.goo.gl/cJvtR8DGniZ4VaM7A'),
('donate_appeal_title', 'Donate to Sayak Library'),
('donate_appeal_desc', 'Your contributions directly support book restoration, student scholarships, rare manuscript preservation, and e-learning resources.'),
('donate_bank_name', 'State Bank of India (College Street Branch)'),
('donate_account_no', '38491029384'),
('donate_ifsc', 'SBIN0001234'),
('donate_upi_id', 'sayaklibrary@sbi'),
('smtp_host', 'mail.sayaklibrary.org'),
('smtp_port', '587'),
('smtp_user', 'no-reply@sayaklibrary.org'),
('smtp_pass', 'smtp_password_secret'),
('smtp_from_name', 'Sayak Library Administration'),
('smtp_from_email', 'no-reply@sayaklibrary.org'),
('cron_last_run', '2026-09-13 00:00:00'),
('governance_pdf', 'uploads/documents/memorandum_of_association_dakshineswar_shayak.pdf'),
('registered_office', '18/1, Ramgarh Road, Calcutta - 700 076'),
('reg_date', '27 August 1997'),
('cert_copy_date', '03 July 2023'),
('cert_ref_no', '79AB 299217');

-- ------------------------------------------------------------
-- Table: users
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `role_id` INT UNSIGNED NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `status` ENUM('Active', 'Restricted', 'Suspended', 'Pending') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Super Admin Initial Production Account
-- Email: todkkhaskel@gmail.com
-- Password: Dibyendu@@123
-- ------------------------------------------------------------
INSERT INTO `users` (`id`, `role_id`, `full_name`, `email`, `password`, `status`) VALUES
(1, 1, 'Super Administrator', 'todkkhaskel@gmail.com', '$2y$10$D2n04xRoNypBcCyapYpjvOjMqOLHWtDZrAfVjeT7yxbcM4QanD81W', 'Active');

-- ------------------------------------------------------------
-- Table: members
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `members`;
CREATE TABLE `members` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `member_code` VARCHAR(30) NOT NULL UNIQUE,
  `mobile` VARCHAR(20) NOT NULL,
  `address` TEXT NOT NULL,
  `dob` DATE DEFAULT NULL,
  `profile_photo` VARCHAR(255) DEFAULT NULL,
  `membership_status` ENUM('Active', 'Expiring Soon', 'Expired', 'Restricted', 'Suspended') NOT NULL DEFAULT 'Active',
  `restriction_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: librarians
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `librarians`;
CREATE TABLE `librarians` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL UNIQUE,
  `employee_code` VARCHAR(30) NOT NULL UNIQUE,
  `phone` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: categories
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_name` VARCHAR(100) NOT NULL UNIQUE,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`, `category_name`, `slug`, `description`) VALUES
(1, 'General Collection', 'general-collection', 'Reference books, encyclopedias, general knowledge, and history'),
(2, 'Fiction & Literature', 'fiction-literature', 'Novels, drama, poetry, classics, and modern fiction'),
(3, 'Higher Secondary Academics', 'higher-secondary', 'Textbooks, guidebooks, and references for HS / Class 11-12'),
(4, 'Competitive Examinations', 'competitive-exams', 'WBCS, UPSC, SSC, Banking, Railways, and entrance exam resources');

-- ------------------------------------------------------------
-- Table: classes
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `classes`;
CREATE TABLE `classes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `class_name` VARCHAR(50) NOT NULL UNIQUE,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `classes` (`id`, `class_name`) VALUES
(1, 'Class 5'), (2, 'Class 6'), (3, 'Class 7'), (4, 'Class 8'),
(5, 'Class 9'), (6, 'Class 10'), (7, 'Higher Secondary (11-12)'),
(8, 'College & Graduation'), (9, 'Competitive Exam Prep'), (10, 'General Reading');

-- ------------------------------------------------------------
-- Table: authors
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `authors`;
CREATE TABLE `authors` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `bio` TEXT DEFAULT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `authors` (`id`, `name`, `bio`) VALUES
(1, 'Rabindranath Tagore', 'Bengali polymath, poet, writer, composer, painter and Nobel laureate in Literature (1913).'),
(2, 'Satyajit Ray', 'Indian filmmaker, screenwriter, author, music composer, lyricist, graphic artist and illustrator.'),
(3, 'Dr. R.S. Aggarwal', 'Renowned author of mathematics, quantitative aptitude, and reasoning textbooks in India.'),
(4, 'M. Laxmikanth', 'Acclaimed author of Indian Polity, widely read for UPSC and Civil Services examinations.'),
(5, 'Sarat Chandra Chattopadhyay', 'Prominent Bengali novelist and short story writer of the early 20th century.');

-- ------------------------------------------------------------
-- Table: publishers
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `publishers`;
CREATE TABLE `publishers` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `address` TEXT DEFAULT NULL,
  `website` VARCHAR(150) DEFAULT NULL,
  `contact` VARCHAR(50) DEFAULT NULL,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `publishers` (`id`, `name`, `website`, `contact`) VALUES
(1, 'Ananda Publishers', 'https://anandapub.in', '+91 33 2237 4221'),
(2, 'Oxford University Press', 'https://global.oup.com', '+91 11 4560 0000'),
(3, 'S. Chand & Company', 'https://schandpublishing.com', '+91 11 4943 1234'),
(4, 'McGraw Hill India', 'https://mheducation.co.in', '+91 120 400 0000'),
(5, 'Dey’s Publishing', 'https://deyspublishing.com', '+91 33 2241 2345');

-- ------------------------------------------------------------
-- Table: subjects
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `subjects` (`id`, `name`, `description`) VALUES
(1, 'Bengali Literature', 'Classics, modern essays, and Bengal history'),
(2, 'English Literature', 'Poetry, drama, and classic English novels'),
(3, 'Mathematics', 'Arithmetic, Algebra, Geometry, and Calculus'),
(4, 'Indian Polity & Governance', 'Constitution, administrative systems, and civil rights'),
(5, 'General Knowledge & Current Affairs', 'India and World static GK and monthly roundups');

-- ------------------------------------------------------------
-- Table: books
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `books`;
CREATE TABLE `books` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `book_code` VARCHAR(30) NOT NULL UNIQUE,
  `name` VARCHAR(255) NOT NULL,
  `author_id` INT UNSIGNED NOT NULL,
  `publisher_id` INT UNSIGNED NOT NULL,
  `category_id` INT UNSIGNED NOT NULL,
  `class_id` INT UNSIGNED NOT NULL,
  `subject_id` INT UNSIGNED NOT NULL,
  `isbn` VARCHAR(30) DEFAULT NULL,
  `edition` VARCHAR(50) DEFAULT '1st Edition',
  `language` VARCHAR(50) DEFAULT 'Bengali',
  `pub_year` INT UNSIGNED DEFAULT 2022,
  `description` TEXT DEFAULT NULL,
  `cover_image` VARCHAR(255) DEFAULT NULL,
  `pdf_file` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`author_id`) REFERENCES `authors` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`publisher_id`) REFERENCES `publishers` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: book_copies
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `book_copies`;
CREATE TABLE `book_copies` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `book_id` INT UNSIGNED NOT NULL,
  `copy_code` VARCHAR(30) NOT NULL UNIQUE,
  `barcode` VARCHAR(50) NOT NULL UNIQUE,
  `shelf` VARCHAR(30) DEFAULT 'Rack-A',
  `rack` VARCHAR(30) DEFAULT 'Shelf-1',
  `status` ENUM('Available', 'Issued', 'Reserved', 'Lost', 'Damaged', 'Maintenance') NOT NULL DEFAULT 'Available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: pdf_documents
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `pdf_documents`;
CREATE TABLE `pdf_documents` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `book_id` INT UNSIGNED NOT NULL UNIQUE,
  `file_path` VARCHAR(255) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_size` INT UNSIGNED DEFAULT 0,
  `pages_count` INT UNSIGNED DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: membership_plans
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `membership_plans`;
CREATE TABLE `membership_plans` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `plan_name` VARCHAR(100) NOT NULL,
  `duration_months` INT UNSIGNED NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `membership_plans` (`id`, `plan_name`, `duration_months`, `price`) VALUES
(1, '1 Month Basic Access', 1, 100.00),
(2, '3 Months Standard', 3, 250.00),
(3, '6 Months Scholar', 6, 450.00),
(4, '1 Year Premium Annual', 12, 800.00);

-- ------------------------------------------------------------
-- Table: memberships
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `memberships`;
CREATE TABLE `memberships` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_id` INT UNSIGNED NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `start_date` DATE NOT NULL,
  `expiry_date` DATE NOT NULL,
  `status` ENUM('Active', 'Expiring Soon', 'Expired', 'Restricted', 'Suspended') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `membership_plans` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: membership_payments
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `membership_payments`;
CREATE TABLE `membership_payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `transaction_code` VARCHAR(30) NOT NULL UNIQUE,
  `member_id` INT UNSIGNED NOT NULL,
  `plan_id` INT UNSIGNED NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(30) DEFAULT 'CASH',
  `payment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `collected_by` INT UNSIGNED NOT NULL,
  `start_date` DATE NOT NULL,
  `expiry_date` DATE NOT NULL,
  `notes` VARCHAR(255) DEFAULT 'Cash Membership Payment',
  FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`plan_id`) REFERENCES `membership_plans` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`collected_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: book_requests
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `book_requests`;
CREATE TABLE `book_requests` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `request_code` VARCHAR(30) NOT NULL UNIQUE,
  `member_id` INT UNSIGNED NOT NULL,
  `book_id` INT UNSIGNED NOT NULL,
  `request_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('Pending', 'Approved', 'Rejected', 'Cancelled', 'Issued', 'Completed') NOT NULL DEFAULT 'Pending',
  `notes` VARCHAR(255) DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: book_issues
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `book_issues`;
CREATE TABLE `book_issues` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `issue_code` VARCHAR(30) NOT NULL UNIQUE,
  `request_id` INT UNSIGNED DEFAULT NULL,
  `member_id` INT UNSIGNED NOT NULL,
  `book_id` INT UNSIGNED NOT NULL,
  `copy_id` INT UNSIGNED NOT NULL,
  `issue_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `return_date` DATE DEFAULT NULL,
  `status` ENUM('Issued', 'Returned', 'Overdue', 'Lost') NOT NULL DEFAULT 'Issued',
  `issued_by` INT UNSIGNED NOT NULL,
  `returned_by` INT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`request_id`) REFERENCES `book_requests` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`copy_id`) REFERENCES `book_copies` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`issued_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: fines
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `fines`;
CREATE TABLE `fines` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `issue_id` INT UNSIGNED NOT NULL UNIQUE,
  `member_id` INT UNSIGNED NOT NULL,
  `book_id` INT UNSIGNED NOT NULL,
  `copy_id` INT UNSIGNED NOT NULL,
  `late_days` INT UNSIGNED NOT NULL DEFAULT 0,
  `fine_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Unpaid', 'Partially Paid', 'Paid', 'Waived') NOT NULL DEFAULT 'Unpaid',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`issue_id`) REFERENCES `book_issues` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`copy_id`) REFERENCES `book_copies` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: fine_payments
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `fine_payments`;
CREATE TABLE `fine_payments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `receipt_code` VARCHAR(30) NOT NULL UNIQUE,
  `fine_id` INT UNSIGNED NOT NULL,
  `member_id` INT UNSIGNED NOT NULL,
  `amount_paid` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(30) DEFAULT 'CASH',
  `payment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `collected_by` INT UNSIGNED NOT NULL,
  FOREIGN KEY (`fine_id`) REFERENCES `fines` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`collected_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: notices
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `notices`;
CREATE TABLE `notices` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `short_description` TEXT NOT NULL,
  `full_description` LONGTEXT NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `publish_date` DATE NOT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `priority` ENUM('Normal', 'Important', 'Urgent') NOT NULL DEFAULT 'Normal',
  `status` ENUM('Draft', 'Published', 'Unpublished', 'Expired') NOT NULL DEFAULT 'Published',
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: donations
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `donations`;
CREATE TABLE `donations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `donor_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `mobile` VARCHAR(20) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `donation_type` ENUM('Money', 'Books', 'Other') NOT NULL DEFAULT 'Money',
  `amount` DECIMAL(10,2) DEFAULT 0.00,
  `message` TEXT DEFAULT NULL,
  `status` ENUM('New', 'Contacted', 'Received', 'Completed', 'Cancelled') NOT NULL DEFAULT 'New',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: gallery
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `gallery`;
CREATE TABLE `gallery` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `category` VARCHAR(50) DEFAULT 'Library Photos',
  `is_published` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: contact_messages
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `contact_messages`;
CREATE TABLE `contact_messages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('New', 'Read', 'Replied') NOT NULL DEFAULT 'New',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: notifications
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: email_logs
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `email_logs`;
CREATE TABLE `email_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `recipient_email` VARCHAR(120) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `status` ENUM('Sent', 'Failed') NOT NULL DEFAULT 'Sent',
  `error_message` TEXT DEFAULT NULL,
  `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: audit_logs
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `role` VARCHAR(50) DEFAULT 'System',
  `action` VARCHAR(100) NOT NULL,
  `target` VARCHAR(100) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: pdf_reading_history
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `pdf_reading_history`;
CREATE TABLE `pdf_reading_history` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `member_id` INT UNSIGNED NOT NULL,
  `book_id` INT UNSIGNED NOT NULL,
  `last_page` INT UNSIGNED DEFAULT 1,
  `total_pages` INT UNSIGNED DEFAULT 1,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `member_book_uniq` (`member_id`, `book_id`),
  FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Table: governing_body_members
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `governing_body_members`;
CREATE TABLE `governing_body_members` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `designation` VARCHAR(150) NOT NULL,
  `committee_type` VARCHAR(50) NOT NULL DEFAULT 'Governing Body',
  `description` TEXT DEFAULT NULL,
  `bio` TEXT DEFAULT NULL,
  `photo` VARCHAR(255) DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT 'fa-user-tie',
  `sort_order` INT NOT NULL DEFAULT 0,
  `status` ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `governing_body_members` (`id`, `name`, `designation`, `committee_type`, `description`, `bio`, `icon`, `sort_order`, `status`) VALUES
(1, 'Anjan Basu', 'President', 'Governing Body', 'President, Dakshineswar Shayak Library Governing Body.', 'Distinguished patron and President of the Dakshineswar Shayak Library Governing Body.\r\n\r\nWith over 25 years of public educational leadership, Sri Anjan Basu has spearheaded library expansion, student textbook distribution drives, and community welfare programs. Under his presidency, the library has broadened its acquisition of competitive examinations curricula and established dedicated quiet study facilities for college and university aspirants.', 'fa-user-tie', 1, 'Active'),
(2, 'Pallab Adhikary', 'Vice President', 'Governing Body', 'Vice President, Dakshineswar Shayak Library Governing Body.', NULL, 'fa-user-shield', 2, 'Active'),
(3, 'Sourav chandra Majee', 'Secretary', 'Governing Body', 'Secretary & Executive Officer, Dakshineswar Shayak Library.', 'Executive Secretary & Administrative Officer of Dakshineswar Shayak Library.\r\n\r\nSourav chandra Majee oversees institutional administration, member services, donor engagement, and digital archive preservation. He coordinates between the Governing Body and Working Committee to ensure smooth daily operations, timely acquisition of university course texts, and seamless lending services for students across North 24 Parganas and Kolkata.', 'fa-user-graduate', 3, 'Active'),
(4, 'Sudip Kumar Denre', 'Assistant Secretary', 'Governing Body', 'Assistant Secretary, Administration & Member Affairs.', NULL, 'fa-user-cog', 4, 'Active'),
(5, 'Lakshmi Denre', 'Assistant Secretary', 'Governing Body', 'Assistant Secretary, Operational & Cultural Coordination.', NULL, 'fa-user-cog', 5, 'Active'),
(6, 'Arobinda Dutta', 'Treasurer', 'Governing Body', 'Treasurer & Finance Controller, Dakshineswar Shayak Library.', NULL, 'fa-coins', 6, 'Active'),
(7, 'Abhimanyu Ganguly', 'Assistant Treasurer', 'Governing Body', 'Assistant Treasurer & Accounts Auditor.', NULL, 'fa-calculator', 7, 'Active'),
(8, 'Sujit Panja', 'Working Committee Member', 'Working Committee', 'Library Operations, Program Coordination & Community Support.', NULL, 'fa-user-check', 11, 'Active'),
(9, 'Diptesh Manna', 'Working Committee Member', 'Working Committee', 'Catalog Logistics, Book Preservation & Youth Engagement.', NULL, 'fa-user-check', 12, 'Active'),
(10, 'Shayari Mondal', 'Working Committee Member', 'Working Committee', 'Reading Hall Assistance, Digital Archive & Patron Relations.', NULL, 'fa-user-check', 13, 'Active'),
(11, 'Aishi Mitra Mustafi', 'Working Committee Member', 'Working Committee', 'Academic Outreach, Event Management & Student Resources.', NULL, 'fa-user-check', 14, 'Active'),
(12, 'Anyasa Roy', 'Working Committee Member', 'Working Committee', 'Library Activities, Membership Desk & Educational Initiatives.', NULL, 'fa-user-check', 15, 'Active'),
-- ------------------------------------------------------------
-- Table: academic_collaborations
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `academic_collaborations`;
CREATE TABLE `academic_collaborations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `partner_name` VARCHAR(255) NOT NULL,
  `partner_subtitle` VARCHAR(255) DEFAULT NULL,
  `partner_logo` VARCHAR(255) DEFAULT NULL,
  `mou_title` VARCHAR(255) NOT NULL,
  `mou_ref_no` VARCHAR(100) DEFAULT NULL,
  `signed_date` DATE DEFAULT NULL,
  `validity_period` VARCHAR(100) DEFAULT '3 Years',
  `partner_signatory` VARCHAR(255) DEFAULT NULL,
  `library_signatory` VARCHAR(255) DEFAULT NULL,
  `witness_details` VARCHAR(255) DEFAULT NULL,
  `summary_text` TEXT DEFAULT NULL,
  `objectives` TEXT DEFAULT NULL,
  `scope_modalities` TEXT DEFAULT NULL,
  `mou_pdf` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('Active', 'Expired', 'Draft') NOT NULL DEFAULT 'Active',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `academic_collaborations` (`id`, `partner_name`, `partner_subtitle`, `partner_logo`, `mou_title`, `mou_ref_no`, `signed_date`, `validity_period`, `partner_signatory`, `library_signatory`, `witness_details`, `summary_text`, `objectives`, `scope_modalities`, `mou_pdf`, `status`, `sort_order`) VALUES
(1, 'Hiralal Mazumdar Memorial College for Women', 'Affiliated under West Bengal State University (WBSU) • Established 1959 • Dakshineswar, Kolkata - 700 035', 'hmmc_college_logo.jpg', 'Bilateral Memorandum of Understanding (MOU) on Library Services', '73AB 065674', '2022-05-24', '3 Years (Subject to mutual renewal)', 'Dr. Soma Ghosh, Principal & Secretary', 'Pallab Adhikary, Secretary', 'Coordinator, IQAC (HMMC) & Sourav Chandra Maju (Shayak Library)', 'Bilateral library services agreement to expand reference reading for undergraduate women students, facilitate mutual book study visits, and receive academic syllabus textbooks from the college.', 'Promote Library Culture: Educate college students and library users on the importance of libraries in an education system.\nResource Utilization: Increase awareness and active circulation of specialized academic syllabi and reference collections.\nInformed Student Body: Foster a vibrant, community-centered academic environment supporting undergraduate women researchers.', 'Mutual Reading Visits: Scheduled reciprocal visits on designated days of the week by library members and students to study, read, and consult reference materials on site.\nFemale Student Access: Dedicated reading hall access provided to female students of both institutions, ensuring a safe, supportive, and resourceful study haven.\nTextbook Donations: College library donates curriculum textbooks, syllabi reference works, and academic volumes as per availability.\nInstitutional Coordination: Both parties assign dedicated liaison coordinators under IQAC and Library Council.', 'mou_hiralal_mazumdar_memorial_college_for_women.pdf', 'Active', 1);

SET FOREIGN_KEY_CHECKS = 1;
