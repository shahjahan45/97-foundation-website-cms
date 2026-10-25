<?php
$stats = $stats ?? [];
$roles = $roles ?? [];
$statuses = $statuses ?? [];
$me = (int)($user['id'] ?? 0);
$initials = static function (string $name): string {
    $out = '';
    foreach (array_slice(preg_split('/\s+/', trim($name)) ?: [], 0, 2) as $p) $out .= mb_strtoupper(mb_substr($p, 0, 1));
    return $out ?: 'A';
};
?>
<div class="page-actions">
  <div>
    <h2>Administrators</h2>
    <p>Create administrator accounts, assign the right role and control who can sign in to the administration workspace. Only Super Admins can see this page.</p>
  </div>
  <div class="page-action-stack"><button class="admin-btn" type="button" data-admin-new><?= admin_icon('plus') ?><span>Add Administrator</span></button></div>
</div>

<div class="v7-kpis">
  <article><span class="v7-kpi-icon green"><?= admin_icon('people') ?></span><div><small>Total administrators</small><b><?= (int)($stats['total'] ?? 0) ?></b></div></article>
  <article><span class="v7-kpi-icon green"><?= admin_icon('check') ?></span><div><small>Active</small><b><?= (int)($stats['active'] ?? 0) ?></b></div></article>
  <article><span class="v7-kpi-icon gold"><?= admin_icon('key') ?></span><div><small>Suspended / inactive</small><b><?= (int)($stats['blocked'] ?? 0) ?></b></div></article>
  <article><span class="v7-kpi-icon blue"><?= admin_icon('shield') ?></span><div><small>Using two-factor sign-in</small><b><?= (int)($stats['twofa'] ?? 0) ?></b></div></article>
</div>

<div class="management-layout v7-admins-layout">
  <section class="admin-panel sticky-form" id="admin-editor">
    <div class="panel-head"><div><h3 data-admin-editor-title>Add Administrator</h3><span data-admin-editor-sub>The new administrator signs in with this email and password.</span></div><button class="outline-link mini" type="button" data-admin-reset>Clear</button></div>
    <form method="post" enctype="multipart/form-data" action="<?= url('admin/administrators/save') ?>" class="admin-form" data-admin-form autocomplete="off">
      <?= csrf_field() ?><input type="hidden" name="id">
      <div class="v7-photo-field full">
        <div class="v7-photo-preview is-round" data-admin-photo-preview><span data-admin-initials>A</span><img alt="" hidden></div>
        <label class="v7-file"><span>Profile photo <em>optional</em></span><input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp"><small class="helper">Square image, at least 400 × 400 px.</small></label>
      </div>
      <label class="toggle-line full" data-admin-remove-photo hidden><input type="checkbox" name="remove_photo" value="1"><span>Remove current photo</span></label>
      <label class="full"><span>Full name *</span><input name="name" required maxlength="120" placeholder="e.g. Sajahan Mansoor" data-admin-name></label>
      <label><span>Email (sign-in) *</span><input type="email" name="email" required maxlength="190" placeholder="name@97foundation.org" autocomplete="off"></label>
      <label><span>Username <em class="v7-opt">optional</em></span><input name="username" maxlength="40" placeholder="e.g. sajahan" autocomplete="off"></label>
      <label><span>Role *</span><select name="role" data-admin-role><?php foreach ($roles as $key => [$label, $desc]): ?><option value="<?= e($key) ?>" data-desc="<?= e($desc) ?>" <?= $key === 'administrator' ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
      <label><span>Status</span><select name="status"><?php foreach ($statuses as $key => $label): ?><option value="<?= e($key) ?>"><?= e($label) ?></option><?php endforeach; ?></select></label>
      <p class="v7-role-note full" data-admin-role-desc><?= e($roles['administrator'][1] ?? '') ?></p>
      <div class="v7-password-block full">
        <div class="v7-password-head"><b data-admin-password-label>Password *</b><button type="button" class="outline-link mini" data-admin-generate><?= admin_icon('key') ?><span>Generate strong password</span></button></div>
        <label><span class="sr-only">Password</span><div class="v7-password-input"><input type="password" name="password" minlength="12" maxlength="128" autocomplete="new-password" data-admin-password placeholder="At least 12 characters"><button type="button" class="v7-eye" data-admin-toggle-password aria-label="Show password">Show</button><button type="button" class="v7-eye" data-admin-copy-password aria-label="Copy password">Copy</button></div></label>
        <label><span class="sr-only">Confirm password</span><input type="password" name="password_confirmation" maxlength="128" autocomplete="new-password" data-admin-password-confirm placeholder="Confirm password"></label>
        <div class="v7-strength" data-admin-strength><i></i><i></i><i></i><i></i><span>Use 12+ characters with upper and lower case, a number and a symbol.</span></div>
      </div>
      <button class="admin-btn full" data-admin-submit>Create Administrator</button>
    </form>
  </section>

  <section class="v7-list-col">
    <form class="v7-toolbar" method="get" action="<?= url('admin/administrators') ?>">
      <label class="v7-search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input type="search" name="q" value="<?= e($q ?? '') ?>" placeholder="Search name, email or username" aria-label="Search administrators"></label>
      <select name="role" aria-label="Filter by role"><option value="">All roles</option><?php foreach ($roles as $key => [$label]): ?><option value="<?= e($key) ?>" <?= ($roleFilter ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
      <button class="outline-link" type="submit">Filter</button>
      <?php if (($q ?? '') !== '' || ($roleFilter ?? '') !== ''): ?><a class="outline-link" href="<?= url('admin/administrators') ?>">Clear</a><?php endif; ?>
    </form>
    <div class="table-wrap v7-admin-table">
      <table>
        <thead><tr><th>Administrator</th><th>Role &amp; status</th><th class="v7-th-actions">Actions</th></tr></thead>
        <tbody>
        <?php foreach (($admins ?? []) as $a):
          $isMe = (int)$a['id'] === $me;
          $payload = e(json_encode(['id' => $a['id'], 'name' => $a['name'], 'email' => $a['email'], 'username' => $a['username'], 'role' => $a['role'], 'status' => $a['status'], 'photo_url' => $a['profile_photo'] ? public_upload($a['profile_photo']) : '', 'is_me' => $isMe ? 1 : 0], JSON_HEX_APOS | JSON_HEX_QUOT));
        ?>
          <tr class="<?= $isMe ? 'is-me' : '' ?>">
            <td><div class="v7-person"><span class="v7-avatar"><?php if ($a['profile_photo']): ?><img src="<?= e(public_upload($a['profile_photo'])) ?>" alt=""><?php else: ?><?= e($initials((string)$a['name'])) ?><?php endif; ?></span><div><b><?= e($a['name']) ?><?php if ($isMe): ?> <em class="v7-you">You</em><?php endif; ?></b><small><?= e($a['email']) ?><?= $a['username'] ? ' · @' . e($a['username']) : '' ?></small><small class="v7-last">Last sign-in: <?= $a['last_login_at'] ? e(date('d M Y, H:i', strtotime($a['last_login_at']))) : 'never' ?></small></div></div></td>
            <td><div class="v7-role-cell"><span class="v7-role v7-role-<?= e($a['role']) ?>"><?= e($roles[$a['role']][0] ?? $a['role']) ?></span><span class="v7-role-meta"><span class="status-badge <?= e($a['status'] === 'active' ? 'active' : ($a['status'] === 'suspended' ? 'suspended' : 'inactive')) ?>"><?= e($statuses[$a['status']] ?? ucfirst($a['status'])) ?></span><?php if ((int)$a['two_factor_enabled'] === 1): ?><span class="v7-2fa" title="Two-factor sign-in enabled"><?= admin_icon('shield') ?>2FA</span><?php endif; ?></span></div></td>
            <td><div class="v7-row-actions">
              <button class="outline-link mini" type="button" data-admin-edit='<?= $payload ?>'>Edit</button>
              <?php if (!$isMe): ?>
                <form method="post" action="<?= url('admin/administrators/status') ?>" data-confirm="<?= $a['status'] === 'active' ? 'Suspend ' . e($a['name']) . '? They will be signed out and unable to sign in.' : 'Allow ' . e($a['name']) . ' to sign in again?' ?>"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="outline-link mini" type="submit"><?= $a['status'] === 'active' ? 'Suspend' : 'Activate' ?></button></form>
                <form method="post" action="<?= url('admin/administrators/delete') ?>" data-confirm="Delete <?= e($a['name']) ?>’s administrator account permanently?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>"><button class="danger-link mini" type="submit">Delete</button></form>
              <?php endif; ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($admins)): ?><tr><td colspan="3" class="v7-empty-row">No administrators match your search.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>
    <?= admin_pagination($pagination ?? []) ?>

    <section class="admin-panel v7-roles-guide">
      <div class="panel-head"><div><h3>Role guide</h3><span>What each role can manage. Give people the smallest role they need.</span></div></div>
      <ul><?php foreach ($roles as $key => [$label, $desc]): ?><li><span class="v7-role v7-role-<?= e($key) ?>"><?= e($label) ?></span><p><?= e($desc) ?></p></li><?php endforeach; ?></ul>
    </section>
  </section>
</div>
