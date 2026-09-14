<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';

require_role('MEMBER');

$db = getDB();
$userId = $_SESSION['user_id'];
$memberId = $_SESSION['member_id'];

// Dynamically check 15-Day Expiry Restriction Rule (#43 & #44)
check_and_update_member_15day_rule($db, $memberId);

// Fetch Member Profile & Membership Details
$mStmt = $db->prepare("
    SELECT m.*, u.full_name, u.email, u.status AS user_status,
           ms.expiry_date, ms.start_date, ms.status AS ms_status, mp.plan_name, mp.price
    FROM members m 
    JOIN users u ON m.user_id = u.id 
    LEFT JOIN memberships ms ON m.id = ms.member_id 
    LEFT JOIN membership_plans mp ON ms.plan_id = mp.id 
    WHERE m.id = ?
    ORDER BY ms.id DESC LIMIT 1
");
$mStmt->execute([$memberId]);
$member = $mStmt->fetch();

// Fetch Outstanding Books Count
$bkStmt = $db->prepare("SELECT COUNT(*) FROM book_issues WHERE member_id = ? AND status IN ('Issued', 'Overdue')");
$bkStmt->execute([$memberId]);
$issuedBooksCount = (int)$bkStmt->fetchColumn();

// Fetch Pending Requests Count
$reqStmt = $db->prepare("SELECT COUNT(*) FROM book_requests WHERE member_id = ? AND status = 'Pending'");
$reqStmt->execute([$memberId]);
$pendingRequestsCount = (int)$reqStmt->fetchColumn();

// Fetch Outstanding Fines Total
$fnStmt = $db->prepare("SELECT SUM(fine_amount - paid_amount) FROM fines WHERE member_id = ? AND status != 'Paid'");
$fnStmt->execute([$memberId]);
$outstandingFine = (float)$fnStmt->fetchColumn();

// Book Search on Member Dashboard
$searchQ = sanitize_input($_GET['q'] ?? '');
$searchResults = [];
if (!empty($searchQ)) {
    $sTerm = "%{$searchQ}%";
    $sStmt = $db->prepare("
        SELECT b.*, a.name AS author_name, p.name AS publisher_name, c.category_name,
               (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'Available') AS available_copies
        FROM books b
        JOIN authors a ON b.author_id = a.id
        JOIN publishers p ON b.publisher_id = p.id
        LEFT JOIN categories c ON b.category_id = c.id
        WHERE b.name LIKE ? OR b.book_code LIKE ? OR b.isbn LIKE ? OR a.name LIKE ? OR p.name LIKE ?
        ORDER BY b.id DESC LIMIT 12
    ");
    $sStmt->execute([$sTerm, $sTerm, $sTerm, $sTerm, $sTerm]);
    $searchResults = $sStmt->fetchAll();
}

// Check if status is RESTRICTED (Expired > 15 Days)
$isRestricted = ($member['membership_status'] === 'Restricted' || $member['user_status'] === 'Restricted');

$expiryDateStr = $member['expiry_date'] ?? 'N/A';
$restrDateStr = $member['restriction_date'] ?? 'N/A';

$pageTitle = "Member Dashboard";
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
            <!-- Member Header Banner -->
            <div class="card sayak-card mb-4 border-start border-4 border-maroon">
                <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <span class="badge bg-maroon mb-1" style="background-color: #7A0C0C;"><?= escape($member['member_code']) ?></span>
                        <h3 class="font-serif fw-bold text-dark mb-0">Welcome, <?= escape($member['full_name']) ?></h3>
                        <small class="text-muted"><i class="fas fa-envelope me-1"></i> <?= escape($member['email']) ?> | <i class="fas fa-phone me-1"></i> <?= escape($member['mobile']) ?></small>
                    </div>
                    <div>
                        <?php if ($isRestricted): ?>
                            <span class="badge bg-danger fs-6 py-2 px-3"><i class="fas fa-user-lock me-1"></i> ACCOUNT RESTRICTED</span>
                        <?php else: ?>
                            <span class="badge bg-success fs-6 py-2 px-3"><i class="fas fa-check-circle me-1"></i> ACTIVE MEMBER</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Book Search & Discovery Bar -->
            <div class="card sayak-card mb-4 border-0 shadow-sm" style="background: linear-gradient(135deg, #7A0C0C 0%, #4A0505 100%); color: white;">
                <div class="card-body p-4">
                    <div class="row align-items-center g-3">
                        <div class="col-lg-5">
                            <h4 class="font-serif fw-bold mb-1 text-white"><i class="fas fa-search me-2 text-warning"></i> Search Book Catalog</h4>
                            <p class="small text-white-50 mb-0">Search physical books, authors, subjects, or digital PDF library.</p>
                        </div>
                        <div class="col-lg-7">
                            <form action="" method="GET" class="input-group input-group-lg">
                                <input type="text" name="q" class="form-control font-serif fs-6" placeholder="Type Book Title, Author Name, ISBN, or Code..." value="<?= escape($searchQ) ?>" required>
                                <button class="btn btn-gold px-4 font-serif fw-bold" type="submit" style="background-color: #D4AF37; color: #1A1A1A;">
                                    <i class="fas fa-search me-1"></i> Search
                                </button>
                                <?php if (!empty($searchQ)): ?>
                                    <a href="<?= BASE_URL ?>member/dashboard.php" class="btn btn-outline-light px-3 d-flex align-items-center" title="Clear Search">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($searchQ)): ?>
                <!-- Search Results Display -->
                <div class="card sayak-card mb-4 border-maroon">
                    <div class="card-header bg-white font-serif py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold text-dark mb-0">
                            <i class="fas fa-list me-2 text-maroon" style="color: #7A0C0C;"></i> Search Results for "<?= escape($searchQ) ?>"
                        </h5>
                        <span class="badge bg-maroon" style="background-color: #7A0C0C;"><?= count($searchResults) ?> Book(s) Found</span>
                    </div>
                    <div class="card-body p-4">
                        <?php if (!empty($searchResults)): ?>
                            <div class="row g-4">
                                <?php foreach ($searchResults as $sb): ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="card h-100 sayak-card p-3 border">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <span class="badge bg-light text-dark border"><?= escape($sb['book_code']) ?></span>
                                                <?php if (!empty($sb['pdf_file'])): ?>
                                                    <span class="badge bg-danger"><i class="fas fa-file-pdf me-1"></i> PDF</span>
                                                <?php endif; ?>
                                            </div>
                                            <h6 class="font-serif fw-bold text-dark mb-1"><?= escape($sb['name']) ?></h6>
                                            <small class="text-muted d-block mb-2"><i class="fas fa-pen me-1"></i> <?= escape($sb['author_name']) ?></small>
                                            
                                            <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                                                <small class="<?= $sb['available_copies'] > 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                                                    <i class="fas fa-book me-1"></i> <?= $sb['available_copies'] ?> Copy(ies) Avail
                                                </small>
                                                <div class="btn-group btn-group-sm">
                                                    <?php if (!empty($sb['pdf_file'])): ?>
                                                        <a href="<?= BASE_URL ?>pdf_viewer.php?id=<?= $sb['id'] ?>" target="_blank" class="btn btn-outline-danger py-1 px-2" title="Read PDF">
                                                            <i class="fas fa-eye me-1"></i> PDF
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="<?= BASE_URL ?>book-detail.php?id=<?= $sb['id'] ?>" class="btn btn-maroon py-1 px-2" style="background-color: #7A0C0C;" title="View & Request Book">
                                                        Details & Request
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-search fa-3x text-secondary mb-3"></i>
                                <p class="mb-0">No books found matching "<strong><?= escape($searchQ) ?></strong>". Try searching with another title or author name.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($isRestricted): ?>
                <!-- ============================================================ -->
                <!-- REQUIREMENT 44: RESTRICTED MEMBER WARNING SCREEN             -->
                <!-- ============================================================ -->
                <div class="card sayak-card border-danger border-3 shadow-lg mb-5">
                    <div class="card-header bg-danger text-white py-3 fw-bold font-serif fs-5">
                        <i class="fas fa-exclamation-triangle me-2"></i> ACCOUNT ACCESS RESTRICTED
                    </div>
                    <div class="card-body p-4 p-md-5 text-center">
                        <i class="fas fa-user-clock fa-5x text-danger mb-3"></i>
                        <h2 class="text-danger fw-bold font-serif mb-2">Your Membership Has Expired for More Than 15 Days</h2>
                        <p class="lead text-secondary mx-auto mb-4" style="max-width: 650px;">
                            Your library membership expired on <strong><?= format_date($expiryDateStr) ?></strong> and has surpassed the 15-day grace period. Normal member dashboard features have been restricted.
                        </p>

                        <div class="row g-3 justify-content-center mb-4 text-start">
                            <div class="col-md-5">
                                <div class="p-3 bg-light rounded border">
                                    <small class="text-muted d-block">Membership Expired Date:</small>
                                    <strong class="text-danger fs-6"><?= format_date($expiryDateStr) ?></strong>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="p-3 bg-light rounded border">
                                    <small class="text-muted d-block">Account Restricted Date:</small>
                                    <strong class="text-dark fs-6"><?= format_date($restrDateStr) ?></strong>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="p-3 bg-light rounded border">
                                    <small class="text-muted d-block">Outstanding Issued Books:</small>
                                    <strong class="<?= $issuedBooksCount > 0 ? 'text-danger' : 'text-success' ?> fs-6"><?= $issuedBooksCount ?> Book(s) Pending Return</strong>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="p-3 bg-light rounded border">
                                    <small class="text-muted d-block">Outstanding Late Fine:</small>
                                    <strong class="<?= $outstandingFine > 0 ? 'text-danger' : 'text-success' ?> fs-6"><?= format_currency($outstandingFine) ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning text-start mx-auto" style="max-width: 700px;">
                            <i class="fas fa-info-circle me-2"></i> Please visit the <strong>Sayak Library Administrative Desk</strong> to settle your cash membership renewal. If you have an unreturned physical book, the Librarian is authorized to process your book return and fine payment.
                        </div>

                        <div class="d-flex justify-content-center gap-3 mt-4">
                            <a href="<?= BASE_URL ?>contact.php" class="btn btn-maroon btn-lg font-serif" style="background-color: #7A0C0C;">
                                <i class="fas fa-phone-alt me-2"></i> Contact Librarian Desk
                            </a>
                            <a href="<?= BASE_URL ?>member/membership.php" class="btn btn-outline-secondary btn-lg font-serif">
                                <i class="fas fa-history me-2"></i> View Renewal History
                            </a>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- ============================================================ -->
                <!-- NORMAL UNRESTRICTED MEMBER DASHBOARD                         -->
                <!-- ============================================================ -->
                <!-- KPI Cards Grid -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3 col-sm-6">
                        <div class="metric-card">
                            <div class="metric-value"><?= $issuedBooksCount ?></div>
                            <div class="metric-label"><i class="fas fa-book-reader me-1 text-maroon"></i> Currently Issued Books</div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="metric-card" style="border-left-color: #D4AF37;">
                            <div class="metric-value" style="color: #B59325;"><?= $pendingRequestsCount ?></div>
                            <div class="metric-label"><i class="fas fa-clock me-1 text-warning"></i> Pending Book Requests</div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="metric-card" style="border-left-color: #198754;">
                            <div class="metric-value" style="color: #198754;"><?= format_currency($outstandingFine) ?></div>
                            <div class="metric-label"><i class="fas fa-receipt me-1 text-success"></i> Outstanding Late Fines</div>
                        </div>
                    </div>

                    <div class="col-md-3 col-sm-6">
                        <div class="metric-card" style="border-left-color: #0d6efd;">
                            <div class="metric-value fs-4 text-primary"><?= format_date($expiryDateStr) ?></div>
                            <div class="metric-label"><i class="fas fa-calendar-alt me-1 text-primary"></i> Membership Expiry</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Access Shortcuts -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card h-100 sayak-card text-center p-4">
                            <i class="fas fa-book fa-3x text-maroon mb-3" style="color: #7A0C0C;"></i>
                            <h5 class="font-serif fw-bold">My Issued Books</h5>
                            <p class="text-muted small">View your active physical loans, due dates, and return status.</p>
                            <a href="<?= BASE_URL ?>member/my-books.php" class="btn btn-outline-maroon btn-sm mt-auto">View My Books</a>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100 sayak-card text-center p-4">
                            <i class="fas fa-file-pdf fa-3x text-danger mb-3"></i>
                            <h5 class="font-serif fw-bold">Digital PDF Library</h5>
                            <p class="text-muted small">Access digital books online with our secure in-browser reader.</p>
                            <a href="<?= BASE_URL ?>collections.php?pdf=1" class="btn btn-maroon btn-sm mt-auto" style="background-color: #7A0C0C;">Explore PDF Library</a>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100 sayak-card text-center p-4">
                            <i class="fas fa-id-card fa-3x text-warning mb-3"></i>
                            <h5 class="font-serif fw-bold">Membership & Payments</h5>
                            <p class="text-muted small">Check current plan status, cash payment records, and renewals.</p>
                            <a href="<?= BASE_URL ?>member/membership.php" class="btn btn-outline-maroon btn-sm mt-auto">View Plan & History</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
