<div class="page-actions"><div><h2>Social Media Links</h2><p>Add, edit, reorder, enable or disable Foundation social profiles. Professional platform icons are selected automatically.</p></div><button class="admin-btn" data-scroll-to="#social-form">＋ Add Social Link</button></div>
<div class="management-layout social-management-layout">
<section class="admin-panel sticky-form social-editor-panel" id="social-form">
  <div class="panel-head"><div><h3>Social Link Editor</h3><span>Use the platform name and HTTPS URL. The correct icon is applied automatically.</span></div><button type="button" class="outline-link mini" data-social-reset>New Link</button></div>
  <form method="post" action="<?=url('admin/social/save')?>" class="admin-form social-admin-form" data-social-form><?=csrf_field()?><input type="hidden" name="id">
    <label><span>Platform *</span><input name="platform" maxlength="80" required placeholder="Facebook"></label>
    <label><span>Display Label</span><input name="label" maxlength="100" placeholder="Facebook"></label>
    <label class="full"><span>HTTPS URL *</span><input type="url" name="url" required maxlength="500" placeholder="https://facebook.com/97foundation"></label>
    <div class="full social-auto-icon-note"><span><?=social_icon_svg('link')?></span><div><b>Automatic professional icon</b><small>Facebook, Instagram, LinkedIn, YouTube, X, WhatsApp, TikTok, Telegram, GitHub and more are detected from the platform name or URL.</small></div></div>
    <label><span>Sort Order</span><input type="number" name="sort_order" value="0"></label>
    <label class="toggle-line"><input type="checkbox" name="is_active" value="1" checked><span>Active on website</span></label>
    <button class="admin-btn full">Save Social Link</button>
  </form>
</section>
<section class="content-card-list social-list-cards">
<?php foreach($links as $l):$payload=e(json_encode(['id'=>$l['id'],'platform'=>$l['platform'],'label'=>$l['label'],'url'=>$l['url'],'sort_order'=>$l['sort_order'],'is_active'=>(int)$l['is_active']],JSON_HEX_APOS|JSON_HEX_QUOT));$kind=social_platform_key($l['platform'],$l['url']);?>
  <article class="admin-panel social-admin-card social-kind-<?=e($kind)?>">
    <div class="social-admin-icon" title="<?=e($l['label']?:$l['platform'])?>"><?=social_icon_svg($l['platform'],$l['url'])?></div>
    <div class="social-admin-copy"><div class="social-admin-title"><h3><?=e($l['platform'])?></h3><span class="status-badge <?=$l['is_active']?'active':'inactive'?>"><?=$l['is_active']?'Active':'Inactive'?></span></div><a href="<?=e($l['url'])?>" target="_blank" rel="noopener noreferrer"><?=e(mb_strimwidth($l['url'],0,72,'…'))?></a><small><?=e($l['label']?:$l['platform'])?></small></div>
    <div class="card-admin-actions"><button class="outline-link" type="button" data-social-edit='<?=$payload?>'>✎ Edit</button><form method="post" action="<?=url('admin/social/delete')?>" data-confirm="Delete this social media link?"><?=csrf_field()?><input type="hidden" name="id" value="<?=$l['id']?>"><button class="danger-link">Delete</button></form></div>
  </article>
<?php endforeach;?>
<?php if(!$links):?><div class="admin-panel empty-card">No social media links have been added.</div><?php endif;?>
<?=admin_pagination($pagination??[])?>
</section></div>
