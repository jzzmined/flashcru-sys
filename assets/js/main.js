/* ============================================================
   FlashCru Emergency Response System — Main JS v4.0
   ============================================================ */

(function () {
  'use strict';

  /* ── Sidebar Collapse ──────────────────────────────────── */
  const sidebar = document.getElementById('fcSidebar');
  const mainContent = document.getElementById('fcMainContent');
  const toggleBtn = document.getElementById('sidebarToggle');

  function getSidebarState() {
    return localStorage.getItem('fc_sidebar_collapsed') === '1';
  }

  function applySidebarState(collapsed, animate) {
    if (!sidebar) return;
    if (collapsed) {
      sidebar.classList.add('collapsed');
      if (mainContent) mainContent.classList.add('sidebar-collapsed');
      if (toggleBtn) toggleBtn.textContent = '›';
    } else {
      sidebar.classList.remove('collapsed');
      if (mainContent) mainContent.classList.remove('sidebar-collapsed');
      if (toggleBtn) toggleBtn.textContent = '‹';
    }
  }

  // Init state
  applySidebarState(getSidebarState(), false);

  if (toggleBtn) {
    toggleBtn.addEventListener('click', function () {
      const next = !getSidebarState();
      localStorage.setItem('fc_sidebar_collapsed', next ? '1' : '0');
      applySidebarState(next, true);
    });
  }

  /* ── Live Clock ────────────────────────────────────────── */
  const clockEl = document.getElementById('fcClock');
  function tick() {
    if (!clockEl) return;
    const now = new Date();
    const d = now.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    const t = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    clockEl.textContent = d + ' · ' + t;
  }
  tick();
  setInterval(tick, 30000);

  /* ── Notification Panel ────────────────────────────────── */
  const notifToggle = document.getElementById('fcNotifToggle');
  const notifPanel  = document.getElementById('fcNotifPanel');

  window.toggleNotifPanel = function () {
    if (!notifPanel) return;
    notifPanel.classList.toggle('open');
  };

  window.clearNotifications = function () {
    const list = document.getElementById('fcNotifList');
    if (list) {
      list.innerHTML = '<div style="padding:28px;text-align:center;color:var(--subtle);font-size:13px;">No new notifications</div>';
    }
    const dot = document.getElementById('fcNotifDot');
    if (dot) dot.style.display = 'none';
  };

  document.addEventListener('click', function (e) {
    if (!notifPanel || !notifToggle) return;
    if (!notifPanel.contains(e.target) && !notifToggle.contains(e.target)) {
      notifPanel.classList.remove('open');
    }
  });

  /* ── Modal Helpers ─────────────────────────────────────── */
  window.openModal = function (id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('active');
  };

  window.closeModal = function (id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('active');
  };

  // Close modal on overlay click
  document.querySelectorAll('.modal-overlay').forEach(function (overlay) {
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) {
        overlay.classList.remove('active');
      }
    });
  });

  // Close modal on Escape
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay.active').forEach(function (m) {
        m.classList.remove('active');
      });
    }
  });

  /* ── Alert Auto-dismiss ────────────────────────────────── */
  document.querySelectorAll('.alert[data-autodismiss]').forEach(function (el) {
    setTimeout(function () {
      el.style.transition = 'opacity 0.4s';
      el.style.opacity = '0';
      setTimeout(function () { el.remove(); }, 400);
    }, 4000);
  });

  /* ── Incident Detail Modal ─────────────────────────────── */
  window.openIncidentDetail = function (r) {
    const sc = r.status_color || '#64748B';
    const sn = r.status_name  || 'Unknown';
    const titleEl = document.getElementById('incidentDetailTitle');
    const bodyEl  = document.getElementById('incidentDetailBody');
    if (titleEl) titleEl.textContent = 'Incident #' + r.id;
    if (bodyEl) {
      bodyEl.innerHTML = `
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
          <tr style="border-bottom:1px solid var(--bg);">
            <td style="padding:9px 0;color:var(--muted);width:38%;font-weight:500;">Type</td>
            <td style="padding:9px 0;font-weight:600;">${r.type_name || '—'}</td>
          </tr>
          <tr style="border-bottom:1px solid var(--bg);">
            <td style="padding:9px 0;color:var(--muted);">Barangay</td>
            <td style="padding:9px 0;">${r.barangay_name || '—'}</td>
          </tr>
          <tr style="border-bottom:1px solid var(--bg);">
            <td style="padding:9px 0;color:var(--muted);">Street / Landmark</td>
            <td style="padding:9px 0;">${r.street_landmark || '—'}</td>
          </tr>
          <tr style="border-bottom:1px solid var(--bg);">
            <td style="padding:9px 0;color:var(--muted);">Contact</td>
            <td style="padding:9px 0;">${r.contact_number || '—'}</td>
          </tr>
          <tr style="border-bottom:1px solid var(--bg);">
            <td style="padding:9px 0;color:var(--muted);">Assigned Team</td>
            <td style="padding:9px 0;font-weight:600;color:var(--dispatched);">${r.team_name || 'Not yet assigned'}</td>
          </tr>
          <tr style="border-bottom:1px solid var(--bg);">
            <td style="padding:9px 0;color:var(--muted);">Status</td>
            <td style="padding:9px 0;">
              <span class="status-badge" style="background:${sc}20;color:${sc};border:1px solid ${sc}40;">${sn}</span>
            </td>
          </tr>
          <tr>
            <td style="padding:9px 0;color:var(--muted);">Date Filed</td>
            <td style="padding:9px 0;font-size:12px;font-family:'JetBrains Mono',monospace;">${r.created_at || '—'}</td>
          </tr>
        </table>
        <div style="margin-top:16px;">
          <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Description</div>
          <div style="font-size:13px;color:var(--ink);line-height:1.7;background:var(--bg);padding:14px;border-radius:var(--radius);">${r.description || '—'}</div>
        </div>
      `;
    }
    openModal('incidentDetailModal');
  };

  /* ── My Reports Detail Modal ───────────────────────────── */
  window.openReportDetail = function (r) {
    window.openIncidentDetail(r); // reuse same logic
  };

  /* ── Table Sort (basic) ────────────────────────────────── */
  document.querySelectorAll('.data-table th[data-sort]').forEach(function (th) {
    th.style.cursor = 'pointer';
    th.addEventListener('click', function () {
      const table = th.closest('table');
      const col = Array.from(th.parentElement.children).indexOf(th);
      const asc = th.dataset.sortDir !== 'asc';
      th.dataset.sortDir = asc ? 'asc' : 'desc';
      const tbody = table.querySelector('tbody');
      const rows = Array.from(tbody.querySelectorAll('tr'));
      rows.sort(function (a, b) {
        const aText = (a.cells[col] && a.cells[col].textContent.trim()) || '';
        const bText = (b.cells[col] && b.cells[col].textContent.trim()) || '';
        return asc ? aText.localeCompare(bText) : bText.localeCompare(aText);
      });
      rows.forEach(function (row) { tbody.appendChild(row); });
    });
  });

  /* ── Confirm Delete ────────────────────────────────────── */
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (e) {
      if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
  });

})();