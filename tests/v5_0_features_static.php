<?php
$root=dirname(__DIR__);$checks=[];
$checks['LKR money helper']=str_contains(file_get_contents($root.'/app/helpers.php'),"return 'LKR '");
$checks['No legacy currency literal in app']=!str_contains(shell_exec('grep -R "Q'.'AR" -n '.escapeshellarg($root.'/app').' 2>/dev/null')?:'','Q'.'AR');
$checks['Broadcast audiences']=str_contains(file_get_contents($root.'/app/Controllers/Admin/MessageAdminController.php'),"'all_users'")&&str_contains(file_get_contents($root.'/app/Controllers/Admin/MessageAdminController.php'),"'administrators'");
$checks['Broadcast history schema']=str_contains(file_get_contents($root.'/database/schema.sql'),'message_broadcasts');
$checks['Dashboard column chart']=str_contains(file_get_contents($root.'/app/Views/admin/dashboard.php'),'donation-column-chart');
$checks['Favicon upload']=str_contains(file_get_contents($root.'/app/Controllers/Admin/BrandingAdminController.php'),"'favicon_path'")&&str_contains(file_get_contents($root.'/app/Views/admin/branding.php'),'Browser Tab Logo / Favicon');
$checks['Favicon layout output']=str_contains(file_get_contents($root.'/app/Views/layouts/site.php'),'rel="icon"')&&str_contains(file_get_contents($root.'/app/Views/layouts/admin.php'),'rel="icon"');
$checks['Image size guide']=str_contains(file_get_contents($root.'/app/Views/admin/branding.php'),'Website Image Size Guide');
$checks['Professional verified tag']=str_contains(file_get_contents($root.'/app/Views/admin/donation-view.php'),'donation-record-status');
$ok=true;foreach($checks as $name=>$pass){echo ($pass?'PASS':'FAIL')." - $name\n";$ok=$ok&&$pass;}exit($ok?0:1);
