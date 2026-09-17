</div><!-- /.main-content-wrapper -->

<?php
$scriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isDashboard = (
    strpos($scriptPath, '/admin/') !== false ||
    strpos($scriptPath, '/librarian/') !== false ||
    strpos($scriptPath, '/member/') !== false ||
    (!empty($isDashboard))
);
?>

<?php if ($isDashboard): ?>
    <!-- Minimal Clean Portal Footer -->
    <footer class="portal-footer py-3 text-center text-muted small border-top bg-white mt-auto d-print-none">
        <div class="container-fluid d-flex flex-wrap justify-content-between align-items-center px-4">
            <span>&copy; <?= date('Y') ?> <strong><?= escape(get_setting('library_name', 'SAYAK LIBRARY')) ?></strong> &bull; Library Management Portal</span>
            <div>
                <a href="<?= BASE_URL ?>" target="_blank" class="text-muted me-3 text-decoration-none"><i class="fas fa-globe me-1"></i> Public Website</a>
                <a href="<?= BASE_URL ?>logout.php" class="text-danger text-decoration-none"><i class="fas fa-sign-out-alt me-1"></i> Log Out</a>
            </div>
        </div>
    </footer>
<?php else: ?>
    <!-- Full Public Institutional Website Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-book-reader fa-2x text-warning me-2"></i>
                        <h5 class="mb-0 text-white font-serif"><?= escape(get_setting('library_name', 'SAYAK LIBRARY')) ?></h5>
                    </div>
                    <p class="text-secondary small">
                        <?= escape(get_setting('footer_about', 'Dedicated to promoting knowledge, academic research, and literary heritage. Providing comprehensive physical lending services and digital PDF resources to readers and students.')) ?>
                    </p>
                    <div class="social-links d-flex gap-2">
                        <?php if (!empty(get_setting('social_facebook', '#')) && get_setting('social_facebook', '#') !== '#'): ?>
                            <a href="<?= escape(get_setting('social_facebook', '#')) ?>" target="_blank" class="btn btn-outline-light btn-sm rounded-circle" style="width:36px; height:36px;" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <?php endif; ?>
                        <?php if (!empty(get_setting('social_twitter', '#')) && get_setting('social_twitter', '#') !== '#'): ?>
                            <a href="<?= escape(get_setting('social_twitter', '#')) ?>" target="_blank" class="btn btn-outline-light btn-sm rounded-circle" style="width:36px; height:36px;" title="Twitter / X"><i class="fab fa-twitter"></i></a>
                        <?php endif; ?>
                        <?php if (!empty(get_setting('social_instagram', '#')) && get_setting('social_instagram', '#') !== '#'): ?>
                            <a href="<?= escape(get_setting('social_instagram', '#')) ?>" target="_blank" class="btn btn-outline-light btn-sm rounded-circle" style="width:36px; height:36px;" title="Instagram"><i class="fab fa-instagram"></i></a>
                        <?php endif; ?>
                        <?php if (!empty(get_setting('social_youtube'))): ?>
                            <a href="<?= escape(get_setting('social_youtube')) ?>" target="_blank" class="btn btn-outline-light btn-sm rounded-circle" style="width:36px; height:36px;" title="YouTube"><i class="fab fa-youtube"></i></a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-2 col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="<?= BASE_URL ?>our-journey.php">Our Journey</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>usership.php">Join Library</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>rules.php">Rules & Regulations</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>news.php">News & Updates</a></li>
                        <li class="mb-2"><a href="<?= BASE_URL ?>donate.php">Donate Books / Funds</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-4">
                    <h5>Collections</h5>
                    <ul class="list-unstyled">
                        <?php
                        $footerDb = getDB();
                        $footerCats = $footerDb->query("SELECT id, category_name FROM categories WHERE status = 'Active' ORDER BY id ASC LIMIT 5")->fetchAll();
                        foreach ($footerCats as $fc):
                        ?>
                            <li class="mb-2"><a href="<?= BASE_URL ?>collections.php?cat=<?= (int)$fc['id'] ?>"><?= escape($fc['category_name']) ?></a></li>
                        <?php endforeach; ?>
                        <li class="mb-2"><a href="<?= BASE_URL ?>collections.php" class="text-warning small"><i class="fas fa-arrow-right me-1"></i> View All Collections</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-4">
                    <h5>Contact Info</h5>
                    <p class="text-secondary small mb-2"><i class="fas fa-map-marker-alt text-warning me-2"></i> <?= escape(get_setting('address', '11, Nepal Chandra Chatterjee Street, Ariadaha, Kolkata - 700057')) ?></p>
                    <p class="text-secondary small mb-2"><i class="fas fa-phone text-warning me-2"></i> <?= escape(get_setting('phone', '7595929232, 8420011218')) ?></p>
                    <p class="text-secondary small mb-2"><i class="fas fa-envelope text-warning me-2"></i> <?= escape(get_setting('email', 'dakshineswarshayak1997@gmail.com')) ?></p>
                    <p class="text-secondary small mb-0"><i class="fas fa-clock text-warning me-2"></i> <?= escape(get_setting('opening_hours', 'Monday - Saturday: 9:00 AM - 7:00 PM | Sunday: Closed')) ?></p>
                </div>
            </div>

            <div class="footer-bottom text-center">
                <div class="row align-items-center">
                    <div class="col-md-6 text-md-start mb-2 mb-md-0">
                        <p class="mb-0 text-secondary small">&copy; <?= date('Y') ?> <strong><?= escape(get_setting('library_name', 'DAKSHINESWAR SHAYAK LIBRARY')) ?></strong>. All Rights Reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small><a href="<?= BASE_URL ?>rules.php" class="text-secondary">Library Terms</a></small>
                    </div>
                </div>
            </div>
        </div>
    </footer>
<?php endif; ?>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
