<?php
require dirname(__DIR__).'/bootstrap.php';
use App\Core\Database;

$ok=true;
function check68(bool $pass,string $label,string $detail=''):void{
    global $ok;$ok=$ok&&$pass;
    echo ($pass?'[OK] ':'[FAIL] ').$label.($detail!==''?' — '.$detail:'').PHP_EOL;
}

check68(PHP_VERSION_ID>=80200,'PHP version',PHP_VERSION);
foreach(['pdo','pdo_mysql','mbstring','fileinfo','openssl'] as $ext) check68(extension_loaded($ext),'Extension '.$ext);

$root=dirname(__DIR__);
$localRoot=$root.'/config.local.php';
$localPrivate=$root.'/storage/private/config.local.php';
$hasEnv=getenv('DB_DATABASE')!==false && trim((string)getenv('DB_DATABASE'))!=='';
$hasPersistent=is_file($localRoot)||is_file($localPrivate)||$hasEnv;
check68($hasPersistent,'Persistent production database configuration',$hasEnv?'server environment variables':(is_file($localRoot)?'config.local.php':(is_file($localPrivate)?'storage/private/config.local.php':'not configured')));

foreach([
    $root.'/storage/private/sessions'=>'Session storage',
    $root.'/storage/logs'=>'Log storage',
    $root.'/public/uploads'=>'Upload storage',
] as $dir=>$label) check68(is_dir($dir)&&is_writable($dir),$label.' writable',$dir);

try{
    $pdo=Database::pdo();
    check68(true,'Database connection',(string)config('db.host').' / '.(string)config('db.name'));
    foreach(['settings','pages','content_sections','hero_slides','campaigns','donations','users','member_profiles'] as $table){
        $exists=(bool)Database::one('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1',[$table]);
        check68($exists,'Core table '.$table);
    }
    $heroStart=(bool)Database::one('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME="hero_slides" AND COLUMN_NAME="start_at" LIMIT 1');
    $heroEnd=(bool)Database::one('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME="hero_slides" AND COLUMN_NAME="end_at" LIMIT 1');
    check68($heroStart&&$heroEnd,'Responsive/scheduled hero schema');
    $memberPublic=(bool)Database::one('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME="member_profiles" AND COLUMN_NAME="is_public" LIMIT 1');
    check68($memberPublic,'Member publication schema');
}catch(Throwable $e){
    check68(false,'Database connection/schema','See storage/logs/php-error.log for the exact server-side error.');
}

echo PHP_EOL.($ok?'Production check passed.':'Production check found items that must be corrected.').PHP_EOL;
exit($ok?0:1);
