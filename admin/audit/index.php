<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/permissions.php';

require_role('SUPER_ADMIN');

$db = getDB();

$search = sanitize_input($_GET['q'] ?? '');
$roleFilter = sanitize_input($_GET['role'] ?? 'All');

$whereClauses = [];
$params = [];

if (!empty($search)) {
    $whereClauses[] = "(action LIKE ? OR details LIKE ? OR target LIKE ? OR ip_address LIKE ?)";
    $term = "%{$search}%";
    $params = array_merge($params, [$term, $term, $term, $term]);
}

if ($roleFilter !== 'All') {
    $whereClauses[] = "user_role = ?";
    $params[] = $roleFilter;
}

$whereSql = "";
if (!empty($whereClauses)) {
    $whereSql = "WHERE " . implode(" AND ", $whereClauses);
}

$stmt = $db->prepare("SELECT * FROM audit_logs {$whereSql} ORDER BY id DESC LIMIT 200");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$pageTitle = "System Audit Logs";
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
                    <h2 class="section-title mb-1">System Audit Trail Logs</h2>
                    <p class="text-muted small mb-0">Security tracking for logins, book edits, PDF downloads, fine collections, and membership updates.</p>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="card sayak-card mb-4">
                <div class="card-body">
                    <form action="" method="GET" class="row g-2">
                        <div class="col-md-6">
                            <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by Action, Target, IP, or Details..." value="<?= escape($search) ?>">
                        </div>
                        <div class="col-md-4">
                            <select name="role" class="form-select form-select-sm">
                                <option value="All">All Roles</option>
                                <option value="SUPER_ADMIN" <?= $roleFilter === 'SUPER_ADMIN' ? 'selected' : '' ?>>Super Admin</option>
                                <option value="LIBRARIAN" <?= $roleFilter === 'LIBRARIAN' ? 'selected' : '' ?>>Librarian</option>
                                <option value="MEMBER" <?= $roleFilter === 'MEMBER' ? 'selected' : '' ?>>Member</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-maroon btn-sm w-100" style="background-color: #7A0C0C;">Filter Logs</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card sayak-card">
                <div class="card-body p-0">
                    <?php if (!empty($logs)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Role</th>
                                        <th>User ID</th>
                                        <th>Action Performed</th>
                                        <th>Target Entity</th>
                                        <th>IP Address</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $l): ?>
                                        <tr>
                                            <td><small><?= format_date($l['created_at']) ?></small></td>
                                            <td>
                                                <?php if ($l['role'] === 'SUPER_ADMIN'): ?>
                                                    <span class="badge bg-danger">Super Admin</span>
                                                <?php elseif ($l['role'] === 'LIBRARIAN'): ?>
                                                    <span class="badge bg-primary">Librarian</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary"><?= escape($l['role']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><small>#<?= $l['user_id'] ?: 'System' ?></small></td>
                                            <td><strong class="text-dark font-serif"><?= escape($l['action']) ?></strong></td>
                                            <td><small class="text-muted"><?= escape($l['target']) ?></small></td>
                                            <td><code><?= escape($l['ip_address']) ?></code></td>
                                            <td><small class="text-secondary"><?= escape($l['details']) ?></small></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-5 text-center text-muted">No audit logs matching search parameters.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
