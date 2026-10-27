<?php
use App\Core\Scholarship;

$ref = (string)($done['reference'] ?? '');
$purpose = Scholarship::PURPOSES[$done['purpose'] ?? ''] ?? '';
$at = strtotime((string)($done['at'] ?? '')) ?: time();
$foundation = $settings['foundation'] ?? [];
$phone = trim((string)($foundation['phone'] ?? '')) ?: '+94 71 258 3411';
?>
<section class="sch7-done">
  <div class="container">
    <article class="sch7-done-card" aria-labelledby="sch-done-title">
      <span class="sch7-done-seal" aria-hidden="true"><?= site_line_icon('check') ?></span>
      <span class="sch7-kicker">Application received</span>
      <h1 id="sch-done-title" tabindex="-1" data-sch-done-focus>Thank you. Your application has been submitted.</h1>
      <p class="sch7-done-lead">The 97 Foundation selection team will review it. Please keep your reference number — you may be asked for it when we contact you.</p>

      <div class="sch7-ref" aria-label="Your reference number">
        <div><small>Your reference number</small><b data-sch-ref><?= e($ref) ?></b></div>
        <button class="sch7-copy" type="button" data-copy-text="<?= e($ref) ?>" data-copy-done="Copied"><?= site_line_icon('copy') ?><span>Copy</span></button>
      </div>

      <dl class="sch7-done-meta">
        <?php if ($purpose !== ''): ?><div><dt>Support requested</dt><dd><?= e($purpose) ?></dd></div><?php endif; ?>
        <div><dt>Submitted</dt><dd><?= e(date('d M Y, g:i A', $at)) ?></dd></div>
        <div><dt>Status</dt><dd><span class="sch7-pill is-pending"><i aria-hidden="true"></i>Pending review</span></dd></div>
      </dl>

      <h2 class="sch7-done-sub">What happens next</h2>
      <ol class="sch7-done-steps">
        <li><span><?= site_line_icon('file') ?></span><div><b>We review your application</b><p>Every application is read by the selection team. Nothing is approved automatically.</p></div></li>
        <li><span><?= site_line_icon('phone') ?></span><div><b>We may call you</b><p>If your application is shortlisted, we will call the mobile number you gave to confirm details.</p></div></li>
        <li><span><?= site_line_icon('graduation') ?></span><div><b>The team decides</b><p>Selected students are contacted directly with the next steps.</p></div></li>
      </ol>

      <div class="sch7-done-actions">
        <button class="h7-btn sch7-outline" type="button" data-print-page><?= site_line_icon('print') ?><span>Print this page</span></button>
        <a class="h7-btn h7-btn-primary" href="<?= url('') ?>" data-spa-link><span>Back to home</span><?= site_line_icon('arrow') ?></a>
      </div>
      <p class="sch7-done-help">Questions? Call <a href="tel:<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a> and mention your reference number.</p>
    </article>
  </div>
</section>
