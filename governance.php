<?php
$pageTitle = "Governance - About Us";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card sayak-card">
                <div class="card-header bg-maroon text-white fw-bold" style="background-color: #7A0C0C;">
                    About Us
                </div>
                <ul class="list-group list-group-flush">
                    <a href="<?= BASE_URL ?>our-journey.php" class="list-group-item list-group-item-action">Our Journey</a>
                    <a href="<?= BASE_URL ?>governing-body.php" class="list-group-item list-group-item-action">Governing Body</a>
                    <a href="<?= BASE_URL ?>governance.php" class="list-group-item list-group-item-action active fw-bold" style="background-color: #7A0C0C; border-color: #7A0C0C;">Governance</a>
                    <a href="<?= BASE_URL ?>academic-collaborations.php" class="list-group-item list-group-item-action">Academic Collaborations</a>
                </ul>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border">
                <h1 class="section-title">Institutional Governance</h1>
                <p class="text-secondary"><?= escape(get_setting('governance_lead', 'Standards of transparency, financial accountability, and operational integrity.')) ?></p>
                <hr class="my-4">

                <div class="mb-4">
                    <h4 class="font-serif fw-bold text-maroon" style="color: #7A0C0C;"><i class="fas fa-balance-scale me-2"></i> <?= escape(get_setting('gov_comp_title', 'Regulatory Compliance')) ?></h4>
                    <p><?= nl2br(escape(get_setting('gov_comp_desc', 'SAYAK LIBRARY operates under Registration No. ' . get_setting('registration_no', 'SL/WB/2023/8892') . ' in full compliance with public society registration acts and West Bengal library management standards.'))) ?></p>
                </div>

                <div class="mb-4">
                    <h4 class="font-serif fw-bold text-maroon" style="color: #7A0C0C;"><i class="fas fa-receipt me-2"></i> <?= escape(get_setting('gov_audit_title', 'Cash Audit & Financial Receipts')) ?></h4>
                    <p><?= nl2br(escape(get_setting('gov_audit_desc', 'All membership fees, renewals, and fine collections are recorded with unique system transaction codes (e.g. SL-TXN-000001) and audited quarterly by independent chartered accountants.'))) ?></p>
                </div>

                <div class="mb-4">
                    <h4 class="font-serif fw-bold text-maroon" style="color: #7A0C0C;"><i class="fas fa-lock me-2"></i> <?= escape(get_setting('gov_sec_title', 'Digital Data Security')) ?></h4>
                    <p><?= nl2br(escape(get_setting('gov_sec_desc', 'We enforce strict data protection policies. Member credentials are stored using password_hash(), digital PDFs are protected against unauthorized redistribution, and user activity is logged via secure audit channels.'))) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
