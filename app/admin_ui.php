<?php
/**
 * 97 Foundation V7.1 — Administration UI helpers (icons, navigation map, breadcrumbs).
 */

/** Consistent 24px outline icon set for the Admin workspace (stroke = currentColor). */
function admin_icon(string $name, string $class = ''): string
{
    static $paths = [
        'dashboard' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.6"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.6"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.6"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.6"/>',
        'fundraising' => '<path d="M12 20.5s-7.5-4.4-7.5-10.2A4.3 4.3 0 0 1 12 7.6a4.3 4.3 0 0 1 7.5 2.7c0 5.8-7.5 10.2-7.5 10.2Z"/>',
        'campaign' => '<path d="M5 21V4"/><path d="M5 4.5h10.5l-1.8 3.5 1.8 3.5H5"/>',
        'donation' => '<rect x="3" y="6" width="18" height="12" rx="2.5"/><circle cx="12" cy="12" r="2.6"/><path d="M6.5 9.5v.01M17.5 14.5v.01"/>',
        'bank' => '<path d="M3 9.5 12 4l9 5.5"/><path d="M5 10v7M9.5 10v7M14.5 10v7M19 10v7"/><path d="M3 20h18"/>',
        'card' => '<rect x="3" y="5.5" width="18" height="13" rx="2.5"/><path d="M3 10h18M7 15h3"/>',
        'people' => '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 19.5a5.5 5.5 0 0 1 11 0"/><path d="M15.5 5.2a3 3 0 0 1 0 5.6M17.5 13.6a5 5 0 0 1 3 5.9"/>',
        'member' => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><circle cx="9" cy="11" r="2.2"/><path d="M5.8 16.2a3.4 3.4 0 0 1 6.4 0M14.5 10h4M14.5 13.5h3"/>',
        'registration' => '<circle cx="10" cy="8" r="3.4"/><path d="M3.5 20a6.5 6.5 0 0 1 10.4-5.2"/><path d="M18 14v6M15 17h6"/>',
        'volunteer' => '<path d="M11 13.5 7.4 10a2.4 2.4 0 0 1 3.4-3.4l.2.2.2-.2a2.4 2.4 0 0 1 3.4 3.4Z"/><path d="M3 15.5h3.5l3 2.2h4.3a1.6 1.6 0 0 0 0-3.2h-2.4M14.5 16.3l4.3-2.4a1.6 1.6 0 0 1 2.2 2.2l-5.8 4H9l-6-1"/>',
        'officials' => '<circle cx="12" cy="9" r="5.5"/><path d="m8.6 13.4-1.6 7.1 5-2.7 5 2.7-1.6-7.1"/><path d="m12 6.6.8 1.6 1.7.2-1.2 1.2.3 1.7-1.6-.8-1.6.8.3-1.7-1.2-1.2 1.7-.2Z"/>',
        'support' => '<path d="M20.5 12a8 8 0 0 1-11.6 7.1L4 20.5l1.4-4.6A8 8 0 1 1 20.5 12Z"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m4 7.5 8 5.5 8-5.5"/>',
        'chat' => '<path d="M20 11.5a7.5 7.5 0 0 1-10.8 6.7L4 19.5l1.4-4.3A7.5 7.5 0 1 1 20 11.5Z"/><path d="M8.5 11.5h.01M12 11.5h.01M15.5 11.5h.01"/>',
        'content' => '<rect x="3.5" y="4" width="17" height="16" rx="2.5"/><path d="M3.5 9h17M9 9v11"/>',
        'edit' => '<path d="M4 20h4L19 9a2.8 2.8 0 0 0-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/>',
        'heart' => '<path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z"/>',
        'event' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
        'gallery' => '<rect x="3.5" y="4.5" width="17" height="15" rx="2.5"/><circle cx="9" cy="10" r="1.8"/><path d="m4 17 4.5-4.5 3.5 3.5 2.5-2.5 5.5 5"/>',
        'news' => '<path d="M5 20h13a2 2 0 0 0 2-2V5H7v13a2 2 0 0 1-4 0V9h4"/><path d="M10.5 9h6M10.5 12.5h6M10.5 16h3.5"/>',
        'brand' => '<path d="M12 3a9 9 0 1 0 0 18c1.1 0 1.6-.8 1.6-1.6 0-1.2-.9-1.6-.9-2.6 0-.9.7-1.6 1.6-1.6h1.9A4.8 4.8 0 0 0 21 10.5C21 6.4 17 3 12 3Z"/><circle cx="7.5" cy="11" r="1.1"/><circle cx="10.5" cy="7" r="1.1"/><circle cx="15.5" cy="7.5" r="1.1"/>',
        'developer' => '<path d="m8 8-4.5 4L8 16M16 8l4.5 4-4.5 4M13.8 5l-3.6 14"/>',
        'plug' => '<path d="M9 3v5M15 3v5M6.5 8h11v3.5a5.5 5.5 0 0 1-11 0V8ZM12 17v4"/>',
        'system' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1-2.8 2.8-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21h-4v-.1a1.7 1.7 0 0 0-1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1L4.2 17l.1-.1a1.7 1.7 0 0 0 .3-1.9A1.7 1.7 0 0 0 3 14v-4h.1a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9L4.2 7 7 4.2l.1.1A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.6V3h4v.1a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1L19.8 7l-.1.1a1.7 1.7 0 0 0-.3 1.9 1.7 1.7 0 0 0 1.6 1h.1v4H21a1.7 1.7 0 0 0-1.6 1Z"/>',
        'share' => '<circle cx="18" cy="5.5" r="2.5"/><circle cx="6" cy="12" r="2.5"/><circle cx="18" cy="18.5" r="2.5"/><path d="m8.2 10.8 7.6-4M8.2 13.2l7.6 4"/>',
        'shield' => '<path d="M12 3 4.5 6v5.5c0 4.6 3.2 8.4 7.5 9.5 4.3-1.1 7.5-4.9 7.5-9.5V6L12 3Z"/><path d="m9 12 2.2 2.2L15.5 10"/>',
        'key' => '<circle cx="8" cy="15.5" r="4"/><path d="m10.8 12.7 8.7-8.7M16.5 7l2.5 2.5M14 9.5l2 2"/>',
        'user' => '<circle cx="12" cy="8.5" r="4"/><path d="M4.5 20.5a7.5 7.5 0 0 1 15 0"/>',
        'admins' => '<circle cx="9.5" cy="8" r="3.5"/><path d="M3 20a6.5 6.5 0 0 1 10-5.5"/><path d="M17.5 13 20 14v2.2c0 1.6-1 3-2.5 3.6-1.5-.6-2.5-2-2.5-3.6V14l2.5-1Z"/>',
        'external' => '<path d="M14 4h6v6M20 4l-9 9"/><path d="M19 14v4a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h4"/>',
        'logout' => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="m10 16 4-4-4-4M14 12H4"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
        'bell' => '<path d="M18 8.5a6 6 0 0 0-12 0c0 6.5-2.5 8-2.5 8h17S18 15 18 8.5Z"/><path d="M10 20.5a2.2 2.2 0 0 0 4 0"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'chevron' => '<path d="m9 6 6 6-6 6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'check' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'home' => '<path d="m3.5 11 8.5-7 8.5 7"/><path d="M5.5 9.5V20h13V9.5"/><path d="M10 20v-5.5h4V20"/>',
        'upload' => '<path d="M12 15V4m0 0-4 4m4-4 4 4"/><path d="M4 15v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>',
        'eye' => '<path d="M2.8 12s3.2-6 9.2-6 9.2 6 9.2 6-3.2 6-9.2 6-9.2-6-9.2-6Z"/><circle cx="12" cy="12" r="2.6"/>',
        'eye_off' => '<path d="M10.6 6.1A9.6 9.6 0 0 1 12 6c6 0 9.2 6 9.2 6a15.5 15.5 0 0 1-2.4 3.2M6.6 7.6C4.2 9.2 2.8 12 2.8 12s3.2 6 9.2 6a9 9 0 0 0 4.4-1.1"/><path d="M9.9 9.9a2.6 2.6 0 0 0 3.7 3.7M4 4l16 16"/>',
        'trash' => '<path d="M4 7h16M9 7V4.5h6V7M6.5 7l1 13h9l1-13M10 11v5.5M14 11v5.5"/>',
        'pin' => '<path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11Z"/><circle cx="12" cy="10" r="2.4"/>',
        'clock' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'power' => '<path d="M12 3v8"/><path d="M6.3 6.8a8 8 0 1 0 11.4 0"/>',
        'slider' => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m3 15 5-5 4 4 3-3 6 6"/><circle cx="15.5" cy="9.5" r="1.4"/>',
    ];
    $body = $paths[$name] ?? $paths['dashboard'];
    return '<svg class="' . e(trim('ai ' . $class)) . '" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $body . '</svg>';
}

/** Breadcrumb for the admin top bar: [section label, page label]. */
function admin_breadcrumb(string $path, string $title): array
{
    $map = [
        '/admin/campaigns' => 'Fundraising', '/admin/donations' => 'Fundraising', '/admin/donation-settings' => 'Fundraising', '/admin/online-payments' => 'Fundraising',
        '/admin/members' => 'People & Community', '/admin/volunteers' => 'People & Community', '/admin/officials' => 'People & Community',
        '/admin/messages' => 'Support & Messages', '/admin/live-chat' => 'Support & Messages',
        '/admin/content' => 'Content & Website', '/admin/causes' => 'Content & Website', '/admin/events' => 'Content & Website', '/admin/gallery' => 'Content & Website', '/admin/posts' => 'Content & Website', '/admin/branding' => 'Content & Website',
        '/admin/developer' => 'Developer Integration',
        '/admin/administrators' => 'System', '/admin/social' => 'System', '/admin/security' => 'System', '/admin/account-security' => 'System', '/admin/profile' => 'System', '/admin/notifications' => 'System',
    ];
    foreach ($map as $prefix => $section) {
        if ($path === $prefix || str_starts_with($path, $prefix . '/')) return [$section, $title];
    }
    return ['Overview', $title];
}

/** Current admin path with its query string, for "return here" redirects (V7.2). */
function admin_return_path(): string
{
    $q = (string)($_SERVER['QUERY_STRING'] ?? '');
    return ltrim(current_path(), '/') . ($q !== '' ? '?' . $q : '');
}
