<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';

require_unrestricted_member();

$db = getDB();
$memberId = $_SESSION['member_id'];

// Handle Request Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_id'])) {
    $reqId = (int)$_POST['cancel_id'];
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $cStmt = $db->prepare("UPDATE book_requests SET status = 'Cancelled' WHERE id = ? AND member_id = ? AND status = 'Pending'");
        $cStmt->execute([$reqId, $memberId]);
        set_flash_message('info', 'Book request cancelled.');
        header("Location: " . BASE_URL . "member/requests.php");
        exit();
    }
}

// Fetch requests
$stmt = $db->prepare("
    SELECT br.*, b.name AS book_name, b.book_code, b.id AS book_id 
    FROM book_requests br 
    JOIN books b ON br.book_id = b.id 
    WHERE br.member_id = ? 
    ORDER BY br.id DESC
");
$stmt->execute([$memberId]);
$requests = $stmt->fetchAll();

$pageTitle = "My Book Requests";
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
                    <h2 class="section-title mb-1">My Hard Copy Requests</h2>
                    <p class="text-muted small mb-0">Status tracking for your physical book borrowing requests.</p>
                </div>
                <a href="<?= BASE_URL ?>collections.php" class="btn btn-maroon btn-sm" style="background-color: #7A0C0C;">
                    <i class="fas fa-plus me-1"></i> Request Another Book
                </a>
            </div>

            <div class="card sayak-card">
                <div class="card-body p-0">
                    <?php if (!empty($requests)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Request Code</th>
                                        <th>Book Name</th>
                                        <th>Request Date</th>
                                        <th>Current Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $req): ?>
                                        <tr>
                                            <td><code><?= escape($req['request_code']) ?></code></td>
                                            <td>
                                                <a href="<?= BASE_URL ?>book-detail.php?id=<?= $req['book_id'] ?>" class="fw-bold font-serif text-dark text-decoration-none">
                                                    <?= escape($req['book_name']) ?>
                                                </a>
                                                <small class="text-muted d-block"><?= escape($req['book_code']) ?></small>
                                            </td>
                                            <td><?= format_date($req['request_date'], 'd M Y, h:i A') ?></td>
                                            <td>
                                                <?php if ($req['status'] === 'Approved'): ?>
                                                    <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i> Approved (Visit Library Counter)</span>
                                                <?php elseif ($req['status'] === 'Pending'): ?>
                                                    <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i> Pending Approval</span>
                                                <?php elseif ($req['status'] === 'Issued'): ?>
                                                    <span class="badge bg-primary">Book Issued</span>
                                                <?php elseif ($req['status'] === 'Rejected'): ?>
                                                    <span class="badge bg-danger">Rejected</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= escape($req['status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($req['status'] === 'Pending'): ?>
                                                    <form action="" method="POST" onsubmit="return confirm('Cancel this request?');" class="d-inline">
                                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                        <input type="hidden" name="cancel_id" value="<?= $req['id'] ?>">
                                                        <button type="submit" class="btn btn-outline-danger btn-sm">Cancel</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted small">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3 text-secondary"></i>
                            <p>You have not submitted any book borrowing requests yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
