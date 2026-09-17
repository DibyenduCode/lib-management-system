<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/notifications.php';
require_once __DIR__ . '/includes/email.php';
require_once __DIR__ . '/includes/audit.php';

$errors = [];
$membershipMode = get_setting('membership_mode', 'online');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($membershipMode === 'pdf') {
        set_flash_message('danger', 'Online registration is currently closed. Please download and submit the manual application form.');
        header("Location: " . BASE_URL . "usership.php");
        exit();
    }

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token mismatch. Please refresh and try again.";
    }

    $fullName = sanitize_input($_POST['full_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $mobile = sanitize_input($_POST['mobile'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $dob = sanitize_input($_POST['dob'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $selectedPlanId = (int)($_POST['plan_id'] ?? 1);

    if (empty($fullName)) $errors[] = "Full Name is required.";
    if (!$email) $errors[] = "A valid Email address is required.";
    if (empty($mobile)) $errors[] = "Mobile number is required.";
    if (empty($address)) $errors[] = "Address is required.";
    if (empty($password)) $errors[] = "Password is required.";
    if (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
    if ($password !== $confirmPassword) $errors[] = "Password confirmation does not match.";

    $db = getDB();

    if ($email) {
        $chkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $chkStmt->execute([$email]);
        if ($chkStmt->fetchColumn()) {
            $errors[] = "An account with this email address already exists.";
        }
    }

    // Process Profile Photo Upload if present
    $photoFilename = null;
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $tmpName = $_FILES['profile_photo']['tmp_name'];
        $origName = basename($_FILES['profile_photo']['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowedExts)) {
            $errors[] = "Invalid profile photo format. Allowed: JPG, PNG, WEBP.";
        } else {
            $photoFilename = 'member_' . time() . '_' . rand(100, 999) . '.' . $ext;
            if (!is_dir(UPLOAD_MEMBER_DIR)) {
                mkdir(UPLOAD_MEMBER_DIR, 0755, true);
            }
            move_uploaded_file($tmpName, UPLOAD_MEMBER_DIR . $photoFilename);
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            $passHash = password_hash($password, PASSWORD_DEFAULT);

            // 1. Insert User
            $uStmt = $db->prepare("
                INSERT INTO users (role_id, full_name, email, password, status, created_at)
                VALUES (3, ?, ?, ?, 'Active', NOW())
            ");
            $uStmt->execute([$fullName, $email, $passHash]);
            $userId = $db->lastInsertId();

            // 2. Generate Member Code
            $memberCode = generate_member_id($db);

            // 3. Insert Member
            $mStmt = $db->prepare("
                INSERT INTO members (user_id, member_code, mobile, address, dob, profile_photo, membership_status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'Active', NOW())
            ");
            $mStmt->execute([$userId, $memberCode, $mobile, $address, $dob ?: null, $photoFilename]);
            $memberId = $db->lastInsertId();

            // 4. Create Initial Membership Record
            $pStmt = $db->prepare("SELECT duration_months FROM membership_plans WHERE id = ?");
            $pStmt->execute([$selectedPlanId]);
            $durationMonths = (int)$pStmt->fetchColumn() ?: 1;

            $startDate = date('Y-m-d');
            $expiryDate = date('Y-m-d', strtotime("+{$durationMonths} months"));

            $msStmt = $db->prepare("
                INSERT INTO memberships (member_id, plan_id, start_date, expiry_date, status)
                VALUES (?, ?, ?, ?, 'Active')
            ");
            $msStmt->execute([$memberId, $selectedPlanId, $startDate, $expiryDate]);

            // Notify Librarians & Admins
            notify_role('SUPER_ADMIN', 'New Member Registered', "New member registered: {$fullName} ({$memberCode})");
            notify_role('LIBRARIAN', 'New Member Registered', "New member registered: {$fullName} ({$memberCode})");

            log_audit_action($userId, 'MEMBER', 'Member Self Registration', 'Members', "Member ID: {$memberCode}");

            $db->commit();

            set_flash_message('success', "Registration successful! Your Member ID is {$memberCode}. You can now log in.");
            header('Location: ' . BASE_URL . 'login.php');
            exit();

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Registration error: " . $e->getMessage();
        }
    }
}

// Fetch active membership plans for selection
$db = getDB();
$plans = $db->query("SELECT * FROM membership_plans WHERE status = 'Active' ORDER BY duration_months ASC")->fetchAll();

if ($membershipMode === 'pdf') {
    $pdfUrl = get_membership_form_url();
    $pdfTitle = get_setting('membership_pdf_title', 'Sayak Library Membership Application Form');
    $pdfNotice = get_setting('membership_pdf_notice', 'Online user registration is currently offline. Prospective members are kindly requested to download the official membership form, fill it out, and submit it at the library desk.');
    $pdfInstructions = get_setting('membership_pdf_instructions', "1. Print the downloaded form on clean A4 white paper.\n2. Complete all fields in CAPITAL letters and attach 2 recent passport-size photographs.\n3. Attach photocopies of Valid Photo ID Proof (Aadhaar / Voter ID / Student ID) and Address Proof.\n4. Submit the completed form along with membership fee at the library counter during official hours.\n5. Your library membership card and access credentials will be issued upon counter verification.");
    
    // Split instructions into individual lines
    $instructionSteps = array_filter(array_map('trim', explode("\n", $pdfInstructions)));

    $pageTitle = $pdfTitle;
    require_once __DIR__ . '/includes/header.php';
    ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <!-- Admin Notification (If logged in as Admin / Librarian) -->
                <?php if (isset($_SESSION['role_code']) && in_array($_SESSION['role_code'], ['SUPER_ADMIN', 'LIBRARIAN'])): ?>
                    <div class="alert alert-warning border-start border-4 border-warning shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 py-2">
                        <div class="small">
                            <strong><i class="fas fa-user-shield me-1 text-warning"></i> Admin Notice:</strong> Public visitors currently see this PDF Application Form download page. You can register or approve members manually in the admin portal.
                        </div>
                        <div class="d-flex gap-2">
                            <a href="<?= BASE_URL ?>admin/users/index.php" class="btn btn-sm btn-dark">
                                <i class="fas fa-user-plus me-1"></i> Register Member (Admin)
                            </a>
                            <a href="<?= BASE_URL ?>admin/settings/index.php#membership-settings" class="btn btn-sm btn-outline-dark">
                                <i class="fas fa-cog me-1"></i> Signup Mode Settings
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Main Hero Application Card -->
                <div class="card sayak-card border-0 shadow-sm overflow-hidden mb-4">
                    <div class="p-4 p-md-5 text-white" style="background: linear-gradient(135deg, #7A0C0C 0%, #4A0505 100%);">
                        <div class="text-center text-md-start d-md-flex justify-content-between align-items-center">
                            <div class="mb-4 mb-md-0">
                                <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-3">
                                    <i class="fas fa-file-pdf me-1"></i> Official Application Form
                                </span>
                                <h2 class="font-serif fw-bold text-white mb-2"><?= escape($pdfTitle) ?></h2>
                                <p class="mb-0 text-white-50" style="max-width: 650px;">
                                    <?= nl2br(escape($pdfNotice)) ?>
                                </p>
                            </div>
                            <?php if (!empty($pdfUrl)): ?>
                                <div class="text-center text-md-end flex-shrink-0">
                                    <a href="<?= escape($pdfUrl) ?>" download class="btn btn-warning btn-lg fw-bold px-4 py-3 shadow text-dark d-block mb-2">
                                        <i class="fas fa-download me-2"></i> Download PDF Form
                                    </a>
                                    <a href="<?= escape($pdfUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light btn-sm px-3 py-2">
                                        <i class="fas fa-external-link-alt me-1"></i> Preview in Browser
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (empty($pdfUrl)): ?>
                        <div class="card-body p-4 text-center bg-light">
                            <div class="py-4">
                                <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                                <h5 class="fw-bold text-dark">Application Form PDF Pending Upload</h5>
                                <p class="text-muted small mb-3">The library administration has not uploaded the printable membership PDF yet. Please visit the library desk in person or check back soon.</p>
                                <?php if (isset($_SESSION['role_code']) && in_array($_SESSION['role_code'], ['SUPER_ADMIN', 'LIBRARIAN'])): ?>
                                    <a href="<?= BASE_URL ?>admin/settings/index.php#membership-settings" class="btn btn-maroon btn-sm" style="background-color: #7A0C0C;">
                                        <i class="fas fa-upload me-1"></i> Upload Application PDF in Settings
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Step-by-Step Counter Submission Roadmap -->
                <div class="card sayak-card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white font-serif py-3 fw-bold fs-5 border-bottom">
                        <i class="fas fa-clipboard-list text-maroon me-2" style="color: #7A0C0C;"></i> How to Apply & Submit Your Membership
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($instructionSteps)): ?>
                            <div class="row g-3">
                                <?php foreach ($instructionSteps as $idx => $step): ?>
                                    <div class="col-12">
                                        <div class="d-flex align-items-start p-3 bg-light rounded-3 border">
                                            <div class="rounded-circle bg-maroon text-white fw-bold d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 38px; height: 38px; background-color: #7A0C0C;">
                                                <?= $idx + 1 ?>
                                            </div>
                                            <div class="pt-1">
                                                <span class="text-dark fw-semibold"><?= escape(preg_replace('/^[0-9]+[\.\-\)]\s*/', '', $step)) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Submission Helpdesk Info -->
                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100 bg-white">
                                    <h6 class="fw-bold text-maroon mb-2" style="color: #7A0C0C;">
                                        <i class="fas fa-map-marker-alt me-1"></i> Submission Desk Address
                                    </h6>
                                    <p class="small text-muted mb-0">
                                        <?= escape(get_setting('address', '124 Academic Avenue, College Street, Kolkata, West Bengal - 700073')) ?>
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100 bg-white">
                                    <h6 class="fw-bold text-maroon mb-2" style="color: #7A0C0C;">
                                        <i class="fas fa-clock me-1"></i> Desk Timings & Help
                                    </h6>
                                    <p class="small text-muted mb-1">
                                        <strong>Opening Hours:</strong> <?= escape(get_setting('opening_hours', 'Monday - Saturday: 9:00 AM - 7:00 PM')) ?>
                                    </p>
                                    <p class="small text-muted mb-0">
                                        <strong>Phone:</strong> <?= escape(get_setting('phone', '+91 33 2241 8900')) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Available Membership Plans Reference Table -->
                <?php if (!empty($plans)): ?>
                    <div class="card sayak-card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white font-serif py-3 fw-bold fs-5 border-bottom">
                            <i class="fas fa-layer-group text-maroon me-2" style="color: #7A0C0C;"></i> Available Membership Subscription Plans
                        </div>
                        <div class="card-body p-4">
                            <p class="text-muted small mb-3">Please choose your preferred subscription category when filling out the physical application form:</p>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Plan Name</th>
                                            <th>Duration</th>
                                            <th>Subscription Fee</th>
                                            <th>Borrowing Privileges</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($plans as $pl): ?>
                                            <tr>
                                                <td class="fw-bold font-serif text-dark"><?= escape($pl['plan_name']) ?></td>
                                                <td><?= (int)$pl['duration_months'] ?> Months</td>
                                                <td class="fw-bold text-success"><?= format_currency($pl['price']) ?></td>
                                                <td class="small text-muted"><?= escape($pl['description'] ?? 'Physical book borrowing & digital PDF access') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($pdfUrl)): ?>
                    <!-- Embedded PDF Viewer Preview Card -->
                    <div class="card sayak-card border-0 shadow-sm overflow-hidden mb-4">
                        <div class="card-header bg-white font-serif py-3 fw-bold d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-file-pdf text-danger me-2"></i> Application Form Preview</span>
                            <a href="<?= escape($pdfUrl) ?>" download class="btn btn-outline-maroon btn-sm" style="color: #7A0C0C; border-color: #7A0C0C;">
                                <i class="fas fa-download me-1"></i> Download Form
                            </a>
                        </div>
                        <div style="height: 650px;">
                            <iframe 
                                src="<?= escape($pdfUrl) ?>" 
                                style="width: 100%; height: 650px; border: 0;" 
                                loading="lazy"
                            ></iframe>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Login Shortcut for Existing Members -->
                <div class="text-center mt-4">
                    <p class="text-muted small mb-0">
                        Already have an active membership account? 
                        <a href="<?= BASE_URL ?>login.php" class="fw-bold text-maroon ms-1" style="color: #7A0C0C;">
                            Member Portal Login <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

$pageTitle = "Usership Form - Join the Library";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border">
                <div class="text-center mb-4">
                    <i class="fas fa-id-card fa-3x text-maroon mb-2" style="color: #7A0C0C;"></i>
                    <h2 class="section-title text-center">Join Sayak Library</h2>
                    <p class="text-muted">Fill out the membership application form to get instant access to physical borrowing and digital PDF resources.</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?>
                                <li><?= escape($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" placeholder="e.g. Sudipto Banerjee" value="<?= escape($_POST['full_name'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="name@example.com" value="<?= escape($_POST['email'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mobile Number <span class="text-danger">*</span></label>
                            <input type="tel" name="mobile" class="form-control" placeholder="+91 98300 00000" value="<?= escape($_POST['mobile'] ?? '') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date of Birth</label>
                            <input type="date" name="dob" class="form-control" value="<?= escape($_POST['dob'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Full Address <span class="text-danger">*</span></label>
                            <textarea name="address" class="form-control" rows="2" placeholder="House No, Street, Landmark, City, Pincode" required><?= escape($_POST['address'] ?? '') ?></textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label fw-bold">Select Preferred Membership Plan</label>
                            <select name="plan_id" class="form-select">
                                <?php foreach ($plans as $p): ?>
                                    <option value="<?= $p['id'] ?>">
                                        <?= escape($p['plan_name']) ?> - <?= format_currency($p['price']) ?> (<?= $p['duration_months'] ?> Months)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="text-muted">Membership payments can be settled via Cash at the library counter.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Profile Photo (Optional)</label>
                            <input type="file" name="profile_photo" class="form-control" accept="image/*">
                        </div>

                        <div class="col-md-6"></div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" placeholder="At least 6 characters" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
                        </div>
                    </div>

                    <div class="form-check my-4">
                        <input class="form-check-input" type="checkbox" id="termsCheck" required checked>
                        <label class="form-check-label small text-secondary" for="termsCheck">
                            I agree to abide by the <a href="<?= BASE_URL ?>rules.php" target="_blank">Sayak Library Rules & Regulations</a>.
                        </label>
                    </div>

                    <button type="submit" class="btn btn-maroon btn-lg w-100 py-3 font-serif fw-bold" style="background-color: #7A0C0C;">
                        <i class="fas fa-user-plus me-2"></i> Submit Membership Application
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
