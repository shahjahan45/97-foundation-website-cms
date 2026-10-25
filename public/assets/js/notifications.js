(()=>{
  const qs=(s,r=document)=>r.querySelector(s),qsa=(s,r=document)=>[...r.querySelectorAll(s)];
  const center=qs('[data-notification-center]');
  if(!center)return;

  const list=qs('[data-notification-list]',center);
  const token=qs('[data-notification-token]',center)?.value||'';
  const feedUrl=center.dataset.feedUrl||'';
  const readUrl=center.dataset.readUrl||'';
  const readAllUrl=center.dataset.readAllUrl||'';
  const loadMore=qs('[data-notification-load-more]',center);
  let page=1,pages=1,filter='all',loading=false,items=[];

  const esc=v=>String(v??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
  const kind=v=>String(v||'info').toLowerCase().replace(/[^a-z0-9_-]+/g,'-');
  const label=v=>String(v||'Info').replace(/[_-]+/g,' ').replace(/\b\w/g,m=>m.toUpperCase());
  const icon=type=>{
    const t=kind(type);
    if(t.includes('message')||t.includes('chat'))return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v12H8l-4 3V5Z"/><path d="M8 9h8M8 13h5"/></svg>';
    if(t.includes('donation')||t.includes('payment')||t.includes('refund'))return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16v12H4z"/><path d="M7 4h10v3M8 11h8M8 15h5"/></svg>';
    if(t.includes('member')||t.includes('volunteer')||t.includes('account'))return '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 21c.8-4.2 3.4-6.4 8-6.4s7.2 2.2 8 6.4"/></svg>';
    if(t.includes('security')||t.includes('login'))return '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="3"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>';
    return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>';
  };

  const updateCount=count=>{
    const n=Math.max(0,Number(count)||0),display=n>99?'99+':String(n);
    qsa('[data-notification-count]').forEach(el=>{el.textContent=display;el.hidden=n===0;});
    qsa('[data-notification-unread-label]').forEach(el=>el.textContent=n?`${display} unread`:'All caught up');
    qsa('.notification-page-summary b').forEach(el=>el.textContent=String(n));
    qsa('[data-notification-toggle]').forEach(el=>el.classList.toggle('has-unread',n>0));
  };

  const itemTemplate=n=>{
    const unread=!n.read_at,k=kind(n.type),open=n.link_url?`<a class="notification-item-open" href="${esc(n.link_url)}">Open related item ↗</a>`:'';
    const readAction=unread?`<button type="button" class="notification-item-read" data-notification-read data-notification-id="${Number(n.id)}">Mark as read</button>`:'';
    return `<article class="notification-drawer-item ${unread?'is-unread':'is-read'}" data-notification-item data-notification-id="${Number(n.id)}" data-notification-type="${esc(k)}">
      <span class="notification-drawer-icon">${icon(k)}</span>
      <div class="notification-drawer-copy">
        <div class="notification-drawer-title"><h3>${esc(n.title)}</h3>${unread?'<i aria-label="Unread"></i>':''}</div>
        <p>${esc(n.message).replace(/\n/g,'<br>')}</p>
        <div class="notification-drawer-meta"><time>${esc(n.created_label||n.created_at)}</time><span>${esc(label(n.type))}</span><b class="notification-read-state">${unread?'Unread':'Read'}</b></div>
        ${(readAction||open)?`<div class="notification-drawer-actions">${readAction}${open}</div>`:''}
      </div>
    </article>`;
  };

  const emptyTemplate=()=>`<div class="notification-empty-state notification-drawer-empty"><span>✓</span><h4>${filter==='unread'?'No unread notifications':'You’re all caught up'}</h4><p>${filter==='unread'?'Every notification in this list has been read.':'New Foundation updates will appear here.'}</p></div>`;

  const applyFilter=()=>{
    let visible=0;
    qsa('.notification-drawer-item',list).forEach(el=>{
      const show=filter==='all'||el.classList.contains('is-unread');
      el.hidden=!show;if(show)visible++;
    });
    let empty=qs('.notification-drawer-filter-empty',list);
    if(!visible&&items.length){if(!empty){empty=document.createElement('div');empty.className='notification-drawer-filter-empty';empty.innerHTML=emptyTemplate();list.appendChild(empty);}empty.hidden=false;}else if(empty)empty.hidden=true;
  };

  const render=(data,append=false)=>{
    const incoming=Array.isArray(data.items)?data.items:[];
    page=Number(data.page)||1;pages=Number(data.pages)||1;
    if(append)items=[...items,...incoming];else items=incoming;
    const html=incoming.map(itemTemplate).join('');
    if(append){list.insertAdjacentHTML('beforeend',html);}else{list.innerHTML=html||(emptyTemplate());}
    updateCount(data.unread_count);
    loadMore.hidden=!(data.has_more||page<pages);
    loadMore.disabled=false;
    applyFilter();
  };

  const load=async(nextPage=1,append=false)=>{
    if(loading||!feedUrl)return;loading=true;
    if(!append)list.innerHTML='<div class="notification-center-loading"><i></i><span>Loading notifications…</span></div>';
    if(loadMore){loadMore.disabled=true;loadMore.textContent='Loading…';}
    try{
      const u=new URL(feedUrl,location.href);u.searchParams.set('page',String(nextPage));u.searchParams.set('_',String(Date.now()));
      const r=await fetch(u,{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json','X-Requested-With':'97Foundation-Notifications'}});
      if(!r.ok)throw new Error('Unable to load notifications');
      render(await r.json(),append);
    }catch(e){
      if(!append)list.innerHTML='<div class="notification-error-state"><span>!</span><h4>Notifications could not be loaded</h4><p>Please try again.</p><button type="button" data-notification-retry>Retry</button></div>';
    }finally{
      loading=false;if(loadMore){loadMore.disabled=false;loadMore.textContent='Load older notifications';}
    }
  };

  const post=async(url,data={})=>{
    const body=new URLSearchParams({_token:token,...Object.fromEntries(Object.entries(data).map(([k,v])=>[k,String(v)]))});
    const r=await fetch(url,{method:'POST',credentials:'same-origin',cache:'no-store',headers:{'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8','Accept':'application/json','X-Requested-With':'97Foundation-Notifications'},body});
    if(!r.ok)throw new Error('Notification update failed');
    return r.json();
  };

  const markDomRead=id=>{
    qsa(`[data-notification-item][data-notification-id="${CSS.escape(String(id))}"]`).forEach(el=>{
      el.classList.remove('is-unread');el.classList.add('is-read');
      qs('.notification-drawer-title i',el)?.remove();
      const state=qs('.notification-read-state',el);if(state)state.textContent='Read';
      qs('[data-notification-read]',el)?.remove();
    });
    const item=items.find(x=>String(x.id)===String(id));if(item)item.read_at='now';
    applyFilter();
  };

  const markRead=async(id,button)=>{
    if(!id||!readUrl)return;
    button?.setAttribute('disabled','disabled');
    try{const data=await post(readUrl,{id});markDomRead(id);updateCount(data.unread_count);}catch{button?.removeAttribute('disabled');}
  };

  const markAll=async(button)=>{
    if(!readAllUrl)return;
    button?.setAttribute('disabled','disabled');
    try{
      const data=await post(readAllUrl);
      qsa('[data-notification-item]').forEach(el=>{el.classList.remove('is-unread');el.classList.add('is-read');qs('.notification-drawer-title i',el)?.remove();const state=qs('.notification-read-state',el);if(state)state.textContent='Read';qs('[data-notification-read]',el)?.remove();});
      items.forEach(x=>x.read_at=x.read_at||'now');updateCount(data.unread_count);applyFilter();
    }finally{button?.removeAttribute('disabled');}
  };

  const open=()=>{
    center.classList.add('is-open');center.setAttribute('aria-hidden','false');document.body.classList.add('notification-center-open');qsa('[data-notification-toggle]').forEach(b=>b.setAttribute('aria-expanded','true'));page=1;items=[];load(1,false);setTimeout(()=>qs('[data-notification-close]',center)?.focus(),120);
  };
  const close=()=>{
    center.classList.remove('is-open');center.setAttribute('aria-hidden','true');document.body.classList.remove('notification-center-open');qsa('[data-notification-toggle]').forEach(b=>b.setAttribute('aria-expanded','false'));
  };

  document.addEventListener('click',e=>{
    const toggle=e.target.closest('[data-notification-toggle]');if(toggle){e.preventDefault();center.classList.contains('is-open')?close():open();return;}
    if(e.target.closest('[data-notification-close]')){e.preventDefault();close();return;}
    const retry=e.target.closest('[data-notification-retry]');if(retry){load(1,false);return;}
    const read=e.target.closest('[data-notification-read]');if(read){e.preventDefault();markRead(read.dataset.notificationId,read);return;}
    const all=e.target.closest('[data-notification-mark-all]');if(all){e.preventDefault();markAll(all);return;}
    const filterBtn=e.target.closest('[data-notification-filter]');if(filterBtn&&center.contains(filterBtn)){filter=filterBtn.dataset.notificationFilter==='unread'?'unread':'all';qsa('[data-notification-filter]',center).forEach(x=>x.classList.toggle('active',x===filterBtn));applyFilter();return;}
    const more=e.target.closest('[data-notification-load-more]');if(more&&page<pages){load(page+1,true);return;}
    const panelLink=e.target.closest('.notification-item-open,.notification-center-foot a');if(panelLink&&center.contains(panelLink)){close();}
  });
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&center.classList.contains('is-open'))close();});
  addEventListener('pageshow',()=>{if(center.classList.contains('is-open'))load(1,false);});
})();
