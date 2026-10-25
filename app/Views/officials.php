<?php
$mode = ($mode ?? 'current') === 'past' ? 'past' : 'current';
$officials = $officials ?? [];
$groups = $groups ?? [];
$isPast = $mode === 'past';
$fallbackLogo = brand_logo_url($settings['foundation'] ?? []);
$initials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) $out .= mb_strtoupper(mb_substr($p, 0, 1));
    return $out ?: '97';
};
$renderCard = static function (array $o) use ($isPast, $initials, $fallbackLogo): void {
    $term = \App\Core\Officials::termLabel($o);
    ?>
    <article class="official7-card reveal<?= $isPast ? ' is-past' : '' ?>">
      <div class="official7-photo">
        <?php if (!empty($o['photo_path'])): ?>
          <img src="<?= e(public_upload($o['photo_path'])) ?>" alt="<?= e($o['full_name']) ?>" width="480" height="516" loading="lazy" decoding="async">
        <?php else: ?>
          <span class="official7-initials" aria-hidden="true"><?= e($initials((string)$o['full_name'])) ?></span>
        <?php endif; ?>
        <?php if ($term !== ''): ?><span class="official7-badge"><?= e($term) ?></span><?php endif; ?>
      </div>
      <div class="official7-body">
        <span class="official7-position"><?= e($o['position']) ?></span>
        <h3><?= e($o['full_name']) ?></h3>
        <?php if (!empty($o['bio'])): ?><p><?= e(mb_strimwidth((string)$o['bio'], 0, 160, '…')) ?></p><?php endif; ?>
        <?php if (!$isPast && (int)($o['show_contact'] ?? 0) === 1 && (!empty($o['email']) || !empty($o['phone']))): ?>
        <div class="official7-contact">
          <?php if (!empty($o['email'])): ?><a href="mailto:<?= e($o['email']) ?>"><?= site_icon('mail') ?><span><?= e($o['email']) ?></span></a><?php endif; ?>
          <?php if (!empty($o['phone'])): ?><a href="tel:<?= e(tel_href((string)$o['phone'])) ?>"><?= site_icon('call') ?><span><?= e($o['phone']) ?></span></a><?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </article>
    <?php
};
?>
<section class="page7-hero" style="--page7-image:url('<?= e(asset('images/hero/hero-' . ($isPast ? '4' : '1') . '.jpg')) ?>')">
  <div class="container reveal">
    <span class="eyebrow7"><?= $isPast ? 'HONOURING OUR LEADERSHIP' : 'FOUNDATION LEADERSHIP' ?></span>
    <h1><?= $isPast ? 'Past Officials' : 'Our Officials' ?></h1>
    <p><?= $isPast
        ? 'We gratefully remember the office bearers whose service and dedication built the foundation that 97 Foundation stands on today.'
        : 'Meet the office bearers who guide 97 Foundation’s programmes, finances and community partnerships with transparency and care.' ?></p>
  </div>
</section>
<?php $membersTab = $isPast ? 'past' : 'officials'; require __DIR__ . '/partials/members-subnav.php'; ?>
<section class="section7">
  <div class="container">
    <?php if (!$officials): ?>
      <div class="empty7 reveal">
        <span class="empty7-icon"><?= site_icon('handshake') ?></span>
        <h3><?= $isPast ? 'Past officials will be listed here soon' : 'Our officials will be introduced here soon' ?></h3>
        <p><?= $isPast ? 'The Foundation is preparing the record of previous office bearers and their service terms.' : 'The current office bearers are being published by the Foundation administration.' ?></p>
        <a class="btn7 btn7-login" href="<?= url('members') ?>" data-spa-link>View Members Directory</a>
      </div>
    <?php elseif ($isPast): ?>
      <?php foreach ($groups as $label => $rows): ?>
        <div class="term7-group">
          <div class="term7-head reveal"><h3><?= e($label) ?></h3><i></i><span><?= count($rows) ?> <?= count($rows) === 1 ? 'official' : 'officials' ?></span></div>
          <div class="officials7-grid is-compact"><?php foreach ($rows as $o) $renderCard($o); ?></div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="section7-head reveal"><div><h2>Office Bearers</h2><p><?= count($officials) ?> <?= count($officials) === 1 ? 'official' : 'officials' ?> currently serving the Foundation.</p></div><a class="btn7 btn7-login" href="<?= url('members/past-officials') ?>" data-spa-link>View Past Officials</a></div>
      <div class="officials7-grid"><?php foreach ($officials as $o) $renderCard($o); ?></div>
    <?php endif; ?>
  </div>
</section>
