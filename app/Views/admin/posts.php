<?php
$stats = $stats ?? [];
$categories = $categories ?? [];
$catMap = [];
foreach ($categories as $c) $catMap[(int)$c['id']] = $c;
$now = time();
$stateOf = static function (array $p) use ($now): string {
    if ($p['status'] === 'published') return strtotime((string)$p['published_at']) > $now ? 'scheduled' : 'published';
    return (string)$p['status'];
};
$stateLabels = ['published' => 'Published', 'scheduled' => 'Scheduled', 'draft' => 'Draft', 'archived' => 'Archived'];
$filtered = ($q ?? '') !== '' || ($type ?? '') !== '' || ($state ?? '') !== '' || (int)($categoryFilter ?? 0) > 0;
?>
<div class="page-actions">
  <div>
    <h2>News &amp; Blog</h2>
    <p>Write, schedule and publish articles. Every published article gets its own web address, share preview and search listing.</p>
  </div>
  <div class="page-action-stack"><button class="admin-btn" type="button" data-post7-new><?= admin_icon('plus') ?><span>New article</span></button></div>
</div>

<div class="v7-kpis">
  <a href="<?= url('admin/posts?state=published') ?>"><span class="v7-kpi-icon green"><?= admin_icon('check') ?></span><div><small>Published</small><b><?= (int)($stats['published'] ?? 0) ?></b></div></a>
  <a href="<?= url('admin/posts?state=scheduled') ?>"><span class="v7-kpi-icon blue"><?= admin_icon('event') ?></span><div><small>Scheduled</small><b><?= (int)($stats['scheduled'] ?? 0) ?></b></div></a>
  <a href="<?= url('admin/posts?state=draft') ?>"><span class="v7-kpi-icon gold"><?= admin_icon('edit') ?></span><div><small>Drafts</small><b><?= (int)($stats['drafts'] ?? 0) ?></b></div></a>
  <a href="#categories"><span class="v7-kpi-icon green"><?= admin_icon('content') ?></span><div><small>Categories</small><b><?= count($categories) ?></b></div></a>
</div>

<div class="management-layout v7-posts-layout">
  <section class="admin-panel sticky-form" id="post-editor">
    <div class="panel-head"><div><h3 data-post7-title>New article</h3><span data-post7-sub>Draft first, publish when it is ready.</span></div><button class="outline-link mini" type="button" data-post7-reset>Clear</button></div>
    <form method="post" enctype="multipart/form-data" action="<?= url('admin/posts/save') ?>" class="admin-form" data-post7-form data-base-url="<?= e(absolute_url('news/')) ?>">
      <?= csrf_field() ?><input type="hidden" name="id">
      <label><span>Type</span><select name="post_type"><option value="news">News</option><option value="blog">Blog</option></select></label>
      <label><span>Category</span><select name="category_id"><option value="">No category</option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?><?= (int)$c['is_active'] === 1 ? '' : ' (hidden)' ?></option><?php endforeach; ?></select></label>
      <label class="full"><span>Title *</span><input name="title" required maxlength="200" placeholder="e.g. School supplies delivered to 120 students in Thambala" data-post7-titlefield></label>
      <label class="full"><span>Web address</span><div class="v7-slug"><span data-post7-base><?= e(preg_replace('~^https?://~', '', absolute_url('news/'))) ?></span><input name="slug" maxlength="180" pattern="[a-z0-9-]*" placeholder="created from the title" data-post7-slug autocapitalize="off" spellcheck="false"></div><small class="helper">Lower-case words and dashes. Changing it on a published article breaks links that were already shared.</small></label>
      <label class="full"><span>Summary <em class="v7-opt">shown on cards and as the article intro</em></span><textarea name="excerpt" rows="3" maxlength="800" data-post7-count="300"></textarea></label>
      <div class="full v7-editor">
        <div class="v7-editor-bar" role="toolbar" aria-label="Formatting">
          <button type="button" data-fmt="h2" title="Section heading">H2</button>
          <button type="button" data-fmt="h3" title="Sub-heading">H3</button>
          <button type="button" data-fmt="bold" title="Bold"><b>B</b></button>
          <button type="button" data-fmt="list" title="Bulleted list">• List</button>
          <button type="button" data-fmt="quote" title="Quote">“ Quote</button>
          <button type="button" data-fmt="link" title="Link">Link</button>
        </div>
        <label><span class="sr-only">Article content</span><textarea name="content" rows="14" maxlength="50000" data-post7-content placeholder="Write the article here.&#10;&#10;## Use a heading like this for each section&#10;&#10;- Use dashes for a list&#10;&#10;Leave an empty line between paragraphs."></textarea></label>
        <small class="helper">Use <code>## Heading</code> for sections, <code>**bold**</code>, <code>- item</code> for lists and <code>[text](https://link)</code> for links. Headings also build the article’s table of contents.</small>
      </div>
      <label><span>Status</span><select name="status" data-post7-status><option value="draft">Draft</option><option value="published">Published</option><option value="archived">Archived</option></select></label>
      <label><span>Publish date &amp; time</span><input type="datetime-local" name="published_at"><small class="helper">A future time schedules the article.</small></label>
      <div class="full v7-photo-field">
        <div class="v7-photo-preview" data-post7-preview><span>16:9</span><img alt="" hidden></div>
        <label class="v7-file"><span>Featured image</span><input type="file" name="featured_image" accept=".jpg,.jpeg,.png,.webp"><small class="helper">1200 × 675 px works best. Also used for social media previews.</small></label>
      </div>
      <label class="toggle-line full" data-post7-remove hidden><input type="checkbox" name="remove_image" value="1"><span>Remove current image</span></label>
      <label class="toggle-line full"><input type="checkbox" name="is_featured" value="1"><span>Feature this article (shown first on the News &amp; Blog page)</span></label>
      <div class="form-divider full"><b>Search &amp; social preview</b></div>
      <label class="full"><span>SEO title <em class="v7-opt" data-post7-counter="meta_title">0 / 60</em></span><input name="meta_title" maxlength="240" placeholder="Defaults to the article title"></label>
      <label class="full"><span>Meta description <em class="v7-opt" data-post7-counter="meta_description">0 / 160</em></span><textarea name="meta_description" rows="3" maxlength="500" placeholder="One or two sentences that answer what the article is about."></textarea></label>
      <div class="full v7-serp" aria-label="Search result preview"><small data-serp-url><?= e(preg_replace('~^https?://~', '', absolute_url('news/'))) ?>…</small><b data-serp-title>Article title | 97 Foundation</b><p data-serp-desc>The summary or meta description appears here.</p></div>
      <button class="admin-btn full" data-post7-submit>Save article</button>
    </form>
  </section>

  <section class="v7-list-col">
    <form class="v7-toolbar" method="get" action="<?= url('admin/posts') ?>">
      <label class="v7-search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input type="search" name="q" value="<?= e($q ?? '') ?>" placeholder="Search title or address" aria-label="Search articles"></label>
      <select name="state" aria-label="Status"><option value="">All statuses</option><?php foreach ($stateLabels as $k => $l): ?><option value="<?= $k ?>" <?= ($state ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
      <select name="type" aria-label="Type"><option value="">News &amp; blog</option><option value="news" <?= ($type ?? '') === 'news' ? 'selected' : '' ?>>News</option><option value="blog" <?= ($type ?? '') === 'blog' ? 'selected' : '' ?>>Blog</option></select>
      <?php if ($categories): ?><select name="category" aria-label="Category"><option value="">All categories</option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($categoryFilter ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select><?php endif; ?>
      <button class="outline-link" type="submit">Filter</button>
      <?php if ($filtered): ?><a class="outline-link" href="<?= url('admin/posts') ?>">Clear</a><?php endif; ?>
    </form>

    <div class="v7-post-list">
      <?php foreach (($posts ?? []) as $p):
        $st = $stateOf($p);
        $dt = $p['published_at'] ? date('Y-m-d\TH:i', strtotime($p['published_at'])) : '';
        $publicUrl = absolute_url('news/' . $p['slug']);
        $payload = e(json_encode(['id' => $p['id'], 'post_type' => $p['post_type'], 'category_id' => $p['category_id'] ?? '', 'title' => $p['title'], 'slug' => $p['slug'], 'excerpt' => $p['excerpt'], 'content' => $p['content'], 'status' => $p['status'], 'is_featured' => (int)$p['is_featured'], 'published_at' => $dt, 'meta_title' => $p['meta_title'], 'meta_description' => $p['meta_description'], 'image' => $p['featured_image'] ? public_upload($p['featured_image']) : ''], JSON_HEX_APOS | JSON_HEX_QUOT)); ?>
      <article class="admin-panel v7-post-card is-<?= e($st) ?>">
        <div class="v7-post-thumb"><?php if ($p['featured_image']): ?><img src="<?= e(public_upload($p['featured_image'])) ?>" alt="" loading="lazy"><?php else: ?><span>97</span><?php endif; ?></div>
        <div class="v7-post-copy">
          <div class="v7-post-tags"><span class="v7-state is-<?= e($st) ?>"><?= e($stateLabels[$st] ?? ucfirst($st)) ?></span><span><?= e(ucfirst((string)$p['post_type'])) ?></span><?php if (!empty($p['category_name'])): ?><span><?= e($p['category_name']) ?></span><?php endif; ?><?php if ((int)$p['is_featured'] === 1): ?><span class="is-featured">Featured</span><?php endif; ?></div>
          <h3><?= e($p['title']) ?></h3>
          <p><?= e(mb_strimwidth((string)($p['excerpt'] ?: strip_tags((string)$p['content'])), 0, 140, '…')) ?></p>
          <small><?= $p['published_at'] ? e(($st === 'scheduled' ? 'Goes live ' : ($st === 'published' ? 'Published ' : 'Dated ')) . date('d M Y, H:i', strtotime($p['published_at']))) : 'No publish date yet' ?> · <span class="v7-post-url"><?= e(preg_replace('~^https?://~', '', $publicUrl)) ?></span></small>
        </div>
        <div class="v7-post-actions">
          <button class="outline-link mini" type="button" data-post7-edit='<?= $payload ?>'>Edit</button>
          <?php if ($st === 'published'): ?>
            <a class="outline-link mini" href="<?= e($publicUrl) ?>" target="_blank" rel="noopener">View</a>
            <button class="outline-link mini" type="button" data-copy-link="<?= e($publicUrl) ?>">Copy link</button>
          <?php endif; ?>
          <form method="post" action="<?= url('admin/posts/status') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><?php if ($p['status'] === 'published'): ?><input type="hidden" name="to" value="draft"><button class="outline-link mini" type="submit">Unpublish</button><?php else: ?><input type="hidden" name="to" value="published"><button class="outline-link mini is-primary" type="submit">Publish now</button><?php endif; ?></form>
          <form method="post" action="<?= url('admin/posts/delete') ?>" data-confirm="Delete “<?= e($p['title']) ?>” permanently? Its web address will stop working."><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$p['id'] ?>"><button class="danger-link mini" type="submit">Delete</button></form>
        </div>
      </article>
      <?php endforeach; ?>
      <?php if (empty($posts)): ?><div class="admin-panel empty-card"><?= $filtered ? 'No articles match these filters.' : 'No articles yet. Write your first one with the editor.' ?></div><?php endif; ?>
    </div>
    <?= admin_pagination($pagination ?? []) ?>

    <section class="admin-panel v7-categories" id="categories">
      <div class="panel-head"><div><h3>Categories</h3><span>Group articles by topic. Each category has its own page and web address.</span></div></div>
      <?php if (!$categories && !\App\Core\Upgrade72::hasTable('post_categories')): ?>
        <p class="muted">Categories become available after the V7.2 database update. Reload this page once; it applies automatically.</p>
      <?php else: ?>
      <div class="v7-cat-list">
        <?php foreach ($categories as $c): ?>
        <form method="post" action="<?= url('admin/posts/categories/save') ?>" class="v7-cat-row">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
          <label><span class="sr-only">Name</span><input name="name" value="<?= e($c['name']) ?>" required maxlength="120"></label>
          <label><span class="sr-only">Address</span><input name="slug" value="<?= e($c['slug']) ?>" maxlength="140" pattern="[a-z0-9-]*" title="Lower-case words and dashes"></label>
          <label class="v7-cat-active"><input type="checkbox" name="is_active" value="1" <?= (int)$c['is_active'] === 1 ? 'checked' : '' ?>><span>Visible</span></label>
          <small><?= (int)$c['posts'] ?> article<?= (int)$c['posts'] === 1 ? '' : 's' ?></small>
          <button class="outline-link mini" type="submit">Save</button>
          <button class="danger-link mini" type="submit" formaction="<?= url('admin/posts/categories/delete') ?>" data-confirm-click="Delete the “<?= e($c['name']) ?>” category? Its articles are kept.">Delete</button>
        </form>
        <?php endforeach; ?>
      </div>
      <form method="post" action="<?= url('admin/posts/categories/save') ?>" class="v7-cat-row is-new">
        <?= csrf_field() ?><input type="hidden" name="id" value="0">
        <label><span class="sr-only">New category name</span><input name="name" required maxlength="120" placeholder="New category name"></label>
        <label><span class="sr-only">Address</span><input name="slug" maxlength="140" pattern="[a-z0-9-]*" placeholder="address (optional)"></label>
        <label class="v7-cat-active"><span class="sr-only">Description</span><input name="description" maxlength="400" placeholder="Short description (optional)"></label>
        <button class="admin-btn" type="submit"><?= admin_icon('plus') ?><span>Add</span></button>
      </form>
      <?php endif; ?>
    </section>
  </section>
</div>
