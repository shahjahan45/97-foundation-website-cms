<?php
$root=dirname(__DIR__);
$checks=[
 'Donate Now nowrap'=>['public/assets/css/app.css','white-space:nowrap'],
 'Glass footer'=>['public/assets/css/app.css','footer-glass-shell'],
 'Footer donation QR'=>['app/Views/layouts/site.php','data-qr-text'],
 'Campaign active field'=>['database/schema.sql','is_active TINYINT(1) NOT NULL DEFAULT 1'],
 'Campaign active toggle route'=>['index.php','admin/campaigns/toggle'],
 'Campaign visibility route'=>['index.php','admin/campaigns/visibility'],
 'Donation pagination'=>['app/Controllers/Admin/DonationAdminController.php','admin_pager($total,$per)'],
 'Content multi pagination'=>['app/Controllers/Admin/ContentAdminController.php',"'sections_page'"],
 'Volunteer applications route'=>['index.php','admin/volunteers/applications'],
 'Volunteer applications view'=>['app/Views/admin/volunteer-applications.php','Pending Applications'],
 'Nested volunteer menu'=>['app/Views/layouts/admin.php','data-side-toggle'],
 'Professional live chat icon'=>['app/Views/layouts/site.php','chat-launch-icon'],
 'Robust chat base URL'=>['app/Views/layouts/site.php','data-chat-base'],
 'Live chat JSON handling'=>['public/assets/js/app.js','readJson'],
 'V4 migration'=>['database/upgrade_v4.php','V4 database upgrade completed successfully'],
];
$fail=0;foreach($checks as $name=>[$file,$needle]){$s=@file_get_contents($root.'/'.$file);$ok=$s!==false&&str_contains($s,$needle);echo ($ok?'[PASS] ':'[FAIL] ').$name."\n";if(!$ok)$fail++;}if($fail){exit(1);}echo "V4 static feature checks passed.\n";
