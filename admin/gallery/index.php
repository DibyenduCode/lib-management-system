<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

require_role('SUPER_ADMIN');

$db = getDB();
$errors = [];

// Handle Image Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token mismatch.";
    } else {
        $title = sanitize_input($_POST['title'] ?? '');
        $description = sanitize_input($_POST['description'] ?? '');
        $category = sanitize_input($_POST['category'] ?? 'Facilities');

        if (empty($title)) $errors[] = "Title is required.";

        if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['gallery_image']['tmp_name'];
            $origName = basename($_FILES['gallery_image']['name']);
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowed)) {
                $errors[] = "Invalid image file format. Allowed: JPG, PNG, WEBP.";
            } else {
                $imgFilename = 'gallery_' . time() . '_' . rand(100, 999) . '.' . $ext;
                if (!is_dir(UPLOAD_GALLERY_DIR)) {
                    mkdir(UPLOAD_GALLERY_DIR, 0755, true);
                }

                if (move_uploaded_file($tmpName, UPLOAD_GALLERY_DIR . $imgFilename)) {
                    $db->prepare("INSERT INTO gallery (title, description, image_path, category, is_published, created_at) VALUES (?, ?, ?, ?, 1, NOW())")->execute([$title, $description, $imgFilename, $category]);
                    set_flash_message('success', 'Gallery image uploaded and published.');
                    header("Location: " . BASE_URL . "admin/gallery/index.php");
                    exit();
                } else {
                    $errors[] = "Failed to upload image file.";
                }
            }
        } else {
            $errors[] = "Please select an image file.";
        }
    }
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $delId = (int)$_POST['delete_id'];
        $db->prepare("DELETE FROM gallery WHERE id = ?")->execute([$delId]);
        set_flash_message('info', 'Gallery photo deleted.');
        header("Location: " . BASE_URL . "admin/gallery/index.php");
        exit();
    }
}

$photos = $db->query("SELECT * FROM gallery ORDER BY id DESC")->fetchAll();

$pageTitle = "Gallery Management";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row g-4">
        <!-- Sidebar Navigation Column -->
        <div class="col-lg-3 col-xl-2 d-print-none">
            <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>
        </div>

        <!-- Main Content Area Column -->
        <div class="col-lg-9 col-xl-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="section-title mb-1">Gallery Photo Management</h2>
                    <p class="text-muted small mb-0">Upload photos, set titles/descriptions, and manage published library photos.</p>
                </div>
                <button type="button" class="btn btn-maroon btn-sm font-serif" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="fas fa-upload me-1"></i> Upload Photo
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

            <div class="row g-4">
                <?php foreach ($photos as $img): ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card sayak-card h-100 border-0 shadow-sm overflow-hidden">
                            <div style="height: 180px; background-color: #EAEAEA;" class="d-flex align-items-center justify-content-center">
                                <?php if (!empty($img['image_path']) && file_exists(ROOT_PATH . 'uploads/gallery/' . $img['image_path'])): ?>
                                    <img src="<?= BASE_URL ?>uploads/gallery/<?= escape($img['image_path']) ?>" class="w-100 h-100" style="object-fit: cover;" alt="<?= escape($img['title']) ?>">
                                <?php else: ?>
                                    <i class="fas fa-image fa-3x text-secondary"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-3">
                                <span class="badge bg-light text-dark border mb-1"><?= escape($img['category']) ?></span>
                                <h6 class="fw-bold text-dark mb-1 font-serif"><?= escape($img['title']) ?></h6>
                                <small class="text-muted d-block text-truncate"><?= escape($img['description']) ?></small>
                                <form action="" method="POST" onsubmit="return confirm('Delete photo?');" class="mt-2 text-end">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    <input type="hidden" name="delete_id" value="<?= $img['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Upload Gallery -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-maroon text-white" style="background-color: #7A0C0C;">
                <h5 class="modal-title font-serif">Upload Gallery Photo</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Photo Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="Photo caption title" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Category</label>
                        <select name="category" class="form-select">
                            <option value="Facilities">Facilities</option>
                            <option value="Collections">Collections</option>
                            <option value="Events">Events</option>
                            <option value="Programs">Programs</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Photo Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Select Image File <span class="text-danger">*</span></label>
                        <input type="file" name="gallery_image" class="form-control" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="upload_image" class="btn btn-maroon" style="background-color: #7A0C0C;">Upload & Publish</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
