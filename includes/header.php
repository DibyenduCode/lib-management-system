<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$currentUser = get_logged_in_user();

// Fetch latest active urgent notice for top ticker
$db = getDB();
$tickerStmt = $db->query("
    SELECT id, title FROM notices 
    WHERE status = 'Published' AND (expiry_date IS NULL OR expiry_date >= CURDATE()) 
    ORDER BY priority DESC, publish_date DESC LIMIT 1
");
$tickerNotice = $tickerStmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? escape($pageTitle) . ' - ' : '' ?><?= escape(get_setting('library_name', 'DAKSHINESWAR SHAYAK LIBRARY')) ?></title>
    <meta name="description" content="DAKSHINESWAR SHAYAK LIBRARY - Premier educational and public digital library system offering vast textbook collections, e-learning resources, and membership programs.">
    
    <!-- Favicon & Touch Icons -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>assets/images/favicon.png">
    <link rel="shortcut icon" href="<?= BASE_URL ?>favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>assets/images/favicon.png">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
<?php
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isDashboard = (
    strpos($scriptPath, '/admin/') !== false ||
    strpos($scriptPath, '/librarian/') !== false ||
    strpos($scriptPath, '/member/') !== false ||
    (!empty($isDashboard))
);

$dashboardHomeUrl = BASE_URL;
if ($currentUser) {
    if ($currentUser['role_code'] === 'SUPER_ADMIN') {
        $dashboardHomeUrl = BASE_URL . 'admin/dashboard.php';
    } elseif ($currentUser['role_code'] === 'LIBRARIAN') {
        $dashboardHomeUrl = BASE_URL . 'librarian/dashboard.php';
    } else {
        $dashboardHomeUrl = BASE_URL . 'member/dashboard.php';
    }
}
?>
</head>
<body class="<?= $isDashboard ? 'dashboard-layout bg-light d-flex flex-column min-vh-100' : '' ?>">

<?php if ($isDashboard): ?>
    <!-- ==================================================== -->
    <!-- SLIM, STREAMLINED DASHBOARD / PORTAL HEADER          -->
    <!-- (No public announcement, big header, or 6-menu bar)  -->
    <!-- ==================================================== -->
    <?php 
    $logoVal = get_setting('site_logo', '');
    if (!empty($logoVal) && file_exists(ROOT_PATH . 'uploads/' . $logoVal)) {
        $logoUrl = BASE_URL . 'uploads/' . $logoVal;
    } else {
        $logoUrl = BASE_URL . 'assets/images/site_logo.png';
    }
    $roleName = $currentUser['role_name'] ?? 'Dashboard';
    $roleCode = $currentUser['role_code'] ?? '';
    ?>
    <header class="navbar navbar-expand navbar-light bg-white sticky-top shadow-sm px-3 d-print-none dashboard-navbar border-bottom" style="z-index: 1040; min-height: 58px;">
        <div class="container-fluid d-flex align-items-center justify-content-between">
            <!-- Left: Brand Title & Portal Role -->
            <div class="d-flex align-items-center">
                <a href="<?= $dashboardHomeUrl ?>" class="navbar-brand d-flex align-items-center me-3 py-0 text-dark">
                    <img src="<?= $logoUrl ?>" alt="Sayak Logo" class="rounded-circle bg-white p-1 me-2 border shadow-sm" style="width: 44px; height: 44px; object-fit: cover;">
                    <span class="font-serif fw-bold text-dark fs-5 tracking-wide" style="color: #0F172A !important;"><?= escape(get_setting('library_name', 'DAKSHINESWAR SHAYAK LIBRARY')) ?></span>
                </a>
                <?php
                $roleBadgeClass = 'bg-primary-subtle text-primary border-primary-subtle';
                $roleIcon = 'fa-user';
                if ($roleCode === 'SUPER_ADMIN') {
                    $roleBadgeClass = 'bg-danger-subtle text-danger border-danger-subtle';
                    $roleIcon = 'fa-shield-alt';
                } elseif ($roleCode === 'LIBRARIAN') {
                    $roleBadgeClass = 'bg-primary-subtle text-primary border-primary-subtle';
                    $roleIcon = 'fa-book-reader';
                } else {
                    $roleBadgeClass = 'bg-success-subtle text-success border-success-subtle';
                    $roleIcon = 'fa-user-check';
                }
                ?>
                <span class="badge border <?= $roleBadgeClass ?> font-serif px-2 py-1 align-middle d-none d-sm-inline-block" style="font-size: 11px; font-weight: 600;">
                    <i class="fas <?= $roleIcon ?> me-1"></i>
                    <?= escape($roleName) ?>
                </span>
            </div>

            <!-- Right: Public Website link, Alerts & User Menu -->
            <div class="d-flex align-items-center gap-2">
                <a href="<?= BASE_URL ?>" target="_blank" class="btn btn-sm btn-outline-secondary font-serif d-none d-md-inline-flex align-items-center rounded-3" title="Open Public Website in new tab">
                    <i class="fas fa-external-link-alt me-1 text-muted"></i> Public Website
                </a>

                <?php if ($roleCode === 'LIBRARIAN' || $roleCode === 'SUPER_ADMIN'): ?>
                    <?php $criticalAlertCount = get_expired_unreturned_alerts_count(); ?>
                    <?php if ($criticalAlertCount > 0): ?>
                        <a href="<?= BASE_URL ?>librarian/alerts/index.php" class="btn btn-sm btn-outline-danger position-relative me-1 rounded-3" title="<?= $criticalAlertCount ?> Critical Alerts Pending">
                            <i class="fas fa-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px;">
                                <?= $criticalAlertCount ?>
                            </span>
                        </a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($currentUser): ?>
                    <div class="dropdown">
                        <button class="btn btn-sm bg-light border d-flex align-items-center gap-2 py-1 px-2 rounded-3 dropdown-toggle text-dark" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 12px; background-color: #8B1E26;">
                                <?= strtoupper(substr($currentUser['full_name'] ?? 'U', 0, 1)) ?>
                            </div>
                            <span class="small fw-semibold d-none d-sm-inline-block text-truncate text-dark" style="max-width: 140px;">
                                <?= escape($currentUser['full_name']) ?>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2">
                            <li class="px-3 py-2 border-bottom bg-light">
                                <strong class="d-block text-dark font-serif"><?= escape($currentUser['full_name']) ?></strong>
                                <small class="text-muted d-block"><?= escape($currentUser['email'] ?? '') ?></small>
                                <span class="badge bg-secondary font-monospace mt-1"><?= escape($currentUser['role_name'] ?? '') ?></span>
                            </li>
                            <li><a class="dropdown-item py-2" href="<?= $dashboardHomeUrl ?>"><i class="fas fa-tachometer-alt me-2 text-primary"></i> Dashboard Home</a></li>
                            <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>profile.php"><i class="fas fa-user-cog me-2 text-maroon" style="color: #8B1E26;"></i> My Profile & Password</a></li>
                            <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>" target="_blank"><i class="fas fa-external-link-alt me-2 text-muted"></i> Public Website</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>logout.php"><i class="fas fa-sign-out-alt me-2"></i> Log Out</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

<?php else: ?>

    <!-- ==================================================== -->
    <!-- PUBLIC WEBSITE HEADER (Announcement, Logo, 6 Menus)  -->
    <!-- ==================================================== -->
    <!-- Top Announcement Bar -->
    <div class="top-notice-bar d-none d-md-block">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <span class="badge bg-danger me-2">ANNOUNCEMENT</span>
                <?php if ($tickerNotice): ?>
                    <marquee class="mb-0" scrollamount="4">
                        <a href="<?= BASE_URL ?>news-detail.php?id=<?= $tickerNotice['id'] ?>">
                            <?= escape($tickerNotice['title']) ?>
                        </a>
                    </marquee>
                <?php else: ?>
                    <span>Welcome to Sayak Library. Physical and digital collections open for all readers.</span>
                <?php endif; ?>
            </div>
            <div class="top-contacts text-nowrap">
                <small class="me-3"><i class="fas fa-phone me-1"></i> <?= escape(get_setting('phone', '+91 33 2241 8900')) ?></small>
                <small><i class="fas fa-clock me-1"></i> <?= escape(get_setting('opening_hours', '9 AM - 7 PM')) ?></small>
            </div>
        </div>
    </div>

    <!-- Main Institutional Header -->
    <header class="main-header">
        <div class="container d-flex justify-content-between align-items-center">
            <?php 
            $logoVal = get_setting('site_logo', '');
            if (!empty($logoVal) && file_exists(ROOT_PATH . 'uploads/' . $logoVal)) {
                $logoUrl = BASE_URL . 'uploads/' . $logoVal;
            } else {
                $logoUrl = BASE_URL . 'assets/images/site_logo.png';
            }
            ?>
            <a href="<?= BASE_URL ?>" class="d-flex align-items-center text-decoration-none">
                <img src="<?= $logoUrl ?>" alt="Sayak Library Logo" class="brand-logo-img me-3">
                <div>
                    <h1 class="brand-title"><?= escape(get_setting('library_name', 'DAKSHINESWAR SHAYAK LIBRARY')) ?></h1>
                    <div class="brand-subtitle">
                        Estd: <?= escape(get_setting('established_year', '1996')) ?> | Reg No: <?= escape(get_setting('registration_no', 'S/87920 of 1997-1998')) ?>
                    </div>
                </div>
            </a>

            <div class="header-action-buttons d-none d-lg-flex align-items-center gap-2">
                <a href="<?= BASE_URL ?>news.php" class="btn btn-outline-maroon btn-sm">
                    <i class="fas fa-bullhorn me-1"></i> News & Updates
                </a>

                <a href="<?= BASE_URL ?>donate.php" class="btn btn-gold btn-sm">
                    <i class="fas fa-hand-holding-heart me-1"></i> Donate Now
                </a>

                <?php if ($currentUser): ?>
                    <div class="dropdown position-relative" style="z-index: 1050;">
                        <button class="btn btn-maroon btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i> <?= escape($currentUser['full_name']) ?> (<?= escape($currentUser['role_name']) ?>)
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow" style="z-index: 1080 !important;">
                            <?php if ($currentUser['role_code'] === 'SUPER_ADMIN'): ?>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>admin/dashboard.php"><i class="fas fa-tachometer-alt me-2 text-danger"></i> Admin Dashboard</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>admin/cms/index.php"><i class="fas fa-edit me-2 text-warning"></i> Website CMS</a></li>
                            <?php elseif ($currentUser['role_code'] === 'LIBRARIAN'): ?>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>librarian/dashboard.php"><i class="fas fa-tachometer-alt me-2 text-primary"></i> Librarian Dashboard</a></li>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>admin/books/index.php"><i class="fas fa-book-open me-2 text-maroon" style="color: #7A0C0C;"></i> Master Book Manager (Catalog, Barcodes & PDF)</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item py-2" href="<?= BASE_URL ?>member/dashboard.php"><i class="fas fa-user me-2 text-success"></i> My Member Portal</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item py-2 text-danger" href="<?= BASE_URL ?>logout.php"><i class="fas fa-sign-out-alt me-2"></i> Log Out</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>login.php" class="btn btn-maroon btn-sm">
                        <i class="fas fa-sign-in-alt me-1"></i> Log in
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Navigation Bar (6 Menus Exactly) -->
    <nav class="navbar navbar-expand-lg navbar-dark main-navbar sticky-top">
        <div class="container">
            <button class="navbar-toggler py-2 my-1" type="button" data-bs-toggle="collapse" data-bs-target="#sayakNav" aria-controls="sayakNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span> Menu
            </button>

            <div class="collapse navbar-collapse" id="sayakNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <!-- 1. Home -->
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['SCRIPT_NAME']) == 'index.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>">Home</a>
                    </li>

                    <!-- 2. About Us -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">About Us</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>our-journey.php">Our Journey</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>governing-body.php">Governing Body</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>governance.php">Governance</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>academic-collaborations.php">Academic Collaborations</a></li>
                        </ul>
                    </li>

                    <!-- 3. Join the Library -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Join the Library</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>usership.php">Usership Form</a></li>
                            <li><a class="dropdown-item" href="<?= BASE_URL ?>rules.php">User Rules & Regulations</a></li>
                        </ul>
                    </li>

                    <!-- 4. Collections (Dynamic from Database) -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= (basename($_SERVER['SCRIPT_NAME']) == 'collections.php') ? 'active' : '' ?>" href="#" role="button" data-bs-toggle="dropdown">Collections</a>
                        <ul class="dropdown-menu shadow-sm">
                            <?php
                            $navCats = $db->query("SELECT id, category_name FROM categories WHERE status = 'Active' ORDER BY id ASC")->fetchAll();
                            if (!empty($navCats)):
                                foreach ($navCats as $nc):
                            ?>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>collections.php?cat=<?= (int)$nc['id'] ?>"><?= escape($nc['category_name']) ?></a></li>
                            <?php 
                                endforeach;
                            ?>
                                <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item fw-bold text-maroon" href="<?= BASE_URL ?>collections.php" style="color: #7A0C0C;"><i class="fas fa-layer-group me-1"></i> View All Collections</a></li>
                        </ul>
                    </li>

                    <!-- 5. Get in Touch -->
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['SCRIPT_NAME']) == 'contact.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>contact.php">Get in Touch</a>
                    </li>

                    <!-- 6. Gallery -->
                    <li class="nav-item">
                        <a class="nav-link <?= (basename($_SERVER['SCRIPT_NAME']) == 'gallery.php') ? 'active' : '' ?>" href="<?= BASE_URL ?>gallery.php">Gallery</a>
                    </li>
                </ul>

                <!-- Mobile Quick Buttons -->
                <div class="d-flex d-lg-none my-2 gap-2 flex-wrap">
                    <a href="<?= BASE_URL ?>news.php" class="btn btn-light btn-sm text-dark">News</a>
                    <a href="<?= BASE_URL ?>donate.php" class="btn btn-gold btn-sm">Donate</a>
                    <?php if ($currentUser): ?>
                        <a href="<?= $dashboardHomeUrl ?>" class="btn btn-warning btn-sm text-dark fw-bold"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                        <a href="<?= BASE_URL ?>logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>login.php" class="btn btn-light btn-sm">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
<?php endif; ?>

<div class="main-content-wrapper <?= $isDashboard ? 'flex-grow-1' : '' ?>">
    <div class="container my-2">
        <?php display_flash_messages(); ?>
    </div>
