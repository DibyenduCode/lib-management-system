<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "User Rules & Regulations - Join the Library";
require_once __DIR__ . '/includes/header.php';

$rulesLead = get_setting('rules_lead', 'Official code of conduct, lending policies, and fine structures governing Sayak Library.');

$sec1Title = get_setting('rules_sec1_title', '1. Membership Card & Identity');
$sec1Content = get_setting('rules_sec1_content', "• Members must present their physical or digital Member ID (e.g. <code>SL-MEM-000001</code>) at the entry counter and borrowing desk.\n• Membership is non-transferable. Borrowing privileges are restricted to the registered member.");

$sec2Title = get_setting('rules_sec2_title', '2. Physical Book Borrowing & Limits');
$sec2Content = get_setting('rules_sec2_content', "• Active members can borrow up to <strong>3 physical books</strong> simultaneously for a standard period of <strong>14 days</strong>.\n• Reference books, rare manuscripts, and single-copy encyclopedias cannot be removed from the library reading hall.");

$sec3Title = get_setting('rules_sec3_title', '3. Fines, Grace Period & Payments');
$finePerDay = number_format((float)get_setting('fine_per_day', '5.00'), 2);
$graceDays = get_setting('grace_period_days', '2');
$sec3Content = get_setting('rules_sec3_content', "• A fine rate of <strong>₹{$finePerDay} per day</strong> applies to overdue items after a <strong>{$graceDays}-day grace period</strong>.\n• All fine payments are collected in <strong>CASH</strong> at the librarian desk with an official printed cash receipt (<code>SL-RCP-000001</code>).");

$sec4Title = get_setting('rules_sec4_title', '4. Important 15-Day Expiry & Restriction Rule (Rule #43)');
$sec4Content = get_setting('rules_sec4_content', "• If a membership has expired for <strong>MORE THAN 15 DAYS</strong>, the account status automatically shifts to <code>RESTRICTED</code>.\n• Restricted members cannot borrow new books or access member features until membership renewal is completed.\n• <strong>Note:</strong> Librarians remain fully authorized to receive returned books and collect outstanding fines from restricted accounts.");

$sec5Title = get_setting('rules_sec5_title', '5. Digital PDF Library Guidelines');
$sec5Content = get_setting('rules_sec5_content', "• Members are granted online in-browser reading privileges for digital PDFs.\n• <strong>Downloading or printing PDFs is strictly prohibited for members.</strong> Backend authorization will block direct file downloads.");

function render_rules_list_items(string $rawText): void {
    $lines = preg_split('/\r\n|\r|\n/', trim($rawText));
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;
        $cleanLine = preg_replace('/^[•\-\*]\s*/u', '', $line);
        echo '<li>' . strip_tags($cleanLine, '<strong><b><em><i><code><br><span><mark>') . "</li>\n";
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="bg-white p-4 p-md-5 rounded-3 shadow-sm border">
                <h1 class="section-title">User Rules & Regulations</h1>
                <p class="text-secondary"><?= escape($rulesLead) ?></p>
                <hr class="my-4">

                <div class="mb-4">
                    <h4 class="font-serif fw-bold text-maroon" style="color: #7A0C0C;"><?= escape($sec1Title) ?></h4>
                    <ul>
                        <?php render_rules_list_items($sec1Content); ?>
                    </ul>
                </div>

                <div class="mb-4">
                    <h4 class="font-serif fw-bold text-maroon" style="color: #7A0C0C;"><?= escape($sec2Title) ?></h4>
                    <ul>
                        <?php render_rules_list_items($sec2Content); ?>
                    </ul>
                </div>

                <div class="mb-4">
                    <h4 class="font-serif fw-bold text-maroon" style="color: #7A0C0C;"><?= escape($sec3Title) ?></h4>
                    <ul>
                        <?php render_rules_list_items($sec3Content); ?>
                    </ul>
                </div>

                <div class="mb-4">
                    <h4 class="font-serif fw-bold text-maroon" style="color: #7A0C0C;"><?= escape($sec4Title) ?></h4>
                    <ul>
                        <?php render_rules_list_items($sec4Content); ?>
                    </ul>
                </div>

                <div class="mb-4">
                    <h4 class="font-serif fw-bold text-maroon" style="color: #7A0C0C;"><?= escape($sec5Title) ?></h4>
                    <ul>
                        <?php render_rules_list_items($sec5Content); ?>
                    </ul>
                </div>

                <div class="text-center mt-5">
                    <a href="<?= BASE_URL ?>usership.php" class="btn btn-maroon btn-lg font-serif" style="background-color: #7A0C0C;">Apply for Usership Now</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
