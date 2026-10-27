<?php
$foundation=$settings['foundation']??[];
$fundraisingOn7=\App\Core\Fundraising::enabled();$donationsOpen7=\App\Core\Fundraising::donationsOpen();
$seoSettings=$settings['seo']??[];
$seoData=$seo??[];
$pageTitle=$seoData['title']??($title??($seoSettings['default_title']??($foundation['name']??'97 Foundation')));
$description=$seoData['description']??($seoSettings['default_description']??'97 Foundation — Hope. Help. Humanity.');
$canonical=!empty($seoData['canonical'])?(string)$seoData['canonical']:absolute_url(ltrim(current_path(),'/'));
$seoType=in_array($seoData['type']??'',['article','website'],true)?$seoData['type']:'website';
// Private and transactional pages stay out of search results.
$noindex=!empty($seoData['noindex'])||(bool)preg_match('#^/(login|logout|member(/|$)|portal(/|$)|admin(/|$)|donate/(payment|online)(/|$)|verify(/|$)|scholarships/submitted(/|$))#',current_path());
$logo=$foundation['logo_path']??'';$logoEnabled=($foundation['logo_enabled']??'1')==='1';$favicon=$foundation['favicon_path']??'';
$orgName=$foundation['name']??'97 Foundation';$orgWebsite=trim((string)($foundation['website']??''))?:absolute_url('');
$socialImage=$seoData['image']??($seoSettings['social_image']??'');$socialImageUrl=$socialImage?(preg_match('~^https?://~i',(string)$socialImage)?(string)$socialImage:absolute_url('public/uploads/'.ltrim((string)$socialImage,'/'))):'';
if(!$socialImageUrl)$socialImageUrl=absolute_url('public/assets/images/hero/hero-1.jpg');
$targetCountry=$seoSettings['target_country']??'Sri Lanka';$targetDistrict=$seoSettings['target_district']??'Polonnaruwa';$targetLocality=$seoSettings['target_locality']??'Thambala';$keywords=$seoSettings['keywords']??'';
$orgAddress=$foundation['address']??($targetLocality.', '.$targetDistrict.', '.$targetCountry);
$schema=[
  '@context'=>'https://schema.org','@type'=>'NGO','name'=>$orgName,'url'=>$orgWebsite,
  'description'=>$description,'slogan'=>$foundation['tagline']??null,'email'=>$foundation['email']??null,'telephone'=>$foundation['phone']??null,
  'logo'=>$logo?absolute_url('public/uploads/'.ltrim((string)$logo,'/')):null,
  'image'=>$socialImageUrl?:null,
  'address'=>['@type'=>'PostalAddress','streetAddress'=>$orgAddress,'addressLocality'=>$targetLocality,'addressRegion'=>$targetDistrict,'addressCountry'=>$targetCountry],
  'areaServed'=>['@type'=>'Place','name'=>$targetLocality.', '.$targetDistrict.', '.$targetCountry],
];
$socialLinks=\App\Core\Database::all('SELECT * FROM social_links WHERE is_active=1 ORDER BY sort_order,id');
$schema['sameAs']=array_values(array_filter(array_map(fn($row)=>trim((string)($row['url']??'')),$socialLinks)));
$breadcrumbSchema=null;if(!empty($seoData['breadcrumbs'])&&is_array($seoData['breadcrumbs'])){$items=[];foreach(array_values($seoData['breadcrumbs']) as $i=>$bc){$items[]=['@type'=>'ListItem','position'=>$i+1,'name'=>(string)$bc[0],'item'=>absolute_url((string)$bc[1])];}$breadcrumbSchema=['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>$items];}
$websiteSchema=['@context'=>'https://schema.org','@type'=>'WebSite','@id'=>$orgWebsite.'#website','name'=>$orgName,'url'=>$orgWebsite,'inLanguage'=>'en-LK','publisher'=>['@type'=>'Organization','name'=>$orgName,'url'=>$orgWebsite]];
$pageSchema=['@context'=>'https://schema.org','@type'=>'WebPage','name'=>$pageTitle,'description'=>$description,'url'=>$canonical,'isPartOf'=>['@type'=>'WebSite','name'=>$orgName,'url'=>$orgWebsite],'about'=>['@type'=>'Thing','name'=>$targetLocality.', '.$targetDistrict.', '.$targetCountry]]; if(!empty($post)&&isset($article)&&$seoType==='article'){ $pageSchema=['@context'=>'https://schema.org','@type'=>($post['post_type']??'')==='blog'?'BlogPosting':'NewsArticle','headline'=>mb_substr((string)$post['title'],0,110),'description'=>$description,'datePublished'=>date('c',strtotime((string)$post['published_at'])),'dateModified'=>date('c',strtotime((string)($post['updated_at']??$post['published_at']))),'mainEntityOfPage'=>['@type'=>'WebPage','@id'=>$canonical],'url'=>$canonical,'inLanguage'=>'en-LK','articleSection'=>$seoData['section']??null,'wordCount'=>str_word_count(strip_tags((string)($post['content']??''))),'author'=>['@type'=>'Organization','name'=>$orgName,'url'=>$orgWebsite],'publisher'=>['@type'=>'Organization','name'=>$orgName,'url'=>$orgWebsite,'logo'=>['@type'=>'ImageObject','url'=>$logo?absolute_url('public/uploads/'.ltrim((string)$logo,'/')):absolute_url('public/assets/images/brand/97-foundation-logo.png')]]]; if($socialImageUrl)$pageSchema['image']=[$socialImageUrl]; $pageSchema=array_filter($pageSchema,static fn($v)=>$v!==null); } elseif(!empty($page['answer_summary'])) $pageSchema['abstract']=(string)$page['answer_summary']; elseif(!empty($seoSettings['answer_summary'])) $pageSchema['abstract']=(string)$seoSettings['answer_summary'];
?>
<!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#1a3d28"><meta property="og:locale" content="en_LK"><?php $tabIcon=$favicon?public_upload($favicon):($logo?public_upload($logo):brand_asset('icon')); if($tabIcon):?><link rel="icon" type="image/png" href="<?=e($tabIcon)?>"><link rel="shortcut icon" href="<?=e($tabIcon)?>"><link rel="apple-touch-icon" sizes="180x180" href="<?=e($tabIcon)?>"><?php endif;?>
<title><?=e($pageTitle)?></title><meta name="description" content="<?=e($description)?>"><?php if($keywords):?><meta name="keywords" content="<?=e($keywords)?>"><?php endif;?><meta name="robots" content="<?=$noindex?'noindex,nofollow':'index,follow,max-image-preview:large,max-snippet:-1'?>"><link rel="canonical" href="<?=e($canonical)?>">
<meta name="geo.region" content="<?=e($targetCountry.'-'.$targetDistrict)?>"><meta name="geo.placename" content="<?=e($targetLocality.', '.$targetDistrict)?>"><meta name="application-name" content="<?=e($orgName)?>">
<meta property="og:type" content="<?=e($seoType)?>"><meta property="og:title" content="<?=e($pageTitle)?>"><meta property="og:description" content="<?=e($description)?>"><meta property="og:url" content="<?=e($canonical)?>"><meta property="og:site_name" content="<?=e($orgName)?>"><?php if($socialImageUrl):?><meta property="og:image" content="<?=e($socialImageUrl)?>"><meta property="og:image:alt" content="<?=e($seoData['image_alt']??$pageTitle)?>"><?php endif;?><?php if($seoType==='article'&&!empty($seoData['published'])):?><meta property="article:published_time" content="<?=e(date('c',strtotime((string)$seoData['published'])))?>"><meta property="article:modified_time" content="<?=e(date('c',strtotime((string)($seoData['modified']??$seoData['published']))))?>"><?php if(!empty($seoData['section'])):?><meta property="article:section" content="<?=e($seoData['section'])?>"><?php endif;?><meta property="article:publisher" content="<?=e($orgWebsite)?>"><?php endif;?>
<?php if(!empty($heroPreloadUrl)):?><link rel="preload" as="image" href="<?=e($heroPreloadUrl)?>" fetchpriority="high" media="(min-width: 769px)"><?php endif;?>
<meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="<?=e($pageTitle)?>"><meta name="twitter:description" content="<?=e($description)?>"><?php if($socialImageUrl):?><meta name="twitter:image" content="<?=e($socialImageUrl)?>"><?php endif;?>
<script type="application/ld+json"><?=seo_json($schema)?></script><?php if(isset($websiteSchema)):?><script type="application/ld+json"><?=seo_json($websiteSchema)?></script><?php endif;?><script type="application/ld+json" data-page-schema><?=seo_json($pageSchema)?></script><?php if($breadcrumbSchema):?><script type="application/ld+json" data-breadcrumb-schema><?=seo_json($breadcrumbSchema)?></script><?php endif;?>
<script src="<?=asset('js/motion-flag.js')?>"></script><link rel="preload" href="<?=url('public/assets/fonts/inter-latin.woff2')?>" as="font" type="font/woff2" crossorigin><link rel="stylesheet" href="<?=asset('css/app.css')?>"><link rel="stylesheet" href="<?=asset('css/site-v7.css')?>"><link rel="stylesheet" href="<?=asset('css/site-home.css')?>"><link rel="stylesheet" href="<?=asset('css/site-v73.css')?>"></head><body><div class="scroll7-progress" aria-hidden="true"><i data-scroll-progress></i></div>
<?php
$brandLogo=brand_logo_url($foundation);$brandWordmark=brand_wordmark_url($foundation);
$contactEmail=trim((string)($foundation['email']??''))?:'97found@gmail.com';
$contactPhone=trim((string)($foundation['phone']??''))?:'+94 71 258 3411';
$facebookUrl=trim((string)($foundation['facebook']??''));
$primaryNav=site_primary_nav();
$headerBrand=header_brand_settings($settings??null);
?>
<div id="page-loader" class="page-loader show" aria-hidden="true"><div class="loader-brand"><?php if($logoEnabled):?><img src="<?=e($brandLogo)?>" alt=""><?php else:?><span class="loader-97">97</span><?php endif;?><i></i><b><?=e(strtoupper($orgName))?></b><small><?=e($foundation['tagline']??'Hope · Help · Humanity.')?></small></div></div>
<a class="skip-link" href="#main-content">Skip to main content</a>
<div class="topbar7" role="region" aria-label="Contact information"><div class="container topbar7-inner">
  <p class="topbar7-message"><?=site_icon('favorite','topbar7-heart')?><span>Together, we can create a better tomorrow</span></p>
  <ul class="topbar7-contacts">
    <li><a href="mailto:<?=e($contactEmail)?>"><?=site_icon('mail')?><span><?=e($contactEmail)?></span></a></li>
    <li><a href="tel:<?=e(tel_href($contactPhone))?>"><?=site_icon('call')?><span><?=e($contactPhone)?></span></a></li>
    <?php if($facebookUrl!==''):?><li><a href="<?=e($facebookUrl)?>" target="_blank" rel="noopener noreferrer" aria-label="97 Foundation on Facebook (opens in a new tab)"><?=site_facebook_icon()?><span>Facebook</span></a></li><?php endif;?>
  </ul>
</div></div>
<header class="site-header7<?=$headerBrand['shrink_on_scroll']==='1'?' is-shrinkable':''?><?=$headerBrand['show_wordmark']==='1'?'':' no-wordmark'?>" data-site-header style="<?=e($headerBrand['style'])?>">
  <div class="container header7-inner">
    <a class="brand7" href="<?=url('')?>" data-spa-link aria-label="<?=e($orgName)?> — Home">
      <?php if($logoEnabled):?><img class="brand7-logo" src="<?=e($brandLogo)?>" alt="<?=$headerBrand['show_wordmark']==='1'?'':e($orgName)?>" height="<?=(int)$headerBrand['logo_height']?>" decoding="async" fetchpriority="high"><?php endif;?>
      <?php if($headerBrand['show_wordmark']==='1'||!$logoEnabled):?><img class="brand7-wordmark" src="<?=e($brandWordmark)?>" alt="<?=e($orgName)?> — Together We Can" height="<?=(int)$headerBrand['wordmark_height']?>" decoding="async"><?php endif;?>
    </a>
    <nav class="nav7" aria-label="Primary navigation">
      <ul class="nav7-list" data-primary-nav>
        <?php foreach($primaryNav as $i=>$item):$active=site_nav_is_active($item);$hasChildren=!empty($item['children']);$menuId='nav7-sub-'.$i;?>
        <li class="nav7-item<?=$hasChildren?' has-children':''?><?=$active?' is-active':''?>"<?=$hasChildren?' data-nav-dropdown':''?>>
          <a class="nav7-link" href="<?=url($item['path'])?>" data-spa-link<?=$active?' aria-current="page"':''?><?=$hasChildren?' aria-haspopup="true" aria-expanded="false" aria-controls="'.e($menuId).'"':''?>><span><?=e($item['label'])?></span><?php if($hasChildren):?><?=site_icon('expand_more','nav7-chevron')?><?php endif;?></a>
          <?php if($hasChildren):?><div class="nav7-dropdown" id="<?=e($menuId)?>"><ul>
            <?php foreach($item['children'] as $child):$childActive=site_nav_child_is_active($child);?><li><a class="<?=$childActive?'is-active':''?>" href="<?=url($child['path'])?>" data-spa-link<?=$childActive?' aria-current="page"':''?>><b><?=e($child['label'])?></b><?php if(!empty($child['description'])):?><small><?=e($child['description'])?></small><?php endif;?></a></li><?php endforeach;?>
          </ul></div><?php endif;?>
        </li>
        <?php endforeach;?>
      </ul>
    </nav>
    <div class="header7-actions">
      <?php if($donationsOpen7):?><a class="btn7 btn7-donate" href="<?=url('donate')?>" data-spa-link><?=site_icon('favorite')?><span>Donate Now</span></a><?php endif;?>
      <a class="btn7 btn7-login<?=nav_active('login')?' is-active':''?>" href="<?=url('login')?>" data-spa-link><?=site_icon('login')?><span>Login</span></a>
    </div>
    <button class="nav7-toggle" type="button" data-menu-toggle aria-expanded="false" aria-controls="mobile-nav7" aria-label="Open menu"><?=site_icon('menu','nav7-toggle-open')?><?=site_icon('close','nav7-toggle-close')?></button>
  </div>
</header>
<div class="mobile-nav7" id="mobile-nav7" data-menu hidden>
  <div class="mobile-nav7-backdrop" data-menu-close></div>
  <aside class="mobile-nav7-panel" role="dialog" aria-modal="true" aria-label="Website menu">
    <div class="mobile-nav7-head">
      <a class="brand7 brand7-compact" href="<?=url('')?>" data-spa-link><?php if($logoEnabled):?><img class="brand7-logo" src="<?=e($brandLogo)?>" alt="<?=$headerBrand['show_wordmark']==='1'?'':e($orgName)?>" height="44"><?php endif;?><?php if($headerBrand['show_wordmark']==='1'||!$logoEnabled):?><img class="brand7-wordmark" src="<?=e($brandWordmark)?>" alt="<?=e($orgName)?>" height="44"><?php endif;?></a>
      <button class="mobile-nav7-close" type="button" data-menu-close aria-label="Close menu"><?=site_icon('close')?></button>
    </div>
    <ul class="mobile-nav7-list" data-mobile-nav>
      <?php foreach($primaryNav as $i=>$item):$active=site_nav_is_active($item);$hasChildren=!empty($item['children']);?>
      <li class="<?=$active?'is-active':''?><?=$hasChildren?' has-children':''?>">
        <?php if($hasChildren):?>
          <div class="mobile-nav7-row"><a href="<?=url($item['path'])?>" data-spa-link><?=e($item['label'])?></a><button type="button" data-mobile-sub-toggle aria-expanded="<?=$active?'true':'false'?>" aria-controls="m-nav7-sub-<?=$i?>" aria-label="Show <?=e($item['label'])?> pages"><?=site_icon('expand_more')?></button></div>
          <ul class="mobile-nav7-sub" id="m-nav7-sub-<?=$i?>"<?=$active?'':' hidden'?>><?php foreach($item['children'] as $child):?><li><a class="<?=site_nav_child_is_active($child)?'is-active':''?>" href="<?=url($child['path'])?>" data-spa-link><?=e($child['label'])?></a></li><?php endforeach;?></ul>
        <?php else:?>
          <a href="<?=url($item['path'])?>" data-spa-link<?=$active?' aria-current="page"':''?>><?=e($item['label'])?></a>
        <?php endif;?>
      </li>
      <?php endforeach;?>
    </ul>
    <div class="mobile-nav7-actions">
      <?php if($donationsOpen7):?><a class="btn7 btn7-donate" href="<?=url('donate')?>" data-spa-link><?=site_icon('favorite')?><span>Donate Now</span></a><?php endif;?>
      <a class="btn7 btn7-login" href="<?=url('login')?>" data-spa-link><?=site_icon('login')?><span>Login</span></a>
    </div>
    <ul class="mobile-nav7-contacts">
      <li><a href="mailto:<?=e($contactEmail)?>"><?=site_icon('mail')?><span><?=e($contactEmail)?></span></a></li>
      <li><a href="tel:<?=e(tel_href($contactPhone))?>"><?=site_icon('call')?><span><?=e($contactPhone)?></span></a></li>
      <?php if($facebookUrl!==''):?><li><a href="<?=e($facebookUrl)?>" target="_blank" rel="noopener noreferrer"><?=site_facebook_icon()?><span>Follow us on Facebook</span></a></li><?php endif;?>
    </ul>
  </aside>
</div>
<main id="main-content" data-spa-main tabindex="-1"><?=$content?></main>
<?php
// V7.3 footer: frosted-glass panels over an aurora background, quick actions, grouped links and legal pages.
$footerCats7=[];try{$footerCats7=\App\Core\Database::all('SELECT c.name,c.slug FROM post_categories c WHERE c.is_active=1 AND EXISTS(SELECT 1 FROM posts p WHERE p.category_id=c.id AND p.status="published") ORDER BY c.sort_order,c.name LIMIT 3');}catch(\Throwable){$footerCats7=[];}
$scholarshipsOpen7=\App\Core\Scholarship::applicationsOpen();
$footerHours7=trim((string)($foundation['opening_hours']??''))?:'Mon – Fri: 8:00 AM – 6:00 PM';
$footerIntro7='Creating positive change through compassion, community and accountable action across '.$targetDistrict.' and surrounding communities.';
$footerActions7=[
  ['scholarships'.($scholarshipsOpen7?'#apply':''),'graduation',$scholarshipsOpen7?'Apply for a scholarship':'Scholarships','Apply','O/L, A/L, university &amp; school support'],
  $donationsOpen7?['donate','heart','Donate securely','Donate','Bank transfer or card, every gift verified']:['campaigns','campaign','Our campaigns','Campaigns','See what the community is building'],
  ['volunteer','people','Become a volunteer','Volunteer','Give your time to local programmes'],
];
?>
<footer class="f73" aria-labelledby="f73-title" data-footer>
  <h2 class="sr-only" id="f73-title">About 97 Foundation and site links</h2>
  <div class="f73-aurora" aria-hidden="true"><i></i><i></i><i></i></div>
  <div class="container f73-wrap">
    <nav class="f73-actions" aria-label="Get involved">
      <?php foreach($footerActions7 as [$path,$icon,$title,$short,$text]):$parts=explode('#',$path,2);?>
      <a class="f73-action f73-glass" href="<?=url($parts[0])?><?=isset($parts[1])?'#'.e($parts[1]):''?>" data-spa-link aria-label="<?=e($title)?>">
        <span class="f73-action-icon"><?=site_line_icon($icon)?></span>
        <span class="f73-action-copy"><b><span class="f73-long"><?=e($title)?></span><span class="f73-short"><?=e($short)?></span></b><small><?=$text?></small></span>
        <span class="f73-action-go" aria-hidden="true"><?=site_line_icon('arrow')?></span>
      </a>
      <?php endforeach;?>
    </nav>

    <div class="f73-shell f73-glass" data-footer-shell>
      <div class="f73-top">
        <div class="f73-brand">
          <a class="f73-logo" href="<?=url('')?>" data-spa-link aria-label="<?=e($orgName)?> — Home">
            <span class="f73-logo-badge"><?php if($logoEnabled):?><img src="<?=e($brandLogo)?>" alt="" width="44" height="44" loading="lazy" decoding="async"><?php else:?><b>97</b><?php endif;?></span>
            <span class="f73-logo-text"><b><?=e(strtoupper($orgName))?></b><small><?=e($foundation['tagline']??'Hope · Help · Humanity.')?></small></span>
          </a>
          <p class="f73-intro"><?=e($footerIntro7)?></p>
          <?php if($socialLinks||$facebookUrl!==''):?><ul class="f73-social" aria-label="Follow 97 Foundation">
            <?php foreach($socialLinks as $sl):$socialKind=social_platform_key($sl['platform'],$sl['url']);$socialLabel=$sl['label']?:$sl['platform'];?><li><a class="social-kind-<?=e($socialKind)?>" href="<?=e($sl['url'])?>" target="_blank" rel="noopener noreferrer" aria-label="<?=e($socialLabel)?> (opens in a new tab)" title="<?=e($socialLabel)?>"><?=social_icon_svg($sl['platform'],$sl['url'])?></a></li><?php endforeach;?>
            <?php if(!$socialLinks&&$facebookUrl!==''):?><li><a class="social-kind-facebook" href="<?=e($facebookUrl)?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook (opens in a new tab)" title="Facebook"><?=social_icon_svg('facebook',$facebookUrl)?></a></li><?php endif;?>
          </ul><?php endif;?>
          <?php if($donationsOpen7):?><a class="f73-qr" href="<?=url('donate')?>" data-spa-link aria-label="Open the donation page"><span class="f73-qr-code" data-qr-code data-qr-text="<?=e(absolute_url('donate'))?>"></span><span><b>Scan to donate</b><small>Opens the secure donation page on your phone.</small></span></a><?php endif;?>
        </div>

        <nav class="f73-links" aria-label="Footer">
          <div class="f73-col">
            <h3>Explore</h3>
            <ul>
              <li><a href="<?=url('about')?>" data-spa-link>About Us</a></li>
              <li><a href="<?=url('what-we-do')?>" data-spa-link>What We Do</a></li>
              <li><a href="<?=url('causes')?>" data-spa-link>Our Causes</a></li>
              <li><a href="<?=url('events')?>" data-spa-link>Events</a></li>
              <li><a href="<?=url('gallery')?>" data-spa-link>Gallery</a></li>
              <li><a href="<?=url('members')?>" data-spa-link>Our Members</a></li>
              <li><a href="<?=url('contact')?>" data-spa-link>Contact Us</a></li>
            </ul>
          </div>
          <div class="f73-col">
            <h3>Education</h3>
            <ul>
              <li><a href="<?=url('scholarships')?>" data-spa-link>Scholarships</a></li>
              <?php if($scholarshipsOpen7):?><li><a class="f73-hl" href="<?=url('scholarships')?>#apply" data-spa-link title="Applications are open">Apply Online<i class="f73-dot" aria-hidden="true"></i></a></li><?php endif;?>
              <li><a href="<?=url('scholarships')?>#process" data-spa-link>Selection Process</a></li>
              <li><a href="<?=url('scholarships')?>#faq" data-spa-link>Scholarship FAQs</a></li>
            </ul>
          </div>
          <div class="f73-col">
            <h3>Get Involved</h3>
            <ul>
              <?php if($donationsOpen7):?><li><a href="<?=url('donate')?>" data-spa-link>Donate</a></li><?php endif;?>
              <?php if($fundraisingOn7):?><li><a href="<?=url('campaigns')?>" data-spa-link>Campaigns</a></li><?php endif;?>
              <li><a href="<?=url('volunteer')?>" data-spa-link>Volunteer</a></li>
              <li><a href="<?=url('member/register')?>" data-spa-link>Become a Member</a></li>
              <li><a href="<?=url('login')?>" data-spa-link>Portal Login</a></li>
            </ul>
          </div>
          <div class="f73-col">
            <h3>News &amp; Blog</h3>
            <ul>
              <li><a href="<?=url('news')?>" data-spa-link>Latest News</a></li>
              <?php foreach($footerCats7 as $fc):?><li><a href="<?=url('news/category/'.rawurlencode((string)$fc['slug']))?>" data-spa-link><?=e($fc['name'])?></a></li><?php endforeach;?>
            </ul>
          </div>
        </nav>

        <div class="f73-contact">
          <h3>Contact</h3>
          <ul>
            <li><span class="f73-ico"><?=contact_icon_svg('address')?></span><span><?=e($orgAddress)?></span></li>
            <li><span class="f73-ico"><?=contact_icon_svg('phone')?></span><a href="tel:<?=e(tel_href($contactPhone))?>"><?=e($contactPhone)?></a></li>
            <li><span class="f73-ico"><?=contact_icon_svg('email')?></span><a href="mailto:<?=e($contactEmail)?>"><?=e($contactEmail)?></a></li>
            <li><span class="f73-ico"><?=contact_icon_svg('hours')?></span><span><?=e($footerHours7)?></span></li>
          </ul>
        </div>
      </div>

      <div class="f73-bottom">
        <p class="f73-copy">© <?=date('Y')?> <?=e($orgName)?>. All rights reserved.</p>
        <ul class="f73-legal">
          <li><a href="<?=url('privacy-policy')?>" data-spa-link>Privacy Policy</a></li>
          <li><a href="<?=url('terms')?>" data-spa-link>Terms &amp; Conditions</a></li>
        </ul>
        <p class="f73-credit">Fully Developed by 97 Foundation IT Team.</p>
      </div>
    </div>
  </div>
  <div class="f73-mark" aria-hidden="true">97 FOUNDATION</div>
</footer>

<?php $waNumber=preg_replace('/\D+/','',(string)($foundation['whatsapp']??$foundation['phone']??''));?>
<?php if($waNumber):?><a class="floating-whatsapp" href="https://wa.me/<?=e($waNumber)?>" target="_blank" rel="noopener noreferrer" aria-label="Contact 97 Foundation on WhatsApp" title="WhatsApp 97 Foundation"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 3.5A11.8 11.8 0 0 0 12.1 0C5.5 0 .2 5.3.2 11.9c0 2.1.6 4.1 1.6 5.9L0 24l6.4-1.7a12 12 0 0 0 5.7 1.5h.1c6.6 0 11.9-5.3 11.9-11.9 0-3.2-1.3-6.2-3.6-8.4Zm-8.4 18.3c-1.8 0-3.6-.5-5.1-1.4l-.4-.2-3.8 1 1-3.7-.2-.4A9.8 9.8 0 1 1 12.1 21.8Zm5.4-7.4c-.3-.2-1.8-.9-2.1-1-.3-.1-.5-.2-.7.2-.2.3-.8 1-1 1.2-.2.2-.4.2-.7.1-1.8-.9-3-1.6-4.2-3.6-.3-.5.3-.5.9-1.6.1-.2.1-.4 0-.6-.1-.2-.7-1.7-1-2.3-.3-.7-.6-.6-.8-.6h-.7c-.2 0-.6.1-.9.4-.3.3-1.2 1.2-1.2 2.9 0 1.7 1.2 3.3 1.4 3.6.2.2 2.4 3.7 5.9 5.2.8.4 1.5.6 2 .7.8.3 1.6.2 2.2.1.7-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.1-.3-.2-.6-.4Z"/></svg><span>WhatsApp</span></a><?php endif;?>

<div class="live-chat-widget" data-live-chat data-chat-base="<?=e(url('chat'))?>">
<button class="live-chat-launch" type="button" data-chat-toggle aria-label="Open live support chat"><span class="chat-launch-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 11.5a8.5 8.5 0 0 1-9 8.5 9.8 9.8 0 0 1-3.7-.8L3 21l1.6-4.7A8.1 8.1 0 0 1 3 11.5 8.5 8.5 0 0 1 12 3a8.5 8.5 0 0 1 9 8.5Z"/><path d="M8 11.5h.01M12 11.5h.01M16 11.5h.01"/></svg></span><b>Live Chat</b><small>We are here to help</small></button>
<section class="live-chat-panel" aria-hidden="true" data-chat-panel-state="start">
<header><div><b>97 Foundation Support</b><small data-chat-header-status>Secure website chat</small></div><button type="button" data-chat-close aria-label="Minimize support chat">×</button></header>
<div class="live-chat-body" data-chat-body>
<section class="live-chat-start-state" data-chat-state-start>
<div class="chat-welcome"><span>97</span><h3>Hello 👋</h3><p>How can 97 Foundation help you today?</p></div>
<form data-chat-start-form data-no-loader><?=csrf_field()?><label><span>Name *</span><input name="name" required maxlength="120" autocomplete="name"></label><label><span>Email *</span><input name="email" type="email" required maxlength="190" autocomplete="email"></label><label><span>Phone</span><input name="phone" maxlength="60" autocomplete="tel"></label><label><span>Topic</span><select name="category"><option value="general">General Inquiry</option><option value="donation">Donation Support</option><option value="volunteer">Volunteer</option><option value="campaign">Campaign</option><option value="help">Need Help</option><option value="other">Other</option></select></label><label><span>Message *</span><textarea name="message" rows="4" required maxlength="3000" placeholder="Tell us how we can help…"></textarea></label><button class="btn btn-primary full" data-chat-start-button>Start Chat</button></form>
</section>
<section class="live-chat-active-state" data-chat-state-active hidden>
<div class="live-chat-thread" data-chat-thread role="log" aria-live="polite" aria-label="Support conversation"></div>
<div class="live-chat-closed-state" data-chat-closed-state hidden role="status"><span class="closed-state-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m7 12 3 3 7-7"/></svg></span><div class="closed-state-copy"><b>Support conversation completed</b><p>97 Foundation Support has closed this conversation. Thank you for contacting us.</p><small>You can start a new conversation whenever you need more help.</small></div><button type="button" data-chat-new><span>Start New Chat</span><b>↗</b></button></div>
<form class="live-chat-send" data-chat-send-form data-no-loader><?=csrf_field()?><div class="live-chat-composer"><textarea name="message" maxlength="3000" rows="1" placeholder="Type your message…" required data-chat-message-input></textarea><div class="live-chat-composer-tools"><small>Enter to send · Shift + Enter for new line</small><button class="widget-send-button" type="submit" data-chat-send-button><span>Send</span><b>➤</b></button></div></div></form>
</section>
</div>
<footer><span>🔒 Protected conversation</span><button type="button" data-chat-end hidden>Clear Chat</button></footer>
</section></div>
<div class="toast-stack" data-toast-stack></div><button class="back-top" type="button" data-back-top aria-label="Back to top"><?=site_line_icon('arrow_up')?></button>
<script src="<?=asset('js/vendor/qrcode.min.js')?>" defer></script><script src="<?=asset('js/app.js')?>" defer></script></body></html>
