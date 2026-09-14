<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';

require_login();

$userId = $_SESSION['user_id'];
$roleCode = $_SESSION['role_code'];
$db = getDB();

$errors = [];

// Fetch current user from database
$uStmt = $db->prepare("
    SELECT u.*, r.role_name, r.role_code 
    FROM users u 
    JOIN roles r ON u.role_id = r.id 
    WHERE u.id = ?
");
$uStmt->execute([$userId]);
$user = $uStmt->fetch();

if (!$user) {
    set_flash_message('danger', 'User account not found.');
    header("Location: " . BASE_URL . "login.php");
    exit();
}

// If member, fetch membership details
$member = null;
if ($roleCode === 'MEMBER') {
    $mStmt = $db->prepare("
        SELECT m.*, mp.plan_name, ms.expiry_date, ms.status AS ms_status
        FROM members m 
        LEFT JOIN memberships ms ON m.id = ms.member_id
        LEFT JOIN membership_plans mp ON ms.plan_id = mp.id
        WHERE m.user_id = ?
        ORDER BY ms.id DESC LIMIT 1
    ");
    $mStmt->execute([$userId]);
    $member = $mStmt->fetch();
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token mismatch. Please reload the page.";
    } else {
        $action = $_POST['action'] ?? '';

        // 1. UPDATE PROFILE DETAILS
        if ($action === 'update_profile') {
            $fullName = sanitize_input($_POST['full_name'] ?? '');
            $email = sanitize_input($_POST['email'] ?? '');

            if (empty($fullName)) {
                $errors[] = "Full Name is required.";
            }

            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "A valid Email Address is required.";
            } else {
                // Check if email already used by another user
                $chkEmail = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $chkEmail->execute([$email, $userId]);
                if ($chkEmail->fetchColumn()) {
                    $errors[] = "Email address '{$email}' is already in use by another account.";
                }
            }

            // Member-specific fields
            $mobile = '';
            $address = '';
            $gender = 'Other';
            $dob = null;

            if ($roleCode === 'MEMBER') {
                $mobile = sanitize_input($_POST['mobile'] ?? '');
                $address = sanitize_input($_POST['address'] ?? '');
                $gender = sanitize_input($_POST['gender'] ?? 'Other');
                $dob = !empty($_POST['dob']) ? sanitize_input($_POST['dob']) : null;

                if (empty($mobile)) {
                    $errors[] = "Mobile number is required for members.";
                }
            }

            if (empty($errors)) {
                try {
                    $db->beginTransaction();

                    // Update users table
                    $upUser = $db->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                    $upUser->execute([$fullName, $email, $userId]);

                    // Update session
                    $_SESSION['full_name'] = $fullName;
                    $_SESSION['email'] = $email;

                    // Update members table if member
                    if ($roleCode === 'MEMBER' && $member) {
                        $upMem = $db->prepare("
                            UPDATE members 
                            SET mobile = ?, address = ?, gender = ?, dob = ? 
                            WHERE id = ?
                        ");
                        $upMem->execute([$mobile, $address, $gender, $dob, $member['id']]);
                    }

                    log_audit_action($userId, $roleCode, 'Update Profile', 'Users', "User updated profile details.");
                    $db->commit();

                    set_flash_message('success', 'Profile details updated successfully.');
                    header("Location: " . BASE_URL . "profile.php");
                    exit();

                } catch (Exception $e) {
                    $db->rollBack();
                    $errors[] = "Database update error: " . $e->getMessage();
                }
            }
        }

        // 2. CHANGE PASSWORD
        elseif ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            if (empty($currentPass)) {
                $errors[] = "Current Password is required.";
            } elseif (!password_verify($currentPass, $user['password'])) {
                $errors[] = "Current Password is incorrect.";
            }

            if (empty($newPass)) {
                $errors[] = "New Password is required.";
            } elseif (strlen($newPass) < 6) {
                $errors[] = "New Password must be at least 6 characters long.";
            } elseif ($newPass !== $confirmPass) {
                $errors[] = "New Password and Confirmation do not match.";
            }

            if (empty($errors)) {
                $newHash = password_hash($newPass, PASSWORD_DEFAULT);
                $upPass = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upPass->execute([$newHash, $userId]);

                log_audit_action($userId, $roleCode, 'Change Password', 'Users', "User updated their account password.");
                set_flash_message('success', 'Password changed successfully! Please use your new password next time you log in.');
                header("Location: " . BASE_URL . "profile.php");
                exit();
            }
        }
    }
}

// Refresh user info after potential POST changes
$uStmt->execute([$userId]);
$user = $uStmt->fetch();

if ($roleCode === 'MEMBER' && $member) {
    $mStmt->execute([$userId]);
    $member = $mStmt->fetch();
}

$pageTitle = "My Profile & Password Manager";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row g-4">
        <!-- Sidebar Navigation Column -->
        <div class="col-lg-3 col-xl-2 d-print-none">
            <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
        </div>

        <!-- Main Content Area Column -->
        <div class="col-lg-9 col-xl-10">
            <!-- Header Section -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="section-title mb-1">Profile & Password Manager</h2>
                    <p class="text-muted small mb-0">Manage your personal details, email address, and account security credentials.</p>
                </div>
                <div class="mt-2 mt-md-0">
                    <span class="badge bg-white text-dark border px-3 py-2 shadow-sm font-serif">
                        <i class="fas fa-shield-alt text-success me-1"></i> Account: 
                        <strong class="text-maroon" style="color: #8B1E26;"><?= escape($user['role_name']) ?></strong>
                    </span>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger shadow-sm mb-4">
                    <div class="fw-bold mb-1"><i class="fas fa-exclamation-circle me-1"></i> Please check the following issues:</div>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= escape($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Profile Summary Banner -->
            <div class="card sayak-card mb-4 border-0 shadow-sm overflow-hidden">
                <div class="card-body p-4 bg-white">
                    <div class="d-flex flex-column flex-md-row align-items-center gap-4">
                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center shadow flex-shrink-0" style="width: 76px; height: 76px; font-size: 28px; background: linear-gradient(135deg, #8B1E26 0%, #B22222 100%);">
                            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                        </div>
                        <div class="text-center text-md-start flex-grow-1">
                            <h4 class="font-serif fw-bold text-dark mb-1"><?= escape($user['full_name']) ?></h4>
                            <div class="d-flex flex-wrap justify-content-center justify-content-md-start align-items-center gap-2 text-muted small">
                                <span><i class="fas fa-envelope text-secondary me-1"></i> <?= escape($user['email']) ?></span>
                                <span>&bull;</span>
                                <span><i class="fas fa-calendar-alt text-secondary me-1"></i> Registered: <?= format_date($user['created_at']) ?></span>
                                <span>&bull;</span>
                                <span class="badge <?= $user['status'] === 'Active' ? 'bg-success' : 'bg-warning text-dark' ?>"><?= escape($user['status']) ?></span>
                            </div>
                        </div>
                        <?php if ($roleCode === 'MEMBER' && $member): ?>
                            <div class="text-center text-md-end p-3 rounded-3 bg-light border">
                                <div class="small text-muted">Library Card ID</div>
                                <div class="font-monospace fw-bold fs-5 text-maroon" style="color: #8B1E26;"><?= escape($member['member_code']) ?></div>
                                <span class="badge bg-gold text-dark font-serif" style="font-size: 11px;">
                                    <?= escape($member['plan_name'] ?: 'Standard Plan') ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 2-Column Grid: Details on Left, Password on Right -->
            <div class="row g-4">
                <!-- Column 1: Personal Profile Details -->
                <div class="col-lg-6">
                    <div class="card sayak-card h-100 border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom py-3 font-serif fw-bold fs-5">
                            <i class="fas fa-user-edit me-2 text-maroon" style="color: #8B1E26;"></i> Personal Information
                        </div>
                        <div class="card-body p-4">
                            <form action="" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="action" value="update_profile">

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Full Name <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted"><i class="fas fa-user"></i></span>
                                        <input type="text" name="full_name" class="form-control" value="<?= escape($user['full_name']) ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Email Address <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted"><i class="fas fa-envelope"></i></span>
                                        <input type="email" name="email" class="form-control" value="<?= escape($user['email']) ?>" required>
                                    </div>
                                    <small class="text-muted" style="font-size: 11px;">Used for authentication and library communications.</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">System Role</label>
                                    <input type="text" class="form-control bg-light text-muted" value="<?= escape($user['role_name']) ?>" readonly>
                                </div>

                                <?php if ($roleCode === 'MEMBER' && $member): ?>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Mobile Number <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted"><i class="fas fa-phone"></i></span>
                                            <input type="text" name="mobile" class="form-control" value="<?= escape($member['mobile']) ?>" required>
                                        </div>
                                    </div>

                                    <div class="row g-2 mb-3">
                                        <div class="col-sm-6">
                                            <label class="form-label fw-bold small">Gender</label>
                                            <select name="gender" class="form-select">
                                                <option value="Male" <?= ($member['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                                                <option value="Female" <?= ($member['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                                                <option value="Other" <?= ($member['gender'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                                            </select>
                                        </div>
                                        <div class="col-sm-6">
                                            <label class="form-label fw-bold small">Date of Birth</label>
                                            <input type="date" name="dob" class="form-control" value="<?= escape($member['dob'] ?? '') ?>">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Residential Address</label>
                                        <textarea name="address" class="form-control" rows="2" placeholder="Full postal address"><?= escape($member['address'] ?? '') ?></textarea>
                                    </div>
                                <?php endif; ?>

                                <button type="submit" class="btn btn-maroon w-100 font-serif py-2 fw-semibold" style="background-color: #8B1E26;">
                                    <i class="fas fa-save me-1"></i> Save Profile Details
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Password Management -->
                <div class="col-lg-6">
                    <div class="card sayak-card h-100 border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom py-3 font-serif fw-bold fs-5">
                            <i class="fas fa-key me-2 text-maroon" style="color: #8B1E26;"></i> Change Password
                        </div>
                        <div class="card-body p-4">
                            <form action="" method="POST">
                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                <input type="hidden" name="action" value="change_password">

                                <div class="alert alert-light border py-2 px-3 small text-muted mb-3">
                                    <i class="fas fa-info-circle text-primary me-1"></i> For account security, you must enter your current password to authorize this change.
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">Current Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted"><i class="fas fa-lock"></i></span>
                                        <input type="password" name="current_password" id="curPass" class="form-control" placeholder="Enter current password" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassVisibility('curPass', this)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold small">New Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted"><i class="fas fa-key"></i></span>
                                        <input type="password" name="new_password" id="newPass" class="form-control" placeholder="Minimum 6 characters" minlength="6" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassVisibility('newPass', this)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted" style="font-size: 11px;">Must be at least 6 characters long.</small>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold small">Confirm New Password <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted"><i class="fas fa-check-double"></i></span>
                                        <input type="password" name="confirm_password" id="confPass" class="form-control" placeholder="Repeat new password" minlength="6" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassVisibility('confPass', this)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-gold w-100 font-serif py-2 fw-bold text-dark">
                                    <i class="fas fa-shield-alt me-1"></i> Update & Set New Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
