<?php
$isVol=$user['role']==='volunteer';$p=$isVol?($volunteer??null):($member??null);
$valid=$isVol?($p&&in_array($p['status'],['approved','active'],true)&&!empty($p['volunteer_id'])):($p&&$user['status']==='active'&&!empty($p['member_number']));
$id=$isVol?($p['volunteer_id']??''):($p['member_number']??'');$token=$p['verification_token']??'';$verify=$valid?url('verify/'.($isVol?'volunteer':'member').'/'.$token):'';$date=$isVol?($p['approved_at']??null):($p['joined_at']??null);
$roleLabel=$isVol?'Volunteer':'Member';$dateLabel=$date?date('d M Y',strtotime($date)):'';$website=trim(setting('foundation','website',''))?:absolute_url('');$logo=setting('foundation','logo_path','');$logoEnabled=setting('foundation','logo_enabled','1')==='1';$logoSrc=($logoEnabled&&$logo)?public_upload($logo):'';$photoSrc=!empty($user['profile_photo'])?public_upload($user['profile_photo']):'';$verifyAbsolute=$valid?absolute_url(ltrim(parse_url($verify,PHP_URL_PATH)?:$verify,'/')):'';
?>
<div class="page-actions member-id-page-actions"><div><h2><?=$isVol?'My Volunteer ID':'My Member ID'?></h2><p>Your official 97 Foundation identity card uses the international CR80 ID-card proportion and a privacy-safe verification QR.</p></div><?php if($valid):?><div class="id-action-buttons"><button class="outline-link" type="button" data-print-id>Print ID</button><button class="admin-btn" type="button" data-download-id>Download PNG</button></div><?php endif;?></div>
<?php if(!$valid):?><div class="admin-panel empty-card"><h3>ID not available</h3><p><?=$isVol?'Your volunteer identity card becomes available after administrator approval.':'Your member ID is not currently active.'?></p></div><?php else:?>
<div class="member-id-layout">
<section class="member-id-card" data-member-id-card
 data-card-name="<?=e($user['name'])?>" data-card-id="<?=e($id)?>" data-card-role="<?=e($roleLabel)?>" data-card-date="<?=e($dateLabel)?>" data-card-status="Active" data-card-verify="<?=e($verifyAbsolute)?>" data-card-website="<?=e($website)?>" data-logo-src="<?=e($logoSrc)?>" data-photo-src="<?=e($photoSrc)?>">
  <div class="member-id-header">
    <div class="member-id-brand"><?php if($logoSrc):?><img src="<?=e($logoSrc)?>" alt="97 Foundation logo"><?php else:?><span>97</span><?php endif;?><div><b>97 FOUNDATION</b><small>Official <?=$roleLabel?> Identity Card</small></div></div>
    <span class="member-id-active"><i></i> Active</span>
  </div>
  <div class="member-id-body">
    <div class="member-id-photo"><?php if($photoSrc):?><img src="<?=e($photoSrc)?>" alt="<?=e($user['name'])?> profile photograph"><?php else:?><span><?=e(strtoupper(substr($user['name'],0,1)))?></span><?php endif;?></div>
    <div class="member-id-person"><small><?=$roleLabel?></small><h3><?=e($user['name'])?></h3><label><?=$isVol?'Volunteer ID':'Member ID'?></label><strong><?=e($id)?></strong><div class="member-id-meta"><span><small><?=$isVol?'Approved':'Member Since'?></small><b><?=e($dateLabel?:'—')?></b></span><span><small>Organization</small><b>97 Foundation</b></span></div></div>
    <div class="member-id-verify"><div class="member-id-qr" data-id-qr data-qr-code data-qr-text="<?=e($verifyAbsolute)?>"></div><b>Scan to verify</b><small>Official verification</small></div>
  </div>
  <footer><span>Hope • Help • Humanity</span><b><?=e($website)?></b></footer>
</section>
<aside class="admin-panel id-control-panel"><div class="panel-head"><div><h3>Official ID Controls</h3><span>Compact, printable and ready to keep on your phone.</span></div></div><div class="id-standard-note"><span>CR80</span><div><b>International ID Card Size</b><small>85.60 × 53.98 mm · same proportion as standard bank and identification cards.</small></div></div><label><span>Organization Website</span><input value="<?=e($website)?>" readonly></label><label><span>Verification URL</span><input value="<?=e($verifyAbsolute)?>" readonly></label><div class="id-control-actions"><button class="outline-link" type="button" data-print-id>Print ID</button><button class="admin-btn" type="button" data-download-id>Download PNG</button></div><a class="outline-link full-id-link" href="<?=e($verify)?>" target="_blank">Open Verification Page ↗</a><div class="security-visual enabled"><span>✓</span><div><b>Privacy Safe</b><small>The QR stores only the official verification URL/token.</small></div></div></aside>
</div>
<?php endif;?>
