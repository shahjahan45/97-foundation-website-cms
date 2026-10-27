<?php
use App\Core\Scholarship;

$p = $page ?? [];
$errors = $errors ?? [];
$old = $old ?? [];
$faq = $faq ?? [];
$foundation = $settings['foundation'] ?? [];
$phone = trim((string)($foundation['phone'] ?? '')) ?: '+94 71 258 3411';
$email = trim((string)($foundation['email'] ?? '')) ?: '97found@gmail.com';
$val = static fn(string $k): string => e((string)($old[$k] ?? ''));
$purposeIcons = ['ol' => 'book', 'al' => 'school', 'university' => 'graduation', 'general' => 'education'];
$bodyHtml = trim((string)($p['body'] ?? '')) !== '' ? article_render($p['body'])['html'] : '';
$heroImage = safe_image($p['image_path'] ?? 'demo/cause-education.jpg');
$hasErrors = $errors || !empty($formError);

/** One labelled input block with hint and error, wired for screen readers. */
$field = static function (string $name, string $label, string $control, string $hint = '', string $icon = '') use ($errors): string {
    $id = 'sch-' . $name;
    $error = $errors[$name] ?? '';
    $html = '<div class="sch7-field' . ($error !== '' ? ' is-invalid' : '') . '" data-field="' . e($name) . '">';
    $html .= '<label for="' . e($id) . '">' . e($label) . ' <span class="sch7-req" aria-hidden="true">*</span></label>';
    $html .= '<div class="sch7-control' . ($icon !== '' ? ' has-icon' : '') . '">' . ($icon !== '' ? '<span class="sch7-control-icon">' . site_line_icon($icon) . '</span>' : '') . $control . '</div>';
    if ($hint !== '') $html .= '<p class="sch7-hint" id="' . e($id) . '-hint">' . $hint . '</p>';
    $html .= '<p class="sch7-error" id="' . e($id) . '-error" data-error-for="' . e($name) . '"' . ($error === '' ? ' hidden' : '') . '>' . site_line_icon('alert') . '<span>' . e($error) . '</span></p>';
    return $html . '</div>';
};
$aria = static function (string $name, bool $hint = true) use ($errors): string {
    $id = 'sch-' . $name;
    return 'id="' . $id . '" name="' . $name . '" aria-required="true" aria-describedby="' . ($hint ? $id . '-hint ' : '') . $id . '-error"' . (isset($errors[$name]) ? ' aria-invalid="true"' : '');
};
?>
<section class="sch7-hero">
  <div class="sch7-hero-bg" style="--sch-img:url('<?= e($heroImage) ?>')" aria-hidden="true"></div>
  <div class="container sch7-hero-inner">
    <div class="sch7-hero-copy" data-reveal>
      <nav class="sch7-crumbs" aria-label="Breadcrumb"><a href="<?= url('') ?>" data-spa-link>Home</a><span aria-hidden="true">/</span><span aria-current="page">Scholarships</span></nav>
      <span class="sch7-eyebrow"><?= site_line_icon('graduation') ?><?= e($p['eyebrow'] ?? 'EDUCATION SUPPORT') ?></span>
      <h1><?= e(($p['hero_title'] ?? '') ?: ($p['title'] ?? 'Scholarships & Education Support')) ?></h1>
      <p class="sch7-lead"><?= e($p['hero_subtitle'] ?? '') ?></p>
      <div class="sch7-hero-actions">
        <a class="h7-btn h7-btn-primary" href="#apply"><?= site_line_icon('file') ?><span><?= $open ? 'Apply now' : 'Application details' ?></span></a>
        <a class="h7-btn sch7-ghost" href="#process"><span>How selection works</span><?= site_line_icon('arrow') ?></a>
      </div>
      <ul class="sch7-hero-facts">
        <li><?= site_line_icon('check') ?>Free to apply</li>
        <li><?= site_line_icon('clock') ?>Takes about 5 minutes</li>
        <li><?= site_line_icon('lock') ?>Details kept private</li>
      </ul>
    </div>
    <aside class="sch7-hero-card" data-reveal aria-label="Support available">
      <div class="sch7-hero-card-head"><b>Support available for</b><span class="sch7-pill <?= $open ? 'is-open' : 'is-closed' ?>"><i aria-hidden="true"></i><?= $open ? 'Applications open' : 'Applications closed' ?></span></div>
      <ul>
        <?php foreach (Scholarship::PURPOSES as $k => $label): ?>
        <li><span class="sch7-ico"><?= site_line_icon($purposeIcons[$k]) ?></span><span><b><?= e($label) ?></b><small><?= e(Scholarship::PURPOSE_NOTES[$k]) ?></small></span></li>
        <?php endforeach; ?>
      </ul>
    </aside>
  </div>
</section>

<section class="sch7-section sch7-about" id="process" aria-labelledby="sch-process-title">
  <div class="container sch7-about-grid">
    <div class="sch7-about-copy" data-reveal>
      <span class="sch7-kicker">About the programme</span>
      <h2 id="sch-process-title">Keeping students in school</h2>
      <?php if ($bodyHtml !== ''): ?><div class="sch7-prose"><?= $bodyHtml ?></div><?php endif; ?>
      <div class="sch7-notice" role="note"><?= site_line_icon('shield') ?><p><b>Every application is reviewed by people.</b> Applications are never approved automatically. The selection team discusses each one and contacts the families they select. Submitting an application does not guarantee support.</p></div>
    </div>
    <ol class="sch7-steps" data-reveal-group>
      <li data-reveal><span class="sch7-step-n">1</span><div><b>Apply online</b><p>A parent or guardian completes the form below and receives a reference number.</p></div></li>
      <li data-reveal><span class="sch7-step-n">2</span><div><b>Review</b><p>The selection team reads every application and checks the details provided.</p></div></li>
      <li data-reveal><span class="sch7-step-n">3</span><div><b>Shortlist &amp; verify</b><p>Shortlisted families may be called to confirm details or show supporting documents.</p></div></li>
      <li data-reveal><span class="sch7-step-n">4</span><div><b>Decision</b><p>The team decides together and contacts the selected students directly.</p></div></li>
    </ol>
  </div>
</section>

<section class="sch7-section sch7-apply" id="apply" aria-labelledby="sch-apply-title">
  <div class="container sch7-apply-grid">
    <div class="sch7-form-card">
      <header class="sch7-form-head">
        <span class="sch7-kicker">Application form</span>
        <h2 id="sch-apply-title">Apply for education support</h2>
        <p>To be completed by the student's parent or guardian. Fields marked <span class="sch7-req" aria-hidden="true">*</span><span class="sr-only">with an asterisk</span> are required.</p>
      </header>

      <?php if (!$open): ?>
      <div class="sch7-closed" role="status"><?= site_line_icon('clock') ?><div><b>Applications are closed</b><p><?= e($closedMessage ?? Scholarship::DEFAULT_CLOSED_MESSAGE) ?></p><a href="<?= url('contact') ?>" data-spa-link>Contact the Foundation</a></div></div>
      <?php else: ?>
      <div class="sch7-error-summary" role="alert" tabindex="-1" data-sch-summary aria-labelledby="sch-summary-title"<?= $hasErrors ? '' : ' hidden' ?>>
        <?= site_line_icon('alert') ?>
        <div>
          <h3 id="sch-summary-title">Please check your application</h3>
          <p data-sch-summary-text><?= e(($formError ?? '') ?: 'Some details need your attention.') ?></p>
          <ul data-sch-summary-list><?php foreach ($errors as $k => $msg): ?><li><a href="#sch-<?= e($k) ?>"><?= e($msg) ?></a></li><?php endforeach; ?></ul>
        </div>
      </div>

      <form class="sch7-form" method="post" action="<?= url('scholarships/apply') ?>" novalidate data-scholarship-form>
        <?= csrf_field() ?>
        <div class="sch7-hp" aria-hidden="true"><label for="sch-website">Leave this field empty</label><input id="sch-website" name="website" type="text" tabindex="-1" autocomplete="off"></div>

        <fieldset class="sch7-step">
          <legend><span class="sch7-step-no" aria-hidden="true"><?= site_line_icon('user') ?></span><span><b>Student details</b><small>The student who needs support</small></span></legend>
          <div class="sch7-fields">
            <?= $field('student_name', "Student's full name", '<input type="text" ' . $aria('student_name') . ' required minlength="3" maxlength="120" autocomplete="off" spellcheck="false" value="' . $val('student_name') . '">', 'As written in school records.', 'user') ?>
            <?php
            $gradeSel = '<select ' . $aria('student_grade', false) . ' required><option value="">Choose a grade or class</option><optgroup label="School">';
            foreach (Scholarship::grades() as $gk => $gl) {
                if ($gk === 'university') $gradeSel .= '</optgroup><optgroup label="After school">';
                $gradeSel .= '<option value="' . e($gk) . '"' . (($old['student_grade'] ?? '') === $gk ? ' selected' : '') . '>' . e($gl) . '</option>';
            }
            $gradeSel .= '</optgroup></select>';
            echo $field('student_grade', 'Current school grade / class', $gradeSel, '', 'school');
            ?>
          </div>
        </fieldset>

        <fieldset class="sch7-step">
          <legend><span class="sch7-step-no" aria-hidden="true"><?= site_line_icon('people') ?></span><span><b>Parent or guardian</b><small>The adult applying for the student</small></span></legend>
          <div class="sch7-fields">
            <?= $field('guardian_name', "Parent or guardian's full name", '<input type="text" ' . $aria('guardian_name') . ' required minlength="3" maxlength="120" autocomplete="name" value="' . $val('guardian_name') . '">', 'As shown on the National Identity Card.', 'user') ?>
            <?= $field('guardian_nic', "Parent or guardian's NIC number", '<input type="text" ' . $aria('guardian_nic') . ' required maxlength="14" autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="e.g. 853202937V or 198532002937" value="' . $val('guardian_nic') . '" data-nic-input>', '<span data-nic-hint>Old format: 9 digits and V or X. New format: 12 digits.</span>', 'id') ?>
          </div>
        </fieldset>

        <fieldset class="sch7-step">
          <legend><span class="sch7-step-no" aria-hidden="true"><?= site_line_icon('phone') ?></span><span><b>Contact &amp; address</b><small>How the selection team can reach you</small></span></legend>
          <div class="sch7-fields">
            <?= $field('mobile', 'Contact mobile number', '<input type="tel" ' . $aria('mobile') . ' required maxlength="16" inputmode="tel" autocomplete="tel" placeholder="e.g. 071 234 5678" value="' . $val('mobile') . '">', 'A Sri Lankan mobile number. We will call this number if your application is shortlisted.', 'phone') ?>
            <div class="sch7-field-wide"><?= $field('address', 'Residential address', '<textarea ' . $aria('address') . ' required minlength="10" maxlength="500" rows="3" autocomplete="street-address" data-sch-count>' . $val('address') . '</textarea>', 'House number, street, village or town and district. <span class="sch7-count" data-sch-counter></span>', 'home') ?></div>
          </div>
        </fieldset>

        <fieldset class="sch7-step">
          <legend><span class="sch7-step-no" aria-hidden="true"><?= site_line_icon('graduation') ?></span><span><b>Support requested</b><small>Choose the closest match</small></span></legend>
          <div class="sch7-fields">
            <?php
            $purposeSel = '<select ' . $aria('purpose') . ' required><option value="">Choose the type of support</option>';
            foreach (Scholarship::PURPOSES as $pk => $pl) $purposeSel .= '<option value="' . e($pk) . '" data-note="' . e(Scholarship::PURPOSE_NOTES[$pk]) . '"' . (($old['purpose'] ?? '') === $pk ? ' selected' : '') . '>' . e($pl) . '</option>';
            $purposeSel .= '</select>';
            ?>
            <div class="sch7-field-wide"><?= $field('purpose', 'Scholarship or support type', $purposeSel, '<span data-purpose-note>Select the education stage the support is for.</span>', 'education') ?></div>
          </div>
        </fieldset>

        <div class="sch7-consent<?= isset($errors['consent']) ? ' is-invalid' : '' ?>" data-field="consent">
          <label class="sch7-check" for="sch-consent">
            <input type="checkbox" id="sch-consent" name="consent" value="1" aria-required="true" aria-describedby="sch-consent-error"<?= !empty($old['consent']) ? ' checked' : '' ?><?= isset($errors['consent']) ? ' aria-invalid="true"' : '' ?>>
            <span class="sch7-check-box" aria-hidden="true"><?= site_line_icon('check') ?></span>
            <span>I confirm that the information in this application is true, and I agree that 97 Foundation may use it to assess the application and contact me, as described in the <a href="<?= url('privacy-policy') ?>" target="_blank" rel="noopener">Privacy Policy</a>. <span class="sch7-req" aria-hidden="true">*</span></span>
          </label>
          <p class="sch7-error" id="sch-consent-error" data-error-for="consent"<?= isset($errors['consent']) ? '' : ' hidden' ?>><?= site_line_icon('alert') ?><span><?= e($errors['consent'] ?? '') ?></span></p>
        </div>

        <div class="sch7-submit">
          <button class="h7-btn h7-btn-primary sch7-submit-btn" type="submit" data-sch-submit><span>Submit application</span><?= site_line_icon('arrow') ?></button>
          <p><?= site_line_icon('lock') ?>Sent securely. You will see your reference number on the next screen.</p>
        </div>
      </form>
      <?php endif; ?>
    </div>

    <aside class="sch7-aside" aria-label="Help with your application">
      <div class="sch7-aside-card">
        <h3><?= site_line_icon('list') ?>Before you start</h3>
        <ul class="sch7-ticks">
          <li><?= site_line_icon('check') ?>The student's current grade or class</li>
          <li><?= site_line_icon('check') ?>The parent or guardian's NIC number</li>
          <li><?= site_line_icon('check') ?>A mobile number we can call</li>
          <li><?= site_line_icon('check') ?>Your full home address</li>
        </ul>
      </div>
      <div class="sch7-aside-card is-privacy">
        <h3><?= site_line_icon('lock') ?>Your information is protected</h3>
        <p>NIC numbers are stored encrypted. Only authorised Foundation administrators can see application details, and they are never published or shared. <a href="<?= url('privacy-policy') ?>" data-spa-link>Privacy Policy</a></p>
      </div>
      <div class="sch7-aside-card">
        <h3><?= site_line_icon('chat') ?>Need help?</h3>
        <p>Call or email us and we will help you complete the application.</p>
        <a class="sch7-contact" href="tel:<?= e(tel_href($phone)) ?>"><?= site_line_icon('phone') ?><span><?= e($phone) ?></span></a>
        <a class="sch7-contact" href="mailto:<?= e($email) ?>"><?= site_line_icon('mail') ?><span><?= e($email) ?></span></a>
      </div>
    </aside>
  </div>
</section>

<?php if ($faq): ?>
<section class="sch7-section sch7-faq" id="faq" aria-labelledby="sch-faq-title">
  <div class="container sch7-faq-inner">
    <div class="sch7-faq-head" data-reveal><span class="sch7-kicker">Questions</span><h2 id="sch-faq-title">Frequently asked questions</h2><?php if (!empty($p['answer_summary'])): ?><p><?= e($p['answer_summary']) ?></p><?php endif; ?></div>
    <div class="sch7-faq-list">
      <?php foreach ($faq as $i => [$q, $a]): ?>
      <details<?= $i === 0 ? ' open' : '' ?>><summary><span><?= e($q) ?></span><i aria-hidden="true"></i></summary><p><?= e($a) ?></p></details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<script type="application/ld+json"><?= seo_json(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_map(static fn($f) => ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]], $faq)]) ?></script>
<?php endif; ?>
