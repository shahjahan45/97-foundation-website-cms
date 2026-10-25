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
$quickKeys=[
 'home_quick_help'=>['green','♡'],
 'home_quick_donate'=>['gold','◇'],
 'home_quick_volunteer'=>['teal','♧'],
];
$hasQuick=false;foreach(array_keys($quickKeys) as $k)if($isOn($getSection($k))){$hasQuick=true;break;}
if($hasQuick):?>
<section class="quick-wrap"><div class="container quick-grid glass-panel reveal">
<?php foreach($quickKeys as $key=>[$tone,$icon]):$q=$getSection($key);if(!$isOn($q))continue;?>
<a class="quick-card <?=e($tone)?>" href="<?=url($q['cta_url']?:'contact')?>" data-spa-link><span class="quick-icon"><?=e($icon)?></span><div><h3><?=e($q['title'])?></h3><p><?=e($q['content'])?></p><b><?=e($q['cta_text'])?> →</b></div></a>
<?php endforeach;?>
</div></section>
<?php endif;?>

<section class="impact-strip"><div class="container impact-row reveal"><div><span>♧</span><b><?=number_format((int)($stats['volunteers']??0))?></b><small>Approved Volunteers</small></div><div><span>▣</span><b><?=number_format((int)($stats['campaigns']??0))?></b><small>Active Campaigns</small></div><div><span>♡</span><b><?=number_format((int)($stats['verified_donations']??0))?></b><small>Verified Donations</small></div><div><span>⌾</span><b><?=number_format((int)($stats['focus_areas']??0))?></b><small>Focus Areas</small></div></div></section>

<?php $about=$getSection('home_about');if($isOn($about)):?>
<section class="section home-about"><div class="container home-about-grid"><div class="home-about-frame reveal"><img src="<?=e(safe_image($about['image_path']??null,'images/demo/hero-children.jpg'))?>" alt="<?=e($about['title'])?>" loading="lazy"><span><b>97 FOUNDATION</b><small>Hope · Help · Humanity.</small></span></div><div class="home-about-copy reveal"><span class="eyebrow green-text"><?=e($about['eyebrow'])?></span><h2><?=e($about['title'])?></h2><p><?=nl2br(e($about['content']))?></p><?php if(!empty($about['cta_text'])):?><a class="btn btn-primary" href="<?=url($about['cta_url']?:'about')?>" data-spa-link><?=e($about['cta_text'])?> →</a><?php endif;?></div></div></section>
<?php endif;?>

<?php $sc=$getSection('home_causes');if($isOn($sc)):?>
<section class="section causes-preview"><div class="container causes-layout"><div class="section-intro reveal"><span class="eyebrow green-text"><?=e($sc['eyebrow'])?></span><h2><?=e($sc['title'])?></h2><i></i><p><?=e($sc['content'])?></p><?php if($sc['cta_text']):?><a class="btn btn-primary" href="<?=url($sc['cta_url']?:'causes')?>" data-spa-link><?=e($sc['cta_text'])?> →</a><?php endif;?></div><div class="cause-category-grid"><?php foreach($categories as $c):?><article class="cause-category reveal"><div class="category-photo"><img src="<?=e(safe_image($c['image_path']??null,'images/demo/cause-education.jpg'))?>" alt="<?=e($c['name'])?>" loading="lazy"><span><?=e($c['icon']?:'♡')?></span></div><div><h3><?=e($c['name'])?></h3><p><?=e($c['description']??'')?></p><a href="<?=url('campaigns')?>" data-spa-link>Learn More →</a></div></article><?php endforeach;?></div></div></section>
<?php endif;?>

<?php $sc=$getSection('home_campaigns');if($isOn($sc)):?>
<section class="section campaign-band"><div class="container"><div class="section-heading reveal"><span class="eyebrow green-text"><?=e($sc['eyebrow'])?></span><div><h2><?=e($sc['title'])?></h2><?php if($sc['cta_text']):?><a href="<?=url($sc['cta_url']?:'campaigns')?>" data-spa-link><?=e($sc['cta_text'])?> →</a><?php endif;?></div></div><div class="campaign-grid"><?php foreach($campaigns as $idx=>$c):$pct=$c['target_amount']>0?min(100,round($c['raised']/$c['target_amount']*100)):0;$fallback=['images/demo/campaign-families.jpg','images/demo/campaign-education.jpg','images/demo/campaign-health.jpg'][$idx%3];?><article class="campaign-card reveal"><a class="campaign-image" href="<?=url('campaigns/'.$c['slug'])?>" data-spa-link><img src="<?=e($c['cover_image']?public_upload($c['cover_image']):asset($fallback))?>" alt="<?=e($c['title'])?>" loading="lazy"><span><?=e(strtoupper($c['status']))?></span></a><div class="campaign-body"><h3><?=e($c['title'])?></h3><p><?=e($c['short_description']?:'Support this campaign and help create meaningful change.')?></p><div class="amount-line"><span>Raised <b><?=money($c['raised'],$c['currency'])?></b></span><b><?=$pct?>%</b></div><div class="progress"><i style="width:<?=$pct?>%"></i></div><div class="goal">Goal <?=money($c['target_amount'],$c['currency'])?></div><a class="btn btn-primary full" href="<?=url('donate?campaign='.$c['id'])?>">Donate Now ♡</a></div></article><?php endforeach;?><?php if(!$campaigns):?><div class="empty-state premium-empty">No public campaigns are active yet.</div><?php endif;?></div></div></section>
<?php endif;?>

<?php $sc=$getSection('home_gallery');if($isOn($sc)&&!empty($gallery)):?>
<section class="section gallery-preview"><div class="container"><div class="section-heading reveal"><span class="eyebrow green-text"><?=e($sc['eyebrow'])?></span><div><h2><?=e($sc['title'])?></h2><?php if($sc['cta_text']):?><a href="<?=url($sc['cta_url']?:'gallery')?>" data-spa-link><?=e($sc['cta_text'])?> →</a><?php endif;?></div></div><div class="mosaic-gallery"><?php foreach($gallery as $g):?><a class="gallery-tile reveal" href="<?=e(safe_image($g['image_path']))?>" data-lightbox><img src="<?=e(safe_image($g['image_path']))?>" alt="<?=e($g['title'])?>" loading="lazy"><span><b><?=e($g['title'])?></b><small><?=e($g['caption']??'')?></small></span></a><?php endforeach;?></div></div></section>
<?php endif;?>

<?php $sc=$getSection('home_events');if($isOn($sc)&&!empty($events)):?>
<section class="section home-events"><div class="container"><div class="section-heading reveal"><span class="eyebrow green-text"><?=e($sc['eyebrow'])?></span><div><h2><?=e($sc['title'])?></h2><?php if($sc['cta_text']):?><a href="<?=url($sc['cta_url']?:'events')?>" data-spa-link><?=e($sc['cta_text'])?> →</a><?php endif;?></div></div><p class="section-admin-copy reveal"><?=e($sc['content'])?></p><div class="home-event-grid"><?php foreach($events as $event):?><article class="home-event-card reveal"><div class="home-event-date"><b><?=e(date('d',strtotime($event['event_date'])))?></b><span><?=e(strtoupper(date('M',strtotime($event['event_date']))))?></span></div><div class="home-event-photo"><img src="<?=e($event['image_path']?public_upload($event['image_path']):asset('images/demo/cause-emergency.jpg'))?>" alt="<?=e($event['title'])?>" loading="lazy"></div><div class="home-event-copy"><span><?=e($event['location']?:'Location TBA')?></span><h3><?=e($event['title'])?></h3><p><?=e(mb_strimwidth(strip_tags($event['description']??''),0,120,'…'))?></p><a href="<?=url('events')?>" data-spa-link>Learn More →</a></div></article><?php endforeach;?></div></div></section>
<?php endif;?>

<?php $impact=$getSection('home_impact');if($isOn($impact)):?>
<section class="impact-story"><div class="container impact-story-grid reveal"><div><span class="eyebrow"><?=e($impact['eyebrow'])?></span><h2><?=e($impact['title'])?></h2><p><?=e($impact['content'])?></p><?php if($impact['cta_text']):?><a class="btn btn-gold" href="<?=url($impact['cta_url']?:'causes')?>" data-spa-link><?=e($impact['cta_text'])?> →</a><?php endif;?></div><div class="impact-stats"><div><b>✓</b><strong>Verified</strong><small>Donation workflow</small></div><div><b>⌾</b><strong>Protected</strong><small>Private uploads</small></div><div><b>♙</b><strong>Connected</strong><small>Volunteer portal</small></div></div></div></section>
<?php endif;?>

<?php $sc=$getSection('home_news');if($isOn($sc)&&!empty($posts)):?>
<section class="section home-news"><div class="container"><div class="section-heading reveal"><span class="eyebrow green-text"><?=e($sc['eyebrow'])?></span><div><h2><?=e($sc['title'])?></h2><?php if($sc['cta_text']):?><a href="<?=url($sc['cta_url']?:'news')?>" data-spa-link><?=e($sc['cta_text'])?> →</a><?php endif;?></div></div><p class="section-admin-copy reveal"><?=e($sc['content'])?></p><div class="home-news-grid"><?php foreach($posts as $post):?><article class="home-news-card reveal"><a href="<?=url('news/'.$post['slug'])?>" data-spa-link class="home-news-image"><?php if($post['featured_image']):?><img src="<?=e(public_upload($post['featured_image']))?>" alt="<?=e($post['title'])?>" loading="lazy"><?php else:?><span>97</span><?php endif;?><em><?=e(strtoupper($post['post_type']))?></em></a><div><small><?=e(date('d M Y',strtotime($post['published_at'])))?></small><h3><a href="<?=url('news/'.$post['slug'])?>" data-spa-link><?=e($post['title'])?></a></h3><p><?=e($post['excerpt']?:mb_strimwidth(strip_tags($post['content']??''),0,120,'…'))?></p><a href="<?=url('news/'.$post['slug'])?>" data-spa-link>Read More →</a></div></article><?php endforeach;?></div></div></section>
<?php endif;?>

<?php $sc=$getSection('home_newsletter');if($isOn($sc)):?>
<section class="newsletter"><div class="container glass-panel reveal"><span class="mail-icon">✉</span><div><h3><?=e($sc['title'])?></h3><p><?=e($sc['content'])?></p></div><?php if($sc['cta_text']):?><a class="btn btn-primary" href="<?=url($sc['cta_url']?:'contact')?>" data-spa-link><?=e($sc['cta_text'])?></a><?php endif;?></div></section>
<?php endif;?>
