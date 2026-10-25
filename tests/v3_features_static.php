<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$checks=[
 'Member verification route'=>str_contains(file_get_contents($root.'/index.php'),'/verify/member/{token}'),
 'Volunteer verification route'=>str_contains(file_get_contents($root.'/index.php'),'/verify/volunteer/{token}'),
 'News admin route'=>str_contains(file_get_contents($root.'/index.php'),'/admin/posts'),
 'Social admin route'=>str_contains(file_get_contents($root.'/index.php'),'/admin/social'),
 'Live chat admin route'=>str_contains(file_get_contents($root.'/index.php'),'/admin/live-chat'),
 'Live chat public route'=>str_contains(file_get_contents($root.'/index.php'),'/chat/start'),
 'Pagination helper'=>str_contains(file_get_contents($root.'/app/helpers.php'),'function admin_pager'),
 'Local QR library'=>is_file($root.'/public/assets/js/vendor/qrcode.min.js'),
 'Portal QR target'=>str_contains(file_get_contents($root.'/app/Views/portal/id-card.php'),'data-qr-code'),
 'Receipt inline view'=>str_contains(file_get_contents($root.'/app/Controllers/Admin/DonationAdminController.php'),'Content-Disposition: inline'),
 'Receipt new tab'=>str_contains(file_get_contents($root.'/app/Views/admin/donations.php'),'target="_blank"'),
 'IBAN hidden from donation UI'=>!stripos(file_get_contents($root.'/app/Views/donate.php'),'iban'),
 'SWIFT hidden from donation UI'=>!stripos(file_get_contents($root.'/app/Views/donate.php'),'swift'),
 'Members public page'=>is_file($root.'/app/Views/members.php'),
 'News public page'=>is_file($root.'/app/Views/posts.php'),
 'Chat tables in schema'=>str_contains(file_get_contents($root.'/database/schema.sql'),'chat_conversations'),
 'Post tables in schema'=>str_contains(file_get_contents($root.'/database/schema.sql'),'CREATE TABLE IF NOT EXISTS posts'),
 'Social tables in schema'=>str_contains(file_get_contents($root.'/database/schema.sql'),'CREATE TABLE IF NOT EXISTS social_links'),
 'V3 upgrade helper'=>is_file($root.'/database/upgrade_v3.php'),
];
$bad=[];foreach($checks as $name=>$ok){echo ($ok?'[PASS] ':'[FAIL] ').$name.PHP_EOL;if(!$ok)$bad[]=$name;}if($bad){exit(1);}echo "V3 static feature checks passed.\n";
