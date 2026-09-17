<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Institutional Governance & Legal Constitution | Dakshineswar Shayak";
require_once __DIR__ . '/includes/header.php';

$pdfRelativePath = get_setting('governance_pdf', 'uploads/documents/memorandum_of_association_dakshineswar_shayak.pdf');
$pdfAbsolutePath = ROOT_PATH . $pdfRelativePath;
$pdfExists = !empty($pdfRelativePath) && file_exists($pdfAbsolutePath);
$pdfUrl = $pdfExists ? BASE_URL . $pdfRelativePath : BASE_URL . 'uploads/documents/memorandum_of_association_dakshineswar_shayak.pdf';
$pdfFileSize = $pdfExists ? round(filesize($pdfAbsolutePath) / (1024 * 1024), 2) : 4.61;

$regNo = get_setting('registration_no', 'S/87920 of 1997-1998');
$regDate = get_setting('reg_date', '27 August 1997');
$certCopyDate = get_setting('cert_copy_date', '03 July 2023');
$certRefNo = get_setting('cert_ref_no', '79AB 299217');
$regOffice = get_setting('registered_office', '11, Nepal Chandra Chatterjee Street, P.O. Ariadaha, Kolkata - 700 057');
$govLead = get_setting('governance_lead', 'Dakshineswar Shayak is a registered public educational and cultural institution governed strictly under the provisions of the West Bengal Societies Registration Act, 1961. Our institutional governance is founded on unwavering transparency, democratic oversight, and non-profit public service.');
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
                    <a href="<?= BASE_URL ?>our-journey.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-history me-2 text-muted"></i> Our Journey
                    </a>
                    <a href="<?= BASE_URL ?>governing-body.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-users-cog me-2 text-muted"></i> Governing Body
                    </a>
                    <a href="<?= BASE_URL ?>governance.php" class="list-group-item list-group-item-action active fw-bold text-white" style="background-color: #7A0C0C; border-color: #7A0C0C;">
                        <i class="fas fa-balance-scale me-2"></i> Governance
                    </a>
                    <a href="<?= BASE_URL ?>academic-collaborations.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-handshake me-2 text-muted"></i> Academic Collaborations
                    </a>
                </div>
            </div>

            <!-- Fast Facts Sidebar Card -->
            <div class="card border-0 shadow-sm p-3 mb-4" style="background-color: #FFFBF7; border-left: 4px solid #7A0C0C !important;">
                <h6 class="font-serif fw-bold text-maroon mb-2" style="color: #7A0C0C;">
                    <i class="fas fa-certificate me-1"></i> Legal Entity
                </h6>
                <p class="small text-muted mb-2">Registered under <strong>West Bengal Societies Registration Act, 1961</strong>.</p>
                <div class="small mb-1"><strong>Reg No:</strong> <code><?= escape($regNo) ?></code></div>
                <div class="small mb-1"><strong>Registered:</strong> <?= escape($regDate) ?></div>
                <div class="small mb-1"><strong>Certified Ref:</strong> <?= escape($certRefNo) ?></div>
                <div class="small text-muted mt-2 pt-2 border-top" style="font-size: 11px;">
                    <i class="fas fa-map-marker-alt me-1 text-danger"></i> <?= escape($regOffice) ?>
                </div>
            </div>

            <!-- Direct PDF Download Banner -->
            <div class="card border-0 shadow-sm p-3 text-center bg-light">
                <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                <h6 class="fw-bold font-serif mb-1">Official Memorandum</h6>
                <p class="small text-muted mb-3">Govt. Certified Copy of Constitution & Registration (<?= $pdfFileSize ?> MB)</p>
                <a href="<?= $pdfUrl ?>" download="Dakshineswar_Shayak_Memorandum_Of_Association.pdf" class="btn btn-maroon btn-sm w-100 fw-bold" style="background-color: #7A0C0C;">
                    <i class="fas fa-download me-1"></i> Download PDF
                </a>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="col-lg-9">
            <!-- Header Section -->
            <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border mb-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <span class="badge font-serif px-3 py-1 mb-2 text-white" style="background-color: #7A0C0C; font-size: 12px;">
                            <i class="fas fa-shield-alt me-1"></i> Constitutional Framework
                        </span>
                        <h1 class="section-title mb-1">Institutional Governance & Legal Constitution</h1>
                    </div>
                    <a href="#memorandum-viewer" class="btn btn-outline-maroon btn-sm font-serif fw-bold">
                        <i class="fas fa-file-contract me-1"></i> View Registered Deed
                    </a>
                </div>
                <p class="text-secondary lead" style="font-size: 1.05rem;">
                    <?= nl2br(escape($govLead)) ?>
                </p>

                <!-- Statutory Overview Grid -->
                <div class="row g-3 mt-3">
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 rounded border text-center h-100 bg-light">
                            <small class="text-muted d-block mb-1">Society Reg. No.</small>
                            <strong class="font-serif text-maroon" style="color: #7A0C0C; font-size: 1.05rem;"><?= escape($regNo) ?></strong>
                            <small class="d-block text-secondary" style="font-size: 11px;">West Bengal</small>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 rounded border text-center h-100 bg-light">
                            <small class="text-muted d-block mb-1">Registration Date</small>
                            <strong class="font-serif text-dark" style="font-size: 1.05rem;"><?= escape($regDate) ?></strong>
                            <small class="d-block text-secondary" style="font-size: 11px;">Govt. of West Bengal</small>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 rounded border text-center h-100 bg-light">
                            <small class="text-muted d-block mb-1">Certified Copy</small>
                            <strong class="font-serif text-dark" style="font-size: 1.05rem;"><?= escape($certCopyDate) ?></strong>
                            <small class="d-block text-secondary" style="font-size: 11px;">Registrar of Societies</small>
                        </div>
                    </div>
                    <div class="col-sm-6 col-md-3">
                        <div class="p-3 rounded border text-center h-100 bg-light">
                            <small class="text-muted d-block mb-1">Entity Character</small>
                            <strong class="font-serif text-success" style="font-size: 1.05rem;">Public Non-Profit</strong>
                            <small class="d-block text-secondary" style="font-size: 11px;">Welfare Society</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 1: OFFICIAL EMBEDDED MEMORANDUM PDF VIEWER -->
            <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border mb-4" id="memorandum-viewer">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 pb-3 border-bottom">
                    <div>
                        <h4 class="font-serif fw-bold mb-1 text-dark">
                            <i class="fas fa-file-signature text-maroon me-2" style="color: #7A0C0C;"></i> Registered Memorandum of Association & Certificate
                        </h4>
                        <p class="text-muted small mb-0">Official certified document issued by the Registrar of Firms, Societies & Non-Trading Corporations, West Bengal.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-dark btn-sm font-serif" data-bs-toggle="modal" data-bs-target="#pdfFullscreenModal">
                            <i class="fas fa-expand me-1"></i> Full Screen
                        </button>
                        <a href="<?= $pdfUrl ?>" target="_blank" class="btn btn-outline-maroon btn-sm font-serif">
                            <i class="fas fa-external-link-alt me-1"></i> Open Tab
                        </a>
                        <a href="<?= $pdfUrl ?>" download="Dakshineswar_Shayak_Memorandum_Of_Association.pdf" class="btn btn-maroon btn-sm font-serif text-white fw-bold" style="background-color: #7A0C0C;">
                            <i class="fas fa-download me-1"></i> Download
                        </a>
                    </div>
                </div>

                <!-- Document Badges Strip -->
                <div class="alert alert-light border d-flex align-items-center justify-content-between flex-wrap gap-2 py-2 px-3 mb-3">
                    <div class="small text-muted">
                        <i class="fas fa-stamp text-warning me-1"></i> <strong>Official Seal:</strong> Stamp Paper No. <code><?= escape($certRefNo) ?></code> | Delivered: <code><?= escape($certCopyDate) ?></code> | Original Reg: <code><?= escape($regDate) ?></code>
                    </div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Verified Certified Copy</span>
                </div>

                <!-- Embedded PDF Viewer Frame -->
                <div class="position-relative rounded overflow-hidden border shadow-inner pdf-embed-container">
                    <iframe 
                        src="<?= $pdfUrl ?>#toolbar=1&navpanes=1&scrollbar=1" 
                        title="Memorandum of Association - Dakshineswar Shayak" 
                        width="100%" 
                        style="border: none; display: block;"
                    >
                        <div class="p-5 text-center text-white">
                            <i class="fas fa-file-pdf fa-4x text-danger mb-3"></i>
                            <h5>PDF Preview Not Available In Browser</h5>
                            <p class="small text-light mb-4">Your browser may not support inline PDF viewing. Please download the certified copy directly.</p>
                            <a href="<?= $pdfUrl ?>" download class="btn btn-warning fw-bold px-4">
                                <i class="fas fa-download me-1"></i> Download Official PDF
                            </a>
                        </div>
                    </iframe>
                </div>

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3 pt-2">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i> If the document does not render automatically on mobile devices, use the <strong>Open Tab</strong> or <strong>Download</strong> buttons above.
                    </small>
                    <button type="button" class="btn btn-link btn-sm text-decoration-none p-0 text-maroon fw-bold" data-bs-toggle="modal" data-bs-target="#pdfFullscreenModal" style="color: #7A0C0C;">
                        <i class="fas fa-search-plus me-1"></i> Enlarge Document in Modal
                    </button>
                </div>
            </div>

            <!-- SECTION 2: STATUTORY OBJECTS OF THE SOCIETY (FROM CLAUSE 3) -->
            <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border mb-4">
                <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom">
                    <div class="rounded p-2 text-white" style="background-color: #7A0C0C; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-scroll"></i>
                    </div>
                    <div>
                        <h4 class="font-serif fw-bold mb-0 text-dark">Statutory Objects of the Society</h4>
                        <small class="text-muted">Certified excerpt from Clause 3 of the registered Memorandum of Association</small>
                    </div>
                </div>
                <p class="text-secondary small mt-2 mb-4">
                    The society is formally incorporated to execute the following core philanthropic and educational mandates:
                </p>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100 sayak-card" style="background-color: #FFFDFB;">
                            <div class="d-flex align-items-start gap-3">
                                <span class="badge rounded-circle p-2 text-white" style="background-color: #7A0C0C; min-width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 12px;">3a</span>
                                <div>
                                    <h6 class="font-serif fw-bold mb-1 text-dark">Libraries & Educational Facilities</h6>
                                    <p class="small text-muted mb-0">To acquire, establish, start, aid, run, maintain or manage schools, colleges, <strong>libraries</strong>, and hospitals for the benefit of the public.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100 sayak-card" style="background-color: #FFFDFB;">
                            <div class="d-flex align-items-start gap-3">
                                <span class="badge rounded-circle p-2 text-white" style="background-color: #7A0C0C; min-width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 12px;">3b</span>
                                <div>
                                    <h6 class="font-serif fw-bold mb-1 text-dark">Diffusion of Knowledge</h6>
                                    <p class="small text-muted mb-0">To arrange and organize lectures, academic debates, seminars, intellectual discussions, and excursions for the diffusion of knowledge.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100 sayak-card" style="background-color: #FFFDFB;">
                            <div class="d-flex align-items-start gap-3">
                                <span class="badge rounded-circle p-2 text-white" style="background-color: #7A0C0C; min-width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 12px;">3c</span>
                                <div>
                                    <h6 class="font-serif fw-bold mb-1 text-dark">Literature & Publishing</h6>
                                    <p class="small text-muted mb-0">To publish or cause to be published useful literatures, papers, magazines, scholarly periodicals, and books for public intellectual enrichment.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100 sayak-card" style="background-color: #FFFDFB;">
                            <div class="d-flex align-items-start gap-3">
                                <span class="badge rounded-circle p-2 text-white" style="background-color: #7A0C0C; min-width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 12px;">3f</span>
                                <div>
                                    <h6 class="font-serif fw-bold mb-1 text-dark">Support for Needy Students</h6>
                                    <p class="small text-muted mb-0">To actively help the needy and meritorious students of all communities for the prosecution and continuation of their higher studies.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100 sayak-card" style="background-color: #FFFDFB;">
                            <div class="d-flex align-items-start gap-3">
                                <span class="badge rounded-circle p-2 text-white" style="background-color: #7A0C0C; min-width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 12px;">3g</span>
                                <div>
                                    <h6 class="font-serif fw-bold mb-1 text-dark">Manuscript & Heritage Preservation</h6>
                                    <p class="small text-muted mb-0">To collect and preserve manuscripts, paintings, sculptures, works of art, antiquities, specimens of natural history, and scientific designs.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 border rounded h-100 sayak-card" style="background-color: #FFFDFB;">
                            <div class="d-flex align-items-start gap-3">
                                <span class="badge rounded-circle p-2 text-white" style="background-color: #7A0C0C; min-width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; font-size: 12px;">3h/i</span>
                                <div>
                                    <h6 class="font-serif fw-bold mb-1 text-dark">Social & Humanitarian Relief</h6>
                                    <p class="small text-muted mb-0">To aid the aged, sick, helpless, and indigent persons, and to alleviate the sufferings of animals and living creatures.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Non-Profit Statutory Declaration Quote -->
                <div class="p-4 mt-4 rounded border-start border-4 border-maroon" style="background-color: #FFF9F9; border-color: #7A0C0C !important;">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fas fa-hand-holding-heart text-maroon" style="color: #7A0C0C;"></i>
                        <strong class="font-serif text-maroon" style="color: #7A0C0C;">Statutory Non-Profit Clause (Memorandum Page 1):</strong>
                    </div>
                    <blockquote class="blockquote small text-muted mb-0 fst-italic">
                        "The income and property of the society whatsoever derived or obtained shall be applied solely towards the promotion of the objects of the society and no portion thereof shall be paid to or divided amongst any of its members by way of profits..."
                    </blockquote>
                </div>
            </div>

            <!-- SECTION 3: FIRST GOVERNING BODY (HISTORICAL FOUNDATION, 1997) -->
            <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border mb-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3 pb-2 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded p-2 text-white" style="background-color: #7A0C0C; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <h4 class="font-serif fw-bold mb-0 text-dark">First Governing Body (1997)</h4>
                            <small class="text-muted">Certified signatories to the original Memorandum of Association (Page 2)</small>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>governing-body.php" class="btn btn-outline-maroon btn-sm font-serif fw-bold">
                        <i class="fas fa-user-tie me-1"></i> View Present Governing Body
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-bordered align-middle mb-0">
                        <thead class="table-light font-serif small">
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>Name & Foundation Address</th>
                                <th>Designation in 1997 Council</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <tr>
                                <td class="text-center fw-bold">1</td>
                                <td>
                                    <strong class="text-dark">Ranjan Basu</strong>
                                    <div class="text-muted" style="font-size: 11.5px;">18/1, Ramgarh Road, Calcutta - 700 076</div>
                                </td>
                                <td><span class="badge bg-dark">President</span></td>
                            </tr>
                            <tr>
                                <td class="text-center fw-bold">2</td>
                                <td>
                                    <strong class="text-dark">Shubhro Ganguly</strong>
                                    <div class="text-muted" style="font-size: 11.5px;">4, A. C. Sarkar Road, Calcutta - 700 076</div>
                                </td>
                                <td><span class="badge bg-secondary">Vice President</span></td>
                            </tr>
                            <tr>
                                <td class="text-center fw-bold">3</td>
                                <td>
                                    <strong class="text-dark">Sourav Deb Burman</strong>
                                    <div class="text-muted" style="font-size: 11.5px;">5/2, A. C. Sarkar Road, Calcutta - 700 076</div>
                                </td>
                                <td><span class="badge text-white" style="background-color: #7A0C0C;">Secretary</span></td>
                            </tr>
                            <tr>
                                <td class="text-center fw-bold">4</td>
                                <td>
                                    <strong class="text-dark">Sudip Bhattacharya</strong>
                                    <div class="text-muted" style="font-size: 11.5px;">89B, D. D. Mondal Ghat Road, Calcutta - 700 076</div>
                                </td>
                                <td><span class="badge bg-secondary">Asst. Secretary</span></td>
                            </tr>
                            <tr>
                                <td class="text-center fw-bold">5</td>
                                <td>
                                    <strong class="text-dark">Arobinda Dutta</strong>
                                    <div class="text-muted" style="font-size: 11.5px;">20, D. D. Mondal Ghat Road, Calcutta - 700 076</div>
                                </td>
                                <td><span class="badge text-dark bg-warning-subtle border border-warning-subtle">Treasurer</span></td>
                            </tr>
                            <tr>
                                <td class="text-center fw-bold">6</td>
                                <td>
                                    <strong class="text-dark">Koushik Ghosh</strong>
                                    <div class="text-muted" style="font-size: 11.5px;">70, D. D. Mondal Ghat Road, Calcutta - 700 076</div>
                                </td>
                                <td><span class="badge bg-light text-dark border">Asst. Treasurer</span></td>
                            </tr>
                            <tr>
                                <td class="text-center fw-bold">7</td>
                                <td>
                                    <strong class="text-dark">Malay Kundu</strong>
                                    <div class="text-muted" style="font-size: 11.5px;">25, A. C. Sarkar Road, Calcutta - 700 076</div>
                                </td>
                                <td><span class="badge bg-light text-dark border">Executive Member</span></td>
                            </tr>
                            <tr>
                                <td class="text-center fw-bold">8</td>
                                <td>
                                    <strong class="text-dark">Koushik Kundu</strong>
                                    <div class="text-muted" style="font-size: 11.5px;">21, D. D. Mondal Ghat Road, Calcutta - 700 076</div>
                                </td>
                                <td><span class="badge bg-light text-dark border">Executive Member</span></td>
                            </tr>
                            <tr>
                                <td class="text-center fw-bold">9</td>
                                <td>
                                    <strong class="text-dark">Sushanta Mitra Mustafi</strong>
                                    <div class="text-muted" style="font-size: 11.5px;">65, A. C. Sarkar Road, Calcutta - 700 076</div>
                                </td>
                                <td><span class="badge bg-light text-dark border">Executive Member</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Accordion: All 29 Founding Subscribed Members -->
                <div class="accordion mt-3" id="foundingSignatoriesAccordion">
                    <div class="accordion-item border rounded">
                        <h2 class="accordion-header" id="headingSignatories">
                            <button class="accordion-button collapsed font-serif py-2 small fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSignatories" aria-expanded="false" aria-controls="collapseSignatories">
                                <i class="fas fa-feather-alt text-maroon me-2" style="color: #7A0C0C;"></i> View All 29 Founding Signatory Members (Subscribed March 12, 1997)
                            </button>
                        </h2>
                        <div id="collapseSignatories" class="accordion-collapse collapse" aria-labelledby="headingSignatories" data-bs-parent="#foundingSignatoriesAccordion">
                            <div class="accordion-body p-3 bg-light">
                                <p class="small text-muted mb-2">As recorded on Pages 3 & 4 of the registered Memorandum of Association witnessed by Pradip Kumar Roy:</p>
                                <div class="row g-2 small">
                                    <div class="col-sm-6 col-md-4">1. Ranjan Basu (Service)</div>
                                    <div class="col-sm-6 col-md-4">2. Pradip Mitra (Service)</div>
                                    <div class="col-sm-6 col-md-4">3. Shubhro Ganguly (Student)</div>
                                    <div class="col-sm-6 col-md-4">4. Sourav Deb Burman (Student)</div>
                                    <div class="col-sm-6 col-md-4">5. Sukalyan Ganguly (Business)</div>
                                    <div class="col-sm-6 col-md-4">6. Arobinda Dutta (Student)</div>
                                    <div class="col-sm-6 col-md-4">7. Abhijit Dey (Service)</div>
                                    <div class="col-sm-6 col-md-4">8. Malay Kundu (Service)</div>
                                    <div class="col-sm-6 col-md-4">9. Koushik Kundu (Service)</div>
                                    <div class="col-sm-6 col-md-4">10. Sushanta Mitra Mustafi (Business)</div>
                                    <div class="col-sm-6 col-md-4">11. Kishore Mondal (Service)</div>
                                    <div class="col-sm-6 col-md-4">12. Tapan Roychowdhury (Student)</div>
                                    <div class="col-sm-6 col-md-4">13. Sudip Bhattacharya (Service)</div>
                                    <div class="col-sm-6 col-md-4">14. Sukalyan Biswas (Service)</div>
                                    <div class="col-sm-6 col-md-4">15. Shashanka Chatterjee (Student)</div>
                                    <div class="col-sm-6 col-md-4">16. Sumit Bose (Service)</div>
                                    <div class="col-sm-6 col-md-4">17. Shyamal Pal (Business)</div>
                                    <div class="col-sm-6 col-md-4">18. Kalyan Chatterjee (Service)</div>
                                    <div class="col-sm-6 col-md-4">19. Chinmoy Chowdhury (Business)</div>
                                    <div class="col-sm-6 col-md-4">20. Rabindranath Roy Chowdhury (Student)</div>
                                    <div class="col-sm-6 col-md-4">21. Koushik Ghosh (Student)</div>
                                    <div class="col-sm-6 col-md-4">22. Ujjwal Chatterjee (Student)</div>
                                    <div class="col-sm-6 col-md-4">23. Rajib Sanyal (Service)</div>
                                    <div class="col-sm-6 col-md-4">24. Mrs. Jhuma Basu (Teacher)</div>
                                    <div class="col-sm-6 col-md-4">25. Miss Arpita Baral (Student)</div>
                                    <div class="col-sm-6 col-md-4">26. Shoubhik Kar (Student)</div>
                                    <div class="col-sm-6 col-md-4">27. Sandip Ghosh (Business)</div>
                                    <div class="col-sm-6 col-md-4">28. Shailen Ghosh (Service)</div>
                                    <div class="col-sm-6 col-md-4">29. Anjan Basu (Service)</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 4: INSTITUTIONAL ACCOUNTABILITY & FINANCIAL INTEGRITY -->
            <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border">
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <div class="rounded p-2 text-white" style="background-color: #7A0C0C; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <div>
                        <h4 class="font-serif fw-bold mb-0 text-dark">Institutional Accountability & Standards</h4>
                        <small class="text-muted">Rigorous operational compliance, audit control, and digital preservation</small>
                    </div>
                </div>

                <div class="row g-4 mt-1">
                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle p-2 bg-light text-maroon border flex-shrink-0" style="color: #7A0C0C; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-calculator fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="font-serif fw-bold text-dark mb-1"><?= escape(get_setting('gov_audit_title', 'Chartered Financial Audit')) ?></h6>
                                <p class="small text-muted mb-0"><?= escape(get_setting('gov_audit_desc', 'All public donations, book purchases, and membership fees are formally receipted and reviewed annually by independent Chartered Accountants in compliance with state filing requirements.')) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle p-2 bg-light text-maroon border flex-shrink-0" style="color: #7A0C0C; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-database fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="font-serif fw-bold text-dark mb-1">Transparent Digital Catalog</h6>
                                <p class="small text-muted mb-0">Our automated catalog system tracks accession numbers, shelf locations, and book loans in real time, preventing loss and ensuring accountability across 5,000+ volumes.</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle p-2 bg-light text-maroon border flex-shrink-0" style="color: #7A0C0C; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-lock fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="font-serif fw-bold text-dark mb-1"><?= escape(get_setting('gov_sec_title', 'Patron Data Confidentiality')) ?></h6>
                                <p class="small text-muted mb-0"><?= escape(get_setting('gov_sec_desc', 'Strict student privacy guidelines ensure member contact details, reading histories, and fee receipts are encrypted and protected against third-party disclosure.')) ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex align-items-start gap-3">
                            <div class="rounded-circle p-2 bg-light text-maroon border flex-shrink-0" style="color: #7A0C0C; width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-users-cog fa-lg"></i>
                            </div>
                            <div>
                                <h6 class="font-serif fw-bold text-dark mb-1">Democratic Trustee Oversight</h6>
                                <p class="small text-muted mb-0">An elected Governing Body and an active Working Committee convene regularly to guide acquisitions, student welfare initiatives, and institutional governance.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL: FULLSCREEN CERTIFIED MEMORANDUM PDF VIEWER             -->
<!-- ============================================================ -->
<div class="modal fade" id="pdfFullscreenModal" tabindex="-1" aria-labelledby="pdfFullscreenModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content border-0">
            <div class="modal-header text-white" style="background-color: #7A0C0C;">
                <h5 class="modal-title font-serif" id="pdfFullscreenModalLabel">
                    <i class="fas fa-file-contract me-2"></i> Official Memorandum of Association (Reg. No. S/87920) — Dakshineswar Shayak
                </h5>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?= $pdfUrl ?>" download="Dakshineswar_Shayak_Memorandum_Of_Association.pdf" class="btn btn-warning btn-sm fw-bold">
                        <i class="fas fa-download me-1"></i> Download PDF
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0" style="background-color: #525659;">
                <iframe 
                    src="<?= $pdfUrl ?>#toolbar=1&navpanes=1&scrollbar=1" 
                    title="Fullscreen Official Memorandum of Association" 
                    width="100%" 
                    height="100%" 
                    style="border: none; min-height: 90vh;"
                ></iframe>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
