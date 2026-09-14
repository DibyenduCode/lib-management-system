<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

require_role(['LIBRARIAN', 'SUPER_ADMIN']);

$db = getDB();

// Search & Filter parameters
$searchQ = sanitize_input($_GET['q'] ?? '');

$sql = "
    SELECT bi.*, b.name AS book_name, b.book_code, bc.copy_code, bc.barcode, bc.shelf, bc.rack,
           m.id AS member_table_id, m.member_code, m.mobile, m.membership_status, m.restriction_date,
           u.full_name AS member_name, u.email,
           ms.expiry_date,
           DATEDIFF(CURDATE(), ms.expiry_date) AS expired_days,
           DATEDIFF(CURDATE(), bi.due_date) AS overdue_days,
           f.fine_amount, f.paid_amount, f.status AS fine_status
    FROM book_issues bi
    JOIN books b ON bi.book_id = b.id
    JOIN book_copies bc ON bi.copy_id = bc.id
    JOIN members m ON bi.member_id = m.id
    JOIN users u ON m.user_id = u.id
    LEFT JOIN memberships ms ON m.id = ms.member_id
    LEFT JOIN fines f ON bi.id = f.issue_id
    WHERE bi.status IN ('Issued', 'Overdue')
      AND (m.membership_status = 'Restricted' OR ms.expiry_date < DATE_SUB(CURDATE(), INTERVAL 15 DAY))
";

$params = [];
if (!empty($searchQ)) {
    $sql .= " AND (u.full_name LIKE ? OR m.member_code LIKE ? OR m.mobile LIKE ? OR b.name LIKE ? OR bc.barcode LIKE ?)";
    $term = "%{$searchQ}%";
    $params = [$term, $term, $term, $term, $term];
}

$sql .= " ORDER BY ms.expiry_date ASC, bi.due_date ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$alerts = $stmt->fetchAll();

// Calculate aggregate metrics
$totalAlerts = count($alerts);
$totalFinesDefault = 0.0;
$maxDaysExpired = 0;

foreach ($alerts as $a) {
    $outstanding = (float)($a['fine_amount'] ?? 0) - (float)($a['paid_amount'] ?? 0);
    if ($outstanding > 0) {
        $totalFinesDefault += $outstanding;
    }
    if ((int)$a['expired_days'] > $maxDaysExpired) {
        $maxDaysExpired = (int)$a['expired_days'];
    }
}

$pageTitle = "Critical Alerts - Expired Members Holding Books";
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
            <!-- Breadcrumbs / Top Banner -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="section-title mb-1 text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i> Critical Loan Alerts
                    </h2>
                    <p class="text-muted small mb-0">
                        Restricted and expired members (>15 days past membership expiry) who still retain physical library books.
                    </p>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>librarian/return/index.php" class="btn btn-gold btn-sm text-dark font-serif fw-bold">
                        <i class="fas fa-undo me-1"></i> Return Processing Desk
                    </a>
                </div>
            </div>

            <!-- Metric Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="metric-card py-3" style="border-left-color: #dc3545;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="metric-value text-danger"><?= $totalAlerts ?></div>
                                <div class="metric-label"><i class="fas fa-user-slash text-danger me-1"></i> Total Critical Loan Alerts</div>
                            </div>
                            <div class="p-3 bg-danger bg-opacity-10 rounded-circle text-danger">
                                <i class="fas fa-exclamation-circle fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="metric-card py-3" style="border-left-color: #fd7e14;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="metric-value text-warning"><?= format_currency($totalFinesDefault) ?></div>
                                <div class="metric-label"><i class="fas fa-rupee-sign text-warning me-1"></i> Overdue Fines in Default</div>
                            </div>
                            <div class="p-3 bg-warning bg-opacity-10 rounded-circle text-warning">
                                <i class="fas fa-coins fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="metric-card py-3" style="border-left-color: #7A0C0C;">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="metric-value" style="color: #7A0C0C;"><?= $maxDaysExpired ?> Days</div>
                                <div class="metric-label"><i class="fas fa-clock me-1"></i> Max Days Past Expiry</div>
                            </div>
                            <div class="p-3 bg-maroon bg-opacity-10 rounded-circle" style="color: #7A0C0C;">
                                <i class="fas fa-history fa-2x"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Filter Bar -->
            <div class="card sayak-card mb-4 border-0 shadow-sm">
                <div class="card-body p-3">
                    <form action="" method="GET" class="row g-2 align-items-center">
                        <div class="col-md-9 col-12">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" name="q" class="form-control" placeholder="Search by member name, member code, phone, book title, or barcode..." value="<?= escape($searchQ) ?>">
                            </div>
                        </div>
                        <div class="col-md-3 col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-maroon w-100" style="background-color: #7A0C0C;">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                            <?php if (!empty($searchQ)): ?>
                                <a href="<?= BASE_URL ?>librarian/alerts/index.php" class="btn btn-outline-secondary">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Alerts Table Card -->
            <div class="card sayak-card border-danger border-2 shadow-sm">
                <div class="card-header bg-danger text-white py-3 font-serif fw-bold d-flex justify-content-between align-items-center">
                    <span>
                        <i class="fas fa-user-slash me-2"></i> Members Expired >15 Days With Unreturned Books
                    </span>
                    <span class="badge bg-white text-danger px-3 py-2 fw-bold">
                        <?= count($alerts) ?> Case(s)
                    </span>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($alerts)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Member Details</th>
                                        <th>Membership Expiry</th>
                                        <th>Unreturned Book & Copy</th>
                                        <th>Due Date & Overdue</th>
                                        <th>Accrued Fine</th>
                                        <th>Desk Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 1; foreach ($alerts as $row): ?>
                                        <tr>
                                            <td class="text-muted fw-bold"><?= $i++ ?></td>
                                            <td>
                                                <strong class="font-serif text-dark d-block"><?= escape($row['member_name']) ?></strong>
                                                <span class="badge bg-maroon mb-1" style="background-color: #7A0C0C;"><?= escape($row['member_code']) ?></span>
                                                <span class="badge bg-secondary"><?= escape($row['membership_status']) ?></span>
                                                <div class="small text-muted mt-1">
                                                    <i class="fas fa-phone me-1"></i> <?= escape($row['mobile']) ?><br>
                                                    <i class="fas fa-envelope me-1"></i> <?= escape($row['email']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger fs-6 mb-1">
                                                    <?= (int)$row['expired_days'] ?> Days Expired
                                                </span>
                                                <div class="small text-muted">
                                                    Expired: <strong><?= format_date($row['expiry_date']) ?></strong>
                                                </div>
                                                <?php if (!empty($row['restriction_date'])): ?>
                                                    <div class="small text-danger">
                                                        Restricted on: <?= format_date($row['restriction_date']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong class="text-dark d-block font-serif"><?= escape($row['book_name']) ?></strong>
                                                <div class="small text-muted mb-1">Book Code: <code><?= escape($row['book_code']) ?></code></div>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="fas fa-barcode me-1"></i> <?= escape($row['barcode']) ?>
                                                </span>
                                                <small class="text-muted d-block mt-1">
                                                    <i class="fas fa-map-marker-alt me-1"></i> <?= escape($row['shelf'] ?? 'Rack-A') ?> / <?= escape($row['rack'] ?? 'Shelf-1') ?>
                                                </small>
                                            </td>
                                            <td>
                                                <strong class="text-danger d-block"><?= format_date($row['due_date']) ?></strong>
                                                <?php if ((int)$row['overdue_days'] > 0): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <?= (int)$row['overdue_days'] ?> Days Overdue
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-info text-dark">Within Due Date</span>
                                                <?php endif; ?>
                                                <div class="small text-muted mt-1">
                                                    Issued on: <?= format_date($row['issue_date']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <strong class="text-danger fs-6 d-block">
                                                    <?= format_currency($row['fine_amount'] ?: 0) ?>
                                                </strong>
                                                <span class="badge <?= ($row['fine_status'] === 'Paid' ? 'bg-success' : 'bg-warning text-dark') ?>">
                                                    <?= escape($row['fine_status'] ?: 'Unpaid') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    <a href="<?= BASE_URL ?>librarian/return/index.php?issue_id=<?= $row['id'] ?>" class="btn btn-sm btn-success text-nowrap" title="Receive Book Return">
                                                        <i class="fas fa-undo me-1"></i> Return Book
                                                    </a>
                                                    <a href="<?= BASE_URL ?>librarian/fines/index.php?member_id=<?= $row['member_table_id'] ?>" class="btn btn-sm btn-warning text-dark text-nowrap" title="Collect Late Fine">
                                                        <i class="fas fa-rupee-sign me-1"></i> Collect Fine
                                                    </a>
                                                    <a href="<?= BASE_URL ?>librarian/memberships/index.php?member_id=<?= $row['member_table_id'] ?>" class="btn btn-sm btn-maroon text-white text-nowrap" style="background-color: #7A0C0C;" title="Renew Membership">
                                                        <i class="fas fa-redo me-1"></i> Renew Plan
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-5 text-center">
                            <div class="text-success mb-3">
                                <i class="fas fa-check-circle fa-4x"></i>
                            </div>
                            <h4 class="font-serif fw-bold text-dark">All Clear - No Critical Alerts</h4>
                            <p class="text-muted mb-0">
                                There are currently no members with memberships expired over 15 days holding unreturned physical library books.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
