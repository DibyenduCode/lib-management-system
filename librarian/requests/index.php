<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

require_role(['LIBRARIAN', 'SUPER_ADMIN']);

$db = getDB();

// Handle Approve / Reject / Issue Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('danger', 'Invalid security token.');
    } else {
        $reqId = (int)$_POST['request_id'];
        $action = $_POST['action'];

        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE book_requests SET status = 'Approved' WHERE id = ?");
            $stmt->execute([$reqId]);
            set_flash_message('success', 'Request approved. Member can collect book from counter.');
        } elseif ($action === 'reject') {
            $stmt = $db->prepare("UPDATE book_requests SET status = 'Rejected' WHERE id = ?");
            $stmt->execute([$reqId]);
            set_flash_message('info', 'Request rejected.');
        }
        header("Location: " . BASE_URL . "librarian/requests/index.php");
        exit();
    }
}

// Fetch all requests
$stmt = $db->query("
    SELECT br.*, b.name AS book_name, b.book_code, b.id AS book_id,
           m.id AS member_table_id, m.member_code, u.full_name AS member_name, u.email,
           (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'Available') AS available_copies
    FROM book_requests br
    JOIN books b ON br.book_id = b.id
    JOIN members m ON br.member_id = m.id
    JOIN users u ON m.user_id = u.id
    ORDER BY br.id DESC
");
$requests = $stmt->fetchAll();

$pageTitle = "Process Book Requests";
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
                    <h2 class="section-title mb-1">Physical Book Requests</h2>
                    <p class="text-muted small mb-0">Approve or reject member borrowing requests.</p>
                </div>
            </div>

            <div class="card sayak-card">
        <div class="card-body p-0">
            <?php if (!empty($requests)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Req Code</th>
                                <th>Book Details</th>
                                <th>Available Copies</th>
                                <th>Member</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td><code><?= escape($req['request_code']) ?></code></td>
                                    <td>
                                        <strong class="font-serif text-dark d-block"><?= escape($req['book_name']) ?></strong>
                                        <small class="text-muted"><?= escape($req['book_code']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?= $req['available_copies'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                            <?= $req['available_copies'] ?> Available
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="d-block"><?= escape($req['member_name']) ?></strong>
                                        <small class="text-maroon fw-bold" style="color: #7A0C0C;"><?= escape($req['member_code']) ?></small>
                                    </td>
                                    <td><small><?= format_date($req['request_date'], 'd M Y, h:i A') ?></small></td>
                                    <td>
                                        <?php if ($req['status'] === 'Pending'): ?>
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        <?php elseif ($req['status'] === 'Approved'): ?>
                                            <span class="badge bg-success">Approved</span>
                                        <?php elseif ($req['status'] === 'Issued'): ?>
                                            <span class="badge bg-primary">Issued</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= escape($req['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($req['status'] === 'Pending'): ?>
                                            <form action="" method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                                <button type="submit" name="action" value="approve" class="btn btn-sm btn-success me-1"><i class="fas fa-check"></i> Approve</button>
                                                <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger"><i class="fas fa-times"></i> Reject</button>
                                            </form>
                                        <?php elseif ($req['status'] === 'Approved'): ?>
                                            <a href="<?= BASE_URL ?>librarian/issue/index.php?request_id=<?= $req['id'] ?>" class="btn btn-sm btn-maroon" style="background-color: #7A0C0C;">
                                                <i class="fas fa-book-reader me-1"></i> Issue Copy
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted small">No Action</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <p>No borrowing requests recorded.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
