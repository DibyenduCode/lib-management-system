<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Our Journey - About Us | Dakshineswar Shayak Library";
require_once __DIR__ . '/includes/header.php';

$defaultJourneyStory = <<<EOT
From Shishu Vikash School’s Cabinet to a whole building at Nepal Chandra Chatterjee Street housing over 5000 books, Dakshineswar Shayak Library has come a long way in these past 28 years. This library began as a joint effort of a few bachelors in their twenties, who had the sole goal of delivering quality education to students regardless of their financial backgrounds.

So here we are, almost 3 decades later, a friendly, community-focused library to support undergraduate and postgraduate students by providing easy access to textbooks. Shayak was founded with the heartfelt support of people like you to ease the burden of expensive textbooks for students and their families, so they can focus on their studies without the stress of high costs. Through all the challenges, we’ve been here for students promoting equal educational opportunities for all.

In the early days when the founding members were in search of a space to house the library, Abhijit Ray and Avantika Ray were the ones who allowed them to use the ground floor of Shishu Vikash School. They gave many advice on various working of an organization, arranged for funding too.

Fast-forward a few years. From “Anandabazar Patrika’s” (আনন্দবাজার পত্রিকা) “Kolkatar Korcha” (কলকাতার কড়চা) section, Ganesh Bhattacharya and Mira Bhattacharya found out about the library. They approached us with generous funds in loving memory of their late son Niladri Bhattacharya. They have continued to support us to date through funds or any other means possible, even in their old age.

It is also mention-worthy that one of the most notable teachers of Ariadaha Kalachand Highschool, Dr. Rathin Mitra was also a very close well-wisher of us. Visiting us in his free time to give advice and admiring and motivating our dedication to give back to the society.

Eventually, we outgrew our space at Shishu Bikash School. Then, by sheer luck, Divyendu Vishnu and his wife, Srimati Leela Vishnu, offered us a whole building at 11 Nepal Chandra Chatterjee Street. On February 13, 2000, we inaugurated our permanent library location and it also marked the formation of Dakshineswar Shayak Library Trustee Committee. Since then, support from various community members, local leaders, and generations of volunteers has kept our library alive and thriving. And with their very help the library has been renovated to a two-storeyed well organized and decorated instituition.

Thanks to our generous donors, we’re able to keep this initiative growing. Join us in empowering students and building a brighter, more informed future! Your support helps us provide essential resources to students and uplifts our community as a whole. By contributing, you’re making a lasting impact on education and inspiring positive change.

Thank you for being part of our journey!
EOT;

$journeyLead = get_setting('journey_lead', 'From Shishu Vikash School’s Cabinet to 11 Nepal Chandra Chatterjee Street — 28+ Years of Dedication & Service');
$journeyContent = get_setting('journey_story_content', $defaultJourneyStory);
$journeyMission = get_setting('journey_mission', 'Join us in empowering students and building a brighter, more informed future! Your support helps us provide essential resources to students and uplifts our community as a whole.');

// Split content by blank lines / paragraphs
$paragraphs = preg_split('/\n\s*\n/', trim($journeyContent));
?>

<div class="container py-5">
    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-lg-3">
            <div class="card sayak-card mb-4 border-0 shadow-sm overflow-hidden">
                <div class="card-header bg-maroon text-white font-serif py-3 fw-bold" style="background-color: #7A0C0C;">
                    <i class="fas fa-landmark me-2"></i> About Us
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_URL ?>our-journey.php" class="list-group-item list-group-item-action active fw-bold text-white" style="background-color: #7A0C0C; border-color: #7A0C0C;">
                        <i class="fas fa-history me-2"></i> Our Journey
                    </a>
                    <a href="<?= BASE_URL ?>governing-body.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users me-2 text-muted"></i> Governing Body
                    </a>
                    <a href="<?= BASE_URL ?>governance.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-balance-scale me-2 text-muted"></i> Governance & Compliance
                    </a>
                    <a href="<?= BASE_URL ?>academic-collaborations.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-graduation-cap me-2 text-muted"></i> Academic Collaborations
                    </a>
                </div>
            </div>

            <!-- Quick Key Milestones Box -->
            <div class="card sayak-card border-0 shadow-sm p-3 bg-light">
                <h6 class="fw-bold font-serif text-maroon mb-3 border-bottom pb-2" style="color: #7A0C0C;">
                    <i class="fas fa-flag me-1"></i> Historical Milestones
                </h6>
                <div class="timeline-quick-list small">
                    <div class="mb-3 ps-3 border-start border-3 border-danger">
                        <strong class="text-dark d-block">1995 • The Genesis</strong>
                        <span class="text-muted">Started by bachelors in their 20s with a cabinet at Shishu Vikash School.</span>
                    </div>
                    <div class="mb-3 ps-3 border-start border-3 border-warning">
                        <strong class="text-dark d-block">Media Recognition</strong>
                        <span class="text-muted">Anandabazar Patrika's "Kolkatar Korcha" feature & generous patron support.</span>
                    </div>
                    <div class="mb-3 ps-3 border-start border-3 border-primary">
                        <strong class="text-dark d-block">Feb 13, 2000 • Permanent Home</strong>
                        <span class="text-muted">11 Nepal Chandra Chatterjee St building offered by Divyendu & Leela Vishnu; Trustee Committee formed.</span>
                    </div>
                    <div class="ps-3 border-start border-3 border-success">
                        <strong class="text-dark d-block">Present Day</strong>
                        <span class="text-muted">Two-storeyed modern facility housing 5,000+ textbooks for UG & PG scholars.</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Column -->
        <div class="col-lg-9">
            <!-- Admin Notice / Quick Edit Link -->
            <?php if (isset($_SESSION['role_code']) && $_SESSION['role_code'] === 'SUPER_ADMIN'): ?>
                <div class="alert alert-info border-start border-4 border-info shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 py-2">
                    <div class="small">
                        <strong><i class="fas fa-edit me-1 text-primary"></i> Super Admin Quick Edit:</strong> You can edit and update this Journey narrative directly from the CMS.
                    </div>
                    <a href="<?= BASE_URL ?>admin/cms/index.php?tab=institutional" class="btn btn-primary btn-sm fw-bold">
                        <i class="fas fa-sliders-h me-1"></i> Edit Journey in CMS
                    </a>
                </div>
            <?php endif; ?>

            <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border sayak-card">
                <!-- Header Banner -->
                <div class="mb-4 pb-3 border-bottom">
                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-2">
                        <i class="fas fa-book-reader me-1"></i> 28+ YEARS OF INSPIRING SERVICE
                    </span>
                    <h1 class="font-serif fw-bold text-maroon mb-2" style="color: #7A0C0C;">Our Journey & Legacy</h1>
                    <p class="lead text-secondary mb-0" style="font-size: 1.15rem;"><?= escape($journeyLead) ?></p>
                </div>

                <!-- Highlight Metrics Ribbon -->
                <div class="row g-3 text-center mb-5">
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <div class="display-6 fw-bold text-maroon font-serif" style="color: #7A0C0C;">28+</div>
                            <small class="text-muted fw-bold">Years of Service</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <div class="display-6 fw-bold text-success font-serif">5,000+</div>
                            <small class="text-muted fw-bold">Books & Volumes</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <div class="display-6 fw-bold text-primary font-serif">2000</div>
                            <small class="text-muted fw-bold">Permanent Location</small>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <div class="display-6 fw-bold text-warning font-serif">2</div>
                            <small class="text-muted fw-bold">Storeyed Building</small>
                        </div>
                    </div>
                </div>

                <!-- Narrative Story Stream -->
                <div class="journey-story text-dark" style="font-size: 1.05rem; line-height: 1.85;">
                    <?php 
                    $pIndex = 0;
                    foreach ($paragraphs as $para):
                        $para = trim($para);
                        if (empty($para)) continue;
                        $pIndex++;

                        // Special quote styling for Anandabazar Patrika paragraph
                        if (strpos($para, 'Anandabazar Patrika') !== false || strpos($para, 'আনন্দবাজার পত্রিকা') !== false): 
                    ?>
                        <div class="p-4 my-4 rounded-3 border-start border-4 border-warning bg-light shadow-sm">
                            <div class="d-flex">
                                <i class="fas fa-newspaper fa-2x text-warning me-3 mt-1 flex-shrink-0"></i>
                                <div>
                                    <h6 class="fw-bold text-dark mb-2 font-serif">Media Spotlight & Loving Benefactors</h6>
                                    <p class="mb-0 text-secondary"><?= nl2br(escape($para)) ?></p>
                                </div>
                            </div>
                        </div>

                    <?php 
                        // Special milestone highlight for February 13, 2000 / Permanent location
                        elseif (strpos($para, 'February 13, 2000') !== false || strpos($para, 'Nepal Chandra Chatterjee Street') !== false): 
                    ?>
                        <div class="p-4 my-4 rounded-3 border-start border-4 border-maroon bg-light shadow-sm" style="border-left-color: #7A0C0C !important;">
                            <div class="d-flex">
                                <i class="fas fa-building fa-2x text-maroon me-3 mt-1 flex-shrink-0" style="color: #7A0C0C;"></i>
                                <div>
                                    <h6 class="fw-bold text-dark mb-2 font-serif">A Permanent Sanctuary: 11 Nepal Chandra Chatterjee Street</h6>
                                    <p class="mb-0 text-secondary"><?= nl2br(escape($para)) ?></p>
                                </div>
                            </div>
                        </div>

                    <?php 
                        // Dr. Rathin Mitra mentor tribute
                        elseif (strpos($para, 'Dr. Rathin Mitra') !== false || strpos($para, 'Ariadaha Kalachand Highschool') !== false): 
                    ?>
                        <div class="p-4 my-4 rounded-3 border-start border-4 border-primary bg-light shadow-sm">
                            <div class="d-flex">
                                <i class="fas fa-chalkboard-teacher fa-2x text-primary me-3 mt-1 flex-shrink-0"></i>
                                <div>
                                    <h6 class="fw-bold text-dark mb-2 font-serif">Guiding Light & Academic Well-Wisher</h6>
                                    <p class="mb-0 text-secondary"><?= nl2br(escape($para)) ?></p>
                                </div>
                            </div>
                        </div>

                    <?php 
                        // Opening lead paragraph styling
                        elseif ($pIndex === 1): 
                    ?>
                        <p class="fs-5 text-dark fw-normal mb-4 font-serif" style="line-height: 1.8;">
                            <span class="display-6 text-maroon fw-bold float-start me-2 lh-1" style="color: #7A0C0C; font-family: serif;">F</span>
                            <?= nl2br(escape(ltrim($para, 'Ff'))) ?>
                        </p>

                    <?php 
                        // Closing paragraph styling
                        elseif ($para === 'Thank you for being part of our journey!'): 
                    ?>
                        <div class="text-center my-4 py-3">
                            <h4 class="font-serif fw-bold text-maroon mb-0" style="color: #7A0C0C;">
                                <i class="fas fa-heart text-danger me-2"></i> <?= escape($para) ?>
                            </h4>
                        </div>

                    <?php else: ?>
                        <p class="mb-4 text-secondary"><?= nl2br(escape($para)) ?></p>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Mission & Donor Appeal Call to Action -->
                <div class="card border-0 mt-5 p-4 rounded-3 text-white overflow-hidden shadow" style="background: linear-gradient(135deg, #1E293B 0%, #0F172A 100%);">
                    <div class="row align-items-center g-3">
                        <div class="col-lg-8">
                            <h5 class="fw-bold text-warning font-serif mb-2">
                                <i class="fas fa-hands-helping me-2"></i> Support Our Ongoing Mission
                            </h5>
                            <p class="small text-white-50 mb-0">
                                <?= escape($journeyMission) ?>
                            </p>
                        </div>
                        <div class="col-lg-4 text-lg-end">
                            <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                                <a href="<?= BASE_URL ?>donate.php" class="btn btn-warning fw-bold px-3 py-2 text-dark shadow-sm">
                                    <i class="fas fa-hand-holding-heart me-1"></i> Support Shayak
                                </a>
                                <a href="<?= BASE_URL ?>usership.php" class="btn btn-outline-light px-3 py-2">
                                    <i class="fas fa-id-card me-1"></i> Join as Member
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
