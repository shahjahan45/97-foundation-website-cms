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
      const link=qs('.nav7-link',li);let t=null,holdFocusOpen=false;
      const open=()=>{clearTimeout(t);closeDropdowns(li);li.classList.remove('is-closing');li.classList.add('is-open');link?.setAttribute('aria-expanded','true');};
      const close=(delay=140)=>{clearTimeout(t);t=setTimeout(()=>{li.classList.remove('is-open');link?.setAttribute('aria-expanded','false');},delay);};
      li.addEventListener('mouseenter',()=>{if(lastPointer==='mouse')open();});
      li.addEventListener('mouseleave',()=>{li.classList.remove('is-closing');if(lastPointer==='mouse')close();});
      li.addEventListener('focusin',()=>{if(holdFocusOpen){holdFocusOpen=false;return;}open();});
      li.addEventListener('focusout',e=>{if(!li.contains(e.relatedTarget))close(0);});
      link?.addEventListener('click',e=>{if(lastPointer!=='mouse'&&!li.classList.contains('is-open')){e.preventDefault();e.stopImmediatePropagation();open();}});
      link?.addEventListener('keydown',e=>{if(e.key==='ArrowDown'){e.preventDefault();open();qs('.nav7-dropdown a',li)?.focus();}});
      li.addEventListener('keydown',e=>{
        const items=qsa('.nav7-dropdown a',li),idx=items.indexOf(document.activeElement);
        if(e.key==='Escape'){li.classList.remove('is-open');link?.setAttribute('aria-expanded','false');if(document.activeElement!==link){holdFocusOpen=true;link?.focus();}}
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
      // Escape closes the mobile menu wherever focus currently is.
      document.addEventListener('keydown',e=>{if(e.key==='Escape'&&drawer()?.classList.contains('is-open')){e.preventDefault();closeDrawer();}});
      addEventListener('resize',()=>{if(innerWidth>=1120)closeDrawer(false);},{passive:true});
    }
  }
  /* ---------- V7.2 motion: staggered reveal, counters, progress, parallax ---------- */
  const motionOn=()=>document.documentElement.classList.contains('js-motion')&&'IntersectionObserver' in window&&!matchMedia('(prefers-reduced-motion: reduce)').matches;
  function initReveal(){
    if(revealObserver)revealObserver.disconnect();
    const legacy=qsa('.reveal'),modern=qsa('[data-reveal]');
    if(!motionOn()){legacy.forEach(x=>x.classList.add('in-view'));modern.forEach(x=>{x.classList.add('in-view');x.removeAttribute('data-reveal');});return;}
    // Children of a reveal group enter one after another.
    qsa('[data-reveal-group]').forEach(g=>{let i=0;qsa('[data-reveal]',g).forEach(el=>{if(el.parentElement?.closest('[data-reveal-group]')!==g)return;el.style.setProperty('--rd',Math.min(i++,7)*90+'ms');});});
    // Once revealed, hand the element back to its own hover/transition styles.
    const settle=el=>{const d=parseFloat(el.style.getPropertyValue('--rd'))||0;setTimeout(()=>{el.removeAttribute('data-reveal');el.style.removeProperty('--rd');},1300+d);};
    revealObserver=new IntersectionObserver(es=>es.forEach(e=>{if(!e.isIntersecting)return;const el=e.target;el.classList.add('in-view');revealObserver.unobserve(el);if(el.hasAttribute('data-reveal'))settle(el);}),{threshold:.12,rootMargin:'0px 0px -6% 0px'});
    [...legacy,...modern].forEach(x=>revealObserver.observe(x));
  }
  let countObserver=null;
  const fmtNum=(n,dec)=>n.toLocaleString('en-US',{minimumFractionDigits:dec,maximumFractionDigits:dec});
  function runCount(el){
    const target=parseFloat(el.dataset.count)||0,dec=parseInt(el.dataset.decimals||'0',10)||0;
    if(target<=0){el.textContent=fmtNum(0,dec);return;}
    const dur=Math.min(2200,1000+String(Math.round(target)).length*200),t0=performance.now();
    const tick=t=>{const p=Math.min(1,(t-t0)/dur),eased=1-Math.pow(1-p,4);el.textContent=fmtNum(p<1?target*eased:target,dec);if(p<1)requestAnimationFrame(tick);};
    requestAnimationFrame(tick);
  }
  function initCounters(){
    if(countObserver)countObserver.disconnect();
    const counts=qsa('[data-count]'),bars=qsa('[data-progress]');
    if(!motionOn()){bars.forEach(b=>b.classList.add('is-filled'));return;}
    counts.forEach(el=>{if(el.dataset.countReady)return;el.dataset.countReady='1';el.textContent=fmtNum(0,parseInt(el.dataset.decimals||'0',10)||0);});
    countObserver=new IntersectionObserver(es=>es.forEach(e=>{
      if(!e.isIntersecting)return;const el=e.target;countObserver.unobserve(el);
      if(el.hasAttribute('data-progress')){setTimeout(()=>{el.classList.add('is-filled');qsa('[data-count]',el).forEach(runCount);},220);}else runCount(el);
    }),{threshold:.4});
    bars.forEach(b=>countObserver.observe(b));
    counts.forEach(c=>{if(!c.closest('[data-progress]'))countObserver.observe(c);});
  }
  let parallaxEls=[],fxTick=false;
  function onScrollFx(){
    if(fxTick)return;fxTick=true;
    requestAnimationFrame(()=>{
      fxTick=false;
      const max=document.documentElement.scrollHeight-innerHeight,p=max>0?Math.min(1,Math.max(0,scrollY/max)):0;
      const bar=qs('[data-scroll-progress]');if(bar)bar.style.transform=`scaleX(${p.toFixed(4)})`;
      const top=qs('[data-back-top]');if(top)top.style.setProperty('--bp',p.toFixed(3));
      const vh=innerHeight;
      parallaxEls.forEach(el=>{const r=el.getBoundingClientRect();if(r.bottom<-120||r.top>vh+120)return;const speed=parseFloat(el.dataset.parallax)||.06;el.style.setProperty('--py',(((r.top+r.height/2)-vh/2)*-speed).toFixed(1)+'px');});
    });
  }
  function initScrollFx(){parallaxEls=motionOn()?qsa('[data-parallax]'):[];onScrollFx();}
  /* ---------- V7.1 hero: horizontal five-image background loop ---------- */
  let hero7Cleanup=null;
  function initHero(){
    if(hero7Cleanup){hero7Cleanup();hero7Cleanup=null;}
    const root=qs('[data-hero7]');if(!root)return;
    const track=qs('[data-hero7-track]',root),slides=qsa('[data-hero7-slide]',root),dots=qsa('[data-hero7-dot]',root),current=qs('[data-hero7-current]',root),live=qs('[data-hero7-live]',root);
    const n=slides.length;if(!track||n<2)return;
    const reduced=matchMedia('(prefers-reduced-motion: reduce)').matches;
    const delay=Math.max(3,Math.min(15,parseInt(root.dataset.interval||'6',10)||6))*1000;
    root.style.setProperty('--hero7-interval',delay+'ms');root.classList.toggle('is-static',reduced);
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
    // Restart the timer fill inside the active dot so it matches the fresh autoplay delay.
    const refill=()=>{const d=dots[index];if(!d)return;d.classList.remove('is-active');void d.offsetWidth;d.classList.add('is-active');};
    const pause=()=>{paused=true;clearTimeout(timer);root.classList.add('is-paused');};
    const resume=()=>{paused=false;root.classList.remove('is-paused');refill();schedule();};
    const onDot=e=>{const d=e.target.closest('[data-hero7-dot]');if(!d)return;const k=parseInt(d.dataset.hero7Dot,10);goTo(k,true);resume();};
    const onKey=e=>{if(!e.target.closest('[data-hero7-dot]'))return;if(e.key==='ArrowRight'){e.preventDefault();goTo((index+1)%n,true);dots[index]?.focus();}else if(e.key==='ArrowLeft'){e.preventDefault();goTo((index-1+n)%n,true);dots[index]?.focus();}};
    const onDown=e=>{if(e.pointerType==='mouse')return;startX=e.clientX;startY=e.clientY;};
    const onUp=e=>{if(startX===null)return;const dx=e.clientX-startX,dy=e.clientY-startY;startX=null;if(Math.abs(dx)>48&&Math.abs(dx)>Math.abs(dy)*1.3){step(dx<0?1:-1,true);resume();}};
    const onVis=()=>{if(document.hidden)clearTimeout(timer);else{if(!paused)refill();schedule();}};
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
    qsa('[data-lightbox]').forEach(a=>{if(a.dataset.bound)return;a.dataset.bound='1';a.addEventListener('click',e=>{e.preventDefault();const items=qsa('[data-lightbox]').filter(x=>x.offsetParent!==null&&!x.closest('.hidden'));openLightbox(items.length?items:[a],Math.max(0,items.indexOf(a)),a);});});
  }
  /* ---------- V7.2 image viewer: animated, keyboard, swipe, previous/next ---------- */
  const LB_SVG=d=>`<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${d}</svg>`;
  function openLightbox(items,start,opener){
    qs('.lb7')?.remove();
    const reduced=matchMedia('(prefers-reduced-motion: reduce)').matches,multi=items.length>1;let i=start,busy=false,sx=null,sy=null;
    const box=document.createElement('div');box.className='lb7';box.setAttribute('role','dialog');box.setAttribute('aria-modal','true');box.setAttribute('aria-label','Image viewer');
    box.innerHTML=`<span class="lb7-count" aria-live="polite"></span><figure class="lb7-figure"><img class="lb7-img" alt=""><figcaption class="lb7-cap"><b></b><small></small></figcaption></figure><button type="button" class="lb7-btn lb7-close" aria-label="Close image viewer">${LB_SVG('<path d="M6 6l12 12M18 6 6 18"/>')}</button>`+(multi?`<button type="button" class="lb7-btn lb7-prev" aria-label="Previous image">${LB_SVG('<path d="m15 6-6 6 6 6"/>')}</button><button type="button" class="lb7-btn lb7-next" aria-label="Next image">${LB_SVG('<path d="m9 6 6 6-6 6"/>')}</button>`:'');
    const img=qs('.lb7-img',box),cap=qs('.lb7-cap',box),count=qs('.lb7-count',box);
    const meta=a=>{const im=qs('img',a);return{src:a.getAttribute('href')||im?.src||'',title:a.dataset.title||im?.getAttribute('alt')||'',caption:a.dataset.caption||''};};
    const preload=k=>{const m=meta(items[(k+items.length)%items.length]);if(m.src){const p=new Image();p.src=m.src;}};
    const apply=()=>{const m=meta(items[i]);img.src=m.src;img.alt=m.title||'Gallery image';qs('b',cap).textContent=m.title;qs('small',cap).textContent=m.caption;cap.hidden=!m.title&&!m.caption;count.textContent=multi?`${String(i+1).padStart(2,'0')} / ${String(items.length).padStart(2,'0')}`:'';if(multi){preload(i+1);preload(i-1);}};
    const go=dir=>{if(!multi||busy)return;i=(i+dir+items.length)%items.length;if(reduced){apply();return;}busy=true;img.classList.add(dir>0?'is-out-left':'is-out-right');setTimeout(()=>{apply();img.classList.remove('is-out-left','is-out-right');img.classList.add('no-anim',dir>0?'is-out-right':'is-out-left');void img.offsetWidth;img.classList.remove('no-anim','is-out-right','is-out-left');busy=false;},220);};
    const close=()=>{document.removeEventListener('keydown',onKey,true);box.classList.remove('is-open');document.body.classList.remove('lb7-locked');setTimeout(()=>box.remove(),reduced?0:360);opener?.focus({preventScroll:true});};
    const onKey=e=>{if(e.key==='Escape'){e.preventDefault();close();}else if(e.key==='ArrowRight'){e.preventDefault();go(1);}else if(e.key==='ArrowLeft'){e.preventDefault();go(-1);}else if(e.key==='Tab'){const f=qsa('button',box);const k=f.indexOf(document.activeElement);e.preventDefault();f[(k+(e.shiftKey?-1:1)+f.length)%f.length]?.focus();}};
    box.addEventListener('click',e=>{if(e.target.closest('.lb7-close')||e.target===box)close();else if(e.target.closest('.lb7-next'))go(1);else if(e.target.closest('.lb7-prev'))go(-1);});
    box.addEventListener('pointerdown',e=>{sx=e.clientX;sy=e.clientY;},{passive:true});
    box.addEventListener('pointerup',e=>{if(sx===null)return;const dx=e.clientX-sx,dy=e.clientY-sy;sx=null;if(Math.abs(dx)>50&&Math.abs(dx)>Math.abs(dy)*1.3)go(dx<0?1:-1);},{passive:true});
    document.addEventListener('keydown',onKey,true);
    apply();document.body.append(box);document.body.classList.add('lb7-locked');
    requestAnimationFrame(()=>requestAnimationFrame(()=>box.classList.add('is-open')));
    qs('.lb7-close',box).focus({preventScroll:true});
  }
  function initDonation(){
    qsa('[data-amount]').forEach(b=>{if(b.dataset.bound)return;b.dataset.bound='1';b.addEventListener('click',()=>{qsa('[data-amount]').forEach(x=>x.classList.remove('selected'));b.classList.add('selected');const i=qs('[data-amount-input]');if(i){i.value=b.dataset.amount;i.focus();}})});
    qsa('[data-online-amount]').forEach(b=>{if(b.dataset.bound)return;b.dataset.bound='1';b.addEventListener('click',()=>{qsa('[data-online-amount]').forEach(x=>x.classList.remove('selected'));b.classList.add('selected');const i=qs('[data-online-amount-input]');if(i){i.value=b.dataset.onlineAmount;i.focus();}})});
    qsa('[data-donation-tab]').forEach(btn=>{if(btn.dataset.bound)return;btn.dataset.bound='1';btn.addEventListener('click',()=>{if(btn.disabled)return;const key=btn.dataset.donationTab;qsa('[data-donation-tab]').forEach(x=>{const on=x===btn;x.classList.toggle('active',on);x.setAttribute('aria-selected',on?'true':'false')});qsa('[data-donation-panel]').forEach(p=>{const on=p.dataset.donationPanel===key;p.hidden=!on;p.classList.toggle('active',on)});const u=new URL(location.href);if(key==='online')u.searchParams.set('tab','online');else u.searchParams.delete('tab');history.replaceState(history.state,'',u);});});
    qsa('[data-account-choice]').forEach(r=>{if(r.dataset.bound)return;r.dataset.bound='1';r.addEventListener('change',()=>{qsa('.bank-card').forEach(c=>c.classList.remove('selected'));r.closest('.bank-card')?.classList.add('selected');const s=qs('[data-account-select]');if(s)s.value=r.value;})});
    const sel=qs('[data-account-select]');if(sel&&!sel.dataset.bound){sel.dataset.bound='1';sel.addEventListener('change',()=>{qsa('.bank-card').forEach(c=>c.classList.remove('selected'));const r=qs(`[data-account-choice][value="${CSS.escape(sel.value)}"]`);if(r){r.checked=true;r.closest('.bank-card')?.classList.add('selected')}});if(sel.value)sel.dispatchEvent(new Event('change'));}
  }

  /* ---------- V7.2 mandatory donation receipt upload ---------- */
  const RECEIPT_TYPES={'image/jpeg':'JPG','image/png':'PNG','image/webp':'WEBP','application/pdf':'PDF'};
  const fmtBytes=b=>b>=1048576?(b/1048576).toFixed(1)+' MB':Math.max(1,Math.round(b/1024))+' KB';
  function initReceipt(){
    qsa('[data-receipt]').forEach(root=>{
      if(root.dataset.bound)return;root.dataset.bound='1';
      const input=qs('[data-receipt-input]',root),drop=qs('[data-receipt-drop]',root),preview=qs('[data-receipt-preview]',root),thumb=qs('[data-receipt-thumb]',root),nameEl=qs('[data-receipt-name]',root),sizeEl=qs('[data-receipt-size]',root),err=qs('[data-receipt-error]',root),clear=qs('[data-receipt-clear]',root),form=root.closest('form');
      if(!input)return;
      const max=parseInt(root.dataset.max||'8388608',10)||8388608;
      const typeOf=f=>{if(RECEIPT_TYPES[f.type])return f.type;const ext=(f.name.split('.').pop()||'').toLowerCase();return({jpg:'image/jpeg',jpeg:'image/jpeg',png:'image/png',webp:'image/webp',pdf:'application/pdf'})[ext]||'';};
      const showError=msg=>{err.textContent=msg;err.hidden=!msg;root.classList.toggle('is-invalid',!!msg);input.setAttribute('aria-invalid',msg?'true':'false');};
      const reset=()=>{input.value='';preview.hidden=true;thumb.innerHTML='';root.classList.remove('has-file');};
      const validate=f=>{if(!f)return 'Upload your payment receipt to submit this donation.';const t=typeOf(f);if(!t)return 'This file type is not accepted. Upload a JPG, PNG, WEBP image or a PDF.';if(f.size<=0)return 'This file is empty. Choose the original photo or PDF.';if(f.size>max)return `This file is ${fmtBytes(f.size)}. Receipts must be ${fmtBytes(max)} or smaller.`;return '';};
      const show=f=>{
        const t=typeOf(f);nameEl.textContent=f.name;sizeEl.textContent=`${RECEIPT_TYPES[t]} · ${fmtBytes(f.size)} · ready to upload`;thumb.innerHTML='';
        if(t.startsWith('image/')){const r=new FileReader();r.onload=()=>{const im=new Image();im.alt='Receipt preview';im.src=String(r.result);thumb.append(im);};r.readAsDataURL(f);}else thumb.textContent='PDF';
        preview.hidden=false;root.classList.add('has-file');
      };
      const handle=()=>{const f=input.files&&input.files[0];const msg=validate(f);if(msg){reset();showError(f?msg:'');if(!f)return;return;}showError('');show(f);};
      input.addEventListener('change',handle);
      input.addEventListener('invalid',e=>{e.preventDefault();showError('Upload your payment receipt to submit this donation.');drop?.scrollIntoView({block:'center',behavior:'smooth'});});
      clear?.addEventListener('click',()=>{reset();showError('');input.click();});
      ['dragenter','dragover'].forEach(ev=>drop?.addEventListener(ev,e=>{e.preventDefault();root.classList.add('is-drag');}));
      ['dragleave','dragend'].forEach(ev=>drop?.addEventListener(ev,()=>root.classList.remove('is-drag')));
      drop?.addEventListener('drop',e=>{e.preventDefault();root.classList.remove('is-drag');const files=e.dataTransfer?.files;if(!files||!files.length)return;try{const dt=new DataTransfer();dt.items.add(files[0]);input.files=dt.files;}catch{input.files=files;}handle();});
      form?.addEventListener('submit',e=>{const f=input.files&&input.files[0];const msg=validate(f);if(msg){e.preventDefault();e.stopImmediatePropagation();showError(msg);setLoading(false);drop?.scrollIntoView({block:'center',behavior:'smooth'});return;}const b=qs('[data-receipt-submit]',form);if(b){b.disabled=true;b.textContent='Uploading receipt…';}},true);
    });
  }

  /* ---------- V7.2 sharing: native share, copy link, share windows; article contents highlight ---------- */
  function initShare(){
    qsa('[data-share]').forEach(root=>{
      if(root.dataset.bound)return;root.dataset.bound='1';
      const url=root.dataset.shareUrl||location.href,title=root.dataset.shareTitle||document.title,text=root.dataset.shareText||'',status=qs('[data-share-status]',root);
      const native=qs('[data-share-native]',root);
      if(native&&navigator.share&&matchMedia('(pointer:coarse)').matches){native.hidden=false;native.addEventListener('click',async()=>{try{await navigator.share({title,text,url});}catch(e){}});}
      const copy=qs('[data-share-copy]',root),label=qs('[data-share-copy-label]',root);
      copy?.addEventListener('click',async()=>{let ok=true;try{await navigator.clipboard.writeText(url);}catch{const t=document.createElement('textarea');t.value=url;t.setAttribute('readonly','');t.style.position='fixed';t.style.opacity='0';document.body.append(t);t.select();try{ok=document.execCommand('copy');}catch{ok=false;}t.remove();}
        copy.classList.toggle('is-done',ok);if(label)label.textContent=ok?'Link copied':'Copy failed';if(status)status.textContent=ok?'Link copied to clipboard':'Could not copy the link';setTimeout(()=>{copy.classList.remove('is-done');if(label)label.textContent='Copy link';},2200);});
      qsa('[data-share-network]',root).forEach(a=>a.addEventListener('click',e=>{if(a.dataset.shareNetwork==='whatsapp'||matchMedia('(pointer:coarse)').matches)return;const w=window.open(a.href,'share97','width=640,height=620');if(w){try{w.opener=null;}catch{}e.preventDefault();}}));
    });
    const toc=qs('.a7-toc');if(!toc||toc.dataset.bound||!('IntersectionObserver' in window))return;toc.dataset.bound='1';
    const links=qsa('a[href^="#"]',toc),map=new Map(links.map(a=>[decodeURIComponent(a.getAttribute('href').slice(1)),a]));
    const io=new IntersectionObserver(es=>es.forEach(e=>{if(e.isIntersecting){links.forEach(l=>l.classList.remove('is-current'));map.get(e.target.id)?.classList.add('is-current');}}),{rootMargin:'-20% 0px -70% 0px'});
    map.forEach((a,id)=>{const h=document.getElementById(id);if(h)io.observe(h);});
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
  /* ---------- V7.3 scholarship application (mirrors App\Core\Scholarship::validate) ---------- */
  function initScholarshipForm(){
    const form=qs('[data-scholarship-form]');if(!form||form.dataset.schBound)return;form.dataset.schBound='1';
    const reduced=matchMedia('(prefers-reduced-motion: reduce)').matches;
    const summary=qs('[data-sch-summary]'),list=summary?qs('[data-sch-summary-list]',summary):null,text=summary?qs('[data-sch-summary-text]',summary):null;
    const optionSet=name=>new Set([...(form.elements[name]?.options||[])].map(o=>o.value).filter(Boolean));
    const grades=optionSet('student_grade'),purposes=optionSet('purpose');
    const len=v=>[...v].length,squash=v=>String(v||'').replace(/\s+/g,' ').trim();
    let nameRe;try{nameRe=new RegExp("^[\\p{L}\\p{M}][\\p{L}\\p{M} .'\u2019-]*$",'u');}catch{nameRe=/^[^0-9!@#$%^&*()_+=\[\]{};:"\\|,<>\/?~`]+$/;}
    const nic=v=>{const s=String(v||'').replace(/[\s-]+/g,'').toUpperCase();let m,day;if((m=s.match(/^(\d{2})(\d{3})(\d{4})[VX]$/)))day=+m[2];else if((m=s.match(/^(\d{4})(\d{3})(\d{5})$/))){const y=+m[1];if(y<1900||y>new Date().getFullYear())return null;day=+m[2];}else return null;if(day>500)day-=500;return day>=1&&day<=366?s:null;};
    const mobile=v=>{let s=String(v||'').replace(/[\s().-]+/g,'');const m=s.match(/^(?:\+94|0094|94)(7\d{8})$/);if(m)s='0'+m[1];return /^07\d{8}$/.test(s)?s:null;};
    const nameRule=(empty)=>v=>{v=squash(v);if(!v)return empty;return len(v)<3||len(v)>120||!nameRe.test(v)?'Use letters only (3–120 characters). Dots, apostrophes and hyphens are fine.':'';};
    const rules={
      student_name:nameRule("Enter the student's full name."),
      student_grade:v=>grades.has(v)?'':"Choose the student's current grade.",
      guardian_name:nameRule("Enter the parent or guardian's full name."),
      guardian_nic:v=>!String(v||'').trim()?"Enter the parent or guardian's NIC number.":(nic(v)?'':'Enter a valid NIC: 9 digits followed by V or X (e.g. 853202937V), or 12 digits (e.g. 198532002937).'),
      mobile:v=>!String(v||'').trim()?'Enter a contact mobile number.':(mobile(v)?'':'Enter a Sri Lankan mobile number, e.g. 071 234 5678 or +94 71 234 5678.'),
      address:v=>{const a=String(v||'').replace(/\r/g,'').replace(/[ \t]+/g,' ').trim();if(!a)return 'Enter the residential address.';return len(a)<10||len(a)>500?'The address should be between 10 and 500 characters.':'';},
      purpose:v=>purposes.has(v)?'':'Choose the type of scholarship or support.',
      consent:(v,el)=>el.checked?'':'Please confirm the declaration to submit the application.'
    };
    const touched=new Set();
    const wrap=el=>el.closest('[data-field]');
    const show=(name,msg)=>{const el=form.elements[name];if(!el)return;const w=wrap(el),err=qs(`[data-error-for="${name}"]`,form);const filled=el.type==='checkbox'?el.checked:String(el.value).trim()!=='';
      w?.classList.toggle('is-invalid',!!msg);w?.classList.toggle('is-valid',!msg&&filled);
      if(msg)el.setAttribute('aria-invalid','true');else el.removeAttribute('aria-invalid');
      if(err){const span=qs('span',err);if(span)span.textContent=msg||'';err.hidden=!msg;}};
    const check=name=>{const el=form.elements[name];if(!el)return '';const msg=rules[name](el.value,el);show(name,msg);return msg;};
    Object.keys(rules).forEach(name=>{const el=form.elements[name];if(!el)return;
      if(el.getAttribute('aria-invalid')==='true')touched.add(name);
      const instant=el.tagName==='SELECT'||el.type==='checkbox';
      el.addEventListener(instant?'change':'input',()=>{if(instant||touched.has(name)){touched.add(name);check(name);}});
      if(!instant)el.addEventListener('blur',()=>{if(touched.has(name)||String(el.value).trim()!==''){touched.add(name);check(name);}});
    });
    // Friendly tidying once a value is valid.
    const nicEl=form.elements.guardian_nic,nicHint=qs('[data-nic-hint]',form),nicDefault=nicHint?nicHint.textContent:'';
    nicEl?.addEventListener('input',()=>{const n=nic(nicEl.value);if(nicHint)nicHint.textContent=n?(n.length===10?'Old format NIC recognised.':'New format NIC recognised.'):nicDefault;});
    nicEl?.addEventListener('blur',()=>{const n=nic(nicEl.value);if(n&&n!==nicEl.value)nicEl.value=n;});
    const telEl=form.elements.mobile;telEl?.addEventListener('blur',()=>{const m=mobile(telEl.value);if(m)telEl.value=m.replace(/^(\d{3})(\d{3})(\d{4})$/,'$1 $2 $3');});
    const addr=form.elements.address,counter=qs('[data-sch-counter]',form);
    const count=()=>{if(!addr||!counter)return;const n=len(addr.value.trim());counter.textContent=n?`${n} / 500`:'';counter.classList.toggle('is-over',n>500);};
    addr?.addEventListener('input',count);count();
    const purposeEl=form.elements.purpose,purposeNote=qs('[data-purpose-note]',form),purposeDefault=purposeNote?purposeNote.textContent:'';
    const note=()=>{if(!purposeEl||!purposeNote)return;const o=purposeEl.options[purposeEl.selectedIndex];purposeNote.textContent=o&&o.dataset.note?o.dataset.note:purposeDefault;};
    purposeEl?.addEventListener('change',note);note();
    const focusField=name=>{const el=form.elements[name]||qs('#sch-'+name);if(!el)return;el.scrollIntoView({behavior:reduced?'auto':'smooth',block:'center'});el.focus({preventScroll:true});};
    summary?.addEventListener('click',e=>{const a=e.target.closest('a[href^="#sch-"]');if(!a)return;e.preventDefault();focusField(a.getAttribute('href').slice(5));});
    const showSummary=errors=>{if(!summary||!list)return;list.innerHTML='';errors.forEach(([n,m])=>{const li=document.createElement('li'),a=document.createElement('a');a.href='#sch-'+n;a.textContent=m;li.append(a);list.append(li);});
      if(text)text.textContent=errors.length===1?'One detail needs your attention before you submit.':`${errors.length} details need your attention before you submit.`;
      summary.hidden=false;summary.scrollIntoView({behavior:reduced?'auto':'smooth',block:'center'});summary.focus({preventScroll:true});};
    form.addEventListener('submit',e=>{
      const errors=[];Object.keys(rules).forEach(n=>{touched.add(n);const m=check(n);if(m)errors.push([n,m]);});
      if(errors.length){e.preventDefault();e.stopPropagation();showSummary(errors);return;}
      if(summary)summary.hidden=true;
      const b=qs('[data-sch-submit]',form);if(b){b.disabled=true;b.setAttribute('aria-busy','true');const s=qs('span',b);if(s)s.textContent='Submitting…';}
    });
    addEventListener('pageshow',()=>{const b=qs('[data-sch-submit]',form);if(b){b.disabled=false;b.removeAttribute('aria-busy');const s=qs('span',b);if(s)s.textContent='Submit application';}});
    // Errors returned by the server: bring the summary into view.
    if(summary&&!summary.hidden){setTimeout(()=>{summary.scrollIntoView({behavior:'auto',block:'center'});summary.focus({preventScroll:true});},60);}
  }
  function initCopyPrint(){
    qsa('[data-copy-text]').forEach(b=>{if(b.dataset.copyBound)return;b.dataset.copyBound='1';b.addEventListener('click',async()=>{const t=b.dataset.copyText||'';let ok=false;
      try{await navigator.clipboard.writeText(t);ok=true;}catch{try{const ta=document.createElement('textarea');ta.value=t;ta.setAttribute('readonly','');ta.style.position='fixed';ta.style.opacity='0';document.body.append(ta);ta.select();ok=document.execCommand('copy');ta.remove();}catch{ok=false;}}
      const label=qs('span',b);if(!label)return;const old=label.dataset.label||label.textContent;label.dataset.label=old;label.textContent=ok?(b.dataset.copyDone||'Copied'):'Copy failed';b.classList.toggle('is-done',ok);setTimeout(()=>{label.textContent=old;b.classList.remove('is-done');},2200);});});
    qsa('[data-print-page]').forEach(b=>{if(b.dataset.printBound)return;b.dataset.printBound='1';b.addEventListener('click',()=>window.print());});
    const done=qs('[data-sch-done-focus]');if(done&&!done.dataset.focused){done.dataset.focused='1';done.focus({preventScroll:true});}
  }
  /* V7.3 footer: soft light that follows the pointer across the glass panel (mouse only, motion allowed). */
  function initFooterGlow(){
    const shell=qs('[data-footer-shell]');if(!shell||shell.dataset.glowBound)return;shell.dataset.glowBound='1';
    if(!matchMedia('(hover: hover) and (pointer: fine)').matches||matchMedia('(prefers-reduced-motion: reduce)').matches)return;
    let raf=0,x=0,y=0;
    shell.addEventListener('pointermove',e=>{const r=shell.getBoundingClientRect();x=e.clientX-r.left;y=e.clientY-r.top;if(raf)return;raf=requestAnimationFrame(()=>{raf=0;shell.style.setProperty('--mx',x+'px');shell.style.setProperty('--my',y+'px');});},{passive:true});
    shell.addEventListener('pointerenter',()=>shell.classList.add('is-lit'));
    shell.addEventListener('pointerleave',()=>shell.classList.remove('is-lit'));
  }
  function initAll(){initHeader();initMenu();initReveal();initCounters();initScrollFx();initHero();initGallery();initDonation();initReceipt();initShare();initPaymentFlow();initBackTop();initSiteQr();initMemberDirectoryImages();initMemberForms();initLiveChat();initScholarshipForm();initCopyPrint();initFooterGlow();}

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
    if(u.hash&&u.pathname===location.pathname&&u.search===location.search)return false;
    const p=u.pathname.toLowerCase();
    if(p.includes('/admin')||p.includes('/portal')||p.includes('/member/')||p.includes('/public/')||p.endsWith('/sitemap.xml')||p.endsWith('/robots.txt'))return false;
    return true;
  }
  async function spaNavigate(href,push=true){
    if(navigating)return;navigating=true;
    let bar=qs('.spa7-bar');if(!bar){bar=document.createElement('div');bar.className='spa7-bar';bar.setAttribute('aria-hidden','true');document.body.append(bar);}
    bar.className='spa7-bar';void bar.offsetWidth;bar.classList.add('is-loading');
    // The full-screen loader only appears when a page is slow to arrive.
    const slow=setTimeout(()=>setLoading(true),900);
    try{
      const r=await fetch(href,{credentials:'same-origin',headers:{'X-Requested-With':'97Foundation-SPA','Accept':'text/html'}});if(!r.ok)throw new Error('Navigation failed');
      const html=await r.text(),doc=new DOMParser().parseFromString(html,'text/html'),nextMain=doc.querySelector('main[data-spa-main]'),cur=qs('main[data-spa-main]');
      if(!nextMain||!cur){location.href=href;return;}
      cur.classList.add('spa-leaving');await new Promise(res=>setTimeout(res,matchMedia('(prefers-reduced-motion: reduce)').matches?0:170));
      cur.innerHTML=nextMain.innerHTML;cur.classList.remove('spa-leaving');cur.classList.add('spa-entering');requestAnimationFrame(()=>requestAnimationFrame(()=>cur.classList.remove('spa-entering')));
      syncHead(doc);
      ['[data-primary-nav]','[data-mobile-nav]'].forEach(sel=>{const next=doc.querySelector(sel),curNav=qs(sel);if(next&&curNav)curNav.innerHTML=next.innerHTML;});closeDrawer(false);closeDropdowns();
      if(push)history.pushState({href},'',href);
      {const se=document.documentElement,prev=se.style.scrollBehavior;se.style.scrollBehavior='auto';let target=null;try{const h=new URL(href,location.href).hash;if(h)target=document.getElementById(decodeURIComponent(h.slice(1)));}catch{}if(target)target.scrollIntoView();else scrollTo(0,0);se.style.scrollBehavior=prev;}initAll();cur.focus({preventScroll:true});
    }catch(e){location.href=href;return;}
    finally{navigating=false;clearTimeout(slow);bar.classList.remove('is-loading');bar.classList.add('is-done');setTimeout(()=>setLoading(false),120)}
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
  addEventListener('popstate',()=>spaNavigate(location.href,false));addEventListener('scroll',initHeader,{passive:true});addEventListener('scroll',onScrollFx,{passive:true});addEventListener('resize',onScrollFx,{passive:true});
  document.addEventListener('DOMContentLoaded',()=>{initAll();setTimeout(()=>{setLoading(false);document.documentElement.classList.add('motion-ready');},140)});
})();
