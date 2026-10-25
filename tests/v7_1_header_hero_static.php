<?php
/** V7.1 — header, navigation, hero slider, officials and admin design checks. */
$root = dirname(__DIR__);
$read = static fn(string $f): string => (string)file_get_contents($root . '/' . $f);
$layout = $read('app/Views/layouts/site.php');
$ui = $read('app/site_ui.php');
$home = $read('app/Views/home.php');
$js = $read('public/assets/js/app.js');
$css = $read('public/assets/css/site-v7.css');
$index = $read('index.php');
$schema = $read('database/schema.sql');
$admin = $read('app/Views/layouts/admin.php');
$checks = [
    'Header email 97found@gmail.com default' => str_contains($read('app/Core/Upgrade71.php'), "EMAIL = '97found@gmail.com'") && str_contains($schema, "'97found@gmail.com'"),
    'Header phone default' => str_contains($read('app/Core/Upgrade71.php'), "PHONE = '+94 71 258 3411'"),
    'Facebook page in top bar' => str_contains($layout, 'site_facebook_icon()') && str_contains($schema, 'facebook.com/share/19hyNTG2oZ'),
    'Material Symbols mail + call icons' => str_contains($ui, "'mail' => 'M160-160q") && str_contains($ui, "'call' => 'M798-120q") && str_contains($layout, "site_icon('mail')") && str_contains($layout, "site_icon('call')"),
    'New logo + foundation text image' => is_file($root . '/public/assets/images/brand/97-foundation-logo.png') && is_file($root . '/public/assets/images/brand/97-foundation-wordmark.png') && str_contains($layout, 'brand7-wordmark'),
    'Menu: About Us submenu' => str_contains($ui, "'What We Do'") && str_contains($ui, "'97 Foundation'"),
    'Menu: Members submenu' => str_contains($ui, "'Officials'") && str_contains($ui, "'Past Officials'"),
    'Menu order' => (bool)preg_match("/'Home'.*'About Us'.*'Members'.*'News & Blogs'.*'Gallery'.*'Volunteer'.*'Contact Us'/s", $ui),
    'Donate Now + Login buttons' => str_contains($layout, 'btn7 btn7-donate') && str_contains($layout, 'btn7 btn7-login'),
    'Accessible dropdowns' => str_contains($layout, 'aria-haspopup="true"') && str_contains($js, "e.key==='ArrowDown'"),
    'Mobile drawer with focus trap' => str_contains($layout, 'id="mobile-nav7"') && str_contains($js, "e.key!=='Tab'"),
    'Five background images' => str_contains($ui, 'const HERO_SLIDE_COUNT = 5') && count(glob($root . '/public/assets/images/hero/hero-[1-5].jpg')) === 5,
    'Horizontal seamless loop' => str_contains($js, 'translate3d(${-p*100}%,0,0)') && str_contains($js, 'headClone') && str_contains($js, 'tailClone'),
    'Pagination dots bottom centre' => str_contains($home, 'class="hero7-dots"') && str_contains($css, '.hero7-bar-inner{min-height:54px;display:grid;grid-template-columns:1fr auto 1fr'),
    'Higher-opacity green overlay' => str_contains($css, '--hero7-overlay:.82') && str_contains($ui, "'overlay_opacity' => '82'"),
    'Reduced motion respected' => str_contains($js, "prefers-reduced-motion: reduce") && str_contains($css, '@media (prefers-reduced-motion: reduce)'),
    'Officials routes' => str_contains($index, "/members/officials") && str_contains($index, "/members/past-officials") && str_contains($index, "/admin/officials/save"),
    'Officials table in schema' => str_contains($schema, 'CREATE TABLE IF NOT EXISTS foundation_officials'),
    'What We Do page route' => str_contains($index, "'/what-we-do'"),
    'V7.1 upgrade script' => is_file($root . '/database/upgrade_v7_1.php'),
    'Admin hero banner save route' => str_contains($index, "/admin/hero/save"),
    'Admin professional layer' => str_contains($admin, "css/admin-v7.css") && str_contains($admin, 'admin_breadcrumb(') && str_contains($admin, "admin/officials"),
    'Header logo size controls' => str_contains($read('app/Views/admin/branding.php'), 'name="header_logo_height"') && str_contains($read('app/Controllers/Admin/BrandingAdminController.php'), "'logo_height'") && str_contains($css, '.brand7-logo{height:var(--logo-h)'),
    'Logo empty-space trimming' => str_contains($read('app/Core/BrandImage.php'), 'function optimize(') && str_contains($read('app/Controllers/Admin/BrandingAdminController.php'), 'BrandImage::optimize'),
    'Administrators manager (Super Admin only)' => str_contains($index, "/admin/administrators/save") && str_contains($read('app/Controllers/Admin/AdministratorAdminController.php'), "!== 'super_admin'") && str_contains($read('app/Controllers/Admin/AdministratorAdminController.php'), 'activeSuperAdmins') && str_contains($admin, "admin/administrators"),
    'Sidebar profile card with Sign out underneath' => (bool)preg_match('~class="side-profile.*?</a>\s*<form[^>]+admin/logout~s', $admin),
    'CSP-safe assets (no external fonts/scripts)' => !str_contains($layout, 'fonts.googleapis.com') && !preg_match('~<script[^>]+src="https?://~', $layout),
];
$ok = true;
foreach ($checks as $name => $pass) { echo ($pass ? 'PASS' : 'FAIL') . " - $name\n"; $ok = $ok && $pass; }
echo $ok ? "V7.1 header, hero and officials checks passed.\n" : "V7.1 checks failed.\n";
exit($ok ? 0 : 1);
