<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/notifications.php';
require_once __DIR__ . '/includes/email.php';
require_once __DIR__ . '/includes/audit.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
