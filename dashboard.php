<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — FlashCru Emergency Response</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/main.css">
<style>
  /* ── Dashboard-specific overrides ── */
  :root {
    --critical: #DC2626;
    --active:   #D97706;
    --pending:  #7C3AED;
    --resolved: #16A34A;
    --fire:     #EA580C;
    --medical:  #0891B2;
    --rescue:   #1D4ED8;
    --accident: #7C3AED;
  }

  body {
    background: #F5F7FA;
    color: #0F172A;
    display: flex;
  }

  /* Sidebar overrides for standalone page */
  .sidebar {
    width: 240px;
    min-height: 100vh;
    background: #0F172A;
    border-right: none;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    position: fixed;
    top: 0; left: 0;
    height: 100%;
    z-index: 100;
    box-shadow: 2px 0 12px rgba(0,0,0,0.12);
  }

  .logo {
    padding: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .logo-icon {
    width: 36px; height: 36px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    background: #DC2626;
  }

  .logo-icon img { width: 100%; height: 100%; object-fit: contain; }
  .logo-icon-text { font-size: 18px; }

  .logo-text { font-weight: 800; font-size: 16px; color: #FFFFFF; line-height: 1.2; }
  .logo-sub  { font-size: 10px; color: rgba(255,255,255,0.4); font-weight: 400; letter-spacing: 0.06em; }

  .nav { padding: 10px 12px; flex: 1; }

  .nav-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    cursor: pointer;
    color: rgba(255,255,255,0.55);
    font-size: 13.5px;
    font-weight: 500;
    transition: background 0.15s, color 0.15s;
    margin-bottom: 2px;
    user-select: none;
  }

  .nav-item:hover { background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.85); }
  .nav-item.active { background: rgba(29,78,216,0.35); color: #FFFFFF; font-weight: 600; }

  .nav-badge {
    margin-left: auto;
    background: #DC2626;
    color: white;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 999px;
  }

  .sidebar-bottom {
    padding: 14px 12px;
    border-top: 1px solid rgba(255,255,255,0.07);
  }

  .user-card {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.15s;
  }
  .user-card:hover { background: rgba(255,255,255,0.06); }

  .user-avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, #3B82F6, #7C3AED);
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 800;
    color: white;
    flex-shrink: 0;
  }

  .user-name { font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.85); }
  .user-role { font-size: 11px; color: rgba(255,255,255,0.4); }

  /* Main */
  .main {
    margin-left: 240px;
    flex: 1;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: #F5F7FA;
  }

  /* Topbar */
  .topbar {
    background: #FFFFFF;
    border-bottom: 1px solid #E2E8F0;
    padding: 0 28px;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 50;
    box-shadow: 0 1px 3px rgba(15,23,42,0.05);
  }

  .topbar-left { display: flex; flex-direction: column; }
  .page-title  { font-size: 15px; font-weight: 700; color: #0F172A; }
  .breadcrumb  { font-size: 11px; color: #94A3B8; margin-top: 1px; }

  .topbar-right {
    display: flex; align-items: center; gap: 14px;
    font-size: 12px; color: #64748B;
  }

  /* Tab Nav */
  .tabs-nav {
    background: #FFFFFF;
    border-bottom: 1px solid #E2E8F0;
    padding: 0 28px;
    display: flex;
  }

  .tab-btn {
    padding: 14px 20px;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    font-weight: 500;
    color: #94A3B8;
    background: none;
    border: none;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: color 0.15s, border-color 0.15s;
    display: flex; align-items: center; gap: 7px;
    white-space: nowrap;
  }

  .tab-btn:hover { color: #0F172A; }
  .tab-btn.active { color: #1D4ED8; border-bottom-color: #1D4ED8; font-weight: 600; }

  /* Content */
  .content { padding: 28px; flex: 1; }

  .tab-panel { display: none; }
  .tab-panel.active { display: block; }

  /* Greeting */
  .greeting { margin-bottom: 24px; }
  .greeting h1 {
    font-size: 22px; font-weight: 800;
    color: #0F172A;
    display: flex; align-items: center; gap: 8px;
    letter-spacing: -0.3px;
  }
  .greeting p { color: #64748B; font-size: 13px; margin-top: 4px; }

  /* KPI cards grid */
  .cards-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 16px;
    margin-bottom: 24px;
  }

  .stat-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 20px 22px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15,23,42,0.05);
    transition: box-shadow 0.2s, transform 0.2s;
  }

  .stat-card:hover {
    box-shadow: 0 4px 16px rgba(15,23,42,0.09);
    transform: translateY(-1px);
  }

  .stat-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    border-radius: 14px 14px 0 0;
  }

  .stat-card.critical::before  { background: #DC2626; }
  .stat-card.active-c::before  { background: #D97706; }
  .stat-card.pending::before   { background: #7C3AED; }
  .stat-card.resolved::before  { background: #16A34A; }
  .stat-card.teams::before     { background: #2563EB; }
  .stat-card.total::before     { background: #64748B; }
  .stat-card.today::before     { background: #0891B2; }
  .stat-card.tteams::before    { background: #7C3AED; }

  .card-icon-box {
    width: 36px; height: 36px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px;
    margin-bottom: 14px;
  }

  .card-icon-box.red    { background: #FEF2F2; }
  .card-icon-box.amber  { background: #FFFBEB; }
  .card-icon-box.purple { background: #F5F3FF; }
  .card-icon-box.green  { background: #F0FDF4; }
  .card-icon-box.blue   { background: #EFF6FF; }
  .card-icon-box.slate  { background: #F1F5F9; }
  .card-icon-box.cyan   { background: #ECFEFF; }

  .card-label {
    font-size: 11px;
    font-weight: 600;
    color: #94A3B8;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    margin-bottom: 6px;
  }

  .card-value {
    font-size: 34px;
    font-weight: 800;
    line-height: 1;
    font-family: 'Inter', sans-serif;
  }

  .card-value.critical { color: #DC2626; }
  .card-value.active-c { color: #D97706; }
  .card-value.pending  { color: #7C3AED; }
  .card-value.resolved { color: #16A34A; }
  .card-value.teams    { color: #2563EB; }
  .card-value.total    { color: #0F172A; }
  .card-value.today    { color: #0891B2; }
  .card-value.tteams   { color: #7C3AED; }

  /* Quick Nav card */
  .quick-nav {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 28px;
    text-align: center;
    box-shadow: 0 1px 3px rgba(15,23,42,0.05);
    margin-top: 4px;
  }

  .quick-nav h3 { font-size: 16px; font-weight: 700; color: #0F172A; margin-bottom: 6px; }
  .quick-nav p  { font-size: 13px; color: #64748B; margin-bottom: 20px; }

  .quick-nav-btns {
    display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;
  }

  .qbtn {
    padding: 9px 20px;
    border-radius: 8px;
    font-family: 'Inter', sans-serif;
    font-weight: 600;
    font-size: 13px;
    cursor: pointer;
    border: 1.5px solid;
    transition: transform 0.1s, box-shadow 0.15s;
  }
  .qbtn:hover { transform: translateY(-1px); }

  .qbtn-blue   { background: #EFF6FF; color: #1D4ED8; border-color: #BFDBFE; }
  .qbtn-green  { background: #F0FDF4; color: #16A34A; border-color: #BBF7D0; }
  .qbtn-purple { background: #F5F3FF; color: #7C3AED; border-color: #DDD6FE; }

  /* Section header */
  .section-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px;
  }

  .section-title {
    font-size: 15px; font-weight: 700; color: #0F172A;
    display: flex; align-items: center; gap: 8px;
  }

  .view-all {
    font-size: 12px; color: #1D4ED8; font-weight: 600;
    background: none; border: none; cursor: pointer;
    font-family: 'Inter', sans-serif; padding: 0;
  }
  .view-all:hover { text-decoration: underline; }

  /* Incidents table */
  .table-wrap {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15,23,42,0.05);
  }

  .table-wrap table { width: 100%; border-collapse: collapse; }

  .table-wrap thead th {
    padding: 11px 16px;
    text-align: left;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.07em;
    color: #94A3B8;
    background: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
    white-space: nowrap;
  }

  .table-wrap tbody tr {
    border-bottom: 1px solid #F0F4F8;
    transition: background 0.12s;
    cursor: pointer;
  }

  .table-wrap tbody tr:last-child { border-bottom: none; }
  .table-wrap tbody tr:hover { background: #F8FAFC; }

  .table-wrap tbody td {
    padding: 12px 16px;
    font-size: 13px;
    color: #0F172A;
    vertical-align: middle;
  }

  .incident-id        { font-size: 11px; font-weight: 600; color: #94A3B8; }
  .incident-title     { font-weight: 600; font-size: 13px; color: #0F172A; line-height: 1.4; }
  .incident-addr      { font-size: 11px; color: #94A3B8; margin-top: 2px; }

  /* Type badges */
  .type-badge {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: 11px; font-weight: 600;
    padding: 3px 9px; border-radius: 6px;
  }
  .type-badge.fire     { background: #FFF7ED; color: #C2410C; }
  .type-badge.medical  { background: #ECFEFF; color: #0891B2; }
  .type-badge.rescue   { background: #EFF6FF; color: #1D4ED8; }
  .type-badge.accident { background: #F5F3FF; color: #7C3AED; }

  /* Status badges */
  .status-badge {
    display: inline-block;
    padding: 3px 9px;
    border-radius: 999px;
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.04em;
  }
  .status-badge.CRITICAL { background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; }
  .status-badge.ACTIVE   { background: #EFF6FF; color: #1D4ED8; border: 1px solid #BFDBFE; }
  .status-badge.PENDING  { background: #FFFBEB; color: #D97706; border: 1px solid #FDE68A; }
  .status-badge.RESOLVED { background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; }

  /* Priority */
  .priority-badge { font-size: 11px; font-weight: 700; }
  .priority-badge.CRITICAL { color: #DC2626; }
  .priority-badge.HIGH     { color: #D97706; }
  .priority-badge.MEDIUM   { color: #CA8A04; }
  .priority-badge.LOW      { color: #94A3B8; }

  .team-text       { font-size: 12px; font-weight: 600; color: #0F172A; }
  .team-unassigned { font-size: 12px; color: #94A3B8; font-style: italic; }

  /* Teams grid */
  .teams-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
  }

  .team-card {
    background: #FFFFFF;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(15,23,42,0.05);
    transition: box-shadow 0.2s, transform 0.2s;
  }

  .team-card:hover {
    box-shadow: 0 4px 16px rgba(15,23,42,0.09);
    transform: translateY(-2px);
  }

  .team-card-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 16px;
  }

  .team-name-row { display: flex; align-items: center; gap: 10px; }

  .team-icon {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 19px;
    border: 1px solid #E2E8F0;
  }

  .team-icon.fire    { background: #FFF7ED; }
  .team-icon.medical { background: #ECFEFF; }
  .team-icon.rescue  { background: #EFF6FF; }
  .team-icon.police  { background: #F5F3FF; }

  .team-card-name { font-weight: 700; font-size: 14px; color: #0F172A; }
  .team-card-type { font-size: 11px; color: #94A3B8; }

  .avail-pill {
    font-size: 10px; font-weight: 700;
    padding: 3px 10px;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
  }

  .avail-pill.available { background: #F0FDF4; color: #16A34A; border: 1px solid #BBF7D0; }
  .avail-pill.on-call   { background: #FFFBEB; color: #D97706; border: 1px solid #FDE68A; }
  .avail-pill.busy      { background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; }

  .team-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    border-top: 1px solid #F0F4F8;
    padding-top: 14px;
  }

  .team-stat-label {
    font-size: 10px; color: #94A3B8;
    margin-bottom: 3px;
    text-transform: uppercase; letter-spacing: 0.06em;
  }

  .team-stat-val {
    font-size: 20px; font-weight: 800;
    font-family: 'Inter', sans-serif;
  }

  /* Map wrapper */
  .map-wrapper {
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #E2E8F0;
    height: 500px;
    box-shadow: 0 1px 3px rgba(15,23,42,0.05);
  }

  .map-wrapper iframe {
    width: 100%; height: 100%;
    border: none; display: block;
  }

  /* Map legend bar */
  .map-legend-bar {
    display: flex; gap: 16px; align-items: center;
    font-size: 12px; font-weight: 500; color: #475569;
  }

  .map-legend-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 5px;
  }

  @media (max-width: 1200px) {
    .cards-grid  { grid-template-columns: repeat(2, 1fr); }
    .teams-grid  { grid-template-columns: repeat(2, 1fr); }
  }

  @media (max-width: 700px) {
    .cards-grid { grid-template-columns: 1fr; }
    .teams-grid { grid-template-columns: 1fr; }
  }
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="logo">
    <div class="logo-icon">
      <img src="fc-logo.jpg" alt="FC" onerror="this.parentElement.innerHTML='<span class=\'logo-icon-text\'>⚡</span>'">
    </div>
    <div>
      <div class="logo-text">FlashCru</div>
      <div class="logo-sub">Emergency Response</div>
    </div>
  </div>

  <nav class="nav">
    <div class="nav-item active" onclick="setNav(this)">
      <span>▪</span> Dashboard
    </div>
    <div class="nav-item" onclick="setNav(this)">
      <span>🔔</span> Incidents
      <span class="nav-badge">6</span>
    </div>
    <div class="nav-item" onclick="setNav(this)">
      <span>👥</span> Teams
    </div>
    <div class="nav-item" onclick="setNav(this)">
      <span>📊</span> Reports
    </div>
    <div class="nav-item" onclick="setNav(this)">
      <span>⚙</span> Settings
    </div>
  </nav>

  <div class="sidebar-bottom">
    <div class="user-card">
      <div class="user-avatar">SA</div>
      <div>
        <div class="user-name">System Admin</div>
        <div class="user-role">Administrator</div>
      </div>
    </div>
  </div>
</aside>

<!-- MAIN -->
<main class="main">

  <!-- Topbar -->
  <header class="topbar">
    <div class="topbar-left">
      <div class="page-title">Dashboard</div>
      <div class="breadcrumb">FlashCru / Dashboard</div>
    </div>
    <div class="topbar-right">
      <span id="clock" style="font-weight:500;"></span>
      <div style="width:34px;height:34px;border-radius:8px;border:1px solid #E2E8F0;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:16px;color:#64748B;">🔔</div>
      <div class="user-avatar" style="width:34px;height:34px;font-size:12px;">SA</div>
    </div>
  </header>

  <!-- Tabs -->
  <div class="tabs-nav">
    <button class="tab-btn active" onclick="switchTab('dashboard', this)">▪ Overview</button>
    <button class="tab-btn" onclick="switchTab('incidents', this)">🔴 Recent Incidents</button>
    <button class="tab-btn" onclick="switchTab('map', this)">🗺 Live Map</button>
    <button class="tab-btn" onclick="switchTab('teams', this)">👥 Team Status</button>
  </div>

  <div class="content">

    <!-- ====== OVERVIEW TAB ====== -->
    <div class="tab-panel active" id="tab-dashboard">

      <div class="greeting">
        <h1 id="greeting-text">Good Day, System! ⚡</h1>
        <p>Here's what's happening with FlashCru right now. · <span style="color:#1D4ED8;font-weight:600;">Wednesday, February 18, 2026</span></p>
      </div>

      <div class="cards-grid">
        <div class="stat-card critical">
          <div class="card-icon-box red">🚨</div>
          <div class="card-label">Critical</div>
          <div class="card-value critical">2</div>
        </div>
        <div class="stat-card active-c">
          <div class="card-icon-box amber">⚡</div>
          <div class="card-label">Active</div>
          <div class="card-value active-c">4</div>
        </div>
        <div class="stat-card pending">
          <div class="card-icon-box purple">⏳</div>
          <div class="card-label">Pending</div>
          <div class="card-value pending">2</div>
        </div>
        <div class="stat-card resolved">
          <div class="card-icon-box green">✅</div>
          <div class="card-label">Resolved</div>
          <div class="card-value resolved">4</div>
        </div>
        <div class="stat-card teams">
          <div class="card-icon-box blue">🚒</div>
          <div class="card-label">Teams Available</div>
          <div class="card-value teams">6/8</div>
        </div>
        <div class="stat-card total">
          <div class="card-icon-box slate">📋</div>
          <div class="card-label">Total Incidents</div>
          <div class="card-value total">12</div>
        </div>
        <div class="stat-card today">
          <div class="card-icon-box cyan">📅</div>
          <div class="card-label">Today</div>
          <div class="card-value today">0</div>
        </div>
        <div class="stat-card tteams">
          <div class="card-icon-box purple">🏢</div>
          <div class="card-label">Total Teams</div>
          <div class="card-value tteams">8</div>
        </div>
      </div>

      <div class="quick-nav">
        <div style="font-size:32px;margin-bottom:10px;">📊</div>
        <h3>Quick Navigation</h3>
        <p>Use the tabs above to view Recent Incidents, the Live Map, or Team Status.</p>
        <div class="quick-nav-btns">
          <button class="qbtn qbtn-blue" onclick="switchTab('incidents', document.querySelectorAll('.tab-btn')[1])">
            🔴 Recent Incidents
          </button>
          <button class="qbtn qbtn-green" onclick="switchTab('map', document.querySelectorAll('.tab-btn')[2])">
            🗺 Live Map
          </button>
          <button class="qbtn qbtn-purple" onclick="switchTab('teams', document.querySelectorAll('.tab-btn')[3])">
            👥 Team Status
          </button>
        </div>
      </div>

    </div>

    <!-- ====== INCIDENTS TAB ====== -->
    <div class="tab-panel" id="tab-incidents">
      <div class="section-header">
        <div class="section-title">🔴 Recent Incidents</div>
        <button class="view-all">View All</button>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Title & Location</th>
              <th>Type</th>
              <th>Status</th>
              <th>Priority</th>
              <th>Assigned Team</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td class="incident-id">#1</td>
              <td>
                <div class="incident-title">Multi-Story Building Fire</div>
                <div class="incident-addr">📍 J.P. Laurel Ave, Davao City</div>
              </td>
              <td><span class="type-badge fire">🔥 Fire</span></td>
              <td><span class="status-badge CRITICAL">CRITICAL</span></td>
              <td><span class="priority-badge CRITICAL">CRITICAL</span></td>
              <td><span class="team-text">Fire Squad Alpha</span></td>
            </tr>
            <tr>
              <td class="incident-id">#2</td>
              <td>
                <div class="incident-title">Cardiac Arrest — Male Adult</div>
                <div class="incident-addr">📍 Roxas Ave, Davao City</div>
              </td>
              <td><span class="type-badge medical">🏥 Medical</span></td>
              <td><span class="status-badge CRITICAL">CRITICAL</span></td>
              <td><span class="priority-badge CRITICAL">CRITICAL</span></td>
              <td><span class="team-text">Medical Unit 1</span></td>
            </tr>
            <tr>
              <td class="incident-id">#3</td>
              <td>
                <div class="incident-title">Multi-Vehicle Collision</div>
                <div class="incident-addr">📍 CM Recto Ave, Davao City</div>
              </td>
              <td><span class="type-badge accident">🚗 Accident</span></td>
              <td><span class="status-badge ACTIVE">ACTIVE</span></td>
              <td><span class="priority-badge HIGH">HIGH</span></td>
              <td><span class="team-text">Rescue Team Bravo</span></td>
            </tr>
            <tr>
              <td class="incident-id">#4</td>
              <td>
                <div class="incident-title">Flood Rescue Operation</div>
                <div class="incident-addr">📍 Davao Overland Terminal Area</div>
              </td>
              <td><span class="type-badge rescue">🚁 Rescue</span></td>
              <td><span class="status-badge ACTIVE">ACTIVE</span></td>
              <td><span class="priority-badge HIGH">HIGH</span></td>
              <td><span class="team-text">Rescue Team Bravo</span></td>
            </tr>
            <tr>
              <td class="incident-id">#5</td>
              <td>
                <div class="incident-title">Child Respiratory Emergency</div>
                <div class="incident-addr">📍 Quirino Ave, Davao City</div>
              </td>
              <td><span class="type-badge medical">🏥 Medical</span></td>
              <td><span class="status-badge ACTIVE">ACTIVE</span></td>
              <td><span class="priority-badge HIGH">HIGH</span></td>
              <td><span class="team-text">Medical Unit 2</span></td>
            </tr>
            <tr>
              <td class="incident-id">#6</td>
              <td>
                <div class="incident-title">Commercial Building Electrical Fire</div>
                <div class="incident-addr">📍 McArthur Highway, Davao City</div>
              </td>
              <td><span class="type-badge fire">🔥 Fire</span></td>
              <td><span class="status-badge ACTIVE">ACTIVE</span></td>
              <td><span class="priority-badge MEDIUM">MEDIUM</span></td>
              <td><span class="team-text">Fire Squad Beta</span></td>
            </tr>
            <tr>
              <td class="incident-id">#7</td>
              <td>
                <div class="incident-title">Motorcycle Accident</div>
                <div class="incident-addr">📍 Marina Town Square, Davao</div>
              </td>
              <td><span class="type-badge accident">🚗 Accident</span></td>
              <td><span class="status-badge PENDING">PENDING</span></td>
              <td><span class="priority-badge MEDIUM">MEDIUM</span></td>
              <td><span class="team-unassigned">Unassigned</span></td>
            </tr>
            <tr>
              <td class="incident-id">#8</td>
              <td>
                <div class="incident-title">Elderly Fall Injury</div>
                <div class="incident-addr">📍 Bolton St, Davao City</div>
              </td>
              <td><span class="type-badge medical">🏥 Medical</span></td>
              <td><span class="status-badge PENDING">PENDING</span></td>
              <td><span class="priority-badge LOW">LOW</span></td>
              <td><span class="team-unassigned">Unassigned</span></td>
            </tr>
            <tr>
              <td class="incident-id">#9</td>
              <td>
                <div class="incident-title">Kitchen Fire — Residential</div>
                <div class="incident-addr">📍 Bajada Area, Davao City</div>
              </td>
              <td><span class="type-badge fire">🔥 Fire</span></td>
              <td><span class="status-badge RESOLVED">RESOLVED</span></td>
              <td><span class="priority-badge LOW">LOW</span></td>
              <td><span class="team-text">Fire Squad Alpha</span></td>
            </tr>
            <tr>
              <td class="incident-id">#10</td>
              <td>
                <div class="incident-title">Minor Traffic Injury</div>
                <div class="incident-addr">📍 Lanang Business Park</div>
              </td>
              <td><span class="type-badge medical">🏥 Medical</span></td>
              <td><span class="status-badge RESOLVED">RESOLVED</span></td>
              <td><span class="priority-badge LOW">LOW</span></td>
              <td><span class="team-text">Medical Unit 1</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ====== MAP TAB ====== -->
    <div class="tab-panel" id="tab-map">
      <div class="section-header">
        <div class="section-title">🗺 Live Incident Map</div>
        <div class="map-legend-bar">
          <span><span class="map-legend-dot" style="background:#DC2626;"></span>Critical</span>
          <span><span class="map-legend-dot" style="background:#D97706;"></span>Active</span>
          <span><span class="map-legend-dot" style="background:#7C3AED;"></span>Pending</span>
          <span><span class="map-legend-dot" style="background:#16A34A;"></span>Resolved</span>
        </div>
      </div>

      <div class="map-wrapper">
        <iframe
          src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d31478.12!2d125.6128!3d7.0731!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1sen!2sph!4v1"
          allowfullscreen=""
          loading="lazy"
          referrerpolicy="no-referrer-when-downgrade">
        </iframe>
      </div>
    </div>

    <!-- ====== TEAMS TAB ====== -->
    <div class="tab-panel" id="tab-teams">
      <div class="section-header">
        <div class="section-title">👥 Team Status</div>
        <button class="view-all">View All</button>
      </div>

      <div class="teams-grid">

        <div class="team-card">
          <div class="team-card-header">
            <div class="team-name-row">
              <div class="team-icon fire">🔥</div>
              <div>
                <div class="team-card-name">Fire Squad Alpha</div>
                <div class="team-card-type">Fire Response</div>
              </div>
            </div>
            <span class="avail-pill available">Available</span>
          </div>
          <div class="team-stats">
            <div><div class="team-stat-label">Members</div><div class="team-stat-val" style="color:#1D4ED8">6</div></div>
            <div><div class="team-stat-label">On Scene</div><div class="team-stat-val" style="color:#D97706">2</div></div>
            <div><div class="team-stat-label">Resolved</div><div class="team-stat-val" style="color:#16A34A">12</div></div>
          </div>
        </div>

        <div class="team-card">
          <div class="team-card-header">
            <div class="team-name-row">
              <div class="team-icon fire">🔥</div>
              <div>
                <div class="team-card-name">Fire Squad Beta</div>
                <div class="team-card-type">Fire Response</div>
              </div>
            </div>
            <span class="avail-pill available">Available</span>
          </div>
          <div class="team-stats">
            <div><div class="team-stat-label">Members</div><div class="team-stat-val" style="color:#1D4ED8">5</div></div>
            <div><div class="team-stat-label">On Scene</div><div class="team-stat-val" style="color:#D97706">1</div></div>
            <div><div class="team-stat-label">Resolved</div><div class="team-stat-val" style="color:#16A34A">8</div></div>
          </div>
        </div>

        <div class="team-card">
          <div class="team-card-header">
            <div class="team-name-row">
              <div class="team-icon medical">🏥</div>
              <div>
                <div class="team-card-name">Medical Unit 1</div>
                <div class="team-card-type">Emergency Medical</div>
              </div>
            </div>
            <span class="avail-pill available">Available</span>
          </div>
          <div class="team-stats">
            <div><div class="team-stat-label">Members</div><div class="team-stat-val" style="color:#1D4ED8">4</div></div>
            <div><div class="team-stat-label">On Scene</div><div class="team-stat-val" style="color:#D97706">1</div></div>
            <div><div class="team-stat-label">Resolved</div><div class="team-stat-val" style="color:#16A34A">15</div></div>
          </div>
        </div>

        <div class="team-card">
          <div class="team-card-header">
            <div class="team-name-row">
              <div class="team-icon medical">🏥</div>
              <div>
                <div class="team-card-name">Medical Unit 2</div>
                <div class="team-card-type">Emergency Medical</div>
              </div>
            </div>
            <span class="avail-pill on-call">On Call</span>
          </div>
          <div class="team-stats">
            <div><div class="team-stat-label">Members</div><div class="team-stat-val" style="color:#1D4ED8">4</div></div>
            <div><div class="team-stat-label">On Scene</div><div class="team-stat-val" style="color:#D97706">1</div></div>
            <div><div class="team-stat-label">Resolved</div><div class="team-stat-val" style="color:#16A34A">9</div></div>
          </div>
        </div>

        <div class="team-card">
          <div class="team-card-header">
            <div class="team-name-row">
              <div class="team-icon medical">🏥</div>
              <div>
                <div class="team-card-name">Medical Unit 3</div>
                <div class="team-card-type">Emergency Medical</div>
              </div>
            </div>
            <span class="avail-pill available">Available</span>
          </div>
          <div class="team-stats">
            <div><div class="team-stat-label">Members</div><div class="team-stat-val" style="color:#1D4ED8">3</div></div>
            <div><div class="team-stat-label">On Scene</div><div class="team-stat-val" style="color:#D97706">0</div></div>
            <div><div class="team-stat-label">Resolved</div><div class="team-stat-val" style="color:#16A34A">6</div></div>
          </div>
        </div>

        <div class="team-card">
          <div class="team-card-header">
            <div class="team-name-row">
              <div class="team-icon police">🚓</div>
              <div>
                <div class="team-card-name">Police Team 1</div>
                <div class="team-card-type">Law Enforcement</div>
              </div>
            </div>
            <span class="avail-pill available">Available</span>
          </div>
          <div class="team-stats">
            <div><div class="team-stat-label">Members</div><div class="team-stat-val" style="color:#1D4ED8">8</div></div>
            <div><div class="team-stat-label">On Scene</div><div class="team-stat-val" style="color:#D97706">0</div></div>
            <div><div class="team-stat-label">Resolved</div><div class="team-stat-val" style="color:#16A34A">21</div></div>
          </div>
        </div>

        <div class="team-card">
          <div class="team-card-header">
            <div class="team-name-row">
              <div class="team-icon rescue">🚁</div>
              <div>
                <div class="team-card-name">Rescue Team Alpha</div>
                <div class="team-card-type">Search & Rescue</div>
              </div>
            </div>
            <span class="avail-pill available">Available</span>
          </div>
          <div class="team-stats">
            <div><div class="team-stat-label">Members</div><div class="team-stat-val" style="color:#1D4ED8">6</div></div>
            <div><div class="team-stat-label">On Scene</div><div class="team-stat-val" style="color:#D97706">0</div></div>
            <div><div class="team-stat-label">Resolved</div><div class="team-stat-val" style="color:#16A34A">7</div></div>
          </div>
        </div>

        <div class="team-card">
          <div class="team-card-header">
            <div class="team-name-row">
              <div class="team-icon rescue">🚁</div>
              <div>
                <div class="team-card-name">Rescue Team Bravo</div>
                <div class="team-card-type">Search & Rescue</div>
              </div>
            </div>
            <span class="avail-pill busy">On Scene</span>
          </div>
          <div class="team-stats">
            <div><div class="team-stat-label">Members</div><div class="team-stat-val" style="color:#1D4ED8">6</div></div>
            <div><div class="team-stat-label">On Scene</div><div class="team-stat-val" style="color:#D97706">2</div></div>
            <div><div class="team-stat-label">Resolved</div><div class="team-stat-val" style="color:#16A34A">5</div></div>
          </div>
        </div>

      </div>
    </div>

  </div><!-- /content -->
</main>

<script>
  function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tabId).classList.add('active');
    btn.classList.add('active');
  }

  function setNav(el) {
    document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
    el.classList.add('active');
  }

  function updateClock() {
    const now = new Date();
    const opts = { weekday: 'short', year: 'numeric', month: 'short', day: 'numeric' };
    const date = now.toLocaleDateString('en-US', opts);
    const time = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    const el = document.getElementById('clock');
    if (el) el.textContent = date + ' · ' + time;

    const hour = now.getHours();
    const greet = hour < 12 ? 'Good Morning' : hour < 17 ? 'Good Afternoon' : 'Good Evening';
    const gtEl = document.getElementById('greeting-text');
    if (gtEl) gtEl.textContent = greet + ', System! ⚡';
  }

  updateClock();
  setInterval(updateClock, 30000);
</script>
</body>
</html>