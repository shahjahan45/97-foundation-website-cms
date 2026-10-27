<?php
use App\Core\Scholarship;

$f = $filters ?? [];
$stats = $stats ?? [];
$byPurpose = $byPurpose ?? [];
$hasFilter = ($f['q'] ?? '') !== '' || ($f['status'] ?? '') !== '' || ($f['purpose'] ?? '') !== '' || ($f['level'] ?? '') !== '' || ($f['eligibility'] ?? '') !== '' || ($f['flag'] ?? '') !== '' || ($f['nic'] ?? '') !== '';
$query = array_filter(['q' => $f['q'] ?? '', 'status' => $f['status'] ?? '', 'purpose' => $f['purpose'] ?? '', 'level' => $f['level'] ?? '', 'eligibility' => $f['eligibility'] ?? '', 'flag' => $f['flag'] ?? '', 'nic' => $f['nic'] ?? ''], static fn($v) => $v !== '');
$exportUrl = url('admin/scholarships/export') . ($query ? '?' . http_build_query($query) : '');
$tile = static function (string $status, string $label, string $icon, string $tone) use ($f, $stats): string {
    $current = ($f['status'] ?? '') === $status && ($f['eligibility'] ?? '') === '';
    $href = url('admin/scholarships' . ($status !== '' ? '?status=' . $status : ''));
    $count = $status === '' ? (int)($stats['total'] ?? 0) : (int)($stats[$status] ?? 0);
    return '<a class="sch-tile is-' . e($tone) . ($current ? ' is-current' : '') . '" href="' . e($href) . '"' . ($current ? ' aria-current="true"' : '') . '><span class="sch-tile-icon">' . admin_icon($icon) . '</span><span><small>' . e($label) . '</small><b>' . number_format($count) . '</b></span></a>';
};
?>
<div class="page-actions">
  <div><h2>Scholarship Applications</h2><p>Review applications for education support. The team decides every application — nothing is approved automatically.</p></div>
  <div class="page-action-stack">
    <a class="outline-link" href="<?= url('scholarships') ?>" target="_blank" rel="noopener">View application page ↗</a>
    <a class="outline-link" href="<?= e($exportUrl) ?>"><?= admin_icon('upload') ?><span>Export CSV</span></a>
  </div>
</div>

<div class="sch-tiles" aria-label="Applications by status">
  <?= $tile('', 'All applications', 'registration', 'neutral') ?>
  <?= $tile('pending', 'Pending', 'clock', 'gold') ?>
  <?= $tile('under_review', 'Under review', 'search', 'blue') ?>
  <?= $tile('shortlisted', 'Shortlisted', 'officials', 'purple') ?>
  <?= $tile('approved', 'Approved', 'check', 'green') ?>
  <?= $tile('rejected', 'Rejected', 'eye_off', 'red') ?>
</div>

<div class="sch-strip">
  <a class="sch-qualified<?= ($f['eligibility'] ?? '') === 'eligible' ? ' is-current' : '' ?>" href="<?= url('admin/scholarships?eligibility=eligible') ?>">
    <span class="sch-tile-icon"><?= admin_icon('shield') ?></span>
    <span class="sch-grow"><b><?= number_format((int)($stats['qualified'] ?? 0)) ?> meet the criteria</b><small>Marked “Meets the criteria” and still waiting for a decision</small></span>
    <?= admin_icon('chevron', 'sch-chev') ?>
  </a>
  <?php if ((int)($stats['duplicates'] ?? 0) > 0): ?>
  <a class="sch-qualified is-warn<?= ($f['flag'] ?? '') === 'duplicate' ? ' is-current' : '' ?>" href="<?= url('admin/scholarships?flag=duplicate') ?>">
    <span class="sch-tile-icon"><?= admin_icon('people') ?></span>
    <span class="sch-grow"><b><?= number_format((int)$stats['duplicates']) ?> possible duplicates</b><small>Same parent NIC and student name as an earlier application</small></span>
    <?= admin_icon('chevron', 'sch-chev') ?>
  </a>
  <?php endif; ?>
  <form class="sch-intake<?= $open ? ' is-open' : ' is-closed' ?>" method="post" action="<?= url('admin/scholarships/settings') ?>"<?= $open ? ' data-confirm="Close scholarship applications on the website? Saved applications are not affected."' : '' ?>>
    <?= csrf_field() ?>
    <input type="hidden" name="applications_open" value="<?= $open ? '0' : '1' ?>">
    <input type="hidden" name="closed_message" value="<?= e($closedMessage ?? '') ?>">
    <span class="sch-tile-icon"><?= admin_icon('power') ?></span>
    <span class="sch-grow"><b><?= $open ? 'Applications are open' : 'Applications are closed' ?></b><small><?= $open ? 'Families can apply on the website' : 'The form is hidden; saved applications stay here' ?></small></span>
    <button class="<?= $open ? 'outline-link mini' : 'admin-btn mini' ?>" type="submit"><?= $open ? 'Close' : 'Open' ?></button>
  </form>
</div>

<section class="admin-panel sch-panel">
  <form method="get" action="<?= url('admin/scholarships') ?>" class="sch-filters" role="search" aria-label="Filter applications">
    <label class="sch-search"><span class="sr-only">Search</span><?= admin_icon('search') ?><input type="search" name="q" value="<?= e($f['q'] ?? '') ?>" placeholder="Name, reference or mobile"></label>
    <label><span>Status</span><select name="status"><option value="">All statuses</option><?php foreach (Scholarship::STATUSES as $k => $l): ?><option value="<?= $k ?>"<?= ($f['status'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    <label><span>Support category</span><select name="purpose"><option value="">All categories</option><?php foreach (Scholarship::PURPOSES as $k => $l): ?><option value="<?= $k ?>"<?= ($f['purpose'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?><?= isset($byPurpose[$k]) ? ' (' . (int)$byPurpose[$k] . ')' : '' ?></option><?php endforeach; ?></select></label>
    <label><span>Education level</span><select name="level"><option value="">All levels</option><?php foreach (Scholarship::LEVELS as $k => $l): ?><option value="<?= $k ?>"<?= ($f['level'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    <label><span>Eligibility</span><select name="eligibility"><option value="">Any</option><?php foreach (Scholarship::ELIGIBILITY as $k => $l): ?><option value="<?= $k ?>"<?= ($f['eligibility'] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></label>
    <?php if (($f['flag'] ?? '') !== ''): ?><input type="hidden" name="flag" value="<?= e($f['flag']) ?>"><?php endif; ?>
    <?php if (($f['nic'] ?? '') !== ''): ?><input type="hidden" name="nic" value="match"><?php endif; ?>
    <div class="sch-filter-actions"><button class="admin-btn" type="submit">Apply filters</button><?php if ($hasFilter): ?><a class="outline-link" href="<?= url('admin/scholarships') ?>">Clear</a><?php endif; ?></div>
  </form>
  <details class="sch-nic-find"<?= ($f['nic'] ?? '') !== '' ? ' open' : '' ?>>
    <summary><?= admin_icon('key') ?><span>Find applications by parent or guardian NIC</span></summary>
    <form method="post" action="<?= url('admin/scholarships/find-nic') ?>" autocomplete="off">
      <?= csrf_field() ?>
      <label><span class="sr-only">NIC number</span><input name="nic" maxlength="14" autocomplete="off" spellcheck="false" placeholder="Full NIC number, e.g. 853202937V" required></label>
      <button class="outline-link" type="submit">Find</button>
      <small><?= admin_icon('shield') ?>The number is matched securely and is never added to the page address or logs.</small>
    </form>
  </details>
  <?php if (($f['nic'] ?? '') !== ''): ?><p class="sch-active-note"><?= admin_icon('key') ?>Showing applications that match the NIC you searched for. <a href="<?= url('admin/scholarships') ?>">Show all</a></p><?php endif; ?>

  <?php if ($rows): ?>
  <div class="sch-table-wrap">
    <table class="sch-table">
      <thead><tr><th scope="col">Reference</th><th scope="col">Student</th><th scope="col">Parent / guardian</th><th scope="col">Support</th><th scope="col">Status</th><th scope="col">Eligibility</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r):
        $view = url('admin/scholarships/view/' . (int)$r['id']); ?>
        <tr class="is-<?= e($r['status']) ?>">
          <td data-label="Reference"><a class="sch-ref" href="<?= e($view) ?>"><?= e($r['reference']) ?></a><small><?= e(date('d M Y, g:i A', strtotime((string)$r['created_at']))) ?></small><?php if ((int)$r['possible_duplicate']): ?><span class="sch-flag" title="Same parent NIC and student name as an earlier application">Possible duplicate</span><?php endif; ?></td>
          <td data-label="Student"><b><?= e($r['student_name']) ?></b><small><?= e(Scholarship::gradeLabel((string)$r['student_grade'])) ?></small></td>
          <td data-label="Parent / guardian"><span><?= e($r['guardian_name']) ?></span><small class="sch-mono">NIC <?= e(Scholarship::maskNic((string)$r['guardian_nic_last4'])) ?></small></td>
          <td data-label="Support"><span class="sch-purpose is-<?= e($r['purpose']) ?>" title="<?= e(Scholarship::PURPOSES[$r['purpose']] ?? $r['purpose']) ?>"><?= e(Scholarship::PURPOSE_SHORT[$r['purpose']] ?? $r['purpose']) ?></span></td>
          <td data-label="Status"><span class="sch-pill is-<?= e($r['status']) ?>"><?= e(Scholarship::STATUSES[$r['status']] ?? $r['status']) ?></span></td>
          <td data-label="Eligibility"><span class="sch-elig is-<?= e($r['eligibility']) ?>"><?= e(Scholarship::ELIGIBILITY_SHORT[$r['eligibility']] ?? $r['eligibility']) ?></span><?php if ($r['rating'] !== null): ?><small class="sch-stars" aria-label="Rating <?= (int)$r['rating'] ?> out of 5"><?= str_repeat('★', (int)$r['rating']) ?><i><?= str_repeat('★', 5 - (int)$r['rating']) ?></i></small><?php endif; ?></td>
          <td class="sch-row-action"><a class="outline-link mini" href="<?= e($view) ?>">Review<?= admin_icon('chevron') ?></a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= admin_pagination($pagination) ?>
  <?php else: ?>
  <div class="v7-empty-panel sch-empty">
    <span><?= admin_icon('registration') ?></span>
    <h3><?= $hasFilter ? 'No applications match these filters' : 'No applications yet' ?></h3>
    <p><?= $hasFilter ? 'Try a different status, category or search.' : 'Applications submitted on the Scholarships page will appear here for review.' ?></p>
    <?php if ($hasFilter): ?><a class="outline-link" href="<?= url('admin/scholarships') ?>">Clear filters</a><?php else: ?><a class="outline-link" href="<?= url('scholarships') ?>" target="_blank" rel="noopener">Open the application page ↗</a><?php endif; ?>
  </div>
  <?php endif; ?>
</section>
