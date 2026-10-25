<?php
$root=dirname(__DIR__);
$checks=[
 'branding website'=>str_contains(file_get_contents($root.'/app/Views/admin/branding.php'),'Organization Website')&&str_contains(file_get_contents($root.'/app/Controllers/Admin/BrandingAdminController.php'),"'website'"),
 'id website'=>str_contains(file_get_contents($root.'/app/Views/portal/id-card.php'),'data-card-website')&&str_contains(file_get_contents($root.'/public/assets/js/portal.js'),'cardWebsite'),
 'five member columns'=>str_contains(file_get_contents($root.'/public/assets/css/app.css'),'repeat(5,minmax(0,1fr))'),
 'chat closed public css'=>str_contains(file_get_contents($root.'/public/assets/css/app.css'),'.live-chat-closed-state')&&str_contains(file_get_contents($root.'/app/Views/layouts/site.php'),'Support conversation completed'),
 'compact portal loader'=>str_contains(file_get_contents($root.'/app/Views/layouts/portal.php'),'portal-route-loader')&&str_contains(file_get_contents($root.'/public/assets/css/admin.css'),'.portal-route-loader'),
];
foreach($checks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)exit(1);}
