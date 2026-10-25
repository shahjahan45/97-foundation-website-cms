<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$checks=[
 'Numeric CAPTCHA'=>str_contains(file_get_contents($root.'/app/Core/Captcha.php'),'random_int(1000,9999)'),
 'Volunteer registration password'=>str_contains(file_get_contents($root.'/app/Controllers/VolunteerController.php'),'password_hash'),
 'Volunteer ID generation'=>str_contains(file_get_contents($root.'/app/Controllers/Admin/VolunteerAdminController.php'),'97V-'),
 'Portal messaging'=>str_contains(file_get_contents($root.'/app/Controllers/Portal/PortalController.php'),'INSERT INTO messages'),
 'Portal notifications'=>is_file($root.'/app/Controllers/Portal/NotificationPortalController.php') && str_contains(file_get_contents($root.'/app/Controllers/Portal/NotificationPortalController.php'),'UPDATE notifications SET read_at'),
 'Gallery management'=>is_file($root.'/app/Controllers/Admin/GalleryAdminController.php') && is_file($root.'/app/Views/gallery.php'),
 'Cause/campaign separation'=>is_file($root.'/app/Controllers/Admin/CauseAdminController.php') && str_contains(file_get_contents($root.'/index.php'),"/admin/causes"),
 'Campaign visibility'=>str_contains(file_get_contents($root.'/app/Controllers/Admin/CampaignAdminController.php'),'is_visible'),
 'Admin logo upload'=>str_contains(file_get_contents($root.'/app/Controllers/Admin/BrandingAdminController.php'),"'logo_path'"),
 'SPA History API'=>str_contains(file_get_contents($root.'/public/assets/js/app.js'),'history.pushState') && str_contains(file_get_contents($root.'/public/assets/js/app.js'),'DOMParser'),
 'Branded loader'=>str_contains(file_get_contents($root.'/app/Views/layouts/site.php'),'page-loader') && str_contains(file_get_contents($root.'/public/assets/js/app.js'),'setLoading'),
 'SEO sitemap'=>str_contains(file_get_contents($root.'/app/Controllers/HomeController.php'),'sitemap') && str_contains(file_get_contents($root.'/app/Views/layouts/site.php'),'application/ld+json'),
 'AEO quick answer'=>str_contains(file_get_contents($root.'/app/Views/layouts/site.php'),"answer_summary") && str_contains(file_get_contents($root.'/app/Views/admin/content.php'),'AEO Quick Answer'),
 'Core page deletion guard'=>str_contains(file_get_contents($root.'/app/Controllers/Admin/ContentAdminController.php'),'core website page'),
 'Private donation proof'=>str_contains(file_get_contents($root.'/app/Core/Upload.php'),'donation-proofs'),
];
$failed=0;
foreach($checks as $name=>$ok){echo ($ok?'PASS':'FAIL').": $name\n";if(!$ok)$failed++;}
exit($failed?1:0);
