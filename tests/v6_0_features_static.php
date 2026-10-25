<?php
$root=dirname(__DIR__);$checks=[];
$adminLayout=file_get_contents($root.'/app/Views/layouts/admin.php');
$portalLayout=file_get_contents($root.'/app/Views/layouts/portal.php');
$helpers=file_get_contents($root.'/app/helpers.php');
$routes=file_get_contents($root.'/index.php');
$js=file_get_contents($root.'/public/assets/js/notifications.js');
$css=file_get_contents($root.'/public/assets/css/admin.css');
$adminView=file_get_contents($root.'/app/Views/admin/notifications.php');
$portalView=file_get_contents($root.'/app/Views/portal/notifications.php');
$checks['Admin bell opens drawer']=str_contains($adminLayout,'data-notification-toggle')&&str_contains($adminLayout,"notification_panel_shell('admin'");
$checks['Portal bell opens drawer']=str_contains($portalLayout,'data-notification-toggle')&&str_contains($portalLayout,"notification_panel_shell('portal'");
$checks['Reusable notification shell']=str_contains($helpers,'function notification_panel_shell')&&str_contains($helpers,'data-notification-mark-all');
$checks['Read and read-all routes']=str_contains($routes,"/admin/notifications/read-all")&&str_contains($routes,"/portal/notifications/read-all");
$checks['AJAX notification engine']=str_contains($js,'data-notification-filter')&&str_contains($js,'markRead')&&str_contains($js,'markAll')&&str_contains($js,'Load older notifications');
$checks['Responsive notification UI']=str_contains($css,'notification-center-panel')&&str_contains($css,'@media(max-width:640px)')&&str_contains($css,'notification-page-item');
$checks['Admin read/unread page UI']=str_contains($adminView,'notification-read-state')&&str_contains($adminView,'data-notification-read');
$checks['Portal read/unread page UI']=str_contains($portalView,'notification-read-state')&&str_contains($portalView,'data-notification-read');
$ok=true;foreach($checks as $name=>$pass){echo ($pass?'PASS':'FAIL')." - $name\n";$ok=$ok&&$pass;}exit($ok?0:1);
