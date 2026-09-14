<?php
$pageTitle = "Notice Detail";
require_once __DIR__ . '/includes/header.php';

$noticeId = (int)($_GET['id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("
    SELECT n.*, u.full_name AS author_name 
    FROM notices n 
    JOIN users u ON n.created_by = u.id 
    WHERE n.id = ? AND n.status = 'Published' AND (n.expiry_date IS NULL OR n.expiry_date >= CURDATE())
    LIMIT 1
");
$stmt->execute([$noticeId]);
$notice = $stmt->fetch();

if (!$notice) {
    echo '<div class="container py-5"><div class="alert alert-warning text-center">Notice not found or expired. <a href="' . BASE_URL . 'news.php">Back to News</a></div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit();
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border">
                <a href="<?= BASE_URL ?>news.php" class="btn btn-outline-secondary btn-sm mb-4">
                    <i class="fas fa-arrow-left me-1"></i> Back to All Notices
                </a>

                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-maroon" style="background-color: #7A0C0C;"><?= escape($notice['priority']) ?></span>
                    <small class="text-muted"><i class="far fa-calendar-alt me-1"></i> Published: <?= format_date($notice['publish_date']) ?></small>
                </div>

                <h1 class="font-serif fw-bold text-dark mb-3"><?= escape($notice['title']) ?></h1>

                <div class="p-3 bg-light rounded border-start border-4 border-warning mb-4">
                    <p class="lead mb-0 text-dark" style="font-size: 1.05rem;"><?= escape($notice['short_description']) ?></p>
                </div>

                <?php if (!empty($notice['image']) && file_exists(ROOT_PATH . 'uploads/covers/' . $notice['image'])): ?>
                    <div class="text-center mb-4">
                        <img src="<?= BASE_URL ?>uploads/covers/<?= escape($notice['image']) ?>" class="img-fluid rounded shadow-sm" style="max-height: 400px;" alt="<?= escape($notice['title']) ?>">
                    </div>
                <?php endif; ?>

                <div class="article-body text-secondary lh-lg mb-5">
                    <?= nl2br(escape($notice['full_description'])) ?>
                </div>

                <div class="p-3 border-top d-flex justify-content-between text-muted small">
                    <span>Issued By: <strong><?= escape($notice['author_name']) ?></strong> (Sayak Library Administration)</span>
                    <span>Valid Until: <strong><?= $notice['expiry_date'] ? format_date($notice['expiry_date']) : 'Indefinite' ?></strong></span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
