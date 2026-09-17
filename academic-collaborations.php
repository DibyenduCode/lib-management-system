<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();
$pageTitle = "Academic Collaborations & Institutional MOUs | Dakshineswar Shayak";
require_once __DIR__ . '/includes/header.php';

// Fetch all active collaborations from the database
$collaborations = $db->query("SELECT * FROM academic_collaborations WHERE status = 'Active' ORDER BY sort_order ASC, id ASC")->fetchAll();

// Determine currently selected collaboration
$selectedId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$currentCollab = null;
if ($selectedId > 0) {
    foreach ($collaborations as $c) {
        if ((int)$c['id'] === $selectedId) {
            $currentCollab = $c;
            break;
        }
    }
}
if (!$currentCollab && !empty($collaborations)) {
    $currentCollab = $collaborations[0];
}

$pageLead = get_setting('collab_lead', 'Dakshineswar Shayak Library partners actively with leading academic institutions, universities, and colleges to promote library usage, textbook accessibility, and student empowerment throughout greater Kolkata and North 24 Parganas.');

if ($currentCollab) {
    $hasLogo = !empty($currentCollab['partner_logo']) && file_exists(ROOT_PATH . 'uploads/documents/' . $currentCollab['partner_logo']);
    $logoUrl = $hasLogo ? BASE_URL . 'uploads/documents/' . $currentCollab['partner_logo'] : null;

    $mouPdfName = $currentCollab['mou_pdf'] ?? '';
    $mouRelativePath = !empty($mouPdfName) ? 'uploads/documents/' . $mouPdfName : '';
    $mouAbsolutePath = !empty($mouRelativePath) ? ROOT_PATH . $mouRelativePath : '';
    $mouExists = !empty($mouRelativePath) && file_exists($mouAbsolutePath);
    $mouUrl = $mouExists ? BASE_URL . $mouRelativePath : '';
    $mouFileSize = $mouExists ? round(filesize($mouAbsolutePath) / (1024 * 1024), 2) : 0;
}
?>

<div class="container py-5">
    <div class="row g-4">
        <!-- Sidebar Navigation Column -->
        <div class="col-lg-3">
            <div class="card sayak-card mb-4 border-0 shadow-sm overflow-hidden">
                <div class="card-header bg-maroon text-white font-serif py-3 fw-bold" style="background-color: #7A0C0C;">
                    <i class="fas fa-landmark me-2"></i> About Us
                </div>
                <div class="list-group list-group-flush">
                    <a href="<?= BASE_URL ?>our-journey.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-history me-2 text-muted"></i> Our Journey
                    </a>
                    <a href="<?= BASE_URL ?>governing-body.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users-cog me-2 text-muted"></i> Governing Body
                    </a>
                    <a href="<?= BASE_URL ?>governance.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-balance-scale me-2 text-muted"></i> Governance
                    </a>
                    <a href="<?= BASE_URL ?>academic-collaborations.php" class="list-group-item list-group-item-action active fw-bold text-white" style="background-color: #7A0C0C; border-color: #7A0C0C;">
                        <i class="fas fa-handshake me-2"></i> Academic Collaborations
                    </a>
                </div>
            </div>

            <?php if (count($collaborations) > 1): ?>
                <!-- Multi-Partner Institutional Switcher -->
                <div class="card sayak-card mb-4 border-0 shadow-sm overflow-hidden">
                    <div class="card-header bg-white font-serif py-2 px-3 fw-bold small text-maroon border-bottom" style="color: #7A0C0C;">
                        <i class="fas fa-university me-1"></i> Partner Institutions (<?= count($collaborations) ?>)
                    </div>
                    <div class="list-group list-group-flush small">
                        <?php foreach ($collaborations as $p): ?>
                            <a href="<?= BASE_URL ?>academic-collaborations.php?id=<?= $p['id'] ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-2 <?= $p['id'] == $currentCollab['id'] ? 'active text-white' : '' ?>" style="<?= $p['id'] == $currentCollab['id'] ? 'background-color: #7A0C0C; border-color: #7A0C0C;' : '' ?>">
                                <i class="fas fa-handshake <?= $p['id'] == $currentCollab['id'] ? 'text-white' : 'text-maroon' ?>"></i>
                                <span class="text-truncate fw-semibold"><?= escape($p['partner_name']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($currentCollab): ?>
                <!-- Partner Profile Summary Card -->
                <div class="card border-0 shadow-sm p-3 mb-4 text-center" style="background-color: #FFFBF7; border-top: 4px solid #7A0C0C !important;">
                    <?php if ($hasLogo): ?>
                        <img src="<?= $logoUrl ?>" alt="<?= escape($currentCollab['partner_name']) ?>" class="mx-auto mb-2 rounded-circle border shadow-sm" style="width: 80px; height: 80px; object-fit: contain; background: #fff; padding: 3px;">
                    <?php else: ?>
                        <div class="rounded-circle bg-light border shadow-sm mx-auto mb-2 d-flex align-items-center justify-content-center" style="width: 70px; height: 70px;">
                            <i class="fas fa-university text-maroon fa-2x" style="color: #7A0C0C;"></i>
                        </div>
                    <?php endif; ?>

                    <h6 class="font-serif fw-bold text-dark mb-1" style="font-size: 14px;"><?= escape($currentCollab['partner_name']) ?></h6>
                    <?php if (!empty($currentCollab['partner_subtitle'])): ?>
                        <p class="small text-muted mb-2"><?= escape($currentCollab['partner_subtitle']) ?></p>
                    <?php endif; ?>
                    <div class="badge bg-light text-dark border mb-2" style="font-size: 11px;">Official MOU Partner</div>
                    <hr class="my-2">
                    <div class="small text-start">
                        <div class="mb-1"><strong>Status:</strong> <span class="badge bg-success-subtle text-success border border-success-subtle"><?= escape($currentCollab['status']) ?> Collaboration</span></div>
                        <?php if (!empty($currentCollab['signed_date'])): ?>
                            <div class="mb-1"><strong>Signed:</strong> <?= date('d M Y', strtotime($currentCollab['signed_date'])) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($currentCollab['validity_period'])): ?>
                            <div class="mb-1"><strong>Validity:</strong> <?= escape($currentCollab['validity_period']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($currentCollab['mou_ref_no'])): ?>
                            <div><strong>Ref No:</strong> <code class="small"><?= escape($currentCollab['mou_ref_no']) ?></code></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Direct Download Card -->
                <?php if ($mouExists): ?>
                    <div class="card border-0 shadow-sm p-3 text-center bg-light">
                        <i class="fas fa-file-signature fa-3x text-maroon mb-2" style="color: #7A0C0C;"></i>
                        <h6 class="fw-bold font-serif mb-1">Official Signed MOU</h6>
                        <p class="small text-muted mb-3">Govt. Agreement Document (<?= $mouFileSize ?> MB PDF)</p>
                        <a href="<?= $mouUrl ?>" download="<?= preg_replace('/[^a-zA-Z0-9_-]/', '_', $currentCollab['partner_name']) ?>_MOU.pdf" class="btn btn-maroon btn-sm w-100 fw-bold" style="background-color: #7A0C0C;">
                            <i class="fas fa-download me-1"></i> Download Signed MOU
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Main Content Column -->
        <div class="col-lg-9">
            <?php if (!$currentCollab): ?>
                <div class="bg-white p-5 rounded-3 shadow-sm border text-center">
                    <i class="fas fa-handshake-slash fa-4x text-muted mb-3"></i>
                    <h3 class="font-serif fw-bold text-dark">Institutional Collaborations</h3>
                    <p class="text-secondary mb-4">
                        Dakshineswar Shayak Library is currently formalizing institutional academic partnerships and bilateral resource-sharing agreements.
                    </p>
                    <a href="<?= BASE_URL ?>" class="btn btn-maroon font-serif" style="background-color: #7A0C0C;">
                        <i class="fas fa-home me-1"></i> Back to Homepage
                    </a>
                </div>
            <?php else: ?>
                <!-- Header Banner -->
                <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div>
                            <span class="badge font-serif px-3 py-1 mb-2 text-white" style="background-color: #7A0C0C; font-size: 12px;">
                                <i class="fas fa-graduation-cap me-1"></i> Academic Outreach
                            </span>
                            <h1 class="section-title mb-1">Academic Collaborations & Institutional MOUs</h1>
                        </div>
                        <?php if ($mouExists): ?>
                            <a href="#mou-viewer" class="btn btn-outline-maroon btn-sm font-serif fw-bold">
                                <i class="fas fa-file-contract me-1"></i> View Signed MOU Document
                            </a>
                        <?php endif; ?>
                    </div>
                    <p class="text-secondary lead" style="font-size: 1.05rem;">
                        <?= nl2br(escape($pageLead)) ?>
                    </p>

                    <!-- Partner Selector Tabs for Multi-Partner Setup -->
                    <?php if (count($collaborations) > 1): ?>
                        <div class="d-flex gap-2 flex-wrap mt-3 pt-3 border-top">
                            <span class="small fw-bold text-muted d-flex align-items-center me-1"><i class="fas fa-filter me-1"></i> Partner Institutions:</span>
                            <?php foreach ($collaborations as $p): ?>
                                <a href="<?= BASE_URL ?>academic-collaborations.php?id=<?= $p['id'] ?>" class="btn btn-sm <?= $p['id'] == $currentCollab['id'] ? 'btn-maroon text-white fw-bold' : 'btn-outline-secondary' ?>" style="<?= $p['id'] == $currentCollab['id'] ? 'background-color: #7A0C0C;' : '' ?>">
                                    <?= escape($p['partner_name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Featured Partnership Card -->
                    <div class="p-4 rounded border mt-4" style="background: linear-gradient(135deg, #FFFDFB 0%, #FFF5F5 100%); border-left: 5px solid #7A0C0C !important;">
                        <div class="row align-items-center g-3">
                            <?php if ($hasLogo): ?>
                                <div class="col-md-2 text-center">
                                    <img src="<?= $logoUrl ?>" alt="<?= escape($currentCollab['partner_name']) ?>" class="img-fluid rounded-circle border shadow-sm" style="max-width: 85px; background: #fff; padding: 4px;">
                                </div>
                            <?php endif; ?>
                            <div class="<?= $hasLogo ? 'col-md-10' : 'col-12' ?>">
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <h4 class="font-serif fw-bold text-dark mb-0"><?= escape($currentCollab['partner_name']) ?></h4>
                                    <span class="badge text-white font-serif" style="background-color: #7A0C0C; font-size: 11px;">Official MOU Partner</span>
                                </div>
                                <?php if (!empty($currentCollab['partner_subtitle'])): ?>
                                    <p class="text-muted small mb-2">
                                        <em><?= escape($currentCollab['partner_subtitle']) ?></em>
                                    </p>
                                <?php endif; ?>
                                <p class="small text-secondary mb-0">
                                    <?= !empty($currentCollab['summary_text']) ? nl2br(escape($currentCollab['summary_text'])) : 'Dakshineswar Shayak Library is proud to maintain active bilateral academic cooperation with ' . escape($currentCollab['partner_name']) . ', providing inter-library reading facilities, syllabus textbook access, and student educational support.' ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 1: EMBEDDED SIGNED MOU VIEWER -->
                <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border mb-4" id="mou-viewer">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-3 border-bottom">
                        <div>
                            <h4 class="font-serif fw-bold mb-1 text-dark">
                                <i class="fas fa-file-contract text-maroon me-2" style="color: #7A0C0C;"></i> <?= escape($currentCollab['mou_title']) ?>
                            </h4>
                            <p class="text-muted small mb-0">
                                Official bilateral agreement between <?= escape($currentCollab['partner_name']) ?> & Dakshineswar Shayak Library.
                                <?php if (!empty($currentCollab['mou_ref_no'])): ?>
                                    (Deed Ref: <code><?= escape($currentCollab['mou_ref_no']) ?></code>)
                                <?php endif; ?>
                            </p>
                        </div>
                        <?php if ($mouExists): ?>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-dark btn-sm font-serif" data-bs-toggle="modal" data-bs-target="#mouFullscreenModal">
                                    <i class="fas fa-expand me-1"></i> Full Screen
                                </button>
                                <a href="<?= $mouUrl ?>" target="_blank" class="btn btn-outline-maroon btn-sm font-serif">
                                    <i class="fas fa-external-link-alt me-1"></i> Open Tab
                                </a>
                                <a href="<?= $mouUrl ?>" download="<?= preg_replace('/[^a-zA-Z0-9_-]/', '_', $currentCollab['partner_name']) ?>_MOU.pdf" class="btn btn-maroon btn-sm font-serif text-white fw-bold" style="background-color: #7A0C0C;">
                                    <i class="fas fa-download me-1"></i> Download PDF
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Signatories & Stamp Info Strip -->
                    <?php if (!empty($currentCollab['partner_signatory']) || !empty($currentCollab['library_signatory'])): ?>
                        <div class="row g-2 mb-3">
                            <?php if (!empty($currentCollab['partner_signatory'])): ?>
                                <div class="col-md-6">
                                    <div class="p-2 px-3 border rounded bg-light small h-100">
                                        <strong class="text-dark d-block"><i class="fas fa-pen-nib text-maroon me-1" style="color: #7A0C0C;"></i> First Party Signatory:</strong>
                                        <span class="text-secondary"><?= escape($currentCollab['partner_signatory']) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($currentCollab['library_signatory'])): ?>
                                <div class="col-md-6">
                                    <div class="p-2 px-3 border rounded bg-light small h-100">
                                        <strong class="text-dark d-block"><i class="fas fa-pen-nib text-maroon me-1" style="color: #7A0C0C;"></i> Second Party Signatory:</strong>
                                        <span class="text-secondary"><?= escape($currentCollab['library_signatory']) ?></span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($currentCollab['witness_details'])): ?>
                                <div class="col-12">
                                    <div class="p-2 px-3 border rounded bg-light small text-muted">
                                        <i class="fas fa-users me-1 text-primary"></i> <strong>Witnesses & Coordination:</strong> <?= escape($currentCollab['witness_details']) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($mouExists): ?>
                        <!-- Embedded PDF Viewer Frame -->
                        <div class="position-relative rounded overflow-hidden border shadow-inner pdf-embed-container">
                            <iframe 
                                src="<?= $mouUrl ?>#toolbar=1&navpanes=1&scrollbar=1" 
                                title="MOU Agreement Document" 
                                width="100%" 
                                style="border: none; display: block;"
                            >
                                <div class="p-5 text-center text-white">
                                    <i class="fas fa-file-pdf fa-4x text-danger mb-3"></i>
                                    <h5>PDF Document Preview</h5>
                                    <p class="small text-light mb-4">Your browser may not support inline PDF viewing. You can download the complete signed agreement directly.</p>
                                    <a href="<?= $mouUrl ?>" download class="btn btn-warning fw-bold px-4">
                                        <i class="fas fa-download me-1"></i> Download MOU Agreement PDF
                                    </a>
                                </div>
                            </iframe>
                        </div>

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-2">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i> Complete signed bilateral agreement with official institutional seals and endorsements.
                            </small>
                            <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 text-maroon fw-bold" data-bs-toggle="modal" data-bs-target="#mouFullscreenModal" style="color: #7A0C0C;">
                                <i class="fas fa-search-plus me-1"></i> Enlarge Document in Fullscreen Modal
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-light border text-center py-4">
                            <i class="fas fa-file-pdf text-danger fa-3x mb-2"></i>
                            <h6 class="font-serif fw-bold text-dark">Signed Deed Document in Process</h6>
                            <p class="small text-muted mb-0">The signed deed document will be uploaded by the administration shortly.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- SECTION 2: PURPOSE & CORE OBJECTIVES OF THE MOU -->
                <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <div class="rounded p-2 text-white" style="background-color: #7A0C0C; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <div>
                            <h4 class="font-serif fw-bold mb-0 text-dark">Purpose & Strategic Objectives</h4>
                            <small class="text-muted">Formalized terms of the institutional library services collaboration (Clause I)</small>
                        </div>
                    </div>

                    <?php
                    $objectivesText = trim($currentCollab['objectives'] ?? '');
                    if (!empty($objectivesText)):
                        $objItems = array_filter(array_map('trim', explode("\n", $objectivesText)));
                        $icons = ['fa-book-open', 'fa-search-plus', 'fa-users-class', 'fa-graduation-cap', 'fa-award', 'fa-hands-helping'];
                    ?>
                        <div class="row g-3">
                            <?php foreach (array_values($objItems) as $idx => $obj): ?>
                                <div class="col-md-4">
                                    <div class="p-3 border rounded h-100 sayak-card" style="background-color: #FFFDFB;">
                                        <div class="rounded-circle p-2 bg-light text-maroon border mb-2 text-center" style="width: 40px; height: 40px; color: #7A0C0C;">
                                            <i class="fas <?= $icons[$idx % count($icons)] ?>"></i>
                                        </div>
                                        <h6 class="font-serif fw-bold text-dark mb-2">Objective <?= $idx + 1 ?></h6>
                                        <p class="small text-muted mb-0"><?= nl2br(escape($obj)) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="p-3 border rounded h-100 sayak-card" style="background-color: #FFFDFB;">
                                    <div class="rounded-circle p-2 bg-light text-maroon border mb-2 text-center" style="width: 40px; height: 40px; color: #7A0C0C;">
                                        <i class="fas fa-book-open"></i>
                                    </div>
                                    <h6 class="font-serif fw-bold text-dark mb-2">Promote Library Culture</h6>
                                    <p class="small text-muted mb-0">Educate college students and library users on the indispensable role of physical and digital libraries in higher education.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded h-100 sayak-card" style="background-color: #FFFDFB;">
                                    <div class="rounded-circle p-2 bg-light text-maroon border mb-2 text-center" style="width: 40px; height: 40px; color: #7A0C0C;">
                                        <i class="fas fa-search-plus"></i>
                                    </div>
                                    <h6 class="font-serif fw-bold text-dark mb-2">Resource Utilization</h6>
                                    <p class="small text-muted mb-0">Increase awareness and active circulation of specialized academic syllabi, reference titles, and rare collections.</p>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 border rounded h-100 sayak-card" style="background-color: #FFFDFB;">
                                    <div class="rounded-circle p-2 bg-light text-maroon border mb-2 text-center" style="width: 40px; height: 40px; color: #7A0C0C;">
                                        <i class="fas fa-users-class"></i>
                                    </div>
                                    <h6 class="font-serif fw-bold text-dark mb-2">Informed Student Body</h6>
                                    <p class="small text-muted mb-0">Foster a vibrant, community-centered academic environment supporting undergraduate researchers and scholars.</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- SECTION 3: OPERATIONAL SCOPE & TERMS OF COLLABORATION -->
                <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border mb-4">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <div class="rounded p-2 text-white" style="background-color: #7A0C0C; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div>
                            <h4 class="font-serif fw-bold mb-0 text-dark">Scope & Operational Modalities</h4>
                            <small class="text-muted">Agreed working parameters under the Memorandum of Understanding</small>
                        </div>
                    </div>

                    <?php
                    $scopeText = trim($currentCollab['scope_modalities'] ?? '');
                    if (!empty($scopeText)):
                        $scopeItems = array_filter(array_map('trim', explode("\n", $scopeText)));
                        $letters = range('A', 'Z');
                    ?>
                        <div class="list-group list-group-flush mb-3">
                            <?php foreach (array_values($scopeItems) as $idx => $sc): ?>
                                <div class="list-group-item px-0 py-3 border-bottom">
                                    <div class="d-flex align-items-start gap-3">
                                        <span class="badge rounded-circle p-2 text-white" style="background-color: #7A0C0C; min-width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 12px;">
                                            <?= $letters[$idx % 26] ?>
                                        </span>
                                        <div>
                                            <p class="small text-secondary mb-0 fw-semibold" style="line-height: 1.6; font-size: 14px;"><?= nl2br(escape($sc)) ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush mb-3">
                            <div class="list-group-item px-0 py-3 border-bottom">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="badge rounded-circle p-2 text-white" style="background-color: #7A0C0C; min-width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 12px;">A</span>
                                    <div>
                                        <h6 class="font-serif fw-bold text-dark mb-1">Mutual Reading Visits & Library Usage</h6>
                                        <p class="small text-muted mb-0">Scheduled reciprocal visits on designated days of the week by library members and students to study, read, and consult reference materials on site.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item px-0 py-3 border-bottom">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="badge rounded-circle p-2 text-white" style="background-color: #7A0C0C; min-width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 12px;">B</span>
                                    <div>
                                        <h6 class="font-serif fw-bold text-dark mb-1">Empowering Undergraduate Scholars</h6>
                                        <p class="small text-muted mb-0">Dedicated reading hall access provided to students of partner institutions, ensuring a safe, supportive, and resourceful study haven.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item px-0 py-3 border-bottom">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="badge rounded-circle p-2 text-white" style="background-color: #7A0C0C; min-width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 12px;">C</span>
                                    <div>
                                        <h6 class="font-serif fw-bold text-dark mb-1">Textbook Donations & Repository Expansion</h6>
                                        <p class="small text-muted mb-0">Partner institutions share curriculum textbooks, syllabi reference works, and academic volumes to Dakshineswar Shayak Library based on availability.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="list-group-item px-0 py-3">
                                <div class="d-flex align-items-start gap-3">
                                    <span class="badge rounded-circle p-2 text-white" style="background-color: #7A0C0C; min-width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 12px;">D</span>
                                    <div>
                                        <h6 class="font-serif fw-bold text-dark mb-1">Mutual Institutional Coordination</h6>
                                        <p class="small text-muted mb-0">Both parties assign dedicated liaison coordinators to review activities, organize orientation visits, and strengthen community relationships.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- SECTION 4: BROADER ACADEMIC COLLABORATION INITIATIVES -->
                <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border">
                    <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                        <div class="rounded p-2 text-white" style="background-color: #7A0C0C; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-university"></i>
                        </div>
                        <div>
                            <h4 class="font-serif fw-bold mb-0 text-dark">Broader Academic Outreach & Linkages</h4>
                            <small class="text-muted">Extending scholarly opportunities across schools, colleges, and civil service aspirants</small>
                        </div>
                    </div>

                    <div class="row g-4 mt-1">
                        <div class="col-md-6">
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-circle p-2 bg-light text-maroon border flex-shrink-0" style="color: #7A0C0C; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-book-reader fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="font-serif fw-bold text-dark mb-1">Undergraduate Textbook Bank</h6>
                                    <p class="small text-muted mb-0">Extensive textbook reserve covering B.A., B.Sc., B.Com., and specialized honours curricula for colleges across the University of Calcutta and West Bengal State University.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-circle p-2 bg-light text-maroon border flex-shrink-0" style="color: #7A0C0C; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-award fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="font-serif fw-bold text-dark mb-1">Competitive Exam Resource Desk</h6>
                                    <p class="small text-muted mb-0">Dedicated guidance repository with test series, general studies volumes, and current affairs journals for WBCS, UPSC, SSC, and NET/SET aspirants.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-circle p-2 bg-light text-maroon border flex-shrink-0" style="color: #7A0C0C; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-chalkboard-teacher fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="font-serif fw-bold text-dark mb-1">Local School & College Support</h6>
                                    <p class="small text-muted mb-0">Historical ties with institutions such as Ariadaha Kalachand Highschool, Shishu Bikash School, and regional educational trusts to aid young learners.</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex align-items-start gap-3">
                                <div class="rounded-circle p-2 bg-light text-maroon border flex-shrink-0" style="color: #7A0C0C; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-laptop-code fa-lg"></i>
                                </div>
                                <div>
                                    <h6 class="font-serif fw-bold text-dark mb-1">Digital Academic Repository</h6>
                                    <p class="small text-muted mb-0">Digitization of rare Bengali literature, academic journals, and curriculum notes accessible securely through the library's local portal.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($currentCollab) && $mouExists): ?>
<!-- ============================================================ -->
<!-- MODAL: FULLSCREEN SIGNED MOU PDF VIEWER                       -->
<!-- ============================================================ -->
<div class="modal fade" id="mouFullscreenModal" tabindex="-1" aria-labelledby="mouFullscreenModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content border-0">
            <div class="modal-header text-white" style="background-color: #7A0C0C;">
                <h5 class="modal-title font-serif" id="mouFullscreenModalLabel">
                    <i class="fas fa-file-signature me-2"></i> <?= escape($currentCollab['mou_title']) ?> — <?= escape($currentCollab['partner_name']) ?>
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= $mouUrl ?>" download="<?= preg_replace('/[^a-zA-Z0-9_-]/', '_', $currentCollab['partner_name']) ?>_MOU.pdf" class="btn btn-warning btn-sm fw-bold">
                        <i class="fas fa-download me-1"></i> Download Signed MOU
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0" style="background-color: #525659;">
                <iframe 
                    src="<?= $mouUrl ?>#toolbar=1&navpanes=1&scrollbar=1" 
                    title="Fullscreen MOU Agreement Document" 
                    width="100%" 
                    height="100%" 
                    style="border: none; min-height: 90vh;"
                ></iframe>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
