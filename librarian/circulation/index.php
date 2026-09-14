<?php
/**
 * SAYAK LIBRARY - Physical Copies & Current Holders Tracker
 * Unified circulation monitor for Super Admin and Librarian
 */
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

require_role(['LIBRARIAN', 'SUPER_ADMIN']);

$db = getDB();

// ============================================================
// CSV EXPORT ACTION
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $copiesExportStmt = $db->query("
        SELECT 
            b.book_code,
            b.name AS book_name,
            COALESCE(a.name, 'N/A') AS author_name,
            COALESCE(c.category_name, 'General') AS category_name,
            bc.barcode,
            bc.copy_code,
            bc.shelf,
            bc.rack,
            bc.status AS copy_status,
            bi.issue_code,
            bi.issue_date,
            bi.due_date,
            m.member_code,
            u.full_name AS member_name,
            m.mobile AS member_mobile,
            u.email AS member_email,
            CASE 
                WHEN bi.id IS NOT NULL AND bi.due_date < CURDATE() THEN DATEDIFF(CURDATE(), bi.due_date)
                ELSE 0
            END AS days_overdue
        FROM book_copies bc
        JOIN books b ON bc.book_id = b.id
        LEFT JOIN authors a ON b.author_id = a.id
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN book_issues bi ON bc.id = bi.copy_id AND bi.status IN ('Issued', 'Overdue')
        LEFT JOIN members m ON bi.member_id = m.id
        LEFT JOIN users u ON m.user_id = u.id
        ORDER BY b.name ASC, bc.copy_code ASC
    ");
    $exportRows = $copiesExportStmt->fetchAll(PDO::FETCH_ASSOC);

    $filename = "Sayak_Library_Physical_Copies_Circulation_" . date('Y-m-d_His') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, [
        'Book Code',
        'Book Title',
        'Author',
        'Category',
        'Barcode',
        'Copy Code',
        'Shelf',
        'Rack',
        'Possession / Status',
        'Current Holder Name',
        'Member Code',
        'Member Phone',
        'Member Email',
        'Issue Code',
        'Issue Date',
        'Due Date',
        'Overdue Days'
    ]);

    foreach ($exportRows as $row) {
        $possessionStatus = !empty($row['member_name']) ? 'Issued to Member' : ($row['copy_status'] ?? 'Available');
        fputcsv($output, [
            $row['book_code'],
            $row['book_name'],
            $row['author_name'],
            $row['category_name'],
            $row['barcode'],
            $row['copy_code'],
            $row['shelf'],
            $row['rack'],
            $possessionStatus,
            $row['member_name'] ?? 'None (In Library)',
            $row['member_code'] ?? '',
            $row['member_mobile'] ?? '',
            $row['member_email'] ?? '',
            $row['issue_code'] ?? '',
            $row['issue_date'] ?? '',
            $row['due_date'] ?? '',
            $row['days_overdue'] > 0 ? $row['days_overdue'] : '0'
        ]);
    }
    fclose($output);
    exit();
}

// ============================================================
// METRICS COUNTERS
// ============================================================
$totalBooksCount = (int)$db->query("SELECT COUNT(*) FROM books")->fetchColumn();
$totalCopiesCount = (int)$db->query("SELECT COUNT(*) FROM book_copies")->fetchColumn();
$availableCopiesCount = (int)$db->query("SELECT COUNT(*) FROM book_copies WHERE status = 'Available'")->fetchColumn();
$activeIssuedCount = (int)$db->query("SELECT COUNT(*) FROM book_issues WHERE status IN ('Issued', 'Overdue')")->fetchColumn();
$overdueCount = (int)$db->query("SELECT COUNT(*) FROM book_issues WHERE status IN ('Issued', 'Overdue') AND due_date < CURDATE()")->fetchColumn();

// Fetch categories for filter dropdown
$categories = $db->query("SELECT id, category_name FROM categories WHERE status = 'Active' ORDER BY category_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// MAIN DATA: ALL COPIES & CURRENT ACTIVE LOANS
// ============================================================
$copiesQuery = $db->query("
    SELECT 
        bc.id AS copy_id,
        bc.copy_code,
        bc.barcode,
        bc.shelf,
        bc.rack,
        bc.status AS copy_status,
        b.id AS book_id,
        b.name AS book_name,
        b.book_code,
        b.isbn,
        b.edition,
        b.cover_image,
        COALESCE(a.name, 'N/A') AS author_name,
        COALESCE(c.category_name, 'General') AS category_name,
        COALESCE(cl.class_name, 'General') AS class_name,
        -- Active Loan Information
        bi.id AS issue_id,
        bi.issue_code,
        bi.issue_date,
        bi.due_date,
        bi.status AS issue_status,
        CASE 
            WHEN bi.due_date IS NOT NULL AND bi.due_date < CURDATE() THEN DATEDIFF(CURDATE(), bi.due_date)
            ELSE 0
        END AS days_overdue,
        m.id AS member_id,
        m.member_code,
        m.mobile AS member_mobile,
        u.full_name AS member_name,
        u.email AS member_email
    FROM book_copies bc
    JOIN books b ON bc.book_id = b.id
    LEFT JOIN authors a ON b.author_id = a.id
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN classes cl ON b.class_id = cl.id
    LEFT JOIN book_issues bi ON bc.id = bi.copy_id AND bi.status IN ('Issued', 'Overdue')
    LEFT JOIN members m ON bi.member_id = m.id
    LEFT JOIN users u ON m.user_id = u.id
    ORDER BY 
        CASE WHEN bi.id IS NOT NULL THEN 0 ELSE 1 END,
        b.name ASC, 
        bc.copy_code ASC
");
$allCopies = $copiesQuery->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// BOOK-LEVEL AGGREGATED VIEW
// ============================================================
$booksSummaryQuery = $db->query("
    SELECT 
        b.id,
        b.book_code,
        b.name AS book_name,
        b.isbn,
        b.edition,
        b.cover_image,
        COALESCE(a.name, 'N/A') AS author_name,
        COALESCE(c.category_name, 'General') AS category_name,
        COUNT(bc.id) AS total_copies,
        SUM(CASE WHEN bc.status = 'Available' THEN 1 ELSE 0 END) AS available_copies,
        SUM(CASE WHEN bi.id IS NOT NULL THEN 1 ELSE 0 END) AS issued_copies
    FROM books b
    LEFT JOIN authors a ON b.author_id = a.id
    LEFT JOIN categories c ON b.category_id = c.id
    LEFT JOIN book_copies bc ON b.id = bc.book_id
    LEFT JOIN book_issues bi ON bc.id = bi.copy_id AND bi.status IN ('Issued', 'Overdue')
    GROUP BY b.id
    ORDER BY b.name ASC
");
$allBooks = $booksSummaryQuery->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = "Physical Copies & Current Holders | Sayak Library";
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
            <!-- Header Banner -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                <div>
                    <h2 class="section-title mb-1 font-serif text-maroon" style="color: #7A0C0C;">
                        <i class="fas fa-barcode me-2"></i> Physical Copies & Current Holders
                    </h2>
                    <p class="text-muted small mb-0">
                        Live circulation tracker: monitor all books, copy barcodes, shelf locations, and who currently holds physical copies.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2 d-print-none">
                    <a href="?action=export_csv" class="btn btn-outline-success btn-sm font-serif">
                        <i class="fas fa-file-csv me-1"></i> Export to CSV
                    </a>
                    <button type="button" class="btn btn-outline-secondary btn-sm font-serif" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Print Circulation
                    </button>
                    <a href="<?= BASE_URL ?>librarian/issue/index.php" class="btn btn-maroon btn-sm font-serif text-white" style="background-color: #7A0C0C;">
                        <i class="fas fa-book-reader me-1"></i> Issue a Book
                    </a>
                </div>
            </div>

            <!-- KPI Metric Cards -->
            <div class="row g-3 mb-4 d-print-none">
                <div class="col-xl-2 col-md-4 col-6">
                    <div class="metric-card py-3 border-start border-4 border-primary">
                        <div class="metric-value text-primary"><?= $totalBooksCount ?></div>
                        <div class="metric-label"><i class="fas fa-book me-1"></i> Total Titles</div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6">
                    <div class="metric-card py-3 border-start border-4 border-dark">
                        <div class="metric-value text-dark"><?= $totalCopiesCount ?></div>
                        <div class="metric-label"><i class="fas fa-barcode me-1"></i> Physical Copies</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-4 col-6">
                    <div class="metric-card py-3 border-start border-4 border-warning">
                        <div class="metric-value text-warning"><?= $activeIssuedCount ?></div>
                        <div class="metric-label"><i class="fas fa-hand-holding me-1"></i> Currently in Hands of Members</div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-4 col-6">
                    <div class="metric-card py-3 border-start border-4 border-success">
                        <div class="metric-value text-success"><?= $availableCopiesCount ?></div>
                        <div class="metric-label"><i class="fas fa-check-circle me-1"></i> Available on Shelves</div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 col-6">
                    <div class="metric-card py-3 border-start border-4 border-danger">
                        <div class="metric-value text-danger"><?= $overdueCount ?></div>
                        <div class="metric-label"><i class="fas fa-exclamation-triangle me-1"></i> Overdue Copies</div>
                    </div>
                </div>
            </div>

            <!-- Filter & Search Controls Card -->
            <div class="card sayak-card mb-4 shadow-sm border-0 d-print-none">
                <div class="card-body p-3">
                    <div class="row g-3 align-items-center">
                        <!-- Search Bar -->
                        <div class="col-md-5 col-lg-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" id="circulationSearchInput" class="form-control border-start-0" 
                                       placeholder="Filter by Book, Barcode, Member Name, Member Code, Author, Shelf..." 
                                       onkeyup="filterCirculationTable()">
                                <button type="button" class="btn btn-outline-secondary" onclick="clearCirculationSearch()">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Category Filter -->
                        <div class="col-md-4 col-lg-3">
                            <select id="categoryFilter" class="form-select" onchange="filterCirculationTable()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= escape($cat['category_name']) ?>"><?= escape($cat['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- View Mode Switcher -->
                        <div class="col-md-3 col-lg-3 text-md-end">
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-sm btn-maroon active font-serif" id="btnViewCopies" onclick="switchView('copies')" style="background-color: #7A0C0C; color:#fff;">
                                    <i class="fas fa-list me-1"></i> By Copies
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary font-serif" id="btnViewBooks" onclick="switchView('books')">
                                    <i class="fas fa-layer-group me-1"></i> By Books
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Status Pills -->
                    <div class="d-flex flex-wrap gap-2 mt-3 pt-2 border-top">
                        <span class="small fw-bold text-muted me-1 align-self-center">Quick Filters:</span>
                        <button type="button" class="btn btn-sm btn-outline-dark active rounded-pill filter-pill" data-status="all" onclick="setStatusFilter('all', this)">
                            All (<?= count($allCopies) ?>)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning rounded-pill filter-pill text-dark" data-status="issued" onclick="setStatusFilter('issued', this)">
                            <i class="fas fa-hand-holding me-1 text-warning"></i> Currently Issued (<?= $activeIssuedCount ?>)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success rounded-pill filter-pill" data-status="available" onclick="setStatusFilter('available', this)">
                            <i class="fas fa-check me-1"></i> Available on Shelf (<?= $availableCopiesCount ?>)
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill filter-pill" data-status="overdue" onclick="setStatusFilter('overdue', this)">
                            <i class="fas fa-clock me-1"></i> Overdue with Members (<?= $overdueCount ?>)
                        </button>
                    </div>
                </div>
            </div>

            <!-- ==================================================== -->
            <!-- VIEW 1: PHYSICAL COPIES & CURRENT HOLDERS TABLE     -->
            <!-- ==================================================== -->
            <div id="copiesViewContainer" class="card sayak-card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="font-serif mb-0 fw-bold text-dark">
                        <i class="fas fa-stream me-2 text-maroon" style="color: #7A0C0C;"></i> Physical Copies & Current Possession List
                    </h5>
                    <span class="badge bg-secondary font-monospace" id="visibleCopiesCountBadge"><?= count($allCopies) ?> copies shown</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="copiesTable">
                        <thead class="table-light small text-uppercase font-serif">
                            <tr>
                                <th style="width: 28%;">Book Title & Category</th>
                                <th style="width: 22%;">Physical Copy & Barcode</th>
                                <th style="width: 35%;">Who Holds Physical Copy Now</th>
                                <th style="width: 15%;" class="text-end d-print-none">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allCopies)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="fas fa-box-open fa-3x mb-3 d-block text-secondary"></i>
                                        No physical copies found in the library database.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allCopies as $cp): 
                                    $isIssued = !empty($cp['issue_id']);
                                    $isOverdue = $isIssued && ($cp['days_overdue'] > 0);
                                    $statusCategory = $isOverdue ? 'overdue' : ($isIssued ? 'issued' : 'available');
                                ?>
                                    <tr class="circulation-row" 
                                        data-status="<?= $statusCategory ?>" 
                                        data-category="<?= escape(strtolower($cp['category_name'])) ?>"
                                        data-search="<?= escape(strtolower($cp['book_name'] . ' ' . $cp['book_code'] . ' ' . $cp['author_name'] . ' ' . $cp['barcode'] . ' ' . $cp['copy_code'] . ' ' . ($cp['member_name'] ?? '') . ' ' . ($cp['member_code'] ?? '') . ' ' . $cp['shelf'] . ' ' . $cp['rack'])) ?>">
                                        
                                        <!-- Book Title & Meta -->
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded bg-light border d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 44px; height: 56px; overflow:hidden;">
                                                    <?php 
                                                    $cpCoverPath = !empty($cp['cover_image']) && file_exists(ROOT_PATH . 'uploads/covers/' . $cp['cover_image']) ? BASE_URL . 'uploads/covers/' . $cp['cover_image'] : (!empty($cp['cover_image']) && file_exists(ROOT_PATH . $cp['cover_image']) ? BASE_URL . $cp['cover_image'] : '');
                                                    ?>
                                                    <?php if (!empty($cpCoverPath)): ?>
                                                        <img src="<?= $cpCoverPath ?>" alt="Cover" class="img-fluid" style="height:100%; object-fit:cover;">
                                                    <?php else: ?>
                                                        <i class="fas fa-book text-muted fa-lg"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="overflow-hidden">
                                                    <a href="<?= BASE_URL ?>books.php?id=<?= $cp['book_id'] ?>" target="_blank" class="fw-bold font-serif text-dark text-decoration-none text-truncate d-block hover-maroon">
                                                        <?= escape($cp['book_name']) ?>
                                                    </a>
                                                    <div class="small text-muted text-truncate">
                                                        <i class="fas fa-user-edit me-1"></i> <?= escape($cp['author_name']) ?>
                                                    </div>
                                                    <div class="small mt-1">
                                                        <span class="badge bg-light text-dark border font-monospace me-1"><?= escape($cp['book_code']) ?></span>
                                                        <span class="badge bg-secondary-subtle text-secondary border"><?= escape($cp['category_name']) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>

                                        <!-- Copy Details & Barcode -->
                                        <td>
                                            <div class="d-flex align-items-center mb-1">
                                                <span class="badge bg-dark font-monospace me-2">
                                                    <i class="fas fa-barcode me-1"></i> <?= escape($cp['barcode']) ?>
                                                </span>
                                                <span class="badge bg-light text-muted border font-monospace">
                                                    <?= escape($cp['copy_code']) ?>
                                                </span>
                                            </div>
                                            <div class="small text-muted">
                                                <i class="fas fa-map-marker-alt text-maroon me-1" style="color: #7A0C0C;"></i>
                                                <strong>Location:</strong> <?= escape($cp['shelf'] ?? 'N/A') ?> / <?= escape($cp['rack'] ?? 'N/A') ?>
                                            </div>
                                            <div class="mt-1">
                                                <?php if ($isOverdue): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-exclamation-triangle me-1"></i> Overdue By <?= $cp['days_overdue'] ?> Day(s)
                                                    </span>
                                                <?php elseif ($isIssued): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-hand-holding me-1"></i> Currently Issued
                                                    </span>
                                                <?php elseif ($cp['copy_status'] === 'Available'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle me-1"></i> In Stacks (Available)
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <?= escape($cp['copy_status']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </td>

                                        <!-- Who Has Physical Copy Now -->
                                        <td>
                                            <?php if ($isIssued): ?>
                                                <div class="p-2 border rounded-3 bg-light">
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <div>
                                                            <strong class="font-serif text-dark d-block">
                                                                <i class="fas fa-user-circle text-maroon me-1" style="color: #7A0C0C;"></i> <?= escape($cp['member_name']) ?>
                                                            </strong>
                                                            <span class="badge bg-maroon font-monospace" style="background-color: #7A0C0C; font-size:11px;">
                                                                <?= escape($cp['member_code']) ?>
                                                            </span>
                                                        </div>
                                                        <span class="badge bg-light text-secondary border font-monospace">
                                                            <?= escape($cp['issue_code']) ?>
                                                        </span>
                                                    </div>

                                                    <div class="small text-muted mt-1">
                                                        <?php if (!empty($cp['member_mobile'])): ?>
                                                            <a href="tel:<?= escape($cp['member_mobile']) ?>" class="text-decoration-none text-muted me-2">
                                                                <i class="fas fa-phone me-1 text-success"></i> <?= escape($cp['member_mobile']) ?>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if (!empty($cp['member_email'])): ?>
                                                            <a href="mailto:<?= escape($cp['member_email']) ?>" class="text-decoration-none text-muted">
                                                                <i class="fas fa-envelope me-1 text-primary"></i> <?= escape($cp['member_email']) ?>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>

                                                    <div class="small mt-2 pt-1 border-top d-flex justify-content-between">
                                                        <span><i class="fas fa-calendar-alt text-muted me-1"></i> Issued: <strong><?= date('d M Y', strtotime($cp['issue_date'])) ?></strong></span>
                                                        <span class="<?= $isOverdue ? 'text-danger fw-bold' : 'text-dark' ?>">
                                                            <i class="fas fa-calendar-check me-1"></i> Due: <strong><?= date('d M Y', strtotime($cp['due_date'])) ?></strong>
                                                        </span>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="p-2 rounded-3 border bg-white text-muted small d-flex align-items-center">
                                                    <i class="fas fa-shield-alt text-success fa-2x me-3 flex-shrink-0"></i>
                                                    <div>
                                                        <strong class="text-success d-block"><i class="fas fa-check me-1"></i> In Library Stacks</strong>
                                                        <span>Physical copy is in the library and ready to be issued to any active member.</span>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Quick Action Buttons -->
                                        <td class="text-end d-print-none">
                                            <?php if ($isIssued): ?>
                                                <a href="<?= BASE_URL ?>librarian/return/index.php?issue_id=<?= $cp['issue_id'] ?>" 
                                                   class="btn btn-sm btn-gold text-dark font-serif fw-bold d-block mb-1 shadow-sm"
                                                   title="Process return for this copy">
                                                    <i class="fas fa-undo me-1"></i> Return Copy
                                                </a>
                                            <?php elseif ($cp['copy_status'] === 'Available'): ?>
                                                <a href="<?= BASE_URL ?>librarian/issue/index.php?book_id=<?= $cp['book_id'] ?>" 
                                                   class="btn btn-sm btn-outline-maroon font-serif d-block mb-1" 
                                                   style="border-color: #7A0C0C; color: #7A0C0C;"
                                                   title="Issue this physical copy">
                                                    <i class="fas fa-book-reader me-1"></i> Issue Copy
                                                </a>
                                            <?php endif; ?>

                                            <a href="<?= BASE_URL ?>admin/books/index.php?action=edit&id=<?= $cp['book_id'] ?>" 
                                               class="btn btn-sm btn-outline-secondary d-block font-serif" 
                                               title="Manage Book & Copies">
                                                <i class="fas fa-edit me-1"></i> Manage
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ==================================================== -->
            <!-- VIEW 2: BOOK CATALOG SUMMARY VIEW (ACCORDION)      -->
            <!-- ==================================================== -->
            <div id="booksViewContainer" class="d-none mb-4">
                <div class="row g-3">
                    <?php foreach ($allBooks as $bk): ?>
                        <div class="col-12 book-card-row" 
                             data-category="<?= escape(strtolower($bk['category_name'])) ?>"
                             data-search="<?= escape(strtolower($bk['book_name'] . ' ' . $bk['book_code'] . ' ' . $bk['author_name'])) ?>">
                            <div class="card sayak-card shadow-sm border-0">
                                <div class="card-body p-3">
                                    <div class="row align-items-center">
                                        <div class="col-md-6 col-lg-5">
                                            <div class="d-flex align-items-center">
                                                <div class="rounded bg-light border d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 50px; height: 65px; overflow:hidden;">
                                                    <?php 
                                                    $bkCoverPath = !empty($bk['cover_image']) && file_exists(ROOT_PATH . 'uploads/covers/' . $bk['cover_image']) ? BASE_URL . 'uploads/covers/' . $bk['cover_image'] : (!empty($bk['cover_image']) && file_exists(ROOT_PATH . $bk['cover_image']) ? BASE_URL . $bk['cover_image'] : '');
                                                    ?>
                                                    <?php if (!empty($bkCoverPath)): ?>
                                                        <img src="<?= $bkCoverPath ?>" alt="Cover" class="img-fluid" style="height:100%; object-fit:cover;">
                                                    <?php else: ?>
                                                        <i class="fas fa-book text-muted fa-2x"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="overflow-hidden">
                                                    <h6 class="fw-bold font-serif mb-0 text-truncate">
                                                        <a href="<?= BASE_URL ?>books.php?id=<?= $bk['id'] ?>" target="_blank" class="text-dark text-decoration-none hover-maroon">
                                                            <?= escape($bk['book_name']) ?>
                                                        </a>
                                                    </h6>
                                                    <div class="small text-muted">Author: <?= escape($bk['author_name']) ?></div>
                                                    <div class="small mt-1">
                                                        <span class="badge bg-light text-dark border font-monospace me-1"><?= escape($bk['book_code']) ?></span>
                                                        <span class="badge bg-secondary-subtle text-secondary border"><?= escape($bk['category_name']) ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4 col-lg-4 text-md-center my-2 my-md-0">
                                            <div class="d-inline-flex gap-2">
                                                <div class="border rounded px-3 py-1 bg-light text-center">
                                                    <small class="text-muted d-block" style="font-size: 10px;">TOTAL COPIES</small>
                                                    <strong class="fs-6"><?= (int)$bk['total_copies'] ?></strong>
                                                </div>
                                                <div class="border rounded px-3 py-1 bg-success-subtle text-success text-center">
                                                    <small class="d-block" style="font-size: 10px;">AVAILABLE</small>
                                                    <strong class="fs-6"><?= (int)$bk['available_copies'] ?></strong>
                                                </div>
                                                <div class="border rounded px-3 py-1 bg-warning-subtle text-dark text-center">
                                                    <small class="d-block" style="font-size: 10px;">WITH MEMBERS</small>
                                                    <strong class="fs-6"><?= (int)$bk['issued_copies'] ?></strong>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-2 col-lg-3 text-md-end d-print-none">
                                            <button class="btn btn-sm btn-outline-dark font-serif me-1" type="button" data-bs-toggle="collapse" data-bs-target="#bookCopiesCollapse_<?= $bk['id'] ?>">
                                                <i class="fas fa-barcode me-1"></i> View Barcodes
                                            </button>
                                            <a href="<?= BASE_URL ?>librarian/issue/index.php?book_id=<?= $bk['id'] ?>" class="btn btn-sm btn-maroon font-serif text-white" style="background-color: #7A0C0C;">
                                                <i class="fas fa-book-reader me-1"></i> Issue
                                            </a>
                                        </div>
                                    </div>

                                    <!-- Collapsible Barcode Copies Breakdown -->
                                    <div class="collapse mt-3 pt-3 border-top" id="bookCopiesCollapse_<?= $bk['id'] ?>">
                                        <h6 class="font-serif fw-bold small text-muted text-uppercase mb-2">
                                            <i class="fas fa-stream me-1 text-maroon"></i> Physical Copies for "<?= escape($bk['book_name']) ?>"
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-bordered small align-middle mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Barcode</th>
                                                        <th>Copy Code</th>
                                                        <th>Shelf / Location</th>
                                                        <th>Current Possession / Holder</th>
                                                        <th class="text-end d-print-none">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $bookCopies = array_filter($allCopies, function($c) use ($bk) {
                                                        return $c['book_id'] == $bk['id'];
                                                    });
                                                    ?>
                                                    <?php if (empty($bookCopies)): ?>
                                                        <tr>
                                                            <td colspan="5" class="text-center py-2 text-muted">No physical copy barcodes generated for this book yet.</td>
                                                        </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($bookCopies as $c): 
                                                            $cIssued = !empty($c['issue_id']);
                                                            $cOverdue = $cIssued && ($c['days_overdue'] > 0);
                                                        ?>
                                                            <tr>
                                                                <td><span class="badge bg-dark font-monospace"><?= escape($c['barcode']) ?></span></td>
                                                                <td><span class="font-monospace text-muted"><?= escape($c['copy_code']) ?></span></td>
                                                                <td><?= escape($c['shelf']) ?> / <?= escape($c['rack']) ?></td>
                                                                <td>
                                                                    <?php if ($cIssued): ?>
                                                                        <span class="badge bg-warning text-dark me-1"><i class="fas fa-hand-holding me-1"></i> In Possession</span>
                                                                        <strong><?= escape($c['member_name']) ?></strong> (<?= escape($c['member_code']) ?>)
                                                                        <span class="text-muted ms-1">&bull; Due: <?= date('d M Y', strtotime($c['due_date'])) ?></span>
                                                                        <?php if ($cOverdue): ?>
                                                                            <span class="badge bg-danger ms-1">Overdue (<?= $c['days_overdue'] ?>d)</span>
                                                                        <?php endif; ?>
                                                                    <?php else: ?>
                                                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                                            <i class="fas fa-check-circle me-1"></i> Available in Library
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td class="text-end d-print-none">
                                                                    <?php if ($cIssued): ?>
                                                                        <a href="<?= BASE_URL ?>librarian/return/index.php?issue_id=<?= $c['issue_id'] ?>" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size: 11px;">
                                                                            <i class="fas fa-undo me-1"></i> Return
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <a href="<?= BASE_URL ?>librarian/issue/index.php?book_id=<?= $c['book_id'] ?>" class="btn btn-xs btn-outline-maroon py-0 px-2" style="border-color:#7A0C0C; color:#7A0C0C; font-size: 11px;">
                                                                            <i class="fas fa-book-reader me-1"></i> Issue
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
.hover-maroon:hover {
    color: #7A0C0C !important;
}
@media print {
    .d-print-none {
        display: none !important;
    }
    body {
        background-color: #fff !important;
        font-size: 12px;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    .table td, .table th {
        padding: 6px !important;
        border: 1px solid #dee2e6 !important;
    }
}
</style>

<script>
let currentStatusFilter = 'all';

function switchView(view) {
    const copiesView = document.getElementById('copiesViewContainer');
    const booksView = document.getElementById('booksViewContainer');
    const btnCopies = document.getElementById('btnViewCopies');
    const btnBooks = document.getElementById('btnViewBooks');

    if (view === 'copies') {
        copiesView.classList.remove('d-none');
        booksView.classList.add('d-none');
        btnCopies.classList.add('btn-maroon', 'active');
        btnCopies.classList.remove('btn-outline-secondary');
        btnCopies.style.backgroundColor = '#7A0C0C';
        btnCopies.style.color = '#fff';

        btnBooks.classList.remove('btn-maroon', 'active');
        btnBooks.classList.add('btn-outline-secondary');
        btnBooks.style.backgroundColor = '';
        btnBooks.style.color = '';
    } else {
        copiesView.classList.add('d-none');
        booksView.classList.remove('d-none');
        btnBooks.classList.add('btn-maroon', 'active');
        btnBooks.classList.remove('btn-outline-secondary');
        btnBooks.style.backgroundColor = '#7A0C0C';
        btnBooks.style.color = '#fff';

        btnCopies.classList.remove('btn-maroon', 'active');
        btnCopies.classList.add('btn-outline-secondary');
        btnCopies.style.backgroundColor = '';
        btnCopies.style.color = '';
    }
}

function setStatusFilter(status, elem) {
    currentStatusFilter = status;
    document.querySelectorAll('.filter-pill').forEach(pill => pill.classList.remove('active'));
    if (elem) elem.classList.add('active');
    filterCirculationTable();
}

function clearCirculationSearch() {
    document.getElementById('circulationSearchInput').value = '';
    filterCirculationTable();
}

function filterCirculationTable() {
    const query = document.getElementById('circulationSearchInput').value.trim().toLowerCase();
    const category = document.getElementById('categoryFilter').value.trim().toLowerCase();

    // 1. Filter Copies Table
    const copyRows = document.querySelectorAll('.circulation-row');
    let visibleCopiesCount = 0;

    copyRows.forEach(row => {
        const rowStatus = row.getAttribute('data-status') || '';
        const rowCategory = row.getAttribute('data-category') || '';
        const rowSearch = row.getAttribute('data-search') || '';

        // Status Match
        let statusMatch = true;
        if (currentStatusFilter === 'issued') {
            statusMatch = (rowStatus === 'issued' || rowStatus === 'overdue');
        } else if (currentStatusFilter === 'available') {
            statusMatch = (rowStatus === 'available');
        } else if (currentStatusFilter === 'overdue') {
            statusMatch = (rowStatus === 'overdue');
        }

        // Category Match
        const categoryMatch = !category || rowCategory.includes(category);

        // Text Search Match
        const textMatch = !query || rowSearch.includes(query);

        if (statusMatch && categoryMatch && textMatch) {
            row.style.display = '';
            visibleCopiesCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const badge = document.getElementById('visibleCopiesCountBadge');
    if (badge) {
        badge.textContent = visibleCopiesCount + ' copies shown';
    }

    // 2. Filter Books Accordion Rows
    const bookRows = document.querySelectorAll('.book-card-row');
    bookRows.forEach(card => {
        const cardCategory = card.getAttribute('data-category') || '';
        const cardSearch = card.getAttribute('data-search') || '';

        const catMatch = !category || cardCategory.includes(category);
        const txtMatch = !query || cardSearch.includes(query);

        if (catMatch && txtMatch) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
