<?php
$root=dirname(__DIR__);
$checks=[];
function has67(string $file,string $needle):bool{global $root;return str_contains((string)file_get_contents($root.'/'.$file),$needle);}
$checks['registration password minimum 8']=has67('app/Views/member/register.php','minlength="8"');
$checks['registration password maximum 12']=has67('app/Views/member/register.php','maxlength="12"');
$checks['server password minimum config']=has67('config.php',"'member_password_min' => 8");
$checks['server password maximum config']=has67('config.php',"'member_password_max' => 12");
$checks['registration remains pending']=has67('app/Controllers/MemberRegistrationController.php','"member","pending"');
$checks['admin approval publishes']=has67('app/Controllers/Admin/MemberAdminController.php','SET is_public=1,approved_by=?');
$checks['non-active member unpublishes']=has67('app/Controllers/Admin/MemberAdminController.php','SET is_public=0 WHERE user_id=?');
$checks['public directory only active/public']=has67('app/Controllers/HomeController.php','u.status="active" AND m.is_public=1');
$checks['member save avoids fragile upsert']=!has67('app/Controllers/Admin/MemberAdminController.php','ON DUPLICATE KEY UPDATE mobile=VALUES');
$checks['member auth excluded from SPA']=has67('public/assets/js/app.js',"p.includes('/member/')");
$checks['global production exception handler']=has67('bootstrap.php','set_exception_handler');
$checks['fatal shutdown handler']=has67('bootstrap.php','register_shutdown_function');
$checks['csrf browser secret cookie']=has67('app/Core/Csrf.php',"foundation97_csrf_key");
$checks['migration repairs member schema']=is_file($root.'/database/upgrade_v6_7.php');
$checks['mobile live chat safe area']=has67('public/assets/css/app.css','env(safe-area-inset-bottom)');
$checks['production member checker']=is_file($root.'/tools/member-production-check.php');
$fail=[];foreach($checks as $name=>$ok){echo ($ok?'[OK] ':'[FAIL] ').$name.PHP_EOL;if(!$ok)$fail[]=$name;}
if($fail){fwrite(STDERR,'V6.7 checks failed: '.implode(', ',$fail).PHP_EOL);exit(1);}echo "V6.7 member production checks passed.\n";
