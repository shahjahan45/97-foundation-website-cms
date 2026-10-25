<?php
declare(strict_types=1);
$root=dirname(__DIR__);
require $root.'/bootstrap.php';
$checks=[];
try {
    $checks['Donation controller loads without inheritance fatal']=class_exists(\App\Controllers\Admin\DonationAdminController::class);
    $ref=new ReflectionClass(\App\Controllers\Admin\DonationAdminController::class);
    $checks['Donation details action exists']=$ref->hasMethod('details') && $ref->getMethod('details')->isPublic();
    $checks['Donation controller does not override base view']=$ref->getMethod('view')->getDeclaringClass()->getName()===\App\Core\Controller::class;
} catch (Throwable $e) {
    fwrite(STDERR,$e->getMessage().PHP_EOL);
    exit(1);
}
$routes=file_get_contents($root.'/index.php');
$adminJs=file_get_contents($root.'/public/assets/js/admin.js');
$checks['Donation view route points to details']=str_contains($routes,"[DonationAdminController::class,'details']");
$checks['Admin loader handles bfcache pageshow']=str_contains($adminJs,"addEventListener('pageshow',recoverNavigationUi)");
$checks['Admin loader handles browser popstate']=str_contains($adminJs,"addEventListener('popstate'");
$failed=array_keys(array_filter($checks,fn($ok)=>!$ok));
foreach($checks as $name=>$ok) echo ($ok?'PASS':'FAIL')." - $name\n";
if($failed){fwrite(STDERR,"Regression checks failed: ".implode(', ',$failed)."\n");exit(1);} 
