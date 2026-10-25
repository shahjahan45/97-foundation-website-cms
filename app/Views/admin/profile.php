<?php
$profileIcon=function(string $name):string{
 $icons=[
 'user'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4.5 20c.7-4 3-6 7.5-6s6.8 2 7.5 6"/></svg>',
 'photo'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="3"/><circle cx="9" cy="10" r="2"/><path d="m5.5 18 5-5 3 3 2-2 3 4"/></svg>',
 'lock'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>',
 'info'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5h.01"/></svg>',
 'calendar'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M7 3v4M17 3v4M3 10h18"/></svg>',
 'shield'=>'<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 20 6v5c0 5.1-3.1 8.4-8 10-4.9-1.6-8-4.9-8-10V6l8-3Z"/><path d="m9 12 2 2 4-4"/></svg>'
 ];return $icons[$name]??$icons['info'];
};
?>
<div class="page-actions page-actions-profile-premium"><div><h2>My Profile</h2><p class="page-breadcrumbs"><span>Dashboard</span><i>›</i><span>My Profile</span></p></div><a class="outline-link profile-security-shortcut" href="<?=url('admin/account-security')?>"><?=$profileIcon('lock')?> <span>Account Security</span></a></div>
<div class="profile-settings-unified-layout">
  <aside class="admin-panel admin-profile-overview-card">
    <div class="admin-profile-hero-bg"></div>
    <div class="admin-profile-overview-inner">
      <div class="admin-profile-main-avatar"><?php if(!empty($profile['profile_photo'])):?><img src="<?=e(public_upload($profile['profile_photo']))?>" alt="Profile"><?php else:?><span><?=e(strtoupper(substr($profile['name'],0,1)))?></span><?php endif;?><i class="admin-profile-edit-dot">✎</i></div>
      <h3><?=e($profile['name'])?></h3><span class="admin-profile-role-pill"><?=e(str_replace('_',' ',ucwords($profile['role'],'_')))?></span><p class="admin-profile-email"><?=e($profile['email'])?></p>
      <div class="admin-profile-stat-list">
        <article><span class="stat-icon-mini"><?=$profileIcon('calendar')?></span><div><small>Last Login</small><b><?=e($profile['last_login_at']?date('d M Y',strtotime($profile['last_login_at'])):'Not recorded')?></b><em><?=e($profile['last_login_at']?date('h:i A',strtotime($profile['last_login_at'])):'')?></em></div></article>
        <article><span class="stat-icon-mini"><?=$profileIcon('shield')?></span><div><small>Account Status</small><b><?=e(ucfirst($profile['status']))?></b></div><strong class="status-chip success"><?=e(ucfirst($profile['status']))?></strong></article>
        <article><span class="stat-icon-mini"><?=$profileIcon('user')?></span><div><small>Role</small><b><?=e(str_replace('_',' ',ucwords($profile['role'],'_')))?></b></div></article>
      </div>
    </div>
  </aside>

  <section class="admin-panel admin-profile-unified-card">
    <header class="unified-profile-header"><span class="panel-icon-badge"><?=$profileIcon('user')?></span><div><h3>Edit Profile</h3><p>Manage your personal administrator profile from one clean workspace.</p></div></header>
    <form method="post" enctype="multipart/form-data" action="<?=url('admin/profile')?>" class="admin-form unified-profile-form" data-profile-personal-form><?=csrf_field()?>
      <section class="profile-form-section"><div class="profile-section-heading"><div><span class="profile-section-icon"><?=$profileIcon('user')?></span><div><h4>Personal Information</h4><p>Update the name displayed across the administration workspace.</p></div></div></div><div class="profile-field-grid"><label class="full"><span>Full Name</span><input name="name" value="<?=e($profile['name'])?>" required maxlength="120"></label></div></section>
      <section class="profile-form-section"><div class="profile-section-heading"><div><span class="profile-section-icon"><?=$profileIcon('photo')?></span><div><h4>Profile Picture</h4><p>Use a clear square portrait for the best header and profile appearance.</p></div></div></div><div class="profile-field-grid"><label class="full"><span>Upload New Photo</span><input type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp"></label><?php if(!empty($profile['profile_photo'])):?><div class="current-photo-row full"><div class="current-photo-preview"><img src="<?=e(public_upload($profile['profile_photo']))?>" alt="Current photo"></div><div class="current-photo-copy"><b>Current Photo</b><small>JPG, PNG or WEBP · Max 2MB</small></div></div><?php endif;?></div></section>
      <section class="profile-form-section"><div class="profile-section-heading"><div><span class="profile-section-icon"><?=$profileIcon('info')?></span><div><h4>Contact Information</h4><p>Your administrator email is used for secure sign-in and system notices.</p></div></div></div><div class="profile-field-grid"><label class="full"><span>Current Email Address</span><div class="email-status-field readonly-email-field"><input type="email" value="<?=e($profile['email'])?>" readonly><span class="email-verified-badge">✓ Sign-in email</span></div><small class="helper">Change your sign-in email and password from <a href="<?=url('admin/account-security')?>">Account Security</a>.</small></label></div></section>
      <section class="profile-form-section"><div class="profile-section-heading"><div><span class="profile-section-icon profile-social-section-icon"><?=social_icon_svg('link')?></span><div><h4>Social Media</h4><p>Organization social profiles are managed centrally and appear automatically in the public website footer.</p></div></div></div><div class="profile-social-overview"><?php foreach(($socialLinks??[]) as $sl):$kind=social_platform_key($sl['platform'],$sl['url']);?><a class="profile-social-chip social-kind-<?=e($kind)?>" href="<?=e($sl['url'])?>" target="_blank" rel="noopener noreferrer" title="<?=e($sl['label']?:$sl['platform'])?>"><?=social_icon_svg($sl['platform'],$sl['url'])?><span><?=e($sl['label']?:$sl['platform'])?></span><?php if(!$sl['is_active']):?><small>Inactive</small><?php endif;?></a><?php endforeach;?><?php if(empty($socialLinks)):?><span class="profile-social-empty">No organization social profiles have been added yet.</span><?php endif;?><a class="outline-link manage-social-link" href="<?=url('admin/social')?>">Manage Social Media ↗</a></div></section>
      <section class="profile-form-section admin-controlled-section"><div class="admin-controlled-note"><span><?=$profileIcon('info')?></span><div><b>Administrator-controlled information</b><p>Your account role, status, permissions and security policies are controlled by authorized administrators and are separated from editable profile fields.</p></div></div></section>
      <div class="profile-action-bar"><button class="admin-btn admin-btn-large">Save Profile</button><a class="outline-link profile-cancel-btn" href="<?=url('admin')?>">Cancel</a></div>
    </form>
  </section>
</div>
