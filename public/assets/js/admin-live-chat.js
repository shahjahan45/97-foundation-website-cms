(()=>{
'use strict';
const qs=(s,r=document)=>r.querySelector(s),qsa=(s,r=document)=>[...r.querySelectorAll(s)];
const esc=value=>String(value??'').replace(/[&<>'"]/g,ch=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch]));
const titleCase=value=>String(value??'').replace(/_/g,' ').replace(/\b\w/g,m=>m.toUpperCase());
const root=qs('[data-live-chat-admin]');
if(!root)return;

const state={
  selected:Number(root.dataset.selectedChat||0),
  status:root.dataset.currentStatus||'',
  q:root.dataset.currentQuery||'',
  page:Number(root.dataset.currentPage||1),
  perPage:Number(root.dataset.currentPerPage||25),
  loading:false,
  lastMessageId:0,
  noteDirty:false,
  poll:null,
  searchTimer:null
};
const urls={feed:root.dataset.feedUrl,reply:root.dataset.replyUrl,status:root.dataset.statusUrl,note:root.dataset.noteUrl,delete:root.dataset.deleteUrl};
const list=qs('[data-chat-list]',root),pager=qs('[data-chat-pagination]',root),thread=qs('[data-admin-chat-thread]',root),selectedShell=qs('[data-chat-selected]',root),emptyShell=qs('[data-chat-empty]',root),visitorSelected=qs('[data-visitor-selected]',root),visitorEmpty=qs('[data-visitor-empty]',root),syncState=qs('[data-chat-sync-state]',root),searchInput=qs('[data-chat-search]',root),replyForm=qs('[data-chat-reply-form]',root),statusForm=qs('[data-chat-status-form]',root),deleteForm=qs('[data-chat-delete-form]',root),noteForm=qs('[data-chat-note-form]',root),noteInput=qs('[data-chat-note-input]',root),messageInput=qs('[data-chat-message-input]',root),sendButton=qs('[data-chat-send-button]',root);

function toast(message,type='success'){
  const stack=qs('[data-chat-toast-stack]',root);if(!stack)return;
  const el=document.createElement('div');el.className='chat-toast '+type;el.textContent=message;stack.appendChild(el);
  setTimeout(()=>{el.style.opacity='0';el.style.transform='translateY(5px)';setTimeout(()=>el.remove(),220)},2600);
}
function setSync(text='Auto-refresh active',syncing=false){if(!syncState)return;syncState.textContent=text;syncState.classList.toggle('syncing',syncing)}
function headers(){return {'Accept':'application/json','X-Requested-With':'97Foundation-AdminChat'}}
async function jsonResponse(response){
  const text=await response.text();let data={};try{data=text?JSON.parse(text):{};}catch{data={ok:false,message:text||'Unexpected server response.'}}
  if(!response.ok||data.ok===false)throw new Error(data.message||`Request failed (${response.status})`);return data;
}
function feedUrl(){const u=new URL(urls.feed,location.href);if(state.selected)u.searchParams.set('chat',state.selected);if(state.status)u.searchParams.set('status',state.status);if(state.q)u.searchParams.set('q',state.q);if(state.page>1)u.searchParams.set('page',state.page);if(state.perPage&&state.perPage!==25)u.searchParams.set('per_page',state.perPage);return u.toString()}
function updateUrl(){const u=new URL(location.href);u.search='';if(state.selected)u.searchParams.set('chat',state.selected);if(state.status)u.searchParams.set('status',state.status);if(state.q)u.searchParams.set('q',state.q);if(state.page>1)u.searchParams.set('page',state.page);if(state.perPage&&state.perPage!==25)u.searchParams.set('per_page',state.perPage);history.replaceState({liveChat:true},'',u.pathname+(u.search?u.search:''));}
function setReturnFields(){qsa('[data-return-status]',root).forEach(x=>x.value=state.status);qsa('[data-return-query]',root).forEach(x=>x.value=state.q);qsa('[data-return-page]',root).forEach(x=>x.value=state.page);qsa('[data-return-per-page]',root).forEach(x=>x.value=state.perPage)}
function switchView(view){root.dataset.activeView=view;qsa('[data-chat-view-button]',root).forEach(btn=>btn.classList.toggle('active',btn.dataset.chatViewButton===view))}
function onlineLabel(chat){if(chat.status==='closed')return 'Inactive / Closed';return chat.is_online?'Active now':'Active conversation'}

function renderCounts(counts={}){
  const map={total:counts.total??0,open:counts.open??0,waiting:counts.waiting??0,closed:counts.closed??0};
  Object.entries(map).forEach(([key,value])=>{const el=qs(`[data-count="${key}"]`,root);if(el)el.textContent=Number(value).toLocaleString()});
  qsa('[data-chat-filter]',root).forEach(a=>a.classList.toggle('active',(a.dataset.status||'')===state.status));
}
function renderConversations(items=[]){
  if(!list)return;
  if(!items.length){list.innerHTML='<div class="conversation-empty"><span>⌕</span><b>No conversations found</b><small>Try another search or status filter.</small></div>';return;}
  list.innerHTML=items.map(c=>{
    const unread=Number(c.unread_count||0);
    return `<a href="#" class="conversation-item ${Number(c.id)===state.selected?'active':''}" data-chat-select="${Number(c.id)}">
      <span class="conversation-avatar">${esc((c.visitor_name||'V').charAt(0).toUpperCase())}<i class="${c.is_online?'online':''}"></i></span>
      <span class="conversation-copy">
        <span class="conversation-name-line"><b>${esc(c.visitor_name)}</b><time>${esc(c.last_time)}</time></span>
        <small>${esc(c.visitor_email)}</small>
        <span class="conversation-preview">${esc(c.last_message||'')}</span>
        <span class="conversation-meta"><em class="status-pill ${esc(c.status)}">${esc(c.status_label)}</em>${unread?`<strong class="unread-count">${unread}</strong>`:''}</span>
      </span>
    </a>`;
  }).join('');
}
function renderPagination(p={}){
  if(!pager)return;const page=Number(p.page||1),pages=Number(p.pages||1),total=Number(p.total||0),perPage=Number(p.per_page||state.perPage||25);state.perPage=perPage;
  const end=Math.min(total,page*perPage);const sizes=[10,20,25,50,100];if(!sizes.includes(perPage))sizes.push(perPage);sizes.sort((a,b)=>a-b);
  pager.innerHTML=`<span>Showing ${end.toLocaleString()} of ${total.toLocaleString()}</span><div class="conversation-page-controls"><button type="button" data-chat-page="${Math.max(1,page-1)}" ${page<=1?'disabled':''} aria-label="Previous page">‹</button><b>Page ${page} / ${pages}</b><button type="button" data-chat-page="${Math.min(pages,page+1)}" ${page>=pages?'disabled':''} aria-label="Next page">›</button></div><label class="conversation-page-size"><span>Rows</span><select data-chat-per-page aria-label="Conversations per page">${sizes.map(n=>`<option value="${n}" ${n===perPage?'selected':''}>${n}</option>`).join('')}</select></label>`;
}
function renderMessages(messages=[],force=false){
  if(!thread)return;const newest=messages.length?Number(messages[messages.length-1].id||0):0;
  if(!force&&newest===state.lastMessageId)return;
  let currentDate='';let html='';
  messages.forEach(m=>{if(m.date_key!==currentDate){currentDate=m.date_key;html+=`<div class="message-date-separator"><span>${esc(m.date_label)}</span></div>`}
    html+=`<div class="support-message ${esc(m.sender_type)}" data-message-id="${Number(m.id)}"><span class="message-bubble-copy">${esc(m.message).replace(/\n/g,'<br>')}</span><span class="message-footer"><b>${esc(m.sender_name)}</b><time>${esc(m.time_label)}</time>${m.sender_type==='admin'?'<em>✓ Sent</em>':''}</span></div>`;
  });
  thread.innerHTML=html;state.lastMessageId=newest;requestAnimationFrame(()=>{thread.scrollTop=thread.scrollHeight});
}
function fillText(selector,value){const el=qs(selector,root);if(el)el.textContent=value??''}
function renderChat(chat,messages=[],force=false){
  const has=!!chat;selectedShell?.classList.toggle('is-hidden',!has);emptyShell?.classList.toggle('is-hidden',has);visitorSelected?.classList.toggle('is-hidden',!has);visitorEmpty?.classList.toggle('is-hidden',has);
  if(!has){state.selected=0;state.lastMessageId=0;if(thread)thread.innerHTML='';return;}
  const changed=Number(chat.id)!==state.selected;state.selected=Number(chat.id);
  const initial=(chat.visitor_name||'V').charAt(0).toUpperCase();
  fillText('[data-chat-avatar]',initial);fillText('[data-chat-name]',chat.visitor_name);fillText('[data-chat-email]',chat.visitor_email);fillText('[data-chat-id]',chat.id);
  const phoneWrap=qs('[data-chat-phone-wrap]',root);if(phoneWrap)phoneWrap.textContent=chat.visitor_phone?' · '+chat.visitor_phone:'';
  const presence=qs('[data-chat-presence]',root);if(presence){presence.textContent=chat.is_online?'Online':'Offline';presence.classList.toggle('online',!!chat.is_online)}
  qs('[data-chat-presence-dot]',root)?.classList.toggle('online',!!chat.is_online);
  qsa('[data-chat-status-id],[data-chat-delete-id],[data-chat-reply-id],[data-chat-note-id]',root).forEach(x=>x.value=chat.id);
  const statusSelect=qs('[data-chat-status-select]',root);if(statusSelect)statusSelect.value=chat.status;
  const closed=chat.status==='closed';if(messageInput)messageInput.disabled=closed;if(sendButton)sendButton.disabled=closed;
  fillText('[data-composer-status]',closed?'Conversation closed':'Ready to reply');
  const closeButton=qs('[data-chat-close-conversation]',root);if(closeButton)closeButton.hidden=closed;

  fillText('[data-detail-avatar]',initial);fillText('[data-detail-name]',chat.visitor_name);fillText('[data-detail-email]',chat.visitor_email||'—');fillText('[data-detail-phone]',chat.visitor_phone||'Not provided');fillText('[data-detail-category]',titleCase(chat.category));fillText('[data-detail-assigned]',chat.assigned_name||'Unassigned');fillText('[data-detail-started]',chat.started_label);fillText('[data-detail-activity]',chat.last_activity_label);fillText('[data-detail-id]',chat.id);
  const detailStatus=qs('[data-detail-status]',root);if(detailStatus){detailStatus.textContent=chat.status_label;detailStatus.className='status-pill '+chat.status;detailStatus.setAttribute('data-detail-status','')}
  const detailPresence=qs('[data-detail-presence]',root);if(detailPresence){detailPresence.textContent=onlineLabel(chat);detailPresence.className='detail-presence '+(closed?'inactive':(chat.is_online?'online':''));detailPresence.setAttribute('data-detail-presence','')}
  qs('[data-detail-presence-dot]',root)?.classList.toggle('online',!!chat.is_online);
  if(noteInput&&!state.noteDirty)noteInput.value=chat.admin_notes||'';
  renderMessages(messages,force||changed);
}

async function refresh({silent=false,forceMessages=false}={}){
  if(state.loading)return;state.loading=true;if(!silent)setSync('Syncing',true);
  try{
    const data=await fetch(feedUrl(),{credentials:'same-origin',cache:'no-store',headers:headers()}).then(jsonResponse);
    state.status=data.status||state.status;state.q=data.q??state.q;state.page=Number(data.pagination?.page||state.page||1);state.perPage=Number(data.pagination?.per_page||state.perPage||25);
    if(data.chat)state.selected=Number(data.chat.id);else if(!data.conversations?.length)state.selected=0;
    renderCounts(data.counts);renderConversations(data.conversations||[]);renderPagination(data.pagination||{});renderChat(data.chat,data.messages||[],forceMessages);setReturnFields();updateUrl();
    if(searchInput&&document.activeElement!==searchInput)searchInput.value=state.q;
    setSync('Auto-refresh active',false);
  }catch(error){setSync('Connection retrying',false);if(!silent)toast(error.message||'Unable to refresh live chat.','error');}
  finally{state.loading=false}
}
async function postForm(url,form){const response=await fetch(url,{method:'POST',body:new FormData(form),credentials:'same-origin',headers:headers()});return jsonResponse(response)}

root.addEventListener('click',event=>{
  const filter=event.target.closest('[data-chat-filter]');if(filter){event.preventDefault();state.status=filter.dataset.status||'';state.page=1;state.selected=0;state.lastMessageId=0;refresh({forceMessages:true});return;}
  const item=event.target.closest('[data-chat-select]');if(item){event.preventDefault();state.selected=Number(item.dataset.chatSelect||0);state.lastMessageId=0;refresh({forceMessages:true});if(innerWidth<=1180)switchView('chat');return;}
  const pageBtn=event.target.closest('[data-chat-page]');if(pageBtn&&!pageBtn.disabled){state.page=Number(pageBtn.dataset.chatPage||1);state.selected=0;state.lastMessageId=0;refresh({forceMessages:true});return;}
  const refreshBtn=event.target.closest('[data-chat-refresh]');if(refreshBtn){refresh({forceMessages:true});return;}
  const viewBtn=event.target.closest('[data-chat-view-button]');if(viewBtn){switchView(viewBtn.dataset.chatViewButton);return;}
});

qs('[data-chat-close-conversation]',root)?.addEventListener('click',async()=>{
  if(!state.selected||!statusForm||!confirm('Close this support conversation? The visitor will be notified and can no longer send messages in this chat.'))return;
  const button=qs('[data-chat-close-conversation]',root);if(button){button.disabled=true;button.textContent='Closing…'}
  try{setReturnFields();const fd=new FormData(statusForm);fd.set('status','closed');const response=await fetch(urls.status,{method:'POST',body:fd,credentials:'same-origin',headers:headers()});const data=await jsonResponse(response);toast(data.message||'Conversation closed.');await refresh({forceMessages:true});}
  catch(error){toast(error.message||'Unable to close conversation.','error')}
  finally{if(button){button.disabled=false;button.textContent='Close Chat'}}
});

root.addEventListener('change',event=>{const per=event.target.closest('[data-chat-per-page]');if(per){state.perPage=Number(per.value||25);state.page=1;state.selected=0;state.lastMessageId=0;refresh({forceMessages:true});}});
qs('[data-chat-search-form]',root)?.addEventListener('submit',event=>{event.preventDefault();state.q=searchInput?.value.trim()||'';state.page=1;state.selected=0;state.lastMessageId=0;refresh({forceMessages:true})});
searchInput?.addEventListener('input',()=>{clearTimeout(state.searchTimer);state.searchTimer=setTimeout(()=>{state.q=searchInput.value.trim();state.page=1;state.selected=0;state.lastMessageId=0;refresh({forceMessages:true})},350)});

messageInput?.addEventListener('keydown',event=>{if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();replyForm?.requestSubmit();}});
replyForm?.addEventListener('submit',async event=>{
  event.preventDefault();if(!state.selected||!messageInput||messageInput.disabled)return;const text=messageInput.value.trim();if(!text)return;
  const original=sendButton?.querySelector('span')?.textContent||'Send';if(sendButton){sendButton.disabled=true;const label=sendButton.querySelector('span');if(label)label.textContent='Sending…'}
  try{setReturnFields();await postForm(urls.reply,replyForm);messageInput.value='';await refresh({forceMessages:true});messageInput.focus();}
  catch(error){toast(error.message||'Unable to send message.','error')}
  finally{if(sendButton){sendButton.disabled=false;const label=sendButton.querySelector('span');if(label)label.textContent=original;const status=qs('[data-chat-status-select]',root)?.value;if(status==='closed')sendButton.disabled=true}}
});
statusForm?.addEventListener('submit',async event=>{
  event.preventDefault();if(!state.selected)return;const button=statusForm.querySelector('button[type="submit"]');if(button){button.disabled=true;button.textContent='Updating…'}
  try{setReturnFields();const data=await postForm(urls.status,statusForm);toast(data.message||'Status updated.');await refresh({forceMessages:true})}
  catch(error){toast(error.message||'Unable to update status.','error')}
  finally{if(button){button.disabled=false;button.textContent='Update'}}
});
deleteForm?.addEventListener('submit',async event=>{
  event.preventDefault();if(!state.selected||!confirm('Delete this conversation permanently? This cannot be undone.'))return;const button=deleteForm.querySelector('button[type="submit"]');if(button)button.disabled=true;
  try{setReturnFields();const data=await postForm(urls.delete,deleteForm);toast(data.message||'Conversation deleted.');state.selected=0;state.lastMessageId=0;await refresh({forceMessages:true});switchView('conversations')}
  catch(error){toast(error.message||'Unable to delete conversation.','error')}
  finally{if(button)button.disabled=false}
});
noteInput?.addEventListener('input',()=>{state.noteDirty=true});
noteForm?.addEventListener('submit',async event=>{
  event.preventDefault();if(!state.selected||noteInput?.disabled)return;const button=noteForm.querySelector('button[type="submit"]');if(button){button.disabled=true;button.textContent='Saving…'}
  try{setReturnFields();const data=await postForm(urls.note,noteForm);state.noteDirty=false;toast(data.message||'Support notes saved.');await refresh()}
  catch(error){toast(error.message||'Unable to save support notes.','error')}
  finally{if(button){button.disabled=false;button.textContent='Save Note'}}
});

document.addEventListener('visibilitychange',()=>{if(!document.hidden)refresh({silent:true})});
if(innerWidth<=1180)switchView(state.selected?'chat':'conversations');else root.dataset.activeView='chat';
requestAnimationFrame(()=>{if(thread)thread.scrollTop=thread.scrollHeight});
state.poll=setInterval(()=>{if(!document.hidden&&!state.loading)refresh({silent:true})},4000);
})();
