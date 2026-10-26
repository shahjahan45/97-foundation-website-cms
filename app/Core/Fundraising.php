<?php
namespace App\Core;

/**
 * V7.2 — global fundraising switch (Admin → Fundraising Campaigns).
 *
 * fundraising.enabled              1 = campaigns are public and accept donations (default)
 * fundraising.general_when_paused  1 = keep accepting general Foundation donations while campaigns are paused (default)
 * fundraising.paused_message       optional notice shown on public pages while paused
 *
 * Nothing is deleted when fundraising is paused: campaigns, totals and donation records stay intact.
 */
final class Fundraising
{
    private static ?array $cache = null;

    private static function load(): array
    {
        if (self::$cache !== null) return self::$cache;
        $out = ['enabled' => '1', 'general_when_paused' => '1', 'paused_message' => ''];
        try {
            foreach (Database::all('SELECT setting_key,setting_value FROM settings WHERE setting_group="fundraising"') as $row) {
                $out[(string)$row['setting_key']] = (string)$row['setting_value'];
            }
        } catch (\Throwable) {
            // Settings table unavailable: fall back to "enabled" so donations are never silently blocked.
        }
        return self::$cache = $out;
    }

    public static function enabled(): bool { return (self::load()['enabled'] ?? '1') !== '0'; }

    /** Can visitors donate to the general Foundation fund right now? */
    public static function generalDonationsOpen(): bool
    {
        return self::enabled() || (self::load()['general_when_paused'] ?? '1') !== '0';
    }

    /** Can visitors make any kind of donation right now? */
    public static function donationsOpen(): bool { return self::generalDonationsOpen(); }

    public static function pausedMessage(): string
    {
        $m = trim((string)(self::load()['paused_message'] ?? ''));
        return $m !== '' ? $m : 'Our fundraising campaigns are paused at the moment. Thank you for your support — please check back soon.';
    }

    public static function settings(): array { return self::load(); }

    /** A campaign accepts new donations only when fundraising is on and the campaign itself is active and public. */
    public static function campaignOpen(?array $campaign): bool
    {
        if (!$campaign || !self::enabled()) return false;
        return ($campaign['status'] ?? '') === 'active' && (int)($campaign['is_active'] ?? 0) === 1 && (int)($campaign['is_visible'] ?? 0) === 1;
    }

    public static function save(bool $enabled, bool $generalWhenPaused, string $message, ?int $userId): void
    {
        $rows = [
            ['enabled', $enabled ? '1' : '0'],
            ['general_when_paused', $generalWhenPaused ? '1' : '0'],
            ['paused_message', mb_substr(trim($message), 0, 400)],
        ];
        foreach ($rows as [$key, $value]) {
            Database::exec('INSERT INTO settings(setting_group,setting_key,setting_value,is_public,updated_by) VALUES("fundraising",?,?,1,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value),is_public=VALUES(is_public),updated_by=VALUES(updated_by)', [$key, $value, $userId]);
        }
        self::$cache = null;
    }

    public static function reset(): void { self::$cache = null; }
}
