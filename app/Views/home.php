<?php
$sections=$sections??[];
$hero=$hero??hero_settings($settings??null);$heroSlides=$heroSlides??hero_background_slides($hero);
$defaults=[
 'home_quick_help'=>['title'=>'I Need Help','eyebrow'=>'SUPPORT','content'=>'Get information, support and assistance from our team.','cta_text'=>'Get Help','cta_url'=>'contact','is_active'=>1],
 'home_quick_donate'=>['title'=>'I Want to Help','eyebrow'=>'DONATE','content'=>'Your donation can bring hope and meaningful change.','cta_text'=>'Donate Now','cta_url'=>'donate','is_active'=>1],
 'home_quick_volunteer'=>['title'=>'I Want to Get Involved','eyebrow'=>'VOLUNTEER','content'=>'Join the volunteer community and contribute your skills.','cta_text'=>'Join Now','cta_url'=>'volunteer','is_active'=>1],
 'home_about'=>['title'=>'Together, We Can Make a Difference','eyebrow'=>'ABOUT 97 FOUNDATION','content'=>'97 Foundation supports people in need, strengthens communities and creates meaningful change through compassion, community and action.','cta_text'=>'Learn About Us','cta_url'=>'about','image_path'=>'demo/hero-children.jpg','is_active'=>1],
 'home_causes'=>['title'=>'We Work For a Better Tomorrow','eyebrow'=>'OUR CAUSES','content'=>'Explore the Foundation areas of support.','cta_text'=>'Explore All Causes','cta_url'=>'causes','is_active'=>1],
 'home_campaigns'=>['title'=>'Support Our Recent Campaigns','eyebrow'=>'FEATURED CAMPAIGNS','content'=>'Support active fundraising campaigns with verified donation totals.','cta_text'=>'View All Campaigns','cta_url'=>'campaigns','is_active'=>1],
 'home_gallery'=>['title'=>'Moments That Matter','eyebrow'=>'OUR GALLERY','content'=>'Stories in pictures from Foundation activities and community impact.','cta_text'=>'Open Gallery','cta_url'=>'gallery','is_active'=>1],
 'home_events'=>['title'=>'Join Our Next Community Activities','eyebrow'=>'UPCOMING EVENTS','content'=>'New published events appear here automatically.','cta_text'=>'View All Events','cta_url'=>'events','is_active'=>1],
 'home_impact'=>['title'=>'Real stories. Real impact.','eyebrow'=>'OUR IMPACT','content'=>'Every approved donation, campaign and volunteer action is handled through a controlled workflow designed for transparency and accountability.','cta_text'=>'See Our Impact','cta_url'=>'causes','is_active'=>1],
 'home_news'=>['title'=>'Latest From 97 Foundation','eyebrow'=>'NEWS & BLOG','content'=>'New published news and blog posts appear here automatically.','cta_text'=>'View All News & Blog','cta_url'=>'news','is_active'=>1],
 'home_newsletter'=>['title'=>'Stay Connected with 97 Foundation','eyebrow'=>'STAY CONNECTED','content'=>'Follow campaigns, events, volunteer activities and community updates.','cta_text'=>'Contact Us','cta_url'=>'contact','is_active'=>1],
];
$getSection=function(string $key)use($sections,$defaults):array{return array_merge($defaults[$key]??[],$sections[$key]??[]);};
$isOn=function(array $section):bool{return !isset($section['is_active'])||(int)$section['is_active']===1;};
?>
<?php
$heroCount=count($heroSlides);
$overlay=max(40,min(95,(int)$hero['overlay_opacity']))/100;
$highlightIcons=[1=>'school',2=>'family_restroom',3=>'menu_book',4=>'handshake'];
$stripParts=array_values(array_filter(array_map('trim',preg_split('/[·•|♥,]+/u',(string)$hero['strip_text']))));
?>
<section class="hero7" data-hero7 role="region" aria-roledescription="carousel" aria-label="97 Foundation highlights" style="--hero7-overlay:<?=e(number_format($overlay,2,'.',''))?>" data-interval="<?=e($hero['interval_seconds'])?>">
  <div class="hero7-viewport" aria-hidden="true">
    <div class="hero7-track" data-hero7-track>
      <?php foreach($heroSlides as $i=>$slide):?>
      <div class="hero7-slide<?=$i===0?' is-active':''?>" data-hero7-slide>
        <picture><?php if($slide['mobile']!==''):?><source media="(max-width: 768px)" srcset="<?=e($slide['mobile'])?>"><?php endif;?><img src="<?=e($slide['desktop'])?>" alt="" width="1920" height="900" decoding="async" <?=$i===0?'loading="eager" fetchpriority="high"':'loading="lazy" fetchpriority="low"'?>></picture>
      </div>
      <?php endforeach;?>
    </div>
  </div>
  <div class="hero7-overlay" aria-hidden="true"></div>
  <div class="hero7-divider" aria-hidden="true"></div>
  <div class="container hero7-inner">
    <div class="hero7-copy">
      <p class="hero7-eyebrow"><?=e($hero['eyebrow'])?></p>
      <h1 class="hero7-title"><span><?=e($hero['title_line_1'])?></span><?php if(trim($hero['title_line_2'])!==''):?> <span><?=e($hero['title_line_2'])?></span><?php endif;?></h1>
      <?php if(trim($hero['subtitle'])!==''):?><p class="hero7-lead"><?=e($hero['subtitle'])?></p><?php endif;?>
      <div class="hero7-actions">
        <?php if(trim($hero['primary_text'])!==''):?><a class="btn7 btn7-donate btn7-lg" href="<?=e(site_link($hero['primary_url'],'volunteer'))?>" data-spa-link><?=site_icon('favorite')?><span><?=e($hero['primary_text'])?></span></a><?php endif;?>
        <?php if(trim($hero['secondary_text'])!==''):?><a class="btn7 btn7-ghost btn7-lg" href="<?=e(site_link($hero['secondary_url'],'about'))?>" data-spa-link><?=site_icon('diamond')?><span><?=e($hero['secondary_text'])?></span></a><?php endif;?>
      </div>
      <?php if(trim($hero['supporters_text'])!==''):?><div class="hero7-supporters"><span class="hero7-faces" aria-hidden="true"><i class="is-gold">97</i><i class="is-green"><?=site_icon('favorite')?></i><i class="is-teal">+</i></span><b><?=e($hero['supporters_text'])?></b></div><?php endif;?>
      <ul class="hero7-highlights">
        <?php for($h=1;$h<=4;$h++):if(trim($hero['highlight_'.$h])==='')continue;?>
        <li><span class="hero7-highlight-icon"><?=site_icon($highlightIcons[$h])?></span><span><?=e($hero['highlight_'.$h])?></span></li>
        <?php endfor;?>
      </ul>
    </div>
    <?php if($hero['show_circles']==='1'):?>
    <div class="hero7-circles" aria-hidden="true">
      <?php for($c=1;$c<=3;$c++):?><span class="hero7-circle"><img src="<?=e(hero_circle_image($hero,$c))?>" alt="" width="160" height="160" loading="<?=$c===1?'eager':'lazy'?>" decoding="async"></span><?php endfor;?>
    </div>
    <?php endif;?>
  </div>
  <div class="hero7-bar">
    <div class="container hero7-bar-inner">
      <span class="hero7-counter" aria-hidden="true"><b data-hero7-current>01</b><i></i><?=e(str_pad((string)$heroCount,2,'0',STR_PAD_LEFT))?></span>
      <?php if($heroCount>1):?><div class="hero7-dots" role="group" aria-label="Choose background image">
        <?php foreach($heroSlides as $i=>$slide):?><button type="button" class="hero7-dot<?=$i===0?' is-active':''?>" data-hero7-dot="<?=$i?>" aria-label="Show background image <?=$i+1?> of <?=$heroCount?>"<?=$i===0?' aria-current="true"':''?>><span></span></button><?php endforeach;?>
      </div><?php endif;?>
      <p class="hero7-strip"><?php foreach($stripParts as $k=>$part):?><?php if($k>0):?><?=site_icon('favorite','hero7-strip-heart')?><?php endif;?><span><?=e($part)?></span><?php endforeach;?></p>
    </div>
  </div>
  <p class="sr-only" aria-live="polite" data-hero7-live></p>
</section>


<?php
/* ==========================================================================
   V7.2 homepage sections — all copy stays admin-managed (Website Content).
   ========================================================================== */
$arrow = site_line_icon('arrow');
$sectionHead = static function (array $sc, string $fallbackUrl, string $extraClass = '') use ($arrow): string {
    $html = '<header class="h7-head ' . e($extraClass) . '" data-reveal-group>';
    $html .= '<div class="h7-head-main">';
    if (trim((string)($sc['eyebrow'] ?? '')) !== '') $html .= '<p class="h7-eyebrow" data-reveal>' . e($sc['eyebrow']) . '</p>';
    $html .= '<h2 class="h7-h2" data-reveal>' . e($sc['title'] ?? '') . '</h2></div>';
    $copy = trim((string)($sc['content'] ?? ''));
    $cta = trim((string)($sc['cta_text'] ?? ''));
    if ($copy !== '' || $cta !== '') {
        $html .= '<div class="h7-head-side" data-reveal>';
        if ($copy !== '') $html .= '<p>' . e($copy) . '</p>';
        if ($cta !== '') $html .= '<a class="h7-link" href="' . url(($sc['cta_url'] ?? '') ?: $fallbackUrl) . '" data-spa-link><span>' . e($cta) . '</span>' . $arrow . '</a>';
        $html .= '</div>';
    }
    return $html . '</header>';
};
?>

<?php
$quickKeys = [
    'home_quick_help' => ['tone-green', 'support'],
    'home_quick_donate' => ['tone-gold', 'donate'],
    'home_quick_volunteer' => ['tone-teal', 'people'],
];
$quickItems = [];
foreach ($quickKeys as $key => $meta) { $q = $getSection($key); if ($isOn($q)) $quickItems[$key] = [$q, $meta]; }
if ($quickItems): ?>
<section class="h7-paths" aria-label="Ways to connect with 97 Foundation">
  <div class="container">
    <div class="h7-paths-card is-count-<?= count($quickItems) ?>" data-reveal-group>
      <?php foreach ($quickItems as $key => [$q, [$tone, $icon]]): ?>
      <a class="h7-path <?= e($tone) ?>" href="<?= url($q['cta_url'] ?: 'contact') ?>" data-spa-link data-reveal>
        <span class="h7-path-icon"><?= site_line_icon($icon) ?></span>
        <span class="h7-path-copy">
          <?php if (trim((string)($q['eyebrow'] ?? '')) !== ''): ?><small><?= e($q['eyebrow']) ?></small><?php endif; ?>
          <h3><?= e($q['title']) ?></h3>
          <span class="h7-path-text"><?= e($q['content']) ?></span>
          <span class="h7-path-cta"><span><?= e($q['cta_text'] ?: 'Learn more') ?></span><?= $arrow ?></span>
        </span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
// Only real, non-zero numbers are shown; the band hides itself on a brand-new site.
$statItems = array_values(array_filter([
    ['people', (int)($stats['volunteers'] ?? 0), 'Approved volunteers'],
    ['campaign', (int)($stats['campaigns'] ?? 0), 'Active campaigns'],
    ['shield', (int)($stats['verified_donations'] ?? 0), 'Verified donations'],
    ['target', (int)($stats['focus_areas'] ?? 0), 'Focus areas'],
], static fn($s) => $s[1] > 0));
if ($statItems): ?>
<section class="h7-stats" aria-label="97 Foundation in numbers">
  <div class="container">
    <div class="h7-stats-band" style="--n:<?= count($statItems) ?>" data-reveal-group>
      <?php foreach ($statItems as [$icon, $value, $label]): ?>
      <div class="h7-stat" data-reveal>
        <span class="h7-stat-icon"><?= site_line_icon($icon) ?></span>
        <div><b data-count="<?= $value ?>"><?= number_format($value) ?></b><small><?= e($label) ?></small></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php $about = $getSection('home_about'); if ($isOn($about)):
$focusList = [];
for ($h = 1; $h <= 4; $h++) { $t = trim((string)($hero['highlight_' . $h] ?? '')); if ($t !== '') $focusList[] = $t; }
?>
<section class="h7-sec h7-about">
  <div class="container h7-about-grid">
    <div class="h7-about-media" data-reveal="zoom">
      <span class="h7-about-shape" aria-hidden="true"></span>
      <figure class="h7-about-frame"><img src="<?= e(safe_image($about['image_path'] ?? null, 'images/demo/hero-children.jpg')) ?>" alt="<?= e($about['title']) ?>" width="1200" height="960" loading="lazy" decoding="async" data-parallax="0.06"></figure>
      <div class="h7-about-badge"><span class="h7-about-badge-mark"><?= site_line_icon('heart') ?></span><div><b>97 Foundation</b><small>Hope · Help · Humanity</small></div></div>
    </div>
    <div class="h7-about-copy" data-reveal-group>
      <?php if (trim((string)$about['eyebrow']) !== ''): ?><p class="h7-eyebrow" data-reveal><?= e($about['eyebrow']) ?></p><?php endif; ?>
      <h2 class="h7-h2" data-reveal><?= e($about['title']) ?></h2>
      <p class="h7-about-text" data-reveal><?= nl2br(e($about['content'])) ?></p>
      <?php if ($focusList): ?>
      <ul class="h7-checks" data-reveal aria-label="What we focus on">
        <?php foreach ($focusList as $item): ?><li><?= site_line_icon('check') ?><span><?= e($item) ?></span></li><?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <?php if (!empty($about['cta_text'])): ?><div data-reveal><a class="h7-btn h7-btn-primary" href="<?= url($about['cta_url'] ?: 'about') ?>" data-spa-link><span><?= e($about['cta_text']) ?></span><?= $arrow ?></a></div><?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php $sc = $getSection('home_causes'); if ($isOn($sc) && !empty($categories)): ?>
<section class="h7-sec h7-causes is-mist">
  <div class="container">
    <?= $sectionHead($sc, 'causes') ?>
    <div class="h7-cause-grid" data-reveal-group>
      <?php foreach ($categories as $c): ?>
      <a class="h7-cause" href="<?= url(($fundraisingOn ?? true) ? 'campaigns' : 'causes') ?>" data-spa-link data-reveal>
        <img src="<?= e(safe_image($c['image_path'] ?? null, 'images/demo/cause-education.jpg')) ?>" alt="" width="600" height="800" loading="lazy" decoding="async">
        <span class="h7-cause-icon"><?= site_line_icon(home_cause_icon((string)$c['name'])) ?></span>
        <span class="h7-cause-body">
          <b><?= e($c['name']) ?></b>
          <?php if (trim((string)($c['description'] ?? '')) !== ''): ?><span class="h7-cause-desc"><span><?= e($c['description']) ?></span></span><?php endif; ?>
          <span class="h7-cause-more"><span><?= ($fundraisingOn ?? true) ? 'See campaigns' : 'Learn more' ?></span><?= $arrow ?></span>
        </span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php $sc = $getSection('home_campaigns'); if ($isOn($sc) && ($fundraisingOn ?? true)): $campCount = count($campaigns ?? []); ?>
<section class="h7-sec h7-campaigns">
  <div class="container">
    <?= $sectionHead($sc, 'campaigns') ?>
    <?php if ($campCount): ?>
    <div class="h7-camp-grid is-count-<?= min(3, $campCount) ?>" data-reveal-group>
      <?php foreach ($campaigns as $idx => $c):
        $pct = $c['target_amount'] > 0 ? (int)min(100, round($c['raised'] / $c['target_amount'] * 100)) : 0;
        $fallback = ['images/demo/campaign-families.jpg', 'images/demo/campaign-education.jpg', 'images/demo/campaign-health.jpg'][$idx % 3];
        $link = url('campaigns/' . $c['slug']); ?>
      <article class="h7-camp" data-reveal>
        <a class="h7-camp-media" href="<?= $link ?>" data-spa-link tabindex="-1" aria-hidden="true"><img src="<?= e($c['cover_image'] ? public_upload($c['cover_image']) : asset($fallback)) ?>" alt="" width="800" height="500" loading="lazy" decoding="async"><span class="h7-chip"><?= e(ucfirst((string)$c['status'])) ?></span></a>
        <div class="h7-camp-body">
          <h3><a href="<?= $link ?>" data-spa-link><?= e($c['title']) ?></a></h3>
          <p><?= e($c['short_description'] ?: 'Support this campaign and help create meaningful change.') ?></p>
          <div class="h7-progress" data-progress>
            <div class="h7-progress-top"><span>Raised <b><?= money($c['raised'], $c['currency']) ?></b></span><b class="h7-progress-pct"><span data-count="<?= $pct ?>"><?= $pct ?></span>%</b></div>
            <div class="h7-progress-track" role="progressbar" aria-label="<?= e($c['title']) ?> funding progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $pct ?>"><i style="--p:<?= $pct ?>%"></i></div>
            <small>Goal <?= money($c['target_amount'], $c['currency']) ?></small>
          </div>
          <div class="h7-camp-actions">
            <a class="h7-btn h7-btn-primary" href="<?= url('donate?campaign=' . $c['id']) ?>"><?= site_line_icon('heart') ?><span>Donate now</span></a>
            <a class="h7-link" href="<?= $link ?>" data-spa-link><span>View details</span><?= $arrow ?></a>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="h7-empty" data-reveal><span><?= site_line_icon('campaign') ?></span><div><b>New campaigns are on the way</b><p>There are no active campaigns right now. You can still donate to the Foundation's general fund.</p></div><a class="h7-btn h7-btn-primary" href="<?= url('donate') ?>"><span>Donate now</span><?= $arrow ?></a></div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<?php $sc = $getSection('home_gallery'); if ($isOn($sc) && !empty($gallery)): ?>
<section class="h7-sec h7-gallery is-mist">
  <div class="container">
    <?= $sectionHead($sc, 'gallery') ?>
    <div class="h7-gallery-grid is-count-<?= min(6, count($gallery)) ?>" data-reveal-group>
      <?php foreach ($gallery as $g): $src = safe_image($g['image_path']); ?>
      <a class="h7-tile" href="<?= e($src) ?>" data-lightbox data-title="<?= e($g['title']) ?>" data-caption="<?= e($g['caption'] ?? '') ?>" data-reveal="wipe">
        <img src="<?= e($src) ?>" alt="<?= e($g['title']) ?>" width="800" height="600" loading="lazy" decoding="async">
        <span class="h7-tile-cap"><b><?= e($g['title']) ?></b><?php if (trim((string)($g['caption'] ?? '')) !== ''): ?><small><?= e($g['caption']) ?></small><?php endif; ?></span>
        <span class="h7-tile-zoom"><?= site_line_icon('expand') ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php $sc = $getSection('home_events'); if ($isOn($sc) && !empty($events)): ?>
<section class="h7-sec h7-events">
  <div class="container">
    <?= $sectionHead($sc, 'events') ?>
    <div class="h7-card-grid" data-reveal-group>
      <?php foreach ($events as $event): $ts = strtotime((string)$event['event_date']); ?>
      <article class="h7-card h7-event" data-reveal>
        <a class="h7-card-media" href="<?= url('events') ?>" data-spa-link tabindex="-1" aria-hidden="true"><img src="<?= e($event['image_path'] ? public_upload($event['image_path']) : asset('images/demo/cause-emergency.jpg')) ?>" alt="" width="800" height="500" loading="lazy" decoding="async"><span class="h7-event-date"><b><?= e(date('d', $ts)) ?></b><span><?= e(date('M', $ts)) ?></span></span></a>
        <div class="h7-card-body">
          <span class="h7-meta"><?= site_line_icon('pin') ?><?= e($event['location'] ?: 'Location to be announced') ?></span>
          <h3><a href="<?= url('events') ?>" data-spa-link><?= e($event['title']) ?></a></h3>
          <p><?= e(mb_strimwidth(strip_tags($event['description'] ?? ''), 0, 130, '…')) ?></p>
          <a class="h7-link" href="<?= url('events') ?>" data-spa-link><span>Event details</span><?= $arrow ?></a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php $impact = $getSection('home_impact'); if ($isOn($impact)): ?>
<section class="h7-impact">
  <span class="h7-impact-glow" aria-hidden="true"><i></i><i></i></span>
  <div class="container h7-impact-grid">
    <div class="h7-impact-copy" data-reveal-group>
      <?php if (trim((string)$impact['eyebrow']) !== ''): ?><p class="h7-eyebrow is-light" data-reveal><?= e($impact['eyebrow']) ?></p><?php endif; ?>
      <h2 class="h7-h2" data-reveal><?= e($impact['title']) ?></h2>
      <p class="h7-impact-text" data-reveal><?= e($impact['content']) ?></p>
      <?php if ($impact['cta_text']): ?><div data-reveal><a class="h7-btn h7-btn-gold" href="<?= url($impact['cta_url'] ?: 'causes') ?>" data-spa-link><span><?= e($impact['cta_text']) ?></span><?= $arrow ?></a></div><?php endif; ?>
    </div>
    <ul class="h7-pillars" data-reveal-group>
      <li data-reveal="left"><span class="h7-pillar-icon"><?= site_line_icon('shield') ?></span><b>Verified</b><small>Donations are checked and approved before totals update.</small></li>
      <li data-reveal="left"><span class="h7-pillar-icon"><?= site_line_icon('lock') ?></span><b>Protected</b><small>Receipts and personal documents stay in private storage.</small></li>
      <li data-reveal="left"><span class="h7-pillar-icon"><?= site_line_icon('network') ?></span><b>Connected</b><small>Volunteers follow their activities in their own portal.</small></li>
    </ul>
  </div>
</section>
<?php endif; ?>

<?php $sc = $getSection('home_news'); if ($isOn($sc) && !empty($posts)): ?>
<section class="h7-sec h7-news">
  <div class="container">
    <?= $sectionHead($sc, 'news') ?>
    <div class="h7-card-grid" data-reveal-group>
      <?php foreach ($posts as $np): $postUrl = url('news/' . $np['slug']); ?>
      <article class="h7-card h7-post" data-reveal>
        <a class="h7-card-media" href="<?= $postUrl ?>" data-spa-link tabindex="-1" aria-hidden="true"><?php if ($np['featured_image']): ?><img src="<?= e(public_upload($np['featured_image'])) ?>" alt="" width="800" height="500" loading="lazy" decoding="async"><?php else: ?><span class="h7-post-mark">97</span><?php endif; ?><span class="h7-chip is-plain"><?= e(ucfirst((string)$np['post_type'])) ?></span></a>
        <div class="h7-card-body">
          <span class="h7-meta"><?= site_line_icon('calendar') ?><time datetime="<?= e(date('Y-m-d', strtotime($np['published_at']))) ?>"><?= e(date('d M Y', strtotime($np['published_at']))) ?></time></span>
          <h3><a href="<?= $postUrl ?>" data-spa-link><?= e($np['title']) ?></a></h3>
          <p><?= e($np['excerpt'] ?: mb_strimwidth(strip_tags($np['content'] ?? ''), 0, 130, '…')) ?></p>
          <a class="h7-link" href="<?= $postUrl ?>" data-spa-link><span>Read article</span><?= $arrow ?></a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php $sc = $getSection('home_newsletter'); if ($isOn($sc)): ?>
<section class="h7-cta-wrap">
  <div class="container">
    <div class="h7-cta" data-reveal="zoom">
      <span class="h7-cta-icon"><?= site_line_icon('mail') ?></span>
      <div class="h7-cta-copy"><h2><?= e($sc['title']) ?></h2><?php if (trim((string)$sc['content']) !== ''): ?><p><?= e($sc['content']) ?></p><?php endif; ?></div>
      <div class="h7-cta-actions">
        <?php if ($sc['cta_text']): ?><a class="h7-btn h7-btn-light" href="<?= url($sc['cta_url'] ?: 'contact') ?>" data-spa-link><span><?= e($sc['cta_text']) ?></span><?= $arrow ?></a><?php endif; ?>
        <a class="h7-btn h7-btn-outline-light" href="<?= url('donate') ?>" data-spa-link><?= site_line_icon('heart') ?><span>Donate now</span></a>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>
