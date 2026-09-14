<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

require_role(['LIBRARIAN', 'SUPER_ADMIN']);

$db = getDB();

// Today's Date or Filtered Date Range
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// 1. Membership Cash Collections
$mCollStmt = $db->prepare("
    SELECT mp.*, mpl.plan_name, u.full_name AS member_name, col.full_name AS collector_name
    FROM membership_payments mp
    JOIN membership_plans mpl ON mp.plan_id = mpl.id
    JOIN members m ON mp.member_id = m.id
    JOIN users u ON m.user_id = u.id
    JOIN users col ON mp.collected_by = col.id
    WHERE DATE(mp.payment_date) BETWEEN ? AND ?
    ORDER BY mp.id DESC
");
$mCollStmt->execute([$startDate, $endDate]);
$membershipCollections = $mCollStmt->fetchAll();
$membershipPayments = $membershipCollections;

// 2. Fine Cash Collections
$fCollStmt = $db->prepare("
    SELECT fp.*, b.name AS book_name, u.full_name AS member_name, col.full_name AS collector_name
    FROM fine_payments fp
    JOIN fines f ON fp.fine_id = f.id
    JOIN books b ON f.book_id = b.id
    JOIN members m ON fp.member_id = m.id
    JOIN users u ON m.user_id = u.id
    JOIN users col ON fp.collected_by = col.id
    WHERE DATE(fp.payment_date) BETWEEN ? AND ?
    ORDER BY fp.id DESC
");
$fCollStmt->execute([$startDate, $endDate]);
$fineCollections = $fCollStmt->fetchAll();
$finePayments = $fineCollections;

// 3. Outstanding Fines Summary
$outFinesStmt = $db->query("
    SELECT f.*, b.name AS book_name, u.full_name AS member_name, u.email
    FROM fines f
    JOIN books b ON f.book_id = b.id
    JOIN members m ON f.member_id = m.id
    JOIN users u ON m.user_id = u.id
    WHERE f.status != 'Paid'
    ORDER BY f.fine_amount DESC
");
$outstandingFines = $outFinesStmt->fetchAll();

// Totals
$totMemAmount = array_sum(array_column($membershipPayments, 'amount'));
$totFineAmount = array_sum(array_column($finePayments, 'amount_paid'));
$grandTotalCash = $totMemAmount + $totFineAmount;

$pageTitle = "Operational Reports";
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
                    <h2 class="section-title mb-1">Daily Cash & Lending Reports</h2>
                    <p class="text-muted small mb-0">Financial cash audit and operational summary for selected date range.</p>
                </div>
                <button onclick="window.print();" class="btn btn-outline-dark btn-sm">
                    <i class="fas fa-print me-1"></i> Print Report
                </button>
            </div>

    <!-- Date Range Filter -->
    <div class="card sayak-card mb-4">
        <div class="card-body">
            <form action="" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">From Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?= escape($startDate) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">To Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?= escape($endDate) ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-maroon w-100 font-serif" style="background-color: #7A0C0C;">Generate Report</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Grand Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="metric-card border-start border-4 border-success">
                <div class="metric-value text-success"><?= format_currency($grandTotalCash) ?></div>
                <div class="metric-label"><i class="fas fa-cash-register me-1"></i> Total Cash Collection</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric-card border-start border-4 border-primary">
                <div class="metric-value text-primary"><?= format_currency($totMemAmount) ?></div>
                <div class="metric-label"><i class="fas fa-id-card me-1"></i> Membership Revenue</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="metric-card border-start border-4 border-warning">
                <div class="metric-value text-warning"><?= format_currency($totFineAmount) ?></div>
                <div class="metric-label"><i class="fas fa-receipt me-1"></i> Late Fine Revenue</div>
            </div>
        </div>
    </div>

    <!-- Membership Cash Payments Table -->
    <div class="card sayak-card mb-4">
        <div class="card-header bg-white font-serif fw-bold py-3">
            <i class="fas fa-id-card text-maroon me-2" style="color: #7A0C0C;"></i> Membership Cash Collections
        </div>
        <div class="card-body p-0">
            <?php if (!empty($membershipPayments)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Txn Code</th>
                                <th>Member Name</th>
                                <th>Subscription Plan</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Payment Date</th>
                                <th>Collected By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($membershipPayments as $m): ?>
                                <tr>
                                    <td><code><?= escape($m['transaction_code']) ?></code></td>
                                    <td><strong><?= escape($m['member_name']) ?></strong></td>
                                    <td><?= escape($m['plan_name']) ?></td>
                                    <td><strong class="text-success"><?= format_currency($m['amount']) ?></strong></td>
                                    <td><span class="badge bg-light text-dark border"><?= escape($m['payment_method']) ?></span></td>
                                    <td><?= format_date($m['payment_date'], 'd M Y, h:i A') ?></td>
                                    <td><small><?= escape($m['collector_name']) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-4 text-center text-muted">No membership collections recorded for this range.</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Fine Cash Payments Table -->
    <div class="card sayak-card">
        <div class="card-header bg-white font-serif fw-bold py-3">
            <i class="fas fa-receipt text-maroon me-2" style="color: #7A0C0C;"></i> Late Fine Cash Collections
        </div>
        <div class="card-body p-0">
            <?php if (!empty($finePayments)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Receipt Code</th>
                                <th>Member Name</th>
                                <th>Fine Paid</th>
                                <th>Method</th>
                                <th>Payment Date</th>
                                <th>Collected By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($finePayments as $f): ?>
                                <tr>
                                    <td><code><?= escape($f['receipt_code']) ?></code></td>
                                    <td><strong><?= escape($f['member_name']) ?></strong></td>
                                    <td><strong class="text-success"><?= format_currency($f['amount_paid']) ?></strong></td>
                                    <td><span class="badge bg-light text-dark border"><?= escape($f['payment_method']) ?></span></td>
                                    <td><?= format_date($f['payment_date'], 'd M Y, h:i A') ?></td>
                                    <td><small><?= escape($f['collector_name']) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-4 text-center text-muted">No fine collections recorded for this range.</div>
            <?php endif; ?>
        </div>
    </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
