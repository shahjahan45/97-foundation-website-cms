<?php
use App\Core\Scholarship;

$a = $app;
$status = (string)$a['status'];
$isSuper = ($user['role'] ?? '') === 'super_admin';
$kindIcon = ['status' => 'chevron', 'evaluation' => 'check', 'comment' => 'edit', 'note' => 'mail', 'access' => 'key'];
$kindLabel = ['status' => 'Status changed', 'evaluation' => 'Evaluation updated', 'comment' => 'Selection comment', 'note' => 'Internal note', 'access' => 'Sensitive data viewed'];
$statusHelp = [
    'pending' => 'New — nobody has started reviewing it yet.',
    'under_review' => 'The team is checking the details.',
    'shortlisted' => 'A strong candidate; verification or discussion pending.',
    'approved' => 'Selected for support. Requires “Meets the criteria”.',
    'rejected' => 'Not selected this time.',
];
?>
<div class="page-actions sch-view-head">
  <div>
    <p class="sch-back"><a href="<?= url('admin/scholarships') ?>"><span aria-hidden="true">←</span> All applications</a></p>
    <h2><span class="sch-mono"><?= e($a['reference']) ?></span></h2>
    <p>Submitted <?= e(date('d M Y, g:i A', strtotime((string)$a['created_at']))) ?> · <?= e(Scholarship::PURPOSES[$a['purpose']] ?? $a['purpose']) ?></p>
  </div>
  <div class="page-action-stack sch-pager">
    <?php if (!empty($neighbours['prev'])): ?><a class="outline-link mini" href="<?= url('admin/scholarships/view/' . (int)$neighbours['prev']['id']) ?>" title="Newer application">‹ Newer</a><?php endif; ?>
    <?php if (!empty($neighbours['next'])): ?><a class="outline-link mini" href="<?= url('admin/scholarships/view/' . (int)$neighbours['next']['id']) ?>" title="Older application">Older ›</a><?php endif; ?>
  </div>
</div>

<div class="sch-summary admin-panel">
  <div><small>Status</small><span class="sch-pill is-<?= e($status) ?> is-lg"><?= e(Scholarship::STATUSES[$status] ?? $status) ?></span></div>
  <div><small>Eligibility</small><span class="sch-elig is-<?= e($a['eligibility']) ?>"><?= e(Scholarship::ELIGIBILITY[$a['eligibility']] ?? $a['eligibility']) ?></span></div>
  <div><small>Rating</small><?php if ($a['rating'] !== null): ?><span class="sch-stars is-lg" aria-label="<?= (int)$a['rating'] ?> out of 5"><?= str_repeat('★', (int)$a['rating']) ?><i><?= str_repeat('★', 5 - (int)$a['rating']) ?></i></span><?php else: ?><b>Not rated</b><?php endif; ?></div>
  <div><small>Last review</small><b><?= $a['reviewed_at'] ? e(($a['reviewer'] ?: 'Administrator') . ' · ' . date('d M Y', strtotime((string)$a['reviewed_at']))) : 'Not reviewed yet' ?></b></div>
</div>

<?php if ((int)$a['possible_duplicate']): ?>
<div class="sch-alert is-warn" role="note"><?= admin_icon('people') ?><p><b>Possible duplicate.</b> An earlier application has the same parent or guardian NIC and the same student name. Check the related applications below before deciding.</p></div>
<?php endif; ?>

<div class="sch-view-grid">
  <div class="sch-view-main">
    <section class="admin-panel sch-card" aria-labelledby="sch-student-h">
      <h3 id="sch-student-h"><?= admin_icon('user') ?>Student</h3>
      <dl class="sch-dl">
        <div><dt>Full name</dt><dd><?= e($a['student_name']) ?></dd></div>
        <div><dt>Current grade / class</dt><dd><?= e(Scholarship::gradeLabel((string)$a['student_grade'])) ?></dd></div>
        <div><dt>Education level</dt><dd><?= e(Scholarship::LEVELS[$a['education_level']] ?? $a['education_level']) ?></dd></div>
        <div><dt>Support requested</dt><dd><span class="sch-purpose is-<?= e($a['purpose']) ?>"><?= e(Scholarship::PURPOSES[$a['purpose']] ?? $a['purpose']) ?></span></dd></div>
      </dl>
    </section>

    <section class="admin-panel sch-card" id="guardian" aria-labelledby="sch-guardian-h">
      <h3 id="sch-guardian-h"><?= admin_icon('people') ?>Parent or guardian</h3>
      <dl class="sch-dl">
        <div><dt>Full name</dt><dd><?= e($a['guardian_name']) ?></dd></div>
        <div class="sch-nic-row"><dt>NIC number</dt><dd>
          <?php if ($nic !== ''): ?>
            <span class="sch-nic is-revealed" data-sch-nic-revealed><?= e($nic) ?></span>
            <small class="sch-nic-note"><?= admin_icon('clock') ?>Shown for 2 minutes. This view was recorded in the audit log.</small>
          <?php else: ?>
            <span class="sch-nic"><?= e(Scholarship::maskNic((string)$a['guardian_nic_last4'], $nicLength)) ?></span>
            <form method="post" action="<?= url('admin/scholarships/reveal') ?>" class="sch-inline-form" data-no-loader>
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
              <button class="outline-link mini" type="submit" data-sch-reveal><?= admin_icon('eye') ?><span>Show full NIC</span></button>
            </form>
            <small class="sch-nic-note"><?= admin_icon('shield') ?>Stored encrypted. Showing it is recorded in the audit log.</small>
          <?php endif; ?>
        </dd></div>
      </dl>
    </section>

    <section class="admin-panel sch-card" aria-labelledby="sch-contact-h">
      <h3 id="sch-contact-h"><?= admin_icon('pin') ?>Contact &amp; address</h3>
      <dl class="sch-dl">
        <div><dt>Mobile</dt><dd><a href="tel:<?= e(tel_href((string)$a['mobile'])) ?>"><?= e(Scholarship::formatMobile((string)$a['mobile'])) ?></a></dd></div>
        <div class="is-wide"><dt>Residential address</dt><dd><?= nl2br(e((string)$a['address'])) ?></dd></div>
        <div class="is-wide"><dt>Declaration</dt><dd>Confirmed by the applicant on <?= e(date('d M Y, g:i A', strtotime((string)$a['consent_at']))) ?></dd></div>
      </dl>
    </section>

    <?php if ($related): ?>
    <section class="admin-panel sch-card" aria-labelledby="sch-related-h">
      <h3 id="sch-related-h"><?= admin_icon('registration') ?>Other applications from this parent or guardian</h3>
      <ul class="sch-related">
        <?php foreach ($related as $r): ?>
        <li><a href="<?= url('admin/scholarships/view/' . (int)$r['id']) ?>"><span class="sch-mono"><?= e($r['reference']) ?></span><b><?= e($r['student_name']) ?></b><small><?= e(Scholarship::gradeLabel((string)$r['student_grade'])) ?> · <?= e(Scholarship::PURPOSES[$r['purpose']] ?? $r['purpose']) ?> · <?= e(date('d M Y', strtotime((string)$r['created_at']))) ?></small></a><span class="sch-pill is-<?= e($r['status']) ?>"><?= e(Scholarship::STATUSES[$r['status']] ?? $r['status']) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>

    <section class="admin-panel sch-card" id="notes" aria-labelledby="sch-notes-h">
      <h3 id="sch-notes-h"><?= admin_icon('chat') ?>Review history &amp; internal notes</h3>
      <form class="sch-note-form" method="post" action="<?= url('admin/scholarships/note') ?>">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
        <label for="sch-note"><span>Add an internal note</span><small>Only administrators can see notes. Use them to record checks, calls and discussion points.</small></label>
        <textarea id="sch-note" name="body" rows="3" maxlength="3000" required placeholder="e.g. Called the parent on 12 Oct — confirmed the student is in Grade 11 at Thambala Maha Vidyalaya."></textarea>
        <button class="admin-btn" type="submit"><?= admin_icon('plus') ?><span>Add note</span></button>
      </form>
      <?php if ($timeline): ?>
      <ol class="sch-timeline">
        <?php foreach ($timeline as $t): $k = (string)$t['kind']; ?>
        <li class="is-<?= e($k) ?>">
          <span class="sch-tl-icon"><?= admin_icon($kindIcon[$k] ?? 'edit') ?></span>
          <div>
            <p class="sch-tl-head"><b><?= e($kindLabel[$k] ?? ucfirst($k)) ?></b><span><?= e(($t['admin_name'] ?: 'Administrator') . ' · ' . date('d M Y, g:i A', strtotime((string)$t['created_at']))) ?></span></p>
            <?php if ($k === 'status'): ?><p class="sch-tl-status"><span class="sch-pill is-<?= e($t['old_status']) ?>"><?= e(Scholarship::STATUSES[$t['old_status']] ?? $t['old_status']) ?></span><span aria-hidden="true">→</span><span class="sr-only">to</span><span class="sch-pill is-<?= e($t['new_status']) ?>"><?= e(Scholarship::STATUSES[$t['new_status']] ?? $t['new_status']) ?></span></p><?php endif; ?>
            <?php if (trim((string)$t['body']) !== ''): ?><p class="sch-tl-body"><?= nl2br(e((string)$t['body'])) ?></p><?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
        <li class="is-submitted"><span class="sch-tl-icon"><?= admin_icon('registration') ?></span><div><p class="sch-tl-head"><b>Application submitted</b><span><?= e(date('d M Y, g:i A', strtotime((string)$a['created_at']))) ?></span></p></div></li>
      </ol>
      <?php else: ?>
      <p class="sch-muted">No review activity yet. Notes, status changes and evaluations will appear here.</p>
      <?php endif; ?>
    </section>
  </div>

  <aside class="sch-view-side">
    <section class="admin-panel sch-card sch-decision" aria-labelledby="sch-decision-h">
      <h3 id="sch-decision-h"><?= admin_icon('shield') ?>Review &amp; decision</h3>
      <p class="sch-muted">Applications are never approved automatically. Record the team's assessment, then choose the status.</p>
      <form method="post" action="<?= url('admin/scholarships/evaluate') ?>" data-sch-review>
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">

        <fieldset class="sch-choice">
          <legend>1. Does the student meet the criteria?</legend>
          <?php foreach (Scholarship::ELIGIBILITY as $k => $l): ?>
          <label class="sch-radio is-<?= e($k) ?>"><input type="radio" name="eligibility" value="<?= e($k) ?>"<?= $a['eligibility'] === $k ? ' checked' : '' ?>><span><?= e($l) ?></span></label>
          <?php endforeach; ?>
        </fieldset>

        <fieldset class="sch-choice sch-rating">
          <legend>2. Rating <small>(optional — strength of need and merit)</small></legend>
          <div class="sch-rating-row">
            <label class="sch-rate is-none"><input type="radio" name="rating" value=""<?= $a['rating'] === null ? ' checked' : '' ?>><span>None</span></label>
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <label class="sch-rate"><input type="radio" name="rating" value="<?= $i ?>"<?= (int)$a['rating'] === $i && $a['rating'] !== null ? ' checked' : '' ?>><span aria-label="<?= $i ?> out of 5"><?= $i ?>★</span></label>
            <?php endfor; ?>
          </div>
        </fieldset>

        <fieldset class="sch-choice">
          <legend>3. Status</legend>
          <?php foreach (Scholarship::STATUSES as $k => $l): ?>
          <label class="sch-radio is-status is-<?= e($k) ?>"><input type="radio" name="status" value="<?= e($k) ?>"<?= $status === $k ? ' checked' : '' ?>><span><b><?= e($l) ?></b><small><?= e($statusHelp[$k]) ?></small></span></label>
          <?php endforeach; ?>
        </fieldset>
        <p class="sch-guard" data-sch-guard hidden role="alert"><?= admin_icon('shield') ?><span>To approve, first mark the student as “Meets the criteria”.</span></p>

        <label class="sch-comment" for="sch-comment">
          <span>4. Selection comment</span>
          <small>Explain the decision. Required when approving or rejecting.</small>
          <textarea id="sch-comment" name="selection_comment" rows="4" maxlength="3000" data-sch-comment placeholder="e.g. Family income is below the threshold and results are strong. Agreed at the selection meeting on 14 Oct."><?= e((string)($a['selection_comment'] ?? '')) ?></textarea>
        </label>
        <p class="sch-guard" data-sch-comment-guard hidden role="alert"><?= admin_icon('edit') ?><span>Add a selection comment before approving or rejecting.</span></p>

        <button class="admin-btn sch-save" type="submit"><?= admin_icon('check') ?><span>Save review</span></button>
      </form>
    </section>

    <?php if ($isSuper): ?>
    <section class="admin-panel sch-card sch-danger" aria-labelledby="sch-danger-h">
      <h3 id="sch-danger-h"><?= admin_icon('trash') ?>Delete application</h3>
      <p class="sch-muted">Permanently removes this application and its review history. Use only for spam or at the family's request.</p>
      <form method="post" action="<?= url('admin/scholarships/delete') ?>" data-confirm="Permanently delete application <?= e($a['reference']) ?> and its review history?">
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
        <button class="danger-link" type="submit">Delete permanently</button>
      </form>
    </section>
    <?php endif; ?>
  </aside>
</div>
