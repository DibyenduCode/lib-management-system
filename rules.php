<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Rules & Regulations - Dakshineswar Shayak Library";
require_once __DIR__ . '/includes/header.php';

// Official 19 Rules (Bilingual: English & Bengali)
$officialRules = [
    1 => [
        'en' => "Books will be issued on demand basis.",
        'bn' => "চাহিদা অনুযায়ী বই দেওয়া হবে।",
        'tag' => "Issuing Policy",
        'icon' => "fas fa-book-reader"
    ],
    2 => [
        'en' => "At a time one / two book(s) will be issued for seven days only.",
        'bn' => "এককালীন একটি বা দুটি বই ৭ দিনের জন্য দেওয়া হবে।",
        'tag' => "7 Days Limit",
        'badge_class' => "bg-warning text-dark",
        'icon' => "fas fa-calendar-week"
    ],
    3 => [
        'en' => "Books may be renewed in absence of other's demand.",
        'bn' => "অন্য কারোর চাহিদা না থাকলে বই-এর পুন:নবীকরণ করা যাবে।",
        'tag' => "Renewal",
        'icon' => "fas fa-redo-alt"
    ],
    4 => [
        'en' => "Demandship will be rejected if it is found that the book was in the library at the time of issuing demand.",
        'bn' => "গ্রন্থাগারে বই থাকা সত্ত্বেও সেই বই এর উপর চাহিদা দিলে চাহিদাপত্র বাতিল বলে গণ্য হবে।",
        'tag' => "Demand Clause",
        'icon' => "fas fa-times-circle"
    ],
    5 => [
        'en' => "User must report any damage / mutilation of book before issuing the same. Identification of damage / mutilation at a later date, automatically the liability will rest with the person to whom the book was last issued. In such cases the Library Authority will make the final decision.",
        'bn' => "বই এর ছেঁড়া/ফাটা পাতা ইত্যাদি বই ইস্যু করার আগে দেখে নিতে হবে। যদি বই এর কোনো রকম ক্ষয়ক্ষতি পরে পাওয়া যায় তবে, সর্বশেষ যার কাছে বইটা ইস্যু ছিল তার ওপর দায় বর্তাবে। গ্রন্থাগার কর্তৃপক্ষ এই ব্যাপারে চূড়ান্ত সিদ্ধান্ত নেবে।",
        'tag' => "Damage Liability",
        'badge_class' => "bg-danger text-white",
        'icon' => "fas fa-search"
    ],
    6 => [
        'en' => "Folding page corners, marking with pencil or ink, or tearing pages/pictures from the books is strictly prohibited.",
        'bn' => "বইয়ের পাতা ভাঁজ করা, পেন্সিল বা কালি দিয়ে দাগ দেওয়া অথবা কোনো ছবি বা পাতা কাটা কঠোরভাবে নিষিদ্ধ।",
        'tag' => "Strictly Prohibited",
        'badge_class' => "bg-danger text-white",
        'icon' => "fas fa-ban"
    ],
    7 => [
        'en' => "In the event of a lost book, the user must replace it with a new copy of the same edition or pay the current market price of the book. The caution money deposit shall not be considered as an alternative compensation for the lost book.",
        'bn' => "কোনো বই হারিয়ে গেলে ব্যবহারকারীকে ওই একই বইয়ের নতুন কপি কিনে দিতে হবে অথবা বর্তমান বাজারদর অনুযায়ী বইয়ের সম্পূর্ণ মূল্য প্রদান করতে হবে। জমা রাখা ফেরতযোগ্য অর্থ (Caution Money) কোনোভাবেই হারিয়ে যাওয়া বইয়ের বিকল্প ক্ষতিপূরণ হিসেবে গণ্য হবে না।",
        'tag' => "Lost Book Policy",
        'badge_class' => "bg-danger text-white",
        'icon' => "fas fa-exclamation-triangle"
    ],
    8 => [
        'en' => "In absence of the user at library on due date he / she would send an authorisation letter addressed to \"The Secretary / Librarian, Dakshineswar Shayak Library\" to change /renew the book, otherwise the book will be returned. The system will be valid for only one week only.",
        'bn' => "গ্রন্থাগার এর নির্দিষ্ট দিনে কোনও লাইব্রেরি ব্যবহারকারী (User) পরিবর্ত কাউকে বদল বা একই বই পুনরায় নিতে পাঠালে, অবশ্যই ঐ লাইব্রেরি ব্যবহারকারী (User)-কে 'গ্রন্থাগারিক / সম্পাদক দক্ষিণেশ্বর শায়ক লাইব্রেরি' এর উদ্দেশ্যে চিঠি পাঠাতে হবে। ওই লাইব্রেরি ব্যবহারকারী (User) তার পরিবর্ত হিসাবে যাকে পাঠাচ্ছেন তার স্বাক্ষর উক্ত চিঠিতে প্রত্যয়িত (attested) করতে হবে। অন্যথায় বই ফেরত নেওয়া হবে। এই পদ্ধতি কেবলমাত্র এক সপ্তাহের জন্যই ধার্য্য হবে।",
        'tag' => "Proxy / Authorisation",
        'icon' => "fas fa-envelope-open-text"
    ],
    9 => [
        'en' => "The usership card is strictly non-transferable (except as conditionally permitted in Rule 8). The card must not be lent to anyone else under any circumstances.",
        'bn' => "গ্রন্থাগারের ব্যবহারকারী কার্ডটি সম্পূর্ণরূপে হস্তান্তরযোগ্য নয় (৮ নং নিয়ম ব্যতীত)। কোনো অবস্থাতেই অন্য কাউকে এই কার্ড ব্যবহার করতে দেওয়া যাবে না।",
        'tag' => "Non-Transferable",
        'badge_class' => "bg-dark text-white",
        'icon' => "fas fa-id-card-alt"
    ],
    10 => [
        'en' => "Two passport size recent colour photographs (taken within the last six months) of the applicant will be required at the time of registration.",
        'bn' => "আবেদনকারীর নাম নথীভুক্তকরণের জন্য দু কপি পাসপোর্ট মাপের রঙিন ছবি (গত ছয় মাসের মধ্যে তোলা) লাগবে।",
        'tag' => "Registration Requirement",
        'icon' => "fas fa-camera"
    ],
    11 => [
        'en' => "A sum of Rs.50/- (Rupees fifty only) per book will be taken as caution money in case of lending, which will be refunded after termination of usership.",
        'bn' => "ফেরতযোগ্য অর্থ (Caution Money) হিসেবে ৫০ টাকা প্রত্যেক বই এর জন্য জমা রাখতে হবে।",
        'tag' => "Caution Money: ₹50/-",
        'badge_class' => "bg-success text-white",
        'icon' => "fas fa-hand-holding-usd"
    ],
    12 => [
        'en' => "Rs.5/- (Rupees five only) will be taken as registration fees.",
        'bn' => "নাম নথী ভুক্তকরণের জন্য ৫ টাকা জমা করতে হবে।",
        'tag' => "Registration Fee: ₹5/-",
        'badge_class' => "bg-primary text-white",
        'icon' => "fas fa-ticket-alt"
    ],
    13 => [
        'en' => "Rs.20/- (Rupees twenty only) will be taken as Monthly Subscription.",
        'bn' => "ব্যবহারকারীর মাসিক চাঁদা ২০ টাকা ধার্য্য করা হবে।",
        'tag' => "Monthly Subscription: ₹20/-",
        'badge_class' => "bg-info text-dark",
        'icon' => "fas fa-coins"
    ],
    14 => [
        'en' => "Rs.1/- (One rupee only) per day per book will be charged as Fine in case of late return book.",
        'bn' => "বই দেরীতে ফেরৎ দিলে প্রতি বই এর ক্ষেত্রে প্রত্যেকদিন ১ টাকা হিসেবে জরিমানা ধার্য্য হবে।",
        'tag' => "Late Fine: ₹1 / Day",
        'badge_class' => "bg-danger text-white",
        'icon' => "fas fa-clock"
    ],
    15 => [
        'en' => "If any user's monthly subscriptions and fine exceed deposit money i.e., Rs.50/- or Rs.100/- his/her usership card will be automatically terminated.",
        'bn' => "যদি কোন গ্রন্থাগার ব্যবহারকারীর মাসিক চাঁদা ও জরিমানা, জমা রাখা ৫০/- অথবা ১০০/- ফেরতযোগ্য অর্থ (Caution Money)-এর অধিক হয়ে যায়, তাহলে তার গ্রন্থাগার ব্যবহারের সদস্যপদ বাতিল বলে গণ্য হবে।",
        'tag' => "Card Termination",
        'badge_class' => "bg-danger text-white",
        'icon' => "fas fa-user-times"
    ],
    16 => [
        'en' => "The Library working days are Thursday & Saturday 7:30 P.M. to 9:00 P.M, Sunday 9:30 A.M. - 12:30 Noon.",
        'bn' => "গ্রন্থাগার প্রতি বৃহস্পতিবার, শনিবার (সন্ধ্যা ৭:৩০ মি: থেকে ৯:০০ টা পর্যন্ত) ও রবিবার (সকাল ৯:৩০ মি: থেকে বেলা ১২:৩০ মি: পর্যন্ত) খোলা থাকে।",
        'tag' => "Library Schedule",
        'badge_class' => "bg-success text-white",
        'icon' => "fas fa-door-open"
    ],
    17 => [
        'en' => "Person(s) who can give recommendation, should have to take their own responsibility for the applicant, in terms with Dakshineswar Shayak Library. User of this library may recommend one person.",
        'bn' => "আবেদনকারীর নাম যারা অনুমোদন করবেন তারা সংস্থার সাথে আবেদনকারীর যোগাযোগের ক্ষেত্রে প্রয়োজনে দায়িত্ব নেবেন। দুজন অনুমোদনকারীর মধ্যে যে কোনও একজন সংস্থার সদস্য / সদস্যা (Member) হলেও চলবে।",
        'tag' => "Recommendation",
        'icon' => "fas fa-user-check"
    ],
    18 => [
        'en' => "In all matters, the decision of the Library Authority shall be final and binding.",
        'bn' => "যে কোনো ক্ষেত্রে গ্রন্থাগার কর্তৃপক্ষের সিদ্ধান্তই চূড়ান্ত বলে গণ্য হবে।",
        'tag' => "Authority Final",
        'badge_class' => "bg-dark text-white",
        'icon' => "fas fa-gavel"
    ],
    19 => [
        'en' => "In case of any ambiguity or conflict in interpretation, the English terminology shall prevail.",
        'bn' => "নিয়মাবলী ব্যাখ্যার ক্ষেত্রে কোনো অস্পষ্টতা বা বিরোধ দেখা দিলে, ইংরেজি পরিভাষা প্রাধান্য পাবে।",
        'tag' => "Interpretation Clause",
        'badge_class' => "bg-primary text-white",
        'icon' => "fas fa-balance-scale"
    ]
];

// Apply any custom edits made by Super Admin in CMS
for ($i = 1; $i <= 19; $i++) {
    $customEn = get_setting("rule_{$i}_en", '');
    $customBn = get_setting("rule_{$i}_bn", '');
    if (!empty($customEn)) {
        $officialRules[$i]['en'] = $customEn;
    }
    if (!empty($customBn)) {
        $officialRules[$i]['bn'] = $customBn;
    }
}
$rulesLead = get_setting('rules_lead', 'Official code of conduct, borrowing privileges, caution money structure, and operating policies governing Dakshineswar Shayak Library.');

$bengaliNumerals = [
    1 => '১', 2 => '২', 3 => '৩', 4 => '৪', 5 => '৫',
    6 => '৬', 7 => '৭', 8 => '৮', 9 => '৯', 10 => '১০',
    11 => '১১', 12 => '১২', 13 => '১৩', 14 => '১৪', 15 => '১৫',
    16 => '১৬', 17 => '১৭', 18 => '১৮', 19 => '১৯'
];
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-9">
            <?php if (isset($_SESSION['role_code']) && $_SESSION['role_code'] === 'SUPER_ADMIN'): ?>
                <div class="alert alert-info border-start border-4 border-info shadow-sm d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4 py-2">
                    <div class="small">
                        <strong><i class="fas fa-edit me-1 text-primary"></i> Super Admin Quick Edit:</strong> You can edit any of these 19 rules (English or Bengali) directly in the CMS.
                    </div>
                    <a href="<?= BASE_URL ?>admin/cms/index.php?tab=rules" class="btn btn-primary btn-sm fw-bold">
                        <i class="fas fa-sliders-h me-1"></i> Edit Rules in CMS
                    </a>
                </div>
            <?php endif; ?>

            <!-- Header Card -->
            <div class="card sayak-card border-0 shadow-sm overflow-hidden mb-4">
                <div class="p-4 p-md-5 text-white" style="background: linear-gradient(135deg, #7A0C0C 0%, #4A0505 100%);">
                    <div class="d-md-flex justify-content-between align-items-center">
                        <div class="mb-3 mb-md-0">
                            <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-2">
                                <i class="fas fa-university me-1"></i> <?= escape(get_setting('library_name', 'DAKSHINESWAR SHAYAK LIBRARY')) ?>
                            </span>
                            <h1 class="font-serif fw-bold text-white mb-2 fs-2">Rules & Regulations / নিয়মাবলী</h1>
                            <p class="mb-0 text-white-50 small" style="max-width: 650px;">
                                <?= escape($rulesLead) ?>
                            </p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" onclick="window.print()" class="btn btn-outline-light btn-sm px-3 py-2">
                                <i class="fas fa-print me-1"></i> Print Rules
                            </button>
                            <a href="<?= BASE_URL ?>usership.php" class="btn btn-warning btn-sm fw-bold px-3 py-2 text-dark">
                                <i class="fas fa-id-card me-1"></i> Usership Portal
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Key Rules Quick Strip -->
                <div class="bg-light p-3 border-top border-bottom">
                    <div class="row g-2 text-center text-md-start">
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded bg-white">
                                <small class="text-muted d-block"><i class="fas fa-calendar-check text-maroon me-1" style="color:#7A0C0C;"></i> Lending Period</small>
                                <strong class="text-dark">7 Days (1-2 Books)</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded bg-white">
                                <small class="text-muted d-block"><i class="fas fa-shield-alt text-success me-1"></i> Caution Deposit</small>
                                <strong class="text-dark">₹50 / Book (Refundable)</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded bg-white">
                                <small class="text-muted d-block"><i class="fas fa-coins text-warning me-1"></i> Reg & Monthly Fee</small>
                                <strong class="text-dark">₹5 Reg | ₹20 / Month</strong>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded bg-white">
                                <small class="text-muted d-block"><i class="fas fa-clock text-danger me-1"></i> Overdue Fine</small>
                                <strong class="text-dark">₹1 / Day per Book</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Language Switcher Bar -->
                <div class="p-3 bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="small text-muted">
                        <i class="fas fa-language text-primary me-1 fs-5"></i> <strong>Display Language:</strong>
                    </div>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-maroon active fw-bold lang-btn" id="btn-lang-en" onclick="switchLanguage('en')">
                            <i class="fas fa-globe-americas me-1"></i> English (Official)
                        </button>
                        <button type="button" class="btn btn-outline-maroon fw-bold lang-btn" id="btn-lang-bn" onclick="switchLanguage('bn')">
                            <i class="fas fa-language me-1"></i> বাংলা
                        </button>
                        <button type="button" class="btn btn-outline-maroon fw-bold lang-btn" id="btn-lang-dual" onclick="switchLanguage('dual')">
                            <i class="fas fa-columns me-1"></i> Bilingual (Dual View)
                        </button>
                    </div>
                </div>
            </div>

            <!-- Rules List Container -->
            <div class="rules-container">
                <?php foreach ($officialRules as $num => $rule): ?>
                    <div class="card sayak-card border-0 shadow-sm mb-3 rule-item overflow-hidden">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-start">
                                <!-- Rule Number Circle Badge -->
                                <div class="rule-badge me-3 flex-shrink-0">
                                    <div class="rounded-circle text-white fw-bold d-flex align-items-center justify-content-center shadow-sm" style="width: 44px; height: 44px; background: linear-gradient(135deg, #7A0C0C 0%, #9B1C1C 100%); font-size: 16px;">
                                        <span class="num-en"><?= $num ?></span>
                                        <span class="num-bn" style="display: none;"><?= $bengaliNumerals[$num] ?></span>
                                    </div>
                                </div>

                                <!-- Rule Content -->
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                        <span class="badge <?= $rule['badge_class'] ?? 'bg-light text-secondary border' ?> px-2 py-1 small">
                                            <i class="<?= $rule['icon'] ?> me-1"></i> <?= escape($rule['tag'] ?? "Rule {$num}") ?>
                                        </span>
                                        <?php if ($num === 19): ?>
                                            <span class="badge bg-warning text-dark px-2 py-1 small fw-bold">
                                                <i class="fas fa-asterisk me-1"></i> Legal Interpretation Clause
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- English Text -->
                                    <div class="rule-text-en fs-6 text-dark fw-normal mb-1" style="line-height: 1.65;">
                                        <?= escape($rule['en']) ?>
                                    </div>

                                    <!-- Bengali Text -->
                                    <div class="rule-text-bn fs-6 text-dark font-serif" style="line-height: 1.7; display: none;">
                                        <?= escape($rule['bn']) ?>
                                    </div>

                                    <!-- Dual View Sub-box -->
                                    <div class="rule-text-dual-bn mt-2 p-2 bg-light rounded border small text-secondary font-serif" style="display: none;">
                                        <i class="fas fa-quote-left text-muted me-1 small"></i> <?= escape($rule['bn']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Library Working Hours Highlight Card (Rule 16) -->
            <div class="card sayak-card border-start border-4 border-success shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-door-open fa-2x text-success me-3"></i>
                        <div>
                            <h5 class="font-serif fw-bold text-dark mb-0">Library Working Schedule / গ্রন্থাগার খোলার সময়</h5>
                            <small class="text-muted">In accordance with Rule 16 of Dakshineswar Shayak Library</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border">
                                <strong class="text-maroon d-block mb-1" style="color: #7A0C0C;"><i class="fas fa-moon me-1"></i> Thursday & Saturday (সন্ধ্যা)</strong>
                                <span class="fs-6 text-dark fw-bold">7:30 PM - 9:00 PM</span>
                                <small class="text-muted d-block">রাত ৭:৩০ মি: থেকে ৯:০০ টা পর্যন্ত</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded border">
                                <strong class="text-maroon d-block mb-1" style="color: #7A0C0C;"><i class="fas fa-sun me-1"></i> Sunday (রবিবার সকাল)</strong>
                                <span class="fs-6 text-dark fw-bold">9:30 AM - 12:30 PM</span>
                                <small class="text-muted d-block">সকাল ৯:৩০ মি: থেকে বেলা ১২:৩০ মি: পর্যন্ত</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Final Decision & Contact Box -->
            <div class="card sayak-card border-0 shadow-sm p-4 bg-light text-center mb-5">
                <p class="mb-2 text-muted small">
                    <i class="fas fa-info-circle text-primary me-1"></i> <strong>Rule 18:</strong> In all matters, the decision of the Library Authority shall be final and binding. (যে কোনো ক্ষেত্রে গ্রন্থাগার কর্তৃপক্ষের সিদ্ধান্তই চূড়ান্ত বলে গণ্য হবে।)
                </p>
                <p class="mb-3 text-muted small">
                    <i class="fas fa-info-circle text-primary me-1"></i> <strong>Rule 19:</strong> In case of any ambiguity or conflict in interpretation, the English terminology shall prevail.
                </p>
                <div class="d-flex justify-content-center gap-2 flex-wrap">
                    <a href="<?= BASE_URL ?>usership.php" class="btn btn-maroon font-serif fw-bold px-4 py-2" style="background-color: #7A0C0C;">
                        <i class="fas fa-id-card me-1"></i> Proceed to Usership Form
                    </a>
                    <a href="<?= BASE_URL ?>contact.php" class="btn btn-outline-secondary px-3 py-2">
                        <i class="fas fa-envelope me-1"></i> Contact Administration
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchLanguage(lang) {
    // Update active button
    document.querySelectorAll('.lang-btn').forEach(btn => {
        btn.classList.remove('active', 'btn-maroon');
        btn.classList.add('btn-outline-maroon');
    });

    const activeBtn = document.getElementById('btn-lang-' + lang);
    if (activeBtn) {
        activeBtn.classList.add('active', 'btn-maroon');
        activeBtn.classList.remove('btn-outline-maroon');
    }

    const enTexts = document.querySelectorAll('.rule-text-en');
    const bnTexts = document.querySelectorAll('.rule-text-bn');
    const dualBnTexts = document.querySelectorAll('.rule-text-dual-bn');
    const numEn = document.querySelectorAll('.num-en');
    const numBn = document.querySelectorAll('.num-bn');

    if (lang === 'en') {
        enTexts.forEach(el => el.style.display = 'block');
        bnTexts.forEach(el => el.style.display = 'none');
        dualBnTexts.forEach(el => el.style.display = 'none');
        numEn.forEach(el => el.style.display = 'inline');
        numBn.forEach(el => el.style.display = 'none');
    } else if (lang === 'bn') {
        enTexts.forEach(el => el.style.display = 'none');
        bnTexts.forEach(el => el.style.display = 'block');
        dualBnTexts.forEach(el => el.style.display = 'none');
        numEn.forEach(el => el.style.display = 'none');
        numBn.forEach(el => el.style.display = 'inline');
    } else if (lang === 'dual') {
        enTexts.forEach(el => el.style.display = 'block');
        bnTexts.forEach(el => el.style.display = 'none');
        dualBnTexts.forEach(el => el.style.display = 'block');
        numEn.forEach(el => el.style.display = 'inline');
        numBn.forEach(el => el.style.display = 'none');
    }
}
</script>

<style>
.btn-outline-maroon {
    color: #7A0C0C;
    border-color: #7A0C0C;
}
.btn-outline-maroon:hover, .btn-outline-maroon.active {
    background-color: #7A0C0C;
    color: #FFFFFF !important;
    border-color: #7A0C0C;
}
@media print {
    header, footer, .btn, .btn-group, .navbar, .top-bar {
        display: none !important;
    }
    .card {
        border: 1px solid #ccc !important;
        box-shadow: none !important;
        page-break-inside: avoid;
    }
    .rule-text-en, .rule-text-bn {
        display: block !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
