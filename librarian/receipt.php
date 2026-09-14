<?php
require_once __DIR__ . '/../includes/permissions.php';

require_role(['LIBRARIAN', 'SUPER_ADMIN']);

$type = $_GET['type'] ?? 'membership';
$id = (int)($_GET['id'] ?? 0);
$db = getDB();

$receiptData = null;

if ($type === 'fine') {
    $stmt = $db->prepare("
        SELECT fp.*, f.fine_amount, b.name AS book_name,
               m.member_code, u.full_name AS member_name, u.email,
               col.full_name AS collector_name
        FROM fine_payments fp
        JOIN fines f ON fp.fine_id = f.id
        JOIN books b ON f.book_id = b.id
        JOIN members m ON fp.member_id = m.id
        JOIN users u ON m.user_id = u.id
        JOIN users col ON fp.collected_by = col.id
        WHERE fp.id = ? LIMIT 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $receiptData = [
            'receipt_no' => $row['receipt_code'],
            'member_name' => $row['member_name'],
            'member_code' => $row['member_code'],
            'payment_type' => 'Overdue Book Fine Collection (' . $row['book_name'] . ')',
            'amount' => $row['amount_paid'],
            'payment_method' => $row['payment_method'],
            'date' => $row['payment_date'],
            'collected_by' => $row['collector_name']
        ];
    }
} else {
    // Membership receipt
    $stmt = $db->prepare("
        SELECT mp.*, mpl.plan_name,
               m.member_code, u.full_name AS member_name, u.email,
               col.full_name AS collector_name
        FROM membership_payments mp
        JOIN membership_plans mpl ON mp.plan_id = mpl.id
        JOIN members m ON mp.member_id = m.id
        JOIN users u ON m.user_id = u.id
        JOIN users col ON mp.collected_by = col.id
        WHERE mp.id = ? LIMIT 1
    ");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $receiptData = [
            'receipt_no' => $row['transaction_code'],
            'member_name' => $row['member_name'],
            'member_code' => $row['member_code'],
            'payment_type' => 'Membership Subscription Plan: ' . $row['plan_name'] . ' (' . format_date($row['start_date']) . ' to ' . format_date($row['expiry_date']) . ')',
            'amount' => $row['amount'],
            'payment_method' => $row['payment_method'],
            'date' => $row['payment_date'],
            'collected_by' => $row['collector_name']
        ];
    }
}

$pageTitle = "Print Cash Receipt";
require_once __DIR__ . '/../includes/header.php';

if (!$receiptData) {
    echo '<div class="container py-5"><div class="alert alert-danger text-center">Receipt record not found.</div></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}
?>

<div class="container py-4">
    <div class="no-print d-flex justify-content-between align-items-center mb-4">
        <a href="<?= BASE_URL ?>librarian/dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
        <button onclick="window.print();" class="btn btn-maroon btn-lg font-serif" style="background-color: #7A0C0C;">
            <i class="fas fa-print me-2"></i> Print Official Receipt
        </button>
    </div>

    <!-- Printable Receipt Card -->
    <div class="card sayak-card p-4 p-md-5 border-2 shadow printable-receipt mx-auto" style="max-width: 750px;">
        <!-- Header -->
        <div class="text-center pb-3 mb-4 border-bottom border-3 border-dark">
            <h2 class="font-serif fw-bold text-maroon mb-1" style="color: #7A0C0C; letter-spacing: 1px;">
                <?= escape(get_setting('library_name', 'SAYAK LIBRARY')) ?>
            </h2>
            <div class="small text-secondary fw-bold">
                Estd: <?= escape(get_setting('established_year', '1995')) ?> | Reg No: <?= escape(get_setting('registration_no', 'SL/WB/2023/8892')) ?>
            </div>
            <div class="small text-muted"><?= escape(get_setting('address', 'College Street, Kolkata')) ?> | Phone: <?= escape(get_setting('phone', '+91 33 2241 8900')) ?></div>
            <div class="mt-2">
                <span class="badge bg-dark fs-6 px-3 py-2">OFFICIAL CASH PAYMENT RECEIPT</span>
            </div>
        </div>

        <!-- Receipt Details Table -->
        <div class="row g-3 mb-4 fs-6">
            <div class="col-6">
                <small class="text-muted d-block">Receipt Number:</small>
                <strong class="font-serif text-dark fs-5"><code><?= escape($receiptData['receipt_no']) ?></code></strong>
            </div>
            <div class="col-6 text-end">
                <small class="text-muted d-block">Transaction Date & Time:</small>
                <strong><?= format_date($receiptData['date'], 'd M Y, h:i A') ?></strong>
            </div>

            <div class="col-6">
                <small class="text-muted d-block">Received From (Member Name):</small>
                <strong class="fs-5 text-dark"><?= escape($receiptData['member_name']) ?></strong>
            </div>
            <div class="col-6 text-end">
                <small class="text-muted d-block">Member ID Code:</small>
                <strong class="fs-5 text-maroon" style="color: #7A0C0C;"><?= escape($receiptData['member_code']) ?></strong>
            </div>

            <div class="col-12">
                <div class="p-3 bg-light rounded border">
                    <small class="text-muted d-block">Payment Description / Purpose:</small>
                    <strong><?= escape($receiptData['payment_type']) ?></strong>
                </div>
            </div>

            <div class="col-6">
                <small class="text-muted d-block">Payment Method:</small>
                <span class="badge bg-success fs-6"><?= escape($receiptData['payment_method']) ?></span>
            </div>

            <div class="col-6 text-end">
                <small class="text-muted d-block">Total Amount Received:</small>
                <span class="display-6 fw-bold text-success" style="font-size: 2rem;"><?= format_currency($receiptData['amount']) ?></span>
            </div>
        </div>

        <!-- Signature Footer -->
        <div class="row align-items-end mt-5 pt-4 border-top">
            <div class="col-6">
                <small class="text-muted d-block">Issued By:</small>
                <strong><?= escape($receiptData['collected_by']) ?></strong>
                <div class="small text-muted">Library Administrative Staff</div>
            </div>
            <div class="col-6 text-end">
                <div class="d-inline-block text-center" style="width: 180px;">
                    <div style="height: 40px;" class="border-bottom border-secondary mb-1"></div>
                    <small class="fw-bold text-dark d-block">Authorized Seal & Sign</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
