<?php
$root=dirname(__DIR__);
$members=file_get_contents($root.'/app/Views/members.php');
$upload=file_get_contents($root.'/app/Core/Upload.php');
$css=file_get_contents($root.'/public/assets/css/app.css');
$js=file_get_contents($root.'/public/assets/js/app.js');
$checks=[
 'responsive member image helper'=>str_contains($members,'member_photo_sources'),
 'async decoding'=>str_contains($members,'decoding="async"'),
 'priority loading'=>str_contains($members,'fetchpriority='),
 'full image contain'=>str_contains($css,'object-fit:contain!important'),
 'skeleton state'=>str_contains($css,'memberSkeleton'),
 'image load initializer'=>str_contains($js,'initMemberDirectoryImages'),
 'webp generation'=>str_contains($upload,'imagewebp'),
 'member photo upload optimizer'=>str_contains($upload,'publicMemberPhoto'),
];
foreach($checks as $name=>$ok){echo ($ok?'[OK] ':'[FAIL] ').$name."\n";if(!$ok)exit(1);}
