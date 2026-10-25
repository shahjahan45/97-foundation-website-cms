<?php
namespace App\Core;

/**
 * 97 Foundation V7.1 update routine (idempotent).
 *
 * Shared by `php database/upgrade_v7_1.php` and the one-click
 * "Apply V7.1 website update" action in Admin → Branding & SEO.
 * Safe to run more than once; it never deletes records.
 */
final class Upgrade71
{
    public const EMAIL = '97found@gmail.com';
    public const PHONE = '+94 71 258 3411';
    public const FACEBOOK = 'https://www.facebook.com/share/19hyNTG2oZ/';

    /** setting key => [bundled source in public/assets/images/brand, upload target] */
    public const BRAND_ASSETS = [
        'logo_path' => ['97-foundation-logo.png', 'branding/97-foundation-logo.png'],
        'wordmark_path' => ['97-foundation-wordmark.png', 'branding/97-foundation-wordmark.png'],
        'favicon_path' => ['97-foundation-icon-192.png', 'branding/97-foundation-favicon.png'],
    ];

    /** Copy an official bundled brand asset into public uploads; returns the stored relative path. */
    public static function installBrandAsset(string $key): ?string
    {
        if (!isset(self::BRAND_ASSETS[$key])) return null;
        [$source, $target] = self::BRAND_ASSETS[$key];
        $root = dirname(__DIR__, 2);
        $from = $root . '/public/assets/images/brand/' . $source;
        $to = $root . '/public/uploads/' . $target;
        if (!is_dir(dirname($to))) @mkdir(dirname($to), 0755, true);
        if (!is_file($from) || !(@copy($from, $to) || is_file($to))) return null;
        @chmod($to, 0644);
        return $target;
    }

    public static function applied(): bool
    {
        return setting('system', 'v71_applied', '0') === '1';
    }

    /** @return string[] human readable log lines */
    public static function run(?int $userId = null): array
    {
        $log = [];

        // 1. Officials table (Members → Officials / Past Officials).
        Officials::ensureTable();
        $log[] = 'Officials table ready';

        // 2. Official brand assets → uploads, so every logo surface (portal, ID cards, receipts) uses them.
        foreach (array_keys(self::BRAND_ASSETS) as $key) {
            $target = self::installBrandAsset($key);
            if ($target !== null) { self::set('foundation', $key, $target, 1, $userId); $log[] = 'Brand asset set: ' . $key; }
            else $log[] = 'Skipped ' . $key . ' (copy failed — upload it from Admin → Branding & SEO)';
        }
        self::set('foundation', 'logo_enabled', '1', 1, $userId);

        // 3. Header contact details requested in the V7.1 change tracker.
        self::set('foundation', 'email', self::EMAIL, 1, $userId);
        self::set('foundation', 'phone', self::PHONE, 1, $userId);
        self::set('foundation', 'facebook', self::FACEBOOK, 1, $userId);
        $log[] = 'Header contact details updated (email, phone, Facebook page)';

        // 4. Keep footer social icons in sync with the Facebook page.
        try {
            $fb = Database::one('SELECT id FROM social_links WHERE LOWER(platform) LIKE ? OR url LIKE ? ORDER BY id LIMIT 1', ['%facebook%', '%facebook.com%']);
            if ($fb) {
                Database::exec('UPDATE social_links SET url=?, is_active=1 WHERE id=?', [self::FACEBOOK, (int)$fb['id']]);
            } else {
                Database::insert('INSERT INTO social_links(platform,label,url,is_active,sort_order,created_by) VALUES(?,?,?,?,?,?)', ['Facebook', '97 Foundation on Facebook', self::FACEBOOK, 1, 1, $userId]);
            }
            $log[] = 'Facebook social link synced';
        } catch (\Throwable $e) {
            $log[] = 'Social link sync skipped: ' . $e->getMessage();
        }

        // 5. Hero banner defaults (only fills keys that do not exist yet).
        foreach (hero_defaults() as $key => $value) {
            if (!self::exists('hero', $key)) self::set('hero', $key, (string)$value, 1, $userId);
        }
        $log[] = 'Hero banner settings ready';

        // 6. "What We Do" CMS page used by the About Us menu.
        try {
            if (!Database::one('SELECT id FROM pages WHERE slug=? LIMIT 1', ['what-we-do'])) {
                Database::insert('INSERT INTO pages(slug,title,eyebrow,hero_title,hero_subtitle,body,image_path,meta_title,meta_description,answer_summary,is_active,sort_order) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)', [
                    'what-we-do', 'What We Do', 'WHAT WE DO', 'What We Do',
                    'Education, family support, student empowerment and social work for communities across Polonnaruwa and Thambala.',
                    '97 Foundation delivers practical, accountable support through four connected programme areas: education support, family support, student empowerment and social work. Every programme is planned with local volunteers, reviewed by Foundation officials and reported transparently.',
                    'demo/cause-education.jpg', 'What We Do | 97 Foundation',
                    'Discover the programmes and community support delivered by 97 Foundation in Sri Lanka.',
                    '97 Foundation supports communities through education support, family support, student empowerment and social work.',
                    1, 2,
                ]);
                $log[] = '"What We Do" page created';
            } else {
                $log[] = '"What We Do" page already exists';
            }
        } catch (\Throwable $e) {
            $log[] = '"What We Do" page skipped: ' . $e->getMessage();
        }

        self::set('system', 'v71_applied', '1', 0, $userId);
        if ($userId) Audit::write($userId, 'WEBSITE_UPDATE', 'system', null, 'V7.1 header, brand and navigation update applied');
        return $log;
    }

    private static function exists(string $group, string $key): bool
    {
        return (bool)Database::one('SELECT id FROM settings WHERE setting_group=? AND setting_key=? LIMIT 1', [$group, $key]);
    }

    private static function set(string $group, string $key, string $value, int $public, ?int $userId): void
    {
        if (self::exists($group, $key)) {
            Database::exec('UPDATE settings SET setting_value=?, is_public=?, updated_by=? WHERE setting_group=? AND setting_key=?', [$value, $public, $userId, $group, $key]);
        } else {
            Database::exec('INSERT INTO settings(setting_group,setting_key,setting_value,is_public,updated_by) VALUES(?,?,?,?,?)', [$group, $key, $value, $public, $userId]);
        }
    }
}
