<?php
require dirname(__DIR__).'/bootstrap.php';
use App\Core\Csrf;
$token=Csrf::token();
if(!Csrf::validate($token)){fwrite(STDERR,"Initial CSRF validation failed\n");exit(1);}
// Simulate a shared-host PHP session rotation while the browser cookie survives.
$_SESSION=[];
if(!Csrf::validate($token)){fwrite(STDERR,"CSRF cookie fallback failed after session rotation\n");exit(1);}
$parts=explode('.',$token);$parts[0]=str_repeat('a',48);$bad=implode('.',$parts);
if(Csrf::validate($bad)){fwrite(STDERR,"Tampered CSRF token was accepted\n");exit(1);}
echo "V6.7 CSRF runtime checks passed.\n";
