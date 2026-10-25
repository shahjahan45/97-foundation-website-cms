<?php
$root=dirname(__DIR__);
$checks=[
 'Unified admin profile component'=>['app/Views/admin/profile.php','admin-profile-unified-card'],
 'Inline account security section'=>['app/Views/admin/profile.php','Account Security'],
 'Password visibility control'=>['public/assets/js/admin.js','data-password-toggle'],
 'Password match validation'=>['public/assets/js/admin.js','Passwords do not match.'],
 'Professional social SVG helper'=>['app/helpers.php','function social_icon_svg'],
 'Automatic social platform detection'=>['app/helpers.php','function social_platform_key'],
 'Footer professional social icons'=>['app/Views/layouts/site.php','social_icon_svg($sl[\'platform\']'],
 'Admin social icon auto detection'=>['app/Views/admin/social.php','Automatic professional icon'],
];
$failed=[];
foreach($checks as $name=>[$file,$needle]){ $p=$root.'/'.$file; $ok=is_file($p)&&str_contains((string)file_get_contents($p),$needle); echo ($ok?'[PASS] ':'[FAIL] ').$name.PHP_EOL; if(!$ok)$failed[]=$name; }
exit($failed?1:0);
