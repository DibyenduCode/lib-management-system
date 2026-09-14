<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/notifications.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token mismatch.";
    }

    $name = sanitize_input($_POST['name'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone = sanitize_input($_POST['phone'] ?? '');
    $subject = sanitize_input($_POST['subject'] ?? '');
    $message = sanitize_input($_POST['message'] ?? '');

    if (empty($name)) $errors[] = "Name is required.";
    if (!$email) $errors[] = "A valid Email is required.";
    if (empty($message)) $errors[] = "Message cannot be empty.";

    if (empty($errors)) {
        try {
            $db = getDB();
            $stmt = $db->prepare("
                INSERT INTO contact_messages (name, email, phone, subject, message, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'New', NOW())
            ");
            $stmt->execute([$name, $email, $phone, $subject, $message]);

            notify_role('SUPER_ADMIN', 'New Contact Message', "Contact inquiry from {$name} ({$email})");
            set_flash_message('success', "Thank you for contacting Sayak Library. We will respond shortly.");
            header("Location: " . BASE_URL . "contact.php");
            exit();
        } catch (Exception $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

$pageTitle = "Get in Touch - Contact Us";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <!-- Contact Information Side -->
        <div class="col-lg-5">
            <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border h-100">
                <h2 class="section-title">Get in Touch</h2>
                <p class="text-secondary">Have a question about book availability, memberships, or donations? Contact our administrative desk.</p>
                <hr class="my-4">

                <div class="d-flex mb-4">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3 text-maroon" style="width:48px; height:48px; background-color:#7A0C0C; color:#D4AF37;">
                        <i class="fas fa-map-marker-alt fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 font-serif">Library Location</h6>
                        <p class="text-secondary small mb-0"><?= nl2br(escape(get_setting('address', '124 Academic Avenue, College Street, Kolkata, West Bengal - 700073'))) ?></p>
                    </div>
                </div>

                <div class="d-flex mb-4">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3 text-maroon" style="width:48px; height:48px; background-color:#7A0C0C; color:#D4AF37;">
                        <i class="fas fa-phone-alt fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 font-serif">Phone Helpline</h6>
                        <p class="text-secondary small mb-0"><?= escape(get_setting('phone', '+91 33 2241 8900 / +91 98300 12345')) ?></p>
                        <?php if (!empty(get_setting('phone_secondary'))): ?>
                            <p class="text-muted small mb-0 mt-1"><i class="fab fa-whatsapp text-success me-1"></i> Helpline: <?= escape(get_setting('phone_secondary')) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex mb-4">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3 text-maroon" style="width:48px; height:48px; background-color:#7A0C0C; color:#D4AF37;">
                        <i class="fas fa-envelope fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 font-serif">Email Support</h6>
                        <p class="text-secondary small mb-0"><?= escape(get_setting('email', 'info@sayaklibrary.org')) ?></p>
                        <?php if (!empty(get_setting('email_support'))): ?>
                            <p class="text-muted small mb-0 mt-1"><i class="fas fa-headset me-1 text-primary"></i> Support: <?= escape(get_setting('email_support')) ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="d-flex mb-4">
                    <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3 text-maroon" style="width:48px; height:48px; background-color:#7A0C0C; color:#D4AF37;">
                        <i class="fas fa-clock fa-lg"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1 font-serif">Opening Hours</h6>
                        <p class="text-secondary small mb-0"><?= escape(get_setting('opening_hours', 'Monday - Saturday: 9:00 AM - 7:00 PM | Sunday: Closed')) ?></p>
                    </div>
                </div>

                <!-- Google Map Embed Container -->
                <?php 
                $mapUrl = get_setting('map_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3684.128795764048!2d88.3638927!3d22.574343!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3a0277ab54a83b27%3A0xb36384a56828551!2sCollege%20St%2C%20Kolkata%2C%20West%20Bengal!5e0!3m2!1sen!2sin!4v1680000000000!5m2!1sen!2sin');
                ?>
                <div class="mt-4 rounded overflow-hidden shadow-sm border" style="height: 180px;">
                    <iframe src="<?= escape($mapUrl) ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
                </div>
            </div>
        </div>

        <!-- Contact Form Side -->
        <div class="col-lg-7">
            <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border">
                <h3 class="font-serif fw-bold text-maroon mb-3" style="color: #7A0C0C;">Send Us a Message</h3>

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
                            <label class="form-label fw-bold">Your Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Full name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" placeholder="+91 Mobile number">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Inquiry Subject</label>
                            <input type="text" name="subject" class="form-control" placeholder="Membership, Book Request...">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Message Details <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="5" placeholder="Write your inquiry here..." required></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-maroon btn-lg w-100 mt-4 font-serif" style="background-color: #7A0C0C;">
                        <i class="fas fa-paper-plane me-2"></i> Submit Inquiry
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
