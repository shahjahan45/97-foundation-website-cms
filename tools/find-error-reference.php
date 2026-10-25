<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$ref=trim((string)($argv[1]??''));
if($ref===''||!preg_match('/^(?:ERR|FATAL)-[A-F0-9]{6,20}$/i',$ref)){fwrite(STDERR,"Usage: php tools/find-error-reference.php ERR-XXXXXXXXXXXX\n");exit(2);}
$log=dirname(__DIR__).'/storage/logs/php-error.log';
if(!is_file($log)){fwrite(STDERR,"Log file not found: $log\n");exit(1);}
$lines=file($log,FILE_IGNORE_NEW_LINES)?:[];$found=[];
foreach($lines as $line)if(stripos($line,$ref)!==false)$found[]=$line;
if(!$found){echo "Reference not found in current log.\n";exit(1);}foreach($found as $line)echo $line.PHP_EOL;
