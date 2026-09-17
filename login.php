<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    $role = $_SESSION['role_code'];
    if ($role === 'SUPER_ADMIN') {
        header("Location: " . BASE_URL . "admin/dashboard.php");
    } elseif ($role === 'LIBRARIAN') {
        header("Location: " . BASE_URL . "librarian/dashboard.php");
    } else {
        header("Location: " . BASE_URL . "member/dashboard.php");
    }
    exit();
}

$errorMsg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errorMsg = "Security token validation failed. Please try again.";
    } else {
        $email = sanitize_input($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $result = login_user($email, $password);
        if ($result['success']) {
            $roleCode = $_SESSION['role_code'];
            if ($roleCode === 'SUPER_ADMIN') {
                header("Location: " . BASE_URL . "admin/dashboard.php");
            } elseif ($roleCode === 'LIBRARIAN') {
                header("Location: " . BASE_URL . "librarian/dashboard.php");
            } else {
                header("Location: " . BASE_URL . "member/dashboard.php");
            }
            exit();
        } else {
            $errorMsg = $result['message'];
        }
    }
}

$pageTitle = "Account Log In";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border">
                <div class="text-center mb-4">
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-2" style="width:64px; height:64px;">
                        <i class="fas fa-lock fa-2x text-maroon" style="color: #7A0C0C;"></i>
                    </div>
                    <h2 class="section-title text-center mb-1">Account Login</h2>
                    <p class="text-muted small">Sign in to access your Library Portal.</p>
                </div>

                <?php if (!empty($errorMsg)): ?>
                    <div class="alert alert-danger py-2 small">
                        <i class="fas fa-exclamation-triangle me-1"></i> <?= escape($errorMsg) ?>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="name@example.com" value="<?= escape($_POST['email'] ?? '') ?>" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-key text-muted"></i></span>
                            <input type="password" name="password" id="loginPassword" class="form-control" placeholder="Enter password" required>
                            <button class="btn btn-outline-secondary" type="button" id="togglePasswordBtn" onclick="togglePasswordVisibility()" title="Show / Hide Password" aria-label="Toggle password visibility">
                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-maroon btn-lg w-100 py-3 font-serif fw-bold mt-2" style="background-color: #7A0C0C;">
                        <i class="fas fa-sign-in-alt me-2"></i> Log In to Portal
                    </button>
                </form>

                <div class="text-center mt-4">
                    <?php $isPdfMode = (get_setting('membership_mode', 'online') === 'pdf'); ?>
                    <small class="text-muted">Not registered yet? <a href="<?= BASE_URL ?>usership.php" class="fw-bold text-maroon" style="color: #7A0C0C;"><?= $isPdfMode ? 'Download Membership Form' : 'Apply for Usership' ?></a></small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePasswordVisibility() {
    const passwordInput = document.getElementById('loginPassword');
    const toggleIcon = document.getElementById('togglePasswordIcon');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
