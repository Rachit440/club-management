/* =====================================================================
   Elite Club Management Portal - Application JS
   Vanilla JS. Keeps the App API used by all CRUD pages and adds
   boot screen, animated counters, collapsible sidebar, scroll reveal,
   back-to-top, keyboard shortcuts, calendar, circular progress, sorting.
   ===================================================================== */
(function () {
  'use strict';

  const App = {
    /* ----------------------- Boot / loading screen ----------------------- */
    hideBoot() {
      const b = document.getElementById('bootScreen');
      if (b) setTimeout(() => b.classList.add('hidden'), 600);
    },

    /* ----------------------- Theme ----------------------- */
    initTheme() {
      const saved = localStorage.getItem('ecmp_theme') || 'light';
      document.documentElement.setAttribute('data-theme', saved);
      const toggle = document.querySelector('[data-theme-toggle]');
      if (toggle) {
        toggle.innerHTML = saved === 'dark' ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
        toggle.addEventListener('click', () => {
          const cur = document.documentElement.getAttribute('data-theme');
          const next = cur === 'dark' ? 'light' : 'dark';
          document.documentElement.setAttribute('data-theme', next);
          localStorage.setItem('ecmp_theme', next);
          toggle.innerHTML = next === 'dark' ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
          App.toast(`Switched to ${next} mode`, 'info', 1500);
        });
      }
    },

    /* ----------------------- Toasts ----------------------- */
    toast(msg, type = 'info', timeout = 3500) {
      let wrap = document.querySelector('.toast-wrap');
      if (!wrap) { wrap = document.createElement('div'); wrap.className = 'toast-wrap'; document.body.appendChild(wrap); }
      const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', warning: 'fa-triangle-exclamation', info: 'fa-circle-info' };
      const el = document.createElement('div');
      el.className = 'toast ' + type;
      el.innerHTML = '<i class="fa-solid ' + (icons[type] || icons.info) + '"></i><span></span>';
      el.querySelector('span').textContent = msg;
      wrap.appendChild(el);
      setTimeout(() => { el.style.opacity = '0'; el.style.transform = 'translateX(40px)'; setTimeout(() => el.remove(), 300); }, timeout);
    },

    /* ----------------------- Loading overlay ----------------------- */
    showLoading() { const o = document.querySelector('.loading-overlay'); if (o) o.classList.add('open'); },
    hideLoading() { const o = document.querySelector('.loading-overlay'); if (o) o.classList.remove('open'); },

    /* ----------------------- Modals ----------------------- */
    openModal(id) { const m = document.getElementById(id); if (m) { m.classList.add('open'); document.body.style.overflow = 'hidden'; } },
    closeModal(id) { const m = document.getElementById(id); if (m) { m.classList.remove('open'); document.body.style.overflow = ''; } },

    /* ----------------------- Confirm dialog ----------------------- */
    confirm(opts) {
      const { title = 'Are you sure?', text = '', confirmText = 'Confirm', cancelText = 'Cancel', danger = false, onConfirm } = opts;
      const id = 'confirmModal';
      let m = document.getElementById(id);
      if (!m) {
        m = document.createElement('div'); m.id = id; m.className = 'modal-overlay';
        m.innerHTML = `<div class="modal" style="max-width:440px">
          <div class="modal-head"><h3 id="cmTitle"></h3><button class="close" data-close>&times;</button></div>
          <div class="modal-body" id="cmText"></div>
          <div class="modal-foot"><button class="btn btn-secondary" id="cmCancel"></button><button class="btn" id="cmOk"></button></div></div>`;
        document.body.appendChild(m);
        m.addEventListener('click', e => { if (e.target === m || e.target.hasAttribute('data-close')) m.classList.remove('open'); });
      }
      m.querySelector('#cmTitle').textContent = title;
      m.querySelector('#cmText').textContent = text;
      const ok = m.querySelector('#cmOk'); ok.textContent = confirmText;
      ok.className = 'btn ' + (danger ? 'btn-danger' : 'btn-primary');
      m.querySelector('#cmCancel').textContent = cancelText;
      const newOk = ok.cloneNode(true); ok.parentNode.replaceChild(newOk, ok);
      newOk.addEventListener('click', () => { m.classList.remove('open'); if (onConfirm) onConfirm(); });
      m.classList.add('open');
    },

    /* ----------------------- AJAX ----------------------- */
    request(url, { method = 'GET', body = null, headers = {}, json = false } = {}) {
      const opts = { method, headers: Object.assign({}, headers) };
      if (body) {
        if (json) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
        else if (body instanceof FormData) { opts.body = body; }
        else { opts.headers['Content-Type'] = 'application/x-www-form-urlencoded'; opts.body = new URLSearchParams(body).toString(); }
      }
      return fetch(url, opts).then(r => r.json().then(data => ({ ok: r.ok, status: r.status, data })));
    },

    /* ----------------------- Collapsible sidebar ----------------------- */
    initSidebar() {
      const toggle = document.querySelector('[data-menu-toggle]');
      const collapseBtn = document.querySelector('[data-collapse]');
      const sidebar = document.querySelector('.sidebar');
      const app = document.querySelector('.app');
      if (collapseBtn && app) {
        collapseBtn.addEventListener('click', () => {
          app.classList.toggle('collapsed');
          localStorage.setItem('ecmp_collapsed', app.classList.contains('collapsed') ? '1' : '0');
        });
        if (localStorage.getItem('ecmp_collapsed') === '1') app.classList.add('collapsed');
      }
      if (!toggle || !sidebar) return;
      let backdrop = document.querySelector('.sidebar-backdrop');
      if (!backdrop) { backdrop = document.createElement('div'); backdrop.className = 'sidebar-backdrop'; document.body.appendChild(backdrop); }
      const open = () => { sidebar.classList.add('open'); backdrop.classList.add('open'); };
      const close = () => { sidebar.classList.remove('open'); backdrop.classList.remove('open'); };
      toggle.addEventListener('click', () => sidebar.classList.contains('open') ? close() : open());
      backdrop.addEventListener('click', close);
    },

    /* ----------------------- Dropdowns ----------------------- */
    initDropdowns() {
      document.querySelectorAll('[data-dropdown]').forEach(btn => {
        const target = document.getElementById(btn.getAttribute('data-dropdown'));
        if (!target) return;
        btn.addEventListener('click', e => {
          e.stopPropagation();
          document.querySelectorAll('.profile-dd .menu.open, .notif-dd .panel.open').forEach(p => { if (p !== target) p.classList.remove('open'); });
          target.classList.toggle('open');
        });
      });
      document.addEventListener('click', () => {
        document.querySelectorAll('.profile-dd .menu.open, .notif-dd .panel.open').forEach(p => p.classList.remove('open'));
      });
    },

    /* ----------------------- Form validation ----------------------- */
    validateForm(form) {
      let valid = true;
      form.querySelectorAll('[required]').forEach(field => {
        const val = (field.value || '').trim();
        const fb = field.parentElement.querySelector('.invalid-feedback');
        if (!val) { field.classList.add('is-invalid'); if (fb) fb.textContent = 'This field is required.'; valid = false; }
        else { field.classList.remove('is-invalid'); }
      });
      form.querySelectorAll('input[type=email]').forEach(field => {
        if (field.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
          field.classList.add('is-invalid');
          const fb = field.parentElement.querySelector('.invalid-feedback') || (() => { const d = document.createElement('div'); d.className = 'invalid-feedback'; field.parentElement.appendChild(d); return d; })();
          fb.textContent = 'Enter a valid email address.'; valid = false;
        }
      });
      form.querySelectorAll('input[type=tel], input[data-phone]').forEach(field => {
        if (field.value && !/^[0-9\+\-\(\)\s]{7,20}$/.test(field.value)) { field.classList.add('is-invalid'); valid = false; }
      });
      return valid;
    },

    /* ----------------------- Auto close alerts ----------------------- */
    initAutoAlerts() {
      document.querySelectorAll('[data-auto-alert]').forEach(el => {
        const type = el.getAttribute('data-auto-alert'); const msg = el.textContent.trim();
        if (msg) { this.toast(msg, type); el.remove(); }
      });
    },

    /* ----------------------- Animated counters ----------------------- */
    animateCounters() {
      document.querySelectorAll('[data-counter]').forEach(el => {
        const target = parseFloat(el.getAttribute('data-counter'));
        const prefix = el.getAttribute('data-prefix') || '';
        const suffix = el.getAttribute('data-suffix') || '';
        const decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
        let start = 0; const dur = 1400; const t0 = performance.now();
        const tick = (now) => {
          const p = Math.min((now - t0) / dur, 1);
          const eased = 1 - Math.pow(1 - p, 3);
          const val = start + (target - start) * eased;
          el.textContent = prefix + val.toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + suffix;
          if (p < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
      });
    },

    /* ----------------------- Circular progress ----------------------- */
    animateCircular() {
      document.querySelectorAll('[data-circ]').forEach(el => {
        const pct = Math.min(100, Math.max(0, parseFloat(el.getAttribute('data-circ'))));
        const circle = el.querySelector('.bar');
        const text = el.querySelector('.pct b');
        if (!circle) return;
        const r = circle.getAttribute('r'); const C = 2 * Math.PI * r;
        circle.style.strokeDasharray = C;
        circle.style.strokeDashoffset = C;
        setTimeout(() => {
          circle.style.strokeDashoffset = C - (pct / 100) * C;
          if (text) { let c = 0; const step = pct / 40; const iv = setInterval(() => { c += step; if (c >= pct) { c = pct; clearInterval(iv); } text.textContent = Math.round(c) + '%'; }, 25); }
        }, 200);
      });
    },

    /* ----------------------- Scroll reveal ----------------------- */
    initReveal() {
      const els = document.querySelectorAll('[data-reveal]');
      if (!els.length) return;
      const io = new IntersectionObserver((entries) => {
        entries.forEach(en => { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
      }, { threshold: .12 });
      els.forEach(el => io.observe(el));
    },

    /* ----------------------- Back to top ----------------------- */
    initBackTop() {
      let btn = document.querySelector('.back-top');
      if (!btn) { btn = document.createElement('button'); btn.className = 'back-top'; btn.innerHTML = '<i class="fa-solid fa-arrow-up"></i>'; document.body.appendChild(btn); }
      btn.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
      window.addEventListener('scroll', () => btn.classList.toggle('show', window.scrollY > 400));
    },

    /* ----------------------- Keyboard shortcuts ----------------------- */
    initShortcuts() {
      document.addEventListener('keydown', (e) => {
        if (e.target.matches('input,textarea,select')) return;
        const map = { 'g d': 'dashboard.php', 'g m': 'admin/members.php', 'g p': 'admin/payments.php', 'g e': 'admin/events.php', 'g r': 'admin/reports.php' };
        if (e.key === 'k' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); const s = document.querySelector('.topbar .search input') || document.getElementById('globalSearch'); if (s) s.focus(); }
        if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
      });
    },

    /* ----------------------- Current date in topbar ----------------------- */
    initTopDate() {
      const el = document.getElementById('topDate');
      if (el) el.textContent = new Date().toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
    },

    /* ----------------------- Calendar widget ----------------------- */
    renderCalendar(elId, events = {}) {
      const el = document.getElementById(elId); if (!el) return;
      const now = new Date(); const y = now.getFullYear(); const m = now.getMonth();
      const first = new Date(y, m, 1).getDay(); const days = new Date(y, m + 1, 0).getDate();
      const monthName = now.toLocaleString('en-US', { month: 'long' });
      const dows = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
      let html = `<div class="cal-head"><span>${monthName} ${y}</span><span class="muted" style="font-size:.78rem;font-weight:500">${Object.keys(events).length} event(s)</span></div><div class="cal-grid">`;
      dows.forEach(d => html += `<div class="dow">${d}</div>`);
      for (let i = 0; i < first; i++) html += '<div class="day muted"></div>';
      for (let d = 1; d <= days; d++) {
        const ds = `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        const cls = ['day']; if (d === now.getDate()) cls.push('today'); if (events[ds]) cls.push('has-event');
        html += `<div class="${cls.join(' ')}" title="${events[ds] || ''}">${d}</div>`;
      }
      html += '</div>';
      el.innerHTML = html;
    },

    /* ----------------------- CSV export ----------------------- */
    exportCSV(filename, rows) {
      const csv = rows.map(r => r.map(c => { const s = String(c == null ? '' : c).replace(/"/g, '""'); return /[",\n]/.test(s) ? `"${s}"` : s; }).join(',')).join('\n');
      this.download(new Blob([csv], { type: 'text/csv;charset=utf-8;' }), filename);
    },
    download(blob, filename) {
      const url = URL.createObjectURL(blob); const a = document.createElement('a');
      a.href = url; a.download = filename; document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
    },

    /* ----------------------- Print ----------------------- */
    printArea(selector) {
      const node = document.querySelector(selector);
      const w = window.open('', '_blank');
      w.document.write('<html><head><title>Print</title><link rel="stylesheet" href="' + (window.APP_URL || '') + '/assets/css/style.css"></head><body style="background:#fff">');
      w.document.write(node ? node.outerHTML : document.body.innerHTML);
      w.document.write('</body></html>'); w.document.close(); w.focus();
      setTimeout(() => w.print(), 500);
    },

    /* ----------------------- Init ----------------------- */
    init() {
      this.initTheme();
      this.initSidebar();
      this.initDropdowns();
      this.initAutoAlerts();
      this.initReveal();
      this.initBackTop();
      this.initShortcuts();
      this.initTopDate();
      this.hideBoot();
      this.animateCounters();
      this.animateCircular();
      document.querySelectorAll('[data-modal-open]').forEach(btn => btn.addEventListener('click', () => this.openModal(btn.getAttribute('data-modal-open'))));
      document.querySelectorAll('[data-modal-close]').forEach(btn => btn.addEventListener('click', () => this.closeModal(btn.getAttribute('data-modal-close'))));
      document.querySelectorAll('.modal-overlay').forEach(m => m.addEventListener('click', e => { if (e.target === m) this.closeModal(m.id); }));
    }
  };

  window.App = App;
  document.addEventListener('DOMContentLoaded', () => App.init());

  /* ----------------------- Table pagination ----------------------- */
  window.paginateTable = function (tableId, perPage = 10) {
    const table = document.getElementById(tableId); if (!table) return;
    const tbody = table.querySelector('tbody'); if (!tbody) return;
    const rows = Array.from(tbody.querySelectorAll('tr'));
    let page = 1; const pages = Math.max(1, Math.ceil(rows.length / perPage));
    const pager = table.closest('.card')?.querySelector('.pager') || table.parentElement.parentElement.querySelector('.pager');
    function render() {
      rows.forEach((r, i) => r.style.display = (i >= (page - 1) * perPage && i < page * perPage) ? '' : 'none');
      if (!pager) return;
      const info = pager.querySelector('.info');
      if (info) info.textContent = `Showing ${Math.min((page - 1) * perPage + 1, rows.length)}-${Math.min(page * perPage, rows.length)} of ${rows.length}`;
      const pagesEl = pager.querySelector('.pages'); if (!pagesEl) return;
      pagesEl.innerHTML = '';
      const mk = (html, fn, dis, act) => { const b = document.createElement('button'); b.innerHTML = html; b.disabled = !!dis; if (act) b.classList.add('active'); b.onclick = fn; return b; };
      pagesEl.appendChild(mk('&laquo;', () => { page = Math.max(1, page - 1); render(); }, page === 1));
      for (let i = 1; i <= pages; i++) pagesEl.appendChild(mk(i, () => { page = i; render(); }, false, i === page));
      pagesEl.appendChild(mk('&raquo;', () => { page = Math.min(pages, page + 1); render(); }, page === pages));
    }
    render();
  };

  /* ----------------------- Table sorting ----------------------- */
  window.enableSort = function (tableId) {
    const table = document.getElementById(tableId); if (!table) return;
    const ths = table.querySelectorAll('thead th[data-sort]');
    ths.forEach((th, ci) => {
      th.addEventListener('click', () => {
        const tbody = table.querySelector('tbody'); const rows = Array.from(tbody.querySelectorAll('tr'));
        const asc = th.classList.contains('sorted-asc') ? false : true;
        ths.forEach(t => t.classList.remove('sorted-asc', 'sorted-desc'));
        th.classList.add(asc ? 'sorted-asc' : 'sorted-desc');
        rows.sort((a, b) => {
          const av = a.children[ci]?.textContent.trim() || ''; const bv = b.children[ci]?.textContent.trim() || '';
          const an = parseFloat(av.replace(/[^0-9.\-]/g, '')); const bn = parseFloat(bv.replace(/[^0-9.\-]/g, ''));
          if (!isNaN(an) && !isNaN(bn)) return asc ? an - bn : bn - an;
          return asc ? av.localeCompare(bv) : bv.localeCompare(av);
        });
        rows.forEach(r => tbody.appendChild(r));
        if (window.paginateTable) paginateTable(tableId);
      });
    });
  };
})();
