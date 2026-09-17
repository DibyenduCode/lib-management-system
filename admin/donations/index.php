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

// Save Bank & UPI Details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment_details'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $fields = ['donate_bank_name', 'donate_account_no', 'donate_ifsc', 'donate_upi_id', 'donate_appeal_title', 'donate_appeal_desc'];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $val = trim($_POST[$f]);
                $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->execute([$f, $val]);
            }
        }
        log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Update Payment Details', 'Settings', "Updated official bank & UPI details.");
        set_flash_message('success', 'Official Bank & UPI transfer details updated successfully! Live website reflects changes.');
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
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="section-title mb-1">Donation Submissions & Pledges</h2>
                    <p class="text-muted small mb-0">Review monetary, book, and infrastructure donations submitted by public donors.</p>
                </div>
                <div>
                    <button type="button" class="btn btn-maroon font-serif fw-bold btn-sm shadow-sm" data-bs-toggle="modal" data-bs-target="#editPaymentDetailsModal" style="background-color: #7A0C0C;">
                        <i class="fas fa-university me-1"></i> Edit Bank & UPI Payment Details
                    </button>
                </div>
            </div>

            <!-- Official Bank & UPI Details Overview Card -->
            <div class="card sayak-card mb-4 border-start border-4 border-maroon">
                <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h6 class="font-serif fw-bold text-maroon mb-1" style="color: #7A0C0C;">
                            <i class="fas fa-university me-2"></i> Official Direct Bank & UPI Transfer Details (Live on Public Donate Page)
                        </h6>
                        <div class="d-flex flex-wrap gap-3 small text-muted">
                            <span><strong>Bank:</strong> <?= escape(get_setting('donate_bank_name', 'State Bank of India (College Street Branch)')) ?></span>
                            <span><strong>A/C No:</strong> <code class="text-dark font-monospace"><?= escape(get_setting('donate_account_no', '38491029384')) ?></code></span>
                            <span><strong>IFSC:</strong> <code class="text-dark font-monospace"><?= escape(get_setting('donate_ifsc', 'SBIN0001234')) ?></code></span>
                            <span><strong>UPI ID:</strong> <span class="badge bg-success-subtle text-success border font-monospace"><?= escape(get_setting('donate_upi_id', 'sayaklibrary@sbi')) ?></span></span>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-maroon btn-sm font-serif fw-bold" data-bs-toggle="modal" data-bs-target="#editPaymentDetailsModal">
                            <i class="fas fa-edit me-1"></i> Edit Details
                        </button>
                    </div>
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

<!-- ============================================================ -->
<!-- MODAL: EDIT DIRECT BANK & UPI PAYMENT DETAILS                -->
<!-- ============================================================ -->
<div class="modal fade" id="editPaymentDetailsModal" tabindex="-1" aria-labelledby="editPaymentModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white" style="background-color: #7A0C0C;">
                <h5 class="modal-title font-serif" id="editPaymentModalTitle">
                    <i class="fas fa-university me-2"></i> Edit Official Bank & UPI Transfer Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                    <div class="alert alert-info py-2 px-3 small mb-3">
                        <i class="fas fa-info-circle me-1"></i> These details are displayed on the public <strong>Donate Page</strong> and <strong>Homepage Support Card</strong> for donor bank transfers and QR/UPI payments.
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Bank Name & Branch <span class="text-danger">*</span></label>
                            <input type="text" name="donate_bank_name" class="form-control" value="<?= escape(get_setting('donate_bank_name', 'State Bank of India (College Street Branch)')) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Bank Account Number <span class="text-danger">*</span></label>
                            <input type="text" name="donate_account_no" class="form-control font-monospace" value="<?= escape(get_setting('donate_account_no', '38491029384')) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">IFSC Code <span class="text-danger">*</span></label>
                            <input type="text" name="donate_ifsc" class="form-control font-monospace text-uppercase" value="<?= escape(get_setting('donate_ifsc', 'SBIN0001234')) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Official UPI ID (e.g. sayaklibrary@sbi) <span class="text-danger">*</span></label>
                            <input type="text" name="donate_upi_id" class="form-control font-monospace" value="<?= escape(get_setting('donate_upi_id', 'sayaklibrary@sbi')) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold small">Donation Appeal Headline</label>
                            <input type="text" name="donate_appeal_title" class="form-control" value="<?= escape(get_setting('donate_appeal_title', 'Donate to Sayak Library')) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold small">Donation Appeal Description</label>
                            <textarea name="donate_appeal_desc" class="form-control" rows="2"><?= escape(get_setting('donate_appeal_desc', 'Your contributions directly support book restoration, student scholarships, rare manuscript preservation, and e-learning resources.')) ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_payment_details" class="btn btn-maroon font-serif fw-bold" style="background-color: #7A0C0C;">
                        <i class="fas fa-save me-1"></i> Save Payment Details
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
