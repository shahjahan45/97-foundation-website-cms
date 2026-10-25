<?php
$root=dirname(__DIR__);$checks=[];
$must=function(string $file,string $needle,string $label)use(&$checks,$root){$text=file_get_contents($root.'/'.$file);$ok=str_contains($text,$needle);$checks[]=[ $ok,$label ];if(!$ok)fwrite(STDERR,"FAIL: $label\n");};
$must('index.php',"/member/register",'Public member registration routes');
$must('index.php',"/member/login",'Separate member login route');
$must('app/Controllers/MemberRegistrationController.php','97M-%s-%06d','Automatic Member ID generation');
$must('app/Controllers/MemberRegistrationController.php','PASSWORD_ARGON2ID','Secure registration password hashing');
$must('database/upgrade_v5_7.php',"mobile_image_path",'Hero mobile-image migration');
$must('database/upgrade_v5_7.php',"nic_number",'Member identity migration');
// V7.1: responsive desktop/mobile images are managed per background slot of the hero banner.
$must('app/Views/home.php','<source media="(max-width: 768px)"','Responsive hero picture element');
$must('app/Views/admin/content.php','name="bg_mobile_','Desktop/mobile hero management');
$must('app/Views/admin/content.php','hero7-slot-no','Ordered hero background slots');
$must('app/Views/layouts/site.php','floating-whatsapp','Floating WhatsApp button');
$must('app/Controllers/Admin/MemberAdminController.php','resetPassword','Admin member password reset');
$must('app/Controllers/Admin/MemberAdminController.php','public function status','Member approval/status management');
$must('app/Views/portal/dashboard.php','Foundation Announcements','Member announcements dashboard');
$must('app/Views/portal/dashboard.php','Upcoming Events','Member events dashboard');
if(array_filter($checks,fn($c)=>!$c[0]))exit(1);echo "V5.7 feature checks passed (".count($checks)." checks).\n";
