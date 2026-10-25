<?php
$home=file_get_contents(__DIR__.'/../app/Views/home.php');
$js=file_get_contents(__DIR__.'/../public/assets/js/app.js');
$css=file_get_contents(__DIR__.'/../public/assets/css/app.css');
$admin=file_get_contents(__DIR__.'/../app/Views/admin/content.php');
// V7.1: hero content is a single admin-managed overlay above the five-image background loop.
$checks=[
 'hero overlay title'=>str_contains($home,'hero7-title')&&str_contains($home,"\$hero['title_line_1']"),
 'hero buttons restored'=>str_contains($home,"\$hero['primary_text']")&&str_contains($home,"\$hero['secondary_text']"),
 'no arrow controls in home'=>!str_contains($home,'data-hero-prev')&&!str_contains($home,'data-hero-next'),
 'timeout autoplay'=>str_contains($js,'timer=setTimeout(()=>{step(1);schedule();},delay)'),
 'admin-configurable interval'=>str_contains($js,"root.dataset.interval")&&str_contains($home,'data-interval='),
 'admin overlay fields'=>str_contains($admin,'Primary button text')&&str_contains($admin,'Secondary button text')&&str_contains($admin,'overlay_opacity'),
 'full image cover'=>str_contains((string)file_get_contents(__DIR__.'/../public/assets/css/site-v7.css'),'.hero7-slide img{object-fit:cover'),
];
$ok=true;foreach($checks as $name=>$pass){echo ($pass?'PASS ':'FAIL ').$name.PHP_EOL;$ok=$ok&&$pass;}exit($ok?0:1);
