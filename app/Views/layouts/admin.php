<?php
$u=$user??\App\Core\Auth::user();
$unread=unread_notifications((int)($u['id']??0));
$logo=setting('foundation','logo_path','');
$logoEnabled=setting('foundation','logo_enabled','1')==='1';
$favicon=setting('foundation','favicon_path','');
$path=current_path();
try{$pendingVolunteerCount=(int)(\App\Core\Database::one('SELECT COUNT(*) c FROM volunteers WHERE status="pending"')['c']??0);}catch(\Throwable){$pendingVolunteerCount=0;}
try{$pendingMemberCount=(int)(\App\Core\Database::one('SELECT COUNT(*) c FROM users WHERE role="member" AND status="pending"')['c']??0);}catch(\Throwable){$pendingMemberCount=0;}
try{$openLiveChatCount=(int)(\App\Core\Database::one('SELECT COUNT(*) c FROM chat_conversations WHERE status<>"closed"')['c']??0);}catch(\Throwable){$openLiveChatCount=0;}
$isFundraising=str_starts_with($path,'/admin/campaigns')||str_starts_with($path,'/admin/donations')||str_starts_with($path,'/admin/donation-settings')||str_starts_with($path,'/admin/online-payments');
$isPeople=str_starts_with($path,'/admin/volunteers')||str_starts_with($path,'/admin/members')||str_starts_with($path,'/admin/officials');
$isSupport=str_starts_with($path,'/admin/messages')||str_starts_with($path,'/admin/live-chat');
$isContent=str_starts_with($path,'/admin/content')||str_starts_with($path,'/admin/causes')||str_starts_with($path,'/admin/events')||str_starts_with($path,'/admin/gallery')||str_starts_with($path,'/admin/posts')||str_starts_with($path,'/admin/branding');
$isSystem=str_starts_with($path,'/admin/administrators')||str_starts_with($path,'/admin/social')||str_starts_with($path,'/admin/security')||str_starts_with($path,'/admin/account-security')||str_starts_with($path,'/admin/profile');
$isDeveloper=str_starts_with($path,'/admin/developer');
$bodyClass=trim('admin-v7 '.(string)($adminBodyClass??''));
$brandLogo=$logoEnabled?brand_logo_url():'';
[$crumbSection,$crumbPage]=admin_breadcrumb($path,(string)($adminTitle??'Dashboard'));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?=e($adminTitle??'Admin')?> | 97 Foundation</title>
<?php $tabIcon=$favicon?public_upload($favicon):($logo?public_upload($logo):brand_asset('icon')); if($tabIcon):?><link rel="icon" href="<?=e($tabIcon)?>"><link rel="apple-touch-icon" href="<?=e($tabIcon)?>"><?php endif;?>
<link rel="stylesheet" href="<?=asset('css/admin.css')?>">
<link rel="stylesheet" href="<?=asset('css/admin-v7.css')?>">
</head>
<body class="<?=e($bodyClass)?>">
<div id="admin-loader" class="admin-loader show" aria-hidden="false"><div><?php if($brandLogo):?><img src="<?=e($brandLogo)?>" alt=""><?php else:?><b>97</b><?php endif;?><i></i><span>Loading secure workspace…</span></div></div>
<div class="admin-shell">
<aside class="sidebar" aria-label="Administration">
    <a class="side-brand" href="<?=url('admin')?>">
        <?php if($brandLogo):?><img class="admin-brand-logo" src="<?=e($brandLogo)?>" alt=""><?php else:?><span class="admin-logo">97</span><?php endif;?>
        <div><b>97 Foundation</b><small>Administration</small></div>
    </a>

    <nav aria-label="Admin navigation">
        <p class="side-label">Overview</p>
        <a class="side-dashboard <?=nav_active('admin')?>" href="<?=url('admin')?>"><span class="side-nav-icon"><?=admin_icon('dashboard')?></span><span>Dashboard</span></a>

        <p class="side-label">Manage</p>
        <div class="side-group <?=$isFundraising?'open active':''?>" data-side-group>
            <button type="button" class="side-parent" data-side-toggle aria-expanded="<?=$isFundraising?'true':'false'?>"><span class="side-nav-icon"><?=admin_icon('fundraising')?></span><span>Fundraising</span><i><?=admin_icon('chevron')?></i></button>
            <div class="side-submenu">
                <a class="<?=nav_active('admin/campaigns')?>" href="<?=url('admin/campaigns')?>"><span class="side-sub-icon"><?=admin_icon('campaign')?></span><span>Campaigns</span></a>
                <a class="<?=nav_active('admin/donations')?>" href="<?=url('admin/donations')?>"><span class="side-sub-icon"><?=admin_icon('donation')?></span><span>Donations</span></a>
                <a class="<?=nav_active('admin/donation-settings')?>" href="<?=url('admin/donation-settings')?>"><span class="side-sub-icon"><?=admin_icon('bank')?></span><span>Donation Settings</span></a>
                <?php if(\App\Core\Auth::can('view_online_payments')):?><a class="<?=nav_active('admin/online-payments',true)?>" href="<?=url('admin/online-payments')?>"><span class="side-sub-icon"><?=admin_icon('card')?></span><span>Online Payments</span></a><?php endif;?>
            </div>
        </div>

        <div class="side-group <?=$isPeople?'open active':''?>" data-side-group>
            <button type="button" class="side-parent" data-side-toggle aria-expanded="<?=$isPeople?'true':'false'?>"><span class="side-nav-icon"><?=admin_icon('people')?></span><span>People & Community</span><?php if($pendingVolunteerCount+$pendingMemberCount):?><em><?=$pendingVolunteerCount+$pendingMemberCount?></em><?php endif;?><i><?=admin_icon('chevron')?></i></button>
            <div class="side-submenu">
                <a class="<?=nav_active('admin/officials')?>" href="<?=url('admin/officials')?>"><span class="side-sub-icon"><?=admin_icon('officials')?></span><span>Officials</span></a>
                <a class="<?=nav_active('admin/members')?>" href="<?=url('admin/members')?>"><span class="side-sub-icon"><?=admin_icon('member')?></span><span>Member List</span></a>
                <a class="<?=nav_active('admin/members/registrations')?>" href="<?=url('admin/members/registrations')?>"><span class="side-sub-icon"><?=admin_icon('registration')?></span><span>Member Registrations</span><?php if($pendingMemberCount):?><em><?=$pendingMemberCount?></em><?php endif;?></a>
                <a class="<?=nav_active('admin/volunteers')?>" href="<?=url('admin/volunteers')?>"><span class="side-sub-icon"><?=admin_icon('volunteer')?></span><span>Volunteer List</span></a>
                <a class="<?=nav_active('admin/volunteers/applications')?>" href="<?=url('admin/volunteers/applications')?>" title="Volunteer Registration Management"><span class="side-sub-icon"><?=admin_icon('registration')?></span><span>Volunteer Applications</span><?php if($pendingVolunteerCount):?><em><?=$pendingVolunteerCount?></em><?php endif;?></a>
            </div>
        </div>

        <div class="side-group <?=$isSupport?'open active':''?>" data-side-group>
            <button type="button" class="side-parent" data-side-toggle aria-expanded="<?=$isSupport?'true':'false'?>"><span class="side-nav-icon"><?=admin_icon('support')?></span><span>Support & Messages</span><?php if($openLiveChatCount):?><em><?=$openLiveChatCount?></em><?php endif;?><i><?=admin_icon('chevron')?></i></button>
            <div class="side-submenu">
                <a class="<?=nav_active('admin/messages')?>" href="<?=url('admin/messages')?>"><span class="side-sub-icon"><?=admin_icon('mail')?></span><span>Portal Messages</span></a>
                <a class="<?=nav_active('admin/live-chat')?>" href="<?=url('admin/live-chat')?>"><span class="side-sub-icon"><?=admin_icon('chat')?></span><span>Website Live Chat</span><?php if($openLiveChatCount):?><em><?=$openLiveChatCount?></em><?php endif;?></a>
            </div>
        </div>

        <div class="side-group <?=$isContent?'open active':''?>" data-side-group>
            <button type="button" class="side-parent" data-side-toggle aria-expanded="<?=$isContent?'true':'false'?>"><span class="side-nav-icon"><?=admin_icon('content')?></span><span>Content & Website</span><i><?=admin_icon('chevron')?></i></button>
            <div class="side-submenu">
                <a class="<?=nav_active('admin/content')?>" href="<?=url('admin/content')?>"><span class="side-sub-icon"><?=admin_icon('edit')?></span><span>Website Content</span></a>
                <a class="<?=nav_active('admin/causes')?>" href="<?=url('admin/causes')?>"><span class="side-sub-icon"><?=admin_icon('heart')?></span><span>Cause Areas</span></a>
                <a class="<?=nav_active('admin/events')?>" href="<?=url('admin/events')?>"><span class="side-sub-icon"><?=admin_icon('event')?></span><span>Events</span></a>
                <a class="<?=nav_active('admin/gallery')?>" href="<?=url('admin/gallery')?>"><span class="side-sub-icon"><?=admin_icon('gallery')?></span><span>Gallery</span></a>
                <a class="<?=nav_active('admin/posts')?>" href="<?=url('admin/posts')?>"><span class="side-sub-icon"><?=admin_icon('news')?></span><span>News & Blog</span></a>
                <a class="<?=nav_active('admin/branding')?>" href="<?=url('admin/branding')?>"><span class="side-sub-icon"><?=admin_icon('brand')?></span><span>Branding & SEO</span></a>
            </div>
        </div>

        <p class="side-label">Settings</p>
        <?php if(\App\Core\Auth::can('manage_payment_gateway')):?><div class="side-group <?=$isDeveloper?'open active':''?>" data-side-group>
            <button type="button" class="side-parent" data-side-toggle aria-expanded="<?=$isDeveloper?'true':'false'?>"><span class="side-nav-icon"><?=admin_icon('developer')?></span><span>Developer Integration</span><i><?=admin_icon('chevron')?></i></button>
            <div class="side-submenu"><a class="<?=nav_active('admin/developer/payment-gateway',true)?>" href="<?=url('admin/developer/payment-gateway')?>" title="Payment Gateway"><span class="side-sub-icon"><?=admin_icon('plug')?></span><span>Easy Payment Setup</span></a></div>
        </div><?php endif;?>

        <div class="side-group <?=$isSystem?'open active':''?>" data-side-group>
            <button type="button" class="side-parent" data-side-toggle aria-expanded="<?=$isSystem?'true':'false'?>"><span class="side-nav-icon"><?=admin_icon('system')?></span><span>System</span><i><?=admin_icon('chevron')?></i></button>
            <div class="side-submenu">
                <?php if(($u['role']??'')==='super_admin'):?><a class="<?=nav_active('admin/administrators')?>" href="<?=url('admin/administrators')?>"><span class="side-sub-icon"><?=admin_icon('admins')?></span><span>Administrators</span></a><?php endif;?>
                <a class="<?=nav_active('admin/social')?>" href="<?=url('admin/social')?>"><span class="side-sub-icon"><?=admin_icon('share')?></span><span>Social Media</span></a>
                <a class="<?=nav_active('admin/security')?>" href="<?=url('admin/security')?>"><span class="side-sub-icon"><?=admin_icon('shield')?></span><span>Security & 2FA</span></a>
                <a class="<?=nav_active('admin/account-security')?>" href="<?=url('admin/account-security')?>"><span class="side-sub-icon"><?=admin_icon('key')?></span><span>Account Security</span></a>
                <a class="<?=nav_active('admin/profile')?>" href="<?=url('admin/profile')?>"><span class="side-sub-icon"><?=admin_icon('user')?></span><span>Admin Account</span></a>
            </div>
        </div>
        <div class="side-external-wrap"><a class="side-external" href="<?=url('')?>" target="_blank" rel="noopener"><span class="side-nav-icon"><?=admin_icon('external')?></span><span>View Website</span></a></div>
    </nav>

    <div class="side-footer">
        <?php $sideName=trim((string)($u['name']??'Administrator'));$sideInitials='';foreach(array_slice(preg_split('/\s+/',$sideName)?:[],0,2) as $part)$sideInitials.=mb_strtoupper(mb_substr($part,0,1));?>
        <a class="side-profile <?=nav_active('admin/profile')?>" href="<?=url('admin/profile')?>" title="View my profile">
            <span class="side-avatar"><?php if(!empty($u['profile_photo'])):?><img src="<?=e(public_upload($u['profile_photo']))?>" alt=""><?php else:?><?=e($sideInitials?:'A')?><?php endif;?><i aria-hidden="true"></i></span>
            <span class="side-profile-text"><b><?=e($sideName)?></b><small><?=e(str_replace('_',' ',ucwords($u['role']??'administrator','_')))?></small></span>
            <span class="side-profile-go" aria-hidden="true"><?=admin_icon('chevron')?></span>
        </a>
        <form action="<?=url('admin/logout')?>" method="post" class="side-signout-form"><?=csrf_field()?><button type="submit" class="side-signout"><?=admin_icon('logout')?><span>Sign out</span></button></form>
    </div>
</aside>
<div class="sidebar-scrim" data-admin-menu-close hidden></div>

<section class="admin-main">
    <header class="admin-top">
        <div class="admin-title-area"><button class="admin-menu" type="button" data-admin-menu aria-label="Open navigation"><?=admin_icon('menu')?></button><div><nav class="admin-crumbs" aria-label="Breadcrumb"><span><?=e($crumbSection)?></span><?=admin_icon('chevron')?><span aria-current="page"><?=e($crumbPage)?></span></nav><h1><?=e($adminTitle??'Admin Dashboard')?></h1></div></div>
        <div class="admin-user">
            <label class="search"><?=admin_icon('search')?><input type="search" placeholder="Search menu…" data-admin-search aria-label="Search admin navigation"><kbd>/</kbd></label>
            <a class="top-icon-btn" href="<?=url('')?>" target="_blank" rel="noopener" title="View website" aria-label="View website"><?=admin_icon('external')?></a>
            <button class="bell notification-bell top-icon-btn" type="button" data-notification-toggle aria-label="Open notifications" aria-expanded="false" title="Notifications"><svg class="bell-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><i data-notification-count <?=$unread?'':'hidden'?>><?=$unread?></i></button>
            <a class="avatar-wrap" href="<?=url('admin/profile')?>"><?php if(!empty($u['profile_photo'])):?><img class="avatar-img" src="<?=e(public_upload($u['profile_photo']))?>" alt="Profile"><?php else:?><span class="avatar"><?=e(strtoupper(substr($u['name']??'A',0,1)))?></span><?php endif;?><div><b><?=e($u['name']??'Admin')?></b><small><?=e(str_replace('_',' ',ucwords($u['role']??'administrator','_')))?></small></div></a>
        </div>
    </header>
    <div class="admin-content"><?php if($s=flash('success')):?><div class="alert success" data-auto-dismiss><?=e($s)?></div><?php endif;?><?php if($s=flash('error')):?><div class="alert danger" data-auto-dismiss><?=e($s)?></div><?php endif;?><?=$content?></div>
</section>
</div>
<?=notification_panel_shell('admin',$unread)?>
<div class="modal-shell" data-modal-shell><div class="modal-backdrop" data-modal-close></div><div class="modal-card"><button class="modal-x" data-modal-close>×</button><div data-modal-content></div></div></div>
<?php if(($adminPage??'')==='member-details'):?><script src="<?=asset('js/vendor/qrcode.min.js')?>" defer></script><?php endif;?><script src="<?=asset('js/admin.js')?>" defer></script><script src="<?=asset('js/notifications.js')?>" defer></script><script src="<?=asset('js/admin-v7.js')?>" defer></script>
<?php if(($adminPage??'')==='live-chat'):?><script src="<?=asset('js/admin-live-chat.js')?>" defer></script><?php endif;?>
</body>
</html>
