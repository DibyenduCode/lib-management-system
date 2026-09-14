<?php
$pageTitle = "500 - Internal Server Error";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5 text-center">
    <div class="py-5 bg-white rounded-3 shadow-sm border p-4 p-md-5">
        <i class="fas fa-server fa-5x text-secondary mb-3"></i>
        <h1 class="display-4 font-serif fw-bold text-dark">500 - Server Error</h1>
        <p class="lead text-secondary mx-auto" style="max-width: 600px;">An internal application error occurred. The incident has been logged for system administrator review.</p>
        <a href="<?= BASE_URL ?>" class="btn btn-maroon btn-lg font-serif mt-3" style="background-color: #7A0C0C;">
            <i class="fas fa-home me-2"></i> Return to Homepage
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
