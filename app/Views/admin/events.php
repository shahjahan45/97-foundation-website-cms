<?php $stats = $stats ?? []; $when = $when ?? ''; $statusLabels = ['draft' => 'Draft', 'published' => 'Published', 'completed' => 'Completed', 'cancelled' => 'Cancelled']; ?>
<div class="page-actions">
  <div><h2>Event Management</h2><p>Create events with dates, times, places and photos. Published events appear on the website automatically.</p></div>
  <div class="page-action-stack"><a class="outline-link" href="<?= url('events') ?>" target="_blank" rel="noopener">View Events page ↗</a><button class="admin-btn" type="button" data-scroll-to="#event-editor" data-event-reset><?= admin_icon('plus') ?><span>New event</span></button></div>
</div>

<div class="v7-kpis">
  <a href="<?= url('admin/events') ?>" class="<?= $when === '' ? 'is-current' : '' ?>"><span class="v7-kpi-icon green"><?= admin_icon('event') ?></span><div><small>All events</small><b><?= (int)($stats['total'] ?? 0) ?></b></div></a>
  <a href="<?= url('admin/events?when=upcoming') ?>" class="<?= $when === 'upcoming' ? 'is-current' : '' ?>"><span class="v7-kpi-icon blue"><?= admin_icon('clock') ?></span><div><small>Upcoming</small><b><?= (int)($stats['upcoming'] ?? 0) ?></b></div></a>
  <a href="<?= url('admin/events?when=past') ?>" class="<?= $when === 'past' ? 'is-current' : '' ?>"><span class="v7-kpi-icon green"><?= admin_icon('check') ?></span><div><small>Past</small><b><?= (int)($stats['past'] ?? 0) ?></b></div></a>
  <a href="<?= url('admin/events?when=draft') ?>" class="<?= $when === 'draft' ? 'is-current' : '' ?>"><span class="v7-kpi-icon gold"><?= admin_icon('edit') ?></span><div><small>Drafts</small><b><?= (int)($stats['drafts'] ?? 0) ?></b></div></a>
</div>

<div class="management-layout v7-media-layout">
  <section class="admin-panel sticky-form" id="event-editor">
    <div class="panel-head"><div><h3 data-media-title>New event</h3><span>Set the status to Published to show it on the website.</span></div><button class="outline-link mini" type="button" data-event-reset>Clear</button></div>
    <form method="post" enctype="multipart/form-data" action="<?= url('admin/events/save') ?>" class="admin-form" data-event-form>
      <?= csrf_field() ?><input type="hidden" name="id">
      <label class="full"><span>Event title *</span><input name="title" required maxlength="180" placeholder="e.g. School Supplies Drive"></label>
      <label><span>Date *</span><input type="date" name="event_date" required></label>
      <label><span>Status</span><select name="status"><?php foreach ($statusLabels as $k => $l): ?><option value="<?= $k ?>"><?= $l ?></option><?php endforeach; ?></select></label>
      <label><span>Start time</span><input type="time" name="start_time"></label>
      <label><span>End time</span><input type="time" name="end_time"></label>
      <label class="full"><span>Location</span><input name="location" maxlength="180" placeholder="e.g. Thambala Community Hall"></label>
      <label class="full"><span>Description</span><textarea name="description" rows="5" maxlength="30000" placeholder="What will happen, who it is for and what to bring."></textarea></label>
      <div class="full v7-photo-field">
        <div class="v7-photo-preview is-wide" data-media-preview><span>3:2</span><img alt="" hidden></div>
        <label class="v7-file"><span>Event photo</span><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"><small class="helper">1200 × 800 px works best. Leave empty to keep the current photo.</small></label>
      </div>
      <label class="toggle-line full"><input type="checkbox" name="is_active" value="1" checked><span>Show on the website when published</span></label>
      <button class="admin-btn full" data-media-submit>Save event</button>
    </form>
  </section>

  <section class="v7-list-col">
    <div class="v7-event-list">
      <?php foreach ($events as $ev):
        $ts = strtotime((string)$ev['event_date']);
        $isPast = $ts < strtotime('today');
        $img = $ev['image_path'] ? public_upload($ev['image_path']) : '';
        $payload = e(json_encode(['id' => $ev['id'], 'title' => $ev['title'], 'event_date' => $ev['event_date'], 'status' => $ev['status'], 'start_time' => $ev['start_time'] ? substr((string)$ev['start_time'], 0, 5) : '', 'end_time' => $ev['end_time'] ? substr((string)$ev['end_time'], 0, 5) : '', 'location' => $ev['location'], 'description' => $ev['description'], 'is_active' => (int)$ev['is_active'], 'image' => $img], JSON_HEX_APOS | JSON_HEX_QUOT));
        $time = trim(($ev['start_time'] ? date('g:i A', strtotime((string)$ev['start_time'])) : '') . ($ev['end_time'] ? ' – ' . date('g:i A', strtotime((string)$ev['end_time'])) : '')); ?>
      <article class="admin-panel v7-event-row <?= $isPast ? 'is-past' : '' ?>">
        <div class="v7-event-date"><b><?= e(date('d', $ts)) ?></b><span><?= e(date('M', $ts)) ?></span><small><?= e(date('Y', $ts)) ?></small></div>
        <?php if ($img): ?><img class="v7-event-thumb" src="<?= e($img) ?>" alt="" loading="lazy"><?php endif; ?>
        <div class="v7-event-copy">
          <div class="v7-post-tags"><span class="v7-state is-<?= $ev['status'] === 'published' ? 'published' : ($ev['status'] === 'draft' ? 'draft' : 'archived') ?>"><?= e($statusLabels[$ev['status']] ?? ucfirst((string)$ev['status'])) ?></span><?php if ($isPast): ?><span>Past</span><?php endif; ?><?php if ((int)$ev['is_active'] !== 1): ?><span>Hidden</span><?php endif; ?></div>
          <h3><?= e($ev['title']) ?></h3>
          <p><?php if ($time !== ''): ?><span><?= admin_icon('clock') ?><?= e($time) ?></span><?php endif; ?><span><?= admin_icon('pin') ?><?= e($ev['location'] ?: 'Location to be announced') ?></span></p>
        </div>
        <div class="v7-event-actions">
          <button class="outline-link mini" type="button" data-event-edit='<?= $payload ?>'>Edit</button>
          <form method="post" action="<?= url('admin/events/delete') ?>" data-confirm="Delete “<?= e($ev['title']) ?>”?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$ev['id'] ?>"><button class="danger-link mini" type="submit">Delete</button></form>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php if (empty($events)): ?>
    <div class="admin-panel v7-empty-panel">
      <span><?= admin_icon('event') ?></span>
      <b><?= $when === '' ? 'No events yet' : 'No ' . e($when === 'draft' ? 'draft' : $when) . ' events' ?></b>
      <p><?= $when === '' ? 'Create your first event with the form. Published events appear on the homepage and the Events page.' : 'Choose another filter above to see the rest.' ?></p>
      <?php if ($when === ''): ?><button class="admin-btn" type="button" data-scroll-to="#event-editor" data-event-reset><?= admin_icon('plus') ?><span>Create an event</span></button><?php endif; ?>
    </div>
    <?php endif; ?>
    <?= admin_pagination($pagination ?? []) ?>
  </section>
</div>
