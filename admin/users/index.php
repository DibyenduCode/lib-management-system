<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

require_role('SUPER_ADMIN');

$db = getDB();
$currentUserId = $_SESSION['user_id'];
$errors = [];

// ============================================================
// 1. HANDLE POST ACTIONS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token mismatch. Please refresh and try again.";
    } else {
        $action = $_POST['action'] ?? '';

        // A. CREATE ACCOUNT (LIBRARIAN OR SUPER ADMIN)
        if ($action === 'create_user') {
            $roleCode = sanitize_input($_POST['role_code'] ?? 'LIBRARIAN');
            $fullName = sanitize_input($_POST['full_name'] ?? '');
            $email = sanitize_input($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $status = sanitize_input($_POST['status'] ?? 'Active');
            $phone = sanitize_input($_POST['phone'] ?? '');
            $empCode = sanitize_input($_POST['employee_code'] ?? '');

            if (empty($fullName)) $errors[] = "Full Name is required.";
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "A valid Email Address is required.";
            if (empty($password)) $errors[] = "Password is required.";
            elseif (strlen($password) < 6) $errors[] = "Password must be at least 6 characters long.";
            elseif ($password !== $confirmPassword) $errors[] = "Password and confirmation do not match.";

            // Role verification
            $rStmt = $db->prepare("SELECT id, role_name FROM roles WHERE role_code = ?");
            $rStmt->execute([$roleCode]);
            $roleData = $rStmt->fetch();
            if (!$roleData) $errors[] = "Invalid role selected.";

            // Email uniqueness
            $eStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $eStmt->execute([$email]);
            if ($eStmt->fetchColumn()) {
                $errors[] = "Email '{$email}' is already registered in the system.";
            }

            // If Librarian, check employee code
            if ($roleCode === 'LIBRARIAN') {
                if (empty($empCode)) {
                    $empCode = generate_employee_code($db);
                } else {
                    $chkCode = $db->prepare("SELECT id FROM librarians WHERE employee_code = ?");
                    $chkCode->execute([$empCode]);
                    if ($chkCode->fetchColumn()) {
                        $errors[] = "Employee Code '{$empCode}' is already assigned to another librarian.";
                    }
                }
            }

            if (empty($errors)) {
                try {
                    $db->beginTransaction();

                    $pHash = password_hash($password, PASSWORD_DEFAULT);
                    $insUser = $db->prepare("
                        INSERT INTO users (role_id, full_name, email, password, status, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $insUser->execute([$roleData['id'], $fullName, $email, $pHash, $status]);
                    $newUserId = (int)$db->lastInsertId();

                    if ($roleCode === 'LIBRARIAN') {
                        $insLib = $db->prepare("
                            INSERT INTO librarians (user_id, employee_code, phone, created_at)
                            VALUES (?, ?, ?, NOW())
                        ");
                        $insLib->execute([$newUserId, $empCode, $phone]);
                    } elseif ($roleCode === 'MEMBER') {
                        $memCode = generate_member_id($db);
                        $insMem = $db->prepare("
                            INSERT INTO members (user_id, member_code, mobile, address, membership_status, created_at)
                            VALUES (?, ?, ?, ?, 'Active', NOW())
                        ");
                        $insMem->execute([$newUserId, $memCode, $phone ?: 'Not provided', 'Registered via Admin Panel']);
                        $newMemberId = (int)$db->lastInsertId();

                        // Assign default active membership plan
                        $planStmt = $db->query("SELECT id, duration_months FROM membership_plans ORDER BY id ASC LIMIT 1");
                        $defPlan = $planStmt->fetch();
                        if ($defPlan) {
                            $durMonths = (int)($defPlan['duration_months'] ?: 12);
                            $expDate = date('Y-m-d', strtotime("+{$durMonths} months"));
                            $db->prepare("
                                INSERT INTO memberships (member_id, plan_id, start_date, expiry_date, status)
                                VALUES (?, ?, CURDATE(), ?, 'Active')
                            ")->execute([$newMemberId, $defPlan['id'], $expDate]);
                        }
                    }

                    log_audit_action($currentUserId, 'SUPER_ADMIN', 'Create User', 'Users', "Created {$roleData['role_name']}: {$fullName} ({$email})");
                    $db->commit();

                    set_flash_message('success', "New {$roleData['role_name']} account for '{$fullName}' created successfully.");
                    header("Location: " . BASE_URL . "admin/users/index.php");
                    exit();

                } catch (Exception $e) {
                    $db->rollBack();
                    $errors[] = "Error creating account: " . $e->getMessage();
                }
            }
        }

        // B. EDIT USER DETAILS
        elseif ($action === 'edit_user') {
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            $fullName = sanitize_input($_POST['full_name'] ?? '');
            $email = sanitize_input($_POST['email'] ?? '');
            $status = sanitize_input($_POST['status'] ?? 'Active');
            $phone = sanitize_input($_POST['phone'] ?? '');
            $empCode = sanitize_input($_POST['employee_code'] ?? '');

            if ($targetUserId <= 0) $errors[] = "Invalid user account.";
            if (empty($fullName)) $errors[] = "Full Name is required.";
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";

            // Prevent self-suspension
            if ($targetUserId === $currentUserId && $status !== 'Active') {
                $errors[] = "You cannot suspend your own active administrator account.";
            }

            // Check email uniqueness
            $eStmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $eStmt->execute([$email, $targetUserId]);
            if ($eStmt->fetchColumn()) {
                $errors[] = "Email '{$email}' is already taken by another account.";
            }

            // Fetch target user role
            $tStmt = $db->prepare("SELECT u.*, r.role_code FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
            $tStmt->execute([$targetUserId]);
            $targetUser = $tStmt->fetch();

            if (!$targetUser) $errors[] = "Target account does not exist.";

            if ($targetUser && $targetUser['role_code'] === 'LIBRARIAN' && !empty($empCode)) {
                $cStmt = $db->prepare("SELECT id FROM librarians WHERE employee_code = ? AND user_id != ?");
                $cStmt->execute([$empCode, $targetUserId]);
                if ($cStmt->fetchColumn()) {
                    $errors[] = "Employee Code '{$empCode}' is already in use by another librarian.";
                }
            }

            if (empty($errors)) {
                try {
                    $db->beginTransaction();

                    $uUpdate = $db->prepare("UPDATE users SET full_name = ?, email = ?, status = ? WHERE id = ?");
                    $uUpdate->execute([$fullName, $email, $status, $targetUserId]);

                    if ($targetUser['role_code'] === 'LIBRARIAN') {
                        $lUpdate = $db->prepare("UPDATE librarians SET employee_code = ?, phone = ? WHERE user_id = ?");
                        $lUpdate->execute([$empCode, $phone, $targetUserId]);
                    } elseif ($targetUser['role_code'] === 'MEMBER') {
                        $mUpdate = $db->prepare("UPDATE members SET mobile = ? WHERE user_id = ?");
                        $mUpdate->execute([$phone, $targetUserId]);
                    }

                    // If updating current user, refresh session
                    if ($targetUserId === $currentUserId) {
                        $_SESSION['full_name'] = $fullName;
                        $_SESSION['email'] = $email;
                    }

                    log_audit_action($currentUserId, 'SUPER_ADMIN', 'Edit User', 'Users', "Updated user ID {$targetUserId} ({$fullName})");
                    $db->commit();

                    set_flash_message('success', "Account details for '{$fullName}' updated successfully.");
                    header("Location: " . BASE_URL . "admin/users/index.php");
                    exit();

                } catch (Exception $e) {
                    $db->rollBack();
                    $errors[] = "Error updating user: " . $e->getMessage();
                }
            }
        }

        // C. RESET USER PASSWORD
        elseif ($action === 'reset_password') {
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($targetUserId <= 0) $errors[] = "Invalid user account.";
            if (empty($newPassword)) $errors[] = "New Password is required.";
            elseif (strlen($newPassword) < 6) $errors[] = "Password must be at least 6 characters long.";
            elseif ($newPassword !== $confirmPassword) $errors[] = "Password and confirmation do not match.";

            if (empty($errors)) {
                $pHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $pUpdate = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                $pUpdate->execute([$pHash, $targetUserId]);

                log_audit_action($currentUserId, 'SUPER_ADMIN', 'Reset Password', 'Users', "Password reset for user ID {$targetUserId}");
                set_flash_message('success', "Password successfully updated for user account.");
                header("Location: " . BASE_URL . "admin/users/index.php");
                exit();
            }
        }

        // D. TOGGLE STATUS
        elseif ($action === 'toggle_status') {
            $targetUserId = (int)($_POST['user_id'] ?? 0);
            $newStatus = sanitize_input($_POST['new_status'] ?? 'Active');

            if ($targetUserId === $currentUserId) {
                $errors[] = "You cannot suspend your own logged-in account.";
            } else {
                $db->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$newStatus, $targetUserId]);
                log_audit_action($currentUserId, 'SUPER_ADMIN', 'Toggle User Status', 'Users', "Set user {$targetUserId} to {$newStatus}");
                set_flash_message('info', "User account status changed to {$newStatus}.");
                header("Location: " . BASE_URL . "admin/users/index.php");
                exit();
            }
        }

        // E. DELETE USER
        elseif ($action === 'delete_user') {
            $targetUserId = (int)($_POST['user_id'] ?? 0);

            if ($targetUserId === $currentUserId) {
                $errors[] = "You cannot delete your own logged-in administrator account.";
            } else {
                // Check if target is last Super Admin
                $chkAdmin = $db->prepare("SELECT role_id FROM users WHERE id = ?");
                $chkAdmin->execute([$targetUserId]);
                $roleId = (int)$chkAdmin->fetchColumn();

                if ($roleId === 1) {
                    $totalAdmins = (int)$db->query("SELECT COUNT(*) FROM users WHERE role_id = 1")->fetchColumn();
                    if ($totalAdmins <= 1) {
                        $errors[] = "Cannot delete the only remaining Super Admin in the system.";
                    }
                }

                if (empty($errors)) {
                    $db->prepare("DELETE FROM users WHERE id = ?")->execute([$targetUserId]);
                    log_audit_action($currentUserId, 'SUPER_ADMIN', 'Delete User', 'Users', "Deleted user account ID {$targetUserId}");
                    set_flash_message('success', "User account has been permanently removed.");
                    header("Location: " . BASE_URL . "admin/users/index.php");
                    exit();
                }
            }
        }
    }
}

// ============================================================
// 2. FETCH DATA & FILTERING
// ============================================================
$roleFilter = sanitize_input($_GET['role'] ?? '');
$statusFilter = sanitize_input($_GET['status'] ?? '');
$search = sanitize_input($_GET['q'] ?? '');

$whereClauses = [];
$params = [];

if (!empty($roleFilter)) {
    $whereClauses[] = "r.role_code = ?";
    $params[] = $roleFilter;
}

if (!empty($statusFilter)) {
    $whereClauses[] = "u.status = ?";
    $params[] = $statusFilter;
}

if (!empty($search)) {
    $whereClauses[] = "(u.full_name LIKE ? OR u.email LIKE ? OR l.employee_code LIKE ? OR m.member_code LIKE ?)";
    $term = "%{$search}%";
    $params = array_merge($params, [$term, $term, $term, $term]);
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

$uListStmt = $db->prepare("
    SELECT u.*, r.role_name, r.role_code,
           l.employee_code, l.phone AS librarian_phone,
           m.member_code, m.mobile AS member_mobile
    FROM users u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN librarians l ON u.id = l.user_id
    LEFT JOIN members m ON u.id = m.user_id
    {$whereSql}
    ORDER BY r.id ASC, u.id DESC
");
$uListStmt->execute($params);
$usersList = $uListStmt->fetchAll();

// Metrics
$countSuperAdmins = (int)$db->query("SELECT COUNT(*) FROM users WHERE role_id = 1")->fetchColumn();
$countLibrarians = (int)$db->query("SELECT COUNT(*) FROM users WHERE role_id = 2")->fetchColumn();
$countMembers = (int)$db->query("SELECT COUNT(*) FROM users WHERE role_id = 3")->fetchColumn();
$countActive = (int)$db->query("SELECT COUNT(*) FROM users WHERE status = 'Active'")->fetchColumn();

// Auto-suggested next employee code for new librarian
$nextEmpCode = generate_employee_code($db);

$pageTitle = "Staff & User Account Manager";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row g-4">
        <!-- Sidebar Navigation Column -->
        <div class="col-lg-3 col-xl-2 d-print-none">
            <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
        </div>

        <!-- Main Content Area Column -->
        <div class="col-lg-9 col-xl-10">
            <!-- Header Section -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="section-title mb-1">Staff & Admin Account Manager</h2>
                    <p class="text-muted small mb-0">Create and manage multiple Librarian and Super Admin accounts, update credentials, and control access permissions.</p>
                </div>
                <div class="mt-2 mt-md-0">
                    <button type="button" class="btn btn-maroon btn-sm font-serif" data-bs-toggle="modal" data-bs-target="#createUserModal" style="background-color: #8B1E26;">
                        <i class="fas fa-user-plus me-1"></i> Create New Account
                    </button>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger shadow-sm mb-4">
                    <div class="fw-bold mb-1"><i class="fas fa-exclamation-circle me-1"></i> Action could not be completed:</div>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $err): ?>
                            <li><?= escape($err) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Metrics Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="metric-card border-start border-4 border-maroon py-3" style="border-color: #8B1E26 !important;">
                        <div class="metric-value" style="color: #8B1E26;"><?= $countSuperAdmins ?></div>
                        <div class="metric-label"><i class="fas fa-user-shield me-1"></i> Super Admins</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="metric-card border-start border-4 border-primary py-3">
                        <div class="metric-value text-primary"><?= $countLibrarians ?></div>
                        <div class="metric-label"><i class="fas fa-user-cog me-1"></i> Librarians</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="metric-card border-start border-4 border-info py-3">
                        <div class="metric-value text-info"><?= $countMembers ?></div>
                        <div class="metric-label"><i class="fas fa-users me-1"></i> Library Members</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="metric-card border-start border-4 border-success py-3">
                        <div class="metric-value text-success"><?= $countActive ?></div>
                        <div class="metric-label"><i class="fas fa-check-circle me-1"></i> Active Accounts</div>
                    </div>
                </div>
            </div>

            <!-- Filter & Search Bar -->
            <div class="card sayak-card mb-4 p-3 bg-white border-0 shadow-sm">
                <form action="" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light text-muted"><i class="fas fa-search"></i></span>
                            <input type="text" name="q" class="form-control" placeholder="Search name, email, or employee code..." value="<?= escape($search) ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select name="role" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Account Roles</option>
                            <option value="SUPER_ADMIN" <?= $roleFilter === 'SUPER_ADMIN' ? 'selected' : '' ?>>Super Admins Only</option>
                            <option value="LIBRARIAN" <?= $roleFilter === 'LIBRARIAN' ? 'selected' : '' ?>>Librarians Only</option>
                            <option value="MEMBER" <?= $roleFilter === 'MEMBER' ? 'selected' : '' ?>>Members Only</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="Active" <?= $statusFilter === 'Active' ? 'selected' : '' ?>>Active Only</option>
                            <option value="Suspended" <?= $statusFilter === 'Suspended' ? 'selected' : '' ?>>Suspended Only</option>
                            <option value="Restricted" <?= $statusFilter === 'Restricted' ? 'selected' : '' ?>>Restricted Only</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-maroon btn-sm w-100" style="background-color: #8B1E26;">Filter</button>
                        <?php if (!empty($search) || !empty($roleFilter) || !empty($statusFilter)): ?>
                            <a href="<?= BASE_URL ?>admin/users/index.php" class="btn btn-outline-secondary btn-sm" title="Clear Filters"><i class="fas fa-times"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Users Directory Table Card -->
            <div class="card sayak-card border-0 shadow-sm overflow-hidden">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <span class="fw-bold font-serif fs-5">
                        <i class="fas fa-users-cog me-2 text-maroon" style="color: #8B1E26;"></i> System Accounts Directory
                    </span>
                    <span class="badge bg-light text-dark border"><?= count($usersList) ?> Accounts Listed</span>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($usersList)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Staff / User</th>
                                        <th>Role</th>
                                        <th>Staff / Card Code</th>
                                        <th>Contact Phone</th>
                                        <th>Status</th>
                                        <th>Registered</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($usersList as $u): ?>
                                        <tr class="<?= $u['id'] === $currentUserId ? 'table-warning-subtle' : '' ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center me-2 flex-shrink-0 shadow-sm" style="width:36px; height:36px; font-weight:700; background-color: <?= $u['role_code'] === 'SUPER_ADMIN' ? '#8B1E26' : ($u['role_code'] === 'LIBRARIAN' ? '#0d6efd' : '#6c757d') ?>;">
                                                        <?= strtoupper(substr($u['full_name'], 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <strong class="font-serif text-dark d-block">
                                                            <?= escape($u['full_name']) ?>
                                                            <?php if ($u['id'] === $currentUserId): ?>
                                                                <span class="badge bg-dark ms-1" style="font-size:10px;">YOU</span>
                                                            <?php endif; ?>
                                                        </strong>
                                                        <small class="text-muted"><?= escape($u['email']) ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($u['role_code'] === 'SUPER_ADMIN'): ?>
                                                    <span class="badge text-white px-2 py-1" style="background-color: #8B1E26;"><i class="fas fa-user-shield me-1"></i> Super Admin</span>
                                                <?php elseif ($u['role_code'] === 'LIBRARIAN'): ?>
                                                    <span class="badge bg-primary px-2 py-1"><i class="fas fa-user-cog me-1"></i> Librarian</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary px-2 py-1"><i class="fas fa-user me-1"></i> Member</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($u['employee_code'])): ?>
                                                    <strong class="font-monospace text-primary"><?= escape($u['employee_code']) ?></strong>
                                                <?php elseif (!empty($u['member_code'])): ?>
                                                    <span class="font-monospace text-secondary"><?= escape($u['member_code']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted small">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-dark">
                                                    <?= escape($u['librarian_phone'] ?: ($u['member_mobile'] ?: '—')) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($u['status'] === 'Active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php elseif ($u['status'] === 'Suspended'): ?>
                                                    <span class="badge bg-danger">Suspended</span>
                                                <?php elseif ($u['status'] === 'Restricted'): ?>
                                                    <span class="badge bg-warning text-dark">Restricted</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= escape($u['status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?= format_date($u['created_at']) ?></small>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    <!-- Edit Modal Trigger -->
                                                    <button type="button" class="btn btn-outline-secondary" title="Edit Account" 
                                                            onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                                                'id' => $u['id'],
                                                                'full_name' => $u['full_name'],
                                                                'email' => $u['email'],
                                                                'status' => $u['status'],
                                                                'role_code' => $u['role_code'],
                                                                'role_name' => $u['role_name'],
                                                                'employee_code' => $u['employee_code'] ?? '',
                                                                'phone' => $u['librarian_phone'] ?? ''
                                                            ])) ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>

                                                    <!-- Reset Password Modal Trigger -->
                                                    <button type="button" class="btn btn-outline-dark" title="Reset Password"
                                                            onclick="openPasswordModal(<?= $u['id'] ?>, '<?= escape($u['full_name']) ?>')">
                                                        <i class="fas fa-key"></i>
                                                    </button>

                                                    <!-- Toggle Status Button -->
                                                    <?php if ($u['id'] !== $currentUserId): ?>
                                                        <form action="" method="POST" class="d-inline" onsubmit="return confirm('Change status for this account?');">
                                                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                            <input type="hidden" name="action" value="toggle_status">
                                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                            <?php if ($u['status'] === 'Suspended'): ?>
                                                                <input type="hidden" name="new_status" value="Active">
                                                                <button type="submit" class="btn btn-outline-success" title="Activate Account"><i class="fas fa-check"></i></button>
                                                            <?php else: ?>
                                                                <input type="hidden" name="new_status" value="Suspended">
                                                                <button type="submit" class="btn btn-outline-warning text-dark" title="Suspend Account"><i class="fas fa-ban"></i></button>
                                                            <?php endif; ?>
                                                        </form>

                                                        <!-- Delete Button -->
                                                        <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete account for \'<?= escape($u['full_name']) ?>\'?');">
                                                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                            <input type="hidden" name="action" value="delete_user">
                                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                            <button type="submit" class="btn btn-outline-danger" title="Delete Account"><i class="fas fa-trash"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-5 text-center text-muted">
                            <i class="fas fa-users-slash fa-3x mb-3 text-secondary"></i>
                            <p class="mb-0">No accounts found matching your query.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL: CREATE NEW ACCOUNT (LIBRARIAN / SUPER ADMIN)           -->
<!-- ============================================================ -->
<div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #8B1E26;">
                <h5 class="modal-title font-serif" id="createUserModalLabel">
                    <i class="fas fa-user-plus me-2"></i> Create Staff / Administrator Account
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="create_user">

                <div class="modal-body p-4">
                    <div class="alert alert-light border small text-muted mb-4">
                        <i class="fas fa-info-circle text-primary me-1"></i> You can create new <strong>Librarian</strong> staff, registered <strong>Library Members</strong>, or additional <strong>Super Admin</strong> executive accounts.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Account Role <span class="text-danger">*</span></label>
                            <select name="role_code" id="newRoleSelect" class="form-select" onchange="toggleLibrarianFields(this.value)" required>
                                <option value="LIBRARIAN" selected>Librarian (Daily Operations Staff)</option>
                                <option value="SUPER_ADMIN">Super Admin (Full Administrative Authority)</option>
                                <option value="MEMBER">Library Member (Borrower / Reader)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Account Status</label>
                            <select name="status" class="form-select">
                                <option value="Active" selected>Active</option>
                                <option value="Suspended">Suspended</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" class="form-control" placeholder="e.g. Ramesh Chandra Sen" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Official Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="e.g. librarian2@sayaklibrary.org" required>
                        </div>

                        <!-- Librarian-specific Employee Code and Phone -->
                        <div id="librarianExtraFields" class="col-12">
                            <div class="p-3 bg-light rounded-3 border row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Employee Code <span class="text-danger">*</span></label>
                                    <input type="text" name="employee_code" id="newEmpCode" class="form-control font-monospace" value="<?= escape($nextEmpCode) ?>">
                                    <small class="text-muted" style="font-size: 11px;">Auto-generated unique code. Can be customized if needed.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Contact Phone Number</label>
                                    <input type="text" name="phone" class="form-control" placeholder="+91 98300 XXXXX">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Initial Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="password" id="createPassInput" class="form-control" placeholder="Min 6 characters" minlength="6" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassVisibility('createPassInput', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Confirm Password <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" id="createConfirmInput" class="form-control" placeholder="Repeat password" minlength="6" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassVisibility('createConfirmInput', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-maroon font-serif px-4" style="background-color: #8B1E26;">
                        <i class="fas fa-check-circle me-1"></i> Create Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL: EDIT ACCOUNT DETAILS                                   -->
<!-- ============================================================ -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white" style="background-color: #8B1E26;">
                <h5 class="modal-title font-serif" id="editUserModalLabel">
                    <i class="fas fa-user-edit me-2"></i> Edit Account Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="editUserId">

                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Account Role</label>
                            <input type="text" id="editRoleName" class="form-control bg-light text-muted" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Account Status</label>
                            <select name="status" id="editStatus" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Suspended">Suspended</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="full_name" id="editFullName" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" id="editEmail" class="form-control" required>
                        </div>

                        <div id="editLibrarianFields" class="col-12 d-none">
                            <div class="p-3 bg-light rounded-3 border row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Employee Code</label>
                                    <input type="text" name="employee_code" id="editEmpCode" class="form-control font-monospace">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Phone Number</label>
                                    <input type="text" name="phone" id="editPhone" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-maroon font-serif px-4" style="background-color: #8B1E26;">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL: RESET PASSWORD                                         -->
<!-- ============================================================ -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <div class="modal-header text-white bg-dark">
                <h5 class="modal-title font-serif" id="passwordModalLabel">
                    <i class="fas fa-key me-2 text-warning"></i> Reset Account Password
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="passUserId">

                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Set a new password for <strong class="text-dark" id="passUserName"></strong>. They will use this new password on their next login.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="new_password" id="resetPassInput" class="form-control" placeholder="Min 6 characters" minlength="6" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassVisibility('resetPassInput', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Confirm New Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" id="resetConfInput" class="form-control" placeholder="Repeat new password" minlength="6" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassVisibility('resetConfInput', this)">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-gold font-serif fw-bold text-dark px-4">
                        <i class="fas fa-shield-alt me-1"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleLibrarianFields(role) {
    const extra = document.getElementById('librarianExtraFields');
    if (role === 'LIBRARIAN') {
        extra.classList.remove('d-none');
    } else {
        extra.classList.add('d-none');
    }
}

function openEditModal(data) {
    document.getElementById('editUserId').value = data.id;
    document.getElementById('editFullName').value = data.full_name;
    document.getElementById('editEmail').value = data.email;
    document.getElementById('editStatus').value = data.status;
    document.getElementById('editRoleName').value = data.role_name;

    const libFields = document.getElementById('editLibrarianFields');
    if (data.role_code === 'LIBRARIAN') {
        libFields.classList.remove('d-none');
        document.getElementById('editEmpCode').value = data.employee_code || '';
        document.getElementById('editPhone').value = data.phone || '';
    } else {
        libFields.classList.add('d-none');
    }

    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}

function openPasswordModal(userId, userName) {
    document.getElementById('passUserId').value = userId;
    document.getElementById('passUserName').textContent = userName;
    const modal = new bootstrap.Modal(document.getElementById('passwordModal'));
    modal.show();
}

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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
