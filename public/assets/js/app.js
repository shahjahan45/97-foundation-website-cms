(()=>{
  const qs=(s,r=document)=>r.querySelector(s), qsa=(s,r=document)=>[...r.querySelectorAll(s)];
  const loader=()=>qs('#page-loader');
  let revealObserver=null, navigating=false;

  function setLoading(on){const l=loader();if(l)l.classList.toggle('show',on);document.body.classList.toggle('spa-loading',on);}
  function initHeader(){
    const h=qs('[data-site-header]');
    const onScroll=()=>{if(h){if(scrollY>64)h.classList.add('scrolled');else if(scrollY<8)h.classList.remove('scrolled');}const b=qs('[data-back-top]');if(b)b.classList.toggle('show',scrollY>420)};
    onScroll();
  }
  /* ---------- V7.1 header: desktop dropdowns + mobile drawer ---------- */
  let lastPointer='mouse';
  addEventListener('pointerdown',e=>{lastPointer=e.pointerType||'mouse'},{capture:true,passive:true});
  function closeDropdowns(except){qsa('[data-nav-dropdown].is-open').forEach(li=>{if(li===except)return;li.classList.remove('is-open');qs('.nav7-link',li)?.setAttribute('aria-expanded','false');});}
  function initDropdowns(){
    qsa('[data-nav-dropdown]').forEach(li=>{
      if(li.dataset.bound)return;li.dataset.bound='1';
      const link=qs('.nav7-link',li);let t=null;
      const open=()=>{clearTimeout(t);closeDropdowns(li);li.classList.remove('is-closing');li.classList.add('is-open');link?.setAttribute('aria-expanded','true');};
      const close=(delay=140)=>{clearTimeout(t);t=setTimeout(()=>{li.classList.remove('is-open');link?.setAttribute('aria-expanded','false');},delay);};
      li.addEventListener('mouseenter',()=>{if(lastPointer==='mouse')open();});
      li.addEventListener('mouseleave',()=>{li.classList.remove('is-closing');if(lastPointer==='mouse')close();});
      li.addEventListener('focusin',open);
      li.addEventListener('focusout',e=>{if(!li.contains(e.relatedTarget))close(0);});
      link?.addEventListener('click',e=>{if(lastPointer!=='mouse'&&!li.classList.contains('is-open')){e.preventDefault();e.stopImmediatePropagation();open();}});
      link?.addEventListener('keydown',e=>{if(e.key==='ArrowDown'){e.preventDefault();open();qs('.nav7-dropdown a',li)?.focus();}});
      li.addEventListener('keydown',e=>{
        const items=qsa('.nav7-dropdown a',li),idx=items.indexOf(document.activeElement);
        if(e.key==='Escape'){li.classList.remove('is-open');link?.setAttribute('aria-expanded','false');link?.focus();}
        else if(idx>-1&&e.key==='ArrowDown'){e.preventDefault();items[(idx+1)%items.length]?.focus();}
        else if(idx>-1&&e.key==='ArrowUp'){e.preventDefault();idx===0?link?.focus():items[idx-1]?.focus();}
      });
      qsa('.nav7-dropdown a',li).forEach(a=>a.addEventListener('click',()=>{li.classList.remove('is-open');li.classList.add('is-closing');link?.setAttribute('aria-expanded','false');a.blur();}));
    });
  }
  const drawer=()=>qs('#mobile-nav7');
  function openDrawer(){const d=drawer(),b=qs('[data-menu-toggle]');if(!d||d.classList.contains('is-open'))return;d.hidden=false;void d.offsetWidth;d.classList.add('is-open');document.body.classList.add('nav7-locked');b?.setAttribute('aria-expanded','true');b?.setAttribute('aria-label','Close menu');setTimeout(()=>qs('.mobile-nav7-close',d)?.focus({preventScroll:true}),60);}
  function closeDrawer(restoreFocus=true){const d=drawer(),b=qs('[data-menu-toggle]');if(!d||d.hidden)return;d.classList.remove('is-open');document.body.classList.remove('nav7-locked');b?.setAttribute('aria-expanded','false');b?.setAttribute('aria-label','Open menu');const done=()=>{if(!d.classList.contains('is-open'))d.hidden=true;};matchMedia('(prefers-reduced-motion: reduce)').matches?done():setTimeout(done,330);if(restoreFocus)b?.focus({preventScroll:true});}
  function initMenu(){
    initDropdowns();
    const b=qs('[data-menu-toggle]'),d=drawer();
    if(b&&!b.dataset.bound){b.dataset.bound='1';b.addEventListener('click',()=>d?.classList.contains('is-open')?closeDrawer():openDrawer());}
    if(d&&!d.dataset.bound){
      d.dataset.bound='1';
      d.addEventListener('click',e=>{
        if(e.target.closest('[data-menu-close]')){closeDrawer();return;}
        const sub=e.target.closest('[data-mobile-sub-toggle]');
        if(sub){const list=document.getElementById(sub.getAttribute('aria-controls'));const show=sub.getAttribute('aria-expanded')!=='true';sub.setAttribute('aria-expanded',show?'true':'false');if(list)list.hidden=!show;return;}
        if(e.target.closest('a[href]'))closeDrawer(false);
      });
      d.addEventListener('keydown',e=>{
        if(e.key==='Escape'){closeDrawer();return;}
        if(e.key!=='Tab')return;
        const f=qsa('a[href],button:not([disabled])',qs('.mobile-nav7-panel',d)).filter(x=>x.offsetParent!==null);if(!f.length)return;
        const first=f[0],last=f[f.length-1];
        if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus();}else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus();}
      });
    }
    if(!document.body.dataset.nav7Global){
      document.body.dataset.nav7Global='1';
      document.addEventListener('click',e=>{if(!e.target.closest('[data-nav-dropdown]'))closeDropdowns();});
      addEventListener('resize',()=>{if(innerWidth>=1120)closeDrawer(false);},{passive:true});
    }
  }
  function initReveal(){
    if(revealObserver)revealObserver.disconnect();
    if(matchMedia('(prefers-reduced-motion: reduce)').matches){qsa('.reveal').forEach(x=>x.classList.add('in-view'));return;}
    revealObserver=new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting){e.target.classList.add('in-view');revealObserver.unobserve(e.target)}}),{threshold:.08});
    qsa('.reveal').forEach(x=>revealObserver.observe(x));
  }
  /* ---------- V7.1 hero: horizontal five-image background loop ---------- */
  let hero7Cleanup=null;
  function initHero(){
    if(hero7Cleanup){hero7Cleanup();hero7Cleanup=null;}
    const root=qs('[data-hero7]');if(!root)return;
    const track=qs('[data-hero7-track]',root),slides=qsa('[data-hero7-slide]',root),dots=qsa('[data-hero7-dot]',root),current=qs('[data-hero7-current]',root),live=qs('[data-hero7-live]',root);
    const n=slides.length;if(!track||n<2)return;
    const reduced=matchMedia('(prefers-reduced-motion: reduce)').matches;
    const delay=Math.max(3,Math.min(15,parseInt(root.dataset.interval||'6',10)||6))*1000;
    // Seamless loop: [clone of last] 1 2 3 4 5 [clone of first]
    const headClone=slides[n-1].cloneNode(true),tailClone=slides[0].cloneNode(true);
    [headClone,tailClone].forEach(c=>{c.classList.remove('is-active');c.removeAttribute('data-hero7-slide');c.dataset.hero7Clone='1';});
    track.prepend(headClone);track.append(tailClone);
    let index=0,pos=1,timer=null,paused=false,busy=false,startX=null,startY=null;
    const place=(p,animate)=>{track.classList.toggle('no-anim',!animate||reduced);track.style.transform=`translate3d(${-p*100}%,0,0)`;};
    const sync=(announce)=>{slides.forEach((s,k)=>s.classList.toggle('is-active',k===index));dots.forEach((d,k)=>{const on=k===index;d.classList.toggle('is-active',on);on?d.setAttribute('aria-current','true'):d.removeAttribute('aria-current');});if(current)current.textContent=String(index+1).padStart(2,'0');if(announce&&live)live.textContent=`Background image ${index+1} of ${n}`;};
    let settleTimer=null;
    const settle=()=>{clearTimeout(settleTimer);busy=false;if(pos===0){pos=n;place(pos,false);}else if(pos===n+1){pos=1;place(pos,false);}};
    const goPos=(p,announce=false)=>{pos=p;index=((pos-1)%n+n)%n;place(pos,true);sync(announce);if(reduced){settle();return;}busy=true;clearTimeout(settleTimer);settleTimer=setTimeout(settle,1300);};
    const step=(dir,announce=false)=>{if(busy)return;goPos(pos+dir,announce);};
    const goTo=(k,announce=false)=>{if(k===index)return;settle();goPos(k+1,announce);};
    const onEnd=e=>{if(e.target===track&&e.propertyName==='transform')settle();};
    track.addEventListener('transitionend',onEnd);
    const schedule=()=>{clearTimeout(timer);if(paused||reduced||document.hidden)return;timer=setTimeout(()=>{step(1);schedule();},delay);};
    const pause=()=>{paused=true;clearTimeout(timer);};
    const resume=()=>{paused=false;schedule();};
    const onDot=e=>{const d=e.target.closest('[data-hero7-dot]');if(!d)return;const k=parseInt(d.dataset.hero7Dot,10);goTo(k,true);resume();};
    const onKey=e=>{if(!e.target.closest('[data-hero7-dot]'))return;if(e.key==='ArrowRight'){e.preventDefault();goTo((index+1)%n,true);dots[index]?.focus();}else if(e.key==='ArrowLeft'){e.preventDefault();goTo((index-1+n)%n,true);dots[index]?.focus();}};
    const onDown=e=>{if(e.pointerType==='mouse')return;startX=e.clientX;startY=e.clientY;};
    const onUp=e=>{if(startX===null)return;const dx=e.clientX-startX,dy=e.clientY-startY;startX=null;if(Math.abs(dx)>48&&Math.abs(dx)>Math.abs(dy)*1.3){step(dx<0?1:-1,true);resume();}};
    const onVis=()=>{if(document.hidden)clearTimeout(timer);else schedule();};
    // Background loop keeps playing under the pointer; it pauses only while the dot controls are hovered or focused.
    const bar=qs('.hero7-dots',root);
    root.addEventListener('click',onDot);root.addEventListener('keydown',onKey);
    root.addEventListener('pointerdown',onDown,{passive:true});root.addEventListener('pointerup',onUp,{passive:true});
    bar?.addEventListener('mouseenter',pause);bar?.addEventListener('mouseleave',resume);
    root.addEventListener('focusin',pause);root.addEventListener('focusout',e=>{if(!root.contains(e.relatedTarget))resume();});
    document.addEventListener('visibilitychange',onVis);
    place(pos,false);sync(false);schedule();
    // Off-screen slides sit outside the viewport horizontally; warm them up after first paint.
    const warm=setTimeout(()=>qsa('img[loading="lazy"]',track).forEach(img=>{img.loading='eager';}),1200);
    hero7Cleanup=()=>{clearTimeout(timer);clearTimeout(warm);clearTimeout(settleTimer);document.removeEventListener('visibilitychange',onVis);};
  }
  function initGallery(){
    qsa('[data-gallery-filter]').forEach(b=>{if(b.dataset.bound)return;b.dataset.bound='1';b.addEventListener('click',()=>{qsa('[data-gallery-filter]').forEach(x=>x.classList.remove('active'));b.classList.add('active');const f=b.dataset.galleryFilter;qsa('[data-gallery-item]').forEach(x=>x.classList.toggle('hidden',f!=='all'&&x.dataset.galleryItem!==f));});});
    qsa('[data-lightbox]').forEach(a=>{if(a.dataset.bound)return;a.dataset.bound='1';a.addEventListener('click',e=>{e.preventDefault();const box=document.createElement('div');box.className='lightbox';box.innerHTML='<button aria-label="Close">×</button><img alt="Gallery preview">';qs('img',box).src=a.href;box.addEventListener('click',ev=>{if(ev.target===box||ev.target.tagName==='BUTTON')box.remove()});document.addEventListener('keydown',function esc(ev){if(ev.key==='Escape'){box.remove();document.removeEventListener('keydown',esc)}});document.body.append(box);});});
  }
  function initDonation(){
    qsa('[data-amount]').forEach(b=>{if(b.dataset.bound)return;b.dataset.bound='1';b.addEventListener('click',()=>{qsa('[data-amount]').forEach(x=>x.classList.remove('selected'));b.classList.add('selected');const i=qs('[data-amount-input]');if(i){i.value=b.dataset.amount;i.focus();}})});
    qsa('[data-online-amount]').forEach(b=>{if(b.dataset.bound)return;b.dataset.bound='1';b.addEventListener('click',()=>{qsa('[data-online-amount]').forEach(x=>x.classList.remove('selected'));b.classList.add('selected');const i=qs('[data-online-amount-input]');if(i){i.value=b.dataset.onlineAmount;i.focus();}})});
    qsa('[data-donation-tab]').forEach(btn=>{if(btn.dataset.bound)return;btn.dataset.bound='1';btn.addEventListener('click',()=>{if(btn.disabled)return;const key=btn.dataset.donationTab;qsa('[data-donation-tab]').forEach(x=>{const on=x===btn;x.classList.toggle('active',on);x.setAttribute('aria-selected',on?'true':'false')});qsa('[data-donation-panel]').forEach(p=>{const on=p.dataset.donationPanel===key;p.hidden=!on;p.classList.toggle('active',on)});const u=new URL(location.href);if(key==='online')u.searchParams.set('tab','online');else u.searchParams.delete('tab');history.replaceState(history.state,'',u);});});
    qsa('[data-account-choice]').forEach(r=>{if(r.dataset.bound)return;r.dataset.bound='1';r.addEventListener('change',()=>{qsa('.bank-card').forEach(c=>c.classList.remove('selected'));r.closest('.bank-card')?.classList.add('selected');const s=qs('[data-account-select]');if(s)s.value=r.value;})});
    const sel=qs('[data-account-select]');if(sel&&!sel.dataset.bound){sel.dataset.bound='1';sel.addEventListener('change',()=>{qsa('.bank-card').forEach(c=>c.classList.remove('selected'));const r=qs(`[data-account-choice][value="${CSS.escape(sel.value)}"]`);if(r){r.checked=true;r.closest('.bank-card')?.classList.add('selected')}});if(sel.value)sel.dispatchEvent(new Event('change'));}
  }

  function initMemberDirectoryImages(){
    qsa('[data-member-photo]').forEach(img=>{
      const frame=img.closest('[data-member-photo-frame]');
      if(!frame||img.dataset.memberImageBound)return;
      img.dataset.memberImageBound='1';
      const ready=()=>{frame.classList.add('is-loaded');img.classList.add('is-loaded')};
      const failed=()=>{frame.classList.add('is-loaded','has-image-error');img.classList.add('is-loaded')};
      if(img.complete&&img.naturalWidth>0)ready();
      else{img.addEventListener('load',ready,{once:true});img.addEventListener('error',failed,{once:true});}
    });
  }

  function initMemberForms(){
    qsa('[data-member-password-toggle]').forEach(btn=>{if(btn.dataset.bound)return;btn.dataset.bound='1';btn.addEventListener('click',()=>{const input=btn.parentElement?.querySelector('input');if(!input)return;const hidden=input.type==='password';input.type=hidden?'text':'password';btn.textContent=hidden?'Hide':'Show';btn.setAttribute('aria-label',hidden?'Hide password':'Show password');});});
    qsa('.member-registration-form').forEach(form=>{if(form.dataset.validationBound)return;form.dataset.validationBound='1';const password=qs('input[name="password"]',form),confirm=qs('input[name="password_confirmation"]',form);if(!password||!confirm)return;const validate=()=>{const len=password.value.length;password.setCustomValidity(password.value&&(len<8||len>12)?'Password must be between 8 and 12 characters.':'');confirm.setCustomValidity(confirm.value&&password.value!==confirm.value?'Passwords do not match.':'');password.classList.toggle('input-invalid',!!password.validationMessage);confirm.classList.toggle('input-invalid',!!confirm.validationMessage);};password.addEventListener('input',validate);confirm.addEventListener('input',validate);form.addEventListener('submit',()=>validate());});
  }
  function initPaymentFlow(){const f=qs('[data-gateway-autosubmit]');if(f&&!f.dataset.bound){f.dataset.bound='1';setTimeout(()=>f.submit(),450);}qsa('[data-print-receipt]').forEach(b=>{if(b.dataset.bound)return;b.dataset.bound='1';b.addEventListener('click',()=>window.print());});}
  function initBackTop(){const b=qs('[data-back-top]');if(b&&!b.dataset.bound){b.dataset.bound='1';b.addEventListener('click',()=>scrollTo({top:0,behavior:'smooth'}));}}

  function initSiteQr(){
    qsa('[data-qr-code]').forEach(el=>{if(el.dataset.qrReady)return;const text=el.dataset.qrText||'';if(!text||!window.QRCode)return;el.dataset.qrReady='1';el.innerHTML='';new QRCode(el,{text,width:138,height:138,colorDark:'#071d1a',colorLight:'#ffffff',correctLevel:QRCode.CorrectLevel.M});});
  }
  function initLiveChat(){
    const root=qs('[data-live-chat]');if(!root||root.dataset.bound)return;root.dataset.bound='1';
    const panel=qs('.live-chat-panel',root),toggle=qs('[data-chat-toggle]',root),close=qs('[data-chat-close]',root),startState=qs('[data-chat-state-start]',root),activeState=qs('[data-chat-state-active]',root),startForm=qs('[data-chat-start-form]',root),thread=qs('[data-chat-thread]',root),sendForm=qs('[data-chat-send-form]',root),clear=qs('[data-chat-end]',root),headerStatus=qs('[data-chat-header-status]',root),messageInput=qs('[data-chat-message-input]',root),sendButton=qs('[data-chat-send-button]',root),closedState=qs('[data-chat-closed-state]',root),newChat=qs('[data-chat-new]',root);
    const base=(root.dataset.chatBase||'').replace(/\/$/,'');let timer=null;let token=localStorage.getItem('foundation97.chat.token')||'';let busy=false,lastSignature='';
    const endpoint=p=>base+(p?'/'+String(p).replace(/^\//,''):'');
    const esc=x=>String(x??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
    const notify=(msg,type='error')=>{const stack=qs('[data-toast-stack]');if(!stack){alert(msg);return;}const t=document.createElement('div');t.className='toast '+type;t.textContent=msg;stack.append(t);requestAnimationFrame(()=>t.classList.add('show'));setTimeout(()=>{t.classList.remove('show');setTimeout(()=>t.remove(),250)},4500)};
    async function readJson(r){const ct=r.headers.get('content-type')||'';if(!ct.includes('application/json')){const text=await r.text();throw new Error(r.status===419?'Your secure session expired. Refresh the page and try again.':(text&&text.length<180?text:'Live chat service returned an unexpected response.'));}return r.json();}
    const setState=state=>{const active=state==='active',next=active?'active':'start',changed=panel?.getAttribute('data-chat-panel-state')!==next;panel?.setAttribute('data-chat-panel-state',next);if(startState)startState.hidden=active;if(activeState)activeState.hidden=!active;if(clear)clear.hidden=!active;if(headerStatus)headerStatus.innerHTML=active?'<span class="chat-online-dot"></span> Online · Support conversation':'Secure website chat';if(active&&changed)requestAnimationFrame(()=>{if(thread)thread.scrollTop=thread.scrollHeight;messageInput?.focus({preventScroll:true});});};
    const setClosed=closed=>{if(closedState)closedState.hidden=!closed;if(sendForm)sendForm.hidden=closed;if(messageInput)messageInput.disabled=closed;if(sendButton)sendButton.disabled=closed;if(headerStatus)headerStatus.innerHTML=closed?'Conversation closed':'<span class="chat-online-dot"></span> Online · Support conversation';};
    const resetChat=()=>{localStorage.removeItem('foundation97.chat.token');token='';lastSignature='';clearTimeout(timer);if(thread)thread.innerHTML='';if(messageInput){messageInput.value='';messageInput.disabled=false;}if(sendButton)sendButton.disabled=false;if(startForm)startForm.reset();setClosed(false);setState('start');};
    const open=()=>{panel?.classList.add('open');panel?.setAttribute('aria-hidden','false');toggle?.classList.add('active');if(token){setState('active');load(true)}else setState('start');};
    const hide=()=>{panel?.classList.remove('open');panel?.setAttribute('aria-hidden','true');toggle?.classList.remove('active');};
    const formatDate=value=>{const d=new Date(String(value||'').replace(' ','T'));if(Number.isNaN(d.getTime()))return {key:'',date:'',time:''};return {key:d.toDateString(),date:d.toLocaleDateString(undefined,{month:'short',day:'numeric',year:d.getFullYear()!==new Date().getFullYear()?'numeric':undefined}),time:d.toLocaleTimeString(undefined,{hour:'2-digit',minute:'2-digit'})};};
    const render=messages=>{if(!thread)return;const signature=(messages||[]).map(m=>`${m.sender_type}|${m.created_at}|${m.message}`).join('¦');if(signature===lastSignature)return;lastSignature=signature;let currentDate='';let html='';(messages||[]).forEach(m=>{const dt=formatDate(m.created_at);if(dt.key&&dt.key!==currentDate){currentDate=dt.key;html+=`<div class="widget-date-separator"><span>${esc(dt.date)}</span></div>`;}if(m.sender_type==='system'){const isClose=/conversation has been closed|conversation closed/i.test(m.message||'');const systemCopy=isClose?'Conversation closed by 97 Foundation Support.':m.message;html+=`<div class="widget-system-message${isClose?' is-closed':''}"><span>✓</span><p>${esc(systemCopy).replace(/\n/g,'<br>')}</p><small>${dt.time?esc(dt.time):''}</small></div>`;return;}html+=`<div class="chat-message ${m.sender_type==='visitor'?'visitor':'admin'}"><div class="widget-message-bubble"><p>${esc(m.message).replace(/\n/g,'<br>')}</p></div><small><b>${esc(m.sender_name||m.sender_type)}</b>${dt.time?' · '+esc(dt.time):''}${m.sender_type==='admin'?' · ✓':''}</small></div>`;});thread.innerHTML=html||'<div class="widget-chat-empty"><b>Your conversation has started.</b><span>Send a message below and our support team will respond here.</span></div>';requestAnimationFrame(()=>{thread.scrollTop=thread.scrollHeight});};
    async function load(immediate=false){if(!token||busy)return;busy=true;try{const r=await fetch(endpoint(token),{headers:{Accept:'application/json','X-Requested-With':'97Foundation-Chat'},credentials:'same-origin',cache:'no-store'});const d=await readJson(r);if(!r.ok||!d.ok)throw new Error(d.message||'Unable to load chat.');setState('active');render(d.messages||[]);const closed=d.status==='closed';setClosed(closed);clearTimeout(timer);if(!closed)timer=setTimeout(()=>load(),immediate?2500:4000);}catch(err){clearTimeout(timer);if(err.message&&/not found/i.test(err.message)){resetChat();}else{console.warn('Live chat poll:',err);timer=setTimeout(()=>load(),6000);}}finally{busy=false;}}
    toggle?.addEventListener('click',()=>panel?.classList.contains('open')?hide():open());close?.addEventListener('click',hide);
    startForm?.addEventListener('submit',async e=>{e.preventDefault();const btn=qs('[data-chat-start-button]',startForm);if(btn){btn.disabled=true;btn.textContent='Starting…';}try{const r=await fetch(endpoint('start'),{method:'POST',body:new FormData(startForm),credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'97Foundation-Chat'}});const d=await readJson(r);if(!r.ok||!d.ok)throw new Error(d.message||'Unable to start chat.');token=d.token;lastSignature='';localStorage.setItem('foundation97.chat.token',token);setState('active');setClosed(false);render(d.messages||[]);notify('Live chat started successfully.','success');load(true);}catch(err){notify(err.message||'Unable to start chat. Please try again.');}finally{if(btn){btn.disabled=false;btn.textContent='Start Chat';}}});
    messageInput?.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendForm?.requestSubmit();}});
    sendForm?.addEventListener('submit',async e=>{e.preventDefault();if(!token||busy)return;const text=messageInput?.value?.trim();if(!text)return;const label=sendButton?.querySelector('span');const old=label?.textContent||'Send';if(sendButton)sendButton.disabled=true;if(label)label.textContent='Sending…';try{const r=await fetch(endpoint(token),{method:'POST',body:new FormData(sendForm),credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'97Foundation-Chat'}});const d=await readJson(r);if(!r.ok||!d.ok)throw new Error(d.message||'Message failed.');messageInput.value='';lastSignature='';await load(true);messageInput.focus({preventScroll:true});}catch(err){notify(err.message||'Unable to send message.');if(/closed/i.test(err.message||'')){lastSignature='';setTimeout(()=>load(true),150);}}finally{if(sendButton&&!messageInput?.disabled)sendButton.disabled=false;if(label)label.textContent=old;}});
    clear?.addEventListener('click',()=>{if(!confirm('Clear this chat from this browser?'))return;resetChat();});newChat?.addEventListener('click',resetChat);
    if(token)setState('active');else setState('start');
  }
  function initAll(){initHeader();initMenu();initReveal();initHero();initGallery();initDonation();initPaymentFlow();initBackTop();initSiteQr();initMemberDirectoryImages();initMemberForms();initLiveChat();}

  function syncHead(doc){
    document.title=doc.title;
    const selectors=['meta[name="description"]','meta[name="robots"]','link[rel="canonical"]','meta[property="og:type"]','meta[property="og:title"]','meta[property="og:description"]','meta[property="og:url"]','meta[property="og:site_name"]','meta[property="og:image"]','meta[name="twitter:card"]','meta[name="twitter:title"]','meta[name="twitter:description"]','meta[name="twitter:image"]'];
    selectors.forEach(sel=>{const next=doc.querySelector(sel),cur=document.querySelector(sel);if(next&&cur){if(cur.tagName==='LINK')cur.setAttribute('href',next.getAttribute('href')||'');else cur.setAttribute('content',next.getAttribute('content')||'');}else if(next&&!cur)document.head.append(next.cloneNode(true));});
    const nextSchema=doc.querySelector('script[type="application/ld+json"][data-page-schema]'),curSchema=document.querySelector('script[type="application/ld+json"][data-page-schema]');
    if(nextSchema&&curSchema)curSchema.textContent=nextSchema.textContent;
  }
  function isSpaEligible(a,u){
    if(!a||a.hasAttribute('download')||a.hasAttribute('data-lightbox')||a.dataset.noSpa!==undefined||a.target==='_blank')return false;
    if(u.origin!==location.origin)return false;
    const raw=a.getAttribute('href')||'';if(!raw||raw.startsWith('#')||raw.startsWith('mailto:')||raw.startsWith('tel:')||raw.startsWith('javascript:'))return false;
    const p=u.pathname.toLowerCase();
    if(p.includes('/admin')||p.includes('/portal')||p.includes('/member/')||p.includes('/public/')||p.endsWith('/sitemap.xml')||p.endsWith('/robots.txt'))return false;
    return true;
  }
  async function spaNavigate(href,push=true){
    if(navigating)return;navigating=true;
    try{
      setLoading(true);
      const r=await fetch(href,{credentials:'same-origin',headers:{'X-Requested-With':'97Foundation-SPA','Accept':'text/html'}});if(!r.ok)throw new Error('Navigation failed');
      const html=await r.text(),doc=new DOMParser().parseFromString(html,'text/html'),nextMain=doc.querySelector('main[data-spa-main]'),cur=qs('main[data-spa-main]');
      if(!nextMain||!cur){location.href=href;return;}
      cur.classList.add('spa-leaving');await new Promise(res=>setTimeout(res,140));
      cur.innerHTML=nextMain.innerHTML;cur.classList.remove('spa-leaving');cur.classList.add('spa-entering');requestAnimationFrame(()=>requestAnimationFrame(()=>cur.classList.remove('spa-entering')));
      syncHead(doc);
      ['[data-primary-nav]','[data-mobile-nav]'].forEach(sel=>{const next=doc.querySelector(sel),curNav=qs(sel);if(next&&curNav)curNav.innerHTML=next.innerHTML;});closeDrawer(false);closeDropdowns();
      if(push)history.pushState({href},'',href);
      scrollTo({top:0,behavior:'auto'});initAll();cur.focus({preventScroll:true});
    }catch(e){location.href=href;return;}
    finally{navigating=false;setTimeout(()=>setLoading(false),160)}
  }
  document.addEventListener('click',e=>{
    if(e.defaultPrevented||e.metaKey||e.ctrlKey||e.shiftKey||e.altKey||e.button>0)return;
    const a=e.target.closest('a[href]');if(!a)return;let u;try{u=new URL(a.href,location.href)}catch{return;}if(!isSpaEligible(a,u))return;
    e.preventDefault();spaNavigate(u.href,true);
  });
  document.addEventListener('submit',e=>{
    const form=e.target.closest('form');if(!form||form.target==='_blank'||form.dataset.noLoader!==undefined)return;
    // Native POSTs and other full-page form submissions show the branded loader immediately.
    setLoading(true);
  });
  addEventListener('pageshow',()=>setTimeout(()=>setLoading(false),120));
  addEventListener('popstate',()=>spaNavigate(location.href,false));addEventListener('scroll',initHeader,{passive:true});
  document.addEventListener('DOMContentLoaded',()=>{initAll();setTimeout(()=>setLoading(false),260)});
})();
