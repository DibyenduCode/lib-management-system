<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

require_role(['SUPER_ADMIN', 'LIBRARIAN']);

$db = getDB();
$errors = [];

// ============================================================
// 1. POST ACTION HANDLERS (UNIFIED MASTER MANAGER)
// Quick Add Dynamic Lookup Handler (Author, Publisher, Category, Class, Subject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_add_type'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Security token mismatch.']);
        exit();
    }

    $type = sanitize_input($_POST['quick_add_type'] ?? '');
    $name = sanitize_input($_POST['quick_add_name'] ?? '');

    if (empty($name)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Name is required.']);
        exit();
    }

    $newId = 0;
    $typeLabel = '';

    if ($type === 'author') {
        $stmt = $db->prepare("INSERT INTO authors (name, status, created_at) VALUES (?, 'Active', NOW())");
        $stmt->execute([$name]);
        $newId = $db->lastInsertId();
        $typeLabel = 'Author';
    } elseif ($type === 'publisher') {
        $stmt = $db->prepare("INSERT INTO publishers (name, status, created_at) VALUES (?, 'Active', NOW())");
        $stmt->execute([$name]);
        $newId = $db->lastInsertId();
        $typeLabel = 'Publisher';
    } elseif ($type === 'category') {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        if (empty($slug)) $slug = 'cat-' . time();
        $baseSlug = $slug;
        $counter = 1;
        while (true) {
            $chk = $db->prepare("SELECT COUNT(*) FROM categories WHERE slug = ?");
            $chk->execute([$slug]);
            if ((int)$chk->fetchColumn() === 0) break;
            $slug = $baseSlug . '-' . (++$counter);
        }
        $stmt = $db->prepare("INSERT INTO categories (category_name, slug, status, created_at) VALUES (?, ?, 'Active', NOW())");
        $stmt->execute([$name, $slug]);
        $newId = $db->lastInsertId();
        $typeLabel = 'Category / Collection';
    } elseif ($type === 'class') {
        $stmt = $db->prepare("INSERT INTO classes (class_name, status, created_at) VALUES (?, 'Active', NOW())");
        $stmt->execute([$name]);
        $newId = $db->lastInsertId();
        $typeLabel = 'Class';
    } elseif ($type === 'subject') {
        $stmt = $db->prepare("INSERT INTO subjects (name, status, created_at) VALUES (?, 'Active', NOW())");
        $stmt->execute([$name]);
        $newId = $db->lastInsertId();
        $typeLabel = 'Subject';
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Invalid lookup type.']);
        exit();
    }

    log_audit_action($_SESSION['user_id'], $_SESSION['role_code'], "Add {$typeLabel}", ucfirst($type), "Name: {$name}");

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'id' => $newId,
        'name' => $name,
        'type' => $type,
        'message' => "{$typeLabel} '{$name}' created and auto-selected!"
    ]);
    exit();
}

// A. Save / Edit Book Catalog Metadata (Unified Form)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_book'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token mismatch.";
    } else {
        $bookId = (int)($_POST['book_id'] ?? 0);
        $name = sanitize_input($_POST['name'] ?? '');
        $authorId = (int)($_POST['author_id'] ?? 0);
        $publisherId = (int)($_POST['publisher_id'] ?? 0);
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $classId = (int)($_POST['class_id'] ?? 0);
        $subjectId = (int)($_POST['subject_id'] ?? 0);
        $isbn = sanitize_input($_POST['isbn'] ?? '');
        $edition = sanitize_input($_POST['edition'] ?? '1st Edition');
        $language = sanitize_input($_POST['language'] ?? 'Bengali');
        $pubYear = (int)($_POST['pub_year'] ?? 2023);
        $desc = sanitize_input($_POST['description'] ?? '');

        $barcode = sanitize_input($_POST['barcode'] ?? '');
        $shelf = sanitize_input($_POST['shelf'] ?? 'Rack-A');
        $rack = sanitize_input($_POST['rack'] ?? 'Shelf-1');

        if (empty($name)) $errors[] = "Book Title is required.";

        if (empty($errors)) {
            // Handle Optional Cover Image Upload
            $newCoverFilename = null;
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $cTmp = $_FILES['cover_image']['tmp_name'];
                $cOrig = basename($_FILES['cover_image']['name']);
                $cExt = strtolower(pathinfo($cOrig, PATHINFO_EXTENSION));
                $allowedImgExts = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($cExt, $allowedImgExts)) {
                    $imgCheck = @getimagesize($cTmp);
                    if ($imgCheck !== false) {
                        $coversDir = ROOT_PATH . 'uploads/covers/';
                        if (!is_dir($coversDir)) {
                            mkdir($coversDir, 0755, true);
                        }
                        $candidateCover = 'cover_' . time() . '_' . rand(1000, 9999) . '.' . $cExt;
                        if (move_uploaded_file($cTmp, $coversDir . $candidateCover)) {
                            $newCoverFilename = $candidateCover;
                        }
                    }
                }
            }

            if ($bookId > 0) {
                // If PDF file provided during edit, upload and update pdf_file
                if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['pdf_file']['tmp_name'];
                    $origName = basename($_FILES['pdf_file']['name']);
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

                    if ($ext === 'pdf') {
                        $pdfFilename = 'book_pdf_' . $bookId . '_' . time() . '.pdf';
                        if (!is_dir(PRIVATE_PDF_DIR)) {
                            mkdir(PRIVATE_PDF_DIR, 0755, true);
                        }
                        if (move_uploaded_file($tmpName, PRIVATE_PDF_DIR . $pdfFilename)) {
                            $db->prepare("UPDATE books SET pdf_file = ? WHERE id = ?")->execute([$pdfFilename, $bookId]);
                        }
                    }
                }

                // Handle Cover Image Update / Removal during edit
                $removeCover = isset($_POST['remove_cover']) && $_POST['remove_cover'] == '1';
                if ($newCoverFilename) {
                    $oldCStmt = $db->prepare("SELECT cover_image FROM books WHERE id = ?");
                    $oldCStmt->execute([$bookId]);
                    $oldCover = $oldCStmt->fetchColumn();
                    if (!empty($oldCover) && file_exists(ROOT_PATH . 'uploads/covers/' . $oldCover)) {
                        @unlink(ROOT_PATH . 'uploads/covers/' . $oldCover);
                    }
                    $db->prepare("UPDATE books SET cover_image = ? WHERE id = ?")->execute([$newCoverFilename, $bookId]);
                } elseif ($removeCover) {
                    $oldCStmt = $db->prepare("SELECT cover_image FROM books WHERE id = ?");
                    $oldCStmt->execute([$bookId]);
                    $oldCover = $oldCStmt->fetchColumn();
                    if (!empty($oldCover) && file_exists(ROOT_PATH . 'uploads/covers/' . $oldCover)) {
                        @unlink(ROOT_PATH . 'uploads/covers/' . $oldCover);
                    }
                    $db->prepare("UPDATE books SET cover_image = NULL WHERE id = ?")->execute([$bookId]);
                }

                $stmt = $db->prepare("
                    UPDATE books 
                    SET name = ?, author_id = ?, publisher_id = ?, category_id = ?, class_id = ?, subject_id = ?, isbn = ?, edition = ?, language = ?, pub_year = ?, description = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $authorId, $publisherId, $categoryId, $classId, $subjectId, $isbn, $edition, $language, $pubYear, $desc, $bookId]);
                log_audit_action($_SESSION['user_id'], $_SESSION['role_code'], 'Edit Book Catalog', 'Books', "Book ID: {$bookId}");
                set_flash_message('success', 'Book catalog metadata updated successfully.');
            } else {
                $bookCode = generate_book_code($db);

                // Handle PDF File Upload if attached
                $pdfFilename = null;
                if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                    $tmpName = $_FILES['pdf_file']['tmp_name'];
                    $origName = basename($_FILES['pdf_file']['name']);
                    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

                    if ($ext === 'pdf') {
                        $pdfFilename = 'book_pdf_' . time() . '_' . rand(1000, 9999) . '.pdf';
                        if (!is_dir(PRIVATE_PDF_DIR)) {
                            mkdir(PRIVATE_PDF_DIR, 0755, true);
                        }
                        move_uploaded_file($tmpName, PRIVATE_PDF_DIR . $pdfFilename);
                    }
                }

                $stmt = $db->prepare("
                    INSERT INTO books (book_code, name, author_id, publisher_id, category_id, class_id, subject_id, isbn, edition, language, pub_year, description, pdf_file, cover_image, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$bookCode, $name, $authorId, $publisherId, $categoryId, $classId, $subjectId, $isbn, $edition, $language, $pubYear, $desc, $pdfFilename, $newCoverFilename]);
                $newBookId = $db->lastInsertId();

                // Create initial physical copy barcode
                if (empty($barcode)) {
                    $barcode = 'BAR-' . str_replace('SL-BK-', 'BK', $bookCode) . '-001';
                }
                $copyCode = generate_copy_code($db);
                $db->prepare("INSERT INTO book_copies (book_id, copy_code, barcode, shelf, rack, status) VALUES (?, ?, ?, ?, ?, 'Available')")->execute([$newBookId, $copyCode, $barcode, $shelf, $rack]);

                log_audit_action($_SESSION['user_id'], $_SESSION['role_code'], 'Add Book Catalog', 'Books', "Book Code: {$bookCode}");
                set_flash_message('success', "New book '{$name}' saved with initial barcode {$barcode}" . ($newCoverFilename ? " and cover image." : "."));
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
    }
}

// B. Delete Book Title
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_book'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token mismatch.";
    } else {
        $delBookId = (int)$_POST['book_id'];
        
        $issCheck = $db->prepare("SELECT COUNT(*) FROM book_issues WHERE book_id = ? AND status IN ('Issued', 'Overdue')");
        $issCheck->execute([$delBookId]);
        $activeIssues = (int)$issCheck->fetchColumn();

        if ($activeIssues > 0) {
            set_flash_message('danger', 'Cannot delete this book title because physical copies are currently issued to members.');
        } else {
            try {
                // Fetch associated file names for cleanup
                $oldFileStmt = $db->prepare("SELECT pdf_file, cover_image FROM books WHERE id = ?");
                $oldFileStmt->execute([$delBookId]);
                $oldBookFiles = $oldFileStmt->fetch();

                $db->beginTransaction();
                $db->prepare("DELETE FROM book_requests WHERE book_id = ?")->execute([$delBookId]);
                $db->prepare("DELETE FROM book_copies WHERE book_id = ?")->execute([$delBookId]);
                $db->prepare("DELETE FROM books WHERE id = ?")->execute([$delBookId]);
                $db->commit();

                // Clean up files after successful database deletion
                if ($oldBookFiles) {
                    if (!empty($oldBookFiles['pdf_file']) && file_exists(PRIVATE_PDF_DIR . $oldBookFiles['pdf_file'])) {
                        @unlink(PRIVATE_PDF_DIR . $oldBookFiles['pdf_file']);
                    }
                    if (!empty($oldBookFiles['cover_image']) && file_exists(ROOT_PATH . 'uploads/covers/' . $oldBookFiles['cover_image'])) {
                        @unlink(ROOT_PATH . 'uploads/covers/' . $oldBookFiles['cover_image']);
                    }
                }

                log_audit_action($_SESSION['user_id'], $_SESSION['role_code'], 'Delete Book', 'Books', "Book ID: {$delBookId}");
                set_flash_message('info', 'Book title, copies, and associated files deleted cleanly.');
            } catch (Exception $e) {
                $db->rollBack();
                set_flash_message('danger', 'Error deleting book: ' . $e->getMessage());
            }
        }
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Helper functions for copy management
function get_copies_for_book($db, $bookId) {
    $stmt = $db->prepare("SELECT bc.*, b.name AS book_name, b.book_code FROM book_copies bc JOIN books b ON bc.book_id = b.id WHERE bc.book_id = ? ORDER BY bc.id DESC");
    $stmt->execute([$bookId]);
    return $stmt->fetchAll();
}

function get_next_barcode_suggestion($db, $bookId) {
    $bStmt = $db->prepare("SELECT book_code FROM books WHERE id = ?");
    $bStmt->execute([$bookId]);
    $bCode = $bStmt->fetchColumn() ?: 'BK001';
    
    $cStmt = $db->prepare("SELECT COUNT(*) FROM book_copies WHERE book_id = ?");
    $cStmt->execute([$bookId]);
    $nextNum = ((int)$cStmt->fetchColumn()) + 1;
    $numStr = $nextNum < 10 ? '00' . $nextNum : ($nextNum < 100 ? '0' . $nextNum : (string)$nextNum);
    return 'BAR-' . str_replace('SL-BK-', 'BK', $bCode) . '-' . $numStr;
}

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || isset($_POST['is_ajax']);

// C. Add Physical Copy Barcode for a Book (Supports Continuous Multiple Additions)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_copy'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $msg = "Security token mismatch.";
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $msg]);
            exit();
        }
        $errors[] = $msg;
    } else {
        $bookId = (int)$_POST['book_id'];
        $barcode = sanitize_input($_POST['barcode'] ?? '');
        $shelf = sanitize_input($_POST['shelf'] ?? 'Rack-A');
        $rack = sanitize_input($_POST['rack'] ?? 'Shelf-1');
        $status = sanitize_input($_POST['status'] ?? 'Available');

        if ($bookId <= 0) $errors[] = "Invalid Book selection.";
        if (empty($barcode)) $errors[] = "Barcode is required.";

        $bChk = $db->prepare("SELECT id FROM book_copies WHERE barcode = ?");
        $bChk->execute([$barcode]);
        if ($bChk->fetchColumn()) {
            $errors[] = "Barcode '{$barcode}' already exists in database.";
        }

        if (empty($errors)) {
            $copyCode = generate_copy_code($db);
            $insStmt = $db->prepare("
                INSERT INTO book_copies (book_id, copy_code, barcode, shelf, rack, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $insStmt->execute([$bookId, $copyCode, $barcode, $shelf, $rack, $status]);

            log_audit_action($_SESSION['user_id'], $_SESSION['role_code'], 'Add Book Copy', 'BookCopies', "Book ID: {$bookId}, Barcode: {$barcode}");
            $successMsg = "Copy barcode {$barcode} added successfully!";

            if ($isAjax) {
                $copies = get_copies_for_book($db, $bookId);
                $nextBarcode = get_next_barcode_suggestion($db, $bookId);
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $successMsg,
                    'copies' => $copies,
                    'next_barcode' => $nextBarcode
                ]);
                exit();
            }

            set_flash_message('success', $successMsg);
            header("Location: " . $_SERVER['PHP_SELF'] . "?open_copy_book_id=" . $bookId);
            exit();
        } else {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
                exit();
            }
        }
    }
}

// D. Update Physical Copy Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_copy_status'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $copyId = (int)$_POST['copy_id'];
        $bookId = (int)($_POST['book_id'] ?? 0);
        $newStatus = sanitize_input($_POST['status']);
        $db->prepare("UPDATE book_copies SET status = ? WHERE id = ?")->execute([$newStatus, $copyId]);
        log_audit_action($_SESSION['user_id'], $_SESSION['role_code'], 'Update Copy Status', 'BookCopies', "Copy ID: {$copyId}, Status: {$newStatus}");

        if ($isAjax && $bookId > 0) {
            $copies = get_copies_for_book($db, $bookId);
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'copies' => $copies]);
            exit();
        }

        set_flash_message('info', 'Physical copy status updated to: ' . $newStatus);
        header("Location: " . $_SERVER['PHP_SELF'] . ($bookId ? "?open_copy_book_id=" . $bookId : ""));
        exit();
    }
}

// E. Delete Physical Copy
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_copy'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $copyId = (int)$_POST['copy_id'];
        $bookId = (int)($_POST['book_id'] ?? 0);
        
        $chkIss = $db->prepare("SELECT status FROM book_copies WHERE id = ?");
        $chkIss->execute([$copyId]);
        $status = $chkIss->fetchColumn();

        if ($status === 'Issued') {
            $errMsg = 'Cannot delete physical copy while it is currently issued to a member.';
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => $errMsg]);
                exit();
            }
            set_flash_message('danger', $errMsg);
        } else {
            $db->prepare("DELETE FROM book_copies WHERE id = ?")->execute([$copyId]);
            log_audit_action($_SESSION['user_id'], $_SESSION['role_code'], 'Delete Copy', 'BookCopies', "Copy ID: {$copyId}");
            
            if ($isAjax && $bookId > 0) {
                $copies = get_copies_for_book($db, $bookId);
                $nextBarcode = get_next_barcode_suggestion($db, $bookId);
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'copies' => $copies, 'next_barcode' => $nextBarcode]);
                exit();
            }

            set_flash_message('info', 'Physical copy barcode removed from inventory.');
        }
        header("Location: " . $_SERVER['PHP_SELF'] . ($bookId ? "?open_copy_book_id=" . $bookId : ""));
        exit();
    }
}

// F. Upload PDF Document for a Book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_pdf'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token mismatch.";
    } else {
        $bookId = (int)$_POST['book_id'];

        if ($bookId <= 0) $errors[] = "Please select a valid Book.";

        if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['pdf_file']['tmp_name'];
            $origName = basename($_FILES['pdf_file']['name']);
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

            if ($ext !== 'pdf') {
                $errors[] = "File must be a valid .PDF document.";
            } else {
                $pdfFilename = 'book_pdf_' . $bookId . '_' . time() . '.pdf';
                if (!is_dir(PRIVATE_PDF_DIR)) {
                    mkdir(PRIVATE_PDF_DIR, 0755, true);
                }

                if (move_uploaded_file($tmpName, PRIVATE_PDF_DIR . $pdfFilename)) {
                    $db->prepare("UPDATE books SET pdf_file = ? WHERE id = ?")->execute([$pdfFilename, $bookId]);
                    log_audit_action($_SESSION['user_id'], $_SESSION['role_code'], 'Upload PDF', 'Books', "Book ID: {$bookId}, File: {$pdfFilename}");
                    set_flash_message('success', "Digital PDF document uploaded and attached to book catalog.");
                    header("Location: " . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $errors[] = "Failed to write PDF file to private_pdfs/ directory.";
                }
            }
        } else {
            $errors[] = "Please select a PDF file to upload.";
        }
    }
}

// G. Remove PDF Document from a Book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_pdf'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $bookId = (int)$_POST['book_id'];
        $stmt = $db->prepare("SELECT pdf_file FROM books WHERE id = ?");
        $stmt->execute([$bookId]);
        $pdfFile = $stmt->fetchColumn();

        if ($pdfFile && file_exists(PRIVATE_PDF_DIR . $pdfFile)) {
            @unlink(PRIVATE_PDF_DIR . $pdfFile);
        }

        $db->prepare("UPDATE books SET pdf_file = NULL WHERE id = ?")->execute([$bookId]);
        log_audit_action($_SESSION['user_id'], $_SESSION['role_code'], 'Remove PDF', 'Books', "Book ID: {$bookId}");
        set_flash_message('info', 'Digital PDF document detached from book catalog.');
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// ============================================================
// 2. FETCH DATA FOR UNIFIED MANAGER
// ============================================================
$search = sanitize_input($_GET['q'] ?? '');
$catFilter = (int)($_GET['category'] ?? 0);

$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(b.name LIKE ? OR b.isbn LIKE ? OR b.book_code LIKE ? OR a.name LIKE ?)";
    $term = "%{$search}%";
    $params = array_merge($params, [$term, $term, $term, $term]);
}

if ($catFilter > 0) {
    $whereClauses[] = "b.category_id = ?";
    $params[] = $catFilter;
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

$booksStmt = $db->prepare("
    SELECT b.*, a.name AS author_name, p.name AS publisher_name, c.category_name, cl.class_name, s.name AS subject_name,
           (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id) AS total_copies,
           (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'Available') AS available_copies
    FROM books b
    JOIN authors a ON b.author_id = a.id
    JOIN publishers p ON b.publisher_id = p.id
    JOIN categories c ON b.category_id = c.id
    JOIN classes cl ON b.class_id = cl.id
    JOIN subjects s ON b.subject_id = s.id
    {$whereSql}
    ORDER BY b.id DESC
");
$booksStmt->execute($params);
$books = $booksStmt->fetchAll();

// Fetch copies grouped by book ID
$allCopies = $db->query("
    SELECT bc.*, b.name AS book_name 
    FROM book_copies bc
    JOIN books b ON bc.book_id = b.id
    ORDER BY bc.id DESC
")->fetchAll();

$copiesByBook = [];
foreach ($allCopies as $cp) {
    $copiesByBook[$cp['book_id']][] = $cp;
}

$authors = $db->query("SELECT * FROM authors WHERE status = 'Active'")->fetchAll();
$publishers = $db->query("SELECT * FROM publishers WHERE status = 'Active'")->fetchAll();
$categories = $db->query("SELECT * FROM categories WHERE status = 'Active'")->fetchAll();
$classes = $db->query("SELECT * FROM classes WHERE status = 'Active'")->fetchAll();
$subjects = $db->query("SELECT * FROM subjects WHERE status = 'Active'")->fetchAll();

$pageTitle = "Master Book Manager (Catalog, Barcodes & PDF)";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row g-4">
        <!-- Sidebar Navigation Column -->
        <div class="col-lg-3 col-xl-2">
            <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
        </div>

        <!-- Main Content Area Column -->
        <div class="col-lg-9 col-xl-10">
            <!-- Top Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="section-title mb-1">Master Book Manager</h2>
                    <p class="text-muted small mb-0">Unified management portal for Catalog Metadata, Physical Copy Barcodes, and Digital PDF Documents.</p>
                </div>
                <button type="button" class="btn btn-maroon btn-sm font-serif" data-bs-toggle="modal" data-bs-target="#bookModal" onclick="resetBookForm();" style="background-color: #7A0C0C;">
                    <i class="fas fa-plus me-1"></i> Add New Book Title
                </button>
            </div>

            <?php if (get_setting('catalog_mode', 'database') === 'sheet'): ?>
                <div class="alert alert-warning border-start border-4 border-warning shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
                    <div>
                        <div class="fw-bold text-dark mb-1">
                            <i class="fas fa-file-excel text-warning me-2 fs-5"></i> Public Catalog is currently in "Google Sheet Mode"
                        </div>
                        <div class="small text-muted">
                            The public website is actively showing your external Google Sheet. You can safely continue adding, editing, and uploading books here in the database in the background. When ready, switch to <strong>Live Database Mode</strong> in System Settings to reveal all books to users.
                        </div>
                    </div>
                    <?php if (has_role('SUPER_ADMIN')): ?>
                        <a href="<?= BASE_URL ?>admin/settings/index.php#catalog-settings" class="btn btn-warning btn-sm fw-bold text-dark text-nowrap">
                            <i class="fas fa-sliders-h me-1"></i> Mode Settings
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger shadow-sm mb-4">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?= escape($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Filter & Search Bar -->
    <div class="card sayak-card mb-4 p-3 bg-light border-0 shadow-sm">
        <form action="" method="GET" class="row g-2 align-items-center">
            <div class="col-md-6">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="q" class="form-control" placeholder="Search title, author, ISBN, or book code..." value="<?= escape($search) ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="category" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">All Collections / Categories (Filter)</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $catFilter == $cat['id'] ? 'selected' : '' ?>><?= escape($cat['category_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <button type="submit" class="btn btn-maroon btn-sm w-100" style="background-color: #7A0C0C;">Filter</button>
                <?php if (!empty($search) || $catFilter > 0): ?>
                    <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-secondary btn-sm"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Master Unified Books Table -->
    <div class="card sayak-card">
        <div class="card-body p-0">
            <?php if (!empty($books)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Book Code</th>
                                <th>Book Title & Metadata</th>
                                <th>Author / Publisher</th>
                                <th>Collection / Category & Class</th>
                                <th>Physical Copies</th>
                                <th>Digital PDF Document</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $b): 
                                $bCopies = $copiesByBook[$b['id']] ?? [];
                            ?>
                                <tr>
                                    <td><code><?= escape($b['book_code']) ?></code></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if (!empty($b['cover_image']) && file_exists(ROOT_PATH . 'uploads/covers/' . $b['cover_image'])): ?>
                                                <img src="<?= BASE_URL ?>uploads/covers/<?= escape($b['cover_image']) ?>" alt="Cover" class="rounded border shadow-sm flex-shrink-0" style="width: 36px; height: 48px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="rounded bg-light border d-flex align-items-center justify-content-center text-muted flex-shrink-0" style="width: 36px; height: 48px;" title="No cover image">
                                                    <i class="fas fa-book text-secondary" style="font-size: 14px;"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong class="font-serif text-dark d-block fs-6"><?= escape($b['name']) ?></strong>
                                                <small class="text-muted"><i class="fas fa-barcode me-1"></i> ISBN: <?= escape($b['isbn'] ?: 'N/A') ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="d-block">By <strong><?= escape($b['author_name']) ?></strong></span>
                                        <small class="text-muted"><?= escape($b['publisher_name']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border d-block mb-1"><?= escape($b['category_name']) ?></span>
                                        <small class="text-muted"><?= escape($b['class_name']) ?></small>
                                    </td>

                                    <!-- Unified Physical Copy Column -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary px-2 py-1"><?= $b['available_copies'] ?> / <?= $b['total_copies'] ?> Available</span>
                                            <button type="button" class="btn btn-sm btn-outline-dark font-serif py-0 px-2" onclick='openCopyModal(<?= json_encode($b) ?>, <?= json_encode($bCopies) ?>)'>
                                                <i class="fas fa-layer-group me-1"></i> Manage Copies
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Unified Digital PDF Column -->
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if (!empty($b['pdf_file'])): ?>
                                                <span class="badge bg-success"><i class="fas fa-file-pdf me-1"></i> PDF Active</span>
                                                <a href="<?= BASE_URL ?>pdf_viewer.php?id=<?= $b['id'] ?>" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2" title="Read PDF">
                                                    <i class="fas fa-eye me-1"></i> Read
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No PDF</span>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger font-serif py-0 px-2" onclick='openPdfModal(<?= json_encode($b) ?>)'>
                                                <i class="fas fa-upload me-1"></i> PDF File
                                            </button>
                                        </div>
                                    </td>

                                    <!-- Row Actions -->
                                    <td class="text-end pe-3">
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-maroon" onclick='editBook(<?= json_encode($b) ?>)' title="Edit Metadata">
                                                <i class="fas fa-edit me-1"></i> Edit Catalog
                                            </button>
                                            <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this book? Associated physical copies and PDFs will also be deleted.');">
                                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                <input type="hidden" name="book_id" value="<?= $b['id'] ?>">
                                                <button type="submit" name="delete_book" class="btn btn-outline-danger" title="Delete Book Title">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-5 text-center text-muted">No books found matching criteria.</div>
            <?php endif; ?>
        </div>
    </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL 1: UNIFIED ALL-IN-ONE BOOK MANAGER FORM                 -->
<!-- ============================================================ -->
<div class="modal fade" id="bookModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white" style="background-color: #7A0C0C;">
                <h5 class="modal-title font-serif" id="bookModalTitle">Master Book Manager Form</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="book_id" id="book_id" value="0">

                    <!-- Section 1: Book Catalog Metadata -->
                    <h6 class="fw-bold font-serif text-maroon border-bottom pb-2 mb-3" style="color: #7A0C0C;">
                        <i class="fas fa-book me-1"></i> 1. Catalog & Book Metadata
                    </h6>

                    <div id="bookModalNotice" class="d-none mb-3"></div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Book Title <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" placeholder="Book Title" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold small mb-0">Author / Writer <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link btn-xs p-0 text-maroon font-serif fw-bold text-decoration-none" onclick="toggleQuickAddInline('author')" style="color: #7A0C0C;">
                                    <i class="fas fa-plus-circle me-1"></i> Add New
                                </button>
                            </div>
                            <div id="quickAddBox_author" class="d-none bg-light p-2 rounded mb-2 border border-maroon">
                                <div class="input-group input-group-sm">
                                    <input type="text" id="quickInput_author" class="form-control" placeholder="New Author Name..." onkeydown="if(event.key==='Enter'){event.preventDefault();saveQuickAddInline('author', 'Author / Writer');}">
                                    <button type="button" class="btn btn-maroon btn-sm" onclick="saveQuickAddInline('author', 'Author / Writer')" style="background-color: #7A0C0C; color: white;">Save</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleQuickAddInline('author')">Cancel</button>
                                </div>
                            </div>
                            <select name="author_id" id="author_id" class="form-select" required>
                                <option value="">-- Choose Author --</option>
                                <?php foreach ($authors as $a): ?>
                                    <option value="<?= $a['id'] ?>"><?= escape($a['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold small mb-0">Publisher <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link btn-xs p-0 text-maroon font-serif fw-bold text-decoration-none" onclick="toggleQuickAddInline('publisher')" style="color: #7A0C0C;">
                                    <i class="fas fa-plus-circle me-1"></i> Add New
                                </button>
                            </div>
                            <div id="quickAddBox_publisher" class="d-none bg-light p-2 rounded mb-2 border border-maroon">
                                <div class="input-group input-group-sm">
                                    <input type="text" id="quickInput_publisher" class="form-control" placeholder="New Publisher Name..." onkeydown="if(event.key==='Enter'){event.preventDefault();saveQuickAddInline('publisher', 'Publisher');}">
                                    <button type="button" class="btn btn-maroon btn-sm" onclick="saveQuickAddInline('publisher', 'Publisher')" style="background-color: #7A0C0C; color: white;">Save</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleQuickAddInline('publisher')">Cancel</button>
                                </div>
                            </div>
                            <select name="publisher_id" id="publisher_id" class="form-select" required>
                                <option value="">-- Choose Publisher --</option>
                                <?php foreach ($publishers as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= escape($p['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold small mb-0">Category / Collection <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link btn-xs p-0 text-maroon font-serif fw-bold text-decoration-none" onclick="toggleQuickAddInline('category')" style="color: #7A0C0C;">
                                    <i class="fas fa-plus-circle me-1"></i> Add New
                                </button>
                            </div>
                            <div id="quickAddBox_category" class="d-none bg-light p-2 rounded mb-2 border border-maroon">
                                <div class="input-group input-group-sm">
                                    <input type="text" id="quickInput_category" class="form-control" placeholder="New Category / Collection Name..." onkeydown="if(event.key==='Enter'){event.preventDefault();saveQuickAddInline('category', 'Category / Collection');}">
                                    <button type="button" class="btn btn-maroon btn-sm" onclick="saveQuickAddInline('category', 'Category / Collection')" style="background-color: #7A0C0C; color: white;">Save</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleQuickAddInline('category')">Cancel</button>
                                </div>
                            </div>
                            <select name="category_id" id="category_id" class="form-select" required>
                                <option value="">-- Choose Category / Collection --</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= escape($c['category_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold small mb-0">Class / Standard <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link btn-xs p-0 text-maroon font-serif fw-bold text-decoration-none" onclick="toggleQuickAddInline('class')" style="color: #7A0C0C;">
                                    <i class="fas fa-plus-circle me-1"></i> Add New
                                </button>
                            </div>
                            <div id="quickAddBox_class" class="d-none bg-light p-2 rounded mb-2 border border-maroon">
                                <div class="input-group input-group-sm">
                                    <input type="text" id="quickInput_class" class="form-control" placeholder="New Class Name..." onkeydown="if(event.key==='Enter'){event.preventDefault();saveQuickAddInline('class', 'Class / Standard');}">
                                    <button type="button" class="btn btn-maroon btn-sm" onclick="saveQuickAddInline('class', 'Class / Standard')" style="background-color: #7A0C0C; color: white;">Save</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleQuickAddInline('class')">Cancel</button>
                                </div>
                            </div>
                            <select name="class_id" id="class_id" class="form-select" required>
                                <option value="">-- Choose Class --</option>
                                <?php foreach ($classes as $cl): ?>
                                    <option value="<?= $cl['id'] ?>"><?= escape($cl['class_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold small mb-0">Subject <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-link btn-xs p-0 text-maroon font-serif fw-bold text-decoration-none" onclick="toggleQuickAddInline('subject')" style="color: #7A0C0C;">
                                    <i class="fas fa-plus-circle me-1"></i> Add New
                                </button>
                            </div>
                            <div id="quickAddBox_subject" class="d-none bg-light p-2 rounded mb-2 border border-maroon">
                                <div class="input-group input-group-sm">
                                    <input type="text" id="quickInput_subject" class="form-control" placeholder="New Subject Name..." onkeydown="if(event.key==='Enter'){event.preventDefault();saveQuickAddInline('subject', 'Subject');}">
                                    <button type="button" class="btn btn-maroon btn-sm" onclick="saveQuickAddInline('subject', 'Subject')" style="background-color: #7A0C0C; color: white;">Save</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="toggleQuickAddInline('subject')">Cancel</button>
                                </div>
                            </div>
                            <select name="subject_id" id="subject_id" class="form-select" required>
                                <option value="">-- Choose Subject --</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= escape($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small">ISBN Code</label>
                            <input type="text" name="isbn" id="isbn" class="form-control" placeholder="978-...">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Edition</label>
                            <input type="text" name="edition" id="edition" class="form-control" value="1st Edition">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold small">Publication Year</label>
                            <input type="number" name="pub_year" id="pub_year" class="form-control" value="2023">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Description / Summary</label>
                        <textarea name="description" id="description" class="form-control" rows="2" placeholder="Book overview..."></textarea>
                    </div>

                    <!-- Optional Book Cover Image (Not Mandatory) -->
                    <div class="bg-light p-3 rounded mb-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold font-serif text-dark mb-0">
                                <i class="fas fa-image me-1 text-maroon" style="color: #7A0C0C;"></i> Book Cover Image <span class="badge bg-secondary font-sans fw-normal" style="font-size:10px;">Optional</span>
                            </h6>
                            <span class="text-muted small">JPG, PNG, WEBP (Max 2MB)</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div id="coverPreviewContainer" class="rounded border bg-white d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 54px; height: 72px; overflow: hidden;">
                                <i class="fas fa-book text-muted fa-2x" id="coverPlaceholderIcon"></i>
                                <img id="coverPreviewImg" src="" alt="Cover Preview" class="d-none w-100 h-100" style="object-fit: cover;">
                            </div>
                            <div class="flex-grow-1">
                                <input type="file" name="cover_image" id="cover_image" class="form-control form-control-sm" accept="image/jpeg,image/png,image/webp,image/jpg" onchange="previewBookCover(this)">
                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-muted">Not mandatory. If left blank, default library book icon is used.</small>
                                    <div id="removeCoverBox" class="d-none">
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="checkbox" name="remove_cover" id="remove_cover" value="1">
                                            <label class="form-check-label text-danger small fw-semibold" for="remove_cover">Remove image</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Physical Copy Setup -->
                    <div id="newBookPhysicalSection" class="bg-light p-3 rounded mb-3 border">
                        <h6 class="fw-bold font-serif text-dark mb-2">
                            <i class="fas fa-barcode me-1 text-primary"></i> 2. Physical Copy Barcode Inventory
                        </h6>
                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label small text-muted mb-1">Barcode Code</label>
                                <input type="text" name="barcode" id="barcode" class="form-control form-control-sm" placeholder="Auto-generated if left blank (BAR-BK...)">
                            </div>
                            <div class="col-md-3 me-0 me-md-1">
                                <label class="form-label small text-muted mb-1">Shelf Zone</label>
                                <input type="text" name="shelf" id="shelf" class="form-control form-control-sm" value="Rack-A">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted mb-1">Rack Location</label>
                                <input type="text" name="rack" id="rack" class="form-control form-control-sm" value="Shelf-1">
                            </div>
                        </div>
                        <small class="text-muted d-block mt-1">Additional copies can be added anytime via <strong>Manage Copies</strong>.</small>
                    </div>

                    <!-- Section 3: Digital Library PDF Upload -->
                    <div class="bg-light p-3 rounded border">
                        <h6 class="fw-bold font-serif text-dark mb-2">
                            <i class="fas fa-file-pdf me-1 text-danger"></i> 3. Digital Library PDF File Attachment
                        </h6>
                        <input type="file" name="pdf_file" id="pdf_file" class="form-control form-control-sm" accept=".pdf">
                        <small class="text-muted d-block mt-1">Upload a .PDF file for online reading & digital library access.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_book" class="btn btn-maroon font-serif" style="background-color: #7A0C0C;">
                        <i class="fas fa-save me-1"></i> Save Complete Form
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL 2: UNIFIED PHYSICAL COPY BARCODE MANAGER               -->
<!-- ============================================================ -->
<div class="modal fade" id="copyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title font-serif" id="copyModalTitle"><i class="fas fa-layer-group me-2"></i> Physical Copies Management</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" onclick="onCloseCopyModal()"></button>
            </div>
            <div class="modal-body p-4">
                <div id="copyModalAlert" class="d-none mb-3"></div>

                <div class="bg-light p-3 rounded mb-4 border shadow-sm">
                    <h6 class="fw-bold mb-2 font-serif text-maroon"><i class="fas fa-plus-circle me-1"></i> Add New Copy Barcode for <span id="copyModalBookName"></span></h6>
                    <form action="" method="POST" id="addCopyForm" onsubmit="submitAddCopy(event)" class="row g-2">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="book_id" id="copy_book_id" value="0">
                        <input type="hidden" name="is_ajax" value="1">
                        <input type="hidden" name="add_copy" value="1">
                        <div class="col-md-4">
                            <input type="text" name="barcode" id="copy_new_barcode" class="form-control form-control-sm" placeholder="Copy Barcode (e.g. BAR-001)" required>
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="shelf" id="copy_new_shelf" class="form-control form-control-sm" placeholder="Shelf (Rack-A)" value="Rack-A">
                        </div>
                        <div class="col-md-3">
                            <input type="text" name="rack" id="copy_new_rack" class="form-control form-control-sm" placeholder="Rack (Shelf-1)" value="Shelf-1">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" id="addCopyBtn" class="btn btn-maroon btn-sm w-100 font-serif fw-bold" style="background-color: #7A0C0C;">
                                <i class="fas fa-plus me-1"></i> Add Copy
                            </button>
                        </div>
                    </form>
                </div>

                <h6 class="fw-bold mb-3 font-serif"><i class="fas fa-list me-1"></i> Existing Physical Inventory Copies</h6>
                <div id="copyModalList"></div>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-between">
                <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Window stays open so you can add multiple copies continuously.</small>
                <button type="button" class="btn btn-dark font-serif" data-bs-dismiss="modal" onclick="onCloseCopyModal()">
                    <i class="fas fa-check-circle me-1 text-success"></i> Done (Close Window)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL 3: UNIFIED DIGITAL PDF MANAGER                         -->
<!-- ============================================================ -->
<div class="modal fade" id="pdfModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title font-serif" id="pdfModalTitle"><i class="fas fa-file-pdf me-2"></i> Digital PDF Management</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <h5 class="font-serif fw-bold text-dark mb-1" id="pdfModalBookName"></h5>
                <p class="text-muted small mb-4" id="pdfModalBookCode"></p>

                <div id="pdfModalCurrentState" class="mb-4"></div>

                <div class="border-top pt-3 text-start">
                    <h6 class="fw-bold mb-2 font-serif"><i class="fas fa-upload me-1 text-danger"></i> Upload / Replace PDF Document</h6>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="book_id" id="pdf_book_id" value="0">
                        <div class="mb-3">
                            <input type="file" name="pdf_file" class="form-control" accept=".pdf" required>
                            <small class="text-muted">Must be a valid .PDF document file.</small>
                        </div>
                        <button type="submit" name="upload_pdf" class="btn btn-danger w-100 font-serif fw-bold">
                            <i class="fas fa-cloud-upload-alt me-1"></i> Upload PDF to Digital Library
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<!-- JavaScript Dynamic Modal Controls -->
<script>
var copiesModified = false;

function toggleQuickAddInline(type) {
    var box = document.getElementById('quickAddBox_' + type);
    if (box) {
        if (box.classList.contains('d-none')) {
            box.classList.remove('d-none');
            var input = document.getElementById('quickInput_' + type);
            if (input) { input.value = ''; input.focus(); }
        } else {
            box.classList.add('d-none');
        }
    }
}

function saveQuickAddInline(type, label) {
    var input = document.getElementById('quickInput_' + type);
    var name = input ? input.value.trim() : '';
    if (!name) {
        alert('Please enter a name for ' + label + '.');
        return;
    }

    var formData = new FormData();
    formData.append('csrf_token', '<?= generate_csrf_token() ?>');
    formData.append('quick_add_type', type);
    formData.append('quick_add_name', name);

    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            var selectElem = document.getElementById(type + '_id');
            if (selectElem) {
                var opt = new Option(data.name, data.id, true, true);
                selectElem.add(opt);
                selectElem.value = data.id;
            }

            toggleQuickAddInline(type);

            var bookNotice = document.getElementById('bookModalNotice');
            if (bookNotice) {
                bookNotice.className = 'alert alert-success py-2 mb-3 fw-bold small';
                bookNotice.innerHTML = '<i class="fas fa-check-circle me-1"></i> ' + data.message;
                bookNotice.classList.remove('d-none');
            }
        } else {
            alert(data.error || 'Failed to add item.');
        }
    })
    .catch(err => {
        alert('Connection error while saving item.');
    });
}

function previewBookCover(input) {
    var preview = document.getElementById('coverPreviewImg');
    var icon = document.getElementById('coverPlaceholderIcon');
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('d-none');
            if (icon) icon.classList.add('d-none');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function resetBookForm() {
    document.getElementById('book_id').value = '0';
    document.getElementById('name').value = '';
    document.getElementById('isbn').value = '';
    document.getElementById('description').value = '';

    // Reset cover preview
    var coverInput = document.getElementById('cover_image');
    if (coverInput) coverInput.value = '';
    var preview = document.getElementById('coverPreviewImg');
    var icon = document.getElementById('coverPlaceholderIcon');
    if (preview) { preview.src = ''; preview.classList.add('d-none'); }
    if (icon) icon.classList.remove('d-none');
    var removeBox = document.getElementById('removeCoverBox');
    if (removeBox) removeBox.classList.add('d-none');
    var removeCheck = document.getElementById('remove_cover');
    if (removeCheck) removeCheck.checked = false;

    var physSec = document.getElementById('newBookPhysicalSection');
    if (physSec) physSec.style.display = 'block';
    document.getElementById('bookModalTitle').innerText = 'Add New Book (Catalog, Physical Barcode & PDF)';
}

function editBook(b) {
    document.getElementById('book_id').value = b.id;
    document.getElementById('name').value = b.name;
    document.getElementById('author_id').value = b.author_id;
    document.getElementById('publisher_id').value = b.publisher_id;
    document.getElementById('category_id').value = b.category_id;
    document.getElementById('class_id').value = b.class_id;
    document.getElementById('subject_id').value = b.subject_id;
    document.getElementById('isbn').value = b.isbn || '';
    document.getElementById('edition').value = b.edition;
    document.getElementById('pub_year').value = b.pub_year;
    document.getElementById('description').value = b.description || '';

    // Handle existing cover image
    var coverInput = document.getElementById('cover_image');
    if (coverInput) coverInput.value = '';
    var preview = document.getElementById('coverPreviewImg');
    var icon = document.getElementById('coverPlaceholderIcon');
    var removeBox = document.getElementById('removeCoverBox');
    var removeCheck = document.getElementById('remove_cover');
    if (removeCheck) removeCheck.checked = false;

    if (b.cover_image) {
        preview.src = '<?= BASE_URL ?>uploads/covers/' + b.cover_image;
        preview.classList.remove('d-none');
        if (icon) icon.classList.add('d-none');
        if (removeBox) removeBox.classList.remove('d-none');
    } else {
        preview.src = '';
        preview.classList.add('d-none');
        if (icon) icon.classList.remove('d-none');
        if (removeBox) removeBox.classList.add('d-none');
    }

    var physSec = document.getElementById('newBookPhysicalSection');
    if (physSec) physSec.style.display = 'none';
    document.getElementById('bookModalTitle').innerText = 'Edit Catalog Metadata & PDF Attachment';
    var modal = new bootstrap.Modal(document.getElementById('bookModal'));
    modal.show();
}

function openCopyModal(b, copies) {
    copiesModified = false;
    document.getElementById('copy_book_id').value = b.id;
    document.getElementById('copyModalBookName').innerText = b.name;
    
    var alertBox = document.getElementById('copyModalAlert');
    if (alertBox) {
        alertBox.className = 'd-none mb-3';
        alertBox.innerHTML = '';
    }

    // Auto generate suggested barcode
    var nextNum = copies ? copies.length + 1 : 1;
    var numStr = nextNum < 10 ? '00' + nextNum : (nextNum < 100 ? '0' + nextNum : nextNum);
    document.getElementById('copy_new_barcode').value = 'BAR-' + b.book_code.replace('SL-BK-', 'BK') + '-' + numStr;

    renderCopyList(copies);

    var modalElement = document.getElementById('copyModal');
    var modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
    modal.show();
}

function renderCopyList(copies) {
    var container = document.getElementById('copyModalList');
    if (!copies || copies.length === 0) {
        container.innerHTML = '<div class="alert alert-warning text-center">No physical copy barcodes created yet. Add your first copy above!</div>';
        return;
    }

    var html = '<div class="table-responsive"><table class="table table-sm table-hover align-middle"><thead class="table-light"><tr><th>Copy Code</th><th>Barcode</th><th>Location</th><th>Status</th><th class="text-end">Actions</th></tr></thead><tbody>';
    for (var i = 0; i < copies.length; i++) {
        var cp = copies[i];
        html += '<tr>';
        html += '<td><code>' + cp.copy_code + '</code></td>';
        html += '<td><strong>' + cp.barcode + '</strong></td>';
        html += '<td><small>' + cp.shelf + ' / ' + cp.rack + '</small></td>';
        html += '<td><span class="badge bg-' + (cp.status === 'Available' ? 'success' : (cp.status === 'Issued' ? 'primary' : 'secondary')) + '">' + cp.status + '</span></td>';
        html += '<td class="text-end">';
        html += '<div class="d-inline-flex gap-1 mb-0">';
        html += '<select class="form-select form-select-sm" style="width:110px;" onchange="updateCopyStatusAjax(' + cp.id + ', this.value, ' + cp.book_id + ')">';
        html += '<option value="Available" ' + (cp.status === 'Available' ? 'selected' : '') + '>Available</option>';
        html += '<option value="Issued" ' + (cp.status === 'Issued' ? 'selected' : '') + '>Issued</option>';
        html += '<option value="Lost" ' + (cp.status === 'Lost' ? 'selected' : '') + '>Lost</option>';
        html += '<option value="Damaged" ' + (cp.status === 'Damaged' ? 'selected' : '') + '>Damaged</option>';
        html += '<option value="Maintenance" ' + (cp.status === 'Maintenance' ? 'selected' : '') + '>Maintenance</option>';
        html += '</select>';
        html += '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteCopyAjax(' + cp.id + ', \'' + cp.barcode + '\', ' + cp.book_id + ')" ' + (cp.status === 'Issued' ? 'disabled' : '') + '><i class="fas fa-trash"></i></button>';
        html += '</div>';
        html += '</td>';
        html += '</tr>';
    }
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function submitAddCopy(e) {
    e.preventDefault();
    var form = document.getElementById('addCopyForm');
    var formData = new FormData(form);
    
    var alertBox = document.getElementById('copyModalAlert');
    alertBox.className = 'alert alert-info py-2 mb-3';
    alertBox.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Adding copy barcode...';
    alertBox.classList.remove('d-none');

    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alertBox.className = 'alert alert-success py-2 mb-3 fw-bold';
            alertBox.innerHTML = '<i class="fas fa-check-circle me-1"></i> ' + data.message + ' Add another copy below or click Done.';
            renderCopyList(data.copies);
            document.getElementById('copy_new_barcode').value = data.next_barcode;
            document.getElementById('copy_new_barcode').focus();
            copiesModified = true;
        } else {
            alertBox.className = 'alert alert-danger py-2 mb-3 fw-bold';
            alertBox.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> ' + (data.error || 'Failed to add copy.');
        }
    })
    .catch(err => {
        alertBox.className = 'alert alert-danger py-2 mb-3 fw-bold';
        alertBox.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i> Connection error.';
    });
}

function updateCopyStatusAjax(copyId, newStatus, bookId) {
    var formData = new FormData();
    formData.append('csrf_token', '<?= generate_csrf_token() ?>');
    formData.append('copy_id', copyId);
    formData.append('book_id', bookId);
    formData.append('status', newStatus);
    formData.append('update_copy_status', '1');
    formData.append('is_ajax', '1');

    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderCopyList(data.copies);
            copiesModified = true;
        }
    });
}

function deleteCopyAjax(copyId, barcode, bookId) {
    if (!confirm('Delete copy barcode ' + barcode + '?')) return;
    var formData = new FormData();
    formData.append('csrf_token', '<?= generate_csrf_token() ?>');
    formData.append('copy_id', copyId);
    formData.append('book_id', bookId);
    formData.append('delete_copy', '1');
    formData.append('is_ajax', '1');

    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderCopyList(data.copies);
            if (data.next_barcode) {
                document.getElementById('copy_new_barcode').value = data.next_barcode;
            }
            copiesModified = true;
        } else {
            alert(data.error || 'Failed to delete copy.');
        }
    });
}

function onCloseCopyModal() {
    if (copiesModified) {
        window.location.reload();
    }
}

function openPdfModal(b) {
    document.getElementById('pdf_book_id').value = b.id;
    document.getElementById('pdfModalBookName').innerText = b.name;
    document.getElementById('pdfModalBookCode').innerText = "Book Code: " + b.book_code;

    var stateDiv = document.getElementById('pdfModalCurrentState');
    if (b.pdf_file) {
        stateDiv.innerHTML = '<div class="alert alert-success d-flex align-items-center justify-content-between mb-0"><div><i class="fas fa-check-circle me-2"></i> PDF Document active</div><div><a href="<?= BASE_URL ?>pdf_viewer.php?id=' + b.id + '" target="_blank" class="btn btn-sm btn-success me-1"><i class="fas fa-book-reader me-1"></i> Read PDF</a><form action="" method="POST" class="d-inline" onsubmit="return confirm(\'Remove attached PDF document?\');"><input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>"><input type="hidden" name="book_id" value="' + b.id + '"><button type="submit" name="remove_pdf" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash me-1"></i> Detach</button></form></div></div>';
    } else {
        stateDiv.innerHTML = '<div class="alert alert-secondary mb-0"><i class="fas fa-exclamation-triangle me-1"></i> No PDF document currently attached to this title.</div>';
    }

    var modal = new bootstrap.Modal(document.getElementById('pdfModal'));
    modal.show();
}
</script>

<?php if (isset($_GET['open_copy_book_id'])): 
    $openId = (int)$_GET['open_copy_book_id'];
    $targetBook = null;
    foreach ($books as $bk) {
        if ($bk['id'] == $openId) {
            $targetBook = $bk;
            break;
        }
    }
    if ($targetBook):
        $tCopies = $copiesByBook[$targetBook['id']] ?? [];
?>
<script>
window.addEventListener('DOMContentLoaded', function() {
    openCopyModal(<?= json_encode($targetBook) ?>, <?= json_encode($tCopies) ?>);
});
</script>
<?php endif; endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>

