<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';

require_role('SUPER_ADMIN');

$db = getDB();

// High Level Metrics
$totalMembers = (int)$db->query("SELECT COUNT(*) FROM members")->fetchColumn();
$activeMembers = (int)$db->query("SELECT COUNT(*) FROM members WHERE membership_status = 'Active'")->fetchColumn();
$restrictedMembers = (int)$db->query("SELECT COUNT(*) FROM members WHERE membership_status = 'Restricted'")->fetchColumn();

$totalSuperAdmins = (int)$db->query("SELECT COUNT(*) FROM users WHERE role_id = 1")->fetchColumn();
$totalLibrarians = (int)$db->query("SELECT COUNT(*) FROM librarians")->fetchColumn();
$totalBooks = (int)$db->query("SELECT COUNT(*) FROM books")->fetchColumn();
$totalCopies = (int)$db->query("SELECT COUNT(*) FROM book_copies")->fetchColumn();
$availableCopies = (int)$db->query("SELECT COUNT(*) FROM book_copies WHERE status = 'Available'")->fetchColumn();
$pdfBooksCount = (int)$db->query("SELECT COUNT(*) FROM books WHERE pdf_file IS NOT NULL AND pdf_file != ''")->fetchColumn();

$publishedNotices = (int)$db->query("SELECT COUNT(*) FROM notices WHERE status = 'Published'")->fetchColumn();
$newDonations = (int)$db->query("SELECT COUNT(*) FROM donations WHERE status = 'New'")->fetchColumn();
$unreadInquiries = (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'New'")->fetchColumn();

$totalMembershipRevenue = (float)$db->query("SELECT SUM(amount) FROM membership_payments")->fetchColumn();
$totalFineRevenue = (float)$db->query("SELECT SUM(amount_paid) FROM fine_payments")->fetchColumn();
$totalGrandRevenue = $totalMembershipRevenue + $totalFineRevenue;

// Recent Audit Trail
$auditStmt = $db->query("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 6");
$recentAudit = $auditStmt->fetchAll();

$pageTitle = "Super Admin Executive Dashboard";
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
            <!-- Header Section -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="section-title mb-1">System Executive Dashboard</h2>
                    <p class="text-muted small mb-0">Super Admin system overview, configuration, financial metrics, and audit logs.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= BASE_URL ?>admin/users/index.php" class="btn btn-maroon btn-sm" style="background-color: #8B1E26;">
                        <i class="fas fa-users-cog me-1"></i> Staff & Admin Manager
                    </a>
                    <a href="<?= BASE_URL ?>admin/circulation/index.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-barcode me-1"></i> Physical Copies & Holders
                    </a>
                    <a href="<?= BASE_URL ?>admin/cms/index.php" class="btn btn-gold btn-sm text-dark font-serif fw-bold">
                        <i class="fas fa-edit me-1"></i> Website Content (CMS)
                    </a>
                    <a href="<?= BASE_URL ?>admin/settings/index.php" class="btn btn-outline-dark btn-sm">
                        <i class="fas fa-cog me-1"></i> System Settings
                    </a>
                    <a href="<?= BASE_URL ?>admin/notices/index.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-bullhorn me-1"></i> Manage Notices
                    </a>
                </div>
            </div>

            <!-- Revenue & System KPIs -->
            <div class="row g-3 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="metric-card border-start border-4 border-success py-3">
                        <div class="metric-value text-success"><?= format_currency($totalGrandRevenue) ?></div>
                        <div class="metric-label"><i class="fas fa-rupee-sign me-1"></i> Total Cash Collections</div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="metric-card border-start border-4 border-maroon py-3">
                        <div class="metric-value text-maroon" style="color:#7A0C0C;"><?= $totalMembers ?></div>
                        <div class="metric-label"><i class="fas fa-users me-1"></i> Total Members (<?= $restrictedMembers ?> Restricted)</div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <a href="<?= BASE_URL ?>admin/circulation/index.php" class="text-decoration-none">
                        <div class="metric-card border-start border-4 border-primary py-3">
                            <div class="metric-value text-primary"><?= $totalBooks ?> / <?= $totalCopies ?></div>
                            <div class="metric-label"><i class="fas fa-barcode me-1"></i> Titles / Copies & Holders</div>
                        </div>
                    </a>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="metric-card border-start border-4 border-warning py-3">
                        <div class="metric-value text-warning"><?= $newDonations ?> New</div>
                        <div class="metric-label"><i class="fas fa-hand-holding-heart me-1"></i> Donation Submissions</div>
                    </div>
                </div>
            </div>

            <!-- Admin Modules Grid -->
            <div class="row g-4 mb-4">
                <!-- Notice Management -->
                <div class="col-md-4">
                    <div class="card sayak-card h-100 p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width:50px; height:50px;">
                                <i class="fas fa-bullhorn fa-2x text-maroon" style="color: #7A0C0C;"></i>
                            </div>
                            <div>
                                <h5 class="font-serif fw-bold mb-0">Notice Management</h5>
                                <small class="text-muted">Super Admin Exclusive Access</small>
                            </div>
                        </div>
                        <p class="text-secondary small">Create, publish, edit, unpublish, or delete public notice board bulletins.</p>
                        <a href="<?= BASE_URL ?>admin/notices/index.php" class="btn btn-maroon btn-sm mt-auto" style="background-color: #7A0C0C;">Manage Notices (<?= $publishedNotices ?> Active)</a>
                    </div>
                </div>

                <!-- Book & Catalog System -->
                <div class="col-md-4">
                    <div class="card sayak-card h-100 p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width:50px; height:50px;">
                                <i class="fas fa-book-open fa-2x text-primary"></i>
                            </div>
                            <div>
                                <h5 class="font-serif fw-bold mb-0">Master Book Manager</h5>
                                <small class="text-muted">Catalog, Copies & PDFs</small>
                            </div>
                        </div>
                        <p class="text-secondary small">Manage Books, Authors, Publishers, Subjects, Classes, Categories, Copies & PDF files.</p>
                        <a href="<?= BASE_URL ?>admin/books/index.php" class="btn btn-outline-maroon btn-sm mt-auto">Open Master Book Manager</a>
                    </div>
                </div>

                <!-- System Settings & Fine Rates -->
                <div class="col-md-4">
                    <div class="card sayak-card h-100 p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width:50px; height:50px;">
                                <i class="fas fa-sliders-h fa-2x text-warning"></i>
                            </div>
                            <div>
                                <h5 class="font-serif fw-bold mb-0">System Settings</h5>
                                <small class="text-muted">Fine & Grace Settings</small>
                            </div>
                        </div>
                        <p class="text-secondary small">Configure Library Info, Fine rate per day, Grace period, App URL, and SMTP details.</p>
                        <a href="<?= BASE_URL ?>admin/settings/index.php" class="btn btn-outline-dark btn-sm mt-auto">System Settings</a>
                    </div>
                </div>

                <!-- Donations -->
                <div class="col-md-4">
                    <div class="card sayak-card h-100 p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width:50px; height:50px;">
                                <i class="fas fa-hand-holding-heart fa-2x text-success"></i>
                            </div>
                            <div>
                                <h5 class="font-serif fw-bold mb-0">Donations System</h5>
                                <small class="text-muted">Pledges & Items</small>
                            </div>
                        </div>
                        <p class="text-secondary small">View money/book/item donations submitted by public donors and update status.</p>
                        <a href="<?= BASE_URL ?>admin/donations/index.php" class="btn btn-outline-success btn-sm mt-auto">Manage Donations</a>
                    </div>
                </div>

                <!-- Membership Plans -->
                <div class="col-md-4">
                    <div class="card sayak-card h-100 p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width:50px; height:50px;">
                                <i class="fas fa-tags fa-2x text-purple" style="color:#6f42c1;"></i>
                            </div>
                            <div>
                                <h5 class="font-serif fw-bold mb-0">Membership Plans</h5>
                                <small class="text-muted">Pricing & Durations</small>
                            </div>
                        </div>
                        <p class="text-secondary small">Create or update membership subscription plans, duration months, and cash pricing.</p>
                        <a href="<?= BASE_URL ?>admin/memberships/index.php" class="btn btn-outline-secondary btn-sm mt-auto">Manage Plans</a>
                    </div>
                </div>

                <!-- Staff & Admin Accounts -->
                <div class="col-md-4">
                    <div class="card sayak-card h-100 p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width:50px; height:50px;">
                                <i class="fas fa-users-cog fa-2x text-maroon" style="color:#8B1E26;"></i>
                            </div>
                            <div>
                                <h5 class="font-serif fw-bold mb-0">Staff & Admin Accounts</h5>
                                <small class="text-muted"><?= $totalLibrarians ?> Librarians &bull; <?= $totalSuperAdmins ?> Admins</small>
                            </div>
                        </div>
                        <p class="text-secondary small">Create and manage multiple Librarian and Super Admin accounts, set credentials, and control statuses.</p>
                        <a href="<?= BASE_URL ?>admin/users/index.php" class="btn btn-maroon btn-sm mt-auto" style="background-color: #8B1E26;">Manage Accounts</a>
                    </div>
                </div>

                <!-- Audit Logs -->
                <div class="col-md-4">
                    <div class="card sayak-card h-100 p-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3" style="width:50px; height:50px;">
                                <i class="fas fa-shield-alt fa-2x text-danger"></i>
                            </div>
                            <div>
                                <h5 class="font-serif fw-bold mb-0">System Audit Trail</h5>
                                <small class="text-muted">Security & Action Logs</small>
                            </div>
                        </div>
                        <p class="text-secondary small">Track login attempts, book edits, PDF downloads, fine collections, and user status changes.</p>
                        <a href="<?= BASE_URL ?>admin/audit/index.php" class="btn btn-outline-danger btn-sm mt-auto">View Audit Logs</a>
                    </div>
                </div>
            </div>

            <!-- Audit Log Preview Table -->
            <div class="card sayak-card">
                <div class="card-header bg-white font-serif fw-bold py-3 d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-history text-maroon me-2" style="color: #7A0C0C;"></i> Recent System Activity Audit Log</span>
                    <a href="<?= BASE_URL ?>admin/audit/index.php" class="btn btn-outline-secondary btn-sm">Full Log</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Time</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                    <th>Target</th>
                                    <th>IP Address</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentAudit as $log): ?>
                                    <tr>
                                        <td><small><?= format_date($log['created_at'], 'd M Y, h:i A') ?></small></td>
                                        <td><span class="badge bg-secondary"><?= escape($log['role']) ?></span></td>
                                        <td><strong class="text-dark"><?= escape($log['action']) ?></strong></td>
                                        <td><small><?= escape($log['target']) ?></small></td>
                                        <td><code><?= escape($log['ip_address']) ?></code></td>
                                        <td><small class="text-muted"><?= escape($log['details']) ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
