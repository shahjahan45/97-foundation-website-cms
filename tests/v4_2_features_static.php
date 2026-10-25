<?php
$root=dirname(__DIR__);
$checks=[
 'Always-visible admin pagination summary'=>['app/helpers.php','pagination-summary'],
 'Admin pagination page-size selector'=>['app/helpers.php','pagination-size'],
 'Per-list page size support'=>['app/helpers.php','per_param'],
 'Live chat start state'=>['app/Views/layouts/site.php','data-chat-state-start'],
 'Live chat active state'=>['app/Views/layouts/site.php','data-chat-state-active'],
 'Start form isolated from active chat'=>['public/assets/js/app.js',"setState('active')"],
 'Chat widget message-only scrolling'=>['public/assets/css/app.css','.live-chat-thread{flex:1;min-height:0;max-height:none;overflow-y:auto'],
 'Fixed widget active composer'=>['public/assets/css/app.css','.live-chat-send{display:block!important;flex:0 0 auto'],
 'Live chat Enter to send'=>['public/assets/js/app.js',"e.key==='Enter'&&!e.shiftKey"],
 'Live chat page-size AJAX state'=>['public/assets/js/admin-live-chat.js','data-chat-per-page'],
 'Sidebar animated max-height'=>['public/assets/css/admin.css','transition:max-height .26s'],
 'Sidebar chevron rotation'=>['public/assets/css/admin.css','rotate(90deg)'],
 'Content pagination retains active tab'=>['app/Views/admin/content.php','$activeContentTab'],
];
$fail=0;
foreach($checks as $name=>[$file,$needle]){
    $s=@file_get_contents($root.'/'.$file);$ok=$s!==false&&str_contains($s,$needle);
    echo ($ok?'[PASS] ':'[FAIL] ').$name."\n";if(!$ok)$fail++;
}
if($fail)exit(1);
echo "V4.2 static feature checks passed.\n";
