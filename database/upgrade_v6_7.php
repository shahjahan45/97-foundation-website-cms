<?php
require dirname(__DIR__).'/bootstrap.php';
use App\Core\Database;

function col67(string $table,string $column):bool{
    try{return (bool)Database::one('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1',[$table,$column]);}
    catch(Throwable){return false;}
}
function idx67(string $table,string $index):bool{
    try{return (bool)Database::one('SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=? LIMIT 1',[$table,$index]);}
    catch(Throwable){return false;}
}

// Idempotently guarantee the member schema required by the production workflow.
if(!col67('users','username')) Database::exec('ALTER TABLE users ADD COLUMN username VARCHAR(80) NULL AFTER email');
try{Database::exec("ALTER TABLE users MODIFY status ENUM('active','inactive','suspended','pending','rejected') NOT NULL DEFAULT 'active'");}catch(Throwable $e){error_log('V6.7 users status migration: '.$e->getMessage());}
if(!idx67('users','uq_users_username')){
    try{Database::exec('ALTER TABLE users ADD UNIQUE KEY uq_users_username (username)');}catch(Throwable $e){error_log('V6.7 username index migration: '.$e->getMessage());}
}

$columns=[
    'date_of_birth'=>'DATE NULL AFTER mobile',
    'nic_number'=>'VARCHAR(80) NULL AFTER date_of_birth',
    'gender'=>'VARCHAR(30) NULL AFTER nic_number',
    'approved_by'=>'BIGINT UNSIGNED NULL AFTER is_public',
    'approved_at'=>'DATETIME NULL AFTER approved_by',
    'rejected_at'=>'DATETIME NULL AFTER approved_at',
    'rejection_reason'=>'VARCHAR(500) NULL AFTER rejected_at',
];
foreach($columns as $column=>$definition){
    if(!col67('member_profiles',$column)) Database::exec('ALTER TABLE member_profiles ADD COLUMN '.$column.' '.$definition);
}
if(!idx67('member_profiles','uq_member_nic')){
    try{Database::exec('ALTER TABLE member_profiles ADD UNIQUE KEY uq_member_nic (nic_number)');}catch(Throwable $e){error_log('V6.7 NIC index migration: '.$e->getMessage());}
}
if(!idx67('member_profiles','idx_member_public')){
    try{Database::exec('ALTER TABLE member_profiles ADD INDEX idx_member_public (is_public,user_id)');}catch(Throwable $e){error_log('V6.7 public member index migration: '.$e->getMessage());}
}
try{Database::exec('ALTER TABLE member_profiles MODIFY is_public TINYINT(1) NOT NULL DEFAULT 0');}catch(Throwable $e){error_log('V6.7 public default migration: '.$e->getMessage());}

// A non-active account can never remain accidentally published.
Database::exec('UPDATE member_profiles m JOIN users u ON u.id=m.user_id SET m.is_public=0 WHERE u.role="member" AND u.status<>"active" AND m.is_public<>0');
// Preserve existing approved/public members while ensuring their approval timestamp exists.
Database::exec('UPDATE member_profiles m JOIN users u ON u.id=m.user_id SET m.approved_at=COALESCE(m.approved_at,CONCAT(COALESCE(m.joined_at,CURDATE())," 00:00:00")) WHERE u.role="member" AND u.status="active" AND m.is_public=1 AND m.approved_at IS NULL');

echo "V6.7 member production-readiness upgrade completed.\n";
