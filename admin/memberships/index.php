<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

require_role('SUPER_ADMIN');

$db = getDB();
$errors = [];

// Handle Save Plan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_plan'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token mismatch.";
    } else {
        $planId = (int)($_POST['plan_id'] ?? 0);
        $planName = sanitize_input($_POST['plan_name'] ?? '');
        $durationMonths = (int)$_POST['duration_months'];
        $price = (float)$_POST['price'];
        $status = sanitize_input($_POST['status'] ?? 'Active');

        if (empty($planName)) $errors[] = "Plan Name is required.";
        if ($durationMonths <= 0) $errors[] = "Duration months must be at least 1.";

        if (empty($errors)) {
            if ($planId > 0) {
                $db->prepare("UPDATE membership_plans SET plan_name = ?, duration_months = ?, price = ?, status = ? WHERE id = ?")->execute([$planName, $durationMonths, $price, $status, $planId]);
                set_flash_message('success', 'Membership plan updated.');
            } else {
                $db->prepare("INSERT INTO membership_plans (plan_name, duration_months, price, status) VALUES (?, ?, ?, ?)")->execute([$planName, $durationMonths, $price, $status]);
                set_flash_message('success', 'New membership plan created.');
            }
            header("Location: " . BASE_URL . "admin/memberships/index.php");
            exit();
        }
    }
}

$plans = $db->query("SELECT * FROM membership_plans ORDER BY id ASC")->fetchAll();

$pageTitle = "Membership Plans & Pricing";
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
                    <h2 class="section-title mb-1">Membership Plans & Pricing</h2>
                    <p class="text-muted small mb-0">Configure paid subscription plans, duration months, and cash rates.</p>
                </div>
                <button type="button" class="btn btn-maroon btn-sm font-serif" data-bs-toggle="modal" data-bs-target="#planModal" onclick="resetPlanForm();" style="background-color: #7A0C0C;">
                    <i class="fas fa-plus me-1"></i> Add Subscription Plan
                </button>
            </div>

            <?php if (get_setting('membership_mode', 'online') === 'pdf'): ?>
                <div class="alert alert-warning border-start border-4 border-warning shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                    <div>
                        <div class="fw-bold text-dark mb-1">
                            <i class="fas fa-file-pdf text-danger me-2 fs-5"></i> Public Signup is currently in "Offline PDF Form Mode"
                        </div>
                        <div class="small text-muted">
                            Online member registration is currently disabled for the public. Visitors are directed to download and submit your printable PDF application form.
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>admin/settings/index.php#membership-settings" class="btn btn-warning btn-sm fw-bold text-dark text-nowrap">
                        <i class="fas fa-sliders-h me-1"></i> Membership Mode Settings
                    </a>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <?php foreach ($plans as $p): ?>
                    <div class="col-md-4">
                        <div class="card sayak-card text-center p-4 border-top border-4 border-maroon">
                            <span class="badge bg-light text-dark align-self-center mb-2 border"><?= escape($p['status']) ?></span>
                            <h4 class="font-serif fw-bold"><?= escape($p['plan_name']) ?></h4>
                            <div class="display-6 fw-bold text-maroon my-2" style="color: #7A0C0C;"><?= format_currency($p['price']) ?></div>
                            <p class="text-muted small">Duration: <?= $p['duration_months'] ?> Month(s)</p>
                            <button type="button" class="btn btn-outline-maroon btn-sm mt-auto" onclick='editPlan(<?= json_encode($p) ?>)'>
                                <i class="fas fa-edit me-1"></i> Edit Plan
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Plan Form -->
<div class="modal fade" id="planModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white" style="background-color: #7A0C0C;">
                <h5 class="modal-title font-serif" id="planModalTitle">Add Membership Plan</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="plan_id" id="plan_id" value="0">

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Plan Name <span class="text-danger">*</span></label>
                        <input type="text" name="plan_name" id="plan_name" class="form-control" placeholder="e.g. 6 Months Scholar" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Duration (Months) <span class="text-danger">*</span></label>
                        <input type="number" name="duration_months" id="duration_months" class="form-control" min="1" max="60" value="6" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Price Amount (₹ INR) <span class="text-danger">*</span></label>
                        <input type="number" name="price" id="price" class="form-control" min="0" step="10" value="450" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Status</label>
                        <select name="status" id="plan_status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_plan" class="btn btn-maroon" style="background-color: #7A0C0C;">Save Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetPlanForm() {
    document.getElementById('plan_id').value = '0';
    document.getElementById('plan_name').value = '';
    document.getElementById('duration_months').value = '6';
    document.getElementById('price').value = '450';
    document.getElementById('plan_status').value = 'Active';
    document.getElementById('planModalTitle').innerText = 'Add Membership Plan';
}

function editPlan(p) {
    document.getElementById('plan_id').value = p.id;
    document.getElementById('plan_name').value = p.plan_name;
    document.getElementById('duration_months').value = p.duration_months;
    document.getElementById('price').value = p.price;
    document.getElementById('plan_status').value = p.status;
    document.getElementById('planModalTitle').innerText = 'Edit Membership Plan';
    var modal = new bootstrap.Modal(document.getElementById('planModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
