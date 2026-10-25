<?php
$checks=[];
$home=file_get_contents(__DIR__.'/../app/Views/home.php');
$css=file_get_contents(__DIR__.'/../public/assets/css/app.css');
$js=file_get_contents(__DIR__.'/../public/assets/js/app.js');
$admin=file_get_contents(__DIR__.'/../app/Views/admin/content.php');
// V7.1: full-width background images now live in the hero7 track.
$checks['full-width hero image']=str_contains($home,'data-hero7-slide')&&!str_contains($home,'data-hero-prev')&&!str_contains($home,'data-hero-next');
$checks['cover image rendering']=str_contains($css,'object-fit:cover')&&str_contains($css,'object-position:center center');
$checks['auto slider scheduler']=str_contains($js,'timer=setTimeout(()=>{step(1);schedule();},delay)');
$checks['swipe support']=str_contains($js,'pointerdown')&&str_contains($js,'pointerup');
$checks['keyboard support']=str_contains($js,'ArrowRight')&&str_contains($js,'ArrowLeft');
$checks['lazy later slides']=str_contains($home,'loading="lazy" fetchpriority="low"');
$checks['admin hero management']=str_contains($admin,'Homepage Hero Banner')&&str_contains($admin,'bg_image_');
foreach($checks as $name=>$ok){echo ($ok?'PASS':'FAIL')." - $name\n";if(!$ok)exit(1);}echo "V5.2 full-width hero foundation checks passed.\n";
