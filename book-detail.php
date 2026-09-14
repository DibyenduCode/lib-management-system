<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$bookId = (int)($_GET['id'] ?? 0);
$db = getDB();

// Fetch book metadata
$stmt = $db->prepare("
    SELECT b.*, a.name AS author_name, a.bio AS author_bio, p.name AS publisher_name, 
           c.category_name, cl.class_name, s.name AS subject_name,
           (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'Available') AS available_copies,
           (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id) AS total_copies
    FROM books b
    JOIN authors a ON b.author_id = a.id
    JOIN publishers p ON b.publisher_id = p.id
    JOIN categories c ON b.category_id = c.id
    JOIN classes cl ON b.class_id = cl.id
    JOIN subjects s ON b.subject_id = s.id
    WHERE b.id = ?
    LIMIT 1
");
$stmt->execute([$bookId]);
$book = $stmt->fetch();

$currentUser = get_logged_in_user();
$memberId = $_SESSION['member_id'] ?? null;
$hasPendingRequest = false;

if ($memberId) {
    $reqStmt = $db->prepare("SELECT id, status FROM book_requests WHERE member_id = ? AND book_id = ? AND status IN ('Pending', 'Approved')");
    $reqStmt->execute([$memberId, $bookId]);
    $hasPendingRequest = (bool)$reqStmt->fetch();
}

// Handle Hard Copy Request Form Submission (Requirement 30)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'request_hard_copy') {
    if (!is_logged_in() || $_SESSION['role_code'] !== 'MEMBER') {
        set_flash_message('danger', 'Please log in as a Library Member to request physical books.');
        header("Location: " . BASE_URL . "login.php");
        exit();
    }

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash_message('danger', 'Security token invalid.');
        header("Location: " . BASE_URL . "book-detail.php?id=" . $bookId);
        exit();
    }

    // Check membership status (Rule #43)
    if (($_SESSION['membership_status'] ?? '') === 'Restricted') {
        set_flash_message('danger', 'Your account access is restricted due to expired membership (>15 days). Please renew membership at the library desk.');
        header("Location: " . BASE_URL . "member/dashboard.php");
        exit();
    }

    if (!$book || $book['available_copies'] <= 0) {
        set_flash_message('warning', 'Sorry, no physical copies are currently available for borrowing.');
        header("Location: " . BASE_URL . "book-detail.php?id=" . $bookId);
        exit();
    }

    if ($hasPendingRequest) {
        set_flash_message('info', 'You already have an active request for this book.');
        header("Location: " . BASE_URL . "book-detail.php?id=" . $bookId);
        exit();
    }

    // Insert Book Request
    $requestCode = generate_request_code($db);
    $insStmt = $db->prepare("
        INSERT INTO book_requests (request_code, member_id, book_id, request_date, status)
        VALUES (?, ?, ?, NOW(), 'Pending')
    ");
    $insStmt->execute([$requestCode, $memberId, $bookId]);

    // Send notifications to Librarians
    notify_role('LIBRARIAN', 'New Book Request', "Member requested physical copy of book: {$book['name']} ({$requestCode})");
    log_audit_action($_SESSION['user_id'], 'MEMBER', 'Hard Copy Request', 'BookRequests', "Request ID: {$requestCode}");

    set_flash_message('success', 'Physical book request submitted! Please visit the library desk after approval.');
    header("Location: " . BASE_URL . "member/requests.php");
    exit();
}

$pageTitle = $book ? $book['name'] . " - Book Details" : "Book Details";
require_once __DIR__ . '/includes/header.php';

if (!$book) {
    echo '<div class="container py-5"><div class="alert alert-danger text-center">Book not found. <a href="' . BASE_URL . 'collections.php">Return to collections</a></div></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit();
}
?>

<div class="container py-5">
    <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border">
        <div class="row g-4">
            <!-- Book Cover -->
            <div class="col-md-4 text-center">
                <div class="p-3 bg-light rounded border mb-3">
                    <?php if (!empty($book['cover_image']) && file_exists(ROOT_PATH . 'uploads/covers/' . $book['cover_image'])): ?>
                        <img src="<?= BASE_URL ?>uploads/covers/<?= escape($book['cover_image']) ?>" class="img-fluid rounded shadow-sm" style="max-height: 380px;" alt="<?= escape($book['name']) ?>">
                    <?php else: ?>
                        <div class="py-5 text-secondary">
                            <i class="fas fa-book fa-6x mb-3 text-maroon" style="color: #7A0C0C;"></i>
                            <h5><?= escape($book['book_code']) ?></h5>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Availability Badge -->
                <div class="card p-3 mb-3 border-0 bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Physical Copies:</span>
                        <span class="fw-bold <?= $book['available_copies'] > 0 ? 'text-success' : 'text-danger' ?>">
                            <?= $book['available_copies'] ?> / <?= $book['total_copies'] ?> Available
                        </span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="text-muted small">Digital PDF:</span>
                        <span class="fw-bold <?= !empty($book['pdf_file']) ? 'text-primary' : 'text-secondary' ?>">
                            <?= !empty($book['pdf_file']) ? 'Available Online' : 'Not Available' ?>
                        </span>
                    </div>
                </div>

                <!-- Action Buttons (Requirement 29) -->
                <div class="d-grid gap-2">
                    <?php if (!empty($book['pdf_file'])): ?>
                        <a href="<?= BASE_URL ?>pdf_viewer.php?id=<?= $book['id'] ?>" class="btn btn-maroon btn-lg font-serif" style="background-color: #7A0C0C;">
                            <i class="fas fa-file-pdf me-2"></i> Read PDF Online
                        </a>
                    <?php endif; ?>

                    <?php if ($book['available_copies'] > 0): ?>
                        <?php if ($currentUser && $currentUser['role_code'] === 'MEMBER'): ?>
                            <?php if ($hasPendingRequest): ?>
                                <button class="btn btn-secondary btn-lg" disabled><i class="fas fa-clock me-2"></i> Request Pending</button>
                            <?php else: ?>
                                <form action="" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    <input type="hidden" name="action" value="request_hard_copy">
                                    <button type="submit" class="btn btn-gold btn-lg w-100 font-serif text-dark fw-bold">
                                        <i class="fas fa-book-reader me-2"></i> Request Hard Copy
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php elseif (!$currentUser): ?>
                            <a href="<?= BASE_URL ?>login.php" class="btn btn-outline-maroon btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i> Login to Request Book
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Book Metadata -->
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-maroon" style="background-color: #7A0C0C;"><?= escape($book['book_code']) ?></span>
                    <span class="badge bg-secondary"><?= escape($book['category_name']) ?></span>
                    <span class="badge bg-info text-dark"><?= escape($book['class_name']) ?></span>
                </div>

                <h2 class="font-serif fw-bold text-dark mb-2"><?= escape($book['name']) ?></h2>
                <h5 class="text-maroon mb-3" style="color: #7A0C0C;"><i class="fas fa-pen-nib me-1"></i> Author: <?= escape($book['author_name']) ?></h5>

                <div class="row g-3 py-3 border-top border-bottom my-3">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Publisher:</small>
                        <strong><?= escape($book['publisher_name']) ?></strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Subject:</small>
                        <strong><?= escape($book['subject_name']) ?></strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">ISBN Number:</small>
                        <strong><?= escape($book['isbn'] ?: 'N/A') ?></strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Edition & Year:</small>
                        <strong><?= escape($book['edition']) ?> (<?= $book['pub_year'] ?>)</strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Language:</small>
                        <strong><?= escape($book['language']) ?></strong>
                    </div>
                </div>

                <h5 class="font-serif fw-bold mt-4">Book Description</h5>
                <p class="text-secondary"><?= nl2br(escape($book['description'])) ?></p>

                <?php if (!empty($book['author_bio'])): ?>
                    <div class="p-3 bg-light rounded mt-4 border-start border-4 border-maroon">
                        <h6 class="fw-bold mb-1"><i class="fas fa-info-circle text-maroon me-1"></i> About Author</h6>
                        <small class="text-muted"><?= escape($book['author_bio']) ?></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
