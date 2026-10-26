<?php
namespace App\Core;

/**
 * 97 Foundation V7.2 database update (idempotent, never deletes records).
 *
 * Runs automatically on the first web request after the files are installed,
 * or manually with `php database/upgrade_v7_2.php`.
 *  - News & Blog categories (post_categories table + posts.category_id)
 *  - Fundraising switch defaults (settings group "fundraising")
 */
final class Upgrade72
{
    private const FLAG = '/storage/logs/.v72-schema';

    public const DEFAULT_CATEGORIES = [
        ['Announcements', 'announcements', 'Official notices and Foundation updates.'],
        ['Community Stories', 'community-stories', 'Stories from the people and families we support.'],
        ['Programmes & Activities', 'programmes-activities', 'Education, family support and volunteer activities.'],
    ];

    /** Cheap check on every request: a flag file avoids touching the database once the update is in place. */
    public static function autoApply(): void
    {
        $flag = dirname(__DIR__, 2) . self::FLAG;
        if (is_file($flag)) return;
        if (self::hasColumn('posts', 'category_id') && self::hasTable('post_categories') && setting('system', 'v72_applied', '0') === '1') {
            @file_put_contents($flag, date('c'));
            return;
        }
        self::run(null);
        @file_put_contents($flag, date('c'));
    }

    /** @return string[] human-readable log */
    public static function run(?int $userId): array
    {
        $log = [];
        if (!self::hasTable('post_categories')) {
            Database::exec('CREATE TABLE IF NOT EXISTS post_categories (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(120) NOT NULL,
                slug VARCHAR(140) NOT NULL UNIQUE,
                description VARCHAR(400) NULL,
                sort_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_post_categories_active (is_active, sort_order)
            ) ENGINE=InnoDB');
            $log[] = 'News & Blog categories table created';
        }
        if (!self::hasColumn('posts', 'category_id')) {
            Database::exec('ALTER TABLE posts ADD COLUMN category_id BIGINT UNSIGNED NULL AFTER post_type');
            try { Database::exec('ALTER TABLE posts ADD INDEX idx_posts_category (category_id, status, published_at)'); } catch (\Throwable) {}
            try { Database::exec('ALTER TABLE posts ADD CONSTRAINT fk_posts_category FOREIGN KEY (category_id) REFERENCES post_categories(id) ON DELETE SET NULL'); } catch (\Throwable) {}
            $log[] = 'Posts can now be assigned to a category';
        }
        try {
            if ((int)(Database::one('SELECT COUNT(*) c FROM post_categories')['c'] ?? 0) === 0) {
                foreach (self::DEFAULT_CATEGORIES as $i => [$name, $slug, $desc]) {
                    Database::exec('INSERT INTO post_categories(name,slug,description,sort_order,is_active) VALUES(?,?,?,?,1)', [$name, $slug, $desc, ($i + 1) * 10]);
                }
                $log[] = 'Starter categories added (rename or remove them in Admin → News & Blog)';
            }
        } catch (\Throwable $e) {
            $log[] = 'Starter categories skipped: ' . $e->getMessage();
        }
        foreach (['enabled' => '1', 'general_when_paused' => '1'] as $key => $value) {
            if (!Database::one('SELECT id FROM settings WHERE setting_group="fundraising" AND setting_key=? LIMIT 1', [$key])) {
                Database::exec('INSERT INTO settings(setting_group,setting_key,setting_value,is_public,updated_by) VALUES("fundraising",?,?,1,?)', [$key, $value, $userId]);
            }
        }
        $log[] = 'Fundraising switch ready (currently ' . (setting('fundraising', 'enabled', '1') === '0' ? 'paused' : 'on') . ')';
        if (Database::one('SELECT id FROM settings WHERE setting_group="system" AND setting_key="v72_applied" LIMIT 1')) {
            Database::exec('UPDATE settings SET setting_value="1", updated_by=? WHERE setting_group="system" AND setting_key="v72_applied"', [$userId]);
        } else {
            Database::exec('INSERT INTO settings(setting_group,setting_key,setting_value,is_public,updated_by) VALUES("system","v72_applied","1",0,?)', [$userId]);
        }
        if ($userId) Audit::write($userId, 'WEBSITE_UPDATE', 'system', null, 'V7.2 database update applied');
        return $log;
    }

    public static function hasTable(string $table): bool
    {
        if (!preg_match('/^[a-z0-9_]+$/', $table)) return false;
        try { Database::one('SELECT 1 FROM ' . $table . ' LIMIT 1'); return true; } catch (\Throwable) { return false; }
    }

    public static function hasColumn(string $table, string $column): bool
    {
        if (!preg_match('/^[a-z0-9_]+$/', $table . $column)) return false;
        try { Database::one('SELECT ' . $column . ' FROM ' . $table . ' LIMIT 1'); return true; } catch (\Throwable) { return false; }
    }
}
