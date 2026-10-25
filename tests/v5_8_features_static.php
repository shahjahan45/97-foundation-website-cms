<?php
$root=dirname(__DIR__);$checks=[];$must=function(string $file,string $needle,string $label)use(&$checks,$root){$ok=str_contains(file_get_contents($root.'/'.$file),$needle);$checks[$label]=$ok;};
$must('app/Views/member/register.php','Personal Information','Professional member registration sections');
$must('app/Views/member/login.php','Sign in to your account','Professional member login');
$must('app/Views/layouts/site.php','floating-whatsapp','Floating WhatsApp');
$must('app/Views/admin/login.php','2FA Code','Admin login has no visible optional 2FA field');$checks['Admin login 2FA hidden']=!$checks['Admin login has no visible optional 2FA field'];unset($checks['Admin login has no visible optional 2FA field']);
$must('app/Views/admin/two-factor-challenge.php','Authenticator Code','Dedicated two-factor challenge');
$must('app/Controllers/Admin/AuthController.php',"redirect('admin/2fa')",'Conditional 2FA redirect');
$must('app/Views/portal/dashboard.php','portal-hero-v58','Professional portal dashboard');
$must('app/helpers.php','filemtime','Asset cache busting');
$failed=array_keys(array_filter($checks,fn($v)=>!$v));foreach($checks as $k=>$v)echo ($v?'[PASS] ':'[FAIL] ').$k.PHP_EOL;if($failed){exit(1);}echo "V5.8 feature checks passed.\n";
