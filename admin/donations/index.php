<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

require_role('SUPER_ADMIN');

$db = getDB();

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $donId = (int)$_POST['donation_id'];
        $newStatus = sanitize_input($_POST['status']);
        $db->prepare("UPDATE donations SET status = ? WHERE id = ?")->execute([$newStatus, $donId]);
        log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Update Donation Status', 'Donations', "ID: {$donId}, Status: {$newStatus}");
        set_flash_message('success', 'Donation status updated.');
        header("Location: " . BASE_URL . "admin/donations/index.php");
        exit();
    }
}

// Fetch donations
$donations = $db->query("SELECT * FROM donations ORDER BY id DESC")->fetchAll();

$pageTitle = "Donation Submissions";
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
                    <h2 class="section-title mb-1">Donation Submissions & Pledges</h2>
                    <p class="text-muted small mb-0">Review monetary, book, and infrastructure donations submitted by public donors.</p>
                </div>
            </div>

            <div class="card sayak-card">
                <div class="card-body p-0">
                    <?php if (!empty($donations)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Donor Name & Contact</th>
                                        <th>Donation Type</th>
                                        <th>Amount / Pledged Value</th>
                                        <th>Message / Items</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Update Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($donations as $d): ?>
                                        <tr>
                                            <td>
                                                <strong class="font-serif text-dark d-block"><?= escape($d['donor_name']) ?></strong>
                                                <small class="text-muted"><i class="fas fa-envelope me-1"></i> <?= escape($d['email']) ?> | <i class="fas fa-phone me-1"></i> <?= escape($d['mobile']) ?></small>
                                            </td>
                                            <td>
                                                <?php if ($d['donation_type'] === 'Money'): ?>
                                                    <span class="badge bg-success">Financial (Money)</span>
                                                <?php elseif ($d['donation_type'] === 'Books'): ?>
                                                    <span class="badge bg-primary">Books</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark"><?= escape($d['donation_type']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><strong class="text-success"><?= format_currency($d['amount'] ?: 0) ?></strong></td>
                                            <td><small class="text-secondary"><?= escape($d['message'] ?: 'N/A') ?></small></td>
                                            <td><small><?= format_date($d['created_at']) ?></small></td>
                                            <td>
                                                <?php if ($d['status'] === 'New'): ?>
                                                    <span class="badge bg-warning text-dark">New</span>
                                                <?php elseif ($d['status'] === 'Accepted'): ?>
                                                    <span class="badge bg-success">Accepted</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= escape($d['status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form action="" method="POST" class="d-flex gap-1 align-items-center">
                                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                    <input type="hidden" name="donation_id" value="<?= $d['id'] ?>">
                                                    <select name="status" class="form-select form-select-sm" style="width: auto;">
                                                        <option value="New" <?= $d['status'] === 'New' ? 'selected' : '' ?>>New</option>
                                                        <option value="Accepted" <?= $d['status'] === 'Accepted' ? 'selected' : '' ?>>Accepted</option>
                                                        <option value="Completed" <?= $d['status'] === 'Completed' ? 'selected' : '' ?>>Completed</option>
                                                        <option value="Rejected" <?= $d['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                                    </select>
                                                    <button type="submit" name="update_status" class="btn btn-sm btn-outline-secondary">Save</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-5 text-center text-muted">No donation submissions received yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
