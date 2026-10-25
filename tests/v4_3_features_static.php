<?php
$root=dirname(__DIR__);
$checks=[
 'Volunteer List submenu'=>['app/Views/layouts/admin.php','Volunteer List'],
 'Registration Management submenu'=>['app/Views/layouts/admin.php','Registration Management'],
 'Admin credential route'=>['index.php',"/admin/profile/account"],
 'Admin credential verification'=>['app/Controllers/Admin/ProfileAdminController.php','password_verify($current'],
 'Argon2id admin password update'=>['app/Controllers/Admin/ProfileAdminController.php','PASSWORD_ARGON2ID'],
 'Dedicated Close Chat button'=>['app/Views/admin/live-chat.php','data-chat-close-conversation'],
 'Close Chat visitor notification'=>['app/Controllers/Admin/LiveChatAdminController.php','The visitor has been notified'],
 'System close timeline message'=>['app/Controllers/Admin/LiveChatAdminController.php','sender_type,sender_user_id,sender_name,message) VALUES(?,"system"'],
 'Public closed conversation state'=>['app/Views/layouts/site.php','data-chat-closed-state'],
 'Public Start New Chat action'=>['app/Views/layouts/site.php','data-chat-new'],
 'Portal SPA script'=>['app/Views/layouts/portal.php',"asset('js/portal.js')"],
 'Portal History API navigation'=>['public/assets/js/portal.js','history.pushState'],
 'Portal branded spinner'=>['public/assets/css/admin.css','portalLogoSpin'],
 'CR80 standard card note'=>['app/Views/portal/id-card.php','85.60 × 53.98 mm'],
 'ID PNG download action'=>['app/Views/portal/id-card.php','data-download-id'],
 'ID exact print size'=>['public/assets/css/admin.css','85.6mm!important;height:53.98mm!important'],
 'Slower sidebar transition'=>['public/assets/css/admin.css','max-height .42s'],
];
$absence=[
 'Admin emoji picker removed'=>['app/Views/admin/live-chat.php','data-chat-emoji'],
 'Public emoji picker removed'=>['app/Views/layouts/site.php','data-widget-emoji'],
 'Admin emoji JS removed'=>['public/assets/js/admin-live-chat.js','data-chat-emoji'],
 'Public emoji JS removed'=>['public/assets/js/app.js','data-widget-emoji'],
];
$fail=0;
foreach($checks as $name=>[$file,$needle]){$s=@file_get_contents($root.'/'.$file);$ok=$s!==false&&str_contains($s,$needle);echo ($ok?'[PASS] ':'[FAIL] ').$name."\n";if(!$ok)$fail++;}
foreach($absence as $name=>[$file,$needle]){$s=@file_get_contents($root.'/'.$file);$ok=$s!==false&&!str_contains($s,$needle);echo ($ok?'[PASS] ':'[FAIL] ').$name."\n";if(!$ok)$fail++;}
if($fail)exit(1);echo "V4.3 static feature checks passed.\n";
