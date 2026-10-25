<?php
namespace App\Core;

/**
 * Foundation office bearers (current and past officials).
 *
 * The table is created by database/schema.sql (new installs) and
 * database/upgrade_v7_1.php (existing installs). As a safety net the table is
 * also created on first use, so the Members menu never breaks on a site that
 * has not run the upgrade yet.
 */
final class Officials
{
    public const STATUSES = ['current', 'past'];

    public const CREATE_SQL = 'CREATE TABLE IF NOT EXISTS foundation_officials (
      id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      full_name VARCHAR(160) NOT NULL,
      position VARCHAR(140) NOT NULL,
      photo_path VARCHAR(255) NULL,
      term_start SMALLINT UNSIGNED NULL,
      term_end SMALLINT UNSIGNED NULL,
      status ENUM(\'current\',\'past\') NOT NULL DEFAULT \'current\',
      bio VARCHAR(1200) NULL,
      email VARCHAR(190) NULL,
      phone VARCHAR(60) NULL,
      show_contact TINYINT(1) NOT NULL DEFAULT 0,
      sort_order INT NOT NULL DEFAULT 0,
      is_active TINYINT(1) NOT NULL DEFAULT 1,
      created_by BIGINT UNSIGNED NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_officials_public (status,is_active,sort_order),
      CONSTRAINT fk_officials_creator FOREIGN KEY(created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB';

    private static bool $checked = false;

    public static function ensureTable(): void
    {
        if (self::$checked) return;
        self::$checked = true;
        try {
            Database::one('SELECT id FROM foundation_officials LIMIT 1');
        } catch (\Throwable) {
            try { Database::pdo()->exec(self::CREATE_SQL); }
            catch (\Throwable $e) { error_log('Officials table could not be created automatically: ' . $e->getMessage()); }
        }
    }

    /** Public list for a status, ordered for display. */
    public static function published(string $status): array
    {
        if (!in_array($status, self::STATUSES, true)) return [];
        self::ensureTable();
        try {
            $order = $status === 'past'
                ? 'COALESCE(term_end,term_start,0) DESC, sort_order, full_name'
                : 'sort_order, full_name';
            return Database::all('SELECT * FROM foundation_officials WHERE status=? AND is_active=1 ORDER BY ' . $order, [$status]);
        } catch (\Throwable $e) {
            error_log('Officials query failed: ' . $e->getMessage());
            return [];
        }
    }

    /** Past officials grouped by their service term label, newest first. */
    public static function groupedByTerm(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $label = self::termLabel($row) ?: 'Earlier Terms';
            $groups[$label][] = $row;
        }
        return $groups;
    }

    public static function termLabel(array $row): string
    {
        $start = (int)($row['term_start'] ?? 0);
        $end = (int)($row['term_end'] ?? 0);
        if ($start && $end) return $start === $end ? (string)$start : $start . ' – ' . $end;
        if ($start) return ($row['status'] ?? '') === 'current' ? 'Since ' . $start : (string)$start;
        if ($end) return 'Until ' . $end;
        return '';
    }

    public static function counts(): array
    {
        self::ensureTable();
        $counts = ['current' => 0, 'past' => 0, 'hidden' => 0];
        try {
            foreach (Database::all('SELECT status, is_active, COUNT(*) c FROM foundation_officials GROUP BY status, is_active') as $r) {
                if ((int)$r['is_active'] !== 1) $counts['hidden'] += (int)$r['c'];
                if (isset($counts[$r['status']])) $counts[$r['status']] += (int)$r['c'];
            }
        } catch (\Throwable) {}
        return $counts;
    }
}
