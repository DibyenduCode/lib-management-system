<?php
$pageTitle = "403 - Access Forbidden";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5 text-center">
    <div class="py-5 bg-white rounded-3 shadow-sm border p-4 p-md-5">
        <i class="fas fa-user-shield fa-5x text-danger mb-3"></i>
        <h1 class="display-4 font-serif fw-bold text-danger">403 - Access Forbidden</h1>
        <p class="lead text-secondary mx-auto" style="max-width: 600px;">You do not have permission to access this administrative endpoint or download this resource.</p>
        <div class="d-flex justify-content-center gap-3 mt-3">
            <a href="<?= BASE_URL ?>" class="btn btn-maroon btn-lg font-serif" style="background-color: #7A0C0C;">
                <i class="fas fa-home me-2"></i> Return Home
            </a>
            <a href="<?= BASE_URL ?>login.php" class="btn btn-outline-secondary btn-lg font-serif">
                <i class="fas fa-sign-in-alt me-2"></i> Switch Account
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
