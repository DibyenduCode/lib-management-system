<?php
$pageTitle = "Governing Body - About Us";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card sayak-card">
                <div class="card-header bg-maroon text-white fw-bold" style="background-color: #7A0C0C;">
                    About Us
                </div>
                <ul class="list-group list-group-flush">
                    <a href="<?= BASE_URL ?>our-journey.php" class="list-group-item list-group-item-action">Our Journey</a>
                    <a href="<?= BASE_URL ?>governing-body.php" class="list-group-item list-group-item-action active fw-bold" style="background-color: #7A0C0C; border-color: #7A0C0C;">Governing Body</a>
                    <a href="<?= BASE_URL ?>governance.php" class="list-group-item list-group-item-action">Governance</a>
                    <a href="<?= BASE_URL ?>academic-collaborations.php" class="list-group-item list-group-item-action">Academic Collaborations</a>
                </ul>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border">
                <h1 class="section-title">Governing Body</h1>
                <p class="text-secondary">Distinguished scholars, educational leaders, and library administrators overseeing institutional policy.</p>
                <hr class="my-4">

<?php
$db = getDB();
$members = $db->query("SELECT * FROM governing_body_members WHERE status = 'Active' ORDER BY sort_order ASC, id ASC")->fetchAll();
?>
                <div class="row g-4">
                    <?php if (!empty($members)): ?>
                        <?php foreach ($members as $m): ?>
                            <div class="col-md-6">
                                <div class="card h-100 border p-3 sayak-card">
                                    <div class="d-flex align-items-center">
                                        <?php if (!empty($m['photo']) && file_exists(ROOT_PATH . 'uploads/governing_body/' . $m['photo'])): ?>
                                            <img src="<?= BASE_URL ?>uploads/governing_body/<?= escape($m['photo']) ?>" alt="<?= escape($m['name']) ?>" class="rounded-circle border border-2 border-maroon shadow-sm me-3 flex-shrink-0" style="width: 65px; height: 65px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3 flex-shrink-0 border" style="width:65px; height:65px;">
                                                <i class="fas <?= escape($m['icon'] ?: 'fa-user-tie') ?> fa-2x text-maroon" style="color: #7A0C0C;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <h5 class="fw-bold mb-0 font-serif text-dark"><?= escape($m['name']) ?></h5>
                                            <small class="text-maroon fw-bold d-block" style="color: #7A0C0C;"><?= escape($m['designation']) ?></small>
                                            <?php if (!empty($m['description'])): ?>
                                                <p class="small text-muted mb-0 mt-1"><?= nl2br(escape($m['description'])) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-light text-center py-4 border text-muted">
                                Governing body details are currently being updated.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
