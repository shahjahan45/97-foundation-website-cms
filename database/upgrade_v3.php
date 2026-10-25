<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use App\Core\Database;

function hasColumn(string $table,string $column):bool{ $r=Database::one('SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?',[$table,$column]);return (int)($r['c']??0)>0; }
function addColumn(string $table,string $column,string $definition):void{ if(!hasColumn($table,$column)) Database::exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition"); }

addColumn('member_profiles','role_title','VARCHAR(140) NULL AFTER joined_at');
addColumn('member_profiles','bio','VARCHAR(1000) NULL AFTER role_title');
addColumn('member_profiles','verification_token','CHAR(64) NULL AFTER bio');
$idx=Database::one('SELECT COUNT(*) c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME="member_profiles" AND INDEX_NAME="uq_member_verification_token"');
if((int)($idx['c']??0)===0) Database::exec('ALTER TABLE member_profiles ADD UNIQUE KEY uq_member_verification_token (verification_token)');
addColumn('member_profiles','is_public','TINYINT(1) NOT NULL DEFAULT 1 AFTER verification_token');

Database::exec("CREATE TABLE IF NOT EXISTS social_links (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,platform VARCHAR(80) NOT NULL,label VARCHAR(100) NULL,url VARCHAR(500) NOT NULL,icon VARCHAR(30) NULL,is_active TINYINT(1) NOT NULL DEFAULT 1,sort_order INT NOT NULL DEFAULT 0,created_by BIGINT UNSIGNED NULL,updated_by BIGINT UNSIGNED NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX idx_social_active_sort(is_active,sort_order),CONSTRAINT fk_social_created FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,CONSTRAINT fk_social_updated FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB");
Database::exec("CREATE TABLE IF NOT EXISTS posts (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,post_type ENUM('news','blog') NOT NULL DEFAULT 'news',title VARCHAR(200) NOT NULL,slug VARCHAR(220) NOT NULL UNIQUE,excerpt VARCHAR(800) NULL,content MEDIUMTEXT NULL,featured_image VARCHAR(255) NULL,status ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',is_featured TINYINT(1) NOT NULL DEFAULT 0,published_at DATETIME NULL,meta_title VARCHAR(240) NULL,meta_description VARCHAR(500) NULL,created_by BIGINT UNSIGNED NULL,updated_by BIGINT UNSIGNED NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX idx_posts_public(status,published_at,post_type),CONSTRAINT fk_posts_created FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL,CONSTRAINT fk_posts_updated FOREIGN KEY(updated_by) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB");
Database::exec("CREATE TABLE IF NOT EXISTS chat_conversations (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,public_token CHAR(48) NOT NULL UNIQUE,visitor_name VARCHAR(120) NOT NULL,visitor_email VARCHAR(190) NOT NULL,visitor_phone VARCHAR(60) NULL,category VARCHAR(40) NOT NULL DEFAULT 'general',status ENUM('open','waiting','closed') NOT NULL DEFAULT 'open',assigned_admin_id BIGINT UNSIGNED NULL,last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX idx_chat_status_date(status,last_message_at),CONSTRAINT fk_chat_admin FOREIGN KEY(assigned_admin_id) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB");
Database::exec("CREATE TABLE IF NOT EXISTS chat_messages (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,conversation_id BIGINT UNSIGNED NOT NULL,sender_type ENUM('visitor','admin','system') NOT NULL,sender_user_id BIGINT UNSIGNED NULL,sender_name VARCHAR(120) NULL,message TEXT NOT NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,INDEX idx_chatmsg_conversation(conversation_id,id),CONSTRAINT fk_chatmsg_conversation FOREIGN KEY(conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,CONSTRAINT fk_chatmsg_user FOREIGN KEY(sender_user_id) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB");

// Give all existing member records a permanent verification token if missing.
$members=Database::all('SELECT user_id FROM member_profiles WHERE verification_token IS NULL OR verification_token=""');
foreach($members as $m) Database::exec('UPDATE member_profiles SET verification_token=? WHERE user_id=?',[bin2hex(random_bytes(32)),(int)$m['user_id']]);

echo "97 Foundation V3 database upgrade completed successfully.\n";
