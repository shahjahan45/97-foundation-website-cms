<?php
$root=dirname(__DIR__);
$checks=[
 'Fixed live chat body'=>['public/assets/css/admin.css','body.admin-live-chat-page'],
 'Three panel live chat grid'=>['public/assets/css/admin.css','grid-template-columns:minmax(270px,305px)'],
 'Message-only scrolling'=>['public/assets/css/admin.css','.admin-chat-messages{flex:1;min-height:0;overflow-y:auto'],
 'Admin live chat AJAX'=>['public/assets/js/admin-live-chat.js','setInterval(()=>{if(!document.hidden&&!state.loading)refresh'],
 'Enter to send'=>['public/assets/js/admin-live-chat.js',"event.key==='Enter'&&!event.shiftKey"],
 'Dynamic live chat feed route'=>['index.php','/admin/live-chat/feed'],
 'Support note route'=>['index.php','/admin/live-chat/note'],
 'Support notes database field'=>['database/schema.sql','admin_notes TEXT NULL'],
 'Presence database field'=>['database/schema.sql','visitor_last_seen_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP'],
 'Unread tracking database field'=>['database/schema.sql','admin_last_read_at DATETIME NULL'],
 'Nested Fundraising navigation'=>['app/Views/layouts/admin.php','>Fundraising</span>'],
 'Nested Support navigation'=>['app/Views/layouts/admin.php','>Support & Messages</span>'],
 'Footer developer credit'=>['app/Views/layouts/site.php','Fully Developed by 97 Foundation IT Team.'],
];
$fail=0;
foreach($checks as $name=>[$file,$needle]){
    $s=@file_get_contents($root.'/'.$file);
    $ok=$s!==false&&str_contains($s,$needle);
    echo ($ok?'[PASS] ':'[FAIL] ').$name."\n";
    if(!$ok)$fail++;
}
if($fail)exit(1);
echo "V4.1 static feature checks passed.\n";
