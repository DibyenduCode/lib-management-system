<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

require_role(['LIBRARIAN', 'SUPER_ADMIN']);

$db = getDB();

// Handle Suspend/Unsuspend
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $mId = (int)$_POST['member_id'];
        $newStatus = $_POST['new_status'];
        $db->prepare("UPDATE members SET membership_status = ? WHERE id = ?")->execute([$newStatus, $mId]);
        $db->prepare("UPDATE users u JOIN members m ON u.id = m.user_id SET u.status = ? WHERE m.id = ?")->execute([$newStatus, $mId]);
        set_flash_message('info', 'Member account status updated.');
        header("Location: " . BASE_URL . "librarian/members/index.php");
        exit();
    }
}

// Fetch members
$stmt = $db->query("
    SELECT m.*, u.full_name, u.email, u.status AS user_status,
           ms.expiry_date, ms.start_date, mp.plan_name
    FROM members m
    JOIN users u ON m.user_id = u.id
    LEFT JOIN memberships ms ON m.id = ms.member_id
    LEFT JOIN membership_plans mp ON ms.plan_id = mp.id
    GROUP BY m.id
    ORDER BY m.id DESC
");
$members = $stmt->fetchAll();

$pageTitle = "Manage Members";
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="section-title mb-1">Library Members Directory</h2>
                    <p class="text-muted small mb-0">View member profiles, active subscriptions, and administrative status.</p>
                </div>
                <a href="<?= BASE_URL ?>librarian/memberships/index.php" class="btn btn-maroon btn-sm">
                    <i class="fas fa-plus me-1"></i> Renew / Activate Member
                </a>
            </div>

            <div class="card sayak-card">
                <div class="card-body p-0">
                    <?php if (!empty($members)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Member Code</th>
                                        <th>Full Name & Email</th>
                                        <th>Mobile</th>
                                        <th>Current Plan</th>
                                        <th>Expiry Date</th>
                                        <th>Membership Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $m): ?>
                                        <tr>
                                            <td><strong class="text-maroon" style="color: #8B1E26;"><?= escape($m['member_code']) ?></strong></td>
                                            <td>
                                                <strong class="font-serif text-dark d-block"><?= escape($m['full_name']) ?></strong>
                                                <small class="text-muted"><?= escape($m['email']) ?></small>
                                            </td>
                                            <td><small><?= escape($m['mobile']) ?></small></td>
                                            <td><span class="badge bg-light text-dark border"><?= escape($m['plan_name'] ?: 'None') ?></span></td>
                                            <td><small><?= format_date($m['expiry_date']) ?></small></td>
                                            <td>
                                                <?php if ($m['membership_status'] === 'Active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php elseif ($m['membership_status'] === 'Restricted'): ?>
                                                    <span class="badge bg-danger"><i class="fas fa-lock me-1"></i> Restricted (15+ days)</span>
                                                <?php elseif ($m['membership_status'] === 'Expired'): ?>
                                                    <span class="badge bg-warning text-dark">Expired</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= escape($m['membership_status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="<?= BASE_URL ?>librarian/memberships/index.php?member_id=<?= $m['id'] ?>" class="btn btn-outline-maroon" title="Renew">
                                                        <i class="fas fa-redo me-1"></i> Renew
                                                    </a>
                                                    <form action="" method="POST" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                        <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
                                                        <?php if ($m['membership_status'] === 'Suspended'): ?>
                                                            <input type="hidden" name="new_status" value="Active">
                                                            <button type="submit" name="toggle_status" class="btn btn-outline-success">Unsuspend</button>
                                                        <?php else: ?>
                                                            <input type="hidden" name="new_status" value="Suspended">
                                                            <button type="submit" name="toggle_status" class="btn btn-outline-danger" onclick="return confirm('Suspend this member?');">Suspend</button>
                                                        <?php endif; ?>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-5 text-center text-muted">No members registered.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
