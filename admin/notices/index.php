<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

// Strict Authorization Enforcement (Requirement 89)
if (!can_manage_notices()) {
    header("HTTP/1.1 403 Forbidden");
    require_once ROOT_PATH . 'errors/403.php';
    exit();
}

$db = getDB();
$errors = [];

// Handle Add / Edit Notice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_notice'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token mismatch.";
    } else {
        $noticeId = (int)($_POST['notice_id'] ?? 0);
        $title = sanitize_input($_POST['title'] ?? '');
        $shortDesc = sanitize_input($_POST['short_description'] ?? '');
        $fullDesc = sanitize_input($_POST['full_description'] ?? '');
        $pubDate = $_POST['publish_date'] ?? date('Y-m-d');
        $expDate = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;
        $priority = sanitize_input($_POST['priority'] ?? 'Normal');
        $status = sanitize_input($_POST['status'] ?? 'Published');

        if (empty($title)) $errors[] = "Notice Title is required.";
        if (empty($shortDesc)) $errors[] = "Short Description is required.";
        if (empty($fullDesc)) $errors[] = "Full Description is required.";

        if (empty($errors)) {
            if ($noticeId > 0) {
                // Update
                $stmt = $db->prepare("
                    UPDATE notices 
                    SET title = ?, short_description = ?, full_description = ?, publish_date = ?, expiry_date = ?, priority = ?, status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$title, $shortDesc, $fullDesc, $pubDate, $expDate, $priority, $status, $noticeId]);
                log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Edit Notice', 'Notices', "Notice ID: {$noticeId}");
                set_flash_message('success', 'Notice updated successfully.');
            } else {
                // Insert
                $stmt = $db->prepare("
                    INSERT INTO notices (title, short_description, full_description, publish_date, expiry_date, priority, status, created_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$title, $shortDesc, $fullDesc, $pubDate, $expDate, $priority, $status, $_SESSION['user_id']]);
                log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Add Notice', 'Notices', "Title: {$title}");
                set_flash_message('success', 'Notice created and published successfully.');
            }
            header("Location: " . BASE_URL . "admin/notices/index.php");
            exit();
        }
    }
}

// Handle Delete Notice
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $delId = (int)$_POST['delete_id'];
        $db->prepare("DELETE FROM notices WHERE id = ?")->execute([$delId]);
        log_audit_action($_SESSION['user_id'], 'SUPER_ADMIN', 'Delete Notice', 'Notices', "Deleted Notice ID: {$delId}");
        set_flash_message('info', 'Notice deleted.');
        header("Location: " . BASE_URL . "admin/notices/index.php");
        exit();
    }
}

// Fetch all notices
$notices = $db->query("
    SELECT n.*, u.full_name AS author_name 
    FROM notices n 
    JOIN users u ON n.created_by = u.id 
    ORDER BY n.id DESC
")->fetchAll();

$pageTitle = "Notice Board Management";
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="section-title mb-1">Notice Board Management</h2>
                    <p class="text-muted small mb-0">Super Admin Notice Management: Create, edit, set priority, publish, unpublish, or delete public bulletins.</p>
                </div>
                <button type="button" class="btn btn-maroon btn-sm font-serif" data-bs-toggle="modal" data-bs-target="#noticeModal" onclick="resetNoticeForm();" style="background-color: #7A0C0C;">
                    <i class="fas fa-plus me-1"></i> Add New Notice
                </button>
            </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $err): ?>
                    <li><?= escape($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card sayak-card">
        <div class="card-body p-0">
            <?php if (!empty($notices)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Priority</th>
                                <th>Notice Title</th>
                                <th>Publish Date</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                                <th>Author</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notices as $n): ?>
                                <tr>
                                    <td>
                                        <?php if ($n['priority'] === 'Urgent'): ?>
                                            <span class="badge bg-danger">Urgent</span>
                                        <?php elseif ($n['priority'] === 'Important'): ?>
                                            <span class="badge bg-warning text-dark">Important</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Normal</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="font-serif text-dark d-block"><?= escape($n['title']) ?></strong>
                                        <small class="text-muted text-truncate d-block" style="max-width: 300px;"><?= escape($n['short_description']) ?></small>
                                    </td>
                                    <td><small><?= format_date($n['publish_date']) ?></small></td>
                                    <td><small><?= $n['expiry_date'] ? format_date($n['expiry_date']) : 'Indefinite' ?></small></td>
                                    <td>
                                        <?php if ($n['status'] === 'Published'): ?>
                                            <span class="badge bg-success">Published</span>
                                        <?php elseif ($n['status'] === 'Draft'): ?>
                                            <span class="badge bg-secondary">Draft</span>
                                        <?php else: ?>
                                            <span class="badge bg-dark"><?= escape($n['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?= escape($n['author_name']) ?></small></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-maroon" onclick='editNotice(<?= json_encode($n) ?>)'>
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <form action="" method="POST" onsubmit="return confirm('Delete this notice?');" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                <input type="hidden" name="delete_id" value="<?= $n['id'] ?>">
                                                <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="p-5 text-center text-muted">No notices created yet.</div>
            <?php endif; ?>
        </div>
    </div>
        </div>
    </div>
</div>

<!-- Modal: Add / Edit Notice -->
<div class="modal fade" id="noticeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white" style="background-color: #7A0C0C;">
                <h5 class="modal-title font-serif" id="noticeModalTitle">Add Notice Bulletin</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                    <input type="hidden" name="notice_id" id="notice_id" value="0">

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Notice Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control" placeholder="Headline title" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Short Summary Description <span class="text-danger">*</span></label>
                        <textarea name="short_description" id="short_description" class="form-control" rows="2" placeholder="Brief 1-2 sentence preview" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Full Bulletin Details <span class="text-danger">*</span></label>
                        <textarea name="full_description" id="full_description" class="form-control" rows="5" placeholder="Full announcement text..." required></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Publish Date</label>
                            <input type="date" name="publish_date" id="publish_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Expiry Date (Optional)</label>
                            <input type="date" name="expiry_date" id="expiry_date" class="form-control" placeholder="Auto-disappears after expiry">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Priority Level</label>
                            <select name="priority" id="priority" class="form-select">
                                <option value="Normal">Normal Notice</option>
                                <option value="Important">Important Bulletin</option>
                                <option value="Urgent">Urgent Announcement</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="Published">Published (Publicly Visible)</option>
                                <option value="Draft">Draft (Internal)</option>
                                <option value="Unpublished">Unpublished</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="save_notice" class="btn btn-maroon font-serif" style="background-color: #7A0C0C;">Save Notice</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetNoticeForm() {
    document.getElementById('notice_id').value = '0';
    document.getElementById('title').value = '';
    document.getElementById('short_description').value = '';
    document.getElementById('full_description').value = '';
    document.getElementById('publish_date').value = '<?= date('Y-m-d') ?>';
    document.getElementById('expiry_date').value = '';
    document.getElementById('priority').value = 'Normal';
    document.getElementById('status').value = 'Published';
    document.getElementById('noticeModalTitle').innerText = 'Add Notice Bulletin';
}

function editNotice(data) {
    document.getElementById('notice_id').value = data.id;
    document.getElementById('title').value = data.title;
    document.getElementById('short_description').value = data.short_description;
    document.getElementById('full_description').value = data.full_description;
    document.getElementById('publish_date').value = data.publish_date;
    document.getElementById('expiry_date').value = data.expiry_date || '';
    document.getElementById('priority').value = data.priority;
    document.getElementById('status').value = data.status;
    document.getElementById('noticeModalTitle').innerText = 'Edit Notice Bulletin';
    var modal = new bootstrap.Modal(document.getElementById('noticeModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
