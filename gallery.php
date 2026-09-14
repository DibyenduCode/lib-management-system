<?php
$pageTitle = "Library Gallery";
require_once __DIR__ . '/includes/header.php';

$db = getDB();
$categoryFilter = sanitize_input($_GET['cat'] ?? 'All');

$sql = "SELECT * FROM gallery WHERE is_published = 1";
$params = [];

if ($categoryFilter !== 'All' && !empty($categoryFilter)) {
    $sql .= " AND category = ?";
    $params[] = $categoryFilter;
}

$sql .= " ORDER BY id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$photos = $stmt->fetchAll();

$categories = ['All', 'Facilities', 'Collections', 'Events', 'Programs'];
?>

<div class="container py-5">
    <div class="text-center mb-4">
        <h1 class="section-title text-center">Library Photo Gallery</h1>
        <p class="text-secondary">Visual insights into Sayak Library halls, rare manuscripts, events, and student programs.</p>
        
        <!-- Filter Tabs -->
        <div class="d-flex justify-content-center gap-2 flex-wrap mt-3">
            <?php foreach ($categories as $cat): ?>
                <a href="?cat=<?= urlencode($cat) ?>" class="btn btn-sm <?= $categoryFilter === $cat ? 'btn-maroon' : 'btn-outline-secondary' ?>" style="<?= $categoryFilter === $cat ? 'background-color: #7A0C0C; color: white;' : '' ?>">
                    <?= escape($cat) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="row g-4 mt-2">
        <?php if (!empty($photos)): ?>
            <?php foreach ($photos as $img): ?>
                <div class="col-md-4 col-sm-6">
                    <div class="card h-100 sayak-card border-0 shadow-sm overflow-hidden">
                        <div class="gallery-img-box bg-light text-center d-flex align-items-center justify-content-center" style="height: 220px;">
                            <?php if (!empty($img['image_path']) && file_exists(ROOT_PATH . 'uploads/gallery/' . $img['image_path'])): ?>
                                <img src="<?= BASE_URL ?>uploads/gallery/<?= escape($img['image_path']) ?>" class="img-fluid w-100 h-100" style="object-fit: cover;" alt="<?= escape($img['title']) ?>">
                            <?php else: ?>
                                <div class="text-center p-4">
                                    <i class="fas fa-image fa-4x text-secondary mb-2"></i>
                                    <div class="badge bg-secondary"><?= escape($img['category']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <span class="badge bg-light text-dark border mb-2"><?= escape($img['category']) ?></span>
                            <h5 class="card-title font-serif fw-bold"><?= escape($img['title']) ?></h5>
                            <p class="card-text text-secondary small"><?= escape($img['description']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12 text-center text-muted py-5">
                <i class="fas fa-camera fa-3x mb-3 text-secondary"></i>
                <p>No gallery images uploaded for this category yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
