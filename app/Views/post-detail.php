<?php
$p = $post;
$article = $article ?? article_render($p['content'] ?? '');
$related = $related ?? [];
$url = absolute_url('news/' . $p['slug']);
$arrow = site_line_icon('arrow');
$minutes = reading_minutes($p['content'] ?? '');
$published = strtotime((string)$p['published_at']);
$updated = !empty($p['updated_at']) ? strtotime((string)$p['updated_at']) : $published;
$summary = trim((string)($p['excerpt'] ?? ''));
?>
<article class="a7">
  <header class="a7-head">
    <div class="container a7-head-inner">
      <nav class="crumb7 is-dark" aria-label="Breadcrumb"><a href="<?= url('') ?>" data-spa-link>Home</a><span aria-hidden="true">/</span><a href="<?= url('news') ?>" data-spa-link>News &amp; Blog</a><?php if (!empty($p['category_slug'])): ?><span aria-hidden="true">/</span><a href="<?= url('news/category/' . $p['category_slug']) ?>" data-spa-link><?= e($p['category_name']) ?></a><?php endif; ?></nav>
      <div class="n7-tags"><?php if (!empty($p['category_slug'])): ?><a class="n7-tag is-strong" href="<?= url('news/category/' . $p['category_slug']) ?>" data-spa-link><?= e($p['category_name']) ?></a><?php endif; ?><span class="n7-tag"><?= e(ucfirst((string)$p['post_type'])) ?></span></div>
      <h1><?= e($p['title']) ?></h1>
      <?php if ($summary !== ''): ?><p class="a7-lead"><?= e($summary) ?></p><?php endif; ?>
      <div class="a7-meta">
        <span class="a7-author"><span class="a7-avatar" aria-hidden="true"><?= site_line_icon('heart') ?></span><span><b><span>97 Foundation</span></b><small>Published <time datetime="<?= e(date('c', $published)) ?>"><?= e(date('d F Y', $published)) ?></time><?php if ($updated - $published > 86400): ?> · Updated <time datetime="<?= e(date('c', $updated)) ?>"><?= e(date('d F Y', $updated)) ?></time><?php endif; ?></small></span></span>
        <span class="a7-read"><?= site_line_icon('clock') ?><?= $minutes ?> min read</span>
      </div>
    </div>
  </header>

  <?php if ($p['featured_image']): ?>
  <figure class="container a7-figure"><img src="<?= e(public_upload($p['featured_image'])) ?>" alt="<?= e($p['title']) ?>" width="1200" height="675" fetchpriority="high"></figure>
  <?php endif; ?>

  <div class="container a7-layout">
    <div class="a7-body">
      <div class="a7-prose"><?= $article['html'] ?: '<p>' . e($summary) . '</p>' ?></div>
      <footer class="a7-foot">
        <?= share_bar($url, $p['title'], $summary, 'article', 'is-boxed') ?>
        <div class="a7-support">
          <div><b>Support stories like this</b><p>Your donation and time help 97 Foundation reach more families in Thambala and Polonnaruwa.</p></div>
          <div class="a7-support-actions"><a class="h7-btn h7-btn-primary" href="<?= url('donate') ?>" data-spa-link><?= site_line_icon('heart') ?><span>Donate</span></a><a class="h7-btn a7-btn-soft" href="<?= url('volunteer') ?>" data-spa-link><span>Volunteer</span><?= $arrow ?></a></div>
        </div>
      </footer>
    </div>
    <aside class="a7-aside" aria-label="Article tools">
      <div class="a7-sticky">
        <?php if (count($article['toc']) >= 2): ?>
        <nav class="a7-toc" aria-label="On this page"><b><?= site_line_icon('list') ?>On this page</b><ol><?php foreach ($article['toc'] as $t): ?><li><a href="#<?= e($t['id']) ?>" data-no-spa><?= e($t['text']) ?></a></li><?php endforeach; ?></ol></nav>
        <?php endif; ?>
        <?= share_bar($url, $p['title'], $summary, 'article', 'is-stacked') ?>
        <a class="a7-back" href="<?= url('news') ?>" data-spa-link><?= site_line_icon('arrow', 'is-flip') ?><span>All news &amp; blog</span></a>
      </div>
    </aside>
  </div>
</article>

<?php if ($related): ?>
<section class="h7-sec is-mist a7-related" aria-labelledby="related-title">
  <div class="container">
    <header class="h7-head"><div class="h7-head-main"><p class="h7-eyebrow">Keep reading</p><h2 class="h7-h2" id="related-title">Related articles</h2></div><div class="h7-head-side"><a class="h7-link" href="<?= url('news') ?>" data-spa-link><span>All articles</span><?= $arrow ?></a></div></header>
    <div class="h7-card-grid" data-reveal-group>
      <?php foreach ($related as $r): $href = post_url($r); ?>
      <article class="h7-card h7-post" data-reveal>
        <a class="h7-card-media" href="<?= $href ?>" data-spa-link tabindex="-1" aria-hidden="true"><?php if ($r['featured_image']): ?><img src="<?= e(public_upload($r['featured_image'])) ?>" alt="" width="800" height="500" loading="lazy" decoding="async"><?php else: ?><span class="h7-post-mark">97</span><?php endif; ?><span class="h7-chip is-plain"><?= e(!empty($r['category_name']) ? $r['category_name'] : ucfirst((string)$r['post_type'])) ?></span></a>
        <div class="h7-card-body"><span class="n7-meta"><?= site_line_icon('calendar') ?><time datetime="<?= e(date('Y-m-d', strtotime($r['published_at']))) ?>"><?= e(date('d M Y', strtotime($r['published_at']))) ?></time><i aria-hidden="true"></i><?= reading_minutes($r['content'] ?? '') ?> min read</span><h3><a href="<?= $href ?>" data-spa-link><?= e($r['title']) ?></a></h3><p><?= e($r['excerpt'] ?: mb_strimwidth(trim(strip_tags((string)$r['content'])), 0, 130, '…')) ?></p><a class="h7-link" href="<?= $href ?>" data-spa-link><span>Read article</span><?= $arrow ?></a></div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
