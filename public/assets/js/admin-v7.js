/* 97 Foundation V7.1 — Admin workspace enhancements (loaded after admin.js). */
(() => {
  const qs = (s, r = document) => r.querySelector(s);
  const qsa = (s, r = document) => [...r.querySelectorAll(s)];

  document.addEventListener('DOMContentLoaded', () => {
    /* ---------- Sidebar: scrim, Escape, "/" shortcut, search labels ---------- */
    const sidebar = qs('.sidebar');
    const scrim = qs('[data-admin-menu-close]');
    const toggle = qs('[data-admin-menu]');
    const syncScrim = () => {
      const open = !!sidebar?.classList.contains('open');
      if (scrim) scrim.hidden = !open;
      toggle?.setAttribute('aria-expanded', open ? 'true' : 'false');
      document.body.classList.toggle('admin-nav-open', open);
    };
    if (sidebar) new MutationObserver(syncScrim).observe(sidebar, { attributes: true, attributeFilter: ['class'] });
    scrim?.addEventListener('click', () => sidebar?.classList.remove('open'));
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && sidebar?.classList.contains('open')) sidebar.classList.remove('open');
      const tag = (e.target.tagName || '').toLowerCase();
      if (e.key === '/' && !['input', 'textarea', 'select'].includes(tag) && !e.target.isContentEditable) {
        const s = qs('[data-admin-search]');
        if (s && s.offsetParent !== null) { e.preventDefault(); s.focus(); }
      }
    });
    qs('[data-admin-search]')?.addEventListener('input', e => {
      const has = e.target.value.trim() !== '';
      qsa('.sidebar .side-label').forEach(p => { p.style.display = has ? 'none' : ''; });
      qsa('.sidebar .side-submenu > a').forEach(a => {
        a.style.display = !has || a.textContent.toLowerCase().includes(e.target.value.trim().toLowerCase()) ? '' : 'none';
      });
    });

    /* ---------- File inputs: show chosen file name + instant preview ---------- */
    qsa('.admin-v7 input[type="file"]').forEach(input => {
      input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        const holder = input.closest('label');
        let note = holder?.querySelector('.v7-file-name');
        if (holder && !note) { note = document.createElement('small'); note.className = 'v7-file-name'; holder.append(note); }
        if (note) note.textContent = file ? `Selected: ${file.name} · ${(file.size / 1024 / 1024).toFixed(2)} MB` : '';
        if (!file || !/^image\//.test(file.type)) return;
        const target = input.closest('.hero7-slot')?.querySelector('.hero7-slot-media img')
          || input.closest('.hero7-circle-slot')?.querySelector('img')
          || input.closest('.branding-asset-card')?.querySelector('.branding-preview img')
          || (input.name === 'photo' ? (qs('[data-official-photo-preview] img') || qs('[data-admin-photo-preview] img')) : null)
          || (input.name === 'featured_image' ? qs('[data-post7-preview] img') : null);
        if (!target) return;
        // Data URL (not blob:) keeps the preview inside the site's Content-Security-Policy img-src.
        const reader = new FileReader();
        reader.onload = () => {
          target.hidden = false; target.src = String(reader.result || '');
          target.closest('[data-official-photo-preview]')?.classList.add('has-photo');
        };
        reader.readAsDataURL(file);
      });
    });

    /* ---------- Hero banner live preview ---------- */
    const heroForm = qs('[data-hero7-admin]');
    if (heroForm) {
      qsa('[data-hero7-bind]', heroForm).forEach(field => {
        const out = qs(`[data-hero7-out="${field.dataset.hero7Bind}"]`, heroForm);
        field.addEventListener('input', () => { if (out) out.textContent = field.value; });
      });
      const range = qs('[data-hero7-overlay]', heroForm), preview = qs('[data-hero7-preview]', heroForm), out = qs('[data-hero7-overlay-out]', heroForm);
      range?.addEventListener('input', () => {
        if (preview) preview.style.setProperty('--o', (range.value / 100).toFixed(2));
        if (out) out.textContent = `${range.value}%`;
      });
    }


    /* ---------- Header logo size (Branding & SEO) ---------- */
    const hb = qs('[data-header-brand]');
    if (hb) {
      const preview = qs('[data-hb-preview]', hb);
      const input = k => qs(`[data-hb-input="${k}"]`, hb);
      const out = k => qs(`[data-hb-out="${k}"]`, hb);
      const mark = qs('.v7-hb-mark', hb);
      const sync = () => {
        const logo = +input('logo').value, markH = +input('mark').value, logoM = +input('logo-m').value, showMark = input('show-mark').checked;
        preview.style.setProperty('--pl', logo + 'px');
        preview.style.setProperty('--pm', markH + 'px');
        preview.style.setProperty('--plm', logoM + 'px');
        preview.classList.toggle('no-mark', !showMark);
        if (mark) mark.hidden = !showMark;
        out('logo').textContent = logo + 'px'; out('mark').textContent = markH + 'px'; out('logo-m').textContent = logoM + 'px';
        const h = qs('[data-hb-height]', hb), hm = qs('[data-hb-height-m]', hb);
        if (h) h.textContent = Math.max(72, Math.max(logo, showMark ? markH : 0) + 18);
        if (hm) hm.textContent = Math.max(62, logoM + 16);
        qsa('[data-hb-preset]', hb).forEach(b => b.classList.toggle('is-current', b.dataset.hbPreset === `${logo},${markH},${logoM}`));
      };
      ['logo', 'mark', 'logo-m', 'show-mark'].forEach(k => input(k)?.addEventListener('input', sync));
      input('show-mark')?.addEventListener('change', sync);
      qsa('[data-hb-preset]', hb).forEach(btn => btn.addEventListener('click', () => {
        const [l, m, lm] = btn.dataset.hbPreset.split(',');
        input('logo').value = l; input('mark').value = m; input('logo-m').value = lm; sync();
      }));
      qsa('[data-hb-device]', hb).forEach(btn => btn.addEventListener('click', () => {
        qsa('[data-hb-device]', hb).forEach(b => b.classList.toggle('active', b === btn));
        preview.dataset.device = btn.dataset.hbDevice;
      }));
      // Logo / text image uploads elsewhere on the page update this preview too.
      qsa('input[type="file"][name="logo"],input[type="file"][name="wordmark"]').forEach(f => f.addEventListener('change', () => {
        const file = f.files && f.files[0]; if (!file || !/^image\//.test(file.type)) return;
        const r = new FileReader();
        r.onload = () => { const img = qs(f.name === 'logo' ? '.v7-hb-logo' : '.v7-hb-mark', hb); if (img) img.src = String(r.result || ''); };
        r.readAsDataURL(file);
      }));
      sync();
    }


    /* ---------- Administrators manager ---------- */
    const adminForm = qs('[data-admin-form]');
    if (adminForm) {
      const el = n => adminForm.elements.namedItem(n);
      const title = qs('[data-admin-editor-title]'), sub = qs('[data-admin-editor-sub]'), submit = qs('[data-admin-submit]');
      const pwd = qs('[data-admin-password]'), pwd2 = qs('[data-admin-password-confirm]'), meter = qs('[data-admin-strength]');
      const role = qs('[data-admin-role]'), roleDesc = qs('[data-admin-role-desc]');
      const preview = qs('[data-admin-photo-preview]'), initials = qs('[data-admin-initials]'), removeRow = qs('[data-admin-remove-photo]');
      const pwLabel = qs('[data-admin-password-label]');
      const initialsOf = v => (v || '').trim().split(/\s+/).slice(0, 2).map(p => p[0] || '').join('').toUpperCase() || 'A';
      const syncRole = () => { const o = role?.selectedOptions[0]; if (roleDesc && o) roleDesc.textContent = o.dataset.desc || ''; };
      const strength = v => { let s = 0; if (v.length >= 12) s++; if (/[A-Z]/.test(v) && /[a-z]/.test(v)) s++; if (/\d/.test(v)) s++; if (/[^A-Za-z0-9]/.test(v)) s++; return v ? s : 0; };
      const syncStrength = () => {
        const lvl = strength(pwd.value); meter.dataset.level = String(lvl);
        const span = qs('span', meter);
        if (span) span.textContent = !pwd.value ? (el('id').value ? 'Leave blank to keep the current password.' : 'Use 12+ characters with upper and lower case, a number and a symbol.') : lvl === 4 ? 'Strong password.' : 'Add ' + [pwd.value.length < 12 ? '12+ characters' : '', !(/[A-Z]/.test(pwd.value) && /[a-z]/.test(pwd.value)) ? 'upper & lower case' : '', !/\d/.test(pwd.value) ? 'a number' : '', !/[^A-Za-z0-9]/.test(pwd.value) ? 'a symbol' : ''].filter(Boolean).join(', ') + '.';
        pwd2.setCustomValidity(pwd2.value && pwd2.value !== pwd.value ? 'Passwords do not match.' : '');
      };
      const setPhoto = url => {
        const img = qs('img', preview);
        if (url) { img.src = url; img.hidden = false; } else { img.removeAttribute('src'); img.hidden = true; }
        if (removeRow) removeRow.hidden = !url;
      };
      const setMode = (editing, isMe) => {
        title.textContent = editing ? 'Edit Administrator' : 'Add Administrator';
        sub.textContent = editing ? 'Update details, role or status. Leave the password blank to keep it.' : 'The new administrator signs in with this email and password.';
        submit.textContent = editing ? 'Save Changes' : 'Create Administrator';
        pwLabel.textContent = editing ? 'Reset password (optional)' : 'Password *';
        pwd.required = !editing; pwd2.required = !editing;
        [el('role'), el('status')].forEach(f => { f.disabled = !!isMe; f.title = isMe ? 'You cannot change your own role or status.' : ''; });
        syncStrength();
      };
      const reset = () => { adminForm.reset(); el('id').value = ''; setPhoto(''); initials.textContent = 'A'; setMode(false, false); syncRole(); };
      qsa('[data-admin-edit]').forEach(btn => btn.addEventListener('click', () => {
        let d = {}; try { d = JSON.parse(btn.getAttribute('data-admin-edit')); } catch { return; }
        adminForm.reset();
        ['id', 'name', 'email', 'username', 'role', 'status'].forEach(k => { const f = el(k); if (f) f.value = d[k] ?? ''; });
        initials.textContent = initialsOf(d.name); setPhoto(d.photo_url || ''); setMode(true, !!Number(d.is_me)); syncRole();
        qs('#admin-editor')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        el('name')?.focus({ preventScroll: true });
      }));
      qsa('[data-admin-new],[data-admin-reset]').forEach(b => b.addEventListener('click', () => { reset(); qs('#admin-editor')?.scrollIntoView({ behavior: 'smooth', block: 'start' }); el('name')?.focus({ preventScroll: true }); }));
      // Disabled selects are not submitted; re-enable just before sending (the server also blocks self-demotion).
      adminForm.addEventListener('submit', () => { [el('role'), el('status')].forEach(f => { f.disabled = false; }); });
      qs('[data-admin-name]')?.addEventListener('input', e => { if (qs('img', preview).hidden) initials.textContent = initialsOf(e.target.value); });
      role?.addEventListener('change', syncRole);
      pwd.addEventListener('input', syncStrength); pwd2.addEventListener('input', syncStrength);
      qs('[data-admin-generate]')?.addEventListener('click', () => {
        const sets = ['ABCDEFGHJKLMNPQRSTUVWXYZ', 'abcdefghijkmnopqrstuvwxyz', '23456789', '!@#$%&*?-+='];
        const all = sets.join(''); const rnd = n => { const a = new Uint32Array(1); crypto.getRandomValues(a); return a[0] % n; };
        let chars = sets.map(s => s[rnd(s.length)]); while (chars.length < 16) chars.push(all[rnd(all.length)]);
        for (let i = chars.length - 1; i > 0; i--) { const j = rnd(i + 1); [chars[i], chars[j]] = [chars[j], chars[i]]; }
        pwd.value = pwd2.value = chars.join(''); pwd.type = 'text';
        const t = qs('[data-admin-toggle-password]'); if (t) t.textContent = 'Hide';
        syncStrength();
      });
      qs('[data-admin-toggle-password]')?.addEventListener('click', e => { const show = pwd.type === 'password'; pwd.type = pwd2.type = show ? 'text' : 'password'; e.currentTarget.textContent = show ? 'Hide' : 'Show'; });
      qs('[data-admin-copy-password]')?.addEventListener('click', async e => {
        if (!pwd.value) return; const b = e.currentTarget;
        try { await navigator.clipboard.writeText(pwd.value); b.textContent = 'Copied'; } catch { pwd.type = 'text'; pwd.select(); b.textContent = 'Select'; }
        setTimeout(() => { b.textContent = 'Copy'; }, 1600);
      });
      reset();
    }

    /* ---------- Officials editor ---------- */
    const officialForm = qs('[data-official-form]');
    if (officialForm) {
      const title = qs('[data-official-editor-title]');
      const preview = qs('[data-official-photo-preview]');
      const removeRow = qs('[data-official-remove-photo]');
      const setPhoto = url => {
        const img = preview ? qs('img', preview) : null;
        if (!img) return;
        if (url) { img.src = url; img.hidden = false; preview.classList.add('has-photo'); }
        else { img.removeAttribute('src'); img.hidden = true; preview.classList.remove('has-photo'); }
        if (removeRow) removeRow.hidden = !url;
      };
      qsa('[data-official-edit]').forEach(btn => btn.addEventListener('click', () => {
        let data = {};
        try { data = JSON.parse(btn.getAttribute('data-official-edit')); } catch { return; }
        officialForm.reset();
        Object.entries(data).forEach(([k, v]) => {
          const el = officialForm.elements.namedItem(k);
          if (!el || el.type === 'file') return;
          if (el.type === 'checkbox') el.checked = !!Number(v); else el.value = v ?? '';
        });
        setPhoto(data.photo_url || '');
        if (title) title.textContent = 'Edit Official';
        officialForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
        officialForm.classList.add('editor-pulse'); setTimeout(() => officialForm.classList.remove('editor-pulse'), 500);
        officialForm.elements.full_name?.focus({ preventScroll: true });
      }));
      qs('[data-official-reset]')?.addEventListener('click', () => {
        officialForm.reset();
        if (officialForm.elements.id) officialForm.elements.id.value = '';
        setPhoto('');
        if (title) title.textContent = 'Add Official';
        officialForm.elements.full_name?.focus();
      });
    }
  });
})();

/* V7.2 — fundraising switch: reveal the paused options only when "Paused" is chosen. */
(function(){
  document.querySelectorAll('[data-fundraising-form]').forEach(function(form){
    var options=form.querySelector('[data-fr-options]');if(!options)return;
    form.addEventListener('change',function(e){if(e.target&&e.target.name==='enabled')options.hidden=e.target.value==='1';});
  });
})();


/* V7.2 — News & Blog editor: edit/new, readable web address, SEO counters and preview, formatting toolbar. */
(function(){
  const qs=(s,r=document)=>r.querySelector(s),qsa=(s,r=document)=>[...r.querySelectorAll(s)];
  const form=qs('[data-post7-form]');
  // Confirmation for single buttons (e.g. category delete inside a shared form).
  document.addEventListener('click',e=>{const b=e.target.closest('[data-confirm-click]');if(b&&!confirm(b.dataset.confirmClick)){e.preventDefault();e.stopImmediatePropagation();}},true);
  // Copy public links.
  document.addEventListener('click',async e=>{const b=e.target.closest('[data-copy-link]');if(!b)return;const link=b.dataset.copyLink;try{await navigator.clipboard.writeText(link);}catch{const t=document.createElement('textarea');t.value=link;document.body.append(t);t.select();try{document.execCommand('copy');}catch{}t.remove();}const old=b.textContent;b.textContent='Copied';setTimeout(()=>{b.textContent=old;},1600);});
  if(!form)return;
  const f=n=>form.elements[n];
  const slugify=v=>String(v||'').toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,180);
  const title=qs('[data-post7-title]'),sub=qs('[data-post7-sub]'),submit=qs('[data-post7-submit]',form),preview=qs('[data-post7-preview]'),removeLine=qs('[data-post7-remove]',form),slug=qs('[data-post7-slug]',form);
  let slugTouched=false;
  const serp=()=>{const t=(f('meta_title').value||f('title').value||'Article title').trim();const d=(f('meta_description').value||f('excerpt').value||'The summary or meta description appears here.').trim();qs('[data-serp-title]').textContent=t.length>60?t.slice(0,58)+'…':(t.includes('97 Foundation')?t:t+' | 97 Foundation');qs('[data-serp-desc]').textContent=d.length>160?d.slice(0,157)+'…':d;const sl=slug.value||slug.placeholder;qs('[data-serp-url]').textContent=(form.dataset.baseUrl||'').replace(/^https?:\/\//,'')+(sl&&sl!=='created from the title'?sl:'…');
    qsa('[data-post7-counter]').forEach(c=>{const field=f(c.dataset.post7Counter);const lim=c.dataset.post7Counter==='meta_title'?60:160;const n=field.value.length;c.textContent=`${n} / ${lim}`;c.classList.toggle('is-over',n>lim);});};
  const setPreview=src=>{const img=qs('img',preview);if(!img)return;if(src){img.src=src;img.hidden=false;preview.classList.add('has-photo');}else{img.removeAttribute('src');img.hidden=true;preview.classList.remove('has-photo');}};
  const reset=()=>{form.reset();f('id').value='';slugTouched=false;slug.placeholder='created from the title';setPreview('');if(removeLine)removeLine.hidden=true;title.textContent='New article';sub.textContent='Draft first, publish when it is ready.';submit.textContent='Save article';serp();};
  const open=()=>{qs('#post-editor')?.scrollIntoView({behavior:'smooth',block:'start'});setTimeout(()=>f('title').focus({preventScroll:true}),350);};
  qsa('[data-post7-new],[data-post7-reset]').forEach(b=>b.addEventListener('click',()=>{reset();if(b.hasAttribute('data-post7-new'))open();}));
  document.addEventListener('click',e=>{const b=e.target.closest('[data-post7-edit]');if(!b)return;let d;try{d=JSON.parse(b.dataset.post7Edit);}catch{return;}reset();
    ['id','post_type','category_id','title','slug','excerpt','content','status','published_at','meta_title','meta_description'].forEach(k=>{if(f(k))f(k).value=d[k]??'';});
    f('is_featured').checked=Number(d.is_featured)===1;slugTouched=true;setPreview(d.image||'');if(removeLine)removeLine.hidden=!d.image;
    title.textContent='Edit article';sub.textContent=d.status==='published'?'This article is published. Saving updates it straight away.':'Not visible on the website yet.';submit.textContent='Update article';serp();open();});
  f('title').addEventListener('input',()=>{if(!slugTouched&&!f('id').value)slug.placeholder=slugify(f('title').value)||'created from the title';serp();});
  slug.addEventListener('input',()=>{slugTouched=slug.value!=='';const c=slugify(slug.value);if(slug.value!==c&&!/-$/.test(slug.value))slug.value=c;serp();});
  slug.addEventListener('blur',()=>{slug.value=slugify(slug.value);serp();});
  ['meta_title','meta_description','excerpt'].forEach(n=>f(n).addEventListener('input',serp));
  form.addEventListener('submit',()=>{if(!slug.value&&!f('id').value)slug.value=slugify(f('title').value);});
  // Formatting toolbar for the plain-text article format.
  const ta=qs('[data-post7-content]',form);
  qsa('[data-fmt]',form).forEach(btn=>btn.addEventListener('click',()=>{
    const s=ta.selectionStart,e=ta.selectionEnd,v=ta.value,sel=v.slice(s,e);let out=sel,cursor=0;
    const lineStart=v.lastIndexOf('\n',s-1)+1;
    switch(btn.dataset.fmt){
      case 'h2':out=(s===lineStart?'':'\n\n')+'## '+(sel||'Section heading')+'\n\n';break;
      case 'h3':out=(s===lineStart?'':'\n\n')+'### '+(sel||'Sub-heading')+'\n\n';break;
      case 'bold':out='**'+(sel||'important words')+'**';break;
      case 'list':out=(s===lineStart?'':'\n\n')+(sel?sel.split('\n').map(l=>'- '+l.replace(/^[-*]\s+/,'')).join('\n'):'- First point\n- Second point')+'\n\n';break;
      case 'quote':out=(s===lineStart?'':'\n\n')+'> '+(sel||'Quote')+'\n\n';break;
      case 'link':{const url=prompt('Link address (https://…)','https://');if(!url)return;out='['+(sel||'link text')+']('+url.trim()+')';break;}
    }
    ta.setRangeText(out,s,e,'end');ta.focus();ta.dispatchEvent(new Event('input'));
  }));
  serp();
  if(location.hash==='#categories')qs('#categories')?.scrollIntoView();
})();

/* V7.2 — recipient picker helpers, media editors (gallery, causes, events) and switch forms. */
(function(){
  const qs=(s,r=document)=>r.querySelector(s),qsa=(s,r=document)=>[...r.querySelectorAll(s)];
  // Recipients: count, select all shown, clear.
  qsa('[data-recipient-picker]').forEach(picker=>{
    const count=qs('[data-recipient-count]',picker),boxes=()=>qsa('input[name="recipient_ids[]"]',picker);
    const sync=()=>{const n=boxes().filter(b=>b.checked).length;if(count)count.textContent=n?`${n} selected`:'None selected';boxes().forEach(b=>b.closest('[data-recipient-row]')?.classList.toggle('is-checked',b.checked));};
    picker.addEventListener('change',sync);
    qs('[data-recipient-all]',picker)?.addEventListener('click',()=>{boxes().forEach(b=>{const row=b.closest('[data-recipient-row]');if(row&&row.style.display!=='none')b.checked=true;});sync();});
    qs('[data-recipient-none]',picker)?.addEventListener('click',()=>{boxes().forEach(b=>{b.checked=false;});sync();});
    sync();
  });
  // Media editors: show the current image and switch the editor title between Add and Edit.
  const editors=[['gallery','photo'],['cause','cause'],['event','event']];
  editors.forEach(([key,noun])=>{
    const form=qs(`[data-${key}-form]`);if(!form)return;
    const panel=form.closest('.admin-panel'),title=qs('[data-media-title]',panel),preview=qs('[data-media-preview]',panel),submit=qs('[data-media-submit]',form),note=qs('[data-media-photo-note]',form);
    const setPreview=src=>{const img=preview&&qs('img',preview);if(!img)return;if(src){img.src=src;img.hidden=false;preview.classList.add('has-photo');}else{img.removeAttribute('src');img.hidden=true;preview.classList.remove('has-photo');}};
    const label=(edit)=>{if(title)title.textContent=edit?`Edit ${noun}`:(noun==='event'?'New event':`Add ${noun}`);if(submit)submit.textContent=edit?'Update '+noun:'Save '+noun;if(note)note.textContent=edit?'optional — keeps the current photo':'required for new photos';};
    document.addEventListener('click',e=>{
      const editBtn=e.target.closest(`[data-${key}-edit]`);
      if(editBtn){let d={};try{d=JSON.parse(editBtn.getAttribute(`data-${key}-edit`));}catch{}setTimeout(()=>{setPreview(d.image||'');label(true);panel.scrollIntoView({behavior:'smooth',block:'start'});form.querySelector('input:not([type=hidden]):not([type=file]),textarea')?.focus({preventScroll:true});},0);return;}
      if(e.target.closest(`[data-${key}-reset]`)){form.reset();const id=form.querySelector('input[name="id"]');if(id)id.value='';setTimeout(()=>{setPreview('');label(false);},0);}
    });
    const file=qs('input[type="file"]',form);
    file?.addEventListener('change',()=>{const f=file.files&&file.files[0];if(!f||!/^image\//.test(f.type))return;const r=new FileReader();r.onload=()=>setPreview(String(r.result||''));r.readAsDataURL(f);});
  });
  // Switch forms: reveal the options only when needed is handled per form; keep Save disabled until the choice changes.
  qsa('[data-switch-form]').forEach(form=>{
    const btn=qs('button[type="submit"]',form),start=qs('input[name="enabled"]:checked',form)?.value;
    if(!btn||btn.disabled)return;
    const sync=()=>{const now=qs('input[name="enabled"]:checked',form)?.value;btn.classList.toggle('is-pending',now!==start);};
    form.addEventListener('change',sync);sync();
  });
})();
