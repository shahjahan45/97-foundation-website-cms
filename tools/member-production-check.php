<?php
require dirname(__DIR__).'/bootstrap.php';
use App\Core\Database;

$ok=true;
function report67(bool $pass,string $label,string $detail=''):void{
    global $ok;$ok=$ok&&$pass;
    echo ($pass?'[OK] ':'[FAIL] ').$label.($detail!==''?' — '.$detail:'').PHP_EOL;
}
report67(PHP_VERSION_ID>=80200,'PHP version',PHP_VERSION);
foreach(['pdo','pdo_mysql','mbstring','fileinfo','openssl'] as $ext)report67(extension_loaded($ext),'Extension '.$ext);
report67(defined('PASSWORD_ARGON2ID'),'Argon2id support');
report67((int)config('security.member_password_min',0)===8,'Member password minimum','8 characters');
report67((int)config('security.member_password_max',0)===12,'Member password maximum','12 characters');
$sessionDir=dirname(__DIR__).'/storage/private/sessions';
report67(is_dir($sessionDir)&&is_writable($sessionDir),'Session storage writable',$sessionDir);
$logDir=dirname(__DIR__).'/storage/logs';
report67(is_dir($logDir)&&is_writable($logDir),'Log storage writable',$logDir);
$uploadDir=dirname(__DIR__).'/public/uploads';
report67(is_dir($uploadDir)&&is_writable($uploadDir),'Public upload storage writable',$uploadDir);
try{
    Database::one('SELECT 1');
    report67(true,'Database connection');
    $required=[
        'users'=>['id','name','email','username','password_hash','role','status'],
        'member_profiles'=>['user_id','mobile','date_of_birth','nic_number','gender','member_number','joined_at','verification_token','is_public','approved_at','rejected_at','rejection_reason'],
        'login_attempts'=>['attempt_key','attempts','last_attempt_at'],
        'notifications'=>['user_id','title','message','read_at'],
    ];
    foreach($required as $table=>$columns){
        $exists=(bool)Database::one('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1',[$table]);
        report67($exists,'Table '.$table);
        if(!$exists)continue;
        foreach($columns as $column){
            $has=(bool)Database::one('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1',[$table,$column]);
            report67($has,$table.'.'.$column);
        }
    }
    $emailUnique=(bool)Database::one('SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME="users" AND COLUMN_NAME="email" AND NON_UNIQUE=0 LIMIT 1');
    report67($emailUnique,'Unique member email protection');
    $usernameUnique=(bool)Database::one('SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME="users" AND COLUMN_NAME="username" AND NON_UNIQUE=0 LIMIT 1');
    report67($usernameUnique,'Unique username protection');
    $nicUnique=(bool)Database::one('SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME="member_profiles" AND COLUMN_NAME="nic_number" AND NON_UNIQUE=0 LIMIT 1');
    report67($nicUnique,'Unique NIC / National ID protection');
    $publicDefault=Database::one('SELECT COLUMN_DEFAULT d FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME="member_profiles" AND COLUMN_NAME="is_public" LIMIT 1');
    report67((string)($publicDefault['d']??'')==='0','Pending/private publication default');
}catch(Throwable $e){report67(false,'Database/schema check','See storage/logs/php-error.log');}

echo PHP_EOL.($ok?'Member production readiness check passed.':'Member production readiness check found items that must be fixed before launch.').PHP_EOL;
exit($ok?0:1);
