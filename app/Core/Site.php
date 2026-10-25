<?php
namespace App\Core;
final class Site
{
    public static function settings(): array
    {
        $rows=Database::all('SELECT setting_group,setting_key,setting_value FROM settings WHERE is_public=1'); $s=[];
        foreach($rows as $r) $s[$r['setting_group']][$r['setting_key']]=$r['setting_value']; return $s;
    }
    public static function page(string $slug): ?array { return Database::one('SELECT * FROM pages WHERE slug=? AND is_active=1 LIMIT 1',[$slug]); }
}
