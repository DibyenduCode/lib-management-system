<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';
require_once __DIR__ . '/../../includes/email.php';

require_role(['LIBRARIAN', 'SUPER_ADMIN']);

$db = getDB();
$errors = [];

// ============================================================
// AJAX ENDPOINTS FOR DYNAMIC SEARCH & FETCH
// ============================================================

// 1. AJAX: Fetch available physical copies for a selected book
if (isset($_GET['action']) && $_GET['action'] === 'get_copies') {
    header('Content-Type: application/json');
    $bId = (int)($_GET['book_id'] ?? 0);
    $copies = [];
    if ($bId > 0) {
        $cpStmt = $db->prepare("
            SELECT id, copy_code, barcode, shelf, rack 
            FROM book_copies 
            WHERE book_id = ? AND status = 'Available' 
            ORDER BY copy_code ASC
        ");
        $cpStmt->execute([$bId]);
        $copies = $cpStmt->fetchAll();
    }
    echo json_encode($copies);
    exit();
}

// 2. AJAX: Live search members
if (isset($_GET['action']) && $_GET['action'] === 'search_members') {
    header('Content-Type: application/json');
    $q = sanitize_input($_GET['q'] ?? '');
    $params = [];
    $sql = "
        SELECT m.id, m.member_code, m.mobile, u.full_name, u.email 
        FROM members m 
        JOIN users u ON m.user_id = u.id 
        WHERE u.status != 'Suspended'
    ";
    if (!empty($q)) {
        $sql .= " AND (u.full_name LIKE ? OR m.member_code LIKE ? OR m.mobile LIKE ? OR u.email LIKE ?)";
        $term = "%{$q}%";
        $params = [$term, $term, $term, $term];
    }
    $sql .= " ORDER BY u.full_name ASC LIMIT 30";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
    exit();
}

// 3. AJAX: Live search books
if (isset($_GET['action']) && $_GET['action'] === 'search_books') {
    header('Content-Type: application/json');
    $q = sanitize_input($_GET['q'] ?? '');
    $params = [];
    $sql = "
        SELECT b.id, b.book_code, b.name, b.isbn, a.name AS author_name,
               (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'Available') AS available_copies
        FROM books b
        LEFT JOIN authors a ON b.author_id = a.id
    ";
    if (!empty($q)) {
        $sql .= " WHERE (b.name LIKE ? OR b.book_code LIKE ? OR b.isbn LIKE ? OR a.name LIKE ?)";
        $term = "%{$q}%";
        $params = [$term, $term, $term, $term];
    }
    $sql .= " ORDER BY b.name ASC LIMIT 30";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    echo json_encode($stmt->fetchAll());
    exit();
}

// ============================================================
// PRE-SELECTION & REQUEST RESOLUTION
// ============================================================
$preRequestId = (int)($_GET['request_id'] ?? ($_POST['request_id'] ?? 0));
$preMemberId  = (int)($_GET['member_id'] ?? ($_POST['member_id'] ?? 0));
$preBookId    = (int)($_GET['book_id'] ?? ($_POST['book_id'] ?? 0));

if ($preRequestId > 0) {
    $rStmt = $db->prepare("SELECT member_id, book_id FROM book_requests WHERE id = ?");
    $rStmt->execute([$preRequestId]);
    $rData = $rStmt->fetch();
    if ($rData) {
        if ($preMemberId <= 0) $preMemberId = (int)$rData['member_id'];
        if ($preBookId <= 0) $preBookId = (int)$rData['book_id'];
    }
}

// Fetch pre-selected member details if applicable
$selectedMember = null;
if ($preMemberId > 0) {
    $smStmt = $db->prepare("
        SELECT m.id, m.member_code, m.mobile, u.full_name, u.email 
        FROM members m 
        JOIN users u ON m.user_id = u.id 
        WHERE m.id = ? LIMIT 1
    ");
    $smStmt->execute([$preMemberId]);
    $selectedMember = $smStmt->fetch();
}

// Fetch pre-selected book details if applicable
$selectedBook = null;
if ($preBookId > 0) {
    $sbStmt = $db->prepare("
        SELECT b.id, b.book_code, b.name, b.isbn, a.name AS author_name,
               (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'Available') AS available_copies
        FROM books b 
        LEFT JOIN authors a ON b.author_id = a.id 
        WHERE b.id = ? LIMIT 1
    ");
    $sbStmt->execute([$preBookId]);
    $selectedBook = $sbStmt->fetch();
}

// ============================================================
// FORM SUBMISSION PROCESSING
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Invalid CSRF security token. Please refresh and try again.";
    } else {
        $memberId  = (int)($_POST['member_id'] ?? 0);
        $bookId    = (int)($_POST['book_id'] ?? 0);
        $copyId    = (int)($_POST['copy_id'] ?? 0);
        $issueDate = sanitize_input($_POST['issue_date'] ?? date('Y-m-d'));
        $dueDate   = sanitize_input($_POST['due_date'] ?? date('Y-m-d', strtotime('+14 days')));

        // Safely validate and sanitize request_id to avoid foreign key violation
        $requestId = null;
        if (!empty($_POST['request_id'])) {
            $candidateReqId = (int)$_POST['request_id'];
            if ($candidateReqId > 0) {
                $chkReq = $db->prepare("SELECT id FROM book_requests WHERE id = ? LIMIT 1");
                $chkReq->execute([$candidateReqId]);
                if ($chkReq->fetchColumn()) {
                    $requestId = $candidateReqId;
                }
            }
        }

        if ($memberId <= 0) $errors[] = "Please select a Member.";
        if ($bookId <= 0) $errors[] = "Please select a Book.";
        if ($copyId <= 0) $errors[] = "Please select an available Copy Barcode.";

        // Verify member status
        if ($memberId > 0) {
            $mCheck = $db->prepare("
                SELECT m.membership_status, u.status AS user_status 
                FROM members m 
                JOIN users u ON m.user_id = u.id 
                WHERE m.id = ? LIMIT 1
            ");
            $mCheck->execute([$memberId]);
            $mStatus = $mCheck->fetch();
            if (!$mStatus) {
                $errors[] = "Selected member could not be found.";
            } elseif ($mStatus['user_status'] === 'Suspended') {
                $errors[] = "This member account is suspended and cannot borrow books.";
            }
        }

        // Check if selected copy is still available
        if ($copyId > 0) {
            $cCheck = $db->prepare("SELECT status FROM book_copies WHERE id = ?");
            $cCheck->execute([$copyId]);
            $cStatus = $cCheck->fetchColumn();
            if ($cStatus !== 'Available') {
                $errors[] = "Selected book copy is no longer available (Status: " . ($cStatus ?: 'Not found') . ").";
            }
        }

        if (empty($errors)) {
            try {
                $db->beginTransaction();

                $issueCode = generate_issue_code($db);

                // 1. Insert book_issues with explicit parameter binding for NULL safety
                $insStmt = $db->prepare("
                    INSERT INTO book_issues (issue_code, request_id, member_id, book_id, copy_id, issue_date, due_date, status, issued_by)
                    VALUES (:issue_code, :request_id, :member_id, :book_id, :copy_id, :issue_date, :due_date, 'Issued', :issued_by)
                ");
                $insStmt->bindValue(':issue_code', $issueCode, PDO::PARAM_STR);
                if ($requestId !== null && $requestId > 0) {
                    $insStmt->bindValue(':request_id', $requestId, PDO::PARAM_INT);
                } else {
                    $insStmt->bindValue(':request_id', null, PDO::PARAM_NULL);
                }
                $insStmt->bindValue(':member_id', $memberId, PDO::PARAM_INT);
                $insStmt->bindValue(':book_id', $bookId, PDO::PARAM_INT);
                $insStmt->bindValue(':copy_id', $copyId, PDO::PARAM_INT);
                $insStmt->bindValue(':issue_date', $issueDate, PDO::PARAM_STR);
                $insStmt->bindValue(':due_date', $dueDate, PDO::PARAM_STR);
                $insStmt->bindValue(':issued_by', $_SESSION['user_id'], PDO::PARAM_INT);
                $insStmt->execute();

                // 2. Update copy status -> Issued
                $db->prepare("UPDATE book_copies SET status = 'Issued' WHERE id = ?")->execute([$copyId]);

                // 3. Update request status -> Issued if applicable
                if ($requestId !== null) {
                    $db->prepare("UPDATE book_requests SET status = 'Issued' WHERE id = ?")->execute([$requestId]);
                }

                // 4. Send Notifications
                $mUserStmt = $db->prepare("
                    SELECT u.id, u.email, u.full_name, b.name AS book_name 
                    FROM members m 
                    JOIN users u ON m.user_id = u.id 
                    JOIN books b ON b.id = ? 
                    WHERE m.id = ?
                ");
                $mUserStmt->execute([$bookId, $memberId]);
                $mUser = $mUserStmt->fetch();

                if ($mUser) {
                    add_notification($mUser['id'], "Book Issued: {$mUser['book_name']}", "Your physical book has been issued. Return due date: " . format_date($dueDate), BASE_URL . "member/my-books.php");
                    send_library_email($mUser['email'], "Book Issued - Sayak Library", "<p>Dear {$mUser['full_name']},</p><p>Book <strong>{$mUser['book_name']}</strong> has been issued to your account. Return due date: <strong>" . format_date($dueDate) . "</strong>.</p>");
                }

                log_audit_action($_SESSION['user_id'], $_SESSION['role_code'], 'Issue Book', 'BookIssues', "Issue ID: {$issueCode}, Copy ID: {$copyId}");

                $db->commit();

                set_flash_message('success', "Book issued successfully! Issue Code: {$issueCode}");
                header("Location: " . BASE_URL . "librarian/dashboard.php");
                exit();

            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = "Error issuing book: " . $e->getMessage();
            }
        }
    }
}

// Preload initial list of active members & books (up to 50 for immediate client-side instant filtering)
$initialMembers = $db->query("
    SELECT m.id, m.member_code, m.mobile, u.full_name, u.email 
    FROM members m 
    JOIN users u ON m.user_id = u.id 
    WHERE u.status != 'Suspended' 
    ORDER BY u.full_name ASC 
    LIMIT 60
")->fetchAll();

$initialBooks = $db->query("
    SELECT b.id, b.book_code, b.name, b.isbn, a.name AS author_name,
           (SELECT COUNT(*) FROM book_copies bc WHERE bc.book_id = b.id AND bc.status = 'Available') AS available_copies
    FROM books b 
    LEFT JOIN authors a ON b.author_id = a.id 
    ORDER BY b.name ASC 
    LIMIT 60
")->fetchAll();

// Available copies for pre-selected book
$availableCopies = [];
if ($preBookId > 0) {
    $cpStmt = $db->prepare("
        SELECT id, copy_code, barcode, shelf, rack 
        FROM book_copies 
        WHERE book_id = ? AND status = 'Available' 
        ORDER BY copy_code ASC
    ");
    $cpStmt->execute([$preBookId]);
    $availableCopies = $cpStmt->fetchAll();
}

$pageTitle = "Issue Physical Book";
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
            <div class="card sayak-card">
                <div class="card-header bg-maroon text-white font-serif py-3 fw-bold fs-5" style="background-color: #7A0C0C;">
                    <i class="fas fa-book-reader me-2"></i> Record Physical Book Issue
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= escape($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" id="issueForm">
                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                        <input type="hidden" name="request_id" id="requestIdInput" value="<?= $preRequestId > 0 ? $preRequestId : '' ?>">

                        <!-- ============================================== -->
                        <!-- FIELD 1: SEARCHABLE SELECT MEMBER / USER       -->
                        <!-- ============================================== -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-user text-maroon me-1" style="color:#7A0C0C;"></i> Select Library Member <span class="text-danger">*</span></span>
                                <span class="badge bg-light text-secondary border fw-normal" id="memberCountBadge"><?= count($initialMembers) ?> members loaded</span>
                            </label>
                            
                            <input type="hidden" name="member_id" id="memberIdInput" value="<?= $preMemberId > 0 ? $preMemberId : '' ?>" required>

                            <!-- Selected Member Summary Card -->
                            <div id="memberSelectedCard" class="p-3 border rounded-3 bg-light d-flex justify-content-between align-items-center mb-2 <?= $selectedMember ? '' : 'd-none' ?>">
                                <div class="d-flex align-items-center overflow-hidden">
                                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 44px; height: 44px; background-color: #7A0C0C;">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <h6 class="mb-0 fw-bold font-serif text-dark text-truncate" id="selectedMemberName">
                                            <?= $selectedMember ? escape($selectedMember['full_name']) : '' ?>
                                        </h6>
                                        <div class="small">
                                            <span class="badge bg-maroon me-2" style="background-color: #7A0C0C;" id="selectedMemberCode">
                                                <?= $selectedMember ? escape($selectedMember['member_code']) : '' ?>
                                            </span>
                                            <span class="text-muted" id="selectedMemberContact">
                                                <?php if ($selectedMember): ?>
                                                    <i class="fas fa-phone me-1"></i> <?= escape($selectedMember['mobile']) ?> &bull; <?= escape($selectedMember['email']) ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0 ms-2" onclick="clearMemberSelection()">
                                    <i class="fas fa-exchange-alt me-1"></i> Change
                                </button>
                            </div>

                            <!-- Member Search & Filter Combobox (visible when not selected) -->
                            <div id="memberSearchBox" class="position-relative <?= $selectedMember ? 'd-none' : '' ?>">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" id="memberSearchInput" class="form-control border-start-0" 
                                           placeholder="Type to search by name, code (e.g. SL-MEM-...), phone, or email..." 
                                           autocomplete="off" oninput="onMemberSearchInput(this.value)" onfocus="onMemberSearchFocus()">
                                    <button type="button" class="btn btn-outline-secondary" onclick="resetMemberSearch()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                
                                <div id="memberResultsContainer" class="position-absolute start-0 end-0 bg-white border rounded-bottom shadow-lg overflow-auto" style="top: 100%; z-index: 1060; max-height: 250px; display: none;">
                                    <div id="memberResultsList" class="list-group list-group-flush small">
                                        <!-- Dynamically Populated On Search -->
                                    </div>
                                </div>
                                <small class="text-muted mt-1 d-block"><i class="fas fa-lightbulb text-warning me-1"></i> Quick search filters through registered members as you type.</small>
                            </div>
                        </div>

                        <!-- ============================================== -->
                        <!-- FIELD 2: SEARCHABLE SELECT BOOK TITLE          -->
                        <!-- ============================================== -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-book text-maroon me-1" style="color:#7A0C0C;"></i> Select Book Title <span class="text-danger">*</span></span>
                                <span class="badge bg-light text-secondary border fw-normal" id="bookCountBadge"><?= count($initialBooks) ?> titles loaded</span>
                            </label>

                            <input type="hidden" name="book_id" id="bookIdInput" value="<?= $preBookId > 0 ? $preBookId : '' ?>" required>

                            <!-- Selected Book Summary Card -->
                            <div id="bookSelectedCard" class="p-3 border rounded-3 bg-light d-flex justify-content-between align-items-center mb-2 <?= $selectedBook ? '' : 'd-none' ?>">
                                <div class="d-flex align-items-center overflow-hidden">
                                    <div class="rounded-circle bg-dark text-white d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 44px; height: 44px;">
                                        <i class="fas fa-book"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <h6 class="mb-0 fw-bold font-serif text-dark text-truncate" id="selectedBookName">
                                            <?= $selectedBook ? escape($selectedBook['name']) : '' ?>
                                        </h6>
                                        <div class="small">
                                            <span class="badge bg-dark me-2" id="selectedBookCode">
                                                <?= $selectedBook ? escape($selectedBook['book_code']) : '' ?>
                                            </span>
                                            <span class="text-muted me-2" id="selectedBookAuthor">
                                                <?= $selectedBook ? 'Author: ' . escape($selectedBook['author_name'] ?? 'N/A') : '' ?>
                                            </span>
                                            <span class="badge bg-success" id="selectedBookAvailable">
                                                <?= $selectedBook ? (int)$selectedBook['available_copies'] . ' Available Copy(ies)' : '' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary flex-shrink-0 ms-2" onclick="clearBookSelection()">
                                    <i class="fas fa-exchange-alt me-1"></i> Change
                                </button>
                            </div>

                            <!-- Book Search & Filter Combobox (visible when not selected) -->
                            <div id="bookSearchBox" class="position-relative <?= $selectedBook ? 'd-none' : '' ?>">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" id="bookSearchInput" class="form-control border-start-0" 
                                           placeholder="Type to search by title, code (e.g. SL-BK-...), author, or ISBN..." 
                                           autocomplete="off" oninput="onBookSearchInput(this.value)" onfocus="onBookSearchFocus()">
                                    <button type="button" class="btn btn-outline-secondary" onclick="resetBookSearch()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>

                                <div id="bookResultsContainer" class="position-absolute start-0 end-0 bg-white border rounded-bottom shadow-lg overflow-auto" style="top: 100%; z-index: 1060; max-height: 260px; display: none;">
                                    <div id="bookResultsList" class="list-group list-group-flush small">
                                        <!-- Dynamically Populated On Search -->
                                    </div>
                                </div>
                                <small class="text-muted mt-1 d-block"><i class="fas fa-lightbulb text-warning me-1"></i> Instant search across book titles, codes, authors, and ISBN numbers.</small>
                            </div>
                        </div>

                        <!-- ============================================== -->
                        <!-- FIELD 3: SELECT PHYSICAL COPY BARCODE          -->
                        <!-- ============================================== -->
                        <div class="mb-4">
                            <label class="form-label fw-bold small"><i class="fas fa-barcode text-maroon me-1" style="color:#7A0C0C;"></i> Select Physical Copy Barcode <span class="text-danger">*</span></label>
                            <div id="copyContainer">
                                <select name="copy_id" id="copySelect" class="form-select form-select-lg" required>
                                    <option value="">-- Select Barcode Copy --</option>
                                    <?php foreach ($availableCopies as $cp): ?>
                                        <option value="<?= $cp['id'] ?>">
                                            Barcode: <?= escape($cp['barcode']) ?> &bull; Location: <?= escape($cp['shelf']) ?> / <?= escape($cp['rack']) ?> (<?= escape($cp['copy_code']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div id="copyNotice" class="mt-2 <?= ($preBookId > 0 && !empty($availableCopies)) ? 'd-none' : '' ?>">
                                <?php if ($preBookId <= 0): ?>
                                    <div class="alert alert-info py-2 small mb-0"><i class="fas fa-info-circle me-1"></i> Please select a Book Title above to view and assign available copy barcodes.</div>
                                <?php elseif (empty($availableCopies)): ?>
                                    <div class="alert alert-warning py-2 small mb-0"><i class="fas fa-exclamation-triangle me-1"></i> No physical copies are currently AVAILABLE for this title.</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- ============================================== -->
                        <!-- FIELD 4: DATES                                 -->
                        <!-- ============================================== -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small"><i class="fas fa-calendar-alt text-muted me-1"></i> Issue Date</label>
                                <input type="date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small"><i class="fas fa-calendar-check text-muted me-1"></i> Due Date (Return Deadline)</label>
                                <input type="date" name="due_date" class="form-control" value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-maroon btn-lg w-100 font-serif shadow-sm" style="background-color: #7A0C0C;">
                            <i class="fas fa-check-circle me-2"></i> Confirm & Issue Book
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.searchable-item {
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
}
.searchable-item:hover, .searchable-item:focus {
    background-color: #f8f9fa;
}
.searchable-item.active {
    background-color: #7A0C0C !important;
    color: #fff !important;
}
</style>

<script>
// Raw Initial Datasets embedded for zero-latency local search
let membersData = <?= json_encode($initialMembers) ?>;
let booksData = <?= json_encode($initialBooks) ?>;

let memberDebounceTimer = null;
let bookDebounceTimer = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // If book is pre-selected, fetch its available copies
    const preBookId = document.getElementById('bookIdInput').value;
    if (preBookId && preBookId > 0) {
        fetchAvailableCopies(preBookId);
    }
});

// Close search dropdowns when clicking outside or pressing Escape
document.addEventListener('click', function(e) {
    const memberBox = document.getElementById('memberSearchBox');
    const memberResults = document.getElementById('memberResultsContainer');
    if (memberBox && memberResults && !memberBox.contains(e.target)) {
        memberResults.style.display = 'none';
    }

    const bookBox = document.getElementById('bookSearchBox');
    const bookResults = document.getElementById('bookResultsContainer');
    if (bookBox && bookResults && !bookBox.contains(e.target)) {
        bookResults.style.display = 'none';
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const memberResults = document.getElementById('memberResultsContainer');
        if (memberResults) memberResults.style.display = 'none';
        const bookResults = document.getElementById('bookResultsContainer');
        if (bookResults) bookResults.style.display = 'none';
    }
});

// ============================================================
// MEMBER SEARCH & SELECTION LOGIC
// ============================================================
function renderMemberList(list) {
    const container = document.getElementById('memberResultsList');
    if (!container) return;

    if (!list || list.length === 0) {
        container.innerHTML = '<div class="p-3 text-muted text-center"><i class="fas fa-user-slash me-1"></i> No matching members found.</div>';
        return;
    }

    let html = '';
    list.forEach(m => {
        html += `
            <a href="javascript:void(0)" class="list-group-item list-group-item-action searchable-item p-2 d-flex justify-content-between align-items-center" onclick="selectMember(${m.id}, '${escapeJs(m.full_name)}', '${escapeJs(m.member_code)}', '${escapeJs(m.mobile || '')}', '${escapeJs(m.email || '')}')">
                <div>
                    <strong class="font-serif text-dark d-block">${escapeHtml(m.full_name)}</strong>
                    <small class="text-muted"><i class="fas fa-phone me-1"></i> ${escapeHtml(m.mobile || 'N/A')} &bull; ${escapeHtml(m.email || '')}</small>
                </div>
                <span class="badge bg-maroon rounded-pill" style="background-color: #7A0C0C;">${escapeHtml(m.member_code)}</span>
            </a>
        `;
    });
    container.innerHTML = html;
}

function onMemberSearchFocus() {
    const input = document.getElementById('memberSearchInput');
    const container = document.getElementById('memberResultsContainer');
    if (input && container && input.value.trim().length > 0) {
        container.style.display = 'block';
    }
}

function onMemberSearchInput(query) {
    const container = document.getElementById('memberResultsContainer');
    query = query.trim().toLowerCase();
    
    if (query.length === 0) {
        if (container) container.style.display = 'none';
        return;
    }

    if (container) container.style.display = 'block';
    
    // Instant local filter first
    const localFiltered = membersData.filter(m => 
        (m.full_name && m.full_name.toLowerCase().includes(query)) ||
        (m.member_code && m.member_code.toLowerCase().includes(query)) ||
        (m.mobile && m.mobile.toLowerCase().includes(query)) ||
        (m.email && m.email.toLowerCase().includes(query))
    );
    renderMemberList(localFiltered);

    // Debounced AJAX search for large remote catalogs
    clearTimeout(memberDebounceTimer);
    if (query.length >= 2) {
        memberDebounceTimer = setTimeout(() => {
            fetch('?action=search_members&q=' + encodeURIComponent(query))
                .then(res => res.json())
                .then(data => {
                    if (Array.isArray(data) && container && container.style.display !== 'none') {
                        renderMemberList(data);
                    }
                })
                .catch(() => {});
        }, 250);
    }
}

function resetMemberSearch() {
    document.getElementById('memberSearchInput').value = '';
    const container = document.getElementById('memberResultsContainer');
    if (container) container.style.display = 'none';
}

function selectMember(id, name, code, mobile, email) {
    document.getElementById('memberIdInput').value = id;
    document.getElementById('selectedMemberName').textContent = name;
    document.getElementById('selectedMemberCode').textContent = code;
    document.getElementById('selectedMemberContact').innerHTML = `<i class="fas fa-phone me-1"></i> ${mobile} &bull; ${email}`;

    const container = document.getElementById('memberResultsContainer');
    if (container) container.style.display = 'none';

    document.getElementById('memberSelectedCard').classList.remove('d-none');
    document.getElementById('memberSearchBox').classList.add('d-none');
}

function clearMemberSelection() {
    document.getElementById('memberIdInput').value = '';
    document.getElementById('memberSelectedCard').classList.add('d-none');
    document.getElementById('memberSearchBox').classList.remove('d-none');
    
    const input = document.getElementById('memberSearchInput');
    input.value = '';
    const container = document.getElementById('memberResultsContainer');
    if (container) container.style.display = 'none';
    input.focus();
}

// ============================================================
// BOOK SEARCH & SELECTION LOGIC
// ============================================================
function renderBookList(list) {
    const container = document.getElementById('bookResultsList');
    if (!container) return;

    if (!list || list.length === 0) {
        container.innerHTML = '<div class="p-3 text-muted text-center"><i class="fas fa-book-open me-1"></i> No matching book titles found.</div>';
        return;
    }

    let html = '';
    list.forEach(b => {
        const availCount = parseInt(b.available_copies) || 0;
        const badgeClass = availCount > 0 ? 'bg-success' : 'bg-secondary';
        const badgeText = availCount > 0 ? `${availCount} Available` : '0 Copies';

        html += `
            <a href="javascript:void(0)" class="list-group-item list-group-item-action searchable-item p-2 d-flex justify-content-between align-items-center" onclick="selectBook(${b.id}, '${escapeJs(b.name)}', '${escapeJs(b.book_code)}', '${escapeJs(b.author_name || 'N/A')}', ${availCount})">
                <div class="me-2">
                    <strong class="font-serif text-dark d-block">${escapeHtml(b.name)}</strong>
                    <small class="text-muted">Author: ${escapeHtml(b.author_name || 'N/A')}</small>
                </div>
                <div class="text-end flex-shrink-0">
                    <span class="badge bg-dark mb-1 d-block">${escapeHtml(b.book_code)}</span>
                    <span class="badge ${badgeClass}">${badgeText}</span>
                </div>
            </a>
        `;
    });
    container.innerHTML = html;
}

function onBookSearchFocus() {
    const input = document.getElementById('bookSearchInput');
    const container = document.getElementById('bookResultsContainer');
    if (input && container && input.value.trim().length > 0) {
        container.style.display = 'block';
    }
}

function onBookSearchInput(query) {
    const container = document.getElementById('bookResultsContainer');
    query = query.trim().toLowerCase();

    if (query.length === 0) {
        if (container) container.style.display = 'none';
        return;
    }

    if (container) container.style.display = 'block';

    // Instant local filter first
    const localFiltered = booksData.filter(b => 
        (b.name && b.name.toLowerCase().includes(query)) ||
        (b.book_code && b.book_code.toLowerCase().includes(query)) ||
        (b.author_name && b.author_name.toLowerCase().includes(query)) ||
        (b.isbn && b.isbn.toLowerCase().includes(query))
    );
    renderBookList(localFiltered);

    // Debounced AJAX search for large remote catalogs
    clearTimeout(bookDebounceTimer);
    if (query.length >= 2) {
        bookDebounceTimer = setTimeout(() => {
            fetch('?action=search_books&q=' + encodeURIComponent(query))
                .then(res => res.json())
                .then(data => {
                    if (Array.isArray(data) && container && container.style.display !== 'none') {
                        renderBookList(data);
                    }
                })
                .catch(() => {});
        }, 250);
    }
}

function resetBookSearch() {
    document.getElementById('bookSearchInput').value = '';
    const container = document.getElementById('bookResultsContainer');
    if (container) container.style.display = 'none';
}

function selectBook(id, name, code, author, availableCopies) {
    document.getElementById('bookIdInput').value = id;
    document.getElementById('selectedBookName').textContent = name;
    document.getElementById('selectedBookCode').textContent = code;
    document.getElementById('selectedBookAuthor').textContent = 'Author: ' + author;
    
    const availBadge = document.getElementById('selectedBookAvailable');
    if (availableCopies > 0) {
        availBadge.className = 'badge bg-success';
        availBadge.textContent = availableCopies + ' Available Copy(ies)';
    } else {
        availBadge.className = 'badge bg-warning text-dark';
        availBadge.textContent = 'No Available Copies';
    }

    const container = document.getElementById('bookResultsContainer');
    if (container) container.style.display = 'none';

    document.getElementById('bookSelectedCard').classList.remove('d-none');
    document.getElementById('bookSearchBox').classList.add('d-none');

    // Automatically load the barcodes for this selected book
    fetchAvailableCopies(id);
}

function clearBookSelection() {
    document.getElementById('bookIdInput').value = '';
    document.getElementById('bookSelectedCard').classList.add('d-none');
    document.getElementById('bookSearchBox').classList.remove('d-none');

    const input = document.getElementById('bookSearchInput');
    input.value = '';
    const container = document.getElementById('bookResultsContainer');
    if (container) container.style.display = 'none';
    input.focus();

    // Clear barcode dropdown
    const copySelect = document.getElementById('copySelect');
    const copyNotice = document.getElementById('copyNotice');
    copySelect.innerHTML = '<option value="">-- Select Barcode Copy --</option>';
    copyNotice.className = 'mt-2';
    copyNotice.innerHTML = '<div class="alert alert-info py-2 small mb-0"><i class="fas fa-info-circle me-1"></i> Please select a Book Title above to view available copy barcodes.</div>';
}

// ============================================================
// DYNAMIC BARCODE COPIES LOADER
// ============================================================
function fetchAvailableCopies(bookId) {
    const copySelect = document.getElementById('copySelect');
    const copyNotice = document.getElementById('copyNotice');

    if (!bookId || bookId <= 0) {
        copySelect.innerHTML = '<option value="">-- Select Barcode Copy --</option>';
        copyNotice.className = 'mt-2';
        copyNotice.innerHTML = '<div class="alert alert-info py-2 small mb-0"><i class="fas fa-info-circle me-1"></i> Please select a Book Title above to view available copy barcodes.</div>';
        return;
    }

    copySelect.innerHTML = '<option value="">Loading available barcodes...</option>';

    fetch('?action=get_copies&book_id=' + encodeURIComponent(bookId))
        .then(response => response.json())
        .then(copies => {
            copySelect.innerHTML = '<option value="">-- Select Barcode Copy --</option>';
            if (copies.length > 0) {
                copies.forEach((cp, idx) => {
                    const opt = document.createElement('option');
                    opt.value = cp.id;
                    opt.textContent = `Barcode: ${cp.barcode} | Location: ${cp.shelf} / ${cp.rack} (${cp.copy_code})`;
                    // Auto-select first available copy for convenience
                    if (idx === 0) opt.selected = true;
                    copySelect.appendChild(opt);
                });
                copyNotice.className = 'mt-2 d-none';
                copyNotice.innerHTML = '';
            } else {
                copyNotice.className = 'mt-2';
                copyNotice.innerHTML = '<div class="alert alert-warning py-2 small mb-0"><i class="fas fa-exclamation-triangle me-1"></i> No physical copies are currently AVAILABLE for this title.</div>';
            }
        })
        .catch(err => {
            copyNotice.className = 'mt-2';
            copyNotice.innerHTML = '<div class="alert alert-danger py-2 small mb-0"><i class="fas fa-exclamation-circle me-1"></i> Could not fetch copies. Please try again.</div>';
        });
}

// ============================================================
// SANITIZATION HELPERS
// ============================================================
function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, "&amp;")
               .replace(/</g, "&lt;")
               .replace(/>/g, "&gt;")
               .replace(/"/g, "&quot;")
               .replace(/'/g, "&#039;");
}

function escapeJs(text) {
    if (!text) return '';
    return text.replace(/'/g, "\\'").replace(/"/g, '\\"');
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
