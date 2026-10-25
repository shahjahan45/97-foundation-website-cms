<?php
$root=dirname(__DIR__);
$security=file_get_contents($root.'/app/Core/Security.php');
$csrf=file_get_contents($root.'/app/Core/Csrf.php');
$home=file_get_contents($root.'/app/Controllers/HomeController.php');
$index=file_get_contents($root.'/index.php');
$checks=[
 'protected session storage'=>str_contains($security,'storage/private/sessions'),
 'session gc lifetime aligned'=>str_contains($security,"session.gc_maxlifetime"),
 'reverse proxy HTTPS support'=>str_contains($security,'HTTP_X_FORWARDED_PROTO')&&str_contains($security,'HTTP_CF_VISITOR'),
 'dynamic responses no-store'=>str_contains($security,'no-store, no-cache'),
 'signed CSRF token'=>str_contains($csrf,"hash_hmac('sha256'")&&str_contains($csrf,'foundation97_csrf'),
 'signed double-submit fallback'=>str_contains($csrf,'browserSecret')&&str_contains($csrf,'hash_hmac')&&str_contains($csrf,'foundation97_csrf_key'),
 'professional 419 response'=>str_contains($csrf,'Your secure form needs a refresh'),
 'favicon endpoint'=>str_contains($home,'function favicon')&&str_contains($index,"/favicon.ico"),
];
$failed=[];foreach($checks as $name=>$ok){echo ($ok?'[OK] ':'[FAIL] ').$name.PHP_EOL;if(!$ok)$failed[]=$name;}
exit($failed?1:0);
