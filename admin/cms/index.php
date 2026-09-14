<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

require_role('SUPER_ADMIN');

$db = getDB();
$errors = [];
$activeTab = sanitize_input($_GET['tab'] ?? 'contact');
$allowedTabs = ['contact', 'branding', 'rules', 'institutional', 'govbody'];
if (!in_array($activeTab, $allowedTabs)) {
    $activeTab = 'contact';
}

// ============================================================
// 1. POST ACTION: SAVE CONTACT & SOCIAL CMS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_contact_cms'])) {
    $activeTab = 'contact';
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token validation failed.";
    } else {
        $contactKeys = [
            'phone', 'phone_secondary', 'email', 'email_support',
            'address', 'opening_hours', 'map_embed_url',
            'social_facebook', 'social_twitter', 'social_instagram', 'social_youtube'
        ];

        foreach ($contactKeys as $key) {
            if (isset($_POST[$key])) {
                $val = trim($_POST[$key]);
                $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->execute([$key, $val]);
            }
        }

        if (empty($errors)) {
            log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Update Contact CMS', 'CMS', "Contact numbers, email, address, and social links updated.");
            set_flash_message('success', 'Contact information, email, physical address, and social links updated successfully! Live website reflects changes.');
            header("Location: " . BASE_URL . "admin/cms/index.php?tab=contact");
            exit();
        }
    }
}

// ============================================================
// 2. POST ACTION: SAVE BRANDING & HOMEPAGE CMS & LOGO
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_branding_cms']) || isset($_POST['save_cms']))) {
    $activeTab = 'branding';
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token validation failed.";
    } else {
        // Handle Logo Upload if present
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['logo_file']['tmp_name'];
            $origName = basename($_FILES['logo_file']['name']);
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowed)) {
                $errors[] = "Invalid logo image format. Allowed: JPG, PNG, WEBP.";
            } else {
                $logoFilename = 'site_logo_' . time() . '.' . $ext;
                $uploadPath = ROOT_PATH . 'uploads/' . $logoFilename;
                if (!is_dir(ROOT_PATH . 'uploads/')) {
                    mkdir(ROOT_PATH . 'uploads/', 0755, true);
                }

                if (move_uploaded_file($tmpName, $uploadPath)) {
                    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('site_logo', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    $stmt->execute([$logoFilename]);
                } else {
                    $errors[] = "Failed to upload logo file.";
                }
            }
        }

        // Save Branding & Homepage Keys
        $brandingKeys = [
            'library_name', 'established_year', 'registration_no',
            'hero_badge', 'hero_title', 'hero_lead',
            'hero_featured_book_title', 'hero_featured_book_author', 'hero_featured_book_desc',
            'about_subtitle', 'about_title', 'about_para1', 'about_mission_quote',
            'about_physical_count', 'about_digital_count',
            'facility_1', 'facility_2', 'facility_3', 'facility_4'
        ];

        foreach ($brandingKeys as $key) {
            if (isset($_POST[$key])) {
                $val = trim($_POST[$key]);
                $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->execute([$key, $val]);
            }
        }

        if (empty($errors)) {
            log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Update Branding CMS', 'CMS', "Website text and logo updated.");
            set_flash_message('success', 'Website branding, homepage content, and logo updated successfully! Live website reflects changes.');
            header("Location: " . BASE_URL . "admin/cms/index.php?tab=branding");
            exit();
        }
    }
}

// ============================================================
// 3. POST ACTION: SAVE USER RULES & REGULATIONS CMS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_rules_cms'])) {
    $activeTab = 'rules';
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token validation failed.";
    } else {
        $rulesKeys = [
            'rules_lead',
            'rules_sec1_title', 'rules_sec1_content',
            'rules_sec2_title', 'rules_sec2_content',
            'rules_sec3_title', 'rules_sec3_content',
            'rules_sec4_title', 'rules_sec4_content',
            'rules_sec5_title', 'rules_sec5_content'
        ];

        foreach ($rulesKeys as $key) {
            if (isset($_POST[$key])) {
                $val = trim($_POST[$key]);
                $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->execute([$key, $val]);
            }
        }

        if (empty($errors)) {
            log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Update Rules CMS', 'CMS', "User rules and regulations content updated.");
            set_flash_message('success', 'User Rules & Regulations updated successfully! Live page reflects changes.');
            header("Location: " . BASE_URL . "admin/cms/index.php?tab=rules");
            exit();
        }
    }
}

// ============================================================
// 4. POST ACTION: SAVE INSTITUTIONAL PAGES CONTENT CMS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_institutional_cms'])) {
    $activeTab = 'institutional';
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token validation failed.";
    } else {
        $instKeys = [
            // Our Journey
            'journey_lead', 'journey_1995_title', 'journey_1995_desc', 'journey_2008_title', 'journey_2008_desc', 'journey_present_title', 'journey_present_desc', 'journey_mission',
            // Governance
            'governance_lead', 'gov_comp_title', 'gov_comp_desc', 'gov_audit_title', 'gov_audit_desc', 'gov_sec_title', 'gov_sec_desc',
            // Academic Collaborations
            'collab_lead', 'collab_1_title', 'collab_1_desc', 'collab_2_title', 'collab_2_desc', 'collab_3_title', 'collab_3_desc', 'collab_4_title', 'collab_4_desc',
            // Donation Appeal & Bank Details
            'donate_appeal_title', 'donate_appeal_desc', 'donate_bank_name', 'donate_account_no', 'donate_ifsc', 'donate_upi_id'
        ];

        foreach ($instKeys as $key) {
            if (isset($_POST[$key])) {
                $val = trim($_POST[$key]);
                $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->execute([$key, $val]);
            }
        }

        if (empty($errors)) {
            log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Update Institutional CMS', 'CMS', "Institutional Journey, Governance, Collaborations, and Donation info updated.");
            set_flash_message('success', 'Institutional pages content and donation bank details updated successfully!');
            header("Location: " . BASE_URL . "admin/cms/index.php?tab=institutional");
            exit();
        }
    }
}

// ============================================================
// 5. POST ACTION: SAVE / EDIT GOVERNING BODY MEMBER
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_gov_member'])) {
    $activeTab = 'govbody';
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token validation failed.";
    } else {
        $mId = (int)($_POST['member_id'] ?? 0);
        $name = sanitize_input($_POST['name'] ?? '');
        $designation = sanitize_input($_POST['designation'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $icon = sanitize_input($_POST['icon'] ?? 'fa-user-tie');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = sanitize_input($_POST['status'] ?? 'Active');
        if (!in_array($status, ['Active', 'Inactive'])) $status = 'Active';

        if (empty($name)) $errors[] = "Member name is required.";
        if (empty($designation)) $errors[] = "Designation is required.";

        // Handle Photo Upload (Optional)
        $newPhotoFilename = null;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['photo']['tmp_name'];
            $origName = basename($_FILES['photo']['name']);
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowed)) {
                $errors[] = "Invalid photo format. Allowed formats: JPG, PNG, WEBP.";
            } else {
                $imgCheck = @getimagesize($tmpName);
                if ($imgCheck === false) {
                    $errors[] = "Uploaded file is not a valid image.";
                } else {
                    $uploadDir = ROOT_PATH . 'uploads/governing_body/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    $candidatePhoto = 'gov_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    if (move_uploaded_file($tmpName, $uploadDir . $candidatePhoto)) {
                        $newPhotoFilename = $candidatePhoto;
                    } else {
                        $errors[] = "Failed to save photo to disk.";
                    }
                }
            }
        }

        if (empty($errors)) {
            $removePhoto = isset($_POST['remove_photo']) && $_POST['remove_photo'] == '1';

            if ($mId > 0) {
                // Fetch old photo for cleanup
                $oldStmt = $db->prepare("SELECT photo FROM governing_body_members WHERE id = ?");
                $oldStmt->execute([$mId]);
                $oldPhoto = $oldStmt->fetchColumn();

                if ($newPhotoFilename) {
                    if ($oldPhoto && file_exists(ROOT_PATH . 'uploads/governing_body/' . $oldPhoto)) {
                        @unlink(ROOT_PATH . 'uploads/governing_body/' . $oldPhoto);
                    }
                    $db->prepare("UPDATE governing_body_members SET name = ?, designation = ?, description = ?, icon = ?, sort_order = ?, status = ?, photo = ? WHERE id = ?")
                       ->execute([$name, $designation, $description, $icon, $sortOrder, $status, $newPhotoFilename, $mId]);
                } elseif ($removePhoto) {
                    if ($oldPhoto && file_exists(ROOT_PATH . 'uploads/governing_body/' . $oldPhoto)) {
                        @unlink(ROOT_PATH . 'uploads/governing_body/' . $oldPhoto);
                    }
                    $db->prepare("UPDATE governing_body_members SET name = ?, designation = ?, description = ?, icon = ?, sort_order = ?, status = ?, photo = NULL WHERE id = ?")
                       ->execute([$name, $designation, $description, $icon, $sortOrder, $status, $mId]);
                } else {
                    $db->prepare("UPDATE governing_body_members SET name = ?, designation = ?, description = ?, icon = ?, sort_order = ?, status = ? WHERE id = ?")
                       ->execute([$name, $designation, $description, $icon, $sortOrder, $status, $mId]);
                }
                log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Edit Gov Member', 'GoverningBody', "Updated: {$name} ({$designation})");
                set_flash_message('success', "Governing body member '{$name}' updated successfully.");
            } else {
                $ins = $db->prepare("INSERT INTO governing_body_members (name, designation, description, photo, icon, sort_order, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $ins->execute([$name, $designation, $description, $newPhotoFilename, $icon, $sortOrder, $status]);
                log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Add Gov Member', 'GoverningBody', "Added: {$name} ({$designation})");
                set_flash_message('success', "New governing body member '{$name}' added successfully.");
            }
            header("Location: " . BASE_URL . "admin/cms/index.php?tab=govbody");
            exit();
        }
    }
}

// ============================================================
// 6. POST ACTION: DELETE GOVERNING BODY MEMBER
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_gov_member'])) {
    $activeTab = 'govbody';
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token validation failed.";
    } else {
        $delId = (int)$_POST['member_id'];
        $oldStmt = $db->prepare("SELECT name, photo FROM governing_body_members WHERE id = ?");
        $oldStmt->execute([$delId]);
        $row = $oldStmt->fetch();
        if ($row) {
            if (!empty($row['photo']) && file_exists(ROOT_PATH . 'uploads/governing_body/' . $row['photo'])) {
                @unlink(ROOT_PATH . 'uploads/governing_body/' . $row['photo']);
            }
            $db->prepare("DELETE FROM governing_body_members WHERE id = ?")->execute([$delId]);
            log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Delete Gov Member', 'GoverningBody', "Deleted: {$row['name']}");
            set_flash_message('info', "Governing body member '{$row['name']}' removed.");
        }
        header("Location: " . BASE_URL . "admin/cms/index.php?tab=govbody");
        exit();
    }
}

// Current Logo
$currentLogo = get_setting('site_logo', '');
$currentLogoUrl = !empty($currentLogo) && file_exists(ROOT_PATH . 'uploads/' . $currentLogo) ? BASE_URL . 'uploads/' . $currentLogo : BASE_URL . 'assets/images/site_logo.png';

// Fetch Governing Body Members
$govMembers = $db->query("SELECT * FROM governing_body_members ORDER BY sort_order ASC, id ASC")->fetchAll();

$pageTitle = "Website Content Manager (CMS)";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row g-4">
        <!-- Sidebar Navigation Column -->
        <div class="col-lg-3 col-xl-2">
            <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
        </div>

        <!-- Main Content Area Column -->
        <div class="col-lg-9 col-xl-10">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="section-title mb-1">Website Content Manager (CMS)</h2>
                    <p class="text-muted small mb-0">Super Admin Content Management: Manage contact channels, email, address, branding, rules, institutional pages, and governing body.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>" target="_blank" class="btn btn-outline-secondary btn-sm font-serif">
                        <i class="fas fa-home me-1"></i> Public Website
                    </a>
                    <a href="<?= BASE_URL ?>contact.php" target="_blank" class="btn btn-outline-maroon btn-sm font-serif">
                        <i class="fas fa-envelope me-1"></i> Contact Page
                    </a>
                    <a href="<?= BASE_URL ?>rules.php" target="_blank" class="btn btn-outline-maroon btn-sm font-serif">
                        <i class="fas fa-gavel me-1"></i> Rules Page
                    </a>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger shadow-sm mb-4">
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?>
                            <li><?= escape($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Navigation Tabs -->
            <ul class="nav nav-pills mb-4 gap-2 border-bottom pb-3" id="cmsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'contact' ? 'active' : '' ?> font-serif fw-semibold" id="contact-tab" data-bs-toggle="pill" data-bs-target="#tab-contact" type="button" role="tab" aria-selected="<?= $activeTab === 'contact' ? 'true' : 'false' ?>">
                        <i class="fas fa-address-book me-2"></i> Contact, Email & Address
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'branding' ? 'active' : '' ?> font-serif fw-semibold" id="branding-tab" data-bs-toggle="pill" data-bs-target="#tab-branding" type="button" role="tab" aria-selected="<?= $activeTab === 'branding' ? 'true' : 'false' ?>">
                        <i class="fas fa-sliders-h me-2"></i> Branding & Homepage
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'rules' ? 'active' : '' ?> font-serif fw-semibold" id="rules-tab" data-bs-toggle="pill" data-bs-target="#tab-rules" type="button" role="tab" aria-selected="<?= $activeTab === 'rules' ? 'true' : 'false' ?>">
                        <i class="fas fa-book-reader me-2"></i> Rules & Regulations
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'institutional' ? 'active' : '' ?> font-serif fw-semibold" id="institutional-tab" data-bs-toggle="pill" data-bs-target="#tab-institutional" type="button" role="tab" aria-selected="<?= $activeTab === 'institutional' ? 'true' : 'false' ?>">
                        <i class="fas fa-university me-2"></i> Institutional & Donations
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'govbody' ? 'active' : '' ?> font-serif fw-semibold" id="govbody-tab" data-bs-toggle="pill" data-bs-target="#tab-govbody" type="button" role="tab" aria-selected="<?= $activeTab === 'govbody' ? 'true' : 'false' ?>">
                        <i class="fas fa-users me-2"></i> Governing Body Members <span class="badge bg-maroon ms-1" style="background-color: #7A0C0C;"><?= count($govMembers) ?></span>
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="cmsTabsContent">
                <!-- ==================================================== -->
                <!-- TAB 1: CONTACT, EMAIL, ADDRESS & SOCIAL LINKS        -->
                <!-- ==================================================== -->
                <div class="tab-pane fade <?= $activeTab === 'contact' ? 'show active' : '' ?>" id="tab-contact" role="tabpanel">
                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                        <div class="card sayak-card mb-4 border-top border-4 border-maroon">
                            <div class="card-header bg-white font-serif py-3 fw-bold fs-5 text-maroon" style="color: #7A0C0C;">
                                <i class="fas fa-phone-alt me-2"></i> Library Contact Numbers & Email Addresses
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Primary Phone Helpline <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            <input type="text" name="phone" class="form-control" value="<?= escape(get_setting('phone', '+91 33 2241 8900 / +91 98300 12345')) ?>" placeholder="+91 33 2241 8900" required>
                                        </div>
                                        <small class="text-muted">Displayed in the header top bar, contact page, and footer.</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Secondary Phone / WhatsApp Helpline</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fab fa-whatsapp text-success"></i></span>
                                            <input type="text" name="phone_secondary" class="form-control" value="<?= escape(get_setting('phone_secondary', '+91 98300 12345')) ?>" placeholder="+91 98300 12345">
                                        </div>
                                        <small class="text-muted">Direct mobile / WhatsApp helpline for patrons.</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Official Contact Email <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" name="email" class="form-control" value="<?= escape(get_setting('email', 'info@sayaklibrary.org')) ?>" placeholder="info@sayaklibrary.org" required>
                                        </div>
                                        <small class="text-muted">General public and administrative inquiries email.</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Member Support Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-headset"></i></span>
                                            <input type="email" name="email_support" class="form-control" value="<?= escape(get_setting('email_support', 'support@sayaklibrary.org')) ?>" placeholder="support@sayaklibrary.org">
                                        </div>
                                        <small class="text-muted">Technical support and membership query desk.</small>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold small">Full Physical Campus / Library Address <span class="text-danger">*</span></label>
                                        <textarea name="address" class="form-control" rows="2" required><?= escape(get_setting('address', '124 Academic Avenue, College Street, Kolkata, West Bengal - 700073')) ?></textarea>
                                        <small class="text-muted">Complete physical street address displayed across all contact touchpoints.</small>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold small">Operating & Working Hours <span class="text-danger">*</span></label>
                                        <input type="text" name="opening_hours" class="form-control" value="<?= escape(get_setting('opening_hours', 'Monday - Saturday: 9:00 AM - 7:00 PM | Sunday: Closed')) ?>" required>
                                        <small class="text-muted">e.g. Monday - Saturday: 9:00 AM - 7:00 PM | Sunday: Closed</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section: Google Map & Social Links -->
                        <div class="card sayak-card mb-4 border-start border-4 border-info">
                            <div class="card-header bg-white font-serif py-3 fw-bold fs-5 text-dark">
                                <i class="fas fa-map-marked-alt text-info me-2"></i> Google Map Embed & Social Media Channels
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-bold small">Google Map Embed Iframe URL (src)</label>
                                        <input type="text" name="map_embed_url" class="form-control font-monospace small" value="<?= escape(get_setting('map_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3684.128795764048!2d88.3638927!3d22.574343!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3a0277ab54a83b27%3A0xb36384a56828551!2sCollege%20St%2C%20Kolkata%2C%20West%20Bengal!5e0!3m2!1sen!2sin!4v1680000000000!5m2!1sen!2sin')) ?>">
                                        <small class="text-muted">Paste the <code>https://www.google.com/maps/embed?...</code> URL from Google Maps Share &gt; Embed a map.</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small"><i class="fab fa-facebook-f text-primary me-1"></i> Facebook Page URL</label>
                                        <input type="url" name="social_facebook" class="form-control" value="<?= escape(get_setting('social_facebook', 'https://facebook.com/sayaklibrary')) ?>" placeholder="https://facebook.com/sayaklibrary">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small"><i class="fab fa-twitter text-info me-1"></i> Twitter / X Profile URL</label>
                                        <input type="url" name="social_twitter" class="form-control" value="<?= escape(get_setting('social_twitter', 'https://twitter.com/sayaklibrary')) ?>" placeholder="https://twitter.com/sayaklibrary">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small"><i class="fab fa-instagram text-danger me-1"></i> Instagram Profile URL</label>
                                        <input type="url" name="social_instagram" class="form-control" value="<?= escape(get_setting('social_instagram', 'https://instagram.com/sayaklibrary')) ?>" placeholder="https://instagram.com/sayaklibrary">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small"><i class="fab fa-youtube text-danger me-1"></i> YouTube Channel URL</label>
                                        <input type="url" name="social_youtube" class="form-control" value="<?= escape(get_setting('social_youtube', 'https://youtube.com/@sayaklibrary')) ?>" placeholder="https://youtube.com/@sayaklibrary">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="save_contact_cms" class="btn btn-maroon btn-lg w-100 font-serif fw-bold py-3" style="background-color: #7A0C0C;">
                            <i class="fas fa-save me-2"></i> Save Contact, Email & Address Settings
                        </button>
                    </form>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 2: WEBSITE BRANDING & HOMEPAGE                   -->
                <!-- ==================================================== -->
                <div class="tab-pane fade <?= $activeTab === 'branding' ? 'show active' : '' ?>" id="tab-branding" role="tabpanel">
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                        <!-- Section 1: Logo & Branding -->
                        <div class="card sayak-card mb-4 border-top border-4 border-maroon">
                            <div class="card-header bg-white font-serif py-3 fw-bold fs-5 text-maroon" style="color: #7A0C0C;">
                                <i class="fas fa-image me-2"></i> Website Logo & Brand Header Settings
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4 align-items-center">
                                    <div class="col-md-3 text-center">
                                        <small class="text-muted d-block mb-2 font-serif">Current Website Logo:</small>
                                        <img src="<?= $currentLogoUrl ?>" class="rounded-circle border border-2 border-maroon p-1 shadow-sm" style="width: 120px; height: 120px; object-fit: cover;" alt="Current Logo">
                                    </div>
                                    <div class="col-md-9">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold small">Upload New Logo Image (PNG / JPG / WEBP)</label>
                                            <input type="file" name="logo_file" class="form-control" accept="image/*">
                                            <small class="text-muted">High resolution square or circular image recommended. Updates public header, dashboard, and footer.</small>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label fw-bold small">Library Name</label>
                                                <input type="text" name="library_name" class="form-control" value="<?= escape(get_setting('library_name', 'SAYAK LIBRARY')) ?>" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label fw-bold small">Established Year</label>
                                                <input type="text" name="established_year" class="form-control" value="<?= escape(get_setting('established_year', '1995')) ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label fw-bold small">Registration Number</label>
                                                <input type="text" name="registration_no" class="form-control" value="<?= escape(get_setting('registration_no', 'SL/WB/2023/8892')) ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Homepage Hero Banner -->
                        <div class="card sayak-card mb-4 border-start border-4 border-warning">
                            <div class="card-header bg-white font-serif py-3 fw-bold fs-5">
                                <i class="fas fa-desktop text-warning me-2"></i> Homepage Hero Banner Content
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">Hero Top Badge Text</label>
                                        <input type="text" name="hero_badge" class="form-control" value="<?= escape(get_setting('hero_badge', 'WELCOME TO SAYAK LIBRARY')) ?>">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label fw-bold small">Hero Main Title Headline</label>
                                        <input type="text" name="hero_title" class="form-control" value="<?= escape(get_setting('hero_title', 'Empowering Minds Through Knowledge & Literature')) ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small">Hero Lead Paragraph Text</label>
                                        <textarea name="hero_lead" class="form-control" rows="2"><?= escape(get_setting('hero_lead', 'Explore over 25,000+ physical books, rare historical manuscripts, and an expanding digital PDF repository for students, researchers, and book lovers.')) ?></textarea>
                                    </div>

                                    <div class="col-12"><hr class="my-2"><strong class="font-serif text-maroon" style="color: #7A0C0C;">Featured Book Highlight Box (Hero Right Column)</strong></div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Featured Book Name</label>
                                        <input type="text" name="hero_featured_book_title" class="form-control" value="<?= escape(get_setting('hero_featured_book_title', 'Gitanjali (Song Offerings)')) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Featured Book Author</label>
                                        <input type="text" name="hero_featured_book_author" class="form-control" value="<?= escape(get_setting('hero_featured_book_author', 'By Rabindranath Tagore')) ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small">Featured Book Short Summary</label>
                                        <input type="text" name="hero_featured_book_desc" class="form-control" value="<?= escape(get_setting('hero_featured_book_desc', 'Nobel Prize winning collection of poems capturing spiritual devotion and sublime lyricism.')) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 3: Homepage About Section -->
                        <div class="card sayak-card mb-4">
                            <div class="card-header bg-white font-serif py-3 fw-bold fs-5">
                                <i class="fas fa-university text-maroon me-2" style="color: #7A0C0C;"></i> About Us Introduction & Mission
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Sub-heading Badge</label>
                                        <input type="text" name="about_subtitle" class="form-control" value="<?= escape(get_setting('about_subtitle', 'ABOUT OUR INSTITUTION')) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Main Section Title</label>
                                        <input type="text" name="about_title" class="form-control" value="<?= escape(get_setting('about_title', 'A Center of Learning & Knowledge Excellence')) ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small">Introductory Paragraph Text</label>
                                        <textarea name="about_para1" class="form-control" rows="3"><?= escape(get_setting('about_para1', 'Established in 1995, SAYAK LIBRARY serves as a premier educational repository and public reading hub. We cater to school students, higher secondary candidates, civil service aspirants, and general readers across Bengal.')) ?></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Physical Books Display Count</label>
                                        <input type="text" name="about_physical_count" class="form-control" value="<?= escape(get_setting('about_physical_count', '25,000+')) ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Digital PDFs Display Count</label>
                                        <input type="text" name="about_digital_count" class="form-control" value="<?= escape(get_setting('about_digital_count', '5,000+')) ?>">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold small">Core Institutional Mission Quote</label>
                                        <textarea name="about_mission_quote" class="form-control" rows="2"><?= escape(get_setting('about_mission_quote', 'To foster lifelong learning, preserve Bengali and Indian literary heritage, and equip students with modern digital resources in a quiet, modern academic environment.')) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 4: Facilities Bullet List -->
                        <div class="card sayak-card mb-4">
                            <div class="card-header bg-white font-serif py-3 fw-bold fs-5">
                                <i class="fas fa-list-check text-success me-2"></i> Library Facilities Bullet Items
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Facility Bullet 1</label>
                                        <input type="text" name="facility_1" class="form-control" value="<?= escape(get_setting('facility_1', 'Air-conditioned quiet reading hall with seating for 120 readers.')) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Facility Bullet 2</label>
                                        <input type="text" name="facility_2" class="form-control" value="<?= escape(get_setting('facility_2', 'High-speed Wi-Fi and online computer library catalog (OPAC).')) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Facility Bullet 3</label>
                                        <input type="text" name="facility_3" class="form-control" value="<?= escape(get_setting('facility_3', 'Dedicated PDF Digital Library with in-browser reader for members.')) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Facility Bullet 4</label>
                                        <input type="text" name="facility_4" class="form-control" value="<?= escape(get_setting('facility_4', 'Physical book borrowing with barcoded catalog system.')) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="save_branding_cms" class="btn btn-maroon btn-lg w-100 font-serif fw-bold py-3" style="background-color: #7A0C0C;">
                            <i class="fas fa-save me-2"></i> Save Website Branding & Homepage Content
                        </button>
                    </form>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 3: USER RULES & REGULATIONS CMS                  -->
                <!-- ==================================================== -->
                <div class="tab-pane fade <?= $activeTab === 'rules' ? 'show active' : '' ?>" id="tab-rules" role="tabpanel">
                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                        <div class="card sayak-card mb-4 border-top border-4 border-maroon">
                            <div class="card-header bg-white font-serif py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="fw-bold fs-5 text-maroon" style="color: #7A0C0C;">
                                    <i class="fas fa-gavel me-2"></i> User Rules & Regulations (rules.php)
                                </div>
                                <a href="<?= BASE_URL ?>rules.php" target="_blank" class="btn btn-outline-maroon btn-sm font-serif">
                                    <i class="fas fa-external-link-alt me-1"></i> View Live Rules Page
                                </a>
                            </div>
                            <div class="card-body p-4">
                                <div class="alert alert-light border shadow-sm mb-4">
                                    <i class="fas fa-info-circle text-primary me-2"></i>
                                    <strong>Formatting Tip:</strong> Enter each rule on a new line. You can use standard text or HTML tags like <code>&lt;strong&gt;</code> and <code>&lt;code&gt;</code> for emphasis.
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold small">Rules Page Header Subtitle Lead</label>
                                    <input type="text" name="rules_lead" class="form-control" value="<?= escape(get_setting('rules_lead', 'Official code of conduct, lending policies, and fine structures governing Sayak Library.')) ?>" required>
                                </div>

                                <hr class="my-4">

                                <!-- Rule Section 1 -->
                                <div class="mb-4 p-3 bg-light rounded border">
                                    <label class="form-label fw-bold text-maroon font-serif" style="color: #7A0C0C;">1. Membership Card & Identity Section</label>
                                    <div class="row g-2 mb-2">
                                        <div class="col-12">
                                            <input type="text" name="rules_sec1_title" class="form-control fw-bold" value="<?= escape(get_setting('rules_sec1_title', '1. Membership Card & Identity')) ?>" required>
                                        </div>
                                    </div>
                                    <label class="form-label small text-muted">Rule Items (one bullet per line):</label>
                                    <textarea name="rules_sec1_content" class="form-control font-monospace small" rows="3"><?= escape(get_setting('rules_sec1_content', "• Members must present their physical or digital Member ID (e.g. <code>SL-MEM-000001</code>) at the entry counter and borrowing desk.\n• Membership is non-transferable. Borrowing privileges are restricted to the registered member.")) ?></textarea>
                                </div>

                                <!-- Rule Section 2 -->
                                <div class="mb-4 p-3 bg-light rounded border">
                                    <label class="form-label fw-bold text-maroon font-serif" style="color: #7A0C0C;">2. Physical Book Borrowing & Limits Section</label>
                                    <div class="row g-2 mb-2">
                                        <div class="col-12">
                                            <input type="text" name="rules_sec2_title" class="form-control fw-bold" value="<?= escape(get_setting('rules_sec2_title', '2. Physical Book Borrowing & Limits')) ?>" required>
                                        </div>
                                    </div>
                                    <label class="form-label small text-muted">Rule Items (one bullet per line):</label>
                                    <textarea name="rules_sec2_content" class="form-control font-monospace small" rows="3"><?= escape(get_setting('rules_sec2_content', "• Active members can borrow up to <strong>3 physical books</strong> simultaneously for a standard period of <strong>14 days</strong>.\n• Reference books, rare manuscripts, and single-copy encyclopedias cannot be removed from the library reading hall.")) ?></textarea>
                                </div>

                                <!-- Rule Section 3 -->
                                <div class="mb-4 p-3 bg-light rounded border">
                                    <label class="form-label fw-bold text-maroon font-serif" style="color: #7A0C0C;">3. Fines, Grace Period & Payments Section</label>
                                    <div class="row g-2 mb-2">
                                        <div class="col-12">
                                            <input type="text" name="rules_sec3_title" class="form-control fw-bold" value="<?= escape(get_setting('rules_sec3_title', '3. Fines, Grace Period & Payments')) ?>" required>
                                        </div>
                                    </div>
                                    <label class="form-label small text-muted">Rule Items (one bullet per line):</label>
                                    <?php
                                    $defaultSec3 = "• A fine rate of <strong>₹" . number_format((float)get_setting('fine_per_day', '5.00'), 2) . " per day</strong> applies to overdue items after a <strong>" . get_setting('grace_period_days', '2') . "-day grace period</strong>.\n• All fine payments are collected in <strong>CASH</strong> at the librarian desk with an official printed cash receipt (<code>SL-RCP-000001</code>).";
                                    ?>
                                    <textarea name="rules_sec3_content" class="form-control font-monospace small" rows="3"><?= escape(get_setting('rules_sec3_content', $defaultSec3)) ?></textarea>
                                </div>

                                <!-- Rule Section 4 -->
                                <div class="mb-4 p-3 bg-light rounded border">
                                    <label class="form-label fw-bold text-maroon font-serif" style="color: #7A0C0C;">4. 15-Day Expiry & Restriction Rule Section</label>
                                    <div class="row g-2 mb-2">
                                        <div class="col-12">
                                            <input type="text" name="rules_sec4_title" class="form-control fw-bold" value="<?= escape(get_setting('rules_sec4_title', '4. Important 15-Day Expiry & Restriction Rule (Rule #43)')) ?>" required>
                                        </div>
                                    </div>
                                    <label class="form-label small text-muted">Rule Items (one bullet per line):</label>
                                    <textarea name="rules_sec4_content" class="form-control font-monospace small" rows="4"><?= escape(get_setting('rules_sec4_content', "• If a membership has expired for <strong>MORE THAN 15 DAYS</strong>, the account status automatically shifts to <code>RESTRICTED</code>.\n• Restricted members cannot borrow new books or access member features until membership renewal is completed.\n• <strong>Note:</strong> Librarians remain fully authorized to receive returned books and collect outstanding fines from restricted accounts.")) ?></textarea>
                                </div>

                                <!-- Rule Section 5 -->
                                <div class="mb-4 p-3 bg-light rounded border">
                                    <label class="form-label fw-bold text-maroon font-serif" style="color: #7A0C0C;">5. Digital PDF Library Guidelines Section</label>
                                    <div class="row g-2 mb-2">
                                        <div class="col-12">
                                            <input type="text" name="rules_sec5_title" class="form-control fw-bold" value="<?= escape(get_setting('rules_sec5_title', '5. Digital PDF Library Guidelines')) ?>" required>
                                        </div>
                                    </div>
                                    <label class="form-label small text-muted">Rule Items (one bullet per line):</label>
                                    <textarea name="rules_sec5_content" class="form-control font-monospace small" rows="3"><?= escape(get_setting('rules_sec5_content', "• Members are granted online in-browser reading privileges for digital PDFs.\n• <strong>Downloading or printing PDFs is strictly prohibited for members.</strong> Backend authorization will block direct file downloads.")) ?></textarea>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="save_rules_cms" class="btn btn-maroon btn-lg w-100 font-serif fw-bold py-3" style="background-color: #7A0C0C;">
                            <i class="fas fa-save me-2"></i> Save User Rules & Regulations Content
                        </button>
                    </form>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 4: INSTITUTIONAL PAGES & DONATIONS CMS           -->
                <!-- ==================================================== -->
                <div class="tab-pane fade <?= $activeTab === 'institutional' ? 'show active' : '' ?>" id="tab-institutional" role="tabpanel">
                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                        <!-- Part A: Our Journey Page -->
                        <div class="card sayak-card mb-4 border-top border-4 border-maroon">
                            <div class="card-header bg-white font-serif py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="fw-bold fs-5 text-maroon" style="color: #7A0C0C;">
                                    <i class="fas fa-history me-2"></i> Our Journey & Legacy Content (our-journey.php)
                                </div>
                                <a href="<?= BASE_URL ?>our-journey.php" target="_blank" class="btn btn-outline-maroon btn-sm font-serif">
                                    <i class="fas fa-external-link-alt me-1"></i> View Live Journey Page
                                </a>
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Journey Page Lead Subtitle</label>
                                    <input type="text" name="journey_lead" class="form-control" value="<?= escape(get_setting('journey_lead', 'Tracing three decades of educational dedication, literature preservation, and community empowerment.')) ?>">
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">1995 Foundation Milestone Title</label>
                                        <input type="text" name="journey_1995_title" class="form-control" value="<?= escape(get_setting('journey_1995_title', '1995: The Foundation')) ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small">1995 Foundation Narrative</label>
                                        <textarea name="journey_1995_desc" class="form-control" rows="2"><?= escape(get_setting('journey_1995_desc', 'SAYAK LIBRARY was founded in 1995 by a group of visionary scholars and educators in Kolkata with a initial collection of 1,200 books. The vision was simple yet powerful: to create an accessible repository of learning for all citizens regardless of economic background.')) ?></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">2008 Expansion Milestone Title</label>
                                        <input type="text" name="journey_2008_title" class="form-control" value="<?= escape(get_setting('journey_2008_title', '2008: Academic Expansion')) ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small">2008 Expansion Narrative</label>
                                        <textarea name="journey_2008_desc" class="form-control" rows="2"><?= escape(get_setting('journey_2008_desc', 'With increasing enrollment of competitive examination aspirants and school students, the library introduced dedicated Higher Secondary and Civil Services prep wings, partnering with major Indian publishers.')) ?></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Present Day Milestone Title</label>
                                        <input type="text" name="journey_present_title" class="form-control" value="<?= escape(get_setting('journey_present_title', 'Present Day: Digital & Physical Integration')) ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small">Present Day Narrative</label>
                                        <textarea name="journey_present_desc" class="form-control" rows="2"><?= escape(get_setting('journey_present_desc', 'Today, Sayak Library houses over 25,000 physical volumes and an integrated Digital PDF Library, serving thousands of registered members with barcoded lending and in-browser e-learning access.')) ?></textarea>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold small">Core Mission Highlight Text</label>
                                        <textarea name="journey_mission" class="form-control" rows="2"><?= escape(get_setting('journey_mission', 'To foster lifelong learning, preserve Bengali and Indian literary heritage, and equip students with modern digital resources in a quiet, modern academic environment.')) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Part B: Governance Page -->
                        <div class="card sayak-card mb-4 border-start border-4 border-primary">
                            <div class="card-header bg-white font-serif py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="fw-bold fs-5 text-dark">
                                    <i class="fas fa-balance-scale text-primary me-2"></i> Institutional Governance (governance.php)
                                </div>
                                <a href="<?= BASE_URL ?>governance.php" target="_blank" class="btn btn-outline-secondary btn-sm font-serif">
                                    <i class="fas fa-external-link-alt me-1"></i> View Live Governance Page
                                </a>
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Governance Lead Subtitle</label>
                                    <input type="text" name="governance_lead" class="form-control" value="<?= escape(get_setting('governance_lead', 'Standards of transparency, financial accountability, and operational integrity.')) ?>">
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Regulatory Compliance Title</label>
                                        <input type="text" name="gov_comp_title" class="form-control" value="<?= escape(get_setting('gov_comp_title', 'Regulatory Compliance')) ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small">Regulatory Compliance Text</label>
                                        <textarea name="gov_comp_desc" class="form-control" rows="2"><?= escape(get_setting('gov_comp_desc', 'SAYAK LIBRARY operates in full compliance with public society registration acts and West Bengal library management standards.')) ?></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Cash Audit Title</label>
                                        <input type="text" name="gov_audit_title" class="form-control" value="<?= escape(get_setting('gov_audit_title', 'Cash Audit & Financial Receipts')) ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small">Cash Audit Text</label>
                                        <textarea name="gov_audit_desc" class="form-control" rows="2"><?= escape(get_setting('gov_audit_desc', 'All membership fees, renewals, and fine collections are recorded with unique system transaction codes (e.g. SL-TXN-000001) and audited quarterly by independent chartered accountants.')) ?></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Digital Security Title</label>
                                        <input type="text" name="gov_sec_title" class="form-control" value="<?= escape(get_setting('gov_sec_title', 'Digital Data Security')) ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small">Digital Security Text</label>
                                        <textarea name="gov_sec_desc" class="form-control" rows="2"><?= escape(get_setting('gov_sec_desc', 'We enforce strict data protection policies. Member credentials are stored using password_hash(), digital PDFs are protected against unauthorized redistribution, and user activity is logged via secure audit channels.')) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Part C: Academic Collaborations -->
                        <div class="card sayak-card mb-4 border-start border-4 border-success">
                            <div class="card-header bg-white font-serif py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="fw-bold fs-5 text-dark">
                                    <i class="fas fa-handshake text-success me-2"></i> Academic Collaborations (academic-collaborations.php)
                                </div>
                                <a href="<?= BASE_URL ?>academic-collaborations.php" target="_blank" class="btn btn-outline-secondary btn-sm font-serif">
                                    <i class="fas fa-external-link-alt me-1"></i> View Live Collaborations Page
                                </a>
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Collaborations Page Subtitle</label>
                                    <input type="text" name="collab_lead" class="form-control" value="<?= escape(get_setting('collab_lead', 'Partnerships with universities, research institutes, and educational publishers.')) ?>">
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Partnership 1 Title</label>
                                        <input type="text" name="collab_1_title" class="form-control" value="<?= escape(get_setting('collab_1_title', 'University Inter-Library Loan')) ?>">
                                        <label class="form-label small text-muted mt-1">Partnership 1 Details:</label>
                                        <textarea name="collab_1_desc" class="form-control" rows="2"><?= escape(get_setting('collab_1_desc', 'Collaborative borrowing privileges with regional universities for postgraduate and doctoral research scholars.')) ?></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Partnership 2 Title</label>
                                        <input type="text" name="collab_2_title" class="form-control" value="<?= escape(get_setting('collab_2_title', 'Competitive Exam Academies')) ?>">
                                        <label class="form-label small text-muted mt-1">Partnership 2 Details:</label>
                                        <textarea name="collab_2_desc" class="form-control" rows="2"><?= escape(get_setting('collab_2_desc', 'Resource sharing agreements providing updated test series and reference books for WBCS and Civil Services aspirants.')) ?></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Partnership 3 Title</label>
                                        <input type="text" name="collab_3_title" class="form-control" value="<?= escape(get_setting('collab_3_title', 'National Digital Library Partner')) ?>">
                                        <label class="form-label small text-muted mt-1">Partnership 3 Details:</label>
                                        <textarea name="collab_3_desc" class="form-control" rows="2"><?= escape(get_setting('collab_3_desc', 'Access integration with open educational repositories and digital learning archives.')) ?></textarea>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Partnership 4 Title</label>
                                        <input type="text" name="collab_4_title" class="form-control" value="<?= escape(get_setting('collab_4_title', 'Publishing Houses')) ?>">
                                        <label class="form-label small text-muted mt-1">Partnership 4 Details:</label>
                                        <textarea name="collab_4_desc" class="form-control" rows="2"><?= escape(get_setting('collab_4_desc', 'Direct procurement partnerships with Ananda Publishers, Oxford University Press, S. Chand, and McGraw Hill.')) ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Part D: Donations Appeal & Bank Details -->
                        <div class="card sayak-card mb-4 border-start border-4 border-warning">
                            <div class="card-header bg-white font-serif py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="fw-bold fs-5 text-dark">
                                    <i class="fas fa-hand-holding-heart text-warning me-2"></i> Donations Appeal & Bank / UPI Transfer Details (donate.php)
                                </div>
                                <a href="<?= BASE_URL ?>donate.php" target="_blank" class="btn btn-outline-warning btn-sm font-serif text-dark fw-bold">
                                    <i class="fas fa-external-link-alt me-1"></i> View Live Donations Page
                                </a>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Donation Appeal Headline</label>
                                        <input type="text" name="donate_appeal_title" class="form-control" value="<?= escape(get_setting('donate_appeal_title', 'Donate to Sayak Library')) ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small">Donation Appeal Description</label>
                                        <textarea name="donate_appeal_desc" class="form-control" rows="2"><?= escape(get_setting('donate_appeal_desc', 'Your contributions directly support book restoration, student scholarships, rare manuscript preservation, and e-learning resources.')) ?></textarea>
                                    </div>

                                    <div class="col-12"><hr class="my-2"><strong class="font-serif text-maroon" style="color: #7A0C0C;">Direct Bank & UPI Transfer Information</strong></div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Bank Name & Branch</label>
                                        <input type="text" name="donate_bank_name" class="form-control" value="<?= escape(get_setting('donate_bank_name', 'State Bank of India (College Street Branch)')) ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Bank Account Number</label>
                                        <input type="text" name="donate_account_no" class="form-control" value="<?= escape(get_setting('donate_account_no', '38491029384')) ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">IFSC Code</label>
                                        <input type="text" name="donate_ifsc" class="form-control" value="<?= escape(get_setting('donate_ifsc', 'SBIN0001234')) ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Official UPI ID</label>
                                        <input type="text" name="donate_upi_id" class="form-control" value="<?= escape(get_setting('donate_upi_id', 'sayaklibrary@sbi')) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="save_institutional_cms" class="btn btn-maroon btn-lg w-100 font-serif fw-bold py-3" style="background-color: #7A0C0C;">
                            <i class="fas fa-save me-2"></i> Save Institutional Content & Donation Settings
                        </button>
                    </form>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 5: GOVERNING BODY MEMBERS (DYNAMIC CMS)          -->
                <!-- ==================================================== -->
                <div class="tab-pane fade <?= $activeTab === 'govbody' ? 'show active' : '' ?>" id="tab-govbody" role="tabpanel">
                    <div class="card sayak-card border-top border-4 border-maroon mb-4">
                        <div class="card-header bg-white font-serif py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="fw-bold mb-0 text-maroon" style="color: #7A0C0C;">
                                    <i class="fas fa-users-cog me-2"></i> Governing Body Council Roster
                                </h5>
                                <small class="text-muted">Manage executive titles, add custom designations, and upload official portrait photos for the public Governing Body page.</small>
                            </div>
                            <button type="button" class="btn btn-maroon font-serif fw-bold btn-sm" onclick="openAddGovModal()" style="background-color: #7A0C0C;">
                                <i class="fas fa-user-plus me-1"></i> Add New Member
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($govMembers)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 70px;">Order</th>
                                                <th style="width: 80px;">Photo</th>
                                                <th>Member Name & Bio</th>
                                                <th>Official Designation</th>
                                                <th>Status</th>
                                                <th class="text-end pe-4" style="width: 140px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($govMembers as $m): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-light text-dark border font-monospace"><?= (int)$m['sort_order'] ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($m['photo']) && file_exists(ROOT_PATH . 'uploads/governing_body/' . $m['photo'])): ?>
                                                            <img src="<?= BASE_URL ?>uploads/governing_body/<?= escape($m['photo']) ?>" alt="<?= escape($m['name']) ?>" class="rounded-circle border border-2 border-maroon shadow-sm" style="width: 50px; height: 50px; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border" style="width: 50px; height: 50px;">
                                                                <i class="fas <?= escape($m['icon'] ?: 'fa-user-tie') ?> text-secondary fa-lg"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong class="font-serif text-dark fs-6 d-block"><?= escape($m['name']) ?></strong>
                                                        <small class="text-muted d-block"><?= escape($m['description'] ?: 'No biography entered.') ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge font-serif px-2 py-1" style="background-color: #FFF2F2; color: #7A0C0C; border: 1px solid #7A0C0C; font-size: 12px;">
                                                            <?= escape($m['designation']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($m['status'] === 'Active'): ?>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Inactive</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <div class="btn-group btn-group-sm">
                                                            <button type="button" class="btn btn-outline-maroon" onclick='editGovMember(<?= json_encode($m) ?>)' title="Edit Member">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <form action="" method="POST" class="d-inline" onsubmit="return confirm('Remove <?= escape(addslashes($m['name'])) ?> from the governing body?');">
                                                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                                <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
                                                                <button type="submit" name="delete_gov_member" class="btn btn-outline-danger" title="Delete Member">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="fas fa-users-slash fa-3x mb-3 text-secondary"></i>
                                    <h6>No Governing Body members configured.</h6>
                                    <button type="button" class="btn btn-maroon btn-sm mt-2" onclick="openAddGovModal()" style="background-color: #7A0C0C;">Add First Member</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL: ADD / EDIT GOVERNING BODY MEMBER                      -->
<!-- ============================================================ -->
<div class="modal fade" id="govMemberModal" tabindex="-1" aria-labelledby="govMemberModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white" style="background-color: #7A0C0C;">
                <h5 class="modal-title font-serif" id="govMemberModalTitle">Add New Governing Body Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="member_id" id="gov_member_id" value="0">

                    <div class="row g-3">
                        <!-- Full Name -->
                        <div class="col-md-7">
                            <label class="form-label fw-bold small">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="gov_name" class="form-control" placeholder="e.g. Prof. Subhash Chandra Ghosh" required>
                        </div>

                        <!-- Designation -->
                        <div class="col-md-5">
                            <label class="form-label fw-bold small">Designation / Title <span class="text-danger">*</span></label>
                            <input type="text" name="designation" id="gov_designation" class="form-control" list="designationSuggestions" placeholder="e.g. President, Governing Body" required>
                            <datalist id="designationSuggestions">
                                <option value="President, Governing Body">
                                <option value="Vice President">
                                <option value="General Secretary">
                                <option value="Treasurer & Finance Controller">
                                <option value="Ex-Officio Member Secretary">
                                <option value="Executive Committee Member">
                                <option value="Academic Advisor">
                                <option value="Legal Advisor">
                                <option value="Chief Librarian">
                                <option value="Trustee">
                                <option value="Patron">
                            </datalist>
                            <small class="text-muted">You can select or type any custom designation.</small>
                        </div>

                        <!-- Description / Bio -->
                        <div class="col-12">
                            <label class="form-label fw-bold small">Bio / Department / Qualifications</label>
                            <textarea name="description" id="gov_description" class="form-control" rows="2" placeholder="e.g. Senior Library Science Specialist & Academician."></textarea>
                        </div>

                        <!-- Photo Upload with Live Preview -->
                        <div class="col-12">
                            <label class="form-label fw-bold small">Member Official Portrait Photo (Optional)</label>
                            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded border">
                                <div id="govPhotoPreviewBox" class="rounded-circle border border-2 border-maroon bg-white d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 70px; height: 70px; overflow: hidden;">
                                    <i class="fas fa-user-tie fa-2x text-muted" id="govPhotoPlaceholderIcon"></i>
                                    <img id="govPhotoPreviewImg" src="" alt="Preview" class="d-none w-100 h-100" style="object-fit: cover;">
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file" name="photo" id="gov_photo" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp,image/jpg" onchange="previewGovPhoto(this)">
                                    <div class="d-flex justify-content-between align-items-center mt-1">
                                        <small class="text-muted">Recommended: Square or portrait image (JPG, PNG, WEBP). Max 2MB.</small>
                                        <div id="govRemovePhotoBox" class="d-none">
                                            <div class="form-check form-check-inline mb-0">
                                                <input class="form-check-input" type="checkbox" name="remove_photo" id="gov_remove_photo" value="1">
                                                <label class="form-check-label text-danger small fw-semibold" for="gov_remove_photo">Remove Photo</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fallback Avatar Icon -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Fallback Avatar Icon</label>
                            <select name="icon" id="gov_icon" class="form-select form-select-sm">
                                <option value="fa-user-tie">Executive / Tie (fa-user-tie)</option>
                                <option value="fa-user-graduate">Scholar / Academic (fa-user-graduate)</option>
                                <option value="fa-user-shield">Trustee / Shield (fa-user-shield)</option>
                                <option value="fa-user-tag">Administrator / Officer (fa-user-tag)</option>
                                <option value="fa-award">Honorary / Award (fa-award)</option>
                                <option value="fa-user">Standard Profile (fa-user)</option>
                            </select>
                            <small class="text-muted">Used if no photo is uploaded.</small>
                        </div>

                        <!-- Sort Order -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Display Order Sequence</label>
                            <input type="number" name="sort_order" id="gov_sort_order" class="form-control form-control-sm" value="1" min="0">
                            <small class="text-muted">Lower numbers appear first.</small>
                        </div>

                        <!-- Status -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Publication Status</label>
                            <select name="status" id="gov_status" class="form-select form-select-sm">
                                <option value="Active">Active (Visible on Website)</option>
                                <option value="Inactive">Inactive (Hidden)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_gov_member" class="btn btn-maroon font-serif fw-bold" style="background-color: #7A0C0C;">
                        <i class="fas fa-save me-1"></i> Save Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddGovModal() {
    document.getElementById('govMemberModalTitle').innerText = 'Add New Governing Body Member';
    document.getElementById('gov_member_id').value = '0';
    document.getElementById('gov_name').value = '';
    document.getElementById('gov_designation').value = '';
    document.getElementById('gov_description').value = '';
    document.getElementById('gov_icon').value = 'fa-user-tie';
    document.getElementById('gov_sort_order').value = '<?= count($govMembers) + 1 ?>';
    document.getElementById('gov_status').value = 'Active';

    // Reset photo
    document.getElementById('gov_photo').value = '';
    var preview = document.getElementById('govPhotoPreviewImg');
    var icon = document.getElementById('govPhotoPlaceholderIcon');
    if (preview) { preview.src = ''; preview.classList.add('d-none'); }
    if (icon) icon.classList.remove('d-none');
    var removeBox = document.getElementById('govRemovePhotoBox');
    if (removeBox) removeBox.classList.add('d-none');
    var removeCheck = document.getElementById('gov_remove_photo');
    if (removeCheck) removeCheck.checked = false;

    var modal = new bootstrap.Modal(document.getElementById('govMemberModal'));
    modal.show();
}

function editGovMember(m) {
    document.getElementById('govMemberModalTitle').innerText = 'Edit Governing Body Member';
    document.getElementById('gov_member_id').value = m.id;
    document.getElementById('gov_name').value = m.name;
    document.getElementById('gov_designation').value = m.designation;
    document.getElementById('gov_description').value = m.description || '';
    document.getElementById('gov_icon').value = m.icon || 'fa-user-tie';
    document.getElementById('gov_sort_order').value = m.sort_order;
    document.getElementById('gov_status').value = m.status;

    // Handle photo preview
    document.getElementById('gov_photo').value = '';
    var preview = document.getElementById('govPhotoPreviewImg');
    var icon = document.getElementById('govPhotoPlaceholderIcon');
    var removeBox = document.getElementById('govRemovePhotoBox');
    var removeCheck = document.getElementById('gov_remove_photo');
    if (removeCheck) removeCheck.checked = false;

    if (m.photo) {
        preview.src = '<?= BASE_URL ?>uploads/governing_body/' + m.photo;
        preview.classList.remove('d-none');
        if (icon) icon.classList.add('d-none');
        if (removeBox) removeBox.classList.remove('d-none');
    } else {
        preview.src = '';
        preview.classList.add('d-none');
        if (icon) icon.classList.remove('d-none');
        if (removeBox) removeBox.classList.add('d-none');
    }

    var modal = new bootstrap.Modal(document.getElementById('govMemberModal'));
    modal.show();
}

function previewGovPhoto(input) {
    var preview = document.getElementById('govPhotoPreviewImg');
    var icon = document.getElementById('govPhotoPlaceholderIcon');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            if (icon) icon.classList.add('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
