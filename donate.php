<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token mismatch.";
    }

    $donorName = sanitize_input($_POST['donor_name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $mobile = sanitize_input($_POST['mobile'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $donationType = sanitize_input($_POST['donation_type'] ?? 'Money');
    $amount = (float)($_POST['amount'] ?? 0);
    $message = sanitize_input($_POST['message'] ?? '');

    if (empty($donorName)) $errors[] = "Full Name is required.";
    if (!$email) $errors[] = "A valid Email address is required.";
    if (empty($mobile)) $errors[] = "Mobile number is required.";

    if (empty($errors)) {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO donations (donor_name, email, mobile, address, donation_type, amount, message, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'New', NOW())
            ");
            $stmt->execute([$donorName, $email, $mobile, $address, $donationType, $amount, $message]);

            notify_role('SUPER_ADMIN', 'New Donation Submission', "Donation submitted by {$donorName} ({$donationType})");
            log_audit_action(null, 'Public', 'Donation Form Submit', 'Donations', "Donor: {$donorName}, Type: {$donationType}");

            set_flash_message('success', "Thank you for your generous pledge! Our administrative representative will contact you shortly.");
            header("Location: " . BASE_URL . "donate.php");
            exit();
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

$pageTitle = "Donate Now";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border">
                <div class="text-center mb-4">
                    <i class="fas fa-hand-holding-heart fa-3x text-warning mb-2"></i>
                    <h2 class="section-title text-center"><?= escape(get_setting('donate_appeal_title', 'Donate to Sayak Library')) ?></h2>
                    <p class="text-secondary"><?= nl2br(escape(get_setting('donate_appeal_desc', 'Your contributions directly support book restoration, student scholarships, rare manuscript preservation, and e-learning resources.'))) ?></p>
                </div>

                <!-- Official Direct Transfer Info Box -->
                <div class="card bg-light border-0 mb-4 shadow-sm">
                    <div class="card-body p-3 p-md-4">
                        <h6 class="fw-bold font-serif text-maroon mb-2" style="color: #7A0C0C;"><i class="fas fa-university me-2"></i> Official Direct Bank & UPI Transfer Details</h6>
                        <div class="row g-2 small text-secondary">
                            <div class="col-sm-6">
                                <strong>Bank Name:</strong> <?= escape(get_setting('donate_bank_name', 'State Bank of India (College Street Branch)')) ?>
                            </div>
                            <div class="col-sm-6">
                                <strong>Account Number:</strong> <code><?= escape(get_setting('donate_account_no', '38491029384')) ?></code>
                            </div>
                            <div class="col-sm-6">
                                <strong>IFSC Code:</strong> <code><?= escape(get_setting('donate_ifsc', 'SBIN0001234')) ?></code>
                            </div>
                            <div class="col-sm-6">
                                <strong>UPI ID:</strong> <span class="badge bg-success-subtle text-success border border-success-subtle"><?= escape(get_setting('donate_upi_id', 'sayaklibrary@sbi')) ?></span>
                            </div>
                        </div>
                    </div>
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

                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="donor_name" class="form-control" placeholder="e.g. Dr. Subir Ray" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mobile Number <span class="text-danger">*</span></label>
                            <input type="tel" name="mobile" class="form-control" placeholder="+91 Mobile number" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Donation Type <span class="text-danger">*</span></label>
                            <select name="donation_type" class="form-select" id="donationTypeSelect" required>
                                <option value="Money">Financial Contribution (Money)</option>
                                <option value="Books">Book Contribution (Physical Volumes)</option>
                                <option value="Other">Equipment / Infrastructure / Other</option>
                            </select>
                        </div>
                        <div class="col-md-12" id="amountFieldGroup">
                            <label class="form-label fw-bold">Pledged Amount (₹ INR)</label>
                            <input type="number" name="amount" class="form-control" placeholder="e.g. 1000" min="0" step="100">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Full Address</label>
                            <textarea name="address" class="form-control" rows="2" placeholder="Street, City, Pincode"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Message / Details of Donated Items</label>
                            <textarea name="message" class="form-control" rows="4" placeholder="If donating books or equipment, please describe the items and quantities..."></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gold btn-lg w-100 mt-4 font-serif text-dark fw-bold">
                        <i class="fas fa-heart me-2"></i> Submit Donation Pledge
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
