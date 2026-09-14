<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

require_role(['LIBRARIAN', 'SUPER_ADMIN']);

$db = getDB();
$errors = [];
$filterMemberId = (int)($_GET['member_id'] ?? 0);

// Handle Cash Fine Payment Submission (Requirement 35)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_fine'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token mismatch.";
    } else {
        $fineId = (int)$_POST['fine_id'];
        $amountPaid = (float)$_POST['amount_paid'];

        $fStmt = $db->prepare("SELECT * FROM fines WHERE id = ?");
        $fStmt->execute([$fineId]);
        $fine = $fStmt->fetch();

        if (!$fine) {
            $errors[] = "Fine record not found.";
        }

        if (empty($errors)) {
            try {
                $db->beginTransaction();

                $newPaid = $fine['paid_amount'] + $amountPaid;
                $newStatus = ($newPaid >= $fine['fine_amount']) ? 'Paid' : 'Partially Paid';

                // 1. Update fine status
                $db->prepare("UPDATE fines SET paid_amount = ?, status = ? WHERE id = ?")->execute([$newPaid, $newStatus, $fineId]);

                // 2. Insert fine_payments record
                $receiptCode = generate_receipt_code($db);
                $insPay = $db->prepare("
                    INSERT INTO fine_payments (receipt_code, fine_id, member_id, amount_paid, payment_method, payment_date, collected_by)
                    VALUES (?, ?, ?, ?, 'CASH', NOW(), ?)
                ");
                $insPay->execute([$receiptCode, $fineId, $fine['member_id'], $amountPaid, $_SESSION['user_id']]);
                $paymentId = $db->lastInsertId();

                log_audit_action($_SESSION['user_id'], $_SESSION['role_code'], 'Collect Cash Fine', 'FinePayments', "Receipt: {$receiptCode}, Amount: {$amountPaid}");

                $db->commit();

                set_flash_message('success', "Cash fine payment of " . format_currency($amountPaid) . " recorded. Receipt Code: {$receiptCode}");
                header("Location: " . BASE_URL . "librarian/receipt.php?type=fine&id=" . $paymentId);
                exit();

            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = "Error recording fine payment: " . $e->getMessage();
            }
        }
    }
}

// Build query
$whereSql = "";
$params = [];
if ($filterMemberId > 0) {
    $whereSql = "WHERE f.member_id = ?";
    $params[] = $filterMemberId;
}

$stmt = $db->prepare("
    SELECT f.*, b.name AS book_name, bc.barcode,
           m.member_code, u.full_name AS member_name, u.email
    FROM fines f
    JOIN books b ON f.book_id = b.id
    JOIN book_copies bc ON f.copy_id = bc.id
    JOIN members m ON f.member_id = m.id
    JOIN users u ON m.user_id = u.id
    {$whereSql}
    ORDER BY f.id DESC
");
$stmt->execute($params);
$fines = $stmt->fetchAll();

$pageTitle = "Fine Management & Cash Collection";
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
                    <h2 class="section-title mb-1">Cash Fine Management</h2>
                    <p class="text-muted small mb-0">Record cash fine payments, view overdue charges, and generate printable receipts.</p>
                </div>
            </div>

            <div class="card sayak-card">
                <div class="card-body p-0">
                    <?php if (!empty($fines)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Member</th>
                                        <th>Book & Copy</th>
                                        <th>Late Days</th>
                                        <th>Fine Amount</th>
                                        <th>Amount Paid</th>
                                        <th>Balance Due</th>
                                        <th>Status</th>
                                        <th>Collect Cash Fine</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fines as $fn): 
                                        $balance = $fn['fine_amount'] - $fn['paid_amount'];
                                    ?>
                                        <tr>
                                            <td>
                                                <strong class="d-block font-serif text-dark"><?= escape($fn['member_name']) ?></strong>
                                                <small class="text-maroon fw-bold" style="color: #7A0C0C;"><?= escape($fn['member_code']) ?></small>
                                            </td>
                                            <td>
                                                <strong class="d-block text-dark"><?= escape($fn['book_name']) ?></strong>
                                                <code><?= escape($fn['barcode']) ?></code>
                                            </td>
                                            <td><span class="badge bg-secondary"><?= $fn['late_days'] ?> Days</span></td>
                                            <td><strong class="text-dark"><?= format_currency($fn['fine_amount']) ?></strong></td>
                                            <td><span class="text-success fw-bold"><?= format_currency($fn['paid_amount']) ?></span></td>
                                            <td><strong class="text-danger"><?= format_currency($balance) ?></strong></td>
                                            <td>
                                                <?php if ($fn['status'] === 'Paid'): ?>
                                                    <span class="badge bg-success">Paid</span>
                                                <?php elseif ($fn['status'] === 'Partially Paid'): ?>
                                                    <span class="badge bg-warning text-dark">Partially Paid</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Unpaid</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($balance > 0): ?>
                                                    <form action="" method="POST" class="d-flex gap-1">
                                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                        <input type="hidden" name="fine_id" value="<?= $fn['id'] ?>">
                                                        <input type="number" name="amount_paid" class="form-control form-control-sm" style="width: 90px;" value="<?= $balance ?>" step="1" max="<?= $balance ?>" required>
                                                        <button type="submit" name="pay_fine" class="btn btn-sm btn-success text-nowrap">
                                                            <i class="fas fa-rupee-sign me-1"></i> Collect
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted small"><i class="fas fa-check text-success"></i> Fully Paid</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-5 text-center text-muted">
                            <i class="fas fa-receipt fa-3x mb-3 text-secondary"></i>
                            <p>No fine records found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
