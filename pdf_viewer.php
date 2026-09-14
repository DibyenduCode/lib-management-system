<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/permissions.php';

if (!is_logged_in()) {
    set_flash_message('danger', 'Please log in to access the Digital PDF Library.');
    header("Location: " . BASE_URL . "login.php");
    exit();
}

$user = get_logged_in_user();
$bookId = (int)($_GET['id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("
    SELECT b.*, a.name AS author_name, c.category_name 
    FROM books b 
    JOIN authors a ON b.author_id = a.id 
    JOIN categories c ON b.category_id = c.id 
    WHERE b.id = ? LIMIT 1
");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

$canDownload = can_download_pdf();
$streamUrl = $book ? BASE_URL . "pdf_stream.php?id=" . $book['id'] : '#';
$downloadUrl = $book ? BASE_URL . "pdf_download.php?id=" . $book['id'] : '#';

$pageTitle = $book ? "PDF Reader - " . $book['name'] : "Digital PDF Reader";
require_once __DIR__ . '/includes/header.php';

if (!$book || empty($book['pdf_file'])) {
    echo '<div class="container py-5"><div class="alert alert-danger text-center">PDF file for this book is unavailable. <a href="' . BASE_URL . 'collections.php">Return to catalog</a></div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit();
}
?>

<div class="container-fluid py-3 bg-dark text-white min-vh-100 d-flex flex-column">
    <!-- Reader Top Controls Bar -->
    <div class="d-flex justify-content-between align-items-center bg-secondary p-3 rounded mb-3">
        <div class="d-flex align-items-center gap-3">
            <a href="<?= BASE_URL ?>book-detail.php?id=<?= $book['id'] ?>" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Exit Reader
            </a>
            <div>
                <h5 class="mb-0 text-white font-serif text-truncate" style="max-width: 400px;"><?= escape($book['name']) ?></h5>
                <small class="text-light">By <?= escape($book['author_name']) ?> | <?= escape($book['category_name']) ?></small>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <?php if ($canDownload): ?>
                <a href="<?= $downloadUrl ?>" class="btn btn-gold btn-sm fw-bold">
                    <i class="fas fa-download me-1"></i> Download PDF
                </a>
            <?php else: ?>
                <span class="badge bg-danger py-2 px-3"><i class="fas fa-lock me-1"></i> Download Restricted for Members</span>
            <?php endif; ?>

            <button onclick="toggleFullScreen()" class="btn btn-outline-light btn-sm" title="Toggle Fullscreen">
                <i class="fas fa-expand"></i>
            </button>
        </div>
    </div>

    <!-- Main PDF Stream Frame -->
    <div class="flex-grow-1 bg-black rounded overflow-hidden shadow-lg border border-secondary position-relative">
        <iframe id="pdfFrame" src="<?= $streamUrl ?>#toolbar=0&navpanes=0" class="w-100 h-100" style="min-height: 750px; border:none;"></iframe>
    </div>
</div>

<script>
function toggleFullScreen() {
    var elem = document.getElementById("pdfFrame");
    if (!document.fullscreenElement) {
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.webkitRequestFullscreen) {
            elem.webkitRequestFullscreen();
        }
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
}

// Right-click protection for Members
<?php if (!$canDownload): ?>
document.addEventListener('contextmenu', function(e) {
    e.preventDefault();
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
