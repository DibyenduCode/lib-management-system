<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';

require_role('MEMBER');

$db = getDB();
$memberId = $_SESSION['member_id'];

// Fetch current active membership
$stmt = $db->prepare("
    SELECT ms.*, mp.plan_name, mp.price, mp.duration_months 
    FROM memberships ms 
    JOIN membership_plans mp ON ms.plan_id = mp.id 
    WHERE ms.member_id = ? 
    ORDER BY ms.id DESC LIMIT 1
");
$stmt->execute([$memberId]);
$currentPlan = $stmt->fetch();

// Fetch Cash Payment & Renewal History (Requirement 40 & 55)
$pStmt = $db->prepare("
    SELECT mp.*, mpl.plan_name, u.full_name AS collector_name 
    FROM membership_payments mp 
    JOIN membership_plans mpl ON mp.plan_id = mpl.id 
    JOIN users u ON mp.collected_by = u.id 
    WHERE mp.member_id = ? 
    ORDER BY mp.id DESC
");
$pStmt->execute([$memberId]);
$payments = $pStmt->fetchAll();

$pageTitle = "My Membership & Payments";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row g-4">
        <!-- Sidebar Navigation Column -->
        <div class="col-lg-3 col-xl-2">
            <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
        </div>

        <!-- Main Content Area Column -->
        <div class="col-lg-9 col-xl-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="section-title mb-1">My Membership & Cash Receipts</h2>
                    <p class="text-muted small mb-0">Overview of your subscription plan and payment records.</p>
                </div>
            </div>

            <!-- Active Subscription Overview -->
            <div class="card sayak-card mb-4 border-top border-4 border-maroon">
                <div class="card-body p-4">
                    <h5 class="font-serif fw-bold text-maroon mb-3" style="color: #7A0C0C;"><i class="fas fa-id-card me-2"></i> Current Subscription Status</h5>
                    
                    <?php if ($currentPlan): ?>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <small class="text-muted d-block">Active Plan:</small>
                                <strong class="fs-5 text-dark"><?= escape($currentPlan['plan_name']) ?></strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Start Date:</small>
                                <strong><?= format_date($currentPlan['start_date']) ?></strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Expiry Date:</small>
                                <strong class="text-maroon fs-6" style="color: #7A0C0C;"><?= format_date($currentPlan['expiry_date']) ?></strong>
                            </div>
                            <div class="col-md-3">
                                <small class="text-muted d-block">Status:</small>
                                <span class="badge bg-success py-2 px-3"><i class="fas fa-check-circle me-1"></i> <?= escape($currentPlan['status']) ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i> No active membership subscription found. Please visit the administrative counter to subscribe.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment History -->
            <div class="card sayak-card">
                <div class="card-header bg-white font-serif py-3 fw-bold fs-5">
                    <i class="fas fa-receipt text-maroon me-2" style="color: #7A0C0C;"></i> Membership Cash Payment History
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($payments)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Receipt No</th>
                                        <th>Plan Subscribed</th>
                                        <th>Amount Paid</th>
                                        <th>Payment Date</th>
                                        <th>Payment Method</th>
                                        <th>Collected By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $pay): ?>
                                        <tr>
                                            <td><code><?= escape($pay['transaction_code'] ?? $pay['receipt_no'] ?? 'N/A') ?></code></td>
                                            <td><strong><?= escape($pay['plan_name']) ?></strong></td>
                                            <td><strong class="text-success"><?= format_currency($pay['amount']) ?></strong></td>
                                            <td><small><?= format_date($pay['payment_date']) ?></small></td>
                                            <td><span class="badge bg-light text-dark border"><?= escape($pay['payment_method']) ?></span></td>
                                            <td><small class="text-muted"><?= escape($pay['collector_name']) ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-receipt fa-3x mb-3 text-secondary"></i>
                            <p>No cash payment receipts recorded yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
