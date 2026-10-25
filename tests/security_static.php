<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$checks=[
 'PDO prepared statements'=>'PDO::ATTR_EMULATE_PREPARES => false',
 'CSRF enforcement'=>'Csrf::enforce()',
 'Argon2id admin password'=>'PASSWORD_ARGON2ID',
 'MIME upload validation'=>'FILEINFO_MIME_TYPE',
 'CSP header'=>'Content-Security-Policy',
 'Private proof directory'=>'storage/private/donation-proofs',
 'Approved-only campaign sum'=>'d.status="approved"'
];
$all='';foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/app')) as $f){if($f->isFile()&&$f->getExtension()==='php')$all.=file_get_contents($f->getPathname())."\n";}$all.=file_get_contents($root.'/database/create_admin.php').file_get_contents($root.'/database/schema.sql');
$fail=0;foreach($checks as $name=>$needle){if(strpos($all,$needle)===false){echo "FAIL: $name\n";$fail++;}else echo "PASS: $name\n";}exit($fail?1:0);
