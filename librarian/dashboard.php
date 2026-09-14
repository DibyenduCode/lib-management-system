<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';

require_role(['LIBRARIAN', 'SUPER_ADMIN']);

$db = getDB();

// 1. KPI Counts
$totalMembers = (int)$db->query("SELECT COUNT(*) FROM members")->fetchColumn();
$activeMembers = (int)$db->query("SELECT COUNT(*) FROM members WHERE membership_status = 'Active'")->fetchColumn();
$expiredMembers = (int)$db->query("SELECT COUNT(*) FROM members WHERE membership_status = 'Expired'")->fetchColumn();
$restrictedMembers = (int)$db->query("SELECT COUNT(*) FROM members WHERE membership_status = 'Restricted'")->fetchColumn();

$totalBooks = (int)$db->query("SELECT COUNT(*) FROM books")->fetchColumn();
$totalCopies = (int)$db->query("SELECT COUNT(*) FROM book_copies")->fetchColumn();
$availableCopies = (int)$db->query("SELECT COUNT(*) FROM book_copies WHERE status = 'Available'")->fetchColumn();
$issuedBooks = (int)$db->query("SELECT COUNT(*) FROM book_issues WHERE status = 'Issued' OR status = 'Overdue'")->fetchColumn();

$pendingRequests = (int)$db->query("SELECT COUNT(*) FROM book_requests WHERE status = 'Pending'")->fetchColumn();
$overdueCount = (int)$db->query("SELECT COUNT(*) FROM book_issues WHERE status = 'Issued' AND due_date < CURDATE()")->fetchColumn();

// Today's Cash Collection (Membership + Fine)
$todayCashPayments = (float)$db->query("SELECT SUM(amount) FROM membership_payments WHERE DATE(payment_date) = CURDATE()")->fetchColumn();
$todayCashFines = (float)$db->query("SELECT SUM(amount_paid) FROM fine_payments WHERE DATE(payment_date) = CURDATE()")->fetchColumn();
$todayCashTotal = $todayCashPayments + $todayCashFines;

$outstandingFinesTotal = (float)$db->query("SELECT SUM(fine_amount - paid_amount) FROM fines WHERE status != 'Paid'")->fetchColumn();

// Fetch Pending Requests
$requestsStmt = $db->query("
    SELECT br.*, b.name AS book_name, b.book_code, m.member_code, u.full_name AS member_name
    FROM book_requests br
    JOIN books b ON br.book_id = b.id
    JOIN members m ON br.member_id = m.id
    JOIN users u ON m.user_id = u.id
    WHERE br.status = 'Pending'
    ORDER BY br.id DESC LIMIT 5
");
$recentRequests = $requestsStmt->fetchAll();

$pageTitle = "Librarian Operations Dashboard";
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
            <!-- Header Banner -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="section-title mb-1">Librarian Operations Center</h2>
                    <p class="text-muted small mb-0">Manage daily lending, book returns, member cash payments, and physical copy tracking.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= BASE_URL ?>librarian/circulation/index.php" class="btn btn-outline-info btn-sm font-serif">
                        <i class="fas fa-barcode me-1"></i> Physical Copies & Holders
                    </a>
                    <a href="<?= BASE_URL ?>admin/books/index.php" class="btn btn-maroon btn-sm font-serif" style="background-color: #7A0C0C;">
                        <i class="fas fa-book me-1"></i> Add / Edit / Delete Books
                    </a>
                    <a href="<?= BASE_URL ?>librarian/issue/index.php" class="btn btn-outline-dark btn-sm font-serif">
                        <i class="fas fa-book-reader me-1"></i> Issue Physical Book
                    </a>
                    <a href="<?= BASE_URL ?>librarian/return/index.php" class="btn btn-gold btn-sm text-dark font-serif fw-bold">
                        <i class="fas fa-undo me-1"></i> Process Book Return
                    </a>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="row g-3 mb-4">
                <div class="col-xl-2 col-md-4 col-6">
                    <div class="metric-card py-3">
                        <div class="metric-value"><?= $pendingRequests ?></div>
                        <div class="metric-label"><i class="fas fa-clock text-warning me-1"></i> Pending Requests</div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6">
                    <a href="<?= BASE_URL ?>librarian/circulation/index.php" class="text-decoration-none">
                        <div class="metric-card py-3" style="border-left-color: #dc3545;">
                            <div class="metric-value text-danger"><?= $overdueCount ?></div>
                            <div class="metric-label"><i class="fas fa-exclamation-circle text-danger me-1"></i> Overdue Books</div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-2 col-md-4 col-6">
                    <a href="<?= BASE_URL ?>librarian/circulation/index.php" class="text-decoration-none">
                        <div class="metric-card py-3" style="border-left-color: #0dcaf0;">
                            <div class="metric-value text-info"><?= $issuedBooks ?></div>
                            <div class="metric-label"><i class="fas fa-book-reader text-info me-1"></i> Issued Copies (Holders)</div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-2 col-md-4 col-6">
                    <div class="metric-card py-3" style="border-left-color: #198754;">
                        <div class="metric-value text-success"><?= format_currency($todayCashTotal) ?></div>
                        <div class="metric-label"><i class="fas fa-cash-register text-success me-1"></i> Today's Cash</div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6">
                    <a href="<?= BASE_URL ?>librarian/circulation/index.php" class="text-decoration-none">
                        <div class="metric-card py-3" style="border-left-color: #0d6efd;">
                            <div class="metric-value text-primary"><?= $availableCopies ?> / <?= $totalCopies ?></div>
                            <div class="metric-label"><i class="fas fa-layer-group text-primary me-1"></i> Available Copies</div>
                        </div>
                    </a>
                </div>
                <div class="col-xl-2 col-md-4 col-6">
                    <div class="metric-card py-3" style="border-left-color: #6f42c1;">
                        <div class="metric-value text-purple" style="color:#6f42c1;"><?= $activeMembers ?></div>
                        <div class="metric-label"><i class="fas fa-users me-1"></i> Active Members</div>
                    </div>
                </div>
            </div>

            <!-- Quick Operational Workflows -->
            <div class="row g-4">
                <!-- Pending Requests -->
                <div class="col-lg-6">
                    <div class="card sayak-card h-100">
                        <div class="card-header bg-white font-serif fw-bold py-3 d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-inbox text-maroon me-2" style="color: #7A0C0C;"></i> Pending Hard Copy Requests</span>
                            <a href="<?= BASE_URL ?>librarian/requests/index.php" class="btn btn-outline-maroon btn-sm">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($recentRequests)): ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentRequests as $req): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                                            <div>
                                                <strong class="d-block text-dark font-serif"><?= escape($req['book_name']) ?></strong>
                                                <small class="text-muted">Requested by: <strong><?= escape($req['member_name']) ?></strong> (<?= escape($req['member_code']) ?>)</small>
                                            </div>
                                            <a href="<?= BASE_URL ?>librarian/requests/index.php?process_id=<?= $req['id'] ?>" class="btn btn-sm btn-maroon" style="background-color: #7A0C0C;">Process Request</a>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="p-4 text-center text-muted">No pending borrowing requests.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Links Panel -->
                <div class="col-lg-6">
                    <div class="card sayak-card h-100">
                        <div class="card-header bg-white font-serif fw-bold py-3">
                            <i class="fas fa-th-large text-maroon me-2" style="color: #7A0C0C;"></i> Quick Action Shortcuts
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <a href="<?= BASE_URL ?>admin/books/index.php" class="btn btn-maroon w-100 p-3 text-start text-white shadow-sm" style="background-color: #7A0C0C;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-book-open fa-3x me-3"></i>
                                            <div>
                                                <h5 class="fw-bold mb-1 font-serif">Master Book & Digital Library Manager</h5>
                                                <small class="text-light">Single unified form to manage Book Catalog, Physical Copies & PDF Files.</small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="<?= BASE_URL ?>librarian/members/index.php" class="btn btn-outline-dark w-100 p-3 text-start">
                                        <i class="fas fa-users fa-2x text-primary d-block mb-2"></i>
                                        <span class="fw-bold d-block">Manage Members</span>
                                        <small class="text-muted">Activate / Status Checks</small>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="<?= BASE_URL ?>librarian/fines/index.php" class="btn btn-outline-dark w-100 p-3 text-start">
                                        <i class="fas fa-rupee-sign fa-2x text-success d-block mb-2"></i>
                                        <span class="fw-bold d-block">Cash Fine Collection</span>
                                        <small class="text-muted">Collect Fines & Print Receipts</small>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="<?= BASE_URL ?>librarian/reports/index.php" class="btn btn-outline-dark w-100 p-3 text-start">
                                        <i class="fas fa-chart-line fa-2x text-info d-block mb-2"></i>
                                        <span class="fw-bold d-block">Operational Reports</span>
                                        <small class="text-muted">Cash Collections & Audits</small>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="<?= BASE_URL ?>librarian/requests/index.php" class="btn btn-outline-dark w-100 p-3 text-start">
                                        <i class="fas fa-inbox fa-2x text-warning d-block mb-2"></i>
                                        <span class="fw-bold d-block">Book Requests</span>
                                        <small class="text-muted">Approve / Reject Loans</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
