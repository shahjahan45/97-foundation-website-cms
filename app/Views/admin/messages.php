<?php $audienceCounts=$audienceCounts??[];$broadcasts=$broadcasts??[];$brandLogo=foundation_logo_url(); ?>
<div class="page-actions message-page-actions"><div><h2>Portal Messages</h2><p>Send secure individual, multi-recipient or organization-wide messages to members, volunteers and administrators.</p></div><div class="message-audience-summary"><span><b><?=number_format((int)($audienceCounts['members']??0))?></b> Members</span><span><b><?=number_format((int)($audienceCounts['volunteers']??0))?></b> Volunteers</span><span><b><?=number_format((int)($audienceCounts['administrators']??0))?></b> Admins</span></div></div>
<div class="message-center-grid">
<section class="admin-panel broadcast-compose-card"><div class="panel-head"><div><h3>Compose Message</h3><span>Choose selected people or broadcast to an audience category.</span></div><span class="broadcast-secure-pill">🔒 Secure delivery</span></div>
<form method="post" action="<?=url('admin/messages/send')?>" class="admin-form broadcast-message-form" data-broadcast-form><?=csrf_field()?>
<label class="full"><span>Audience</span><select name="audience" data-message-audience><option value="selected">Selected users</option><option value="members">All Members (<?=number_format((int)($audienceCounts['members']??0))?>)</option><option value="volunteers">All Volunteers (<?=number_format((int)($audienceCounts['volunteers']??0))?>)</option><option value="administrators">All Administrators (<?=number_format((int)($audienceCounts['administrators']??0))?>)</option><option value="portal_users">All Members & Volunteers (<?=number_format((int)($audienceCounts['portal_users']??0))?>)</option><option value="all_users">All Active Users (<?=number_format((int)($audienceCounts['all_users']??0))?>)</option></select></label>
<div class="full recipient-picker v7-recipients" data-recipient-picker>
  <div class="v7-recipients-head">
    <div><b>Recipients</b><small data-recipient-count>None selected</small></div>
    <div class="v7-recipients-tools"><button type="button" class="outline-link mini" data-recipient-all>Select shown</button><button type="button" class="outline-link mini" data-recipient-none>Clear</button></div>
  </div>
  <label class="v7-search"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg><input type="search" placeholder="Search name, email or role" aria-label="Search recipients" data-recipient-search></label>
  <div class="v7-recipient-list recipient-checkbox-grid" data-recipient-list>
    <?php foreach($people as $p):$role=str_replace('_',' ',ucwords($p['role'],'_'));$idLabel=$p['member_number']?:($p['volunteer_id']?:'');$parts=preg_split('/\s+/',trim((string)$p['name']))?:[];$ini=strtoupper(substr($parts[0]??'U',0,1).substr($parts[1]??'',0,1));?>
    <label class="v7-recipient" data-recipient-row>
      <input type="checkbox" name="recipient_ids[]" value="<?=$p['id']?>">
      <span class="v7-recipient-avatar role-<?=e($p['role'])?>" aria-hidden="true"><?=e($ini)?></span>
      <span class="v7-recipient-copy"><b><?=e($p['name'])?></b><small><?=e($p['email'])?></small></span>
      <span class="v7-recipient-role role-<?=e($p['role'])?>"><?=e($role)?><?=$idLabel?' · '.e($idLabel):''?></span>
    </label>
    <?php endforeach;?>
    <?php if(empty($people)):?><p class="muted">No active users to message yet.</p><?php endif;?>
  </div>
</div>
<label class="full"><span>Subject</span><input name="subject" value="Message from 97 Foundation" maxlength="180" required></label>
<label class="full"><span>Message</span><textarea name="body" rows="7" required maxlength="5000" placeholder="Write a clear message for the selected recipients…"></textarea><small class="helper">Each recipient receives a private message in their own portal/admin inbox. Broadcast recipients are never exposed to one another.</small></label>
<div class="full broadcast-action-row"><div class="broadcast-safety"><b>✓ Private delivery</b><span>✓ Notification created</span><span>✓ Audited by the system</span></div><button class="admin-btn" type="submit">Send Message →</button></div>
</form></section>
<section class="admin-panel conversation-panel professional-conversation-panel"><div class="panel-head"><div><h3>Recent Conversations</h3><span>Latest individual message activity.</span></div><span>Latest first</span></div><div class="conversation-stream professional-message-stream"><?php foreach($messages as $m):$mine=(int)$m['sender_id']===(int)$user['id'];?><article class="message-bubble <?=$mine?'mine':'theirs'?>"><div><b><?=e($mine?'You → '.$m['recipient_name']:$m['sender_name'])?></b><small><?=e(date('d M Y · g:i A',strtotime($m['created_at'])))?></small></div><strong><?=e($m['subject']??'')?></strong><p><?=nl2br(e($m['body']))?></p><?php if(!$mine):?><button class="outline-link mini" type="button" data-quick-reply="<?=$m['sender_id']?>" data-name="<?=e($m['sender_name'])?>">Reply</button><?php endif;?></article><?php endforeach;?><?php if(!$messages):?><div class="message-empty-state"><b>No portal messages yet</b><span>Individual and broadcast messages will appear here.</span></div><?php endif;?></div></section>
</div>
<?php if($broadcasts):?><section class="admin-panel broadcast-history-card"><div class="panel-head"><div><h3>Broadcast History</h3><span>Recent multi-user announcements sent by administrators.</span></div></div><div class="broadcast-history-list"><?php foreach($broadcasts as $b):?><article><span class="broadcast-history-icon">✉</span><div><b><?=e($b['subject'])?></b><p><?=e(mb_strimwidth(strip_tags($b['body']),0,150,'…'))?></p><small><?=e(ucwords(str_replace('_',' ',$b['audience'])))?> · <?=number_format((int)$b['recipient_count'])?> recipients · <?=e($b['sender_name'])?> · <?=e(date('d M Y · g:i A',strtotime($b['created_at'])))?></small></div></article><?php endforeach;?></div></section><?php endif;?>
<?=admin_pagination($pagination??[])?>
