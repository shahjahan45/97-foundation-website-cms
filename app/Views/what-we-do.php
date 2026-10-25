<?php
$page = $page ?? [];
$categories = $categories ?? [];
$hero = hero_settings($settings ?? null);
$pillars = [
    ['school', $hero['highlight_1'] ?: 'Education Support', 'Learning materials, tuition support and scholarships that keep children in school.'],
    ['family_restroom', $hero['highlight_2'] ?: 'Family Support', 'Essential supplies, emergency relief and dignified assistance for families in need.'],
    ['menu_book', $hero['highlight_3'] ?: 'Students Empowerment', 'Mentoring, skills programmes and guidance that help students reach their potential.'],
    ['handshake', $hero['highlight_4'] ?: 'Social Work', 'Volunteer-led community projects that strengthen neighbourhoods across our region.'],
];
$image = safe_image($page['image_path'] ?? null, 'images/hero/hero-5.jpg');
if (str_contains($image, 'images/demo/')) $image = asset('images/hero/hero-5.jpg');
?>
<section class="page7-hero" style="--page7-image:url('<?= e(asset('images/hero/hero-3.jpg')) ?>')">
  <div class="container reveal">
    <span class="eyebrow7"><?= e($page['eyebrow'] ?: 'WHAT WE DO') ?></span>
    <h1><?= e($page['hero_title'] ?: ($page['title'] ?? 'What We Do')) ?></h1>
    <?php if (!empty($page['hero_subtitle'])): ?><p><?= e($page['hero_subtitle']) ?></p><?php endif; ?>
  </div>
</section>
<section class="section7">
  <div class="container">
    <div class="wwd7-intro">
      <div class="reveal">
        <h2>Compassion turned into accountable action</h2>
        <?php foreach (preg_split('/\R{2,}/', trim((string)($page['body'] ?? ''))) ?: [] as $para): if (trim($para) === '') continue; ?>
          <p><?= nl2br(e(trim($para))) ?></p>
        <?php endforeach; ?>
        <a class="btn7 btn7-donate" href="<?= url('volunteer') ?>" data-spa-link><?= site_icon('favorite') ?><span>Become a Volunteer</span></a>
      </div>
      <figure class="reveal"><img src="<?= e($image) ?>" alt="97 Foundation volunteers supporting the community" loading="lazy" decoding="async"></figure>
    </div>

    <div class="wwd7-pillars">
      <?php foreach ($pillars as [$icon, $title, $copy]): ?>
        <article class="wwd7-pillar reveal"><span><?= site_icon($icon) ?></span><h3><?= e($title) ?></h3><p><?= e($copy) ?></p></article>
      <?php endforeach; ?>
    </div>

    <?php if ($categories): ?>
    <div class="wwd7-causes">
      <div class="section7-head reveal"><div><h2>Areas where we serve</h2><p>Every cause area is managed by the Foundation and connected to verified campaigns.</p></div><a class="btn7 btn7-login" href="<?= url('campaigns') ?>" data-spa-link>View Campaigns</a></div>
      <div class="cause-page-grid"><?php foreach ($categories as $c): ?><article class="cause-big-card reveal"><img src="<?= e(safe_image($c['image_path'] ?? null, 'images/demo/cause-education.jpg')) ?>" alt="<?= e($c['name']) ?>" loading="lazy"><div class="cause-big-overlay"></div><div><span><?= e($c['icon'] ?: '♡') ?></span><h2><?= e($c['name']) ?></h2><p><?= e($c['description'] ?? '') ?></p><a href="<?= url('campaigns') ?>" data-spa-link>See Campaigns →</a></div></article><?php endforeach; ?></div>
    </div>
    <?php endif; ?>

    <div class="wwd7-cta reveal">
      <div><h3>Together we can do more</h3><p>Give your time, skills or support and help 97 Foundation reach more families.</p></div>
      <div><a class="btn7 btn7-donate" href="<?= url('donate') ?>" data-spa-link><?= site_icon('favorite') ?><span>Donate Now</span></a><a class="btn7 btn7-login" href="<?= url('contact') ?>" data-spa-link>Contact Us</a></div>
    </div>

    <?php if (!empty($page['answer_summary'])): ?><aside class="answer-box reveal" style="margin-top:32px"><b>Quick Answer</b><p><?= e($page['answer_summary']) ?></p></aside><?php endif; ?>
  </div>
</section>
