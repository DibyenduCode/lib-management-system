<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

require_role(['LIBRARIAN', 'SUPER_ADMIN']);

$db = getDB();
$errors = [];

$preIssueId = (int)($_GET['issue_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token validation failed.";
    } else {
        $issueId = (int)$_POST['issue_id'];
        $returnDate = $_POST['return_date'] ?? date('Y-m-d');

        $stmt = $db->prepare("SELECT * FROM book_issues WHERE id = ? AND status IN ('Issued', 'Overdue')");
        $stmt->execute([$issueId]);
        $issue = $stmt->fetch();

        if (!$issue) {
            $errors[] = "Selected book issue record not found or already returned.";
        }

        if (empty($errors)) {
            try {
                $db->beginTransaction();

                // 1. Calculate fine
                $fineCalc = calculate_fine($issue['due_date'], $returnDate);
                $lateDays = $fineCalc['late_days'];
                $fineAmount = $fineCalc['fine_amount'];

                // 2. Update book_issues
                $uIss = $db->prepare("
                    UPDATE book_issues 
                    SET return_date = ?, status = 'Returned', returned_by = ? 
                    WHERE id = ?
                ");
                $uIss->execute([$returnDate, $_SESSION['user_id'], $issueId]);

                // 3. Update copy status -> Available
                $db->prepare("UPDATE book_copies SET status = 'Available' WHERE id = ?")->execute([$issue['copy_id']]);

                // 4. Handle Fine Record
                if ($fineAmount > 0) {
                    $fnCheck = $db->prepare("SELECT id FROM fines WHERE issue_id = ?");
                    $fnCheck->execute([$issueId]);
                    $existingFineId = $fnCheck->fetchColumn();

                    if ($existingFineId) {
                        $db->prepare("UPDATE fines SET late_days = ?, fine_amount = ? WHERE id = ?")->execute([$lateDays, $fineAmount, $existingFineId]);
                    } else {
                        $db->prepare("
                            INSERT INTO fines (issue_id, member_id, book_id, copy_id, late_days, fine_amount, paid_amount, status)
                            VALUES (?, ?, ?, ?, ?, ?, 0.00, 'Unpaid')
                        ")->execute([$issueId, $issue['member_id'], $issue['book_id'], $issue['copy_id'], $lateDays, $fineAmount]);
                    }
                }

                // 5. Send Notification
                $mUserStmt = $db->prepare("SELECT u.id, b.name AS book_name FROM members m JOIN users u ON m.user_id = u.id JOIN books b ON b.id = ? WHERE m.id = ?");
                $mUserStmt->execute([$issue['book_id'], $issue['member_id']]);
                $mUser = $mUserStmt->fetch();
                if ($mUser) {
                    add_notification($mUser['id'], "Book Returned: {$mUser['book_name']}", "Thank you for returning {$mUser['book_name']}. Fine generated: " . format_currency($fineAmount));
                }

                log_audit_action($_SESSION['user_id'], $_SESSION['role_code'], 'Book Return', 'BookIssues', "Issue ID: {$issueId}, Late Days: {$lateDays}, Fine: {$fineAmount}");

                $db->commit();

                if ($fineAmount > 0) {
                    set_flash_message('warning', "Book returned. Late Fine generated: " . format_currency($fineAmount) . ". Please collect fine payment.");
                    header("Location: " . BASE_URL . "librarian/fines/index.php?member_id=" . $issue['member_id']);
                } else {
                    set_flash_message('success', "Book returned successfully with zero fine.");
                    header("Location: " . BASE_URL . "librarian/dashboard.php");
                }
                exit();

            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = "Error processing return: " . $e->getMessage();
            }
        }
    }
}

// Fetch active issued books
$activeIssuesStmt = $db->query("
    SELECT bi.*, b.name AS book_name, b.book_code, bc.barcode, bc.copy_code,
           m.member_code, u.full_name AS member_name, m.membership_status
    FROM book_issues bi
    JOIN books b ON bi.book_id = b.id
    JOIN book_copies bc ON bi.copy_id = bc.id
    JOIN members m ON bi.member_id = m.id
    JOIN users u ON m.user_id = u.id
    WHERE bi.status IN ('Issued', 'Overdue')
    ORDER BY bi.due_date ASC
");
$activeIssues = $activeIssuesStmt->fetchAll();

$pageTitle = "Process Book Return";
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
                    <h2 class="section-title mb-1">Process Physical Book Return</h2>
                    <p class="text-muted small mb-0">Record physical book return, calculate late days, and generate fine invoice.</p>
                </div>
            </div>

            <div class="card sayak-card">
                <div class="card-header bg-white font-serif py-3 fw-bold fs-5 border-bottom">
                    <i class="fas fa-undo me-2 text-maroon" style="color: #8B1E26;"></i> Record Return Transaction
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= escape($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-bold small d-flex justify-content-between">
                                    <span>Select Issued Book Loan <span class="text-danger">*</span></span>
                                    <small class="text-muted"><span id="issCount"><?= count($activeIssues) ?></span> active loans</small>
                                </label>
                                <div class="input-group mb-1">
                                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted small"></i></span>
                                    <input type="text" id="loanFilterInput" class="form-control form-control-sm" placeholder="Type to search borrower name, member code, book title, or barcode..." oninput="filterLoanOptions(this.value)">
                                </div>
                                <select name="issue_id" id="issueSelect" class="form-select form-select-lg" required>
                                    <option value="">-- Choose Issued Book Copy / Member --</option>
                                    <?php foreach ($activeIssues as $iss): ?>
                                        <option value="<?= $iss['id'] ?>" <?= $preIssueId == $iss['id'] ? 'selected' : '' ?> data-text="<?= strtolower(escape($iss['member_name'] . ' ' . $iss['member_code'] . ' ' . $iss['book_name'] . ' ' . $iss['barcode'])) ?>">
                                            <?= escape($iss['member_name']) ?> (<?= escape($iss['member_code']) ?>) - <?= escape($iss['book_name']) ?> [Barcode: <?= escape($iss['barcode']) ?>] (Due: <?= format_date($iss['due_date']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Return Date</label>
                                <input type="date" name="return_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Current Configured Fine Rate</label>
                                <input type="text" class="form-control bg-light" value="₹<?= get_setting('fine_per_day', '5.00') ?> / day (Grace: <?= get_setting('grace_period_days', '2') ?> days)" readonly>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-gold btn-lg w-100 mt-4 font-serif text-dark fw-bold">
                            <i class="fas fa-check-circle me-2"></i> Calculate Fine & Confirm Return
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterLoanOptions(q) {
    q = q.trim().toLowerCase();
    const select = document.getElementById('issueSelect');
    let visible = 0;
    for (let i = 0; i < select.options.length; i++) {
        const opt = select.options[i];
        if (!opt.value) continue;
        const text = opt.getAttribute('data-text') || opt.textContent.toLowerCase();
        if (!q || text.includes(q)) {
            opt.style.display = '';
            visible++;
        } else {
            opt.style.display = 'none';
        }
    }
    const cnt = document.getElementById('issCount');
    if (cnt) cnt.textContent = visible;
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
