<?php
$activeContentTab=in_array(($_GET['tab']??''),['home','pages','sections','slides'],true)?$_GET['tab']:'home';
if(isset($_GET['sections_page'])||isset($_GET['sections_per_page']))$activeContentTab='sections';
elseif(isset($_GET['slides_page'])||isset($_GET['slides_per_page']))$activeContentTab='slides';
elseif(isset($_GET['pages_page'])||isset($_GET['pages_per_page']))$activeContentTab='pages';
$homeSections=$homeSections??[];
?>
<div class="page-actions"><div><h2>Website Content Manager</h2><p>Manage the homepage, hero banner, public pages and reusable sections without editing source code.</p></div><a class="outline-link" href="<?=url('')?>" target="_blank">Preview Website ↗</a></div>
<div class="admin-tabs" data-admin-tabs>
  <button class="<?=$activeContentTab==='home'?'active':''?>" data-tab-target="home">Home Page</button>
  <button class="<?=$activeContentTab==='pages'?'active':''?>" data-tab-target="pages">Pages</button>
  <button class="<?=$activeContentTab==='sections'?'active':''?>" data-tab-target="sections">Content Sections</button>
  <button class="<?=$activeContentTab==='slides'?'active':''?>" data-tab-target="slides">Hero Banner</button>
</div>

<section class="tab-pane <?=$activeContentTab==='home'?'active':''?>" data-tab-pane="home">
<?php
$homeDefaults=[
'home_quick_help'=>['name'=>'Quick Card — Need Help','title'=>'I Need Help','eyebrow'=>'SUPPORT','content'=>'Get information, support and assistance from our team.','cta_text'=>'Get Help','cta_url'=>'contact','limit'=>3,'source'=>'Static homepage card','image'=>false],
'home_quick_donate'=>['name'=>'Quick Card — Donate','title'=>'I Want to Help','eyebrow'=>'DONATE','content'=>'Your donation can bring hope and meaningful change.','cta_text'=>'Donate Now','cta_url'=>'donate','limit'=>3,'source'=>'Static homepage card','image'=>false],
'home_quick_volunteer'=>['name'=>'Quick Card — Volunteer','title'=>'I Want to Get Involved','eyebrow'=>'VOLUNTEER','content'=>'Join the volunteer community and contribute your skills.','cta_text'=>'Join Now','cta_url'=>'volunteer','limit'=>3,'source'=>'Static homepage card','image'=>false],
'home_about'=>['name'=>'About Section','title'=>'Together, We Can Make a Difference','eyebrow'=>'ABOUT 97 FOUNDATION','content'=>'97 Foundation supports people in need, strengthens communities and creates meaningful change through compassion, community and action.','cta_text'=>'Learn About Us','cta_url'=>'about','limit'=>3,'source'=>'Homepage About image & content','image'=>true],
'home_causes'=>['name'=>'Causes Preview','title'=>'We Work For a Better Tomorrow','eyebrow'=>'OUR CAUSES','content'=>'Explore the Foundation areas of support.','cta_text'=>'Explore All Causes','cta_url'=>'causes','limit'=>4,'source'=>'Images/cards come from Cause Areas','source_url'=>'admin/causes','image'=>false],
'home_campaigns'=>['name'=>'Featured Campaigns','title'=>'Support Our Recent Campaigns','eyebrow'=>'FEATURED CAMPAIGNS','content'=>'Support active fundraising campaigns with verified donation totals.','cta_text'=>'View All Campaigns','cta_url'=>'campaigns','limit'=>3,'source'=>'Cards/images come from Campaigns','source_url'=>'admin/campaigns','image'=>false],
'home_gallery'=>['name'=>'Gallery Preview','title'=>'Moments That Matter','eyebrow'=>'OUR GALLERY','content'=>'Stories in pictures from Foundation activities and community impact.','cta_text'=>'Open Gallery','cta_url'=>'gallery','limit'=>6,'source'=>'Images come from Gallery','source_url'=>'admin/gallery','image'=>false],
'home_events'=>['name'=>'Events Preview','title'=>'Join Our Next Community Activities','eyebrow'=>'UPCOMING EVENTS','content'=>'New published events appear here automatically.','cta_text'=>'View All Events','cta_url'=>'events','limit'=>3,'source'=>'Published events/images appear automatically','source_url'=>'admin/events','image'=>false],
'home_impact'=>['name'=>'Impact Section','title'=>'Real stories. Real impact.','eyebrow'=>'OUR IMPACT','content'=>'Every approved donation, campaign and volunteer action is handled through a controlled workflow designed for transparency and accountability.','cta_text'=>'See Our Impact','cta_url'=>'causes','limit'=>3,'source'=>'Static homepage section','image'=>false],
'home_news'=>['name'=>'News & Blog Preview','title'=>'Latest From 97 Foundation','eyebrow'=>'NEWS & BLOG','content'=>'New published news and blog posts appear here automatically.','cta_text'=>'View All News & Blog','cta_url'=>'news','limit'=>3,'source'=>'Published news/images appear automatically','source_url'=>'admin/posts','image'=>false],
'home_newsletter'=>['name'=>'Stay Connected','title'=>'Stay Connected with 97 Foundation','eyebrow'=>'STAY CONNECTED','content'=>'Follow campaigns, events, volunteer activities and community updates.','cta_text'=>'Contact Us','cta_url'=>'contact','limit'=>3,'source'=>'Static homepage call-to-action','image'=>false],
];
?>
<div class="home-builder-intro admin-panel"><div><span class="home-builder-icon">⌂</span><div><h3>Professional Home Page Builder</h3><p>Edit homepage headings, descriptions, buttons, visibility, image and dynamic item counts. Hero images/content are managed in the <b>Hero Slider</b> tab.</p></div></div><a class="outline-link" href="<?=url('')?>" target="_blank">Preview Home ↗</a></div>
<div class="home-builder-grid">
<?php foreach($homeDefaults as $key=>$def):$row=array_merge($def,$homeSections[$key]??[]);?>
<section class="admin-panel home-builder-card">
  <div class="panel-head"><div><span class="status-badge <?=((int)($row['is_active']??1)===1)?'active':'inactive'?>"><?=((int)($row['is_active']??1)===1)?'Visible':'Hidden'?></span><h3><?=e($def['name'])?></h3><small><?=e($def['source'])?></small></div><?php if(!empty($def['source_url'])):?><a href="<?=url($def['source_url'])?>">Manage items ↗</a><?php endif;?></div>
  <?php if(!empty($def['image'])&&!empty($row['image_path'])):?><div class="home-builder-preview"><img src="<?=e(safe_image($row['image_path']))?>" alt="Current section image"></div><?php endif;?>
  <form method="post" enctype="multipart/form-data" action="<?=url('admin/content/save')?>" class="admin-form home-section-form"><?=csrf_field()?>
    <input type="hidden" name="section_key" value="<?=e($key)?>"><input type="hidden" name="return_tab" value="home"><input type="hidden" name="sort_order" value="<?=e((string)($row['sort_order']??0))?>">
    <label><span>Small Label / Eyebrow</span><input name="eyebrow" maxlength="100" value="<?=e($row['eyebrow']??'')?>"></label>
    <label><span>Items Shown</span><input type="number" name="item_limit" min="1" max="12" value="<?=e((string)($row['item_limit']??$def['limit']))?>"><small class="helper">Used for dynamic cards where applicable.</small></label>
    <label class="full"><span>Section Title</span><input name="title" maxlength="200" value="<?=e($row['title']??'')?>"></label>
    <label class="full"><span>Description / Content</span><textarea name="content" rows="4" maxlength="30000"><?=e($row['content']??'')?></textarea></label>
    <label><span>Button Text</span><input name="cta_text" maxlength="100" value="<?=e($row['cta_text']??'')?>"></label>
    <label><span>Button URL / Path</span><input name="cta_url" maxlength="255" value="<?=e($row['cta_url']??'')?>"></label>
    <?php if(!empty($def['image'])):?><label class="full"><span>Section Image</span><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"><small class="helper">Recommended 1200 × 800 px. The homepage frame uses professional cover fitting.</small></label><?php endif;?>
    <label class="toggle-line full"><input type="checkbox" name="is_active" value="1" <?=((int)($row['is_active']??1)===1)?'checked':''?>><span>Show this section on the homepage</span></label>
    <button class="admin-btn full">Save <?=e($def['name'])?></button>
  </form>
</section>
<?php endforeach;?>
</div>
<div class="home-builder-source-map admin-panel"><h3>Homepage image & content sources</h3><div><a href="<?=url('admin/content?tab=slides')?>"><b>Hero Slider</b><span>Hero images + overlay content</span></a><a href="<?=url('admin/causes')?>"><b>Cause Areas</b><span>Cause card images</span></a><a href="<?=url('admin/campaigns')?>"><b>Campaigns</b><span>Campaign cover images</span></a><a href="<?=url('admin/gallery')?>"><b>Gallery</b><span>Homepage gallery images</span></a><a href="<?=url('admin/events')?>"><b>Events</b><span>Published event images auto-feed home</span></a><a href="<?=url('admin/posts')?>"><b>News & Blog</b><span>Published featured images auto-feed home</span></a></div></div>
</section>

<section class="tab-pane <?=$activeContentTab==='pages'?'active':''?>" data-tab-pane="pages">
<div class="management-layout"><section class="admin-panel sticky-form"><div class="panel-head"><h3>Page Editor</h3><button class="outline-link mini" type="button" data-page-reset>New Page</button></div><form method="post" enctype="multipart/form-data" action="<?=url('admin/pages/save')?>" class="admin-form" data-page-form><?=csrf_field()?><label><span>Page Slug *</span><input name="slug" required pattern="[a-z0-9-]+" placeholder="about"></label><label><span>Display Order</span><input type="number" name="sort_order" value="0"></label><label class="full"><span>Page Title *</span><input name="title" required maxlength="200"></label><label><span>Eyebrow / Small Label</span><input name="eyebrow" maxlength="100"></label><label><span>Hero Title</span><input name="hero_title" maxlength="240"></label><label class="full"><span>Hero Subtitle</span><textarea name="hero_subtitle" rows="3" maxlength="600"></textarea></label><label class="full"><span>Page Content</span><textarea name="body" rows="9" maxlength="50000"></textarea></label><label class="full"><span>Page Feature Image</span><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"><small class="helper">Recommended 1200 × 800 px. Used in the page's premium framed/hero presentation with automatic cover fitting.</small></label><label class="full"><span>SEO Title</span><input name="meta_title" maxlength="240"></label><label class="full"><span>Meta Description</span><textarea name="meta_description" rows="3" maxlength="500"></textarea></label><label class="full"><span>AEO Quick Answer</span><textarea name="answer_summary" rows="4" maxlength="900"></textarea></label><label class="toggle-line full"><input type="checkbox" name="is_active" value="1" checked><span>Page active / publicly available</span></label><button class="admin-btn full">Save Page</button></form></section>
<section class="content-card-list"><?php foreach($pages as $p):$payload=e(json_encode(['slug'=>$p['slug'],'sort_order'=>$p['sort_order'],'title'=>$p['title'],'eyebrow'=>$p['eyebrow'],'hero_title'=>$p['hero_title'],'hero_subtitle'=>$p['hero_subtitle'],'body'=>$p['body'],'meta_title'=>$p['meta_title'],'meta_description'=>$p['meta_description'],'answer_summary'=>$p['answer_summary'],'is_active'=>(int)$p['is_active']],JSON_HEX_APOS|JSON_HEX_QUOT));?><article class="admin-panel content-summary-card"><div><span class="status-badge <?=$p['is_active']?'active':'inactive'?>"><?=$p['is_active']?'Active':'Inactive'?></span><code>/<?=e($p['slug'])?></code></div><h3><?=e($p['title'])?></h3><p><?=e(mb_strimwidth(strip_tags($p['body']??''),0,180,'…'))?></p><div class="card-admin-actions"><button class="outline-link" type="button" data-page-edit='<?=$payload?>'>✎ Edit</button><?php if(!in_array($p['slug'],['about','causes','campaigns','events','gallery','volunteer','donate','contact'],true)):?><form method="post" action="<?=url('admin/pages/delete')?>" data-confirm="Delete this page?"><?=csrf_field()?><input type="hidden" name="id" value="<?=$p['id']?>"><button class="danger-link">Delete</button></form><?php endif;?><a class="outline-link" href="<?=url($p['slug'])?>" target="_blank">Preview ↗</a></div></article><?php endforeach;?></section></div><?=admin_pagination($pagePagination??[])?></section>

<section class="tab-pane <?=$activeContentTab==='sections'?'active':''?>" data-tab-pane="sections">
<div class="management-layout"><section class="admin-panel sticky-form"><div class="panel-head"><h3>Reusable Section Editor</h3><button class="outline-link mini" type="button" data-section-reset>New Section</button></div><form method="post" enctype="multipart/form-data" action="<?=url('admin/content/save')?>" class="admin-form" data-section-form><?=csrf_field()?><input type="hidden" name="return_tab" value="sections"><label><span>Section Key *</span><input name="section_key" required pattern="[a-z0-9_]+" placeholder="home_about"></label><label><span>Display Order</span><input type="number" name="sort_order" value="0"></label><label><span>Eyebrow / Small Label</span><input name="eyebrow" maxlength="100"></label><label><span>Items Shown</span><input type="number" name="item_limit" min="1" max="12" value="3"></label><label class="full"><span>Title</span><input name="title" maxlength="200"></label><label class="full"><span>Content</span><textarea name="content" rows="8" maxlength="30000"></textarea></label><label><span>CTA Text</span><input name="cta_text" maxlength="100"></label><label><span>CTA URL / Path</span><input name="cta_url" maxlength="255"></label><label class="full"><span>Related Image</span><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp"><small class="helper">Recommended 1200 × 800 px. Automatically framed without stretching.</small></label><label class="toggle-line full"><input type="checkbox" name="is_active" value="1" checked><span>Section active</span></label><button class="admin-btn full">Save Content Section</button></form></section>
<section class="content-card-list"><?php foreach($sections as $s):$payload=e(json_encode(['section_key'=>$s['section_key'],'sort_order'=>$s['sort_order'],'eyebrow'=>$s['eyebrow']??'','item_limit'=>$s['item_limit']??3,'title'=>$s['title'],'content'=>$s['content'],'cta_text'=>$s['cta_text'],'cta_url'=>$s['cta_url'],'is_active'=>(int)$s['is_active']],JSON_HEX_APOS|JSON_HEX_QUOT));?><article class="admin-panel content-summary-card"><div><span class="status-badge <?=$s['is_active']?'active':'inactive'?>"><?=$s['is_active']?'Active':'Inactive'?></span><code><?=e($s['section_key'])?></code></div><h3><?=e($s['title']?:'Untitled Section')?></h3><p><?=e(mb_strimwidth(strip_tags($s['content']??''),0,180,'…'))?></p><div class="card-admin-actions"><button class="outline-link" type="button" data-section-edit='<?=$payload?>'>✎ Edit</button><form method="post" action="<?=url('admin/content/delete')?>" data-confirm="Delete this content section?"><?=csrf_field()?><input type="hidden" name="section_key" value="<?=e($s['section_key'])?>"><button class="danger-link">Delete</button></form></div></article><?php endforeach;?></section></div><?=admin_pagination($sectionPagination??[])?></section>

<section class="tab-pane <?=$activeContentTab==='slides'?'active':''?>" data-tab-pane="slides">
<?php
$hero=hero_settings();$heroSlides=hero_background_slides($hero);
$heroText=[
  ['eyebrow','Eyebrow label',40,'97 FOUNDATION'],['title_line_1','Headline — line 1',60,'Small Acts.'],['title_line_2','Headline — line 2',60,'Greater Impact.'],
];
?>
<form method="post" enctype="multipart/form-data" action="<?=url('admin/hero/save')?>" class="hero7-admin" data-hero7-admin><?=csrf_field()?>
  <div class="v7-card v7-intro">
    <span class="v7-intro-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 15 5-5 4 4 3-3 6 6"/><circle cx="15.5" cy="9.5" r="1.5"/></svg></span>
    <div><h3>Homepage Hero Banner</h3><p>Headline, buttons and the five-image background slider shown at the top of the homepage. Images slide horizontally in a continuous loop with five pagination dots.</p></div>
    <a class="outline-link" href="<?=url('')?>" target="_blank" rel="noopener">Preview Homepage ↗</a>
  </div>

  <div class="v7-grid-2">
    <section class="v7-card">
      <header class="v7-card-head"><div><h3>Banner Content</h3><p>Text shown over the background slider.</p></div></header>
      <div class="admin-form">
        <?php foreach($heroText as [$k,$label,$max,$ph]):?><label class="<?=$k==='eyebrow'?'full':''?>"><span><?=e($label)?></span><input name="<?=e($k)?>" maxlength="<?=$max?>" value="<?=e($hero[$k])?>" placeholder="<?=e($ph)?>" data-hero7-bind="<?=e($k)?>"></label><?php endforeach;?>
        <label class="full"><span>Description</span><textarea name="subtitle" rows="3" maxlength="320" data-hero7-bind="subtitle"><?=e($hero['subtitle'])?></textarea></label>
        <label><span>Primary button text</span><input name="primary_text" maxlength="40" value="<?=e($hero['primary_text'])?>"></label>
        <label><span>Primary button link</span><input name="primary_url" maxlength="255" value="<?=e($hero['primary_url'])?>" placeholder="volunteer"></label>
        <label><span>Secondary button text</span><input name="secondary_text" maxlength="40" value="<?=e($hero['secondary_text'])?>"></label>
        <label><span>Secondary button link</span><input name="secondary_url" maxlength="255" value="<?=e($hero['secondary_url'])?>" placeholder="about"></label>
        <label class="full"><span>Supporters line</span><input name="supporters_text" maxlength="80" value="<?=e($hero['supporters_text'])?>"></label>
        <?php for($h=1;$h<=4;$h++):?><label><span>Highlight <?=$h?></span><input name="highlight_<?=$h?>" maxlength="40" value="<?=e($hero['highlight_'.$h])?>"></label><?php endfor;?>
        <label class="full"><span>Bottom strip text</span><input name="strip_text" maxlength="80" value="<?=e($hero['strip_text'])?>"><small class="helper">Separate words with “·” — hearts are added automatically.</small></label>
        <small class="helper full">Links accept a page path such as <b>volunteer</b> or a full URL.</small>
      </div>
    </section>
    <section class="v7-card">
      <header class="v7-card-head"><div><h3>Display &amp; Preview</h3><p>Overlay strength and autoplay speed.</p></div></header>
      <div class="hero7-admin-preview" data-hero7-preview style="--o:<?=e(number_format(((int)$hero['overlay_opacity'])/100,2,'.',''))?>">
        <img src="<?=e($heroSlides[0]['desktop'])?>" alt="">
        <div class="hero7-admin-preview-shade"></div>
        <div class="hero7-admin-preview-copy"><small data-hero7-out="eyebrow"><?=e($hero['eyebrow'])?></small><b><span data-hero7-out="title_line_1"><?=e($hero['title_line_1'])?></span> <span data-hero7-out="title_line_2"><?=e($hero['title_line_2'])?></span></b><p data-hero7-out="subtitle"><?=e($hero['subtitle'])?></p></div>
        <div class="hero7-admin-preview-dots"><?php for($d=0;$d<HERO_SLIDE_COUNT;$d++):?><i class="<?=$d===0?'on':''?>"></i><?php endfor;?></div>
      </div>
      <div class="admin-form">
        <label class="full"><span>Green overlay opacity <output data-hero7-overlay-out><?=e($hero['overlay_opacity'])?>%</output></span><input type="range" name="overlay_opacity" min="40" max="95" step="1" value="<?=e($hero['overlay_opacity'])?>" data-hero7-overlay><small class="helper">Higher values give stronger contrast behind the text (recommended 75–88%).</small></label>
        <label><span>Autoplay interval</span><select name="interval_seconds"><?php foreach([4,5,6,7,8,10,12] as $sec):?><option value="<?=$sec?>" <?=(int)$hero['interval_seconds']===$sec?'selected':''?>><?=$sec?> seconds</option><?php endforeach;?></select></label>
        <label class="toggle-line"><input type="checkbox" name="show_circles" value="1" <?=$hero['show_circles']==='1'?'checked':''?>><span>Show the three photo circles (desktop)</span></label>
      </div>
    </section>
  </div>

  <section class="v7-card">
    <header class="v7-card-head"><div><h3>Background Slider — 5 Images</h3><p>Desktop 1920 × 900 px · optional mobile 900 × 1200 px · JPG, PNG or WEBP up to 5 MB. Empty slots use the official design photography.</p></div><span class="v7-pill"><?=count(array_filter($heroSlides,fn($s)=>$s['custom']))?> / <?=HERO_SLIDE_COUNT?> custom</span></header>
    <div class="hero7-slot-grid">
      <?php foreach($heroSlides as $slide):$i=$slide['index'];?>
      <article class="hero7-slot">
        <div class="hero7-slot-media"><img src="<?=e($slide['desktop'])?>" alt="" loading="lazy"><span class="hero7-slot-no"><?=$i?></span><span class="status-badge <?=$slide['custom']?'active':'inactive'?>"><?=$slide['custom']?'Custom':'Default'?></span></div>
        <div class="hero7-slot-body">
          <label class="v7-file"><span>Desktop image</span><input type="file" name="bg_image_<?=$i?>" accept=".jpg,.jpeg,.png,.webp"></label>
          <label class="v7-file"><span>Mobile image <em>optional</em></span><input type="file" name="bg_mobile_<?=$i?>" accept=".jpg,.jpeg,.png,.webp"></label>
          <label class="v7-field"><span>Image description (alt text)</span><input name="bg_alt_<?=$i?>" maxlength="140" value="<?=e($slide['alt'])?>"></label>
          <?php if($slide['custom']):?><label class="toggle-line"><input type="checkbox" name="reset_bg_<?=$i?>" value="1"><span>Restore default photo</span></label><?php endif;?>
        </div>
      </article>
      <?php endforeach;?>
    </div>
  </section>

  <section class="v7-card">
    <header class="v7-card-head"><div><h3>Feature Circles</h3><p>Three round photos on the right of the banner · square 400 × 400 px.</p></div></header>
    <div class="hero7-circle-grid">
      <?php for($c=1;$c<=3;$c++):$custom=trim($hero['circle_image_'.$c])!=='';?>
      <article class="hero7-circle-slot"><img src="<?=e(hero_circle_image($hero,$c))?>" alt="" loading="lazy"><div><b>Circle <?=$c?></b><label class="v7-file"><input type="file" name="circle_image_<?=$c?>" accept=".jpg,.jpeg,.png,.webp"></label><?php if($custom):?><label class="toggle-line"><input type="checkbox" name="reset_circle_<?=$c?>" value="1"><span>Restore default</span></label><?php endif;?></div></article>
      <?php endfor;?>
    </div>
  </section>

  <div class="v7-savebar"><span>Changes publish to the homepage immediately after saving.</span><button class="admin-btn">Save Hero Banner</button></div>
</form>
</section>