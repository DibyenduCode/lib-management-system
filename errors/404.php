<?php
$pageTitle = "404 - Page Not Found";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5 text-center">
    <div class="py-5 bg-white rounded-3 shadow-sm border p-4 p-md-5">
        <i class="fas fa-exclamation-triangle fa-5x text-warning mb-3"></i>
        <h1 class="display-4 font-serif fw-bold text-maroon" style="color: #7A0C0C;">404 - Page Not Found</h1>
        <p class="lead text-secondary mx-auto" style="max-width: 600px;">The requested URL or resource could not be located on the Sayak Library system.</p>
        <a href="<?= BASE_URL ?>" class="btn btn-maroon btn-lg font-serif mt-3" style="background-color: #7A0C0C;">
            <i class="fas fa-home me-2"></i> Return to Homepage
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
