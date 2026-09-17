<?php
$pageTitle = "Book Collections Catalog";
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Check catalog mode (Database vs Google Sheet)
$catalogMode = get_setting('catalog_mode', 'database');
if ($catalogMode === 'sheet') {
    $sheetUrl = get_setting('catalog_sheet_url', '');
    $embedUrl = get_catalog_sheet_embed_url($sheetUrl);
    $sheetTitle = get_setting('catalog_sheet_title', 'Library Books & Catalog Resources');
    $sheetNotice = get_setting('catalog_sheet_notice', 'Our physical library collection is currently being digitized into this portal. In the meantime, please browse our complete book list, titles, and links in the live spreadsheet below.');
    ?>
    <div class="container-fluid px-lg-5 py-4">
        <!-- Notice & Header Card -->
        <div class="card sayak-card mb-4 border-0 shadow-sm overflow-hidden">
            <div class="p-4 text-white" style="background: linear-gradient(135deg, #7A0C0C 0%, #4A0505 100%);">
                <div class="d-md-flex justify-content-between align-items-center">
                    <div class="mb-3 mb-md-0">
                        <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-2">
                            <i class="fas fa-file-excel me-1"></i> Interactive Spreadsheet Catalog
                        </span>
                        <h2 class="font-serif fw-bold mb-1 text-white"><?= escape($sheetTitle) ?></h2>
                        <p class="mb-0 text-white-50 small" style="max-width: 800px;"><?= nl2br(escape($sheetNotice)) ?></p>
                    </div>
                    <?php if (!empty($sheetUrl)): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="<?= escape($sheetUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-warning btn-sm fw-bold px-3 py-2 shadow-sm text-dark">
                                <i class="fas fa-external-link-alt me-1"></i> Open in Google Sheets
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bg-light px-4 py-2 border-top d-flex justify-content-between align-items-center flex-wrap gap-2 text-muted small">
                <div>
                    <i class="fas fa-info-circle text-primary me-1"></i> <strong>Search Tip:</strong> Click inside the sheet below and press <kbd class="bg-white text-dark border">Ctrl</kbd> + <kbd class="bg-white text-dark border">F</kbd> (or <kbd class="bg-white text-dark border">⌘</kbd> + <kbd class="bg-white text-dark border">F</kbd> on Mac) to search any book title or writer instantly.
                </div>
                <div>
                    <i class="fas fa-sync-alt text-success me-1"></i> Live Auto-Updated
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['role_code']) && in_array($_SESSION['role_code'], ['SUPER_ADMIN', 'LIBRARIAN'])): ?>
            <div class="alert alert-warning border-start border-4 border-warning shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 py-2">
                <div class="small">
                    <strong><i class="fas fa-user-shield me-1 text-warning"></i> Admin Notice:</strong> Public users currently see this Google Sheet. You and your staff can continue entering physical books & PDF documents in the background without exposing an incomplete catalog.
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>admin/books/index.php" class="btn btn-sm btn-dark">
                        <i class="fas fa-book-medical me-1"></i> Enter Books (Admin)
                    </a>
                    <a href="<?= BASE_URL ?>admin/settings/index.php#catalog-settings" class="btn btn-sm btn-outline-dark">
                        <i class="fas fa-cog me-1"></i> Mode Settings
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($embedUrl)): ?>
            <!-- Google Sheet Iframe Container -->
            <div class="card sayak-card border-0 shadow-sm overflow-hidden mb-4">
                <div style="min-height: 780px;">
                    <iframe 
                        src="<?= escape($embedUrl) ?>" 
                        style="width: 100%; height: 780px; border: 0;" 
                        allowfullscreen
                        loading="lazy"
                    ></iframe>
                </div>
                <div class="card-footer bg-white text-center py-2 small text-muted border-top">
                    Having trouble viewing the embedded sheet? 
                    <a href="<?= escape($sheetUrl) ?>" target="_blank" rel="noopener noreferrer" class="fw-bold text-maroon ms-1" style="color: #7A0C0C;">
                        Click here to view full sheet in Google Sheets <i class="fas fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="card sayak-card border-0 shadow-sm p-5 text-center my-4">
                <div class="py-4">
                    <i class="fas fa-file-excel fa-4x text-warning mb-3"></i>
                    <h4 class="font-serif fw-bold">Google Sheet URL Not Configured</h4>
                    <p class="text-muted" style="max-width: 600px; margin: 0 auto;">
                        Google Sheet Mode is enabled, but the spreadsheet link has not been added yet in System Settings.
                    </p>
                    <?php if (isset($_SESSION['role_code']) && in_array($_SESSION['role_code'], ['SUPER_ADMIN', 'LIBRARIAN'])): ?>
                        <div class="mt-3">
                            <a href="<?= BASE_URL ?>admin/settings/index.php#catalog-settings" class="btn btn-maroon font-serif fw-bold" style="background-color: #7A0C0C;">
                                <i class="fas fa-link me-1"></i> Configure Google Sheet Link in Settings
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit();
}

// Get Search & Filter parameters
$searchQuery = sanitize_input($_GET['q'] ?? '');
$catFilter = (int)($_GET['cat'] ?? 0);
$classFilter = (int)($_GET['class'] ?? 0);
$subjFilter = (int)($_GET['subject'] ?? 0);
$writerFilter = (int)($_GET['writer'] ?? 0);
$pdfOnly = isset($_GET['pdf']) && $_GET['pdf'] == '1';

// Pagination setup
$limit = 8;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// Build Dynamic SQL query
$whereClauses = [];
$params = [];

if (!empty($searchQuery)) {
    $whereClauses[] = "(b.name LIKE ? OR b.book_code LIKE ? OR b.isbn LIKE ? OR a.name LIKE ? OR p.name LIKE ?)";
    $term = "%{$searchQuery}%";
    $params = array_merge($params, [$term, $term, $term, $term, $term]);
}

if ($catFilter > 0) {
    $whereClauses[] = "b.category_id = ?";
    $params[] = $catFilter;
}

if ($classFilter > 0) {
    $whereClauses[] = "b.class_id = ?";
    $params[] = $classFilter;
}

if ($subjFilter > 0) {
    $whereClauses[] = "b.subject_id = ?";
    $params[] = $subjFilter;
}

if ($writerFilter > 0) {
    $whereClauses[] = "b.author_id = ?";
    $params[] = $writerFilter;
}

if ($pdfOnly) {
    $whereClauses[] = "(b.pdf_file IS NOT NULL AND b.pdf_file != '')";
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Count Total matching records
$countSql = "
    SELECT COUNT(*) 
    FROM books b 
    JOIN authors a ON b.author_id = a.id 
    JOIN publishers p ON b.publisher_id = p.id 
    {$whereSql}
";
$cntStmt = $db->prepare($countSql);
$cntStmt->execute($params);
$totalRecords = (int)$cntStmt->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $limit));

// Fetch Records with pagination
$sql = "
    SELECT b.*, a.name AS author_name, p.name AS publisher_name, c.category_name, cl.class_name,
           (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'Available') AS available_copies
    FROM books b 
    JOIN authors a ON b.author_id = a.id 
    JOIN publishers p ON b.publisher_id = p.id 
    JOIN categories c ON b.category_id = c.id
    JOIN classes cl ON b.class_id = cl.id
    {$whereSql}
    ORDER BY b.id DESC 
    LIMIT {$limit} OFFSET {$offset}
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Filter lists dropdown data
$categories = $db->query("SELECT * FROM categories WHERE status = 'Active'")->fetchAll();
$classesList = $db->query("SELECT * FROM classes WHERE status = 'Active'")->fetchAll();
$subjectsList = $db->query("SELECT * FROM subjects WHERE status = 'Active'")->fetchAll();
$authorsList = $db->query("SELECT * FROM authors WHERE status = 'Active'")->fetchAll();
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar Search Filters -->
        <div class="col-lg-3 mb-4">
            <div class="card sayak-card">
                <div class="card-header bg-maroon text-white fw-bold" style="background-color: #7A0C0C;">
                    <i class="fas fa-filter me-2"></i> Filter Catalog
                </div>
                <div class="card-body">
                    <form action="" method="GET">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Search Keywords</label>
                            <input type="text" name="q" class="form-control form-control-sm" placeholder="Book, Author, Code..." value="<?= escape($searchQuery) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Category</label>
                            <select name="cat" class="form-select form-select-sm">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $catFilter == $cat['id'] ? 'selected' : '' ?>><?= escape($cat['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Class / Standard</label>
                            <select name="class" class="form-select form-select-sm">
                                <option value="0">All Classes</option>
                                <?php foreach ($classesList as $cl): ?>
                                    <option value="<?= $cl['id'] ?>" <?= $classFilter == $cl['id'] ? 'selected' : '' ?>><?= escape($cl['class_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Subject</label>
                            <select name="subject" class="form-select form-select-sm">
                                <option value="0">All Subjects</option>
                                <?php foreach ($subjectsList as $sb): ?>
                                    <option value="<?= $sb['id'] ?>" <?= $subjFilter == $sb['id'] ? 'selected' : '' ?>><?= escape($sb['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Writer / Author</label>
                            <select name="writer" class="form-select form-select-sm">
                                <option value="0">All Writers</option>
                                <?php foreach ($authorsList as $aut): ?>
                                    <option value="<?= $aut['id'] ?>" <?= $writerFilter == $aut['id'] ? 'selected' : '' ?>><?= escape($aut['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="pdf" value="1" id="pdfCheck" <?= $pdfOnly ? 'checked' : '' ?>>
                            <label class="form-check-label small" for="pdfCheck">
                                <i class="fas fa-file-pdf text-danger me-1"></i> Digital PDF Available Only
                            </label>
                        </div>

                        <button type="submit" class="btn btn-maroon btn-sm w-100 fw-bold" style="background-color: #7A0C0C;">Apply Filters</button>
                        <a href="<?= BASE_URL ?>collections.php" class="btn btn-light btn-sm w-100 mt-2 text-dark">Reset Filters</a>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Book Grid -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="font-serif fw-bold text-maroon mb-0" style="color: #7A0C0C;">Library Catalog</h4>
                <span class="text-muted small">Showing <?= count($books) ?> of <?= $totalRecords ?> Books</span>
            </div>

            <?php if (!empty($books)): ?>
                <div class="row g-4">
                    <?php foreach ($books as $book): ?>
                        <div class="col-md-4 col-sm-6">
                            <div class="card book-card sayak-card">
                                <div class="book-cover-wrapper">
                                    <?php if (!empty($book['cover_image']) && file_exists(ROOT_PATH . 'uploads/covers/' . $book['cover_image'])): ?>
                                        <img src="<?= BASE_URL ?>uploads/covers/<?= escape($book['cover_image']) ?>" class="book-cover-img" alt="<?= escape($book['name']) ?>">
                                    <?php else: ?>
                                        <div class="text-center p-3">
                                            <i class="fas fa-book fa-4x text-secondary mb-2"></i>
                                            <div class="badge bg-maroon" style="background-color: #7A0C0C;"><?= escape($book['book_code']) ?></div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="badge bg-light text-dark border"><?= escape($book['category_name']) ?></span>
                                        <?php if (!empty($book['pdf_file'])): ?>
                                            <span class="badge bg-danger"><i class="fas fa-file-pdf"></i> PDF</span>
                                        <?php endif; ?>
                                    </div>
                                    <h6 class="fw-bold mb-1 font-serif text-dark text-truncate"><?= escape($book['name']) ?></h6>
                                    <small class="text-muted">By <?= escape($book['author_name']) ?></small>

                                    <div class="mt-auto pt-3 border-top d-flex justify-content-between align-items-center">
                                        <small class="<?= $book['available_copies'] > 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                                            <?= $book['available_copies'] > 0 ? $book['available_copies'] . ' Available' : 'Out of Stock' ?>
                                        </small>
                                        <a href="<?= BASE_URL ?>book-detail.php?id=<?= $book['id'] ?>" class="btn btn-outline-maroon btn-sm">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($searchQuery) ?>&cat=<?= $catFilter ?>&class=<?= $classFilter ?>&subject=<?= $subjFilter ?>&writer=<?= $writerFilter ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-info text-center py-5">
                    <i class="fas fa-book-open fa-3x mb-3 text-muted"></i>
                    <h5>No books match your filter criteria</h5>
                    <p class="small text-muted">Try clearing search keywords or selecting different category filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
