<?php $f=$settingsData['foundation']??[];$seo=$settingsData['seo']??[]; ?>
<?php if(!\App\Core\Upgrade71::applied()):?>
<form method="post" action="<?=url('admin/branding/apply-update')?>" class="v7-card v7-update-card" data-confirm="Apply the V7.1 website update now? This sets the official logo and header text image, and updates the header email, phone and Facebook page."><?=csrf_field()?>
  <span class="v7-intro-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg></span>
  <div><b>V7.1 website update is ready to apply</b><p>Installs the new 97 Foundation logo and header text image, sets the header contacts to <b><?=e(\App\Core\Upgrade71::EMAIL)?></b> · <b><?=e(\App\Core\Upgrade71::PHONE)?></b> · Facebook page, and prepares the Officials pages and hero banner. Existing records are kept.</p></div>
  <button class="admin-btn" type="submit">Apply Update</button>
</form>
<?php endif;?>
<div class="page-actions"><div><h2>Branding, Visuals & SEO</h2><p>Manage the Foundation logo, browser tab icon, campaign and login visuals, plus location-focused SEO / AEO settings for Sri Lanka and the Polonnaruwa–Thambala target area.</p></div></div>
<form method="post" enctype="multipart/form-data" action="<?=url('admin/branding')?>" class="branding-grid"><?=csrf_field()?>
<?php $hb=header_brand_settings($settingsData);$hbLogo=brand_logo_url($f);$hbMark=brand_wordmark_url($f);?>
<section class="admin-panel v7-header-brand" data-header-brand>
  <div class="panel-head"><div><h3>Header Logo &amp; Size</h3><span>Control how large the logo and foundation text image appear in the website header. Changes preview instantly and publish when you save.</span></div><a class="outline-link mini" href="<?=url('')?>" target="_blank" rel="noopener">View Website ↗</a></div>
  <div class="v7-hb-layout">
    <div class="v7-hb-preview-col">
      <div class="v7-hb-device" role="group" aria-label="Preview size"><button type="button" class="active" data-hb-device="desktop">Desktop</button><button type="button" data-hb-device="mobile">Mobile</button></div>
      <div class="v7-hb-preview" data-hb-preview data-device="desktop" style="--pl:<?=$hb['logo_height']?>px;--pm:<?=$hb['wordmark_height']?>px;--plm:<?=$hb['logo_height_mobile']?>px">
        <div class="v7-hb-topbar"><span>♥ Together, we can create a better tomorrow</span><span><?=e($f['email']??'')?></span></div>
        <div class="v7-hb-header">
          <div class="v7-hb-brand"><img class="v7-hb-logo" src="<?=e($hbLogo)?>" alt="Logo preview"><img class="v7-hb-mark" src="<?=e($hbMark)?>" alt="Foundation text preview" <?=$hb['show_wordmark']==='1'?'':'hidden'?>></div>
          <div class="v7-hb-nav"><i></i><i></i><i></i><i></i><i></i></div>
          <div class="v7-hb-cta"><b>♥ Donate Now</b><em>Login</em></div>
          <div class="v7-hb-burger"><i></i><i></i><i></i></div>
        </div>
        <div class="v7-hb-hero"></div>
      </div>
      <p class="helper">Header height adjusts automatically: <b data-hb-height><?=$hb['header_height']?></b>px on desktop · <b data-hb-height-m><?=$hb['header_height_mobile']?></b>px on mobile.</p>
    </div>
    <div class="v7-hb-controls">
      <div class="v7-hb-presets" role="group" aria-label="Logo size presets">
        <span>Quick size</span>
        <?php foreach(['Small'=>[50,46,40],'Medium'=>[62,56,48],'Large'=>[74,64,54],'Extra large'=>[86,72,60]] as $label=>[$pl,$pm,$plm]):?><button type="button" class="outline-link mini" data-hb-preset="<?=$pl?>,<?=$pm?>,<?=$plm?>"><?=e($label)?></button><?php endforeach;?>
      </div>
      <label class="v7-range"><span>Logo height · desktop <output data-hb-out="logo"><?=$hb['logo_height']?>px</output></span><input type="range" name="header_logo_height" min="40" max="110" step="1" value="<?=$hb['logo_height']?>" data-hb-input="logo"></label>
      <label class="v7-range"><span>Foundation text image height · desktop <output data-hb-out="mark"><?=$hb['wordmark_height']?>px</output></span><input type="range" name="header_wordmark_height" min="32" max="96" step="1" value="<?=$hb['wordmark_height']?>" data-hb-input="mark"></label>
      <label class="v7-range"><span>Logo height · mobile <output data-hb-out="logo-m"><?=$hb['logo_height_mobile']?>px</output></span><input type="range" name="header_logo_height_mobile" min="34" max="72" step="1" value="<?=$hb['logo_height_mobile']?>" data-hb-input="logo-m"></label>
      <label class="toggle-line"><input type="checkbox" name="header_show_wordmark" value="1" <?=$hb['show_wordmark']==='1'?'checked':''?> data-hb-input="show-mark"><span>Show the foundation text image beside the logo <small>Turn off if your logo file already contains the name.</small></span></label>
      <label class="toggle-line"><input type="checkbox" name="header_shrink_on_scroll" value="1" <?=$hb['shrink_on_scroll']==='1'?'checked':''?>><span>Compact the logo slightly when visitors scroll</span></label>
      <label class="toggle-line"><input type="checkbox" name="optimize_logo" value="1" checked><span>Remove empty space around uploaded logos <small>Recommended — makes the artwork fill its size. The original file is kept.</small></span></label>
    </div>
  </div>
</section>
<section class="admin-panel">
  <div class="panel-head"><div><h3>Logo & Browser Tab Icon</h3><span>Used across website, Admin, Member Portal and Volunteer Portal.</span></div></div>
  <div class="branding-assets-grid">
    <div class="branding-asset-card">
      <div class="branding-preview logo-preview"><img src="<?=e(brand_logo_url($f))?>" alt="Current logo"></div>
      <label><b>Foundation Logo</b><input type="file" name="logo" accept=".jpg,.jpeg,.png,.webp"><small>Recommended 512 × 512 px transparent PNG / WEBP.</small></label>
      <label class="toggle-line"><input type="checkbox" name="reset_logo" value="1"><span>Use the official 97 Foundation logo</span></label>
    </div>
    <div class="branding-asset-card">
      <div class="branding-preview favicon-preview"><?php if(!empty($f['favicon_path'])):?><img src="<?=e(public_upload($f['favicon_path']))?>" alt="Current browser tab icon"><?php elseif(!empty($f['logo_path'])):?><img src="<?=e(public_upload($f['logo_path']))?>" alt="Logo fallback"><?php else:?><b>97</b><?php endif;?></div>
      <label><b>Browser Tab Logo / Favicon</b><input type="file" name="favicon" accept=".png,.jpg,.jpeg,.webp"><small>Professional website tab icon used in browsers and mobile shortcuts.</small></label>
      <label class="toggle-line"><input type="checkbox" name="reset_favicon" value="1"><span>Use the official icon</span></label>
    </div>
    <div class="branding-asset-card branding-wordmark-card">
      <div class="branding-preview wordmark-preview"><img src="<?=e(brand_wordmark_url($f))?>" alt="Current header text image"></div>
      <label><b>Header Foundation Text Image</b><input type="file" name="wordmark" accept=".png,.webp"><small>Shown beside the logo in the website header. Transparent PNG / WEBP, about 980 × 400 px.</small></label>
      <label class="toggle-line"><input type="checkbox" name="reset_wordmark" value="1"><span>Use the official header text image</span></label>
    </div>
  </div>
  <div class="admin-form branding-identity-form">
    <label><span>Foundation Name</span><input name="name" value="<?=e($f['name']??'97 Foundation')?>"></label>
    <label><span>Tagline</span><input name="tagline" value="<?=e($f['tagline']??'Hope. Help. Humanity.')?>"></label>
    <label class="toggle-line full"><input type="checkbox" name="logo_enabled" value="1" <?=($f['logo_enabled']??'1')==='1'?'checked':''?>><span>Display uploaded logo in navigation, cards and portal identity areas</span></label>
  </div>
</section>
<section class="admin-panel">
  <div class="panel-head"><div><h3>Portal & Admin Visuals</h3><span>Professional images that can be changed anytime from this panel.</span></div></div>
  <div class="branding-assets-grid portal-visual-grid">
    <div class="branding-asset-card">
      <div class="branding-preview landscape-preview"><?php if(!empty($f['portal_login_image_path'])):?><img src="<?=e(public_upload($f['portal_login_image_path']))?>" alt="Portal login image"><?php else:?><img src="<?=asset('images/demo/hero-children.jpg')?>" alt="Default portal image"><?php endif;?></div>
      <label><b>Foundation Portal Login Image</b><input type="file" name="portal_login_image" accept=".jpg,.jpeg,.png,.webp"><small>Used on the Member & Volunteer Portal login page.</small></label>
    </div>
    <div class="branding-asset-card">
      <div class="branding-preview landscape-preview"><?php if(!empty($f['member_login_image_path'])):?><img src="<?=e(public_upload($f['member_login_image_path']))?>" alt="Member login image"><?php else:?><img src="<?=asset('images/demo/hero-children.jpg')?>" alt="Default member login image"><?php endif;?></div>
      <label><b>Member Login Side Image</b><input type="file" name="member_login_image" accept=".jpg,.jpeg,.png,.webp"><small>Used on the separate public member login page.</small></label>
    </div>
    <div class="branding-asset-card">
      <div class="branding-preview landscape-preview"><?php if(!empty($f['admin_login_background_path'])):?><img src="<?=e(public_upload($f['admin_login_background_path']))?>" alt="Admin login background"><?php else:?><img src="<?=asset('images/demo/campaign-education.jpg')?>" alt="Default admin login background"><?php endif;?></div>
      <label><b>Admin Login Background</b><input type="file" name="admin_login_background" accept=".jpg,.jpeg,.png,.webp"><small>Creates a more premium administration sign-in experience.</small></label>
    </div>
  </div>
</section>
<section class="admin-panel">
  <div class="panel-head"><div><h3>Contact & Social</h3><span>Automatically updates the public website and digital identity pages.</span></div></div>
  <div class="admin-form">
    <label class="full"><span>Organization Website</span><input type="url" name="website" maxlength="255" placeholder="https://www.97foundation.org" value="<?=e($f['website']??absolute_url(''))?>"><small class="helper">Used on official Member / Volunteer IDs and organization information.</small></label>
    <label><span>Email</span><input type="email" name="email" value="<?=e($f['email']??'')?>"><small class="helper">Header top bar, footer and contact pages.</small></label>
    <label><span>Phone</span><input name="phone" value="<?=e($f['phone']??'')?>" placeholder="+94 71 258 3411"><small class="helper">Written in international format, e.g. +94 71 258 3411.</small></label>
    <label><span>WhatsApp Number</span><input name="whatsapp" placeholder="+94 77 123 4567" value="<?=e($f['whatsapp']??'')?>"><small class="helper">Used by the floating WhatsApp contact button.</small></label>
    <label class="full"><span>Address</span><input name="address" value="<?=e($f['address']??'')?>"></label>
    <label class="full"><span>Opening Hours</span><input name="opening_hours" value="<?=e($f['opening_hours']??'')?>"></label>
    <label><span>Facebook Page URL</span><input type="url" name="facebook" placeholder="https://www.facebook.com/…" value="<?=e($f['facebook']??'')?>"><small class="helper">Shown in the website header top bar.</small></label>
    <label><span>Instagram URL</span><input name="instagram" value="<?=e($f['instagram']??'')?>"></label>
    <label><span>LinkedIn URL</span><input name="linkedin" value="<?=e($f['linkedin']??'')?>"></label>
    <label><span>YouTube URL</span><input name="youtube" value="<?=e($f['youtube']??'')?>"></label>
  </div>
</section>
<section class="admin-panel seo-panel">
  <div class="panel-head"><div><h3>SEO + AEO Defaults</h3><span>Search, answer-engine and location-targeted metadata.</span></div></div>
  <div class="admin-form">
    <label class="full"><span>Default SEO Title</span><input name="default_title" maxlength="240" value="<?=e($seo['default_title']??'97 Foundation Sri Lanka | Community Support in Polonnaruwa Thambala')?>"></label>
    <label class="full"><span>Default Meta Description</span><textarea name="default_description" rows="4" maxlength="500"><?=e($seo['default_description']??'97 Foundation is a Sri Lanka community support and charity platform serving Polonnaruwa, Thambala and nearby areas through campaigns, donations, volunteering and social support.')?></textarea></label>
    <label class="full"><span>SEO Keywords</span><textarea name="keywords" rows="3" maxlength="500" placeholder="97 Foundation, Sri Lanka charity, Polonnaruwa foundation, Thambala community support, volunteer in Polonnaruwa"><?=e($seo['keywords']??'')?></textarea></label>
    <label><span>Target Country</span><input name="target_country" maxlength="120" value="<?=e($seo['target_country']??'Sri Lanka')?>"></label>
    <label><span>Target District / Region</span><input name="target_district" maxlength="120" value="<?=e($seo['target_district']??'Polonnaruwa')?>"></label>
    <label><span>Target Locality / Area</span><input name="target_locality" maxlength="120" value="<?=e($seo['target_locality']??'Thambala')?>"></label>
    <label class="full"><span>AEO / Answer Summary</span><textarea name="answer_summary" rows="3" maxlength="500" placeholder="Short factual answer about the Foundation for answer engines."><?=e($seo['answer_summary']??'97 Foundation is a Sri Lanka community-focused foundation that supports people in need, runs local campaigns, encourages volunteering and provides transparent donation support, especially for Polonnaruwa and Thambala communities.')?></textarea></label>
    <label class="full"><span>Default Social / Open Graph Image</span><input type="file" name="social_image" accept=".jpg,.jpeg,.png,.webp"><small class="helper">Recommended 1200 × 630 px. Used when pages are shared on Facebook, WhatsApp and search previews.</small></label>
    <?php if(!empty($seo['social_image'])):?><div class="seo-social-preview full"><img src="<?=e(public_upload($seo['social_image']))?>" alt="Social preview"></div><?php endif;?>
    <div class="seo-preview full"><small>Search Preview</small><h4><?=e($seo['default_title']??'97 Foundation Sri Lanka | Community Support in Polonnaruwa Thambala')?></h4><p><?=e($seo['default_description']??'')?></p></div>
  </div>
</section>
<section class="admin-panel website-image-guide">
  <div class="panel-head"><div><h3>Website Image Size Guide</h3><span>Upload these proportions for the cleanest professional fit.</span></div></div>
  <div class="image-size-grid">
    <article><b>Hero Desktop</b><strong>1920 × 900</strong><span>Desktop/laptop hero artwork</span></article>
    <article><b>Hero Mobile</b><strong>1080 × 1350</strong><span>Phone-specific hero artwork</span></article>
    <article><b>Page / Campaign</b><strong>1200 × 800</strong><span>3:2 landscape · cover fit</span></article>
    <article><b>News / Blog</b><strong>1200 × 675</strong><span>16:9 landscape · cover fit</span></article>
    <article><b>Events / Causes</b><strong>1200 × 800</strong><span>3:2 landscape · cover fit</span></article>
    <article><b>Gallery</b><strong>1600 × 1200</strong><span>4:3 recommended · cover fit</span></article>
    <article><b>Member / Volunteer</b><strong>800 × 800</strong><span>Square portrait · centered crop</span></article>
    <article><b>Foundation Logo</b><strong>512 × 512</strong><span>Transparent PNG / WEBP</span></article>
    <article><b>Browser Tab Icon</b><strong>512 × 512</strong><span>Square PNG / WEBP</span></article>
    <article><b>Portal / Admin Login</b><strong>1600 × 1200</strong><span>Landscape hero background</span></article>
    <article><b>Social Share Image</b><strong>1200 × 630</strong><span>Open Graph standard</span></article>
  </div>
  <p class="image-guide-note">Images are displayed inside fixed professional frames using <b>object-fit: cover</b> or background cover positioning, so different source sizes remain aligned without stretching.</p>
</section>
<div class="branding-save"><button class="admin-btn">Save Branding, Visuals & SEO Settings</button></div></form>
