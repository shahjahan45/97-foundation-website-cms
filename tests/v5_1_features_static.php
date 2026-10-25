<?php
$home=file_get_contents(__DIR__.'/../app/Views/home.php');
$css=file_get_contents(__DIR__.'/../public/assets/css/app.css');
$js=file_get_contents(__DIR__.'/../public/assets/js/app.js');
$admin=file_get_contents(__DIR__.'/../app/Views/admin/content.php');
// V7.1 replaced the legacy artwork slider with the five-image background hero; checks below target the V7.1 markup.
$checks=[
 'Removed previous/next hero arrows'=>!str_contains($home,'data-hero-prev')&&!str_contains($home,'data-hero-next'),
 'Removed duplicate assistance pill'=>!str_contains($home,'Need assistance?')&&!str_contains($home,'live-pill'),
 'Full responsive hero image element'=>str_contains($home,'<picture>')&&str_contains($home,'data-hero7-slide'),
 'Autoplay configured'=>str_contains($home,'data-interval=')&&str_contains($js,'const schedule=()=>'),
 'Slider dot navigation'=>str_contains($home,'data-hero7-dot'),
 'Touch swipe supported'=>str_contains($js,"addEventListener('pointerdown',onDown")&&str_contains($js,"addEventListener('pointerup',onUp"),
 'Admin supports multiple hero slides'=>str_contains($admin,'Background Slider — 5 Images')&&str_contains($admin,'admin/hero/save'),
];
$ok=true;foreach($checks as $name=>$pass){echo ($pass?'PASS':'FAIL').": $name\n";$ok=$ok&&$pass;}exit($ok?0:1);
