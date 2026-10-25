<?php
$p=$user['role']==='volunteer'?($volunteer??null):($member??null);
$isVol=$user['role']==='volunteer';
$identityNumber=$isVol?($p['volunteer_id']??'Pending'):($p['member_number']??'—');
$joinedDate='—';
if($isVol && !empty($p['approved_at'])) $joinedDate=date('d M Y',strtotime($p['approved_at']));
elseif(!$isVol && !empty($p['joined_at'])) $joinedDate=date('d M Y',strtotime($p['joined_at']));
$foundationRole=$isVol?($p['occupation']??'Approved Volunteer'):($p['role_title']??'Foundation Member');
$statusText=$isVol?(!empty($p['status'])?ucfirst((string)$p['status']):'Approved'):'Verified Member';
?>
<div class="page-actions">
  <div>
    <h2>My Profile</h2>
    <p>Update permitted personal information. Official ID, approval/status and Foundation role are controlled by administrators.</p>
  </div>
</div>
<div class="settings-grid portal-profile-grid professional-portal-profile">
  <section class="admin-panel profile-card-panel portal-profile-summary">
    <div class="portal-profile-cover"></div>
    <div class="portal-profile-head">
      <div class="profile-large compact-profile-large">
        <?php if(!empty($user['profile_photo'])):?><img src="<?=e(public_upload($user['profile_photo']))?>" alt="Profile"><?php else:?><span><?=e(strtoupper(substr($user['name'],0,1)))?></span><?php endif;?>
      </div>
      <div class="portal-profile-copy">
        <span class="portal-profile-kicker"><?=$isVol?'Volunteer Portal':'Member Portal'?></span>
        <h3><?=e($user['name'])?></h3>
        <p><?=e($user['email'])?></p>
        <div class="portal-profile-badges">
          <span class="status-badge active small-pill"><?=e($statusText)?></span>
          <span class="status-badge soft-pill"><?=e($foundationRole)?></span>
        </div>
      </div>
    </div>
    <div class="portal-identity-list">
      <div><small><?=$isVol?'Volunteer ID':'Member ID'?></small><b><?=e($identityNumber)?></b></div>
      <div><small><?=$isVol?'Approved On':'Member Since'?></small><b><?=e($joinedDate)?></b></div>
      <div><small>Portal Access</small><b><?=e(ucfirst($user['status']??'active'))?></b></div>
      <div><small>Organization</small><b>97 Foundation</b></div>
    </div>
  </section>
  <section class="admin-panel portal-profile-editor">
    <div class="panel-head"><div><h3>Edit Profile</h3><span>Official identity fields cannot be edited from this portal.</span></div></div>
    <form method="post" enctype="multipart/form-data" action="<?=url('portal/profile')?>" class="admin-form portal-admin-form">
      <?=csrf_field()?>
      <label><span>Full Name</span><input name="name" value="<?=e($user['name'])?>" required></label>
      <label><span>Profile Picture</span><input type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp"></label>
      <label><span>Mobile</span><input name="mobile" value="<?=e($p['mobile']??'')?>"></label>
      <label class="full"><span>Address</span><input name="address" value="<?=e($p['address']??'')?>"></label>
      <?php if($isVol):?>
        <label><span>City</span><input name="city" value="<?=e($p['city']??'')?>"></label>
        <label><span>Country</span><input name="country" value="<?=e($p['country']??'')?>"></label>
        <label><span>Occupation</span><input name="occupation" value="<?=e($p['occupation']??'')?>"></label>
        <label class="full"><span>Skills</span><textarea name="skills" rows="4"><?=e($p['skills']??'')?></textarea></label>
        <label class="full"><span>Areas of Interest</span><textarea name="interests" rows="4"><?=e($p['interests']??'')?></textarea></label>
      <?php endif;?>
      <button class="admin-btn full" type="submit">Save Profile</button>
    </form>
  </section>
</div>
