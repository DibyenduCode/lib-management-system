<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/permissions.php';
require_once __DIR__ . '/../includes/notifications.php';

require_role('MEMBER');

$userId = $_SESSION['user_id'];
$notifications = get_user_notifications($userId, 30);

// Mark all as read
$db = getDB();
$db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);

$pageTitle = "Notifications";
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
                    <h2 class="section-title mb-1">My Dashboard Notifications</h2>
                    <p class="text-muted small mb-0">System alerts, book request updates, and due date reminders.</p>
                </div>
            </div>

            <div class="card sayak-card">
                <div class="card-body p-0">
                    <?php if (!empty($notifications)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $n): ?>
                                <div class="list-group-item p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="fw-bold mb-0 font-serif text-maroon" style="color: #7A0C0C;"><?= escape($n['title']) ?></h6>
                                        <small class="text-muted"><i class="far fa-clock me-1"></i> <?= format_date($n['created_at'], 'd M Y, h:i A') ?></small>
                                    </div>
                                    <p class="text-secondary small mb-0"><?= escape($n['message']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-bell-slash fa-3x mb-3 text-secondary"></i>
                            <p>No notifications at this time.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
