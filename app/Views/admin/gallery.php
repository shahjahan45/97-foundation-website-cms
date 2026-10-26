<?php
$stats = $stats ?? [];
$total = (int)($stats['total'] ?? 0);
$active = (int)($stats['active'] ?? 0);
$back = admin_return_path();
$filtered = ($q ?? '') !== '' || ($state ?? '') !== '' || ($categoryFilter ?? '') !== '';
?>
<div class="page-actions">
  <div><h2>Gallery Manager</h2><p>Upload photos, write captions and choose what appears in the website gallery.</p></div>
  <div class="page-action-stack"><a class="outline-link" href="<?= url('gallery') ?>" target="_blank" rel="noopener">View gallery ↗</a><button class="admin-btn" type="button" data-scroll-to="#gallery-editor" data-gallery-reset><?= admin_icon('plus') ?><span>Add photo</span></button></div>
</div>

<div class="v7-kpis is-three">
  <a href="<?= url('admin/gallery') ?>"><span class="v7-kpi-icon green"><?= admin_icon('gallery') ?></span><div><small>All photos</small><b><?= $total ?></b></div></a>
  <a href="<?= url('admin/gallery?state=active') ?>"><span class="v7-kpi-icon green"><?= admin_icon('check') ?></span><div><small>Visible on website</small><b><?= $active ?></b></div></a>
  <a href="<?= url('admin/gallery?state=hidden') ?>"><span class="v7-kpi-icon gold"><?= admin_icon('key') ?></span><div><small>Hidden</small><b><?= $total - $active ?></b></div></a>
</div>

<div class="management-layout v7-media-layout">
  <section class="admin-panel sticky-form" id="gallery-editor">
    <div class="panel-head"><div><h3 data-media-title>Add photo</h3><span>JPG, PNG or WEBP · 1600 × 1200 px works best.</span></div><button class="outline-link mini" type="button" data-gallery-reset>Clear</button></div>
    <form method="post" enctype="multipart/form-data" action="<?= url('admin/gallery/save') ?>" class="admin-form" data-gallery-form>
      <?= csrf_field() ?><input type="hidden" name="id">
      <div class="full v7-photo-field">
        <div class="v7-photo-preview is-wide" data-media-preview><span>4:3</span><img alt="" hidden></div>
        <label class="v7-file"><span>Photo <em class="v7-opt" data-media-photo-note>required for new photos</em></span><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"><small class="helper">Leave empty when editing to keep the current photo.</small></label>
      </div>
      <label class="full"><span>Title *</span><input name="title" required maxlength="180" placeholder="e.g. School supplies drive, Thambala"></label>
      <label><span>Category</span><input name="category" maxlength="100" placeholder="e.g. Education" list="gallery-categories"><datalist id="gallery-categories"><?php foreach (($categories ?? []) as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist></label>
      <label><span>Display order</span><input type="number" name="sort_order" value="0"><small class="helper">Lower numbers show first.</small></label>
      <label class="full"><span>Caption</span><textarea name="caption" rows="3" maxlength="700" placeholder="One sentence about the moment."></textarea></label>
      <label class="toggle-line full"><input type="checkbox" name="is_active" value="1" checked><span>Show this photo on the website</span></label>
      <button class="admin-btn full" data-media-submit>Save photo</button>
    </form>
  </section>

  <section class="v7-list-col">
    <form class="v7-toolbar" method="get" action="<?= url('admin/gallery') ?>">
      <label class="v7-search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input type="search" name="q" value="<?= e($q ?? '') ?>" placeholder="Search title or caption" aria-label="Search photos"></label>
      <select name="state" aria-label="Visibility"><option value="">All photos</option><option value="active" <?= ($state ?? '') === 'active' ? 'selected' : '' ?>>Visible</option><option value="hidden" <?= ($state ?? '') === 'hidden' ? 'selected' : '' ?>>Hidden</option></select>
      <?php if (!empty($categories)): ?><select name="category" aria-label="Category"><option value="">All categories</option><?php foreach ($categories as $c): ?><option value="<?= e($c) ?>" <?= ($categoryFilter ?? '') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select><?php endif; ?>
      <button class="outline-link" type="submit">Filter</button>
      <?php if ($filtered): ?><a class="outline-link" href="<?= url('admin/gallery') ?>">Clear</a><?php endif; ?>
    </form>
    <div class="v7-media-grid">
      <?php foreach ($items as $g):
        $img = safe_image($g['image_path']);
        $payload = e(json_encode(['id' => $g['id'], 'title' => $g['title'], 'category' => $g['category'], 'sort_order' => $g['sort_order'], 'caption' => $g['caption'], 'is_active' => (int)$g['is_active'], 'image' => $img], JSON_HEX_APOS | JSON_HEX_QUOT)); ?>
      <article class="v7-media-card <?= (int)$g['is_active'] === 1 ? '' : 'is-off-site' ?>">
        <button type="button" class="v7-media-thumb" data-gallery-edit='<?= $payload ?>' aria-label="Edit <?= e($g['title']) ?>"><img src="<?= e($img) ?>" alt="" loading="lazy"><span class="v7-media-state"><?= (int)$g['is_active'] === 1 ? 'Visible' : 'Hidden' ?></span><span class="v7-media-order" title="Display order">#<?= (int)$g['sort_order'] ?></span></button>
        <div class="v7-media-body">
          <b title="<?= e($g['title']) ?>"><?= e($g['title']) ?></b>
          <small><?= e(($g['category'] ?? '') !== '' ? $g['category'] : 'No category') ?></small>
        </div>
        <div class="v7-media-actions">
          <button class="v7-icon-btn" type="button" data-gallery-edit='<?= $payload ?>' title="Edit" aria-label="Edit <?= e($g['title']) ?>"><?= admin_icon('edit') ?></button>
          <form method="post" action="<?= url('admin/gallery/toggle') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$g['id'] ?>"><input type="hidden" name="return" value="<?= e($back) ?>"><button class="v7-icon-btn" type="submit" title="<?= (int)$g['is_active'] === 1 ? 'Hide from website' : 'Show on website' ?>" aria-label="<?= (int)$g['is_active'] === 1 ? 'Hide' : 'Show' ?> <?= e($g['title']) ?>"><?= admin_icon((int)$g['is_active'] === 1 ? 'eye_off' : 'eye') ?></button></form>
          <form method="post" action="<?= url('admin/gallery/delete') ?>" data-confirm="Delete “<?= e($g['title']) ?>” permanently?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$g['id'] ?>"><button class="v7-icon-btn is-danger" type="submit" title="Delete" aria-label="Delete <?= e($g['title']) ?>"><?= admin_icon('trash') ?></button></form>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php if (empty($items)): ?><div class="admin-panel v7-empty-panel"><span><?= admin_icon('gallery') ?></span><b><?= $filtered ? 'No photos match these filters' : 'No photos yet' ?></b><p><?= $filtered ? 'Try another search or clear the filters.' : 'Add your first photo with the form on the left.' ?></p></div><?php endif; ?>
    <?= admin_pagination($pagination ?? []) ?>
  </section>
</div>
