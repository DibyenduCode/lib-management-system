<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

require_role('SUPER_ADMIN');

$db = getDB();
$errors = [];
$activeTab = sanitize_input($_GET['tab'] ?? 'contact');
$allowedTabs = ['contact', 'branding', 'rules', 'institutional', 'govbody', 'collaborations'];
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
            'address', 'opening_hours', 'map_embed_url', 'google_maps_link',
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
            'donate_appeal_title', 'donate_appeal_desc', 'donate_upi_id',
            'about_subtitle', 'about_title', 'about_para1', 'about_mission_quote',
            'about_physical_count', 'about_digital_count',
            'facility_1', 'facility_2', 'facility_3', 'facility_4',
            'home_collections_subtitle', 'home_collections_title',
            'home_membership_subtitle', 'home_membership_title',
            'footer_about'
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
        $rulesKeys = ['rules_lead'];
        for ($i = 1; $i <= 19; $i++) {
            $rulesKeys[] = "rule_{$i}_en";
            $rulesKeys[] = "rule_{$i}_bn";
        }

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
        // Handle Governance PDF File Upload
        if (isset($_FILES['governance_pdf_file']) && $_FILES['governance_pdf_file']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['governance_pdf_file']['tmp_name'];
            $origName = basename($_FILES['governance_pdf_file']['name']);
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                $errors[] = "Governance Document must be a valid PDF file.";
            } else {
                $uploadDir = ROOT_PATH . 'uploads/documents/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $candidatePdf = 'memorandum_of_association_' . time() . '.pdf';
                if (move_uploaded_file($tmpName, $uploadDir . $candidatePdf)) {
                    $pdfRel = 'uploads/documents/' . $candidatePdf;
                    $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES ('governance_pdf', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                    $stmt->execute([$pdfRel]);
                } else {
                    $errors[] = "Failed to upload Governance PDF file to disk.";
                }
            }
        }

        $instKeys = [
            // Our Journey
            'journey_lead', 'journey_story_content', 'journey_mission',
            // Governance
            'governance_lead', 'registration_no', 'established_year', 'registered_office',
            'reg_date', 'cert_copy_date', 'cert_ref_no',
            'gov_comp_title', 'gov_comp_desc', 'gov_audit_title', 'gov_audit_desc', 'gov_sec_title', 'gov_sec_desc',
            // Academic Collaborations Lead
            'collab_lead',
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
            log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Update Institutional CMS', 'CMS', "Institutional Journey, Governance, and Donation info updated.");
            set_flash_message('success', 'Institutional pages content, governance details, and PDF updated successfully!');
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
        $committeeType = sanitize_input($_POST['committee_type'] ?? 'Governing Body');
        if (!in_array($committeeType, ['Governing Body', 'Working Committee'])) $committeeType = 'Governing Body';
        $description = sanitize_input($_POST['description'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
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
                    $db->prepare("UPDATE governing_body_members SET name = ?, designation = ?, committee_type = ?, description = ?, bio = ?, icon = ?, sort_order = ?, status = ?, photo = ? WHERE id = ?")
                       ->execute([$name, $designation, $committeeType, $description, $bio, $icon, $sortOrder, $status, $newPhotoFilename, $mId]);
                } elseif ($removePhoto) {
                    if ($oldPhoto && file_exists(ROOT_PATH . 'uploads/governing_body/' . $oldPhoto)) {
                        @unlink(ROOT_PATH . 'uploads/governing_body/' . $oldPhoto);
                    }
                    $db->prepare("UPDATE governing_body_members SET name = ?, designation = ?, committee_type = ?, description = ?, bio = ?, icon = ?, sort_order = ?, status = ?, photo = NULL WHERE id = ?")
                       ->execute([$name, $designation, $committeeType, $description, $bio, $icon, $sortOrder, $status, $mId]);
                } else {
                    $db->prepare("UPDATE governing_body_members SET name = ?, designation = ?, committee_type = ?, description = ?, bio = ?, icon = ?, sort_order = ?, status = ? WHERE id = ?")
                       ->execute([$name, $designation, $committeeType, $description, $bio, $icon, $sortOrder, $status, $mId]);
                }
                log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Edit Gov Member', 'GoverningBody', "Updated: {$name} ({$designation} - {$committeeType})");
                set_flash_message('success', "Member '{$name}' updated successfully.");
            } else {
                $ins = $db->prepare("INSERT INTO governing_body_members (name, designation, committee_type, description, bio, photo, icon, sort_order, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $ins->execute([$name, $designation, $committeeType, $description, $bio, $newPhotoFilename, $icon, $sortOrder, $status]);
                log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Add Gov Member', 'GoverningBody', "Added: {$name} ({$designation} - {$committeeType})");
                set_flash_message('success', "New member '{$name}' added successfully.");
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

// ============================================================
// 7. POST ACTION: SAVE / EDIT ACADEMIC COLLABORATION
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_collaboration'])) {
    $activeTab = 'collaborations';
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token validation failed.";
    } else {
        $cId = (int)($_POST['collaboration_id'] ?? 0);
        $partnerName = sanitize_input($_POST['partner_name'] ?? '');
        $partnerSubtitle = sanitize_input($_POST['partner_subtitle'] ?? '');
        $mouTitle = sanitize_input($_POST['mou_title'] ?? '');
        $mouRefNo = sanitize_input($_POST['mou_ref_no'] ?? '');
        $signedDate = !empty($_POST['signed_date']) ? $_POST['signed_date'] : null;
        $validityPeriod = sanitize_input($_POST['validity_period'] ?? '3 Years');
        $partnerSignatory = sanitize_input($_POST['partner_signatory'] ?? '');
        $librarySignatory = sanitize_input($_POST['library_signatory'] ?? '');
        $witnessDetails = sanitize_input($_POST['witness_details'] ?? '');
        $summaryText = trim($_POST['summary_text'] ?? '');
        $objectives = trim($_POST['objectives'] ?? '');
        $scopeModalities = trim($_POST['scope_modalities'] ?? '');
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $status = sanitize_input($_POST['status'] ?? 'Active');
        if (!in_array($status, ['Active', 'Expired', 'Draft'])) $status = 'Active';

        if (empty($partnerName)) $errors[] = "Partner Institution Name is required.";
        if (empty($mouTitle)) $errors[] = "MOU Agreement Title is required.";

        // Handle Partner Logo Upload
        $newLogoFilename = null;
        if (isset($_FILES['partner_logo']) && $_FILES['partner_logo']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['partner_logo']['tmp_name'];
            $origName = basename($_FILES['partner_logo']['name']);
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $errors[] = "Invalid logo format. Allowed formats: JPG, PNG, WEBP.";
            } else {
                $uploadDir = ROOT_PATH . 'uploads/documents/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $candLogo = 'partner_logo_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (move_uploaded_file($tmpName, $uploadDir . $candLogo)) {
                    $newLogoFilename = $candLogo;
                } else {
                    $errors[] = "Failed to save partner logo to disk.";
                }
            }
        }

        // Handle MOU PDF Upload
        $newPdfFilename = null;
        if (isset($_FILES['mou_pdf']) && $_FILES['mou_pdf']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['mou_pdf']['tmp_name'];
            $origName = basename($_FILES['mou_pdf']['name']);
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                $errors[] = "MOU document must be a valid PDF file.";
            } else {
                $uploadDir = ROOT_PATH . 'uploads/documents/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $candPdf = 'mou_' . time() . '_' . rand(100, 999) . '.pdf';
                if (move_uploaded_file($tmpName, $uploadDir . $candPdf)) {
                    $newPdfFilename = $candPdf;
                } else {
                    $errors[] = "Failed to save MOU PDF document to disk.";
                }
            }
        }

        if (empty($errors)) {
            if ($cId > 0) {
                // Fetch existing files for preservation
                $oldStmt = $db->prepare("SELECT partner_logo, mou_pdf FROM academic_collaborations WHERE id = ?");
                $oldStmt->execute([$cId]);
                $oldData = $oldStmt->fetch();

                $logoToSave = $newLogoFilename ?: ($oldData['partner_logo'] ?? null);
                $pdfToSave = $newPdfFilename ?: ($oldData['mou_pdf'] ?? null);

                $db->prepare("
                    UPDATE academic_collaborations SET 
                        partner_name = ?, partner_subtitle = ?, mou_title = ?, mou_ref_no = ?,
                        signed_date = ?, validity_period = ?, partner_signatory = ?, library_signatory = ?,
                        witness_details = ?, summary_text = ?, objectives = ?, scope_modalities = ?,
                        sort_order = ?, status = ?, partner_logo = ?, mou_pdf = ?
                    WHERE id = ?
                ")->execute([
                    $partnerName, $partnerSubtitle, $mouTitle, $mouRefNo,
                    $signedDate, $validityPeriod, $partnerSignatory, $librarySignatory,
                    $witnessDetails, $summaryText, $objectives, $scopeModalities,
                    $sortOrder, $status, $logoToSave, $pdfToSave, $cId
                ]);
                log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Edit Academic Collaboration', 'CMS', "Updated: {$partnerName}");
                set_flash_message('success', "Academic Collaboration with '{$partnerName}' updated successfully.");
            } else {
                $db->prepare("
                    INSERT INTO academic_collaborations (
                        partner_name, partner_subtitle, mou_title, mou_ref_no,
                        signed_date, validity_period, partner_signatory, library_signatory,
                        witness_details, summary_text, objectives, scope_modalities,
                        sort_order, status, partner_logo, mou_pdf
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([
                    $partnerName, $partnerSubtitle, $mouTitle, $mouRefNo,
                    $signedDate, $validityPeriod, $partnerSignatory, $librarySignatory,
                    $witnessDetails, $summaryText, $objectives, $scopeModalities,
                    $sortOrder, $status, $newLogoFilename, $newPdfFilename
                ]);
                log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Add Academic Collaboration', 'CMS', "Added: {$partnerName}");
                set_flash_message('success', "New Academic Collaboration with '{$partnerName}' added successfully.");
            }
            header("Location: " . BASE_URL . "admin/cms/index.php?tab=collaborations");
            exit();
        }
    }
}

// ============================================================
// 8. POST ACTION: DELETE ACADEMIC COLLABORATION
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_collaboration'])) {
    $activeTab = 'collaborations';
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token validation failed.";
    } else {
        $delId = (int)$_POST['collaboration_id'];
        $oldStmt = $db->prepare("SELECT partner_name FROM academic_collaborations WHERE id = ?");
        $oldStmt->execute([$delId]);
        $cName = $oldStmt->fetchColumn();
        if ($cName) {
            $db->prepare("DELETE FROM academic_collaborations WHERE id = ?")->execute([$delId]);
            log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Delete Academic Collaboration', 'CMS', "Deleted: {$cName}");
            set_flash_message('info', "Academic collaboration with '{$cName}' has been removed.");
        }
        header("Location: " . BASE_URL . "admin/cms/index.php?tab=collaborations");
        exit();
    }
}

// Current Logo
$currentLogo = get_setting('site_logo', '');
$currentLogoUrl = !empty($currentLogo) && file_exists(ROOT_PATH . 'uploads/' . $currentLogo) ? BASE_URL . 'uploads/' . $currentLogo : BASE_URL . 'assets/images/site_logo.png';

// Fetch Governing Body Members & Academic Collaborations
$govMembers = $db->query("SELECT * FROM governing_body_members ORDER BY sort_order ASC, id ASC")->fetchAll();
$collaborations = $db->query("SELECT * FROM academic_collaborations ORDER BY sort_order ASC, id DESC")->fetchAll();

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
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $activeTab === 'collaborations' ? 'active' : '' ?> font-serif fw-semibold" id="collaborations-tab" data-bs-toggle="pill" data-bs-target="#tab-collaborations" type="button" role="tab" aria-selected="<?= $activeTab === 'collaborations' ? 'true' : 'false' ?>">
                        <i class="fas fa-handshake me-2"></i> Academic Collaborations <span class="badge bg-success ms-1"><?= count($collaborations) ?></span>
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
                                            <input type="email" name="email" class="form-control" value="<?= escape(get_setting('email', 'dakshineswarshayak1997@gmail.com')) ?>" placeholder="dakshineswarshayak1997@gmail.com" required>
                                        </div>
                                        <small class="text-muted">General public and administrative inquiries email.</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Member Support Email</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-headset"></i></span>
                                            <input type="email" name="email_support" class="form-control" value="<?= escape(get_setting('email_support', 'dakshineswarshayak1997@gmail.com')) ?>" placeholder="dakshineswarshayak1997@gmail.com">
                                        </div>
                                        <small class="text-muted">Technical support and membership query desk.</small>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label fw-bold small">Full Physical Campus / Library Address <span class="text-danger">*</span></label>
                                        <textarea name="address" class="form-control" rows="2" required><?= escape(get_setting('address', '11, Nepal Chandra Chatterjee Street, Ariadaha, Kolkata - 700057')) ?></textarea>
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
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Google Map Embed Iframe URL (src)</label>
                                        <input type="text" name="map_embed_url" class="form-control font-monospace small" value="<?= escape(get_setting('map_embed_url', 'https://maps.google.com/maps?q=Dakshineswar+Shayak+Library,+11,+Nepal+Chandra+Chatterjee+St,+Ariadaha,+Kolkata,+West+Bengal+700057&output=embed')) ?>">
                                        <small class="text-muted">Direct embed URL or from Google Maps Share &gt; Embed a map.</small>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small"><i class="fas fa-map-marked-alt text-danger me-1"></i> Google Maps Link (App / Directions / Share Link)</label>
                                        <input type="url" name="google_maps_link" class="form-control font-monospace small" value="<?= escape(get_setting('google_maps_link', 'https://maps.app.goo.gl/cJvtR8DGniZ4VaM7A')) ?>" placeholder="https://maps.app.goo.gl/...">
                                        <small class="text-muted">Short share link for users to open directions in the Google Maps app.</small>
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

                                    <div class="col-12"><hr class="my-2"><strong class="font-serif text-maroon" style="color: #7A0C0C;"><i class="fas fa-hand-holding-heart me-1"></i> Donate Us & Support Card (Hero Right Column)</strong></div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Donate Appeal Title</label>
                                        <input type="text" name="donate_appeal_title" class="form-control" value="<?= escape(get_setting('donate_appeal_title', 'Donate to Sayak Library')) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Donate UPI ID (Displayed on card)</label>
                                        <input type="text" name="donate_upi_id" class="form-control font-monospace" value="<?= escape(get_setting('donate_upi_id', 'sayaklibrary@sbi')) ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small">Donate Appeal Description</label>
                                        <input type="text" name="donate_appeal_desc" class="form-control" value="<?= escape(get_setting('donate_appeal_desc', 'Your contributions directly support book restoration, student scholarships, rare manuscript preservation, and e-learning resources.')) ?>">
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

                        <!-- Section 5: Homepage Catalog & Membership Section Headings -->
                        <div class="card sayak-card mb-4 border-start border-4 border-info">
                            <div class="card-header bg-white font-serif py-3 fw-bold fs-5">
                                <i class="fas fa-layer-group text-info me-2"></i> Homepage Catalog & Membership Headings
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Catalog Section Subtitle Badge</label>
                                        <input type="text" name="home_collections_subtitle" class="form-control" value="<?= escape(get_setting('home_collections_subtitle', 'EXPLORE OUR CATALOG')) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Catalog Section Main Title</label>
                                        <input type="text" name="home_collections_title" class="form-control" value="<?= escape(get_setting('home_collections_title', 'Featured Library Collections')) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Membership Section Subtitle Badge</label>
                                        <input type="text" name="home_membership_subtitle" class="form-control" value="<?= escape(get_setting('home_membership_subtitle', 'JOIN SAYAK LIBRARY')) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Membership Section Main Title</label>
                                        <input type="text" name="home_membership_title" class="form-control" value="<?= escape(get_setting('home_membership_title', 'Simple, Affordable Membership Plans')) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section 6: Global Footer About Paragraph -->
                        <div class="card sayak-card mb-4 border-start border-4 border-secondary">
                            <div class="card-header bg-white font-serif py-3 fw-bold fs-5">
                                <i class="fas fa-shoe-prints text-secondary me-2"></i> Global Footer About Description
                            </div>
                            <div class="card-body p-4">
                                <div class="mb-2">
                                    <label class="form-label fw-bold small">Footer Short About Paragraph</label>
                                    <textarea name="footer_about" class="form-control" rows="3"><?= escape(get_setting('footer_about', 'Established in 1995, Dakshineswar Shayak Library is a registered public academic repository dedicated to fostering education, research, and literature for students and researchers.')) ?></textarea>
                                    <small class="text-muted">Displayed under the library logo in the website footer across all pages.</small>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="save_branding_cms" class="btn btn-maroon btn-lg w-100 font-serif fw-bold py-3" style="background-color: #7A0C0C;">
                            <i class="fas fa-save me-2"></i> Save Website Branding & Homepage Content
                        </button>
                    </form>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 3: USER RULES & REGULATIONS CMS (19 OFFICIAL RULES) -->
                <!-- ==================================================== -->
                <div class="tab-pane fade <?= $activeTab === 'rules' ? 'show active' : '' ?>" id="tab-rules" role="tabpanel">
                    <?php
                    $officialRulesCMS = [
                        1 => [
                            'en' => "Books will be issued on demand basis.",
                            'bn' => "চাহিদা অনুযায়ী বই দেওয়া হবে।",
                            'tag' => "Issuing Policy",
                            'badge' => "bg-primary text-white",
                            'icon' => "fas fa-book-reader"
                        ],
                        2 => [
                            'en' => "At a time one / two book(s) will be issued for seven days only.",
                            'bn' => "এককালীন একটি বা দুটি বই ৭ দিনের জন্য দেওয়া হবে।",
                            'tag' => "7 Days Limit",
                            'badge' => "bg-warning text-dark",
                            'icon' => "fas fa-calendar-week"
                        ],
                        3 => [
                            'en' => "Books may be renewed in absence of other's demand.",
                            'bn' => "অন্য কারোর চাহিদা না থাকলে বই-এর পুন:নবীকরণ করা যাবে।",
                            'tag' => "Renewal",
                            'badge' => "bg-info text-dark",
                            'icon' => "fas fa-redo-alt"
                        ],
                        4 => [
                            'en' => "Demandship will be rejected if it is found that the book was in the library at the time of issuing demand.",
                            'bn' => "গ্রন্থাগারে বই থাকা সত্ত্বেও সেই বই এর উপর চাহিদা দিলে চাহিদাপত্র বাতিল বলে গণ্য হবে।",
                            'tag' => "Demand Clause",
                            'badge' => "bg-secondary text-white",
                            'icon' => "fas fa-times-circle"
                        ],
                        5 => [
                            'en' => "User must report any damage / mutilation of book before issuing the same. Identification of damage / mutilation at a later date, automatically the liability will rest with the person to whom the book was last issued. In such cases the Library Authority will make the final decision.",
                            'bn' => "বই এর ছেঁড়া/ফাটা পাতা ইত্যাদি বই ইস্যু করার আগে দেখে নিতে হবে। যদি বই এর কোনো রকম ক্ষয়ক্ষতি পরে পাওয়া যায় তবে, সর্বশেষ যার কাছে বইটা ইস্যু ছিল তার ওপর দায় বর্তাবে। গ্রন্থাগার কর্তৃপক্ষ এই ব্যাপারে চূড়ান্ত সিদ্ধান্ত নেবে।",
                            'tag' => "Damage Liability",
                            'badge' => "bg-danger text-white",
                            'icon' => "fas fa-search"
                        ],
                        6 => [
                            'en' => "Folding page corners, marking with pencil or ink, or tearing pages/pictures from the books is strictly prohibited.",
                            'bn' => "বইয়ের পাতা ভাঁজ করা, পেন্সিল বা কালি দিয়ে দাগ দেওয়া অথবা কোনো ছবি বা পাতা কাটা কঠোরভাবে নিষিদ্ধ।",
                            'tag' => "Strictly Prohibited",
                            'badge' => "bg-danger text-white",
                            'icon' => "fas fa-ban"
                        ],
                        7 => [
                            'en' => "In the event of a lost book, the user must replace it with a new copy of the same edition or pay the current market price of the book. The caution money deposit shall not be considered as an alternative compensation for the lost book.",
                            'bn' => "কোনো বই হারিয়ে গেলে ব্যবহারকারীকে ওই একই বইয়ের নতুন কপি কিনে দিতে হবে অথবা বর্তমান বাজারদর অনুযায়ী বইয়ের সম্পূর্ণ মূল্য প্রদান করতে হবে। জমা রাখা ফেরতযোগ্য অর্থ (Caution Money) কোনোভাবেই হারিয়ে যাওয়া বইয়ের বিকল্প ক্ষতিপূরণ হিসেবে গণ্য হবে না।",
                            'tag' => "Lost Book Policy",
                            'badge' => "bg-danger text-white",
                            'icon' => "fas fa-exclamation-triangle"
                        ],
                        8 => [
                            'en' => "In absence of the user at library on due date he / she would send an authorisation letter addressed to \"The Secretary / Librarian, Dakshineswar Shayak Library\" to change /renew the book, otherwise the book will be returned. The system will be valid for only one week only.",
                            'bn' => "গ্রন্থাগার এর নির্দিষ্ট দিনে কোনও লাইব্রেরি ব্যবহারকারী (User) পরিবর্ত কাউকে বদল বা একই বই পুনরায় নিতে পাঠালে, অবশ্যই ঐ লাইব্রেরি ব্যবহারকারী (User)-কে 'গ্রন্থাগারিক / সম্পাদক দক্ষিণেশ্বর শায়ক লাইব্রেরি' এর উদ্দেশ্যে চিঠি পাঠাতে হবে। ওই লাইব্রেরি ব্যবহারকারী (User) তার পরিবর্ত হিসাবে যাকে পাঠাচ্ছেন তার স্বাক্ষর উক্ত চিঠিতে প্রত্যয়িত (attested) করতে হবে। অন্যথায় বই ফেরত নেওয়া হবে। এই পদ্ধতি কেবলমাত্র এক সপ্তাহের জন্যই ধার্য্য হবে।",
                            'tag' => "Proxy / Authorisation",
                            'badge' => "bg-warning text-dark",
                            'icon' => "fas fa-envelope-open-text"
                        ],
                        9 => [
                            'en' => "The usership card is strictly non-transferable (except as conditionally permitted in Rule 8). The card must not be lent to anyone else under any circumstances.",
                            'bn' => "গ্রন্থাগারের ব্যবহারকারী কার্ডটি সম্পূর্ণরূপে হস্তান্তরযোগ্য নয় (৮ নং নিয়ম ব্যতীত)। কোনো অবস্থাতেই অন্য কাউকে এই কার্ড ব্যবহার করতে দেওয়া যাবে না।",
                            'tag' => "Non-Transferable",
                            'badge' => "bg-dark text-white",
                            'icon' => "fas fa-id-card-alt"
                        ],
                        10 => [
                            'en' => "Two passport size recent colour photographs (taken within the last six months) of the applicant will be required at the time of registration.",
                            'bn' => "আবেদনকারীর নাম নথীভুক্তকরণের জন্য দু কপি পাসপোর্ট মাপের রঙিন ছবি (গত ছয় মাসের মধ্যে তোলা) লাগবে।",
                            'tag' => "Registration Requirement",
                            'badge' => "bg-primary text-white",
                            'icon' => "fas fa-camera"
                        ],
                        11 => [
                            'en' => "A sum of Rs.50/- (Rupees fifty only) per book will be taken as caution money in case of lending, which will be refunded after termination of usership.",
                            'bn' => "ফেরতযোগ্য অর্থ (Caution Money) হিসেবে ৫০ টাকা প্রত্যেক বই এর জন্য জমা রাখতে হবে।",
                            'tag' => "Caution Money: ₹50/-",
                            'badge' => "bg-success text-white",
                            'icon' => "fas fa-hand-holding-usd"
                        ],
                        12 => [
                            'en' => "Rs.5/- (Rupees five only) will be taken as registration fees.",
                            'bn' => "নাম নথী ভুক্তকরণের জন্য ৫ টাকা জমা করতে হবে।",
                            'tag' => "Registration Fee: ₹5/-",
                            'badge' => "bg-primary text-white",
                            'icon' => "fas fa-ticket-alt"
                        ],
                        13 => [
                            'en' => "Rs.20/- (Rupees twenty only) will be taken as Monthly Subscription.",
                            'bn' => "ব্যবহারকারীর মাসিক চাঁদা ২০ টাকা ধার্য্য করা হবে।",
                            'tag' => "Monthly Subscription: ₹20/-",
                            'badge' => "bg-info text-dark",
                            'icon' => "fas fa-coins"
                        ],
                        14 => [
                            'en' => "Rs.1/- (One rupee only) per day per book will be charged as Fine in case of late return book.",
                            'bn' => "বই দেরীতে ফেরৎ দিলে প্রতি বই এর ক্ষেত্রে প্রত্যেকদিন ১ টাকা হিসেবে জরিমানা ধার্য্য হবে।",
                            'tag' => "Late Fine: ₹1 / Day",
                            'badge' => "bg-danger text-white",
                            'icon' => "fas fa-clock"
                        ],
                        15 => [
                            'en' => "If any user's monthly subscriptions and fine exceed deposit money i.e., Rs.50/- or Rs.100/- his/her usership card will be automatically terminated.",
                            'bn' => "যদি কোন গ্রন্থাগার ব্যবহারকারীর মাসিক চাঁদা ও জরিমানা, জমা রাখা ৫০/- অথবা ১০০/- ফেরতযোগ্য অর্থ (Caution Money)-এর অধিক হয়ে যায়, তাহলে তার গ্রন্থাগার ব্যবহারের সদস্যপদ বাতিল বলে গণ্য হবে।",
                            'tag' => "Card Termination",
                            'badge' => "bg-danger text-white",
                            'icon' => "fas fa-user-times"
                        ],
                        16 => [
                            'en' => "The Library working days are Thursday & Saturday 7:30 P.M. to 9:00 P.M, Sunday 9:30 A.M. - 12:30 Noon.",
                            'bn' => "গ্রন্থাগার প্রতি বৃহস্পতিবার, শনিবার (সন্ধ্যা ৭:৩০ মি: থেকে ৯:০০ টা পর্যন্ত) ও রবিবার (সকাল ৯:৩০ মি: থেকে বেলা ১২:৩০ মি: পর্যন্ত) খোলা থাকে।",
                            'tag' => "Library Schedule",
                            'badge' => "bg-success text-white",
                            'icon' => "fas fa-door-open"
                        ],
                        17 => [
                            'en' => "Person(s) who can give recommendation, should have to take their own responsibility for the applicant, in terms with Dakshineswar Shayak Library. User of this library may recommend one person.",
                            'bn' => "আবেদনকারীর নাম যারা অনুমোদন করবেন তারা সংস্থার সাথে আবেদনকারীর যোগাযোগের ক্ষেত্রে প্রয়োজনে দায়িত্ব নেবেন। দুজন অনুমোদনকারীর মধ্যে যে কোনও একজন সংস্থার সদস্য / সদস্যা (Member) হলেও চলবে।",
                            'tag' => "Recommendation",
                            'badge' => "bg-primary text-white",
                            'icon' => "fas fa-user-check"
                        ],
                        18 => [
                            'en' => "In all matters, the decision of the Library Authority shall be final and binding.",
                            'bn' => "যে কোনো ক্ষেত্রে গ্রন্থাগার কর্তৃপক্ষের সিদ্ধান্তই চূড়ান্ত বলে গণ্য হবে।",
                            'tag' => "Authority Final",
                            'badge' => "bg-dark text-white",
                            'icon' => "fas fa-gavel"
                        ],
                        19 => [
                            'en' => "In case of any ambiguity or conflict in interpretation, the English terminology shall prevail.",
                            'bn' => "নিয়মাবলী ব্যাখ্যার ক্ষেত্রে কোনো অস্পষ্টতা বা বিরোধ দেখা দিলে, ইংরেজি পরিভাষা প্রাধান্য পাবে।",
                            'tag' => "Interpretation Clause",
                            'badge' => "bg-primary text-white",
                            'icon' => "fas fa-balance-scale"
                        ]
                    ];

                    $bnNumbers = [
                        1 => '১', 2 => '২', 3 => '৩', 4 => '৪', 5 => '৫',
                        6 => '৬', 7 => '৭', 8 => '৮', 9 => '৯', 10 => '১০',
                        11 => '১১', 12 => '১২', 13 => '১৩', 14 => '১৪', 15 => '১৫',
                        16 => '১৬', 17 => '১৭', 18 => '১৮', 19 => '১৯'
                    ];
                    ?>
                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                        <div class="card sayak-card mb-4 border-top border-4 border-maroon">
                            <div class="card-header bg-white font-serif py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="fw-bold fs-5 text-maroon" style="color: #7A0C0C;">
                                    <i class="fas fa-gavel me-2"></i> Official 19 Rules & Regulations (rules.php)
                                </div>
                                <a href="<?= BASE_URL ?>rules.php" target="_blank" class="btn btn-outline-maroon btn-sm font-serif">
                                    <i class="fas fa-external-link-alt me-1"></i> View Live Rules Page
                                </a>
                            </div>
                            <div class="card-body p-4">
                                <div class="alert alert-light border shadow-sm mb-4">
                                    <i class="fas fa-info-circle text-primary me-2"></i>
                                    <strong>Bilingual Rules Management:</strong> You can edit both the English and Bengali texts for each of the 19 official rules. Changes made here will be reflected immediately on the public <code>rules.php</code> page in both language views.
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold small">Rules Page Header Subtitle / Lead Statement</label>
                                    <input type="text" name="rules_lead" class="form-control" value="<?= escape(get_setting('rules_lead', 'Official code of conduct, borrowing privileges, caution money structure, and operating policies governing Dakshineswar Shayak Library.')) ?>" required>
                                </div>

                                <hr class="my-4">

                                <h5 class="fw-bold font-serif text-maroon mb-3" style="color: #7A0C0C;">
                                    <i class="fas fa-list-ol me-2"></i> Official 19 Rules (English & বাংলা)
                                </h5>

                                <?php for ($i = 1; $i <= 19; $i++): ?>
                                    <?php
                                    $ruleDef = $officialRulesCMS[$i];
                                    $currentEn = get_setting("rule_{$i}_en", $ruleDef['en']);
                                    $currentBn = get_setting("rule_{$i}_bn", $ruleDef['bn']);
                                    $badgeStyle = $ruleDef['badge'] ?? 'bg-secondary text-white';
                                    ?>
                                    <div class="card mb-3 border shadow-sm">
                                        <div class="card-header bg-light py-2 d-flex justify-content-between align-items-center flex-wrap">
                                            <div class="fw-bold">
                                                <i class="<?= escape($ruleDef['icon']) ?> text-maroon me-2" style="color: #7A0C0C;"></i>
                                                Rule #<?= $i ?> <span class="text-muted fw-normal">| নিয়ম নং <?= $bnNumbers[$i] ?></span>
                                            </div>
                                            <span class="badge <?= escape($badgeStyle) ?> rounded-pill small"><?= escape($ruleDef['tag']) ?></span>
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold small text-primary">
                                                        <i class="fas fa-language me-1"></i> English Rule Text
                                                    </label>
                                                    <textarea name="rule_<?= $i ?>_en" class="form-control" rows="3" style="font-size: 13.5px;"><?= escape($currentEn) ?></textarea>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label fw-bold small text-success">
                                                        <i class="fas fa-feather-alt me-1"></i> বাংলা নিয়মাবলী (Bengali)
                                                    </label>
                                                    <textarea name="rule_<?= $i ?>_bn" class="form-control" rows="3" style="font-size: 13.5px;"><?= escape($currentBn) ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <button type="submit" name="save_rules_cms" class="btn btn-maroon btn-lg w-100 font-serif fw-bold py-3" style="background-color: #7A0C0C;">
                            <i class="fas fa-save me-2"></i> Save All 19 Rules & Regulations
                        </button>
                    </form>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 4: INSTITUTIONAL PAGES & DONATIONS CMS           -->
                <!-- ==================================================== -->
                <div class="tab-pane fade <?= $activeTab === 'institutional' ? 'show active' : '' ?>" id="tab-institutional" role="tabpanel">
                    <form action="" method="POST" enctype="multipart/form-data">
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
                                    <label class="form-label fw-bold small">Journey Page Headline / Lead Subtitle</label>
                                    <input type="text" name="journey_lead" class="form-control" value="<?= escape(get_setting('journey_lead', 'From Shishu Vikash School’s Cabinet to 11 Nepal Chandra Chatterjee Street — 28+ Years of Dedication & Service')) ?>">
                                </div>

                                <?php
                                $defaultJourneyStory = "From Shishu Vikash School’s Cabinet to a whole building at Nepal Chandra Chatterjee Street housing over 5000 books, Dakshineswar Shayak Library has come a long way in these past 28 years. This library began as a joint effort of a few bachelors in their twenties, who had the sole goal of delivering quality education to students regardless of their financial backgrounds.\n\nSo here we are, almost 3 decades later, a friendly, community-focused library to support undergraduate and postgraduate students by providing easy access to textbooks. Shayak was founded with the heartfelt support of people like you to ease the burden of expensive textbooks for students and their families, so they can focus on their studies without the stress of high costs. Through all the challenges, we’ve been here for students promoting equal educational opportunities for all.\n\nIn the early days when the founding members were in search of a space to house the library, Abhijit Ray and Avantika Ray were the ones who allowed them to use the ground floor of Shishu Vikash School. They gave many advice on various working of an organization, arranged for funding too.\n\nFast-forward a few years. From “Anandabazar Patrika’s” (আনন্দবাজার পত্রিকা) “Kolkatar Korcha” (কলকাতার কড়চা) section, Ganesh Bhattacharya and Mira Bhattacharya found out about the library. They approached us with generous funds in loving memory of their late son Niladri Bhattacharya. They have continued to support us to date through funds or any other means possible, even in their old age.\n\nIt is also mention-worthy that one of the most notable teachers of Ariadaha Kalachand Highschool, Dr. Rathin Mitra was also a very close well-wisher of us. Visiting us in his free time to give advice and admiring and motivating our dedication to give back to the society.\n\nEventually, we outgrew our space at Shishu Bikash School. Then, by sheer luck, Divyendu Vishnu and his wife, Srimati Leela Vishnu, offered us a whole building at 11 Nepal Chandra Chatterjee Street. On February 13, 2000, we inaugurated our permanent library location and it also marked the formation of Dakshineswar Shayak Library Trustee Committee. Since then, support from various community members, local leaders, and generations of volunteers has kept our library alive and thriving. And with their very help the library has been renovated to a two-storeyed well organized and decorated instituition.\n\nThanks to our generous donors, we’re able to keep this initiative growing. Join us in empowering students and building a brighter, more informed future! Your support helps us provide essential resources to students and uplifts our community as a whole. By contributing, you’re making a lasting impact on education and inspiring positive change.\n\nThank you for being part of our journey!";
                                ?>

                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-maroon font-serif" style="color: #7A0C0C;">
                                        <i class="fas fa-feather-alt me-1"></i> Full Journey Story & History Narrative
                                    </label>
                                    <textarea name="journey_story_content" class="form-control" rows="14" style="line-height: 1.6; font-size: 14.5px;"><?= escape(get_setting('journey_story_content', $defaultJourneyStory)) ?></textarea>
                                    <small class="text-muted">
                                        <i class="fas fa-info-circle text-primary me-1"></i> Separate paragraphs with double enter (blank line). This story will be rendered with rich layout, milestone badges, and donor tributes on the public <code>our-journey.php</code> page.
                                    </small>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-bold small">Core Mission / Call-to-Action Highlight Text</label>
                                    <textarea name="journey_mission" class="form-control" rows="2"><?= escape(get_setting('journey_mission', 'Join us in empowering students and building a brighter, more informed future! Your support helps us provide essential resources to students and uplifts our community as a whole.')) ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Part B: Governance Page & Legal PDF Document -->
                        <div class="card sayak-card mb-4 border-start border-4 border-primary">
                            <div class="card-header bg-white font-serif py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="fw-bold fs-5 text-dark">
                                    <i class="fas fa-balance-scale text-primary me-2"></i> Institutional Governance & Legal Constitution (governance.php)
                                </div>
                                <a href="<?= BASE_URL ?>governance.php" target="_blank" class="btn btn-outline-primary btn-sm font-serif">
                                    <i class="fas fa-external-link-alt me-1"></i> View Live Governance Page
                                </a>
                            </div>
                            <div class="card-body p-4">
                                <?php
                                $currentGovPdf = get_setting('governance_pdf', 'uploads/documents/memorandum_of_association_dakshineswar_shayak.pdf');
                                $govPdfExists = !empty($currentGovPdf) && file_exists(ROOT_PATH . $currentGovPdf);
                                ?>
                                <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                                    <div>
                                        <div class="fw-bold text-dark font-serif"><i class="fas fa-file-pdf text-danger me-2 fa-lg"></i> Official Memorandum & Constitution PDF Document</div>
                                        <small class="text-muted">Current file: <code><?= escape($currentGovPdf) ?></code> <?= $govPdfExists ? '<span class="badge bg-success-subtle text-success border ms-1"><i class="fas fa-check-circle me-1"></i> File Active (' . round(filesize(ROOT_PATH . $currentGovPdf)/(1024*1024), 2) . ' MB)</span>' : '<span class="badge bg-warning-subtle text-warning border ms-1"><i class="fas fa-exclamation-triangle me-1"></i> File Missing</span>' ?></small>
                                    </div>
                                    <?php if ($govPdfExists): ?>
                                        <a href="<?= BASE_URL . escape($currentGovPdf) ?>" target="_blank" class="btn btn-outline-primary btn-sm font-serif">
                                            <i class="fas fa-eye me-1"></i> View Current PDF
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold small text-primary"><i class="fas fa-upload me-1"></i> Upload / Replace Governance Document PDF</label>
                                        <input type="file" name="governance_pdf_file" class="form-control" accept=".pdf">
                                        <small class="text-muted">Select a new PDF file to replace the current certified Memorandum of Association and Registration deed displayed on <code>governance.php</code>.</small>
                                    </div>

                                    <div class="col-md-12">
                                        <label class="form-label fw-bold small">Governance Lead Subtitle / Introduction</label>
                                        <textarea name="governance_lead" class="form-control" rows="2"><?= escape(get_setting('governance_lead', 'Dakshineswar Shayak is a registered public educational and cultural institution governed strictly under the provisions of the West Bengal Societies Registration Act, 1961. Our institutional governance is founded on unwavering transparency, democratic oversight, and non-profit public service.')) ?></textarea>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">Society Registration No</label>
                                        <input type="text" name="registration_no" class="form-control font-monospace" value="<?= escape(get_setting('registration_no', 'S/87920 of 1997-1998')) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">Registration Date</label>
                                        <input type="text" name="reg_date" class="form-control" value="<?= escape(get_setting('reg_date', '27 August 1997')) ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold small">Certified Copy Date</label>
                                        <input type="text" name="cert_copy_date" class="form-control" value="<?= escape(get_setting('cert_copy_date', '03 July 2023')) ?>">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Certified Copy Stamp Reference No</label>
                                        <input type="text" name="cert_ref_no" class="form-control font-monospace" value="<?= escape(get_setting('cert_ref_no', '79AB 299217')) ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small">Registered Office Address</label>
                                        <input type="text" name="registered_office" class="form-control" value="<?= escape(get_setting('registered_office', '11, Nepal Chandra Chatterjee Street, P.O. Ariadaha, Kolkata - 700 057')) ?>">
                                    </div>

                                    <div class="col-12"><hr class="my-2"><strong class="font-serif text-dark">Statutory Compliance & Operational Policies</strong></div>

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

                        <!-- Part C: Academic Collaborations Summary & Lead Settings -->
                        <div class="card sayak-card mb-4 border-start border-4 border-success">
                            <div class="card-header bg-white font-serif py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="fw-bold fs-5 text-dark">
                                    <i class="fas fa-handshake text-success me-2"></i> Academic Collaborations (academic-collaborations.php)
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-outline-success btn-sm font-serif fw-bold" onclick="document.getElementById('collaborations-tab').click();">
                                        <i class="fas fa-cog me-1"></i> Manage Collaborations & MOUs (<?= count($collaborations) ?>)
                                    </button>
                                    <a href="<?= BASE_URL ?>academic-collaborations.php" target="_blank" class="btn btn-outline-secondary btn-sm font-serif">
                                        <i class="fas fa-external-link-alt me-1"></i> View Live Page
                                    </a>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div class="alert alert-success-subtle border border-success-subtle d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                    <div>
                                        <strong class="text-success font-serif"><i class="fas fa-check-circle me-1"></i> Full Multi-Institution Management Available:</strong>
                                        <span class="small text-secondary ms-1">You can add new colleges/academies, upload their crest logos, and attach signed MOU PDF documents at any time.</span>
                                    </div>
                                    <button type="button" class="btn btn-success btn-sm font-serif" onclick="document.getElementById('collaborations-tab').click();">
                                        <i class="fas fa-handshake me-1"></i> Go to Collaborations Tab
                                    </button>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Collaborations Page Lead Headline / Subtitle</label>
                                    <input type="text" name="collab_lead" class="form-control" value="<?= escape(get_setting('collab_lead', 'Dakshineswar Shayak Library partners actively with leading academic institutions, universities, and colleges to promote library usage, textbook accessibility, and student empowerment.')) ?>">
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
                                <?php
                                $countGov = count(array_filter($govMembers, fn($m) => ($m['committee_type'] ?? 'Governing Body') === 'Governing Body'));
                                $countWork = count(array_filter($govMembers, fn($m) => ($m['committee_type'] ?? '') === 'Working Committee'));
                                ?>
                                <div class="px-4 py-2 bg-light border-bottom d-flex gap-2 align-items-center flex-wrap">
                                    <span class="small fw-bold text-muted me-1"><i class="fas fa-filter me-1"></i> Quick Filter:</span>
                                    <button type="button" class="btn btn-sm btn-dark rounded-pill px-3" id="filterBtnAll" onclick="filterCommitteeTable('All')">
                                        All Members (<?= count($govMembers) ?>)
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" id="filterBtnGov" onclick="filterCommitteeTable('Governing Body')">
                                        <i class="fas fa-landmark me-1"></i> Governing Body (<?= $countGov ?>)
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3" id="filterBtnWork" onclick="filterCommitteeTable('Working Committee')">
                                        <i class="fas fa-hands-helping me-1"></i> Working Committee (<?= $countWork ?>)
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 70px;">Order</th>
                                                <th style="width: 80px;">Photo</th>
                                                <th>Member Name & Bio</th>
                                                <th>Committee Body</th>
                                                <th>Official Designation</th>
                                                <th>Status</th>
                                                <th class="text-end pe-4" style="width: 140px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="govMembersTableBody">
                                            <?php foreach ($govMembers as $m): ?>
                                                <tr data-committee="<?= escape($m['committee_type'] ?? 'Governing Body') ?>">
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
                                                        <small class="text-muted d-block"><?= escape($m['description'] ?: 'No subtitle entered.') ?></small>
                                                        <?php if (!empty(trim($m['bio'] ?? ''))): ?>
                                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-1" style="font-size: 10.5px;">
                                                                <i class="fas fa-info-circle me-1"></i> "Know More" Info Available
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-muted border mt-1" style="font-size: 10px;">
                                                                No extended info
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if (($m['committee_type'] ?? 'Governing Body') === 'Governing Body'): ?>
                                                            <span class="badge text-white font-serif" style="background-color: #7A0C0C; font-size: 11px;">
                                                                <i class="fas fa-landmark me-1"></i> Governing Body
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-dark text-warning border font-serif" style="font-size: 11px;">
                                                                <i class="fas fa-hands-helping me-1"></i> Working Committee
                                                            </span>
                                                        <?php endif; ?>
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
                                                            <form action="" method="POST" class="d-inline" onsubmit="return confirm('Remove <?= escape(addslashes($m['name'])) ?> from the roster?');">
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
                                <div class="text-center py-5">
                                    <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted font-serif">No Governing Body Members Found</h5>
                                    <p class="text-secondary small">Click "Add New Member" to add council members and committee officers.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ==================================================== -->
                <!-- TAB 6: ACADEMIC COLLABORATIONS CMS                   -->
                <!-- ==================================================== -->
                <div class="tab-pane fade <?= $activeTab === 'collaborations' ? 'show active' : '' ?>" id="tab-collaborations" role="tabpanel">
                    <div class="card sayak-card border-top border-4 border-success mb-4">
                        <div class="card-header bg-white font-serif py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h5 class="fw-bold mb-0 text-success">
                                    <i class="fas fa-handshake me-2"></i> Academic Collaborations & Institutional MOUs
                                </h5>
                                <small class="text-muted">Manage affiliated colleges, universities, bilateral MOUs, official seals, and signed deed documents.</small>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="<?= BASE_URL ?>academic-collaborations.php" target="_blank" class="btn btn-outline-secondary btn-sm font-serif">
                                    <i class="fas fa-external-link-alt me-1"></i> View Live Page
                                </a>
                                <button type="button" class="btn btn-success font-serif fw-bold btn-sm" onclick="openAddCollabModal()">
                                    <i class="fas fa-plus-circle me-1"></i> Add New Academic Collaboration
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($collaborations)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 60px;">Order</th>
                                                <th style="width: 70px;">Logo</th>
                                                <th>Partner Institution</th>
                                                <th>MOU Agreement Title & Ref</th>
                                                <th>Signing & Validity</th>
                                                <th>MOU Document (PDF)</th>
                                                <th>Status</th>
                                                <th class="text-end pe-4" style="width: 130px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($collaborations as $c): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-light text-dark border font-monospace"><?= (int)$c['sort_order'] ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($c['partner_logo']) && file_exists(ROOT_PATH . 'uploads/documents/' . $c['partner_logo'])): ?>
                                                            <img src="<?= BASE_URL ?>uploads/documents/<?= escape($c['partner_logo']) ?>" alt="<?= escape($c['partner_name']) ?>" class="rounded-circle border shadow-sm" style="width: 46px; height: 46px; object-fit: contain; background: #fff; padding: 2px;">
                                                        <?php else: ?>
                                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border" style="width: 46px; height: 46px;">
                                                                <i class="fas fa-university text-secondary fa-lg"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <strong class="font-serif text-dark fs-6 d-block"><?= escape($c['partner_name']) ?></strong>
                                                        <small class="text-muted d-block"><?= escape($c['partner_subtitle'] ?: 'No subtitle specified.') ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="d-block small fw-bold text-dark"><?= escape($c['mou_title']) ?></span>
                                                        <?php if (!empty($c['mou_ref_no'])): ?>
                                                            <small class="text-muted font-monospace"><i class="fas fa-barcode me-1"></i> Ref: <?= escape($c['mou_ref_no']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="small">
                                                            <i class="fas fa-calendar-check text-success me-1"></i> <?= !empty($c['signed_date']) ? date('d M Y', strtotime($c['signed_date'])) : '<span class="text-muted">Not specified</span>' ?>
                                                        </div>
                                                        <small class="text-muted"><i class="fas fa-hourglass-half me-1"></i> <?= escape($c['validity_period'] ?: '3 Years') ?></small>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($c['mou_pdf']) && file_exists(ROOT_PATH . 'uploads/documents/' . $c['mou_pdf'])): ?>
                                                            <a href="<?= BASE_URL ?>uploads/documents/<?= escape($c['mou_pdf']) ?>" target="_blank" class="btn btn-outline-danger btn-sm py-1 px-2 font-serif" style="font-size: 11.5px;">
                                                                <i class="fas fa-file-pdf me-1"></i> View PDF
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 11px;">No PDF</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($c['status'] === 'Active'): ?>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Active</span>
                                                        <?php elseif ($c['status'] === 'Expired'): ?>
                                                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1">Expired</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary-subtle text-secondary border px-2 py-1">Draft</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end pe-4">
                                                        <div class="btn-group btn-group-sm">
                                                            <button type="button" class="btn btn-outline-success" onclick='editCollaboration(<?= json_encode($c) ?>)' title="Edit Collaboration">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <form action="" method="POST" class="d-inline" onsubmit="return confirm('Delete collaboration with <?= escape(addslashes($c['partner_name'])) ?>? This cannot be undone.');">
                                                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                                <input type="hidden" name="collaboration_id" value="<?= $c['id'] ?>">
                                                                <button type="submit" name="delete_collaboration" class="btn btn-outline-danger" title="Delete Collaboration">
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
                                <div class="text-center py-5">
                                    <i class="fas fa-handshake-slash fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted font-serif">No Academic Collaborations Found</h5>
                                    <p class="text-secondary small">Click "Add New Academic Collaboration" to add partner universities, colleges, and upload bilateral MOUs.</p>
                                    <button type="button" class="btn btn-success btn-sm font-serif" onclick="openAddCollabModal()">
                                        <i class="fas fa-plus me-1"></i> Add First Partner Collaboration
                                    </button>
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
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white" style="background-color: #7A0C0C;">
                <h5 class="modal-title font-serif" id="govMemberModalTitle">Add New Member</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4" style="max-height: calc(100vh - 200px); overflow-y: auto;">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="member_id" id="gov_member_id" value="0">

                    <div class="row g-3">
                        <!-- Full Name -->
                        <div class="col-md-5">
                            <label class="form-label fw-bold small">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="gov_name" class="form-control" placeholder="e.g. Anjan Basu" required>
                        </div>

                        <!-- Committee Body -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold small">Committee Body <span class="text-danger">*</span></label>
                            <select name="committee_type" id="gov_committee_type" class="form-select">
                                <option value="Governing Body">Governing Body (পরিচালনা পর্ষদ)</option>
                                <option value="Working Committee">Working Committee (কর্মী সমিতি)</option>
                            </select>
                        </div>

                        <!-- Designation -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Designation / Title <span class="text-danger">*</span></label>
                            <input type="text" name="designation" id="gov_designation" class="form-control" list="designationSuggestions" placeholder="e.g. President" required>
                            <datalist id="designationSuggestions">
                                <option value="President">
                                <option value="Vice President">
                                <option value="Secretary">
                                <option value="Assistant Secretary">
                                <option value="Treasurer">
                                <option value="Assistant Treasurer">
                                <option value="Working Committee Member">
                                <option value="Executive Member">
                                <option value="Academic Advisor">
                                <option value="Chief Librarian">
                            </datalist>
                        </div>

                        <!-- Description / Short Subtitle -->
                        <div class="col-12">
                            <label class="form-label fw-bold small">Short Subtitle / Role Overview</label>
                            <input type="text" name="description" id="gov_description" class="form-control" placeholder="e.g. President, Dakshineswar Shayak Library Governing Body.">
                            <small class="text-muted">Brief summary displayed directly on the member's card.</small>
                        </div>

                        <!-- Bio / Detailed Information (Know More Modal Content) -->
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold small text-maroon font-serif mb-0" style="color: #7A0C0C;">
                                    <i class="fas fa-id-card me-1"></i> Detailed Information & Biography ("Know More" Button Content)
                                </label>
                                <span class="badge bg-light text-muted border small">Optional</span>
                            </div>
                            <textarea name="bio" id="gov_bio" class="form-control" rows="4" placeholder="Enter extended biographical details, academic qualifications, tenure, library contributions, contact info, or background details..."></textarea>
                            <small class="text-muted">
                                <i class="fas fa-info-circle text-primary me-1"></i> When you enter information here, a <strong>"Know More"</strong> button will automatically appear on this member's card on the public website.
                            </small>
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
                                <option value="fa-user-shield">Trustee / Shield (fa-user-shield)</option>
                                <option value="fa-user-graduate">Scholar / Secretary (fa-user-graduate)</option>
                                <option value="fa-user-cog">Coordinator / Assistant (fa-user-cog)</option>
                                <option value="fa-coins">Treasurer / Coins (fa-coins)</option>
                                <option value="fa-calculator">Auditor / Calculator (fa-calculator)</option>
                                <option value="fa-user-check">Committee Member (fa-user-check)</option>
                                <option value="fa-user-plus">Vacant / Open (fa-user-plus)</option>
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

<!-- ============================================================ -->
<!-- MODAL: ADD / EDIT ACADEMIC COLLABORATION                     -->
<!-- ============================================================ -->
<!-- ============================================================ -->
<!-- MODAL: ADD / EDIT ACADEMIC COLLABORATION                     -->
<!-- ============================================================ -->
<div class="modal fade" id="collaborationModal" tabindex="-1" aria-labelledby="collaborationModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <form action="" method="POST" enctype="multipart/form-data" class="modal-content shadow-lg border-0">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-serif" id="collaborationModalTitle">Add New Academic Collaboration</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" style="max-height: calc(100vh - 210px); overflow-y: auto;">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="collaboration_id" id="collab_id" value="0">

                <div class="row g-3">
                    <div class="col-12">
                        <h6 class="font-serif fw-bold text-success border-bottom pb-2">
                            <i class="fas fa-university me-1"></i> 1. Partner Institution & MOU Agreement Title
                        </h6>
                    </div>

                    <!-- Partner Name -->
                    <div class="col-md-7">
                        <label class="form-label fw-bold small">Partner Institution Name <span class="text-danger">*</span></label>
                        <input type="text" name="partner_name" id="collab_partner_name" class="form-control" placeholder="e.g. Hiralal Mazumdar Memorial College for Women" required>
                    </div>

                    <!-- Partner Subtitle -->
                    <div class="col-md-5">
                        <label class="form-label fw-bold small">Affiliation / Subtitle</label>
                        <input type="text" name="partner_subtitle" id="collab_partner_subtitle" class="form-control" placeholder="e.g. Affiliated to WBSU • Estd. 1959">
                    </div>

                    <!-- MOU Title -->
                    <div class="col-md-8">
                        <label class="form-label fw-bold small">MOU Agreement Title <span class="text-danger">*</span></label>
                        <input type="text" name="mou_title" id="collab_mou_title" class="form-control" placeholder="e.g. Bilateral Memorandum of Understanding for Academic Cooperation" required>
                    </div>

                    <!-- MOU Ref No -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">MOU Reference / Deed No</label>
                        <input type="text" name="mou_ref_no" id="collab_mou_ref_no" class="form-control font-monospace" placeholder="e.g. 73AB 065674">
                    </div>

                    <div class="col-12 mt-3">
                        <h6 class="font-serif fw-bold text-success border-bottom pb-2">
                            <i class="fas fa-calendar-alt me-1"></i> 2. Dates, Validity & Signatories
                        </h6>
                    </div>

                    <!-- Signed Date -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Signed Date</label>
                        <input type="date" name="signed_date" id="collab_signed_date" class="form-control">
                    </div>

                    <!-- Validity Period -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Validity Period</label>
                        <input type="text" name="validity_period" id="collab_validity_period" class="form-control" placeholder="e.g. 3 Years (Auto Renewal)">
                    </div>

                    <!-- Status -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Status</label>
                        <select name="status" id="collab_status" class="form-select">
                            <option value="Active">Active (Visible)</option>
                            <option value="Expired">Expired</option>
                            <option value="Draft">Draft (Hidden)</option>
                        </select>
                    </div>

                    <!-- Sort Order -->
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Display Sequence Order</label>
                        <input type="number" name="sort_order" id="collab_sort_order" class="form-control" value="1" min="0">
                    </div>

                    <!-- Partner Signatory -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Partner First Party Signatory</label>
                        <input type="text" name="partner_signatory" id="collab_partner_signatory" class="form-control" placeholder="e.g. Dr. Soma Ghosh, Principal">
                    </div>

                    <!-- Library Signatory -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Library Second Party Signatory</label>
                        <input type="text" name="library_signatory" id="collab_library_signatory" class="form-control" placeholder="e.g. Pallab Adhikary, Secretary">
                    </div>

                    <!-- Witnesses -->
                    <div class="col-md-4">
                        <label class="form-label fw-bold small">Witnesses / Coordinators</label>
                        <input type="text" name="witness_details" id="collab_witness_details" class="form-control" placeholder="e.g. IQAC Coordinator & Sourav Maju">
                    </div>

                    <!-- SECTION 3: LOGO & PDF UPLOADS -->
                    <div class="col-12 mt-3">
                        <div class="p-3 rounded border border-success bg-light">
                            <h6 class="font-serif fw-bold text-success mb-3">
                                <i class="fas fa-file-upload me-1"></i> 3. Official Documents & Partner Crest Logo
                            </h6>

                            <div class="row g-3">
                                <!-- Partner Logo -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-dark">
                                        <i class="fas fa-image text-primary me-1"></i> Partner Crest / College Logo (JPG, PNG, WEBP)
                                    </label>
                                    <input type="file" name="partner_logo" class="form-control" accept="image/*">
                                    <small class="text-muted d-block mt-1">Official emblem or college seal displayed on the collaborations page.</small>
                                    <div id="collab_current_logo_wrap" class="mt-2 d-none align-items-center gap-2 p-2 bg-white rounded border">
                                        <span class="small text-muted fw-bold">Current Logo:</span>
                                        <img id="collab_current_logo_img" src="" alt="Logo" class="rounded border p-1" style="width: 44px; height: 44px; object-fit: contain; background: #fff;">
                                    </div>
                                </div>

                                <!-- MOU Signed Deed PDF -->
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-danger">
                                        <i class="fas fa-file-pdf text-danger me-1"></i> Signed Bilateral MOU Deed Document (PDF)
                                    </label>
                                    <input type="file" name="mou_pdf" class="form-control" accept=".pdf">
                                    <small class="text-muted d-block mt-1">Scanned official signed deed document displayed in the interactive viewer.</small>
                                    <div id="collab_current_pdf_wrap" class="mt-2 d-none align-items-center gap-2 p-2 bg-white rounded border">
                                        <span class="small text-muted fw-bold">Current Document:</span>
                                        <a id="collab_current_pdf_link" href="#" target="_blank" class="btn btn-outline-danger btn-sm py-1 px-2 font-monospace" style="font-size: 11.5px;">
                                            <i class="fas fa-file-pdf me-1"></i> View Current PDF
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-3">
                        <h6 class="font-serif fw-bold text-success border-bottom pb-2">
                            <i class="fas fa-align-left me-1"></i> 4. Detailed Narrative & Terms
                        </h6>
                    </div>

                    <!-- Summary Text -->
                    <div class="col-12">
                        <label class="form-label fw-bold small">Partnership Summary / Lead Narrative</label>
                        <textarea name="summary_text" id="collab_summary_text" class="form-control" rows="3" placeholder="Brief overview of the partnership agreement and purpose..."></textarea>
                    </div>

                    <!-- Objectives -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Purpose & Strategic Objectives (Clause I)</label>
                        <textarea name="objectives" id="collab_objectives" class="form-control" rows="4" placeholder="Separate items with double-enter or newlines..."></textarea>
                        <small class="text-muted">Enter key objectives. Rendered as highlighted feature cards on the public page.</small>
                    </div>

                    <!-- Scope & Modalities -->
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Operational Scope & Modalities (Clause II & III)</label>
                        <textarea name="scope_modalities" id="collab_scope_modalities" class="form-control" rows="4" placeholder="Separate terms with double-enter or newlines..."></textarea>
                        <small class="text-muted">Enter operational terms such as reading visits, study hall privileges, textbook sharing.</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" name="save_collaboration" class="btn btn-success font-serif fw-bold px-4">
                    <i class="fas fa-save me-1"></i> Save Academic Collaboration
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddGovModal() {
    document.getElementById('govMemberModalTitle').innerText = 'Add New Member';
    document.getElementById('gov_member_id').value = '0';
    document.getElementById('gov_name').value = '';
    document.getElementById('gov_committee_type').value = 'Governing Body';
    document.getElementById('gov_designation').value = '';
    document.getElementById('gov_description').value = '';
    document.getElementById('gov_bio').value = '';
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
    document.getElementById('govMemberModalTitle').innerText = 'Edit Member';
    document.getElementById('gov_member_id').value = m.id;
    document.getElementById('gov_name').value = m.name;
    document.getElementById('gov_committee_type').value = m.committee_type || 'Governing Body';
    document.getElementById('gov_designation').value = m.designation;
    document.getElementById('gov_description').value = m.description || '';
    document.getElementById('gov_bio').value = m.bio || '';
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

function filterCommitteeTable(type) {
    var rows = document.querySelectorAll('#govMembersTableBody tr');
    rows.forEach(function(row) {
        var comm = row.getAttribute('data-committee') || 'Governing Body';
        if (type === 'All' || comm === type) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });

    var btnAll = document.getElementById('filterBtnAll');
    var btnGov = document.getElementById('filterBtnGov');
    var btnWork = document.getElementById('filterBtnWork');

    if (btnAll) btnAll.className = 'btn btn-sm ' + (type === 'All' ? 'btn-dark' : 'btn-outline-dark') + ' rounded-pill px-3';
    if (btnGov) btnGov.className = 'btn btn-sm ' + (type === 'Governing Body' ? 'btn-danger text-white' : 'btn-outline-danger') + ' rounded-pill px-3';
    if (btnWork) btnWork.className = 'btn btn-sm ' + (type === 'Working Committee' ? 'btn-warning text-dark' : 'btn-outline-warning text-dark') + ' rounded-pill px-3';
}

function openAddCollabModal() {
    document.getElementById('collaborationModalTitle').innerText = 'Add New Academic Collaboration';
    document.getElementById('collab_id').value = 0;
    document.getElementById('collab_partner_name').value = '';
    document.getElementById('collab_partner_subtitle').value = '';
    document.getElementById('collab_mou_title').value = '';
    document.getElementById('collab_mou_ref_no').value = '';
    document.getElementById('collab_signed_date').value = '';
    document.getElementById('collab_validity_period').value = '3 Years';
    document.getElementById('collab_status').value = 'Active';
    document.getElementById('collab_sort_order').value = '<?= count($collaborations) + 1 ?>';
    document.getElementById('collab_partner_signatory').value = '';
    document.getElementById('collab_library_signatory').value = '';
    document.getElementById('collab_witness_details').value = '';
    document.getElementById('collab_summary_text').value = '';
    document.getElementById('collab_objectives').value = '';
    document.getElementById('collab_scope_modalities').value = '';
    
    var logoWrap = document.getElementById('collab_current_logo_wrap');
    if (logoWrap) logoWrap.classList.add('d-none');
    var pdfWrap = document.getElementById('collab_current_pdf_wrap');
    if (pdfWrap) pdfWrap.classList.add('d-none');

    var modal = new bootstrap.Modal(document.getElementById('collaborationModal'));
    modal.show();
}

function editCollaboration(c) {
    document.getElementById('collaborationModalTitle').innerText = 'Edit Academic Collaboration';
    document.getElementById('collab_id').value = c.id;
    document.getElementById('collab_partner_name').value = c.partner_name || '';
    document.getElementById('collab_partner_subtitle').value = c.partner_subtitle || '';
    document.getElementById('collab_mou_title').value = c.mou_title || '';
    document.getElementById('collab_mou_ref_no').value = c.mou_ref_no || '';
    document.getElementById('collab_signed_date').value = c.signed_date || '';
    document.getElementById('collab_validity_period').value = c.validity_period || '3 Years';
    document.getElementById('collab_status').value = c.status || 'Active';
    document.getElementById('collab_sort_order').value = c.sort_order || 0;
    document.getElementById('collab_partner_signatory').value = c.partner_signatory || '';
    document.getElementById('collab_library_signatory').value = c.library_signatory || '';
    document.getElementById('collab_witness_details').value = c.witness_details || '';
    document.getElementById('collab_summary_text').value = c.summary_text || '';
    document.getElementById('collab_objectives').value = c.objectives || '';
    document.getElementById('collab_scope_modalities').value = c.scope_modalities || '';

    // Handle Logo preview
    var logoWrap = document.getElementById('collab_current_logo_wrap');
    var logoImg = document.getElementById('collab_current_logo_img');
    if (c.partner_logo) {
        logoImg.src = '<?= BASE_URL ?>uploads/documents/' + c.partner_logo;
        logoWrap.classList.remove('d-none');
        logoWrap.classList.add('d-flex');
    } else {
        logoWrap.classList.add('d-none');
        logoWrap.classList.remove('d-flex');
    }

    // Handle PDF preview
    var pdfWrap = document.getElementById('collab_current_pdf_wrap');
    var pdfLink = document.getElementById('collab_current_pdf_link');
    if (c.mou_pdf) {
        pdfLink.href = '<?= BASE_URL ?>uploads/documents/' + c.mou_pdf;
        pdfLink.innerHTML = '<i class="fas fa-file-pdf me-1"></i> View ' + c.mou_pdf;
        pdfWrap.classList.remove('d-none');
        pdfWrap.classList.add('d-flex');
    } else {
        pdfWrap.classList.add('d-none');
        pdfWrap.classList.remove('d-flex');
    }

    var modal = new bootstrap.Modal(document.getElementById('collaborationModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
