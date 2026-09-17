<?php
$pageTitle = "Governing Body - About Us";
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
                    <a href="<?= BASE_URL ?>governing-body.php" class="list-group-item list-group-item-action active fw-bold" style="background-color: #7A0C0C; border-color: #7A0C0C;">Governing Body</a>
                    <a href="<?= BASE_URL ?>governance.php" class="list-group-item list-group-item-action">Governance</a>
                    <a href="<?= BASE_URL ?>academic-collaborations.php" class="list-group-item list-group-item-action">Academic Collaborations</a>
                </ul>
            </div>
        </div>

        <div class="col-lg-9">
            <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <h1 class="section-title mb-0">Governing Body & Working Committee</h1>
                    <span class="badge font-serif px-3 py-2 text-white" style="background-color: #7A0C0C; font-size: 12.5px;">
                        <i class="fas fa-university me-1"></i> Leadership & Administration
                    </span>
                </div>
                <p class="text-secondary">Distinguished office bearers, executive council members, and operational coordinators of Dakshineswar Shayak Library.</p>

                <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3 mb-4" style="background-color: #FFFBF7;">
                    <span class="small text-muted">
                        <i class="fas fa-file-contract text-maroon me-1" style="color: #7A0C0C;"></i> Looking for our official registered Memorandum of Association & Society Constitution?
                    </span>
                    <a href="<?= BASE_URL ?>governance.php" class="btn btn-outline-maroon btn-sm py-1 font-serif fw-bold" style="font-size: 12px;">
                        <i class="fas fa-balance-scale me-1"></i> View Registered Deed & PDF
                    </a>
                </div>
                <hr class="my-4">

<?php
$db = getDB();
$allMembers = $db->query("SELECT * FROM governing_body_members WHERE status = 'Active' ORDER BY sort_order ASC, id ASC")->fetchAll();

$govBody = array_filter($allMembers, fn($m) => ($m['committee_type'] ?? 'Governing Body') === 'Governing Body');
$workingComm = array_filter($allMembers, fn($m) => ($m['committee_type'] ?? '') === 'Working Committee');
?>

                <!-- Section 1: Governing Body (পরিচালনা পর্ষদ) -->
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <div class="rounded p-2 text-white" style="background-color: #7A0C0C; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-landmark"></i>
                    </div>
                    <div>
                        <h4 class="font-serif fw-bold mb-0 text-dark">Governing Body (পরিচালনা পর্ষদ)</h4>
                        <small class="text-muted">Executive council & institutional office bearers</small>
                    </div>
                </div>

                <div class="row g-4 mb-5">
                    <?php if (!empty($govBody)): ?>
                        <?php foreach ($govBody as $m): ?>
                            <?php $hasBio = !empty(trim($m['bio'] ?? '')); ?>
                            <div class="col-md-6">
                                <div class="card h-100 border p-3 sayak-card shadow-sm d-flex flex-column justify-content-between">
                                    <div class="d-flex align-items-start">
                                        <?php if (!empty($m['photo']) && file_exists(ROOT_PATH . 'uploads/governing_body/' . $m['photo'])): ?>
                                            <img src="<?= BASE_URL ?>uploads/governing_body/<?= escape($m['photo']) ?>" alt="<?= escape($m['name']) ?>" class="rounded-circle border border-2 border-maroon shadow-sm me-3 flex-shrink-0" style="width: 65px; height: 65px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-3 flex-shrink-0 border" style="width:65px; height:65px;">
                                                <i class="fas <?= escape($m['icon'] ?: 'fa-user-tie') ?> fa-2x text-maroon" style="color: #7A0C0C;"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <h5 class="fw-bold mb-0 font-serif text-dark"><?= escape($m['name']) ?></h5>
                                            <span class="badge font-serif px-2 py-1 my-1" style="background-color: #FFF2F2; color: #7A0C0C; border: 1px solid #7A0C0C; font-size: 12px;">
                                                <?= escape($m['designation']) ?>
                                            </span>
                                            <?php if (!empty($m['description'])): ?>
                                                <p class="small text-muted mb-0 mt-1"><?= nl2br(escape($m['description'])) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($hasBio): ?>
                                        <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                                            <span class="small text-muted" style="font-size: 11px;"><i class="fas fa-id-card text-maroon me-1" style="color: #7A0C0C;"></i> Detailed Profile</span>
                                            <button type="button" class="btn btn-outline-maroon btn-sm py-1 px-3 rounded-pill fw-bold font-serif" style="font-size: 12px;" onclick='openMemberBioModal(<?= htmlspecialchars(json_encode($m), ENT_QUOTES, 'UTF-8') ?>)'>
                                                Know More <i class="fas fa-arrow-right ms-1" style="font-size: 10px;"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-light text-center py-4 border text-muted">
                                Governing body details are currently being updated.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Section 2: Working Committee (কর্মী সমিতি) -->
                <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                    <div class="rounded p-2 text-white bg-dark" style="width: 36px; height: 36px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-hands-helping text-warning"></i>
                    </div>
                    <div>
                        <h4 class="font-serif fw-bold mb-0 text-dark">Working Committee (কর্মী সমিতি)</h4>
                        <small class="text-muted">Operational coordinators, student support & reading hall activities</small>
                    </div>
                </div>

                <div class="row g-4">
                    <?php if (!empty($workingComm)): ?>
                        <?php foreach ($workingComm as $m): ?>
                            <?php 
                            $isVacant = (trim($m['name']) === '(Vacant)'); 
                            $hasBio = !$isVacant && !empty(trim($m['bio'] ?? ''));
                            ?>
                            <div class="col-md-6">
                                <div class="card h-100 border p-3 sayak-card shadow-sm d-flex flex-column justify-content-between <?= $isVacant ? 'bg-light border-dashed' : '' ?>">
                                    <div class="d-flex align-items-start">
                                        <?php if (!empty($m['photo']) && file_exists(ROOT_PATH . 'uploads/governing_body/' . $m['photo'])): ?>
                                            <img src="<?= BASE_URL ?>uploads/governing_body/<?= escape($m['photo']) ?>" alt="<?= escape($m['name']) ?>" class="rounded-circle border border-2 border-maroon shadow-sm me-3 flex-shrink-0" style="width: 65px; height: 65px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded-circle <?= $isVacant ? 'bg-warning-subtle' : 'bg-light' ?> d-flex align-items-center justify-content-center me-3 flex-shrink-0 border" style="width:65px; height:65px;">
                                                <i class="fas <?= escape($m['icon'] ?: ($isVacant ? 'fa-user-plus' : 'fa-user-check')) ?> fa-2x <?= $isVacant ? 'text-warning' : 'text-secondary' ?>"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                                <h5 class="fw-bold mb-0 font-serif text-dark"><?= escape($m['name']) ?></h5>
                                                <?php if ($isVacant): ?>
                                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle small" style="font-size: 11px;">
                                                        <i class="fas fa-clock me-1"></i> Vacant Seat
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="badge bg-light text-dark border px-2 py-1 my-1 small">
                                                <?= escape($m['designation']) ?>
                                            </span>
                                            <?php if (!empty($m['description'])): ?>
                                                <p class="small text-muted mb-0 mt-1"><?= nl2br(escape($m['description'])) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if ($hasBio): ?>
                                        <div class="mt-3 pt-2 border-top d-flex justify-content-between align-items-center">
                                            <span class="small text-muted" style="font-size: 11px;"><i class="fas fa-id-card text-maroon me-1" style="color: #7A0C0C;"></i> Detailed Profile</span>
                                            <button type="button" class="btn btn-outline-maroon btn-sm py-1 px-3 rounded-pill fw-bold font-serif" style="font-size: 12px;" onclick='openMemberBioModal(<?= htmlspecialchars(json_encode($m), ENT_QUOTES, 'UTF-8') ?>)'>
                                                Know More <i class="fas fa-arrow-right ms-1" style="font-size: 10px;"></i>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- MODAL: GOVERNING BODY MEMBER PROFILE ("KNOW MORE")          -->
<!-- ============================================================ -->
<div class="modal fade" id="govMemberBioModal" tabindex="-1" aria-labelledby="govMemberBioModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="p-4 text-white position-relative" style="background: linear-gradient(135deg, #7A0C0C 0%, #4D0505 100%);">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
                <div class="d-flex align-items-center gap-3">
                    <div id="modalMemberPhotoWrapper" class="rounded-circle border border-3 border-white bg-white d-flex align-items-center justify-content-center shadow flex-shrink-0" style="width: 75px; height: 75px; overflow: hidden;">
                        <img id="modalMemberPhoto" src="" alt="Member" class="w-100 h-100 d-none" style="object-fit: cover;">
                        <i id="modalMemberIcon" class="fas fa-user-tie fa-2x text-maroon" style="color: #7A0C0C;"></i>
                    </div>
                    <div>
                        <span id="modalMemberCommittee" class="badge bg-white text-maroon font-serif px-2 py-1 mb-1" style="color: #7A0C0C; font-size: 11px;">Governing Body</span>
                        <h4 class="modal-title font-serif fw-bold text-white mb-0" id="modalMemberName">Member Name</h4>
                        <span id="modalMemberDesignation" class="badge bg-warning-subtle text-warning-emphasis font-serif mt-1" style="font-size: 12px;">Designation</span>
                    </div>
                </div>
            </div>
            <div class="modal-body p-4 bg-light">
                <div id="modalOverviewBox" class="p-3 bg-white rounded-3 border mb-3 shadow-sm">
                    <h6 class="text-maroon font-serif fw-bold mb-1" style="color: #7A0C0C;">
                        <i class="fas fa-briefcase me-2"></i> Role & Institutional Overview
                    </h6>
                    <p class="text-muted small mb-0" id="modalMemberDescription"></p>
                </div>
                <div class="p-4 bg-white rounded-3 border shadow-sm">
                    <h6 class="text-maroon font-serif fw-bold mb-3" style="color: #7A0C0C;">
                        <i class="fas fa-info-circle me-2"></i> Biography & Background Information
                    </h6>
                    <div id="modalMemberBio" class="text-dark" style="line-height: 1.8; font-size: 15px; white-space: pre-line;"></div>
                </div>
            </div>
            <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-between align-items-center">
                <span class="small text-muted font-serif"><i class="fas fa-landmark text-maroon me-1" style="color: #7A0C0C;"></i> Dakshineswar Shayak Library</span>
                <button type="button" class="btn btn-secondary btn-sm px-4 font-serif" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
function openMemberBioModal(m) {
    document.getElementById('modalMemberName').textContent = m.name || '';
    document.getElementById('modalMemberDesignation').textContent = m.designation || '';
    document.getElementById('modalMemberCommittee').textContent = (m.committee_type === 'Working Committee') ? 'Working Committee (কর্মী সমিতি)' : 'Governing Body (পরিচালনা পর্ষদ)';
    
    var descBox = document.getElementById('modalOverviewBox');
    var descEl = document.getElementById('modalMemberDescription');
    if (m.description && m.description.trim()) {
        descEl.textContent = m.description;
        descBox.style.display = 'block';
    } else {
        descBox.style.display = 'none';
    }

    var bioEl = document.getElementById('modalMemberBio');
    bioEl.textContent = m.bio || 'No additional information entered.';

    var photoImg = document.getElementById('modalMemberPhoto');
    var iconEl = document.getElementById('modalMemberIcon');
    if (m.photo) {
        photoImg.src = '<?= BASE_URL ?>uploads/governing_body/' + m.photo;
        photoImg.classList.remove('d-none');
        iconEl.classList.add('d-none');
    } else {
        photoImg.src = '';
        photoImg.classList.add('d-none');
        iconEl.className = 'fas ' + (m.icon || 'fa-user-tie') + ' fa-2x text-maroon';
        iconEl.classList.remove('d-none');
    }

    var modal = new bootstrap.Modal(document.getElementById('govMemberBioModal'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
