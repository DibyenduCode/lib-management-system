<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

require_role('SUPER_ADMIN');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $settingsToSave = [
            'library_name', 'established_year', 'registration_no', 'address', 'phone', 'email',
            'opening_hours', 'fine_per_day', 'grace_period_days',
            'social_facebook', 'social_twitter', 'social_instagram',
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from_name', 'smtp_from_email'
        ];

        foreach ($settingsToSave as $key) {
            if (isset($_POST[$key])) {
                $val = sanitize_input($_POST[$key]);
                $stmt = $db->prepare("
                    INSERT INTO system_settings (setting_key, setting_value)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                ");
                $stmt->execute([$key, $val]);
            }
        }

        log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Update System Settings', 'Settings', "System configuration saved.");
        set_flash_message('success', 'System settings saved successfully.');
        header("Location: " . BASE_URL . "admin/settings/index.php");
        exit();
    }
}

$pageTitle = "System Settings";
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="section-title mb-1">System & Fine Settings</h2>
                    <p class="text-muted small mb-0">Configure library identity, fine calculation rules, grace periods, app links, and SMTP mail credentials.</p>
                </div>
            </div>

    <form action="" method="POST">
        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

        <!-- Section 1: Library Identity -->
        <div class="card sayak-card mb-4">
            <div class="card-header bg-maroon text-white font-serif py-3 fw-bold" style="background-color: #7A0C0C;">
                <i class="fas fa-university me-2"></i> Library Institutional Identity
            </div>
            <div class="card-body p-4">
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
                    <div class="col-12">
                        <label class="form-label fw-bold small">Library Address</label>
                        <textarea name="address" class="form-control" rows="2"><?= escape(get_setting('address', 'College Street, Kolkata')) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Phone Helpline</label>
                        <input type="text" name="phone" class="form-control" value="<?= escape(get_setting('phone', '+91 33 2241 8900')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Official Email</label>
                        <input type="email" name="email" class="form-control" value="<?= escape(get_setting('email', 'info@sayaklibrary.org')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Opening Hours</label>
                        <input type="text" name="opening_hours" class="form-control" value="<?= escape(get_setting('opening_hours', 'Monday - Saturday: 9 AM - 7 PM')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Fine & Grace Rules -->
        <div class="card sayak-card mb-4 border-start border-4 border-warning">
            <div class="card-header bg-white font-serif py-3 fw-bold fs-5">
                <i class="fas fa-gavel text-warning me-2"></i> Late Fine & Grace Period Rules
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Fine Rate Per Overdue Day (₹ INR)</label>
                        <input type="number" name="fine_per_day" class="form-control" step="0.5" value="<?= escape(get_setting('fine_per_day', '5.00')) ?>" required>
                        <small class="text-muted">Rate charged per day after grace period expires.</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Grace Period (Days)</label>
                        <input type="number" name="grace_period_days" class="form-control" min="0" max="30" value="<?= escape(get_setting('grace_period_days', '2')) ?>" required>
                        <small class="text-muted">No fine is charged if book is returned within grace period days.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Social Media Integration -->
        <div class="card sayak-card mb-4">
            <div class="card-header bg-white font-serif py-3 fw-bold fs-5">
                <i class="fas fa-share-alt text-primary me-2"></i> Social Media Integration
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Facebook Page URL</label>
                        <input type="url" name="social_facebook" class="form-control" value="<?= escape(get_setting('social_facebook', '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Twitter / X URL</label>
                        <input type="url" name="social_twitter" class="form-control" value="<?= escape(get_setting('social_twitter', '')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Instagram URL</label>
                        <input type="url" name="social_instagram" class="form-control" value="<?= escape(get_setting('social_instagram', '')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: SMTP Mail Credentials -->
        <div class="card sayak-card mb-4 border-start border-4 border-danger">
            <div class="card-header bg-white font-serif py-3 fw-bold fs-5">
                <i class="fas fa-envelope-open-text text-danger me-2"></i> SMTP Email Server Settings
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">SMTP Host Server</label>
                        <input type="text" name="smtp_host" class="form-control" value="<?= escape(get_setting('smtp_host', 'mail.sayaklibrary.org')) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small">SMTP Port</label>
                        <input type="text" name="smtp_port" class="form-control" value="<?= escape(get_setting('smtp_port', '587')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">SMTP Username</label>
                        <input type="text" name="smtp_user" class="form-control" value="<?= escape(get_setting('smtp_user', 'no-reply@sayaklibrary.org')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">SMTP Password</label>
                        <input type="password" name="smtp_pass" class="form-control" value="<?= escape(get_setting('smtp_pass', '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">From Sender Name</label>
                        <input type="text" name="smtp_from_name" class="form-control" value="<?= escape(get_setting('smtp_from_name', 'Sayak Library Administration')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">From Sender Email</label>
                        <input type="email" name="smtp_from_email" class="form-control" value="<?= escape(get_setting('smtp_from_email', 'no-reply@sayaklibrary.org')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" name="save_settings" class="btn btn-maroon btn-lg w-100 font-serif fw-bold py-3" style="background-color: #7A0C0C;">
            <i class="fas fa-save me-2"></i> Save All System Settings
        </button>
    </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
