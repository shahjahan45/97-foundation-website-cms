<?php
require dirname(__DIR__).'/bootstrap.php';
use App\Core\Database;
function col57(string $t,string $c):bool{try{return (bool)Database::one('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1',[$t,$c]);}catch(Throwable){return false;}}
function idx57(string $t,string $i):bool{try{return (bool)Database::one('SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=? LIMIT 1',[$t,$i]);}catch(Throwable){return false;}}
if(!col57('users','username')) Database::exec('ALTER TABLE users ADD COLUMN username VARCHAR(80) NULL AFTER email');
try{Database::exec("ALTER TABLE users MODIFY status ENUM('active','inactive','suspended','pending','rejected') NOT NULL DEFAULT 'active'");}catch(Throwable $e){}
if(!idx57('users','uq_users_username')) Database::exec('ALTER TABLE users ADD UNIQUE KEY uq_users_username (username)');
if(!col57('member_profiles','date_of_birth')) Database::exec('ALTER TABLE member_profiles ADD COLUMN date_of_birth DATE NULL AFTER mobile');
if(!col57('member_profiles','nic_number')) Database::exec('ALTER TABLE member_profiles ADD COLUMN nic_number VARCHAR(80) NULL AFTER date_of_birth');
if(!idx57('member_profiles','uq_member_nic')) Database::exec('ALTER TABLE member_profiles ADD UNIQUE KEY uq_member_nic (nic_number)');
if(!col57('member_profiles','gender')) Database::exec('ALTER TABLE member_profiles ADD COLUMN gender VARCHAR(30) NULL AFTER nic_number');
if(!col57('member_profiles','approved_by')) Database::exec('ALTER TABLE member_profiles ADD COLUMN approved_by BIGINT UNSIGNED NULL AFTER is_public');
if(!col57('member_profiles','approved_at')) Database::exec('ALTER TABLE member_profiles ADD COLUMN approved_at DATETIME NULL AFTER approved_by');
if(!col57('member_profiles','rejected_at')) Database::exec('ALTER TABLE member_profiles ADD COLUMN rejected_at DATETIME NULL AFTER approved_at');
if(!col57('member_profiles','rejection_reason')) Database::exec('ALTER TABLE member_profiles ADD COLUMN rejection_reason VARCHAR(500) NULL AFTER rejected_at');
if(!col57('hero_slides','mobile_image_path')) Database::exec('ALTER TABLE hero_slides ADD COLUMN mobile_image_path VARCHAR(255) NULL AFTER image_path');
if(!col57('hero_slides','start_at')) Database::exec('ALTER TABLE hero_slides ADD COLUMN start_at DATETIME NULL AFTER mobile_image_path');
if(!col57('hero_slides','end_at')) Database::exec('ALTER TABLE hero_slides ADD COLUMN end_at DATETIME NULL AFTER start_at');
echo "V5.7 member portal and responsive hero upgrade completed.\n";
