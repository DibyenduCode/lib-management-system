<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

require_role(['LIBRARIAN', 'SUPER_ADMIN']);

$db = getDB();
$errors = [];
$preMemberId = (int)($_GET['member_id'] ?? 0);

// Handle Cash Membership Renewal / Activation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_membership'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token mismatch.";
    } else {
        $memberId = (int)$_POST['member_id'];
        $planId = (int)$_POST['plan_id'];
        $notes = sanitize_input($_POST['notes'] ?? 'Cash Membership Renewal');

        $pStmt = $db->prepare("SELECT * FROM membership_plans WHERE id = ?");
        $pStmt->execute([$planId]);
        $plan = $pStmt->fetch();

        if ($memberId <= 0 || !$plan) {
            $errors[] = "Please select a valid member and membership plan.";
        }

        if (empty($errors)) {
            try {
                $db->beginTransaction();

                // Get current latest expiry date for member
                $curStmt = $db->prepare("SELECT expiry_date FROM memberships WHERE member_id = ? ORDER BY expiry_date DESC LIMIT 1");
                $curStmt->execute([$memberId]);
                $lastExpiry = $curStmt->fetchColumn();

                $today = new DateTime('today');

                if ($lastExpiry && new DateTime($lastExpiry) > $today) {
                    $startDate = $lastExpiry;
                } else {
                    $startDate = date('Y-m-d');
                }

                $expiryDate = date('Y-m-d', strtotime("{$startDate} +{$plan['duration_months']} months"));

                // 1. Insert new membership record
                $insMs = $db->prepare("
                    INSERT INTO memberships (member_id, plan_id, start_date, expiry_date, status)
                    VALUES (?, ?, ?, ?, 'Active')
                ");
                $insMs->execute([$memberId, $planId, $startDate, $expiryDate]);

                // 2. Insert membership_payments record
                $txnCode = generate_transaction_code($db);
                $insPay = $db->prepare("
                    INSERT INTO membership_payments (transaction_code, member_id, plan_id, amount, payment_method, payment_date, collected_by, start_date, expiry_date, notes)
                    VALUES (?, ?, ?, ?, 'CASH', NOW(), ?, ?, ?, ?)
                ");
                $insPay->execute([$txnCode, $memberId, $planId, $plan['price'], $_SESSION['user_id'], $startDate, $expiryDate, $notes]);
                $payId = $db->lastInsertId();

                // 3. Update member & user status back to Active
                $db->prepare("UPDATE members SET membership_status = 'Active', restriction_date = NULL WHERE id = ?")->execute([$memberId]);
                $db->prepare("UPDATE users u JOIN members m ON u.id = m.user_id SET u.status = 'Active' WHERE m.id = ?")->execute([$memberId]);

                // 4. Send Notifications
                $mUserStmt = $db->prepare("SELECT u.id, u.email, u.full_name FROM members m JOIN users u ON m.user_id = u.id WHERE m.id = ?");
                $mUserStmt->execute([$memberId]);
                $mUser = $mUserStmt->fetch();
                if ($mUser) {
                    add_notification($mUser['id'], "Membership Activated / Renewed", "Your subscription has been renewed until " . format_date($expiryDate) . ". Cash Txn: {$txnCode}");
                }

                log_audit_action($_SESSION['user_id'], $_SESSION['role_code'], 'Collect Cash Membership', 'MembershipPayments', "Txn: {$txnCode}, Amount: {$plan['price']}");

                $db->commit();

                set_flash_message('success', "Cash membership renewal completed! Txn Code: {$txnCode}");
                header("Location: " . BASE_URL . "librarian/receipt.php?type=membership&id=" . $payId);
                exit();

            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = "Error processing renewal: " . $e->getMessage();
            }
        }
    }
}

// Data
$membersList = $db->query("SELECT m.id, m.member_code, u.full_name FROM members m JOIN users u ON m.user_id = u.id ORDER BY u.full_name ASC")->fetchAll();
$plansList = $db->query("SELECT * FROM membership_plans WHERE status = 'Active' ORDER BY duration_months ASC")->fetchAll();

$pageTitle = "Cash Membership Renewal";
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
                    <h2 class="section-title mb-1">Process Cash Membership Renewal</h2>
                    <p class="text-muted small mb-0">Record cash membership payments, extend subscription validity, and produce printable receipts.</p>
                </div>
            </div>

            <div class="card sayak-card">
                <div class="card-header bg-white font-serif py-3 fw-bold fs-5 border-bottom">
                    <i class="fas fa-id-card me-2 text-maroon" style="color: #8B1E26;"></i> Record Cash Membership Payment
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
                            <div class="col-md-6">
                                <label class="form-label fw-bold small d-flex justify-content-between">
                                    <span>Select Library Member <span class="text-danger">*</span></span>
                                    <small class="text-muted"><span id="memCount"><?= count($membersList) ?></span> members</small>
                                </label>
                                <div class="input-group mb-1">
                                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted small"></i></span>
                                    <input type="text" id="memberFilterInput" class="form-control form-control-sm" placeholder="Type to search member by name or code..." oninput="filterMemberOptions(this.value)">
                                </div>
                                <select name="member_id" id="memberSelect" class="form-select" required>
                                    <option value="">-- Select Member --</option>
                                    <?php foreach ($membersList as $m): ?>
                                        <option value="<?= $m['id'] ?>" <?= $preMemberId == $m['id'] ? 'selected' : '' ?> data-text="<?= strtolower(escape($m['full_name'] . ' ' . $m['member_code'])) ?>">
                                            <?= escape($m['full_name']) ?> (<?= escape($m['member_code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Select Plan <span class="text-danger">*</span></label>
                                <select name="plan_id" class="form-select" required>
                                    <option value="">-- Choose Subscription Plan --</option>
                                    <?php foreach ($plansList as $p): ?>
                                        <option value="<?= $p['id'] ?>">
                                            <?= escape($p['plan_name']) ?> - ₹<?= number_format((float)$p['price'], 2) ?> (<?= $p['duration_months'] ?> Months)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small">Payment Method</label>
                                <input type="text" class="form-control bg-light fw-bold text-success" value="CASH ONLY" readonly>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small">Transaction Notes</label>
                                <input type="text" name="notes" class="form-control" value="Cash Membership Payment & Renewal">
                            </div>
                        </div>

                        <button type="submit" name="process_membership" class="btn btn-gold btn-lg w-100 mt-4 font-serif text-dark fw-bold">
                            <i class="fas fa-check-circle me-2"></i> Record Cash Payment & Issue Receipt
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterMemberOptions(q) {
    q = q.trim().toLowerCase();
    const select = document.getElementById('memberSelect');
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
    const cnt = document.getElementById('memCount');
    if (cnt) cnt.textContent = visible;
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

