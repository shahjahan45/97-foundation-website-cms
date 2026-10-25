<?php
$root=dirname(__DIR__);$ok=true;
$checks=[
 ['app/Views/admin/content.php','Professional Home Page Builder'],
 ['app/Views/admin/content.php','home_about'],
 ['app/Views/admin/content.php','Published event images auto-feed home'],
 ['app/Views/home.php','home_events'],
 ['app/Views/home.php','home_news'],
 ['app/Views/layouts/site.php',"contact_icon_svg('address')"],
 ['app/helpers.php','function contact_icon_svg'],
 ['database/upgrade_v5_4.php','item_limit'],
];
foreach($checks as [$file,$needle]){$txt=file_get_contents($root.'/'.$file);if(strpos($txt,$needle)===false){fwrite(STDERR,"Missing $needle in $file\n");$ok=false;}}
exit($ok?0:1);
