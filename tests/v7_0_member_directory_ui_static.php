<?php
$root=dirname(__DIR__);
$view=file_get_contents($root.'/app/Views/members.php');
$css=file_get_contents($root.'/public/assets/css/app.css');
$js=file_get_contents($root.'/public/assets/js/app.js');
$controller=file_get_contents($root.'/app/Controllers/HomeController.php');
$checks=[
 'member toolbar exists'=>str_contains($view,'data-member-search')&&str_contains($view,'data-member-status')&&str_contains($view,'data-member-sort'),
 'member cards preserve verify route'=>str_contains($view,"url('verify/member/'"),
 'four column desktop'=>str_contains($css,'grid-template-columns:repeat(4,minmax(0,1fr))'),
 'three column tablet'=>str_contains($css,'@media(max-width:1100px)')&&str_contains($css,'grid-template-columns:repeat(3,minmax(0,1fr))'),
 'two column mobile'=>str_contains($css,'@media(max-width:560px)')&&str_contains($css,'grid-template-columns:repeat(2,minmax(0,1fr))!important'),
 'client filter initializer'=>str_contains($js,'function initMemberDirectory()')&&str_contains($js,"data-member-directory"),
 'public member business rule unchanged'=>str_contains($controller,'u.status="active" AND m.is_public=1'),
];
$failed=[];foreach($checks as $name=>$ok){echo ($ok?'PASS':'FAIL')." - $name\n";if(!$ok)$failed[]=$name;}
exit($failed?1:0);
