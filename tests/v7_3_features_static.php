<?php
// 97 Foundation V7.3 — quick checks (no browser, no database). Browser tests: tests/e2e/site.e2e.js
declare(strict_types=1);
$root = dirname(__DIR__);
putenv('PERSONAL_DATA_KEY=' . str_repeat('k', 48)); // test key only; never touches storage/private
require $root . '/bootstrap.php';

use App\Core\PersonalDataVault;
use App\Core\Scholarship;

$read = static fn(string $f): string => (string)file_get_contents($root . '/' . $f);
$index = $read('index.php');
$layout = $read('app/Views/layouts/site.php');
$admin = $read('app/Controllers/Admin/ScholarshipAdminController.php');
$public = $read('app/Controllers/ScholarshipController.php');
$form = $read('app/Views/scholarships.php');
$good = ['student_name' => 'Kavindi Perera', 'student_grade' => 'grade_11', 'guardian_name' => 'නිමාලි පෙරේරා', 'guardian_nic' => '853202937v', 'mobile' => '+94 71 234 5678', 'address' => 'No. 12, Temple Road, Thambala', 'purpose' => 'ol', 'consent' => '1'];
[$clean, $errors] = Scholarship::validate($good);
[, $bad] = Scholarship::validate(['student_name' => 'K1', 'student_grade' => 'grade_20', 'guardian_nic' => '853679937V', 'mobile' => '0112345678', 'address' => 'short', 'purpose' => 'holiday']);
$cipher = PersonalDataVault::encrypt('853202937V');

$checks = [
    'Valid application passes (Sinhala names, +94 mobile, lower-case NIC)' => $errors === [] && $clean['guardian_nic'] === '853202937V' && $clean['mobile'] === '0712345678' && $clean['education_level'] === 'ol',
    'Invalid application is rejected field by field' => count($bad) === 8,
    'NIC rules: old + new formats, impossible dates rejected' => Scholarship::normaliseNic('198532002937') === '198532002937' && Scholarship::normaliseNic('853679937V') === null && Scholarship::normaliseNic('204032002937') === null,
    'NIC stored encrypted and readable only with the key' => str_starts_with($cipher, 'p1.') && !str_contains($cipher, '853202937') && PersonalDataVault::decrypt($cipher) === '853202937V' && PersonalDataVault::decrypt('p1.x.y.z') === '',
    'NIC masking' => Scholarship::maskNic('937V') === '••••••937V',
    'Exactly the five review statuses, default Pending' => array_keys(Scholarship::STATUSES) === ['pending', 'under_review', 'shortlisted', 'approved', 'rejected'] && str_contains($public, "'pending', 'not_assessed'"),
    'Purpose dropdown has the four required options' => array_values(Scholarship::PURPOSES) === ['G.C.E. Ordinary Level (O/L)', 'G.C.E. Advanced Level (A/L)', 'University Education', 'General Education Support'],
    'No automatic approval: approval needs eligibility + comment' => str_contains($admin, "\$status === 'approved' && \$eligibility !== 'eligible'") && str_contains($admin, 'Add a selection comment'),
    'Admin access limited to manage_scholarships' => str_contains($admin, "Auth::enforce('manage_scholarships')") && str_contains($read('app/Core/Auth.php'), "'manage_scholarships'"),
    'Full NIC shown only after an audited request' => str_contains($admin, 'SCHOLARSHIP_NIC_VIEWED') && str_contains($admin, 'REVEAL_SECONDS'),
    'NIC search posted, never in the URL' => str_contains($index, "/admin/scholarships/find-nic") && str_contains($admin, "Session::put('scholarship_nic_filter'"),
    'Logs and notifications carry the reference only' => !preg_match('/Audit::write\([^;]*(student_name|guardian_name|guardian_nic)/', $public . $admin) && !preg_match('/Notification::admins\([^;]*(student_name|guardian_nic)/', $public),
    'CSV export masks NIC and blocks formula injection' => str_contains($admin, 'maskNic') && str_contains($admin, "preg_match('/^[=+\\-@\\t\\r]/'"),
    'Spam protection: CSRF, honeypot and hourly limit' => str_contains($public, '$this->requirePost()') && str_contains($form, 'name="website"') && str_contains($public, 'MAX_PER_HOUR'),
    'Accessible form: labels, error summary, aria wiring' => str_contains($form, 'data-sch-summary') && str_contains($form, 'aria-describedby') && str_contains($read('public/assets/js/app.js'), 'function initScholarshipForm'),
    'Confirmation page is noindex and disallowed' => str_contains($layout, 'scholarships/submitted') && str_contains($read('app/Controllers/HomeController.php'), "'/scholarships/submitted'"),
    'Database tables in schema + automatic update' => str_contains($read('database/schema.sql'), 'CREATE TABLE IF NOT EXISTS scholarship_applications') && str_contains($read('bootstrap.php'), 'Upgrade73::autoApply') && is_file($root . '/database/upgrade_v7_3.php'),
    'Importable SQL update (phpMyAdmin)' => str_contains($read('database/upgrade_v7_3.sql'), 'CREATE TABLE IF NOT EXISTS scholarship_reviews') && str_contains($read('database/upgrade_v7_3.sql'), 'INSERT IGNORE INTO pages'),
    'Redesigned glass footer with legal and scholarship links' => str_contains($layout, 'class="f73"') && str_contains($read('public/assets/css/site-v73.css'), 'backdrop-filter') && str_contains($layout, "url('privacy-policy')") && str_contains($layout, "url('terms')") && str_contains($layout, "url('scholarships')"),
    'Privacy Policy and Terms pages seeded' => (bool)array_filter(App\Core\Upgrade73::pages(), static fn($p) => $p[0] === 'privacy-policy') && (bool)array_filter(App\Core\Upgrade73::pages(), static fn($p) => $p[0] === 'terms'),
    'Admin sidebar and dashboard entries' => str_contains($read('app/Views/layouts/admin.php'), "url('admin/scholarships')") && str_contains($read('app/Views/admin/dashboard.php'), 'Scholarship applications'),
];
$ok = true;
foreach ($checks as $name => $pass) { echo ($pass ? 'PASS' : 'FAIL') . " - $name\n"; $ok = $ok && $pass; }
echo $ok ? "V7.3 checks passed.\n" : "V7.3 checks failed.\n";
exit($ok ? 0 : 1);
