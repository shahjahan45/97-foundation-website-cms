<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use App\Core\Database;

function v4HasColumn(string $table,string $column):bool{$r=Database::one('SELECT COUNT(*) c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?',[$table,$column]);return (int)($r['c']??0)>0;}
function v4AddColumn(string $table,string $column,string $definition):void{if(!v4HasColumn($table,$column))Database::exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");}
function v4HasIndex(string $table,string $index):bool{$r=Database::one('SELECT COUNT(*) c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?',[$table,$index]);return (int)($r['c']??0)>0;}
function v4AddIndex(string $table,string $index,string $columns):void{if(!v4HasIndex($table,$index))Database::exec("ALTER TABLE `$table` ADD INDEX `$index` ($columns)");}

v4AddColumn('campaigns','is_active','TINYINT(1) NOT NULL DEFAULT 1 AFTER status');
v4AddIndex('campaigns','idx_campaign_active_public','is_active,is_visible,status,id');
v4AddIndex('donations','idx_donations_verify_queue','status,id');
v4AddIndex('volunteers','idx_volunteer_application_queue','status,id');
v4AddIndex('posts','idx_posts_admin_list','post_type,status,id');

// Re-create live chat tables when an older V3 installation did not complete the migration.
Database::exec("CREATE TABLE IF NOT EXISTS chat_conversations (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,public_token CHAR(48) NOT NULL UNIQUE,visitor_name VARCHAR(120) NOT NULL,visitor_email VARCHAR(190) NOT NULL,visitor_phone VARCHAR(60) NULL,category VARCHAR(40) NOT NULL DEFAULT 'general',status ENUM('open','waiting','closed') NOT NULL DEFAULT 'open',assigned_admin_id BIGINT UNSIGNED NULL,admin_notes TEXT NULL,admin_last_read_at DATETIME NULL,visitor_last_seen_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX idx_chat_status_date(status,last_message_at),INDEX idx_chat_presence(status,visitor_last_seen_at),CONSTRAINT fk_chat_admin FOREIGN KEY(assigned_admin_id) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB");

// V4.1 live-chat workspace enhancements. Safe to run repeatedly.
v4AddColumn('chat_conversations','admin_notes','TEXT NULL AFTER assigned_admin_id');
v4AddColumn('chat_conversations','admin_last_read_at','DATETIME NULL AFTER admin_notes');
v4AddColumn('chat_conversations','visitor_last_seen_at','DATETIME NULL DEFAULT CURRENT_TIMESTAMP AFTER admin_last_read_at');
v4AddIndex('chat_conversations','idx_chat_presence','status,visitor_last_seen_at');

Database::exec("CREATE TABLE IF NOT EXISTS chat_messages (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,conversation_id BIGINT UNSIGNED NOT NULL,sender_type ENUM('visitor','admin','system') NOT NULL,sender_user_id BIGINT UNSIGNED NULL,sender_name VARCHAR(120) NULL,message TEXT NOT NULL,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,INDEX idx_chatmsg_conversation(conversation_id,id),CONSTRAINT fk_chatmsg_conversation FOREIGN KEY(conversation_id) REFERENCES chat_conversations(id) ON DELETE CASCADE,CONSTRAINT fk_chatmsg_user FOREIGN KEY(sender_user_id) REFERENCES users(id) ON DELETE SET NULL) ENGINE=InnoDB");

echo "97 Foundation V4 database upgrade completed successfully. V4.1 live chat enhancements applied.\n";
