<?php
$pageTitle = "Academic Collaborations - About Us";
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
                    <a href="<?= BASE_URL ?>governance.php" class="list-group-item list-group-item-action">Governance</a>
                    <a href="<?= BASE_URL ?>academic-collaborations.php" class="list-group-item list-group-item-action active fw-bold" style="background-color: #7A0C0C; border-color: #7A0C0C;">Academic Collaborations</a>
                </ul>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border">
                <h1 class="section-title">Academic Collaborations</h1>
                <p class="text-secondary"><?= escape(get_setting('collab_lead', 'Partnerships with universities, research institutes, and educational publishers.')) ?></p>
                <hr class="my-4">

                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="p-3 border rounded bg-light h-100">
                            <h5 class="fw-bold font-serif text-maroon" style="color: #7A0C0C;"><i class="fas fa-university me-2"></i> <?= escape(get_setting('collab_1_title', 'University Inter-Library Loan')) ?></h5>
                            <p class="small text-muted mb-0"><?= nl2br(escape(get_setting('collab_1_desc', 'Collaborative borrowing privileges with regional universities for postgraduate and doctoral research scholars.'))) ?></p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 border rounded bg-light h-100">
                            <h5 class="fw-bold font-serif text-maroon" style="color: #7A0C0C;"><i class="fas fa-book-reader me-2"></i> <?= escape(get_setting('collab_2_title', 'Competitive Exam Academies')) ?></h5>
                            <p class="small text-muted mb-0"><?= nl2br(escape(get_setting('collab_2_desc', 'Resource sharing agreements providing updated test series and reference books for WBCS and Civil Services aspirants.'))) ?></p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 border rounded bg-light h-100">
                            <h5 class="fw-bold font-serif text-maroon" style="color: #7A0C0C;"><i class="fas fa-laptop-code me-2"></i> <?= escape(get_setting('collab_3_title', 'National Digital Library Partner')) ?></h5>
                            <p class="small text-muted mb-0"><?= nl2br(escape(get_setting('collab_3_desc', 'Access integration with open educational repositories and digital learning archives.'))) ?></p>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 border rounded bg-light h-100">
                            <h5 class="fw-bold font-serif text-maroon" style="color: #7A0C0C;"><i class="fas fa-print me-2"></i> <?= escape(get_setting('collab_4_title', 'Publishing Houses')) ?></h5>
                            <p class="small text-muted mb-0"><?= nl2br(escape(get_setting('collab_4_desc', 'Direct procurement partnerships with Ananda Publishers, Oxford University Press, S. Chand, and McGraw Hill.'))) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
