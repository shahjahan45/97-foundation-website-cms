<?php
$config=require dirname(__DIR__).'/config.php';
$min=(int)$config['security']['member_password_min'];$max=(int)$config['security']['member_password_max'];
$cases=['1234567'=>false,'12345678'=>true,'123456789012'=>true,'1234567890123'=>false];
foreach($cases as $password=>$expected){$len=(function_exists('mb_strlen') ? mb_strlen($password,'UTF-8') : strlen($password));$actual=$len>=$min&&$len<=$max;if($actual!==$expected){fwrite(STDERR,"Password boundary failed for length $len\n");exit(1);}}
$hash=password_hash('12345678',PASSWORD_ARGON2ID);if(!$hash||!password_verify('12345678',$hash)){fwrite(STDERR,"Argon2id verification failed\n");exit(1);}echo "V6.7 password boundary and Argon2id checks passed.\n";
