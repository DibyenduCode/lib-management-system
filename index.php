<?php
$pageTitle = "Home - Premier Public & Academic Library";
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Fetch 4 featured books
$booksStmt = $db->query("
    SELECT b.*, a.name AS author_name, c.category_name,
           (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'Available') AS available_copies
    FROM books b
    JOIN authors a ON b.author_id = a.id
    JOIN categories c ON b.category_id = c.id
    ORDER BY b.id DESC LIMIT 4
");
$featuredBooks = $booksStmt->fetchAll();

// Fetch Membership Plans
$plansStmt = $db->query("SELECT * FROM membership_plans WHERE status = 'Active' ORDER BY duration_months ASC");
$plans = $plansStmt->fetchAll();

// Fetch Latest 3 Notices for Homepage
$noticesStmt = $db->query("
    SELECT * FROM notices 
    WHERE status = 'Published' AND (expiry_date IS NULL OR expiry_date >= CURDATE()) 
    ORDER BY priority DESC, publish_date DESC LIMIT 3
");
$latestNotices = $noticesStmt->fetchAll();

// Fetch 3 Gallery Preview Photos
$galleryStmt = $db->query("SELECT * FROM gallery WHERE is_published = 1 ORDER BY id DESC LIMIT 3");
$galleryItems = $galleryStmt->fetchAll();
?>

<!-- Hero Section -->
<section class="hero-section text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-7 mb-4 mb-lg-0">
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3"><?= escape(get_setting('hero_badge', 'WELCOME TO SAYAK LIBRARY')) ?></span>
                <h1 class="hero-title"><?= escape(get_setting('hero_title', 'Empowering Minds Through Knowledge & Literature')) ?></h1>
                <p class="hero-lead"><?= escape(get_setting('hero_lead', 'Explore over 25,000+ physical books, rare historical manuscripts, and an expanding digital PDF repository for students, researchers, and book lovers.')) ?></p>
                
                <!-- Instant Search Bar -->
                <form action="<?= BASE_URL ?>collections.php" method="GET" class="row g-2 bg-white p-2 rounded-3 shadow mt-3">
                    <div class="col-md-8">
                        <div class="input-group">
                            <span class="input-group-text bg-transparent border-0 text-muted"><i class="fas fa-search"></i></span>
                            <input type="text" name="q" class="form-control border-0 shadow-none text-dark" placeholder="Search by Book Name, Writer, ISBN, Code..." required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-maroon w-100 py-2">Search Catalog</button>
                    </div>
                </form>
            </div>
            <div class="col-lg-5">
                <div class="hero-card bg-white text-dark p-4 rounded-3 shadow-lg position-relative border-top border-4 border-warning">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-danger px-3 py-1 rounded-pill fw-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                            <i class="fas fa-heart me-1"></i> DONATE & SUPPORT
                        </span>
                        <small class="text-success fw-bold"><i class="fas fa-shield-alt me-1"></i> Community Initiative</small>
                    </div>

                    <h4 class="font-serif fw-bold mb-2" style="color: #7A0C0C;">
                        <?= escape(get_setting('donate_appeal_title', 'Donate to Sayak Library')) ?>
                    </h4>
                    <p class="text-secondary small mb-3">
                        <?= escape(get_setting('donate_appeal_desc', 'Your contributions directly support book restoration, student scholarships, rare manuscript preservation, and e-learning resources.')) ?>
                    </p>

                    <!-- Quick Impact Points -->
                    <div class="bg-light p-3 rounded-2 mb-3 border">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-book text-maroon me-2" style="color: #7A0C0C;"></i>
                            <span class="small fw-semibold text-dark">Gift syllabus or reference books to readers</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-user-graduate text-maroon me-2" style="color: #7A0C0C;"></i>
                            <span class="small fw-semibold text-dark">Sponsor underprivileged student memberships</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-qrcode text-warning me-2"></i>
                            <span class="small text-muted">UPI: <strong class="text-dark font-monospace"><?= escape(get_setting('donate_upi_id', 'sayaklibrary@sbi')) ?></strong></span>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-7">
                            <a href="<?= BASE_URL ?>donate.php" class="btn btn-gold btn-sm w-100 py-2 fw-bold shadow-sm">
                                <i class="fas fa-hand-holding-heart me-1"></i> Donate Now
                            </a>
                        </div>
                        <div class="col-5">
                            <a href="<?= BASE_URL ?>usership.php" class="btn btn-outline-maroon btn-sm w-100 py-2 fw-bold">
                                Join Library
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- News & Updates Section (Requirement 11) -->
<section class="py-5 bg-white border-bottom">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="section-title mb-1">News & Updates</h2>
                <p class="text-muted small mb-0">Official announcements, library schedules, and program notices.</p>
            </div>
            <a href="<?= BASE_URL ?>news.php" class="btn btn-outline-maroon btn-sm">View All Notices <i class="fas fa-arrow-right ms-1"></i></a>
        </div>

        <div class="row g-4">
            <?php if (!empty($latestNotices)): ?>
                <?php foreach ($latestNotices as $notice): ?>
                    <div class="col-md-4">
                        <div class="card h-100 border-0 shadow-sm sayak-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-secondary"><?= escape($notice['priority']) ?></span>
                                    <small class="text-muted"><i class="far fa-calendar-alt me-1"></i> <?= format_date($notice['publish_date']) ?></small>
                                </div>
                                <h5 class="card-title fw-bold text-dark font-serif" style="font-size: 1.1rem;"><?= escape($notice['title']) ?></h5>
                                <p class="card-text text-secondary small"><?= escape($notice['short_description']) ?></p>
                            </div>
                            <div class="card-footer bg-transparent border-0 pt-0 pb-3">
                                <a href="<?= BASE_URL ?>news-detail.php?id=<?= $notice['id'] ?>" class="text-decoration-none fw-bold text-maroon small" style="color: #7A0C0C;">
                                    Read Full Notice <i class="fas fa-chevron-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted py-4">No active notices at this time.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Library Introduction -->
<section class="py-5" style="background-color: #F8F9FA;">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-6">
                <span class="text-uppercase text-maroon fw-bold small" style="color: #7A0C0C; letter-spacing: 1px;"><?= escape(get_setting('about_subtitle', 'ABOUT OUR INSTITUTION')) ?></span>
                <h2 class="section-title mt-1"><?= escape(get_setting('about_title', 'A Center of Learning & Knowledge Excellence')) ?></h2>
                <p><?= escape(get_setting('about_para1', 'Established in 1995, SAYAK LIBRARY serves as a premier educational repository and public reading hub. We cater to school students, higher secondary candidates, civil service aspirants, and general readers across Bengal.')) ?></p>
                <div class="row g-3 mt-3">
                    <div class="col-6">
                        <div class="p-3 bg-white rounded shadow-sm border-start border-4 border-danger">
                            <h3 class="fw-bold mb-0 text-maroon" style="color: #7A0C0C;"><?= escape(get_setting('about_physical_count', '25,000+')) ?></h3>
                            <small class="text-muted">Physical Books</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-white rounded shadow-sm border-start border-4 border-warning">
                            <h3 class="fw-bold mb-0 text-dark"><?= escape(get_setting('about_digital_count', '5,000+')) ?></h3>
                            <small class="text-muted">Digital PDFs</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="p-4 bg-white rounded-3 shadow">
                    <h4 class="font-serif fw-bold mb-3" style="color: #7A0C0C;"><i class="fas fa-university me-2"></i> Library Facilities</h4>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item bg-transparent px-0"><i class="fas fa-check-circle text-success me-2"></i> <?= escape(get_setting('facility_1', 'Air-conditioned quiet reading hall with seating for 120 readers.')) ?></li>
                        <li class="list-group-item bg-transparent px-0"><i class="fas fa-check-circle text-success me-2"></i> <?= escape(get_setting('facility_2', 'High-speed Wi-Fi and online computer library catalog (OPAC).')) ?></li>
                        <li class="list-group-item bg-transparent px-0"><i class="fas fa-check-circle text-success me-2"></i> <?= escape(get_setting('facility_3', 'Dedicated PDF Digital Library with in-browser reader for members.')) ?></li>
                        <li class="list-group-item bg-transparent px-0"><i class="fas fa-check-circle text-success me-2"></i> <?= escape(get_setting('facility_4', 'Physical book borrowing with barcoded catalog system.')) ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Collections Grid (Requirement 7) -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title text-center"><?= escape(get_setting('home_collections_title', 'Explore Our Collections')) ?></h2>
            <p class="text-muted"><?= escape(get_setting('home_collections_subtitle', 'Browse our curated book categories tailored for academic and literary pursuits.')) ?></p>
        </div>

        <div class="row g-4">
            <?php
            $homeCats = $db->query("SELECT * FROM categories WHERE status = 'Active' ORDER BY id ASC LIMIT 4")->fetchAll();
            $catIcons = [
                1 => ['icon' => 'fas fa-atlas', 'color' => '#7A0C0C'],
                2 => ['icon' => 'fas fa-feather-alt', 'color' => '#f39c12'],
                3 => ['icon' => 'fas fa-graduation-cap', 'color' => '#27ae60'],
                4 => ['icon' => 'fas fa-award', 'color' => '#2980b9'],
            ];
            $defaultIcon = ['icon' => 'fas fa-book-reader', 'color' => '#7A0C0C'];

            foreach ($homeCats as $hCat):
                $iconInfo = $catIcons[$hCat['id']] ?? $defaultIcon;
            ?>
                <div class="col-md-3 col-sm-6">
                    <div class="card h-100 border-0 shadow-sm sayak-card text-center p-4">
                        <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px;">
                            <i class="<?= $iconInfo['icon'] ?> fa-2x" style="color: <?= $iconInfo['color'] ?>;"></i>
                        </div>
                        <h5 class="font-serif fw-bold"><?= escape($hCat['category_name']) ?></h5>
                        <p class="text-muted small"><?= escape($hCat['description'] ?? 'Browse curated library volumes.') ?></p>
                        <a href="<?= BASE_URL ?>collections.php?cat=<?= (int)$hCat['id'] ?>" class="btn btn-outline-maroon btn-sm mt-auto">Browse Books</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php 
$catalogMode = get_setting('catalog_mode', 'database');
if ($catalogMode === 'sheet'): 
?>
<!-- Library Catalog Banner (Google Sheet Mode Active) -->
<section class="py-5" style="background-color: #F8F9FA;">
    <div class="container">
        <div class="card sayak-card border-0 shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);">
            <div class="row align-items-center g-0">
                <div class="col-lg-8 p-4 p-md-5 text-white">
                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-3">
                        <i class="fas fa-file-excel me-1"></i> Interactive Online Catalog
                    </span>
                    <h3 class="font-serif fw-bold text-white mb-2"><?= escape(get_setting('catalog_sheet_title', 'Library Book Collection & Catalog')) ?></h3>
                    <p class="text-white-50 mb-4" style="max-width: 650px;">
                        <?= escape(get_setting('catalog_sheet_notice', 'Our physical library collection is currently being digitized into this portal. In the meantime, please browse our complete book list, titles, and links in the live spreadsheet.')) ?>
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="<?= BASE_URL ?>collections.php" class="btn btn-warning fw-bold px-4 py-2 text-dark">
                            <i class="fas fa-table me-2"></i> Browse Complete Sheet Catalog
                        </a>
                        <?php 
                        $sheetDirectUrl = get_setting('catalog_sheet_url', '');
                        if (!empty($sheetDirectUrl)): 
                        ?>
                            <a href="<?= escape($sheetDirectUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light px-3 py-2">
                                <i class="fas fa-external-link-alt me-1"></i> Open in Google Sheets
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-4 d-none d-lg-flex justify-content-center align-items-center p-4">
                    <div class="text-center text-white-50">
                        <i class="fas fa-book-reader fa-6x text-warning mb-3 opacity-75"></i>
                        <h6 class="text-white fw-bold">Digitization In Progress</h6>
                        <small class="d-block text-white-50">Physical volumes being cataloged daily</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php else: ?>
<!-- Featured Books Showcase -->
<section class="py-5" style="background-color: #F8F9FA;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="section-title mb-1">Featured Books</h2>
                <p class="text-muted small mb-0">Popular physical and digital volumes available for members.</p>
            </div>
            <a href="<?= BASE_URL ?>collections.php" class="btn btn-maroon btn-sm">View Full Catalog</a>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredBooks as $book): ?>
                <div class="col-md-3 col-sm-6">
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
                            <span class="badge bg-light text-dark align-self-start mb-2 border"><?= escape($book['category_name']) ?></span>
                            <h6 class="fw-bold mb-1 font-serif text-dark text-truncate"><?= escape($book['name']) ?></h6>
                            <small class="text-muted mb-2">Author: <?= escape($book['author_name']) ?></small>

                            <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                                <small class="text-success fw-bold"><i class="fas fa-check-circle"></i> <?= $book['available_copies'] ?> Available</small>
                                <a href="<?= BASE_URL ?>book-detail.php?id=<?= $book['id'] ?>" class="btn btn-outline-maroon btn-sm">Details</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Membership Information (Requirement 37) -->
<section class="py-5 bg-white border-top">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title text-center"><?= escape(get_setting('home_membership_title', 'Library Membership Plans')) ?></h2>
            <p class="text-muted"><?= escape(get_setting('home_membership_subtitle', 'Affordable paid subscription plans for students, researchers, and public readers.')) ?></p>
        </div>

        <div class="row g-4 justify-content-center">
            <?php foreach ($plans as $plan): ?>
                <div class="col-md-3 col-sm-6">
                    <div class="card h-100 sayak-card border-top border-4 border-danger text-center p-4">
                        <h5 class="fw-bold font-serif mb-2"><?= escape($plan['plan_name']) ?></h5>
                        <div class="display-6 fw-bold text-maroon my-2" style="color: #7A0C0C;"><?= format_currency($plan['price']) ?></div>
                        <small class="text-muted d-block mb-3">Duration: <?= $plan['duration_months'] ?> Month(s)</small>
                        <hr>
                        <ul class="list-unstyled text-start small mb-4">
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Physical Book Borrowing</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Full Reading Hall Access</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> In-browser PDF Reader</li>
                            <li class="mb-2"><i class="fas fa-check text-success me-2"></i> High-Speed Wi-Fi Access</li>
                        </ul>
                        <a href="<?= BASE_URL ?>usership.php" class="btn btn-maroon btn-sm mt-auto">Apply For Membership</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Donate Callout Section (Requirement 65) -->
<section class="py-5 text-white" style="background: linear-gradient(135deg, #4A0505 0%, #7A0C0C 100%);">
    <div class="container text-center">
        <h2 class="font-serif fw-bold mb-3">Support Knowledge Preservation</h2>
        <p class="lead mb-4 mx-auto" style="max-width: 700px;">Sayak Library welcomes donations of funds, educational books, and rare literary works to expand public learning resources.</p>
        <a href="<?= BASE_URL ?>donate.php" class="btn btn-gold btn-lg fw-bold">
            <i class="fas fa-hand-holding-heart me-2"></i> Make a Donation Now
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
