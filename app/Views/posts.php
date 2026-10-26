<?php
$categories = $categories ?? [];
$cat = $category ?? null;
$type = $type ?? '';
$q = $q ?? '';
$base = $base ?? 'news';
$arrow = site_line_icon('arrow');
$link = static function (string $path, array $query = []): string {
    $query = array_filter($query, static fn($v) => $v !== '' && $v !== null && $v !== 1);
    return url($path) . ($query ? '?' . http_build_query($query) : '');
};
$card = static function (array $p, string $extra = '') use ($arrow): string {
    $href = post_url($p);
    $img = $p['featured_image'] ? '<img src="' . e(public_upload($p['featured_image'])) . '" alt="" width="800" height="500" loading="lazy" decoding="async">' : '<span class="h7-post-mark">97</span>';
    $chip = !empty($p['category_name']) ? e($p['category_name']) : e(ucfirst((string)$p['post_type']));
    $summary = $p['excerpt'] ?: mb_strimwidth(trim(strip_tags((string)$p['content'])), 0, 150, '…');
    return '<article class="h7-card h7-post ' . e($extra) . '" data-reveal>'
        . '<a class="h7-card-media" href="' . $href . '" data-spa-link tabindex="-1" aria-hidden="true">' . $img . '<span class="h7-chip is-plain">' . $chip . '</span></a>'
        . '<div class="h7-card-body"><span class="n7-meta">' . site_line_icon('calendar') . '<time datetime="' . e(date('Y-m-d', strtotime($p['published_at']))) . '">' . e(date('d M Y', strtotime($p['published_at']))) . '</time><i aria-hidden="true"></i>' . reading_minutes($p['content'] ?? '') . ' min read</span>'
        . '<h3><a href="' . $href . '" data-spa-link>' . e($p['title']) . '</a></h3>'
        . '<p>' . e($summary) . '</p>'
        . '<a class="h7-link" href="' . $href . '" data-spa-link aria-label="Read: ' . e($p['title']) . '"><span>Read article</span>' . $arrow . '</a></div></article>';
};
?>
<section class="page7-hero n7-hero" style="--page7-image:url('<?= e(asset('images/hero/hero-2.jpg')) ?>')">
  <div class="container">
    <nav class="crumb7" aria-label="Breadcrumb"><a href="<?= url('') ?>" data-spa-link>Home</a><span aria-hidden="true">/</span><?php if ($cat): ?><a href="<?= url('news') ?>" data-spa-link>News &amp; Blog</a><span aria-hidden="true">/</span><span aria-current="page"><?= e($cat['name']) ?></span><?php else: ?><span aria-current="page">News &amp; Blog</span><?php endif; ?></nav>
    <h1><?= e($cat ? $cat['name'] : ($type === 'news' ? 'News' : ($type === 'blog' ? 'Blog' : 'News & Blog'))) ?></h1>
    <p><?= e($cat && trim((string)$cat['description']) !== '' ? $cat['description'] : 'Announcements, community stories and updates from 97 Foundation in Thambala, Polonnaruwa.') ?></p>
    <form class="n7-search" role="search" method="get" action="<?= url($base) ?>">
      <?= site_line_icon('search', 'n7-search-icon') ?>
      <label class="sr-only" for="news-search">Search articles</label>
      <input id="news-search" type="search" name="q" value="<?= e($q) ?>" placeholder="Search articles" maxlength="80">
      <?php if ($type): ?><input type="hidden" name="type" value="<?= e($type) ?>"><?php endif; ?>
      <button type="submit">Search</button>
    </form>
  </div>
</section>

<section class="n7-filters" aria-label="Filter articles">
  <div class="container n7-filter-row">
    <div class="n7-chips" role="list">
      <?php foreach (['' => 'All', 'news' => 'News', 'blog' => 'Blog'] as $k => $label): ?>
      <a role="listitem" class="n7-chip <?= $type === $k ? 'is-active' : '' ?>" href="<?= $link($base, ['type' => $k, 'q' => $q]) ?>" data-spa-link <?= $type === $k ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>
    <?php if ($categories): ?>
    <div class="n7-chips is-topics" role="list" aria-label="Topics">
      <a role="listitem" class="n7-chip <?= !$cat ? 'is-active' : '' ?>" href="<?= $link('news', ['type' => $type]) ?>" data-spa-link>All topics</a>
      <?php foreach ($categories as $c): if ((int)$c['posts'] === 0 && !($cat && (int)$cat['id'] === (int)$c['id'])) continue; ?>
      <a role="listitem" class="n7-chip <?= $cat && (int)$cat['id'] === (int)$c['id'] ? 'is-active' : '' ?>" href="<?= $link('news/category/' . $c['slug'], ['type' => $type]) ?>" data-spa-link><?= e($c['name']) ?><small><?= (int)$c['posts'] ?></small></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section class="h7-sec n7-list">
  <div class="container">
    <?php if ($q !== ''): ?><p class="n7-result" role="status"><?= (int)$total ?> result<?= (int)$total === 1 ? '' : 's' ?> for “<?= e($q) ?>”. <a href="<?= $link($base, ['type' => $type]) ?>" data-spa-link>Clear search</a></p><?php endif; ?>

    <?php if (!empty($featured)): $fp = $featured; $fhref = post_url($fp); ?>
    <article class="n7-featured" data-reveal>
      <a class="n7-featured-media" href="<?= $fhref ?>" data-spa-link tabindex="-1" aria-hidden="true"><?php if ($fp['featured_image']): ?><img src="<?= e(public_upload($fp['featured_image'])) ?>" alt="" width="1200" height="675" decoding="async"><?php else: ?><span class="h7-post-mark">97</span><?php endif; ?></a>
      <div class="n7-featured-body">
        <div class="n7-tags"><span class="n7-tag is-strong"><?= (int)$fp['is_featured'] === 1 ? 'Featured' : 'Latest' ?></span><?php if (!empty($fp['category_name'])): ?><a class="n7-tag" href="<?= url('news/category/' . $fp['category_slug']) ?>" data-spa-link><?= e($fp['category_name']) ?></a><?php else: ?><span class="n7-tag"><?= e(ucfirst((string)$fp['post_type'])) ?></span><?php endif; ?></div>
        <h2><a href="<?= $fhref ?>" data-spa-link><?= e($fp['title']) ?></a></h2>
        <p><?= e($fp['excerpt'] ?: mb_strimwidth(trim(strip_tags((string)$fp['content'])), 0, 220, '…')) ?></p>
        <span class="n7-meta"><?= site_line_icon('calendar') ?><time datetime="<?= e(date('Y-m-d', strtotime($fp['published_at']))) ?>"><?= e(date('d F Y', strtotime($fp['published_at']))) ?></time><i aria-hidden="true"></i><?= reading_minutes($fp['content'] ?? '') ?> min read</span>
        <a class="h7-btn h7-btn-primary" href="<?= $fhref ?>" data-spa-link><span>Read article</span><?= $arrow ?></a>
      </div>
    </article>
    <?php endif; ?>

    <?php if (!empty($posts)): ?>
    <div class="h7-card-grid n7-grid" data-reveal-group>
      <?php foreach ($posts as $p) echo $card($p); ?>
    </div>
    <?php elseif (empty($featured)): ?>
    <div class="h7-empty" data-reveal><span><?= site_line_icon('news') ?></span><div><b><?= $q !== '' ? 'No articles match your search' : 'No articles here yet' ?></b><p><?= $q !== '' ? 'Try a different word, or browse all news and blog posts.' : 'New announcements and stories appear here as soon as they are published.' ?></p></div><a class="h7-btn h7-btn-primary" href="<?= url('news') ?>" data-spa-link><span>All articles</span><?= $arrow ?></a></div>
    <?php endif; ?>

    <?php if (($pages ?? 1) > 1): ?>
    <nav class="n7-pager" aria-label="Pages">
      <?php if ($page > 1): ?><a href="<?= $link($base, ['type' => $type, 'q' => $q, 'page' => $page - 1]) ?>" data-spa-link rel="prev">Newer</a><?php endif; ?>
      <?php for ($i = 1; $i <= $pages; $i++): ?><a href="<?= $link($base, ['type' => $type, 'q' => $q, 'page' => $i]) ?>" data-spa-link class="<?= $i === $page ? 'is-active' : '' ?>" <?= $i === $page ? 'aria-current="page"' : '' ?>><?= $i ?></a><?php endfor; ?>
      <?php if ($page < $pages): ?><a href="<?= $link($base, ['type' => $type, 'q' => $q, 'page' => $page + 1]) ?>" data-spa-link rel="next">Older</a><?php endif; ?>
    </nav>
    <?php endif; ?>
  </div>
</section>
