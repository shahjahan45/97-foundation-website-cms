<?php
$u=$user??\App\Core\Auth::user();$vol=$volunteer??null;$mem=$member??null;$unread=unread_notifications((int)($u['id']??0));$logo=setting('foundation','logo_path','');$logoEnabled=setting('foundation','logo_enabled','1')==='1';$favicon=setting('foundation','favicon_path','');
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title><?=e($adminTitle??'My Portal')?> | 97 Foundation</title><?php $tabIcon=$favicon?public_upload($favicon):($logo?public_upload($logo):''); if($tabIcon):?><link rel="icon" href="<?=e($tabIcon)?>"><link rel="apple-touch-icon" href="<?=e($tabIcon)?>"><?php endif;?><link rel="stylesheet" href="<?=asset('css/admin.css')?>"></head>
<body class="portal-body" data-portal-app>
<div id="admin-loader" class="portal-route-loader" aria-hidden="true"><span class="portal-route-spinner"><?php if($logoEnabled&&$logo):?><img src="<?=e(public_upload($logo))?>" alt=""><?php else:?><svg viewBox="0 0 40 40" aria-hidden="true"><circle cx="20" cy="20" r="17"></circle><text x="20" y="25" text-anchor="middle">97</text></svg><?php endif;?></span><small>Loading</small></div>
<div class="admin-shell portal-shell">
<aside class="sidebar portal-sidebar">
  <a class="side-brand" href="<?=url('portal')?>" data-portal-link><?php if($logoEnabled&&$logo):?><img class="admin-brand-logo" src="<?=e(public_upload($logo))?>" alt="97 Foundation"><?php else:?><span class="admin-logo">97</span><?php endif;?><div><b>97 FOUNDATION</b><small>Member Portal</small></div></a>
  <nav aria-label="Member portal navigation" class="portal-nav-v58">
    <a class="<?=nav_active('portal')?>" href="<?=url('portal')?>" data-portal-link><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg><span>Dashboard</span></a>
    <a class="<?=nav_active('portal/profile')?>" href="<?=url('portal/profile')?>" data-portal-link><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c.7-4.2 3.4-6.5 8-6.5s7.3 2.3 8 6.5"/></svg><span>My Profile</span></a>
    <a class="<?=nav_active('portal/id')?>" href="<?=url('portal/id')?>" data-portal-link><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="3"/><circle cx="8" cy="11" r="2"/><path d="M12 10h5M12 14h4"/></svg><span><?=$u['role']==='volunteer'?'My Volunteer ID':'My Member ID'?></span></a>
    <a class="<?=nav_active('portal/messages')?>" href="<?=url('portal/messages')?>" data-portal-link><svg viewBox="0 0 24 24"><path d="M4 5h16v12H8l-4 3V5Z"/><path d="m7 8 5 4 5-4"/></svg><span>Messages</span></a>
    <a class="<?=nav_active('portal/notifications')?>" href="<?=url('portal/notifications')?>" data-portal-link><svg viewBox="0 0 24 24"><path d="M18 9a6 6 0 0 0-12 0c0 6-3 6-3 8h18c0-2-3-2-3-8M10 21h4"/></svg><span>Notifications</span><em data-notification-count <?=$unread?'':'hidden'?>><?=$unread?></em></a>
    <a class="<?=nav_active('portal/password')?>" href="<?=url('portal/password')?>" data-portal-link><svg viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="11" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg><span>Security & Password</span></a>
    <a href="<?=url('')?>" target="_blank"><svg viewBox="0 0 24 24"><path d="M14 5h5v5M19 5l-8 8"/><path d="M19 13v6H5V5h6"/></svg><span>View Website</span></a>
  </nav>
  <div class="side-footer"><small>Signed in as</small><b><?=e($u['name'])?></b><form action="<?=url('logout')?>" method="post"><?=csrf_field()?><button>⇥ Logout</button></form></div>
</aside>
<section class="admin-main">
  <header class="admin-top"><div><button class="admin-menu" data-admin-menu>☰</button><h1 data-portal-title><?=e($adminTitle??'My Portal')?></h1><small data-portal-subtitle><?=e($u['role']==='volunteer'?'Approved Volunteer Portal':'Member Portal')?></small></div><div class="admin-user" data-portal-userbar><button class="bell notification-bell" type="button" data-notification-toggle aria-label="Open notifications" aria-expanded="false"><svg class="bell-svg" viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg><i data-notification-count <?=$unread?'':'hidden'?>><?=$unread?></i></button><a class="avatar-wrap" href="<?=url('portal/profile')?>" data-portal-link><?php if(!empty($u['profile_photo'])):?><img class="avatar-img" src="<?=e(public_upload($u['profile_photo']))?>" alt="Profile"><?php else:?><span class="avatar"><?=e(strtoupper(substr($u['name'],0,1)))?></span><?php endif;?><div><b><?=e($u['name'])?></b><small><?=e(ucfirst($u['role']))?></small></div></a></div></header>
  <div class="admin-content" data-portal-content><?php if($s=flash('success')):?><div class="alert success" data-auto-dismiss><?=e($s)?></div><?php endif;?><?php if($s=flash('error')):?><div class="alert danger" data-auto-dismiss><?=e($s)?></div><?php endif;?><?=$content?></div>
</section>
</div>
<?=notification_panel_shell('portal',$unread)?>
<script src="<?=asset('js/vendor/qrcode.min.js')?>" defer></script><script src="<?=asset('js/admin.js')?>" defer></script><script src="<?=asset('js/portal.js')?>" defer></script><script src="<?=asset('js/notifications.js')?>" defer></script>
</body></html>
