<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';

require_unrestricted_member();

$db = getDB();
$memberId = $_SESSION['member_id'];

// Fetch issued books for this member
$stmt = $db->prepare("
    SELECT bi.*, b.name AS book_name, b.book_code, bc.barcode, bc.shelf, bc.rack,
           f.fine_amount, f.status AS fine_status
    FROM book_issues bi
    JOIN books b ON bi.book_id = b.id
    JOIN book_copies bc ON bi.copy_id = bc.id
    LEFT JOIN fines f ON bi.id = f.issue_id
    WHERE bi.member_id = ?
    ORDER BY bi.id DESC
");
$stmt->execute([$memberId]);
$issues = $stmt->fetchAll();

$pageTitle = "My Issued Books";
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
                    <h2 class="section-title mb-1">My Issued Books</h2>
                    <p class="text-muted small mb-0">Track your physical book loans, return due dates, and fine statuses.</p>
                </div>
            </div>

            <div class="card sayak-card">
                <div class="card-body p-0">
                    <?php if (!empty($issues)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Book & Code</th>
                                        <th>Copy Barcode</th>
                                        <th>Issue Date</th>
                                        <th>Due Date</th>
                                        <th>Loan Status</th>
                                        <th>Fine</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($issues as $row): 
                                        $dueDate = new DateTime($row['due_date']);
                                        $today = new DateTime('today');
                                        $isOverdue = ($row['status'] === 'Issued' && $today > $dueDate);
                                        $daysDiff = (int)$today->diff($dueDate)->format("%r%a");
                                    ?>
                                        <tr>
                                            <td>
                                                <strong class="font-serif text-dark d-block"><?= escape($row['book_name']) ?></strong>
                                                <span class="badge bg-light text-dark border"><?= escape($row['book_code']) ?></span>
                                            </td>
                                            <td>
                                                <code><?= escape($row['barcode']) ?></code>
                                                <small class="text-muted d-block"><?= escape($row['shelf']) ?> / <?= escape($row['rack']) ?></small>
                                            </td>
                                            <td><?= format_date($row['issue_date']) ?></td>
                                            <td>
                                                <strong class="<?= $isOverdue ? 'text-danger' : 'text-dark' ?>"><?= format_date($row['due_date']) ?></strong>
                                                <?php if ($row['status'] === 'Issued'): ?>
                                                    <small class="d-block <?= $isOverdue ? 'text-danger' : 'text-muted' ?>">
                                                        <?= $isOverdue ? abs($daysDiff) . ' days overdue' : $daysDiff . ' days remaining' ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($row['status'] === 'Returned'): ?>
                                                    <span class="badge bg-success">Returned on <?= format_date($row['return_date']) ?></span>
                                                <?php elseif ($isOverdue): ?>
                                                    <span class="badge bg-danger">Overdue</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">Issued</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['fine_amount']) && $row['fine_amount'] > 0): ?>
                                                    <strong class="text-danger"><?= format_currency($row['fine_amount']) ?></strong>
                                                    <span class="badge bg-warning text-dark d-block"><?= escape($row['fine_status']) ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">₹0.00</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-book-open fa-3x mb-3 text-secondary"></i>
                            <p>No physical books currently issued to your account.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
