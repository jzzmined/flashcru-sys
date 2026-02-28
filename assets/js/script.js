/* =====================================================
   FLASHCRU — script.js
   ===================================================== */

/* ── Sidebar toggle ──────────────────────────────────── */
function fcOpenSidebar() {
    document.getElementById('fcSidebar').classList.add('open');
    document.getElementById('fcOverlay').classList.add('open');
}
function fcCloseSidebar() {
    document.getElementById('fcSidebar').classList.remove('open');
    document.getElementById('fcOverlay').classList.remove('open');
}
function fcToggleSidebar() {
    const sidebar = document.getElementById('fcSidebar');
    sidebar.classList.toggle('collapsed');
    document.body.classList.toggle('sidebar-collapsed');
    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
}
document.addEventListener('DOMContentLoaded', function () {
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        const sidebar = document.getElementById('fcSidebar');
        if (sidebar) {
            sidebar.classList.add('collapsed');
            document.body.classList.add('sidebar-collapsed');
        }
    }
});

/* ── Auto-dismiss alerts ─────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {

    // Auto dismiss
    document.querySelectorAll('.fc-alert').forEach(function (el) {
        setTimeout(function () {
            el.style.transition = 'opacity .5s';
            el.style.opacity = '0';
            setTimeout(function () { el.remove(); }, 520);
        }, 4500);
    });

    // Bootstrap tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });

    // Counter animation for stat values
    document.querySelectorAll('.fc-stat-val[data-target]').forEach(function (el) {
        var target = parseInt(el.getAttribute('data-target'), 10);
        var step   = Math.max(1, Math.ceil(target / 40));
        var cur    = 0;
        var timer  = setInterval(function () {
            cur += step;
            if (cur >= target) { el.textContent = target; clearInterval(timer); }
            else { el.textContent = cur; }
        }, 28);
    });
});

/* ── Confirm delete ──────────────────────────────────── */
function fcConfirm(msg) {
    return confirm(msg || 'Are you sure you want to delete this?');
}

/* ── Add team member row dynamically ─────────────────── */
function fcAddMember() {
    var container = document.getElementById('fcMemberRows');
    if (!container) return;
    var row = document.createElement('div');
    row.className = 'd-flex gap-2 mb-2 align-items-center';
    row.innerHTML =
        '<input type="text" name="member_name[]" class="fc-form-control" placeholder="Member Name" required>' +
        '<input type="text" name="member_role[]" class="fc-form-control" placeholder="Role (e.g. Paramedic)">' +
        '<button type="button" class="fc-icon-btn del flex-shrink-0" onclick="this.parentElement.remove()">' +
        '<i class="bi bi-trash"></i></button>';
    container.appendChild(row);
}