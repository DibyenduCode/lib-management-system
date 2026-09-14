<?php
$pageTitle = "Our Journey - About Us";
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
                    <a href="<?= BASE_URL ?>our-journey.php" class="list-group-item list-group-item-action active fw-bold" style="background-color: #7A0C0C; border-color: #7A0C0C;">Our Journey</a>
                    <a href="<?= BASE_URL ?>governing-body.php" class="list-group-item list-group-item-action">Governing Body</a>
                    <a href="<?= BASE_URL ?>governance.php" class="list-group-item list-group-item-action">Governance</a>
                    <a href="<?= BASE_URL ?>academic-collaborations.php" class="list-group-item list-group-item-action">Academic Collaborations</a>
                </ul>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border">
                <h1 class="section-title">Our Journey & Legacy</h1>
                <p class="lead text-secondary"><?= escape(get_setting('journey_lead', 'Tracing three decades of educational dedication, literature preservation, and community empowerment.')) ?></p>
                <hr class="my-4">

                <h4 class="font-serif fw-bold text-maroon" style="color: #7A0C0C;"><?= escape(get_setting('journey_1995_title', '1995: The Foundation')) ?></h4>
                <p><?= nl2br(escape(get_setting('journey_1995_desc', 'SAYAK LIBRARY was founded in 1995 by a group of visionary scholars and educators in Kolkata with a initial collection of 1,200 books. The vision was simple yet powerful: to create an accessible repository of learning for all citizens regardless of economic background.'))) ?></p>

                <h4 class="font-serif fw-bold text-maroon mt-4" style="color: #7A0C0C;"><?= escape(get_setting('journey_2008_title', '2008: Academic Expansion')) ?></h4>
                <p><?= nl2br(escape(get_setting('journey_2008_desc', 'With increasing enrollment of competitive examination aspirants and school students, the library introduced dedicated Higher Secondary and Civil Services prep wings, partnering with major Indian publishers.'))) ?></p>

                <h4 class="font-serif fw-bold text-maroon mt-4" style="color: #7A0C0C;"><?= escape(get_setting('journey_present_title', 'Present Day: Digital & Physical Integration')) ?></h4>
                <p><?= nl2br(escape(get_setting('journey_present_desc', 'Today, Sayak Library houses over 25,000 physical volumes and an integrated Digital PDF Library, serving thousands of registered members with barcoded lending and in-browser e-learning access.'))) ?></p>

                <div class="p-4 bg-light rounded border-start border-4 border-warning mt-4">
                    <h5 class="fw-bold mb-1"><i class="fas fa-quote-left text-warning me-2"></i> Our Core Mission</h5>
                    <p class="mb-0 text-muted"><?= escape(get_setting('journey_mission', 'To foster lifelong learning, preserve Bengali and Indian literary heritage, and equip students with modern digital resources in a quiet, modern academic environment.')) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
