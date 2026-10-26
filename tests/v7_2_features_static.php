<?php
// 97 Foundation V7.2 — quick static checks (no browser). Browser tests: tests/e2e/site.e2e.js
$root = dirname(__DIR__);
$read = static fn(string $f): string => (string)file_get_contents($root . '/' . $f);
$donation = $read('app/Controllers/DonationController.php');
$upload = $read('app/Core/Upload.php');
$donate = $read('app/Views/donate.php');
$index = $read('index.php');
$layout = $read('app/Views/layouts/site.php');
$js = $read('public/assets/js/app.js');
$checks = [
    'Receipt is mandatory on the server' => str_contains($donation, 'Upload your payment receipt') && str_contains($donation, 'Upload::donationProof($_FILES[\'proof\'])'),
    'Receipt validation (type, size, empty, damaged)' => str_contains($upload, 'RECEIPT_TYPES') && str_contains($upload, "'%PDF-'") && str_contains($upload, 'getimagesize'),
    'Receipt upload UI with preview and validation' => str_contains($donate, 'data-receipt-input') && str_contains($donate, 'required') && str_contains($js, 'function initReceipt'),
    'Receipt files stay private' => str_contains($read('.htaccess'), 'storage') && str_contains($read('app/Controllers/Admin/DonationAdminController.php'), "Auth::enforce('view_donations')"),
    'Fundraising switch' => is_file($root . '/app/Core/Fundraising.php') && str_contains($index, '/admin/campaigns/fundraising') && str_contains($read('app/Views/admin/campaigns.php'), 'data-fundraising-form'),
    'Closed campaigns reject donations' => str_contains($donation, 'Fundraising::campaignOpen') && str_contains($read('app/Payments/PaymentService.php'), 'Fundraising::campaignOpen'),
    'News categories and readable URLs' => str_contains($index, "/news/category/{slug}") && str_contains($read('app/Controllers/Admin/PostAdminController.php'), 'uniqueSlug') && str_contains($read('database/schema.sql'), 'CREATE TABLE IF NOT EXISTS post_categories'),
    'Publish / unpublish action' => str_contains($index, '/admin/posts/status'),
    'Safe article formatting' => str_contains($read('app/content_ui.php'), 'function article_render') && str_contains($read('app/content_ui.php'), 'Everything is escaped first'),
    'Share buttons (WhatsApp, Facebook, LinkedIn, X, Telegram, email, copy, native)' => (bool)preg_match("/whatsapp.*facebook.*linkedin.*'x'.*telegram/s", $read('app/content_ui.php')) && str_contains($read('app/content_ui.php'), 'data-share-copy') && str_contains($js, 'navigator.share'),
    'Article Open Graph + structured data' => str_contains($layout, 'article:published_time') && str_contains($layout, 'NewsArticle') && str_contains($layout, 'BreadcrumbList'),
    'Private pages are noindex' => str_contains($layout, 'noindex,nofollow'),
    'Sitemap lastmod + robots private paths' => str_contains($read('app/Controllers/HomeController.php'), '<lastmod>') && str_contains($read('app/Controllers/HomeController.php'), "'/donate/payment/'"),
    'Automatic database update' => str_contains($read('bootstrap.php'), 'Upgrade72::autoApply') && is_file($root . '/database/upgrade_v7_2.php'),
    'Homepage motion respects reduced motion' => str_contains($read('public/assets/js/motion-flag.js'), 'prefers-reduced-motion') && str_contains($read('public/assets/css/site-home.css'), '@media (prefers-reduced-motion: reduce)'),
    'Self-hosted Inter font (no external fonts)' => is_file($root . '/public/assets/fonts/inter-latin.woff2') && !str_contains($layout, 'fonts.googleapis.com'),
    'Playwright end-to-end suite' => is_file($root . '/tests/e2e/site.e2e.js'),
    'Online payments on/off switch' => str_contains($index, '/admin/developer/payment-gateway/toggle') && is_file($root . '/app/Views/admin/_online-switch.php') && str_contains($read('app/Controllers/Admin/PaymentGatewayAdminController.php'), 'ONLINE_PAYMENTS_DISABLED'),
    'Compact gallery with one-click show/hide' => str_contains($index, '/admin/gallery/toggle') && str_contains($read('app/Views/admin/gallery.php'), 'v7-media-grid'),
];
$ok = true;
foreach ($checks as $name => $pass) { echo ($pass ? 'PASS' : 'FAIL') . " - $name\n"; $ok = $ok && $pass; }
echo $ok ? "V7.2 checks passed.\n" : "V7.2 checks failed.\n";
exit($ok ? 0 : 1);
