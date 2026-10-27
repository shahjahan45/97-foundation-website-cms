<?php
$img = $page['image_path'] ?? null;
$rendered = article_render($page['body'] ?? '');
$isLegal = in_array($page['slug'] ?? '', ['privacy-policy', 'terms'], true);
?>
<?php if ($isLegal): ?>
<section class="legal7-hero">
  <div class="container">
    <nav class="sch7-crumbs" aria-label="Breadcrumb"><a href="<?= url('') ?>" data-spa-link>Home</a><span aria-hidden="true">/</span><span aria-current="page"><?= e($page['title']) ?></span></nav>
    <span class="sch7-eyebrow"><?= site_line_icon(($page['slug'] ?? '') === 'terms' ? 'scale' : 'lock') ?><?= e($page['eyebrow'] ?: 'LEGAL') ?></span>
    <h1><?= e($page['hero_title'] ?: $page['title']) ?></h1>
    <?php if (!empty($page['hero_subtitle'])): ?><p><?= e($page['hero_subtitle']) ?></p><?php endif; ?>
    <?php if (!empty($page['updated_at'])): ?><p class="legal7-updated"><?= site_line_icon('calendar') ?>Last updated <?= e(date('j F Y', strtotime((string)$page['updated_at']))) ?></p><?php endif; ?>
  </div>
</section>
<section class="legal7-body">
  <div class="container legal7-grid<?= $rendered['toc'] ? '' : ' no-toc' ?>">
    <?php if ($rendered['toc']): ?>
    <nav class="legal7-toc" aria-label="On this page">
      <b>On this page</b>
      <ol><?php foreach ($rendered['toc'] as $t): ?><li><a href="#<?= e($t['id']) ?>"><?= e($t['text']) ?></a></li><?php endforeach; ?></ol>
    </nav>
    <?php endif; ?>
    <article class="legal7-article prose"><?= $rendered['html'] ?>
      <aside class="legal7-contact"><?= site_line_icon('mail') ?><p>Questions about this page? <a href="<?= url('contact') ?>" data-spa-link>Contact 97 Foundation</a>.</p></aside>
    </article>
  </div>
</section>
<?php else: ?>
<section class="inner-hero"><div class="inner-hero-art" style="--page-image:url('<?=e(safe_image($img))?>')"></div><div class="inner-hero-shade"></div><div class="container inner-hero-content reveal"><span class="eyebrow"><?=e($page['eyebrow']?:'97 FOUNDATION')?></span><h1><?=e($page['hero_title']?:$page['title'])?></h1><p><?=e($page['hero_subtitle']??'')?></p></div></section>
<section class="section content-page"><div class="container"><article class="content-glass reveal"><div class="content-accent"></div><h2><?=e($page['title'])?></h2><div class="prose"><?= $rendered['html'] ?></div><?php if(!empty($page['answer_summary'])):?><aside class="answer-box"><b>Quick Answer</b><p><?=e($page['answer_summary'])?></p></aside><?php endif;?></article></div></section>
<?php endif; ?>
