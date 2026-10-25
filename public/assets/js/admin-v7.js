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
          || (input.name === 'photo' ? qs('[data-official-photo-preview] img') : null);
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
