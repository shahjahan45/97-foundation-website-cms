<div class="page-actions">
  <div>
    <h2>Fundraising Campaigns</h2>
    <p>Create, edit, activate/deactivate, publish/hide or safely retire campaigns. Public raised totals include approved donations only.</p>
  </div>
  <div class="page-action-stack">
    <form method="get" action="<?=url('admin/campaigns')?>" class="admin-search-form">
      <input name="q" value="<?=e($q??'')?>" placeholder="Search campaigns">
      <select name="state">
        <option value="">All states</option>
        <option value="active" <?=($state??'')==='active'?'selected':''?>>Active</option>
        <option value="inactive" <?=($state??'')==='inactive'?'selected':''?>>Inactive</option>
      </select>
      <button class="outline-link">Filter</button>
    </form>
    <button class="admin-btn" data-scroll-to="#campaign-form">＋ New Campaign</button>
  </div>
</div>

<div class="management-layout">
  <section class="admin-panel sticky-form" id="campaign-form">
    <div class="panel-head">
      <h3 data-form-title>Campaign Editor</h3>
      <button class="outline-link mini" type="button" data-campaign-reset>Clear</button>
    </div>
    <form method="post" enctype="multipart/form-data" action="<?=url('admin/campaigns/save')?>" class="admin-form" data-campaign-form>
      <?=csrf_field()?>
      <input type="hidden" name="id" value="">
      <label class="full"><span>Campaign Title *</span><input name="title" required maxlength="180" placeholder="e.g. Help 100 Families"></label>
      <label><span>Target Amount (LKR)</span><input type="number" name="target_amount" min="0" step="0.01" required></label>
      <label><span>Lifecycle Status</span><select name="status"><option value="draft">Draft</option><option value="upcoming">Upcoming</option><option value="active">Active</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select></label>
      <label><span>Category</span><input name="category" maxlength="100" placeholder="Education"></label>
      <label><span>Location</span><input name="location" maxlength="160" placeholder="Thambala, Polonnaruwa"></label>
      <label><span>Start Date</span><input type="date" name="start_date"></label>
      <label><span>End Date</span><input type="date" name="end_date"></label>
      <label class="full"><span>Short Description</span><textarea name="short_description" rows="3" maxlength="500"></textarea></label>
      <label class="full"><span>Full Campaign Story</span><textarea name="description" rows="7" maxlength="30000"></textarea></label>
      <label class="full"><span>Cover Image</span><input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp"><small class="helper">Recommended 1200 × 800 px (3:2). JPG, PNG or WEBP, max 5 MB. Updated images refresh immediately after save and are cover-fitted without stretching.</small></label><div class="campaign-editor-image-preview full" data-campaign-image-preview hidden><img src="" alt="Current campaign cover"><div><b>Current Campaign Image</b><small>Upload a new image above to replace it.</small></div></div>
      <label class="toggle-line full"><input type="checkbox" name="is_active" value="1" checked><span><b>Active campaign</b> — administrators can pause a campaign without deleting it.</span></label>
      <label class="toggle-line full"><input type="checkbox" name="is_visible" value="1" checked><span><b>Visible on public website</b> — controls public publication separately.</span></label>
      <button class="admin-btn full" type="submit">Save Campaign</button>
    </form>
  </section>

  <section class="campaign-admin-list">
    <?php $logo=setting('foundation','logo_path','');$logoEnabled=setting('foundation','logo_enabled','1')==='1'; ?>
    <?php foreach($campaigns as $idx=>$c):
      $pct=$c['target_amount']>0?min(100,round($c['raised']/$c['target_amount']*100)):0;
      $payload=e(json_encode([
        'id'=>$c['id'],'title'=>$c['title'],'target_amount'=>$c['target_amount'],'status'=>$c['status'],'category'=>$c['category'],'location'=>$c['location'],'start_date'=>$c['start_date'],'end_date'=>$c['end_date'],'short_description'=>$c['short_description'],'description'=>$c['description'],'is_active'=>(int)$c['is_active'],'is_visible'=>(int)$c['is_visible'],'cover_image_url'=>$c['cover_image']?public_upload($c['cover_image']):''
      ],JSON_HEX_APOS|JSON_HEX_QUOT));
      $cover=campaign_fallback_image($c,$idx);
    ?>
      <article class="admin-panel campaign-admin-card">
        <div class="campaign-admin-media">
          <img src="<?=e($cover)?>" alt="<?=e($c['title'])?>">
          <?php if($logoEnabled&&$logo):?><span class="campaign-admin-brand-mark"><img src="<?=e(public_upload($logo))?>" alt="Foundation logo"></span><?php endif;?>
          <em class="status-badge <?=e($c['status'])?>"><?=e(ucfirst($c['status']))?></em>
        </div>
        <div class="campaign-admin-body">
          <div class="campaign-admin-head">
            <div>
              <h3><?=e($c['title'])?></h3>
              <small><?=e($c['category']?:'Uncategorized')?> · <?=e($c['location']?:'No location')?></small>
            </div>
            <div class="state-badges">
              <span class="status-badge <?=$c['is_active']?'active':'inactive'?>"><?=$c['is_active']?'Active':'Inactive'?></span>
              <span class="visibility <?=$c['is_visible']?'on':'off'?>"><?=$c['is_visible']?'Public':'Hidden'?></span>
            </div>
          </div>
          <div class="admin-progress"><i style="width:<?=$pct?>%"></i></div>
          <div class="two-col"><span>Verified raised <b><?=money($c['raised'],$c['currency'])?></b></span><span><?=$pct?>% of <?=money($c['target_amount'],$c['currency'])?></span></div>
          <div class="card-admin-actions">
            <button class="outline-link" type="button" data-campaign-edit='<?=$payload?>'>✎ Edit</button>
            <form method="post" action="<?=url('admin/campaigns/toggle')?>"><?=csrf_field()?><input type="hidden" name="id" value="<?=$c['id']?>"><button class="outline-link" type="submit"><?=$c['is_active']?'Deactivate':'Activate'?></button></form>
            <form method="post" action="<?=url('admin/campaigns/visibility')?>"><?=csrf_field()?><input type="hidden" name="id" value="<?=$c['id']?>"><button class="outline-link" type="submit"><?=$c['is_visible']?'Hide':'Publish'?></button></form>
            <form method="post" action="<?=url('admin/campaigns/delete')?>" data-confirm="Delete this campaign? This is blocked if donation records exist."><?=csrf_field()?><input type="hidden" name="id" value="<?=$c['id']?>"><button class="danger-link" type="submit">Delete</button></form>
            <?php if($c['is_active']&&$c['is_visible']):?><a class="outline-link" href="<?=url('campaigns/'.$c['slug'])?>" target="_blank">View ↗</a><?php endif;?>
          </div>
        </div>
      </article>
    <?php endforeach;?>
    <?php if(!$campaigns):?><div class="admin-panel empty-card">No campaigns match this filter.</div><?php endif;?>
  </section>
</div>

<?=admin_pagination($pagination??[])?>
