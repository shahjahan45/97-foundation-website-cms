<div class="page-actions notification-page-actions"><div><h2>Notification Centre</h2><p>Foundation announcements, account updates, messages and membership activity in one secure place.</p></div><button class="outline-link notification-page-mark-all" type="button" data-notification-mark-all>✓ Mark all as read</button></div>
<section class="admin-panel notification-page-shell">
  <div class="notification-page-head"><div><span class="notification-page-kicker">MEMBER PORTAL NOTIFICATIONS</span><h3>Your notifications</h3><p>Unread updates stay highlighted until you mark them as read.</p></div><span class="notification-page-summary"><b><?=number_format(unread_notifications((int)$user['id']))?></b> unread</span></div>
  <div class="notification-page-list" data-notification-page-list>
    <?php foreach($notifications as $n):$read=!empty($n['read_at']);$kind=preg_replace('/[^a-z0-9_-]/','-',strtolower((string)($n['type']??'info')))?:'info';?>
    <article class="notification-page-item <?=$read?'is-read':'is-unread'?>" data-notification-item data-notification-id="<?=$n['id']?>" data-notification-type="<?=e($kind)?>">
      <span class="notification-page-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg></span>
      <div class="notification-page-copy"><div class="notification-page-title-row"><h4><?=e($n['title'])?></h4><span class="notification-read-state"><?=$read?'Read':'Unread'?></span></div><p><?=nl2br(e($n['message']))?></p><div class="notification-page-meta"><time><?=e(date('d M Y · g:i A',strtotime($n['created_at'])))?></time><span><?=e(ucwords(str_replace(['_','-'],' ',(string)($n['type']??'info'))))?></span></div></div>
      <div class="notification-page-actions-row"><?php if(!$read):?><button type="button" class="notification-read-button" data-notification-read data-notification-id="<?=$n['id']?>">Mark as read</button><?php endif;?><?php if(!empty($n['link_url'])):?><a class="notification-open-link" href="<?=e(url($n['link_url']))?>" data-portal-link>Open related item ↗</a><?php endif;?></div>
    </article>
    <?php endforeach;?>
    <?php if(!$notifications):?><div class="notification-empty-state"><span>✓</span><h4>You're all caught up</h4><p>No notifications are available yet.</p></div><?php endif;?>
  </div>
</section>
<?=admin_pagination($pagination??[])?>
