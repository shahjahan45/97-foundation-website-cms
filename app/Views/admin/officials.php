<?php
$tab = $tab ?? 'current';
$counts = $counts ?? ['current' => 0, 'past' => 0, 'hidden' => 0];
$q = $q ?? '';
$year = (int)date('Y');
$initials = static function (string $name): string {
    $out = '';
    foreach (array_slice(preg_split('/\s+/', trim($name)) ?: [], 0, 2) as $p) $out .= mb_strtoupper(mb_substr($p, 0, 1));
    return $out ?: '97';
};
?>
<div class="page-actions">
  <div>
    <h2>Foundation Officials</h2>
    <p>Publish the current office bearers and keep a permanent record of past officials. These profiles power the <b>Members → Officials</b> and <b>Members → Past Officials</b> pages.</p>
  </div>
  <div class="page-action-stack">
    <a class="outline-link" href="<?= url($tab === 'past' ? 'members/past-officials' : 'members/officials') ?>" target="_blank" rel="noopener">View Public Page ↗</a>
  </div>
</div>

<div class="v7-stat-tabs" role="tablist" aria-label="Official status">
  <a class="<?= $tab === 'current' ? 'active' : '' ?>" href="<?= url('admin/officials?status=current') ?>" role="tab" aria-selected="<?= $tab === 'current' ? 'true' : 'false' ?>"><span class="v7-dot green"></span><b>Current Officials</b><em><?= (int)$counts['current'] ?></em></a>
  <a class="<?= $tab === 'past' ? 'active' : '' ?>" href="<?= url('admin/officials?status=past') ?>" role="tab" aria-selected="<?= $tab === 'past' ? 'true' : 'false' ?>"><span class="v7-dot gold"></span><b>Past Officials</b><em><?= (int)$counts['past'] ?></em></a>
  <span class="v7-stat-note"><?= (int)$counts['hidden'] ?> hidden from the website</span>
</div>

<div class="management-layout">
  <section class="admin-panel sticky-form">
    <div class="panel-head"><h3 data-official-editor-title>Add Official</h3><button class="outline-link mini" type="button" data-official-reset>New</button></div>
    <form method="post" enctype="multipart/form-data" action="<?= url('admin/officials/save') ?>" class="admin-form" data-official-form>
      <?= csrf_field() ?><input type="hidden" name="id">
      <div class="v7-photo-field full">
        <div class="v7-photo-preview" data-official-photo-preview><span><?= site_icon('family_restroom') ?></span><img alt="" hidden></div>
        <label class="v7-file"><span>Profile photo</span><input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp"><small class="helper">Portrait or square, at least 600 × 640 px.</small></label>
      </div>
      <label class="toggle-line full" data-official-remove-photo hidden><input type="checkbox" name="remove_photo" value="1"><span>Remove current photo</span></label>
      <label class="full"><span>Full name *</span><input name="full_name" required maxlength="160" placeholder="e.g. Mohamed Rizwan"></label>
      <label class="full"><span>Position *</span><input name="position" required maxlength="140" list="official-positions" placeholder="e.g. President"></label>
      <datalist id="official-positions"><option value="Founder"><option value="President"><option value="Vice President"><option value="Secretary"><option value="Assistant Secretary"><option value="Treasurer"><option value="Assistant Treasurer"><option value="Committee Member"><option value="Coordinator"><option value="Advisor"></datalist>
      <label><span>Status</span><select name="status"><option value="current" <?= $tab === 'current' ? 'selected' : '' ?>>Current official</option><option value="past" <?= $tab === 'past' ? 'selected' : '' ?>>Past official</option></select></label>
      <label><span>Display order</span><input type="number" name="sort_order" value="0" min="-999" max="999"></label>
      <label><span>Term start year</span><input type="number" name="term_start" min="1950" max="2100" placeholder="<?= $year ?>"></label>
      <label><span>Term end year</span><input type="number" name="term_end" min="1950" max="2100" placeholder="Still serving"></label>
      <label class="full"><span>Short bio</span><textarea name="bio" rows="4" maxlength="1200" placeholder="Responsibilities, background and contribution to the Foundation."></textarea></label>
      <label><span>Email</span><input type="email" name="email" maxlength="190"></label>
      <label><span>Phone</span><input name="phone" maxlength="60"></label>
      <label class="toggle-line full"><input type="checkbox" name="show_contact" value="1"><span>Show email / phone on the public Officials page</span></label>
      <label class="toggle-line full"><input type="checkbox" name="is_active" value="1" checked><span>Visible on the website</span></label>
      <button class="admin-btn full">Save Official</button>
    </form>
  </section>

  <section class="v7-list-col">
    <form class="v7-toolbar" method="get" action="<?= url('admin/officials') ?>">
      <input type="hidden" name="status" value="<?= e($tab) ?>">
      <label class="v7-search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input type="search" name="q" value="<?= e($q) ?>" placeholder="Search by name or position" aria-label="Search officials"></label>
      <button class="outline-link" type="submit">Search</button>
      <?php if ($q !== ''): ?><a class="outline-link" href="<?= url('admin/officials?status=' . $tab) ?>">Clear</a><?php endif; ?>
    </form>
    <div class="v7-official-list">
      <?php foreach ($officials as $o):
        $payload = e(json_encode(['id' => $o['id'], 'full_name' => $o['full_name'], 'position' => $o['position'], 'status' => $o['status'], 'sort_order' => $o['sort_order'], 'term_start' => $o['term_start'], 'term_end' => $o['term_end'], 'bio' => $o['bio'], 'email' => $o['email'], 'phone' => $o['phone'], 'show_contact' => (int)$o['show_contact'], 'is_active' => (int)$o['is_active'], 'photo_url' => $o['photo_path'] ? public_upload($o['photo_path']) : ''], JSON_HEX_APOS | JSON_HEX_QUOT));
        $term = \App\Core\Officials::termLabel($o);
      ?>
      <article class="v7-official">
        <div class="v7-official-photo"><?php if ($o['photo_path']): ?><img src="<?= e(public_upload($o['photo_path'])) ?>" alt="" loading="lazy"><?php else: ?><span><?= e($initials((string)$o['full_name'])) ?></span><?php endif; ?></div>
        <div class="v7-official-copy">
          <div class="v7-badges"><span class="status-badge <?= $o['status'] === 'current' ? 'active' : 'pending' ?>"><?= $o['status'] === 'current' ? 'Current' : 'Past' ?></span><?php if (!(int)$o['is_active']): ?><span class="status-badge inactive">Hidden</span><?php endif; ?><?php if ($term !== ''): ?><span class="v7-term"><?= e($term) ?></span><?php endif; ?></div>
          <h3><?= e($o['full_name']) ?></h3>
          <p><?= e($o['position']) ?></p>
        </div>
        <div class="v7-official-actions">
          <button class="outline-link" type="button" data-official-edit='<?= $payload ?>'>Edit</button>
          <form method="post" action="<?= url('admin/officials/status') ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$o['id'] ?>"><button class="outline-link" type="submit"><?= $o['status'] === 'current' ? 'Move to Past' : 'Restore to Current' ?></button></form>
          <form method="post" action="<?= url('admin/officials/delete') ?>" data-confirm="Remove <?= e($o['full_name']) ?> permanently?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$o['id'] ?>"><button class="danger-link" type="submit">Delete</button></form>
        </div>
      </article>
      <?php endforeach; ?>
      <?php if (!$officials): ?>
      <div class="admin-panel v7-empty">
        <span><?= site_icon('handshake') ?></span>
        <h3><?= $q !== '' ? 'No officials match your search' : ($tab === 'past' ? 'No past officials recorded yet' : 'No current officials yet') ?></h3>
        <p><?= $tab === 'past' ? 'Add previous office bearers with their service years, or use “Move to Past” on a current official when their term ends.' : 'Use the form to add the President, Secretary, Treasurer and other office bearers.' ?></p>
      </div>
      <?php endif; ?>
    </div>
    <?= admin_pagination($pagination ?? []) ?>
  </section>
</div>
