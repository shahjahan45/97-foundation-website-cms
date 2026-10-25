<?php
/**
 * 97 Foundation V7.1 — shared website UI helpers.
 *
 * Header navigation, Material Symbols icons, brand assets and hero banner
 * settings used by the public layout and the Admin content manager.
 */

use App\Core\Database;

/**
 * Google Material Symbols (Outlined, 24px, weight 400) as inline SVG.
 * Inline paths keep the site CSP-safe (no external font requests) while using
 * the official Material Symbols artwork requested in the design brief.
 */
function site_icon(string $name, string $class = ''): string
{
    static $icons = [
        'mail' => 'M160-160q-33 0-56.5-23.5T80-240v-480q0-33 23.5-56.5T160-800h640q33 0 56.5 23.5T880-720v480q0 33-23.5 56.5T800-160H160Zm320-280L160-640v400h640v-400L480-440Zm0-80 320-200H160l320 200ZM160-640v-80 480-400Z',
        'call' => 'M798-120q-125 0-247-54.5T329-329Q229-429 174.5-551T120-798q0-18 12-30t30-12h162q14 0 25 9.5t13 22.5l26 140q2 16-1 27t-11 19l-97 98q20 37 47.5 71.5T387-386q31 31 65 57.5t72 48.5l94-94q9-9 23.5-13.5T670-390l138 28q14 4 23 14.5t9 23.5v162q0 18-12 30t-30 12ZM241-600l66-66-17-94h-89q5 41 14 81t26 79Zm358 358q39 17 79.5 27t81.5 13v-88l-94-19-67 67ZM241-600Zm358 358Z',
        'login' => 'M480-120v-80h280v-560H480v-80h280q33 0 56.5 23.5T840-760v560q0 33-23.5 56.5T760-120H480Zm-80-160-55-58 102-102H120v-80h327L345-622l55-58 200 200-200 200Z',
        'favorite' => 'm480-120-58-52q-101-91-167-157T150-447.5Q111-500 95.5-544T80-634q0-94 63-157t157-63q52 0 99 22t81 62q34-40 81-62t99-22q94 0 157 63t63 157q0 46-15.5 90T810-447.5Q771-395 705-329T538-172l-58 52Z',
        'expand_more' => 'M480-345 240-585l56-56 184 184 184-184 56 56-240 240Z',
        'menu' => 'M120-240v-80h720v80H120Zm0-200v-80h720v80H120Zm0-200v-80h720v80H120Z',
        'close' => 'm256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z',
        'diamond' => 'm368-630 106-210h12l106 210H368Zm82 474L105-570h345v414Zm60 0v-414h345L510-156Zm148-474L554-840h206l105 210H658Zm-563 0 105-210h206L302-630H95Z',
        'school' => 'M480-120 200-272v-240L40-600l440-240 440 240v320h-80v-276l-80 44v240L480-120Zm0-332 274-148-274-148-274 148 274 148Zm0 241 200-108v-151L480-360 280-470v151l200 108Zm0-241Zm0 90Zm0 0Z',
        'family_restroom' => 'M720-720q-33 0-56.5-23.5T640-800q0-33 23.5-56.5T720-880q33 0 56.5 23.5T800-800q0 33-23.5 56.5T720-720ZM680-80v-320q0-40-20.5-72T607-522l35-103q8-25 29.5-40t48.5-15q27 0 48.5 15t29.5 40l102 305H800v240H680ZM500-500q-25 0-42.5-17.5T440-560q0-25 17.5-42.5T500-620q25 0 42.5 17.5T560-560q0 25-17.5 42.5T500-500ZM220-720q-33 0-56.5-23.5T140-800q0-33 23.5-56.5T220-880q33 0 56.5 23.5T300-800q0 33-23.5 56.5T220-720ZM140-80v-280H80v-240q0-33 23.5-56.5T160-680h120q33 0 56.5 23.5T360-600v240h-60v280H140Zm300 0v-160h-40v-160q0-25 17.5-42.5T460-460h80q25 0 42.5 17.5T600-400v160h-40v160H440Z',
        'menu_book' => 'M560-564v-68q33-14 67.5-21t72.5-7q26 0 51 4t49 10v64q-24-9-48.5-13.5T700-600q-38 0-73 9.5T560-564Zm0 220v-68q33-14 67.5-21t72.5-7q26 0 51 4t49 10v64q-24-9-48.5-13.5T700-380q-38 0-73 9t-67 27Zm0-110v-68q33-14 67.5-21t72.5-7q26 0 51 4t49 10v64q-24-9-48.5-13.5T700-490q-38 0-73 9.5T560-454ZM260-320q47 0 91.5 10.5T440-278v-394q-41-24-87-36t-93-12q-36 0-71.5 7T120-692v396q35-12 69.5-18t70.5-6Zm260 42q44-21 88.5-31.5T700-320q36 0 70.5 6t69.5 18v-396q-33-14-68.5-21t-71.5-7q-47 0-93 12t-87 36v394Zm-40 118q-48-38-104-59t-116-21q-42 0-82.5 11T100-198q-21 11-40.5-1T40-234v-482q0-11 5.5-21T62-752q46-24 96-36t102-12q58 0 113.5 15T480-740q51-30 106.5-45T700-800q52 0 102 12t96 36q11 5 16.5 15t5.5 21v482q0 23-19.5 35t-40.5 1q-37-20-77.5-31T700-240q-60 0-116 21t-104 59ZM280-494Z',
        'handshake' => 'M475-160q4 0 8-2t6-4l328-328q12-12 17.5-27t5.5-30q0-16-5.5-30.5T817-607L647-777q-11-12-25.5-17.5T591-800q-15 0-30 5.5T534-777l-11 11 74 75q15 14 22 32t7 38q0 42-28.5 70.5T527-522q-20 0-38.5-7T456-550l-75-74-175 175q-3 3-4.5 6.5T200-435q0 8 6 14.5t14 6.5q4 0 8-2t6-4l136-136 56 56-135 136q-3 3-4.5 6.5T285-350q0 8 6 14t14 6q4 0 8-2t6-4l136-135 56 56-135 136q-3 2-4.5 6t-1.5 8q0 8 6 14t14 6q4 0 7.5-1.5t6.5-4.5l136-135 56 56-136 136q-3 3-4.5 6.5T454-180q0 8 6.5 14t14.5 6Zm-1 80q-37 0-65.5-24.5T375-166q-34-5-57-28t-28-57q-34-5-56.5-28.5T206-336q-38-5-62-33t-24-66q0-20 7.5-38.5T149-506l232-231 131 131q2 3 6 4.5t8 1.5q9 0 15-5.5t6-14.5q0-4-1.5-8t-4.5-6L398-777q-11-12-25.5-17.5T342-800q-15 0-30 5.5T285-777L144-635q-9 9-15 21t-8 24q-2 12 0 24.5t8 23.5l-58 58q-17-23-25-50.5T40-590q2-28 14-54.5T87-692l141-141q24-23 53.5-35t60.5-12q31 0 60.5 12t52.5 35l11 11 11-11q24-23 53.5-35t60.5-12q31 0 60.5 12t52.5 35l169 169q23 23 35 53t12 61q0 31-12 60.5T873-437L545-110q-14 14-32.5 22T474-80Zm-99-560Z',
    ];
    $path = $icons[$name] ?? $icons['favorite'];
    $cls = trim('ms-icon ms-' . preg_replace('/[^a-z0-9_-]/', '', $name) . ' ' . $class);
    return '<svg class="' . e($cls) . '" viewBox="0 -960 960 960" width="24" height="24" fill="currentColor" aria-hidden="true" focusable="false"><path d="' . $path . '"/></svg>';
}

/** Facebook brand mark (not part of Material Symbols). */
function site_facebook_icon(string $class = ''): string
{
    return '<svg class="' . e(trim('ms-icon ms-facebook ' . $class)) . '" viewBox="0 0 24 24" width="24" height="24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M24 12.07C24 5.41 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.04V9.41c0-3.02 1.8-4.7 4.54-4.7 1.31 0 2.68.24 2.68.24v2.97h-1.5c-1.5 0-1.96.93-1.96 1.89v2.26h3.32l-.53 3.5h-2.8V24C19.62 23.1 24 18.1 24 12.07Z"/></svg>';
}

/** Official bundled brand assets shipped with V7.1. */
function brand_asset(string $kind): string
{
    $map = [
        'logo' => 'images/brand/97-foundation-logo.png',
        'wordmark' => 'images/brand/97-foundation-wordmark.png',
        'icon' => 'images/brand/97-foundation-icon-192.png',
    ];
    return asset($map[$kind] ?? $map['logo']);
}

/** Header logo: admin upload first, official bundled logo as the professional fallback. */
function brand_logo_url(array $foundation = []): string
{
    $logo = trim((string)($foundation['logo_path'] ?? setting('foundation', 'logo_path', '')));
    return $logo !== '' ? public_upload($logo) : brand_asset('logo');
}

/** Header foundation text image (wordmark) — admin upload or bundled default. */
function brand_wordmark_url(array $foundation = []): string
{
    $mark = trim((string)($foundation['wordmark_path'] ?? setting('foundation', 'wordmark_path', '')));
    return $mark !== '' ? public_upload($mark) : brand_asset('wordmark');
}

/** Header logo size defaults (pixels). */
function header_brand_defaults(): array
{
    return [
        'logo_height' => 62,
        'logo_height_mobile' => 48,
        'show_wordmark' => '1',
        'wordmark_height' => 56,
        'shrink_on_scroll' => '1',
    ];
}

/**
 * Header logo sizing managed in Admin → Branding & SEO.
 * Returns clamped values plus the CSS custom properties for the header element.
 */
function header_brand_settings(?array $settings = null): array
{
    $saved = $settings['header'] ?? null;
    if ($saved === null) {
        $saved = [];
        try {
            foreach (Database::all('SELECT setting_key,setting_value FROM settings WHERE setting_group="header"') as $row) $saved[$row['setting_key']] = (string)$row['setting_value'];
        } catch (\Throwable) {}
    }
    $d = header_brand_defaults();
    $v = static fn(string $k, int $min, int $max): int => max($min, min($max, (int)(($saved[$k] ?? '') !== '' ? $saved[$k] : $d[$k])));
    $cfg = [
        'logo_height' => $v('logo_height', 40, 110),
        'logo_height_mobile' => $v('logo_height_mobile', 34, 72),
        'wordmark_height' => $v('wordmark_height', 32, 96),
        'show_wordmark' => (($saved['show_wordmark'] ?? $d['show_wordmark']) === '1') ? '1' : '0',
        'shrink_on_scroll' => (($saved['shrink_on_scroll'] ?? $d['shrink_on_scroll']) === '1') ? '1' : '0',
    ];
    $tallest = max($cfg['logo_height'], $cfg['show_wordmark'] === '1' ? $cfg['wordmark_height'] : 0);
    $cfg['header_height'] = max(72, $tallest + 18);
    $cfg['header_height_mobile'] = max(62, $cfg['logo_height_mobile'] + 16);
    $cfg['style'] = sprintf('--logo-base:%dpx;--mark-base:%dpx;--logo-base-m:%dpx;--mark-base-m:%dpx;--hdr-base:%dpx;--hdr-base-m:%dpx',
        $cfg['logo_height'], $cfg['wordmark_height'], $cfg['logo_height_mobile'],
        (int)round(min($cfg['wordmark_height'], $cfg['logo_height_mobile'] * 0.95)), $cfg['header_height'], $cfg['header_height_mobile']);
    return $cfg;
}

/** Digits-only telephone target for tel: links, preserving a leading +. */
function tel_href(string $phone): string
{
    $phone = trim($phone);
    $digits = preg_replace('/\D+/', '', $phone);
    return ($phone !== '' && $phone[0] === '+' ? '+' : '') . $digits;
}

/**
 * Primary website navigation (V7.1 brief):
 * Home · About Us (What We Do, 97 Foundation) · Members (Officials, Past Officials)
 * · News & Blogs · Gallery · Volunteer · Contact Us
 */
function site_primary_nav(): array
{
    return [
        ['label' => 'Home', 'path' => '', 'match' => ['']],
        ['label' => 'About Us', 'path' => 'about', 'match' => ['about', 'what-we-do'], 'children' => [
            ['label' => 'What We Do', 'path' => 'what-we-do', 'description' => 'Our programmes and areas of support'],
            ['label' => '97 Foundation', 'path' => 'about', 'description' => 'Our story, mission and values'],
        ]],
        ['label' => 'Members', 'path' => 'members', 'match' => ['members', 'members/*'], 'children' => [
            ['label' => 'Officials', 'path' => 'members/officials', 'description' => 'Current office bearers'],
            ['label' => 'Past Officials', 'path' => 'members/past-officials', 'description' => 'Leaders who served before'],
        ]],
        ['label' => 'News & Blogs', 'path' => 'news', 'match' => ['news', 'news/*']],
        ['label' => 'Gallery', 'path' => 'gallery', 'match' => ['gallery']],
        ['label' => 'Volunteer', 'path' => 'volunteer', 'match' => ['volunteer']],
        ['label' => 'Contact Us', 'path' => 'contact', 'match' => ['contact']],
    ];
}

/** True when the current request belongs to a navigation item (or one of its children). */
function site_nav_is_active(array $item): bool
{
    $current = trim(current_path(), '/');
    foreach (($item['match'] ?? [$item['path'] ?? '']) as $pattern) {
        $pattern = trim((string)$pattern, '/');
        if (str_ends_with($pattern, '/*')) {
            $prefix = substr($pattern, 0, -2);
            if ($current === $prefix || str_starts_with($current, $prefix . '/')) return true;
        } elseif ($current === $pattern) {
            return true;
        }
    }
    return false;
}

function site_nav_child_is_active(array $child): bool
{
    return trim(current_path(), '/') === trim((string)$child['path'], '/');
}

/** Number of background images in the hero carousel loop (V7.1 brief). */
const HERO_SLIDE_COUNT = 5;

/** Hero banner defaults (from the approved Figma design). */
function hero_defaults(): array
{
    $defaults = [
        'eyebrow' => '97 FOUNDATION',
        'title_line_1' => 'Small Acts.',
        'title_line_2' => 'Greater Impact.',
        'subtitle' => 'Join 97 Foundation as a volunteer and help turn compassion into action — empowering families and building a stronger community.',
        'primary_text' => 'Become a Volunteer',
        'primary_url' => 'volunteer',
        'secondary_text' => 'TOGETHER WE CAN',
        'secondary_url' => 'about',
        'supporters_text' => 'Community-powered support',
        'highlight_1' => 'Education Support',
        'highlight_2' => 'Family Support',
        'highlight_3' => 'Students Empowerment',
        'highlight_4' => 'Social Work',
        'strip_text' => 'UNITY · SERVICE · IMPACT',
        'show_circles' => '1',
        'overlay_opacity' => '82',
        'interval_seconds' => '6',
    ];
    $alts = [
        1 => 'Volunteers joining hands together',
        2 => 'Smiling children supported by the community',
        3 => 'Volunteers preparing food parcels',
        4 => 'Children gathered at a community programme',
        5 => 'Happy children at a Foundation outreach event',
    ];
    for ($i = 1; $i <= HERO_SLIDE_COUNT; $i++) {
        $defaults['bg_image_' . $i] = '';
        $defaults['bg_mobile_' . $i] = '';
        $defaults['bg_alt_' . $i] = $alts[$i];
    }
    for ($i = 1; $i <= 3; $i++) $defaults['circle_image_' . $i] = '';
    return $defaults;
}

/** Hero settings merged over defaults. Accepts the public settings array when already loaded. */
function hero_settings(?array $settings = null): array
{
    $saved = $settings['hero'] ?? null;
    if ($saved === null) {
        $saved = [];
        try {
            foreach (Database::all('SELECT setting_key,setting_value FROM settings WHERE setting_group="hero"') as $row) {
                $saved[$row['setting_key']] = (string)$row['setting_value'];
            }
        } catch (\Throwable) {
            $saved = [];
        }
    }
    $hero = hero_defaults();
    foreach ($hero as $key => $default) {
        if (!array_key_exists($key, $saved)) continue;
        $value = trim((string)$saved[$key]);
        // Image slots may be cleared intentionally (= use the bundled photo); text keeps its default when blank.
        if ($value !== '' || preg_match('/^(bg_image_|bg_mobile_|circle_image_)/', $key)) $hero[$key] = $value;
    }
    $hero['overlay_opacity'] = (string)max(40, min(95, (int)$hero['overlay_opacity']));
    $hero['interval_seconds'] = (string)max(3, min(15, (int)$hero['interval_seconds']));
    return $hero;
}

/** The five background slides: admin uploads first, bundled photography as fallback. */
function hero_background_slides(array $hero): array
{
    $slides = [];
    for ($i = 1; $i <= HERO_SLIDE_COUNT; $i++) {
        $desktop = trim((string)($hero['bg_image_' . $i] ?? ''));
        $mobile = trim((string)($hero['bg_mobile_' . $i] ?? ''));
        $slides[] = [
            'index' => $i,
            'desktop' => $desktop !== '' ? public_upload($desktop) : asset('images/hero/hero-' . $i . '.jpg'),
            'mobile' => $mobile !== '' ? public_upload($mobile) : ($desktop !== '' ? '' : asset('images/hero/hero-' . $i . '-mobile.jpg')),
            'alt' => (string)($hero['bg_alt_' . $i] ?? ''),
            'custom' => $desktop !== '',
        ];
    }
    return $slides;
}

/** Hero circle image with the bundled design photography as fallback. */
function hero_circle_image(array $hero, int $index): string
{
    $path = trim((string)($hero['circle_image_' . $index] ?? ''));
    return $path !== '' ? public_upload($path) : asset('images/hero/circle-' . max(1, min(3, $index)) . '.jpg');
}

/** Resolve an internal path or absolute URL entered by an administrator. */
function site_link(string $target, string $fallback = ''): string
{
    $target = trim($target) !== '' ? trim($target) : $fallback;
    if (preg_match('~^(https?:|mailto:|tel:|#)~i', $target)) return $target;
    return url(ltrim($target, '/'));
}
