<?php
declare(strict_types=1);
require dirname(__DIR__).'/bootstrap.php';
use App\Core\Database;

Database::exec("ALTER TABLE campaigns MODIFY currency VARCHAR(10) NOT NULL DEFAULT 'LKR'");
Database::exec("ALTER TABLE donation_accounts MODIFY currency VARCHAR(10) NOT NULL DEFAULT 'LKR'");
Database::exec("ALTER TABLE donations MODIFY currency VARCHAR(10) NOT NULL DEFAULT 'LKR'");
Database::exec("UPDATE campaigns SET currency='LKR' WHERE currency<>'LKR'");
Database::exec("UPDATE donation_accounts SET currency='LKR' WHERE currency<>'LKR'");
Database::exec("UPDATE donations SET currency='LKR' WHERE currency<>'LKR'");
Database::exec("CREATE TABLE IF NOT EXISTS message_broadcasts (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,sender_id BIGINT UNSIGNED NOT NULL,audience VARCHAR(40) NOT NULL,subject VARCHAR(180) NOT NULL,body TEXT NOT NULL,recipient_count INT UNSIGNED NOT NULL DEFAULT 0,created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,CONSTRAINT fk_broadcast_sender FOREIGN KEY(sender_id) REFERENCES users(id) ON DELETE CASCADE,INDEX idx_broadcast_created(created_at,id)) ENGINE=InnoDB");
Database::exec("INSERT INTO settings(setting_group,setting_key,setting_value,is_public) VALUES('foundation','favicon_path','',1) ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key)");
echo "97 Foundation V5.0 database upgrade completed: LKR currency normalization and broadcast messaging support applied.\n";
