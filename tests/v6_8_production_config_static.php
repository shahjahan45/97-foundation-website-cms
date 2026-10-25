<?php
$root=dirname(__DIR__);
$config=file_get_contents($root.'/config.php');
$ht=file_get_contents($root.'/.htaccess');
$checks=[
 'local configuration loader'=>str_contains($config,'config.local.php'),
 'private local configuration loader'=>str_contains($config,'storage/private/config.local.php'),
 'environment precedence'=>str_contains($config,"DB_DATABASE"),
 'config local web protection'=>str_contains($ht,'example|local'),
 'production checker'=>is_file($root.'/tools/production-check.php'),
 'error reference finder'=>is_file($root.'/tools/find-error-reference.php'),
];
$ok=true;foreach($checks as $label=>$pass){echo ($pass?'[OK] ':'[FAIL] ').$label.PHP_EOL;$ok=$ok&&$pass;}exit($ok?0:1);
