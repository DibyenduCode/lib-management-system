<?php
/**
 * SAYAK LIBRARY - Unified Portal Sidebar Component
 */
require_once __DIR__ . '/auth.php';
$currentUser = get_logged_in_user();

if (!$currentUser) return;

$currentScript = basename($_SERVER['SCRIPT_NAME']);
$currentDir = basename(dirname($_SERVER['SCRIPT_NAME']));
$role = $currentUser['role_code'];
?>

<div class="card sayak-card p-3 shadow-sm border-0 sticky-top mb-3 mb-lg-0" style="top: 70px; z-index: 1000;">
    <!-- User Profile Header & Mobile Toggle -->
    <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
        <a href="<?= BASE_URL ?>profile.php" class="d-flex align-items-center me-2 overflow-hidden text-decoration-none" title="Manage Profile & Change Password">
            <div class="rounded-circle text-white d-flex align-items-center justify-content-center me-2 shadow-sm flex-shrink-0" style="width:40px; height:40px; background-color:#8B1E26;">
                <i class="fas <?= $role === 'SUPER_ADMIN' ? 'fa-user-shield' : ($role === 'LIBRARIAN' ? 'fa-user-cog' : 'fa-user') ?>"></i>
            </div>
            <div class="overflow-hidden">
                <h6 class="fw-bold font-serif mb-0 text-dark text-truncate" style="max-width: 140px;"><?= escape($currentUser['full_name']) ?></h6>
                <span class="badge bg-gold text-dark font-serif" style="font-size:10px; font-weight:700;"><?= escape($currentUser['role_name']) ?></span>
            </div>
        </a>
        <button class="btn btn-sm btn-outline-secondary d-lg-none font-serif" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarPortalMenu" aria-expanded="false" aria-controls="sidebarPortalMenu">
            <i class="fas fa-bars me-1"></i> Menu
        </button>
    </div>

    <!-- Navigation Links List (Collapsible on Mobile, Expanded on Desktop) -->
    <div class="collapse d-lg-block" id="sidebarPortalMenu">
        <div class="nav flex-column nav-pills portal-sidebar-links gap-1">
            <?php if ($role === 'SUPER_ADMIN'): ?>
                <small class="text-muted fw-bold text-uppercase px-2 mb-1" style="font-size:10px; letter-spacing:0.5px;">Executive Control</small>
                
                <a href="<?= BASE_URL ?>admin/dashboard.php" class="nav-link <?= ($currentScript == 'dashboard.php' && $currentDir == 'admin') ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard Overview
                </a>

                <a href="<?= BASE_URL ?>admin/users/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'users') ? 'active' : '' ?>">
                    <i class="fas fa-users-cog me-2"></i> Staff & Admin Manager
                </a>

                <a href="<?= BASE_URL ?>admin/books/index.php" class="nav-link <?= ($currentScript == 'index.php' && ($currentDir == 'books' || $currentDir == 'pdf' || $currentDir == 'copies')) ? 'active' : '' ?>">
                    <i class="fas fa-book-open me-2"></i> Master Book Manager
                </a>

                <a href="<?= BASE_URL ?>admin/circulation/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'circulation') ? 'active' : '' ?>">
                    <i class="fas fa-barcode me-2"></i> Physical Copies & Holders
                </a>

                <a href="<?= BASE_URL ?>admin/cms/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'cms') ? 'active' : '' ?>">
                    <i class="fas fa-edit me-2"></i> Website Content (CMS)
                </a>

                <a href="<?= BASE_URL ?>admin/notices/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'notices') ? 'active' : '' ?>">
                    <i class="fas fa-bullhorn me-2"></i> Notice Board
                </a>

                <a href="<?= BASE_URL ?>admin/gallery/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'gallery') ? 'active' : '' ?>">
                    <i class="fas fa-images me-2"></i> Photo Gallery
                </a>

                <a href="<?= BASE_URL ?>admin/donations/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'donations') ? 'active' : '' ?>">
                    <i class="fas fa-hand-holding-heart me-2"></i> Donations Records
                </a>

                <a href="<?= BASE_URL ?>admin/memberships/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'memberships') ? 'active' : '' ?>">
                    <i class="fas fa-tags me-2"></i> Membership Plans
                </a>

                <a href="<?= BASE_URL ?>admin/settings/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'settings') ? 'active' : '' ?>">
                    <i class="fas fa-cog me-2"></i> System Settings
                </a>

                <a href="<?= BASE_URL ?>admin/audit/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'audit') ? 'active' : '' ?>">
                    <i class="fas fa-shield-alt me-2"></i> System Audit Logs
                </a>

                <?php $criticalAlertCount = get_expired_unreturned_alerts_count(); ?>
                <a href="<?= BASE_URL ?>librarian/alerts/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'alerts') ? 'active' : '' ?> d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-exclamation-triangle text-danger me-2"></i> Critical Alerts</span>
                    <?php if ($criticalAlertCount > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $criticalAlertCount ?></span>
                    <?php endif; ?>
                </a>

            <?php elseif ($role === 'LIBRARIAN'): ?>
                <?php $criticalAlertCount = get_expired_unreturned_alerts_count(); ?>
                <small class="text-muted fw-bold text-uppercase px-2 mb-1" style="font-size:10px; letter-spacing:0.5px;">Lending Operations</small>
                
                <a href="<?= BASE_URL ?>librarian/dashboard.php" class="nav-link <?= ($currentScript == 'dashboard.php' && $currentDir == 'librarian') ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> Operations Center
                </a>

                <a href="<?= BASE_URL ?>librarian/alerts/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'alerts') ? 'active' : '' ?> d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-exclamation-triangle text-danger me-2"></i> Critical Alerts</span>
                    <?php if ($criticalAlertCount > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= $criticalAlertCount ?></span>
                    <?php endif; ?>
                </a>

                <a href="<?= BASE_URL ?>admin/books/index.php" class="nav-link <?= ($currentScript == 'index.php' && ($currentDir == 'books' || $currentDir == 'pdf' || $currentDir == 'copies')) ? 'active' : '' ?>">
                    <i class="fas fa-book-open me-2"></i> Master Book Manager
                </a>

                <a href="<?= BASE_URL ?>librarian/circulation/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'circulation') ? 'active' : '' ?>">
                    <i class="fas fa-barcode me-2"></i> Physical Copies & Holders
                </a>

                <a href="<?= BASE_URL ?>librarian/issue/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'issue') ? 'active' : '' ?>">
                    <i class="fas fa-book-reader me-2"></i> Issue Physical Book
                </a>

                <a href="<?= BASE_URL ?>librarian/return/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'return') ? 'active' : '' ?>">
                    <i class="fas fa-undo me-2"></i> Process Book Return
                </a>

                <a href="<?= BASE_URL ?>librarian/requests/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'requests') ? 'active' : '' ?>">
                    <i class="fas fa-inbox me-2"></i> Pending Requests
                </a>

                <small class="text-muted fw-bold text-uppercase px-2 mt-2 mb-1" style="font-size:10px; letter-spacing:0.5px;">Members & Fines</small>

                <a href="<?= BASE_URL ?>librarian/members/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'members') ? 'active' : '' ?>">
                    <i class="fas fa-users me-2"></i> Manage Members
                </a>

                <a href="<?= BASE_URL ?>librarian/fines/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'fines') ? 'active' : '' ?>">
                    <i class="fas fa-rupee-sign me-2"></i> Fine Collection
                </a>

                <a href="<?= BASE_URL ?>librarian/memberships/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'memberships') ? 'active' : '' ?>">
                    <i class="fas fa-redo me-2"></i> Renew Subscriptions
                </a>

                <a href="<?= BASE_URL ?>librarian/reports/index.php" class="nav-link <?= ($currentScript == 'index.php' && $currentDir == 'reports') ? 'active' : '' ?>">
                    <i class="fas fa-chart-line me-2"></i> Cash & Audit Reports
                </a>

            <?php else: ?>
                <small class="text-muted fw-bold text-uppercase px-2 mb-1" style="font-size:10px; letter-spacing:0.5px;">Member Portal</small>
                
                <a href="<?= BASE_URL ?>member/dashboard.php" class="nav-link <?= ($currentScript == 'dashboard.php' && $currentDir == 'member') ? 'active' : '' ?>">
                    <i class="fas fa-home me-2"></i> Portal Overview
                </a>

                <a href="<?= BASE_URL ?>member/my-books.php" class="nav-link <?= ($currentScript == 'my-books.php') ? 'active' : '' ?>">
                    <i class="fas fa-book me-2"></i> My Borrowed Books
                </a>

                <a href="<?= BASE_URL ?>member/requests.php" class="nav-link <?= ($currentScript == 'requests.php') ? 'active' : '' ?>">
                    <i class="fas fa-clock me-2"></i> My Requests
                </a>

                <a href="<?= BASE_URL ?>member/membership.php" class="nav-link <?= ($currentScript == 'membership.php') ? 'active' : '' ?>">
                    <i class="fas fa-id-card me-2"></i> My Membership
                </a>

                <a href="<?= BASE_URL ?>member/notifications.php" class="nav-link <?= ($currentScript == 'notifications.php') ? 'active' : '' ?>">
                    <i class="fas fa-bell me-2"></i> Notifications
                </a>
            <?php endif; ?>

            <hr class="my-2">
            <a href="<?= BASE_URL ?>profile.php" class="nav-link <?= ($currentScript == 'profile.php') ? 'active' : '' ?>">
                <i class="fas fa-user-cog me-2"></i> My Profile & Password
            </a>
            <a href="<?= BASE_URL ?>logout.php" class="nav-link text-danger fw-bold">
                <i class="fas fa-sign-out-alt me-2"></i> Log Out
            </a>
        </div>
    </div>
</div>
