<?php
$selectedId = (int)($chat['id'] ?? 0);
$currentPage = (int)($pagination['page'] ?? 1);
$statusLabel = static fn(string $value): string => $value === 'waiting' ? 'Pending' : ($value === 'closed' ? 'Closed' : 'Open');
$isOnline = !empty($chat['is_online']);
$chatClosed = ($chat['status'] ?? '') === 'closed';
$chatNotes = (string)($chat['admin_notes'] ?? '');
?>
<div class="support-workspace" data-live-chat-admin
     data-feed-url="<?=e(url('admin/live-chat/feed'))?>"
     data-reply-url="<?=e(url('admin/live-chat/reply'))?>"
     data-status-url="<?=e(url('admin/live-chat/status'))?>"
     data-note-url="<?=e(url('admin/live-chat/note'))?>"
     data-delete-url="<?=e(url('admin/live-chat/delete'))?>"
     data-selected-chat="<?=$selectedId?>"
     data-current-status="<?=e($status??'')?>"
     data-current-query="<?=e($q??'')?>"
     data-current-page="<?=$currentPage?>" data-current-per-page="<?=e((string)($pagination['per_page']??25))?>" data-active-view="<?=$selectedId?'chat':'conversations'?>">

    <div class="chat-mobile-switcher" role="tablist" aria-label="Live chat workspace view">
        <button type="button" class="active" data-chat-view-button="conversations">Conversations</button>
        <button type="button" data-chat-view-button="chat">Chat</button>
        <button type="button" data-chat-view-button="details">Visitor Details</button>
    </div>

    <div class="live-chat-grid">
        <section class="support-panel conversation-panel" data-chat-panel="conversations">
            <div class="conversation-panel-head">
                <div>
                    <h2>Conversations</h2>
                    <p>Website support inbox</p>
                </div>
                <button type="button" class="icon-button" data-chat-refresh title="Refresh conversations" aria-label="Refresh conversations">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 11a8 8 0 1 0-2.3 5.7M20 4v7h-7"/></svg>
                </button>
            </div>

            <form class="conversation-search" method="get" action="<?=url('admin/live-chat')?>" data-chat-search-form data-no-loader>
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                <input type="search" name="q" value="<?=e($q??'')?>" placeholder="Search name, email, phone or chat #" autocomplete="off" data-chat-search>
                <?php if(!empty($status)):?><input type="hidden" name="status" value="<?=e($status)?>"><?php endif;?>
            </form>

            <nav class="conversation-tabs" aria-label="Conversation status filters">
                <a href="<?=url('admin/live-chat')?>" class="<?=($status??'')===''?'active':''?>" data-chat-filter data-status="">All <b data-count="total"><?=number_format((int)($counts['total']??0))?></b></a>
                <a href="<?=url('admin/live-chat?status=open')?>" class="<?=($status??'')==='open'?'active':''?>" data-chat-filter data-status="open">Open <b data-count="open"><?=number_format((int)($counts['open_count']??0))?></b></a>
                <a href="<?=url('admin/live-chat?status=waiting')?>" class="<?=($status??'')==='waiting'?'active':''?>" data-chat-filter data-status="waiting">Pending <b data-count="waiting"><?=number_format((int)($counts['waiting_count']??0))?></b></a>
                <a href="<?=url('admin/live-chat?status=closed')?>" class="<?=($status??'')==='closed'?'active':''?>" data-chat-filter data-status="closed">Closed <b data-count="closed"><?=number_format((int)($counts['closed_count']??0))?></b></a>
            </nav>

            <div class="conversation-list" data-chat-list>
                <?php foreach($conversations as $conversation):
                    $conversationOnline=!empty($conversation['is_online']);
                    $unread=(int)($conversation['unread_count']??0);
                ?>
                <a href="<?=url('admin/live-chat?chat='.$conversation['id'].(($status??'')?'&status='.urlencode($status):'').(($q??'')?'&q='.urlencode($q):''))?>"
                   class="conversation-item <?=$selectedId===(int)$conversation['id']?'active':''?>"
                   data-chat-select="<?=(int)$conversation['id']?>">
                    <span class="conversation-avatar"><?=e(strtoupper(substr($conversation['visitor_name'],0,1)))?><i class="<?=$conversationOnline?'online':''?>"></i></span>
                    <span class="conversation-copy">
                        <span class="conversation-name-line"><b><?=e($conversation['visitor_name'])?></b><time><?=e(date('H:i',strtotime($conversation['last_message_at'])))?></time></span>
                        <small><?=e($conversation['visitor_email'])?></small>
                        <span class="conversation-preview"><?=e(mb_strimwidth($conversation['last_message']??'',0,90,'…'))?></span>
                        <span class="conversation-meta"><em class="status-pill <?=e($conversation['status'])?>"><?=e($statusLabel($conversation['status']))?></em><?php if($unread):?><strong class="unread-count"><?=$unread?></strong><?php endif;?></span>
                    </span>
                </a>
                <?php endforeach;?>
                <?php if(!$conversations):?><div class="conversation-empty"><span>⌕</span><b>No conversations found</b><small>Try another search or status filter.</small></div><?php endif;?>
            </div>

            <div class="conversation-pagination" data-chat-pagination>
                <span>Showing <?=number_format((int)min(($pagination['total']??0), (($pagination['page']??1)*($pagination['per_page']??25))))?> of <?=number_format((int)($pagination['total']??0))?></span>
                <div class="conversation-page-controls">
                    <button type="button" data-chat-page="<?=max(1,(int)($pagination['page']??1)-1)?>" <?=($pagination['page']??1)<=1?'disabled':''?> aria-label="Previous page">‹</button>
                    <b>Page <?=number_format((int)($pagination['page']??1))?> / <?=number_format((int)($pagination['pages']??1))?></b>
                    <button type="button" data-chat-page="<?=min((int)($pagination['pages']??1),(int)($pagination['page']??1)+1)?>" <?=($pagination['page']??1)>=($pagination['pages']??1)?'disabled':''?> aria-label="Next page">›</button>
                </div>
                <label class="conversation-page-size"><span>Rows</span><select data-chat-per-page aria-label="Conversations per page"><?php foreach([10,20,25,50,100] as $size):?><option value="<?=$size?>" <?=$size===(int)($pagination['per_page']??25)?'selected':''?>><?=$size?></option><?php endforeach;?></select></label>
            </div>
        </section>

        <section class="support-panel chat-panel" data-chat-panel="chat">
            <div class="chat-empty-state <?=$chat?'is-hidden':''?>" data-chat-empty>
                <span class="empty-chat-icon"><svg viewBox="0 0 24 24"><path d="M20 11.5a7.5 7.5 0 0 1-8 7.5 8.7 8.7 0 0 1-3.3-.7L4 20l1.4-4.1A7.2 7.2 0 0 1 4 11.5 7.5 7.5 0 0 1 12 4a7.5 7.5 0 0 1 8 7.5Z"/><path d="M8.5 11.5h.01M12 11.5h.01M15.5 11.5h.01"/></svg></span>
                <h3>Select a conversation</h3>
                <p>Choose a visitor from the conversation list to review and reply without leaving this screen.</p>
            </div>

            <div class="chat-selected-shell <?=$chat?'':'is-hidden'?>" data-chat-selected>
                <header class="chat-header">
                    <div class="chat-person">
                        <span class="chat-avatar" data-chat-avatar><?=e(strtoupper(substr($chat['visitor_name']??'V',0,1)))?><i class="<?=$isOnline?'online':''?>" data-chat-presence-dot></i></span>
                        <div>
                            <div class="chat-name-row"><h2 data-chat-name><?=e($chat['visitor_name']??'Visitor')?></h2><span class="presence-label <?=$isOnline?'online':''?>" data-chat-presence><?=$chat?($isOnline?'Online':'Offline'):''?></span></div>
                            <p><span data-chat-email><?=e($chat['visitor_email']??'')?></span><span data-chat-phone-wrap><?=!empty($chat['visitor_phone'])?' · '.e($chat['visitor_phone']):''?></span> · <b>Chat #<span data-chat-id><?=$selectedId?></span></b></p>
                        </div>
                    </div>
                    <div class="chat-header-actions">
                        <form method="post" action="<?=url('admin/live-chat/status')?>" data-chat-status-form data-no-loader>
                            <?=csrf_field()?>
                            <input type="hidden" name="id" value="<?=$selectedId?>" data-chat-status-id>
                            <input type="hidden" name="return_status" value="<?=e($status??'')?>" data-return-status>
                            <input type="hidden" name="return_q" value="<?=e($q??'')?>" data-return-query>
                            <input type="hidden" name="return_page" value="<?=$currentPage?>" data-return-page><input type="hidden" name="return_per_page" value="<?=e((string)($pagination['per_page']??25))?>" data-return-per-page>
                            <select name="status" data-chat-status-select aria-label="Conversation status">
                                <option value="open" <?=($chat['status']??'')==='open'?'selected':''?>>Open</option>
                                <option value="waiting" <?=($chat['status']??'')==='waiting'?'selected':''?>>Pending</option>
                                <option value="closed" <?=($chat['status']??'')==='closed'?'selected':''?>>Closed</option>
                            </select>
                            <button type="submit" class="compact-button">Update</button>
                        </form>
                        <button type="button" class="compact-button close-conversation" data-chat-close-conversation <?=$chatClosed?'hidden':''?>>Close Chat</button>
                        <?php if(($user['role']??'')==='super_admin'):?>
                        <form method="post" action="<?=url('admin/live-chat/delete')?>" data-chat-delete-form data-no-loader>
                            <?=csrf_field()?>
                            <input type="hidden" name="id" value="<?=$selectedId?>" data-chat-delete-id>
                            <input type="hidden" name="return_status" value="<?=e($status??'')?>" data-return-status>
                            <input type="hidden" name="return_q" value="<?=e($q??'')?>" data-return-query>
                            <input type="hidden" name="return_page" value="<?=$currentPage?>" data-return-page><input type="hidden" name="return_per_page" value="<?=e((string)($pagination['per_page']??25))?>" data-return-per-page>
                            <button type="submit" class="compact-button danger">Delete</button>
                        </form>
                        <?php endif;?>
                    </div>
                </header>

                <div class="chat-live-bar"><span><i></i> Secure live conversation</span><small data-chat-sync-state>Auto-refresh active</small></div>

                <div class="admin-chat-messages" data-admin-chat-thread aria-live="polite">
                    <?php $lastDate=''; foreach($messages as $message): $dateKey=date('Y-m-d',strtotime($message['created_at'])); if($dateKey!==$lastDate): $lastDate=$dateKey; ?>
                        <div class="message-date-separator"><span><?=$dateKey===date('Y-m-d')?'Today':($dateKey===date('Y-m-d',strtotime('-1 day'))?'Yesterday':e(date('d M Y',strtotime($message['created_at']))))?></span></div>
                    <?php endif;?>
                    <div class="support-message <?=e($message['sender_type'])?>" data-message-id="<?=(int)$message['id']?>">
                        <span class="message-bubble-copy"><?=nl2br(e($message['message']))?></span>
                        <span class="message-footer"><b><?=e($message['sender_name']?:ucfirst($message['sender_type']))?></b><time><?=e(date('H:i',strtotime($message['created_at'])))?></time><?php if($message['sender_type']==='admin'):?><em>✓ Sent</em><?php endif;?></span>
                    </div>
                    <?php endforeach;?>
                </div>

                <form class="chat-composer" method="post" action="<?=url('admin/live-chat/reply')?>" data-chat-reply-form data-no-loader>
                    <?=csrf_field()?>
                    <input type="hidden" name="conversation_id" value="<?=$selectedId?>" data-chat-reply-id>
                    <input type="hidden" name="return_status" value="<?=e($status??'')?>" data-return-status>
                    <input type="hidden" name="return_q" value="<?=e($q??'')?>" data-return-query>
                    <input type="hidden" name="return_page" value="<?=$currentPage?>" data-return-page><input type="hidden" name="return_per_page" value="<?=e((string)($pagination['per_page']??25))?>" data-return-per-page>
                    <div class="composer-tabs"><button type="button" class="active">Reply</button><span class="composer-status" data-composer-status><?=$chatClosed?'Conversation closed':'Ready to reply'?></span></div>
                    <div class="composer-box">
                        <textarea name="message" maxlength="3000" rows="3" placeholder="Type your message…" <?=$chatClosed?'disabled':''?> data-chat-message-input></textarea>
                        <div class="composer-tools">
                            <small>Enter to send · Shift + Enter for new line</small>
                            <button type="submit" class="chat-send-button" <?=$chatClosed?'disabled':''?> data-chat-send-button><svg viewBox="0 0 24 24"><path d="m4 12 16-8-5 16-3-6-8-2Z"/><path d="m12 14 8-10"/></svg><span>Send</span></button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        <aside class="support-panel visitor-panel" data-chat-panel="details">
            <div class="visitor-scroll" data-visitor-details>
                <div class="visitor-empty <?=$chat?'is-hidden':''?>" data-visitor-empty><span>♙</span><h3>Visitor Details</h3><p>Select a conversation to view the complete support profile.</p></div>
                <div class="visitor-selected <?=$chat?'':'is-hidden'?>" data-visitor-selected>
                    <div class="visitor-card-head"><h3>Visitor Details</h3><span class="status-pill <?=e($chat['status']??'open')?>" data-detail-status><?=e($statusLabel($chat['status']??'open'))?></span></div>
                    <div class="visitor-identity">
                        <span class="visitor-avatar" data-detail-avatar><?=e(strtoupper(substr($chat['visitor_name']??'V',0,1)))?><i class="<?=$isOnline?'online':''?>" data-detail-presence-dot></i></span>
                        <h2 data-detail-name><?=e($chat['visitor_name']??'Visitor')?></h2>
                        <span class="detail-presence <?=$chatClosed?'inactive':($isOnline?'online':'')?>" data-detail-presence><?=$chatClosed?'Inactive / Closed':($isOnline?'Active now':'Active conversation')?></span>
                    </div>
                    <dl class="visitor-details-list">
                        <div><dt><span>✉</span>Email</dt><dd data-detail-email><?=e($chat['visitor_email']??'—')?></dd></div>
                        <div><dt><span>☎</span>Phone</dt><dd data-detail-phone><?=e($chat['visitor_phone']??'')?:'Not provided'?></dd></div>
                        <div><dt><span>▤</span>Category</dt><dd data-detail-category><?=e(ucwords(str_replace('_',' ',$chat['category']??'general')))?></dd></div>
                        <div><dt><span>♙</span>Assigned administrator</dt><dd data-detail-assigned><?=e($chat['assigned_name']??'')?:'Unassigned'?></dd></div>
                        <div><dt><span>◷</span>Started</dt><dd data-detail-started><?=$chat?e(date('d M Y · H:i',strtotime($chat['created_at']))):'—'?></dd></div>
                        <div><dt><span>↻</span>Last activity</dt><dd data-detail-activity><?=$chat?e(date('d M Y · H:i',strtotime($chat['last_message_at']))):'—'?></dd></div>
                        <div><dt><span>#</span>Conversation ID</dt><dd>#<span data-detail-id><?=$selectedId?></span></dd></div>
                    </dl>
                    <div class="support-notes-card">
                        <div><h3>Support Notes</h3><small>Visible to authorized administrators only.</small></div>
                        <form method="post" action="<?=url('admin/live-chat/note')?>" data-chat-note-form data-no-loader>
                            <?=csrf_field()?>
                            <input type="hidden" name="id" value="<?=$selectedId?>" data-chat-note-id>
                            <input type="hidden" name="return_status" value="<?=e($status??'')?>" data-return-status>
                            <input type="hidden" name="return_q" value="<?=e($q??'')?>" data-return-query>
                            <input type="hidden" name="return_page" value="<?=$currentPage?>" data-return-page><input type="hidden" name="return_per_page" value="<?=e((string)($pagination['per_page']??25))?>" data-return-per-page>
                            <textarea name="admin_notes" maxlength="5000" rows="5" placeholder="Add internal notes about this visitor or conversation…" data-chat-note-input <?=empty($features['notes'])?'disabled':''?>><?=e($chatNotes)?></textarea>
                            <button type="submit" class="compact-button" <?=empty($features['notes'])?'disabled':''?> data-chat-note-save>Save Note</button>
                            <?php if(empty($features['notes'])):?><small class="migration-hint">Run <code>database/upgrade_v4.php</code> once to enable persistent support notes.</small><?php endif;?>
                        </form>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <div class="chat-toast-stack" data-chat-toast-stack aria-live="polite"></div>
</div>
