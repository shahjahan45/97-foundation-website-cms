<?php $causes = $causes ?? []; $activeCount = count(array_filter($causes, static fn($c) => (int)$c['is_active'] === 1)); ?>
<div class="page-actions">
  <div><h2>Cause Areas</h2><p>The areas of support shown on the homepage and Causes page. Fundraising campaigns are managed separately.</p></div>
  <div class="page-action-stack"><a class="outline-link" href="<?= url('causes') ?>" target="_blank" rel="noopener">View Causes page ↗</a><button class="admin-btn" type="button" data-scroll-to="#cause-editor" data-cause-reset><?= admin_icon('plus') ?><span>Add cause</span></button></div>
</div>
<div class="management-layout v7-media-layout">
  <section class="admin-panel sticky-form" id="cause-editor">
    <div class="panel-head"><div><h3 data-media-title>Add cause</h3><span><?= $activeCount ?> of <?= count($causes) ?> causes are visible on the website.</span></div><button class="outline-link mini" type="button" data-cause-reset>Clear</button></div>
    <form method="post" enctype="multipart/form-data" action="<?= url('admin/causes/save') ?>" class="admin-form" data-cause-form>
      <?= csrf_field() ?><input type="hidden" name="id">
      <div class="full v7-photo-field">
        <div class="v7-photo-preview is-wide" data-media-preview><span>3:2</span><img alt="" hidden></div>
        <label class="v7-file"><span>Cause image</span><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"><small class="helper">1200 × 800 px works best. Leave empty to keep the current image.</small></label>
      </div>
      <label class="full"><span>Cause name *</span><input name="name" required maxlength="120" placeholder="e.g. Education"></label>
      <label><span>Display order</span><input type="number" name="sort_order" value="0"><small class="helper">Lower numbers show first.</small></label>
      <input type="hidden" name="icon" value="♡">
      <label class="full"><span>Description</span><textarea name="description" rows="4" maxlength="600" placeholder="One or two sentences about this area of support."></textarea></label>
      <label class="toggle-line full"><input type="checkbox" name="is_active" value="1" checked><span>Show this cause on the website</span></label>
      <button class="admin-btn full" data-media-submit>Save cause</button>
    </form>
  </section>
  <section class="v7-list-col">
    <div class="v7-media-grid is-wide">
      <?php foreach ($causes as $c):
        $img = $c['image_path'] ? safe_image($c['image_path'], 'images/demo/cause-education.jpg') : '';
        $payload = e(json_encode(['id' => $c['id'], 'name' => $c['name'], 'icon' => $c['icon'], 'sort_order' => $c['sort_order'], 'description' => $c['description'], 'is_active' => (int)$c['is_active'], 'image' => $img], JSON_HEX_APOS | JSON_HEX_QUOT)); ?>
      <article class="v7-media-card <?= (int)$c['is_active'] === 1 ? '' : 'is-off-site' ?>">
        <button type="button" class="v7-media-thumb" data-cause-edit='<?= $payload ?>' aria-label="Edit <?= e($c['name']) ?>"><?php if ($img): ?><img src="<?= e($img) ?>" alt="" loading="lazy"><?php else: ?><span class="v7-media-icon"><?= site_line_icon(home_cause_icon((string)$c['name'])) ?></span><?php endif; ?><span class="v7-media-state"><?= (int)$c['is_active'] === 1 ? 'Visible' : 'Hidden' ?></span><span class="v7-media-order" title="Display order">#<?= (int)$c['sort_order'] ?></span></button>
        <div class="v7-media-body"><b title="<?= e($c['name']) ?>"><?= e($c['name']) ?></b><small title="<?= e($c['description'] ?? '') ?>"><?= e(mb_strimwidth((string)($c['description'] ?? ''), 0, 70, '…') ?: 'No description') ?></small></div>
        <div class="v7-media-actions">
          <button class="v7-icon-btn" type="button" data-cause-edit='<?= $payload ?>' title="Edit" aria-label="Edit <?= e($c['name']) ?>"><?= admin_icon('edit') ?></button>
          <form method="post" action="<?= url('admin/causes/toggle') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="v7-icon-btn" type="submit" title="<?= (int)$c['is_active'] === 1 ? 'Hide from website' : 'Show on website' ?>" aria-label="<?= (int)$c['is_active'] === 1 ? 'Hide' : 'Show' ?> <?= e($c['name']) ?>"><?= admin_icon((int)$c['is_active'] === 1 ? 'eye_off' : 'eye') ?></button></form>
          <form method="post" action="<?= url('admin/causes/delete') ?>" data-confirm="Delete the “<?= e($c['name']) ?>” cause area?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="v7-icon-btn is-danger" type="submit" title="Delete" aria-label="Delete <?= e($c['name']) ?>"><?= admin_icon('trash') ?></button></form>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php if (!$causes): ?><div class="admin-panel v7-empty-panel"><span><?= admin_icon('heart') ?></span><b>No cause areas yet</b><p>Add the areas you support, such as Education or Healthcare.</p></div><?php endif; ?>
    <?= admin_pagination($pagination ?? []) ?>
  </section>
</div>
