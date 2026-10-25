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
          || (input.name === 'photo' ? (qs('[data-official-photo-preview] img') || qs('[data-admin-photo-preview] img')) : null);
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
