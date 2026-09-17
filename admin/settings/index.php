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
            'donate_bank_name', 'donate_account_no', 'donate_ifsc', 'donate_upi_id', 'donate_appeal_title', 'donate_appeal_desc',
            'social_facebook', 'social_twitter', 'social_instagram',
            'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass', 'smtp_from_name', 'smtp_from_email',
            'catalog_mode', 'catalog_sheet_url', 'catalog_sheet_title', 'catalog_sheet_notice',
            'membership_mode', 'membership_pdf_url', 'membership_pdf_title', 'membership_pdf_notice', 'membership_pdf_instructions'
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

        // Handle Membership PDF Form File Upload
        if (isset($_FILES['membership_pdf_file']) && $_FILES['membership_pdf_file']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['membership_pdf_file']['tmp_name'];
            $origName = basename($_FILES['membership_pdf_file']['name']);
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                $uploadDir = ROOT_PATH . 'uploads/forms/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0755, true);
                }
                $filename = 'membership_application_form_' . time() . '.pdf';
                if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
                    // Remove old local file if exists
                    $oldFile = get_setting('membership_pdf_file', '');
                    if (!empty($oldFile) && file_exists($uploadDir . $oldFile)) {
                        @unlink($uploadDir . $oldFile);
                    }
                    $stmt = $db->prepare("
                        INSERT INTO system_settings (setting_key, setting_value)
                        VALUES ('membership_pdf_file', ?)
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                    ");
                    $stmt->execute([$filename]);
                }
            }
        }

        // Handle Membership PDF Form Removal
        if (!empty($_POST['remove_membership_pdf_file'])) {
            $oldFile = get_setting('membership_pdf_file', '');
            if (!empty($oldFile) && file_exists(ROOT_PATH . 'uploads/forms/' . $oldFile)) {
                @unlink(ROOT_PATH . 'uploads/forms/' . $oldFile);
            }
            $stmt = $db->prepare("UPDATE system_settings SET setting_value = '' WHERE setting_key = 'membership_pdf_file'");
            $stmt->execute();
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

    <form action="" method="POST" enctype="multipart/form-data">
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
                        <input type="text" name="library_name" class="form-control" value="<?= escape(get_setting('library_name', 'DAKSHINESWAR SHAYAK LIBRARY')) ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Established Year</label>
                        <input type="text" name="established_year" class="form-control" value="<?= escape(get_setting('established_year', '1996')) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Registration Number</label>
                        <input type="text" name="registration_no" class="form-control" value="<?= escape(get_setting('registration_no', 'SO087920')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold small">Library Address</label>
                        <textarea name="address" class="form-control" rows="2"><?= escape(get_setting('address', '11, Nepal Chandra Chatterjee Street, Ariadaha, Kolkata - 700057')) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Phone Helpline</label>
                        <input type="text" name="phone" class="form-control" value="<?= escape(get_setting('phone', '7595929232, 8420011218')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Official Email</label>
                        <input type="email" name="email" class="form-control" value="<?= escape(get_setting('email', 'dakshineswarshayak1997@gmail.com')) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Opening Hours</label>
                        <input type="text" name="opening_hours" class="form-control" value="<?= escape(get_setting('opening_hours', 'Monday - Saturday: 9:00 AM - 7:00 PM | Sunday: Closed')) ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section: Book Catalog Display Mode (Database vs Google Sheet) -->
        <div class="card sayak-card mb-4 border-start border-4 border-primary" id="catalog-settings">
            <div class="card-header bg-white font-serif py-3 fw-bold fs-5 text-primary d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <i class="fas fa-book-open me-2"></i> Public Book Catalog Display Mode
                </div>
                <span class="badge <?= get_setting('catalog_mode', 'database') === 'sheet' ? 'bg-warning text-dark' : 'bg-success text-white' ?> px-3 py-2 fs-6">
                    <i class="fas <?= get_setting('catalog_mode', 'database') === 'sheet' ? 'fa-file-excel' : 'fa-database' ?> me-1"></i> Current Mode: <?= get_setting('catalog_mode', 'database') === 'sheet' ? 'Google Sheet Mode (Interim)' : 'Live Database Mode (Active)' ?>
                </span>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-info py-2 px-3 small mb-4">
                    <i class="fas fa-lightbulb me-1"></i> <strong>How this works:</strong> While library staff members upload and catalog books in the database in the background, you can set the public catalog to show a Google Sheet containing all your book records. Once your catalog is ready, simply switch back to <strong>"Live Database Mode"</strong>!
                </div>

                <div class="row g-4">
                    <div class="col-12">
                        <label class="form-label fw-bold">Select Active Public Catalog Mode:</label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-check p-3 rounded border h-100 d-block <?= get_setting('catalog_mode', 'database') === 'database' ? 'border-success bg-light' : '' ?>" style="cursor: pointer;">
                                    <input class="form-check-input" type="radio" name="catalog_mode" id="mode_database" value="database" <?= get_setting('catalog_mode', 'database') === 'database' ? 'checked' : '' ?> onchange="toggleSheetSettings()">
                                    <span class="form-check-label ms-2 fw-bold text-dark d-block">
                                        <i class="fas fa-database text-success me-1"></i> Live Database Mode (Full System)
                                    </span>
                                    <small class="text-muted d-block mt-1 ps-4">
                                        Public users will see the real-time library database with book search, category filters, author details, PDF reader links, and available copies.
                                    </small>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="form-check p-3 rounded border h-100 d-block <?= get_setting('catalog_mode', 'database') === 'sheet' ? 'border-warning bg-light' : '' ?>" style="cursor: pointer;">
                                    <input class="form-check-input" type="radio" name="catalog_mode" id="mode_sheet" value="sheet" <?= get_setting('catalog_mode', 'database') === 'sheet' ? 'checked' : '' ?> onchange="toggleSheetSettings()">
                                    <span class="form-check-label ms-2 fw-bold text-dark d-block">
                                        <i class="fas fa-file-excel text-warning me-1"></i> Google Sheet Mode (Interim Catalog)
                                    </span>
                                    <small class="text-muted d-block mt-1 ps-4">
                                        Public users will see your embedded Google Sheet or spreadsheet. Admins & librarians can continue uploading books in the background.
                                    </small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="sheet_settings_panel" class="col-12" style="<?= get_setting('catalog_mode', 'database') === 'sheet' ? '' : 'display: none;' ?>">
                        <div class="p-3 bg-light rounded border border-warning">
                            <h6 class="fw-bold text-dark mb-3"><i class="fab fa-google text-danger me-2"></i> Google Sheet Catalog Configuration</h6>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold small">Google Sheet / Spreadsheet Public URL <span class="text-danger">*</span></label>
                                    <input type="url" name="catalog_sheet_url" class="form-control font-monospace" placeholder="https://docs.google.com/spreadsheets/d/.../edit?usp=sharing" value="<?= escape(get_setting('catalog_sheet_url', '')) ?>">
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle text-primary me-1"></i> Paste the sharing link of your Google Sheet. Make sure the sheet's Share permission is set to <strong>"Anyone with the link can view"</strong>. Standard Google Sheet links are automatically formatted for clean embedding.
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Public Page Title</label>
                                    <input type="text" name="catalog_sheet_title" class="form-control" value="<?= escape(get_setting('catalog_sheet_title', 'Library Books & Catalog Resources')) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold small">Public Notice / Message to Visitors</label>
                                    <textarea name="catalog_sheet_notice" class="form-control" rows="2"><?= escape(get_setting('catalog_sheet_notice', 'Our physical library collection is currently being digitized into this portal. In the meantime, please browse our complete book list, titles, and links in the live spreadsheet below.')) ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section: User Signup & Membership Mode (Online vs Offline PDF Form) -->
        <div class="card sayak-card mb-4 border-start border-4 border-info" id="membership-settings">
            <div class="card-header bg-white font-serif py-3 fw-bold fs-5 text-info d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <i class="fas fa-id-card me-2"></i> User Signup & Membership Application Mode
                </div>
                <span class="badge <?= get_setting('membership_mode', 'online') === 'pdf' ? 'bg-warning text-dark' : 'bg-success text-white' ?> px-3 py-2 fs-6">
                    <i class="fas <?= get_setting('membership_mode', 'online') === 'pdf' ? 'fa-file-pdf' : 'fa-globe' ?> me-1"></i> Current Mode: <?= get_setting('membership_mode', 'online') === 'pdf' ? 'Offline PDF Form Mode (Active)' : 'Online Registration Mode (Active)' ?>
                </span>
            </div>
            <div class="card-body p-4">
                <div class="alert alert-info py-2 px-3 small mb-4">
                    <i class="fas fa-info-circle me-1"></i> <strong>Mode Explanation:</strong> If you do not want public users to register accounts online right now, select <strong>"Offline PDF Form Mode"</strong>. Visitors will only be able to download/print your physical membership application form, while administrators can continue adding/approving members in the background.
                </div>

                <div class="row g-4">
                    <div class="col-12">
                        <label class="form-label fw-bold">Select Active Signup Mode:</label>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-check p-3 rounded border h-100 d-block <?= get_setting('membership_mode', 'online') === 'online' ? 'border-success bg-light' : '' ?>" style="cursor: pointer;">
                                    <input class="form-check-input" type="radio" name="membership_mode" id="mode_online" value="online" <?= get_setting('membership_mode', 'online') === 'online' ? 'checked' : '' ?> onchange="toggleMembershipSettings()">
                                    <span class="form-check-label ms-2 fw-bold text-dark d-block">
                                        <i class="fas fa-globe text-success me-1"></i> Online Digital Registration (Full Web Form)
                                    </span>
                                    <small class="text-muted d-block mt-1 ps-4">
                                        Visitors can fill in their details, select plans, upload profile photos, and submit membership applications directly through the website.
                                    </small>
                                </label>
                            </div>
                            <div class="col-md-6">
                                <label class="form-check p-3 rounded border h-100 d-block <?= get_setting('membership_mode', 'online') === 'pdf' ? 'border-warning bg-light' : '' ?>" style="cursor: pointer;">
                                    <input class="form-check-input" type="radio" name="membership_mode" id="mode_pdf" value="pdf" <?= get_setting('membership_mode', 'online') === 'pdf' ? 'checked' : '' ?> onchange="toggleMembershipSettings()">
                                    <span class="form-check-label ms-2 fw-bold text-dark d-block">
                                        <i class="fas fa-file-pdf text-danger me-1"></i> Offline PDF Form Mode (Manual Download & Counter Submission)
                                    </span>
                                    <small class="text-muted d-block mt-1 ps-4">
                                        Online form is disabled. Visitors see an official download page to get the printable PDF application form and counter submission instructions.
                                    </small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div id="membership_pdf_settings_panel" class="col-12" style="<?= get_setting('membership_mode', 'online') === 'pdf' ? '' : 'display: none;' ?>">
                        <div class="p-3 bg-light rounded border border-warning">
                            <h6 class="fw-bold text-dark mb-3"><i class="fas fa-file-pdf text-danger me-2"></i> PDF Membership Form Configuration</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Upload Printable PDF Membership Form (.pdf)</label>
                                    <input type="file" name="membership_pdf_file" class="form-control" accept=".pdf,application/pdf">
                                    <?php 
                                    $currentPdf = get_setting('membership_pdf_file', '');
                                    if (!empty($currentPdf) && file_exists(ROOT_PATH . 'uploads/forms/' . $currentPdf)): 
                                    ?>
                                        <div class="d-flex align-items-center mt-2 p-2 bg-white rounded border">
                                            <i class="fas fa-file-pdf text-danger fa-2x me-2"></i>
                                            <div class="flex-grow-1 text-truncate">
                                                <div class="small fw-bold text-dark text-truncate"><?= escape($currentPdf) ?></div>
                                                <a href="<?= BASE_URL ?>uploads/forms/<?= escape($currentPdf) ?>" target="_blank" class="small text-primary me-3"><i class="fas fa-external-link-alt me-1"></i> View Current PDF</a>
                                            </div>
                                            <div class="form-check mb-0">
                                                <input class="form-check-input" type="checkbox" name="remove_membership_pdf_file" value="1" id="removePdfCheck">
                                                <label class="form-check-label text-danger small fw-bold" for="removePdfCheck">Remove</label>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <small class="text-muted">No PDF file uploaded yet. Upload your official library registration form.</small>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">OR External PDF Form Link (Google Drive / Cloud)</label>
                                    <input type="url" name="membership_pdf_url" class="form-control font-monospace" placeholder="https://drive.google.com/... or https://..." value="<?= escape(get_setting('membership_pdf_url', '')) ?>">
                                    <small class="text-muted">Used if no local PDF is uploaded, or as an alternative cloud download link.</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Application Page Title</label>
                                    <input type="text" name="membership_pdf_title" class="form-control" value="<?= escape(get_setting('membership_pdf_title', 'Sayak Library Membership Application Form')) ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Public Announcement / Notice</label>
                                    <input type="text" name="membership_pdf_notice" class="form-control" value="<?= escape(get_setting('membership_pdf_notice', 'Online user registration is currently offline. Prospective members are kindly requested to download the official membership form, fill it out, and submit it at the library desk.')) ?>">
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold small">Counter Submission Instructions & Guidelines for Applicants</label>
                                    <textarea name="membership_pdf_instructions" class="form-control" rows="3"><?= escape(get_setting('membership_pdf_instructions', "1. Print the downloaded form on clean A4 white paper.\n2. Complete all fields in CAPITAL letters and attach 2 recent passport-size photographs.\n3. Attach photocopies of Valid Photo ID Proof (Aadhaar / Voter ID / Student ID) and Address Proof.\n4. Submit the completed form along with membership fee at the library counter during official hours.\n5. Your library membership card and access credentials will be issued upon counter verification.")) ?></textarea>
                                    <small class="text-muted">Each line will be formatted as a step in the application instructions.</small>
                                </div>
                            </div>
                        </div>
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

        <!-- Section: Official Bank & UPI Transfer Details -->
        <div class="card sayak-card mb-4 border-start border-4 border-success">
            <div class="card-header bg-white font-serif py-3 fw-bold fs-5 text-success">
                <i class="fas fa-university me-2"></i> Official Direct Bank & UPI Transfer Details (Donation & Payment Info)
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Bank Name & Branch</label>
                        <input type="text" name="donate_bank_name" class="form-control" value="<?= escape(get_setting('donate_bank_name', 'State Bank of India (College Street Branch)')) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Bank Account Number</label>
                        <input type="text" name="donate_account_no" class="form-control font-monospace" value="<?= escape(get_setting('donate_account_no', '38491029384')) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small">IFSC Code</label>
                        <input type="text" name="donate_ifsc" class="form-control font-monospace" value="<?= escape(get_setting('donate_ifsc', 'SBIN0001234')) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Official UPI ID</label>
                        <input type="text" name="donate_upi_id" class="form-control font-monospace" value="<?= escape(get_setting('donate_upi_id', 'sayaklibrary@sbi')) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Donation Appeal Headline</label>
                        <input type="text" name="donate_appeal_title" class="form-control" value="<?= escape(get_setting('donate_appeal_title', 'Donate to Sayak Library')) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Donation Appeal Description</label>
                        <input type="text" name="donate_appeal_desc" class="form-control" value="<?= escape(get_setting('donate_appeal_desc', 'Your contributions directly support book restoration, student scholarships, rare manuscript preservation, and e-learning resources.')) ?>">
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
<script>
function toggleSheetSettings() {
    var isSheet = document.getElementById('mode_sheet').checked;
    var panel = document.getElementById('sheet_settings_panel');
    if (panel) {
        panel.style.display = isSheet ? 'block' : 'none';
    }
}

function toggleMembershipSettings() {
    var isPdf = document.getElementById('mode_pdf').checked;
    var panel = document.getElementById('membership_pdf_settings_panel');
    if (panel) {
        panel.style.display = isPdf ? 'block' : 'none';
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
