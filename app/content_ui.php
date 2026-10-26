<?php
/**
 * 97 Foundation V7.2 — News & Blog and sharing helpers.
 */

/** URL-safe slug ("Food Drive 2026!" → "food-drive-2026"). */
function slugify(string $text, int $max = 180): string
{
    $s = strtolower(trim($text));
    if (function_exists('iconv')) { $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s); if (is_string($t) && $t !== '') $s = strtolower($t); }
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    $s = trim(substr(trim($s, '-'), 0, $max), '-');
    return $s;
}

/** Estimated reading time in minutes (220 words per minute, at least 1). */
function reading_minutes(?string $text): int
{
    $words = str_word_count(strip_tags((string)$text));
    return max(1, (int)ceil($words / 220));
}

/** Inline formatting on already-escaped text: **bold**, _italic_ and [label](https://link). */
function article_inline(string $escaped): string
{
    $out = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $escaped) ?? $escaped;
    $out = preg_replace('/(?<![\w])_(?!\s)(.+?)(?<!\s)_(?![\w])/s', '<em>$1</em>', $out) ?? $out;
    return preg_replace_callback('/\[([^\]]{1,200})\]\(((?:https?:\/\/|\/)[^\s)]{1,500})\)/', static function (array $m): string {
        $href = html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
        if (!preg_match('~^(https?://|/)~i', $href)) return $m[0];
        $external = preg_match('~^https?://~i', $href) && !str_starts_with($href, absolute_url(''));
        return '<a href="' . e($href) . '"' . ($external ? ' target="_blank" rel="noopener noreferrer nofollow"' : '') . '>' . $m[1] . '</a>';
    }, $out) ?? $out;
}

/**
 * Turn plain article text into safe, semantic HTML.
 * Supports "## Heading", "### Sub-heading", "- list item", "1. numbered item", "> quote" and blank-line paragraphs.
 * Everything is escaped first, so no HTML from the editor ever reaches the page.
 * @return array{html:string,toc:array<int,array{id:string,text:string}>}
 */
function article_render(?string $text): array
{
    $text = str_replace(["\r\n", "\r"], "\n", (string)$text);
    $blocks = preg_split('/\n{2,}/', trim($text)) ?: [];
    $html = '';
    $toc = [];
    $used = [];
    foreach ($blocks as $block) {
        $lines = array_values(array_filter(array_map('rtrim', explode("\n", $block)), static fn($l) => trim($l) !== ''));
        if (!$lines) continue;
        $first = ltrim($lines[0]);
        if (count($lines) === 1 && preg_match('/^(#{2,3})\s+(.+)$/', $first, $m)) {
            $level = strlen($m[1]);
            $plain = trim($m[2]);
            $id = slugify($plain, 60) ?: 'section';
            $base = $id; $n = 2;
            while (isset($used[$id])) $id = $base . '-' . $n++;
            $used[$id] = true;
            if ($level === 2) $toc[] = ['id' => $id, 'text' => $plain];
            $html .= '<h' . $level . ' id="' . e($id) . '">' . article_inline(e($plain)) . '</h' . $level . '>';
            continue;
        }
        if (preg_match('/^[-*•]\s+/', $first) && count(array_filter($lines, static fn($l) => preg_match('/^\s*[-*•]\s+/', $l))) === count($lines)) {
            $html .= '<ul>';
            foreach ($lines as $l) $html .= '<li>' . article_inline(e(preg_replace('/^\s*[-*•]\s+/', '', $l))) . '</li>';
            $html .= '</ul>';
            continue;
        }
        if (preg_match('/^\d+[.)]\s+/', $first) && count(array_filter($lines, static fn($l) => preg_match('/^\s*\d+[.)]\s+/', $l))) === count($lines)) {
            $html .= '<ol>';
            foreach ($lines as $l) $html .= '<li>' . article_inline(e(preg_replace('/^\s*\d+[.)]\s+/', '', $l))) . '</li>';
            $html .= '</ol>';
            continue;
        }
        if (str_starts_with($first, '>')) {
            $quote = implode("\n", array_map(static fn($l) => ltrim(preg_replace('/^\s*>\s?/', '', $l)), $lines));
            $html .= '<blockquote><p>' . nl2br(article_inline(e($quote)), false) . '</p></blockquote>';
            continue;
        }
        // A heading line followed by text in the same block.
        if (preg_match('/^(#{2,3})\s+(.+)$/', $first, $m)) {
            array_shift($lines);
            $rest = article_render($m[0] . "\n\n" . implode("\n", $lines));
            $html .= $rest['html'];
            foreach ($rest['toc'] as $t) $toc[] = $t;
            continue;
        }
        $html .= '<p>' . nl2br(article_inline(e(implode("\n", $lines))), false) . '</p>';
    }
    return ['html' => $html, 'toc' => $toc];
}

/** Public URL of a post. */
function post_url(array $post): string { return url('news/' . rawurlencode((string)$post['slug'])); }

/**
 * Accessible share bar: WhatsApp, Facebook, LinkedIn, X, Telegram, email, copy link and native share.
 * $url must be an absolute URL.
 */
function share_bar(string $url, string $title, string $text = '', string $context = 'article', string $class = ''): string
{
    $u = rawurlencode($url);
    $t = rawurlencode($title);
    $body = rawurlencode(trim(($text !== '' ? $text . "\n\n" : '') . $url));
    $links = [
        'whatsapp' => ['WhatsApp', 'https://wa.me/?text=' . rawurlencode($title . ' ' . $url)],
        'facebook' => ['Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $u],
        'linkedin' => ['LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $u],
        'x' => ['X', 'https://x.com/intent/post?text=' . $t . '&url=' . $u],
        'telegram' => ['Telegram', 'https://t.me/share/url?url=' . $u . '&text=' . $t],
    ];
    $label = $context === 'campaign' ? 'Share this campaign' : 'Share this article';
    $html = '<div class="share7 ' . e($class) . '" data-share data-share-url="' . e($url) . '" data-share-title="' . e($title) . '" data-share-text="' . e($text) . '">';
    $html .= '<span class="share7-label">' . e($label) . '</span><div class="share7-row">';
    $html .= '<button type="button" class="share7-btn share7-native" data-share-native hidden aria-label="Share using your device">' . site_line_icon('network') . '<span>Share</span></button>';
    foreach ($links as $key => [$name, $href]) {
        $html .= '<a class="share7-btn is-' . $key . '" href="' . e($href) . '" target="_blank" rel="noopener noreferrer" aria-label="Share on ' . e($name) . '" title="Share on ' . e($name) . '" data-share-network="' . $key . '">' . social_icon_svg($key === 'x' ? 'x.com' : $key) . '</a>';
    }
    $html .= '<a class="share7-btn is-email" href="mailto:?subject=' . $t . '&amp;body=' . $body . '" aria-label="Share by email" title="Share by email">' . site_line_icon('mail') . '</a>';
    $html .= '<button type="button" class="share7-btn share7-copy" data-share-copy aria-label="Copy link">' . social_icon_svg('link') . '<span data-share-copy-label>Copy link</span></button>';
    $html .= '</div><span class="sr-only" aria-live="polite" data-share-status></span></div>';
    return $html;
}
