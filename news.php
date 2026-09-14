<?php
$pageTitle = "News & Updates";
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Fetch active published notices
$stmt = $db->query("
    SELECT * FROM notices 
    WHERE status = 'Published' AND (expiry_date IS NULL OR expiry_date >= CURDATE())
    ORDER BY priority DESC, publish_date DESC
");
$notices = $stmt->fetchAll();
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="section-title text-center">News & Institutional Updates</h1>
        <p class="text-secondary">Official library announcements, notice board bulletins, and event schedules.</p>
    </div>

    <div class="row g-4 justify-content-center">
        <?php if (!empty($notices)): ?>
            <?php foreach ($notices as $notice): ?>
                <div class="col-lg-10">
                    <div class="card sayak-card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <?php if ($notice['priority'] === 'Urgent'): ?>
                                        <span class="badge bg-danger">URGENT</span>
                                    <?php elseif ($notice['priority'] === 'Important'): ?>
                                        <span class="badge bg-warning text-dark">IMPORTANT</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">NOTICE</span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted"><i class="far fa-calendar-alt me-1"></i> Published: <?= format_date($notice['publish_date']) ?></small>
                            </div>

                            <h3 class="font-serif fw-bold text-dark mt-2 mb-3"><?= escape($notice['title']) ?></h3>
                            <p class="text-secondary"><?= escape($notice['short_description']) ?></p>

                            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                                <?php if ($notice['expiry_date']): ?>
                                    <small class="text-muted"><i class="fas fa-hourglass-half me-1"></i> Valid Until: <?= format_date($notice['expiry_date']) ?></small>
                                <?php else: ?>
                                    <small class="text-muted"><i class="fas fa-infinity me-1"></i> Permanent Notice</small>
                                <?php endif; ?>

                                <a href="<?= BASE_URL ?>news-detail.php?id=<?= $notice['id'] ?>" class="btn btn-maroon btn-sm" style="background-color: #7A0C0C;">
                                    Read Full Bulletin <i class="fas fa-chevron-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-lg-8 text-center text-muted py-5">
                <i class="fas fa-bullhorn fa-4x mb-3 text-secondary"></i>
                <h5>No active notices listed on the notice board.</h5>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
