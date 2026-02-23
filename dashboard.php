<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — FlashCru Emergency Response</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/main.css">
<style>
  :root {
    --critical: #E53935;
    --active:   #E9A016;
    --pending:  #6C4FDB;
    --resolved: #2E9E6B;
  }

  body { background: var(--bg); color: var(--text-primary); display: flex; }

  /* Sidebar standalone styles */
  .sidebar { width: var(--sidebar-w); min-height: 100vh; background: var(--navy); border-right: none; display: flex; flex-direction: column; flex-shrink: 0; position: fixed; top: 0; left: 0; height: 100%; z-index: 100; box-shadow: 4px 0 24px rgba(26,35,64,0.18); }

  .logo { padding: 22px 20px 18px; border-bottom: 1px solid rgba(255,255,255,0.07); display: flex; align-items: center; gap: 12px; }
  .logo-icon { width: 40px; height: 40px; border-radius: 12px; overflow: hidden; flex-shrink: 0; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #E53935, #C62828); box-shadow: 0 4px 12px rgba(229,57,53,0.40); }
  .logo-icon img { width: 100%; height: 100%; object-fit: contain; }
  .logo-icon-text { font-size: 20px; }
  .logo-text { font-weight: 800; font-size: 17px; color: #FFFFFF; letter-spacing: -0.3px; }
  .logo-sub  { font-size: 10px; color: rgba(255,255,255,0.38); font-weight: 400; letter-spacing: 0.07em; margin-top: 1px; }

  .nav { padding: 14px 12px; flex: 1; }

  .nav-item { display: flex; align-items: center; gap: 11px; padding: 10px 12px; border-radius: 10px; cursor: pointer; color: rgba(255,255,255,0.50); font-size: 13.5px; font-weight: 500; transition: all 0.22s; margin-bottom: 2px; user-select: none; position: relative; text-decoration: none; }
  .nav-item:hover { background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.85); }
  .nav-item.active { background: linear-gradient(135deg, rgba(61,90,241,0.30), rgba(61,90,241,0.18)); color: #fff; font-weight: 600; box-shadow: inset 0 0 0 1px rgba(61,90,241,0.30); }
  .nav-item.active::before { content: ''; position: absolute; left: 0; top: 25%; bottom: 25%; width: 3px; background: var(--indigo); border-radius: 0 3px 3px 0; }

  .nav-badge { margin-left: auto; background: var(--red); color: white; font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 99px; animation: pulse-badge 2s ease-in-out infinite; }
  @keyframes pulse-badge { 0%,100%{box-shadow:0 0 0 0 rgba(229,57,53,0.4)} 50%{box-shadow:0 0 0 4px rgba(229,57,53,0)} }

  .sidebar-bottom { padding: 12px; border-top: 1px solid rgba(255,255,255,0.07); }
  .user-card { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; cursor: pointer; transition: background 0.22s; }
  .user-card:hover { background: rgba(255,255,255,0.06); }
  .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #3D5AF1, #6C4FDB); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; color: white; flex-shrink: 0; box-shadow: 0 3px 8px rgba(61,90,241,0.35); }
  .user-name { font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.85); }
  .user-role { font-size: 11px; color: rgba(255,255,255,0.38); margin-top: 1px; }
  .logout-btn { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border-radius: 10px; color: rgba(229,57,53,0.80); font-size: 13px; font-weight: 600; text-decoration: none; transition: all 0.22s; margin-top: 4px; }
  .logout-btn:hover { background: rgba(229,57,53,0.15); color: #ff6b6b; }

  /* Main */
  .main { margin-left: var(--sidebar-w); flex: 1; min-height: 100vh; display: flex; flex-direction: column; background: var(--bg); }

  /* Topbar */
  .topbar { background: var(--white); border-bottom: 1px solid var(--border); padding: 0 32px; height: 64px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; box-shadow: 0 2px 12px rgba(150,170,220,0.12); }
  .topbar-left { display: flex; flex-direction: column; }
  .page-title  { font-size: 15px; font-weight: 700; color: var(--text-primary); }
  .breadcrumb  { font-size: 11px; color: var(--text-muted); margin-top: 1px; }
  .topbar-right { display: flex; align-items: center; gap: 10px; font-size: 12px; color: var(--text-secondary); }

  #clock { font-weight: 500; padding: 6px 12px; background: var(--bg); border-radius: 10px; box-shadow: var(--neu-inset); font-size: 12px; }

  .notif-btn { width: 36px; height: 36px; border-radius: 10px; background: var(--bg); border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 16px; box-shadow: var(--neu-card); transition: all 0.22s; }
  .notif-btn:hover { box-shadow: var(--neu-hover); transform: translateY(-1px); }

  /* Tabs */
  .tabs-nav { background: var(--white); border-bottom: 1px solid var(--border); padding: 0 32px; display: flex; gap: 2px; box-shadow: 0 2px 8px rgba(150,170,220,0.07); }
  .tab-btn { padding: 16px 20px; font-family: 'DM Sans', sans-serif; font-size: 13px; font-weight: 500; color: var(--text-muted); background: none; border: none; cursor: pointer; border-bottom: 2.5px solid transparent; transition: all 0.22s; display: flex; align-items: center; gap: 7px; white-space: nowrap; }
  .tab-btn:hover { color: var(--text-primary); }
  .tab-btn.active { color: var(--indigo); border-bottom-color: var(--indigo); font-weight: 700; }

  /* Content area */
  .content { padding: 28px 32px; flex: 1; }
  .tab-panel { display: none; }
  .tab-panel.active { display: block; animation: fadeUp 0.25s ease; }
  @keyframes fadeUp { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }

  /* Greeting */
  .greeting { margin-bottom: 28px; }
  .greeting h1 { font-size: 24px; font-weight: 800; color: var(--text-primary); display: flex; align-items: center; gap: 8px; letter-spacing: -0.4px; }
  .greeting p  { color: var(--text-secondary); font-size: 13px; margin-top: 4px; }

  /* KPI grid */
  .cards-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 18px; margin-bottom: 28px; }

  .stat-card { background: var(--white); border-radius: var(--radius); padding: 22px 22px 20px; position: relative; overflow: hidden; box-shadow: var(--neu-card); border: 1px solid rgba(255,255,255,0.70); transition: all 0.22s; }
  .stat-card:hover { box-shadow: var(--neu-hover); transform: translateY(-2px); }
  .stat-card::after { content:''; position:absolute; top:0; left:0; right:0; height:3px; border-radius: var(--radius) var(--radius) 0 0; }
  .stat-card.critical::after { background: linear-gradient(90deg, #E53935, #FF5252); }
  .stat-card.active-c::after { background: linear-gradient(90deg, #E9A016, #FFB74D); }
  .stat-card.pending::after  { background: linear-gradient(90deg, #6C4FDB, #9575CD); }
  .stat-card.resolved::after { background: linear-gradient(90deg, #2E9E6B, #66BB6A); }
  .stat-card.teams::after    { background: linear-gradient(90deg, #2979D9, #64B5F6); }
  .stat-card.total::after    { background: linear-gradient(90deg, #5A6787, #8E9BBE); }
  .stat-card.today::after    { background: linear-gradient(90deg, #0BA5C4, #4DD0E1); }
  .stat-card.tteams::after   { background: linear-gradient(90deg, #6C4FDB, #CE93D8); }

  .card-icon-box { width: 40px; height: 40px; border-radius: 11px; display: flex; align-items: center; justify-content: center; font-size: 18px; margin-bottom: 16px; box-shadow: var(--neu-inset); }
  .card-icon-box.red    { background: var(--red-light); }
  .card-icon-box.amber  { background: var(--amber-light); }
  .card-icon-box.purple { background: var(--purple-light); }
  .card-icon-box.green  { background: var(--green-light); }
  .card-icon-box.blue   { background: var(--blue-light); }
  .card-icon-box.slate  { background: var(--bg-mid); }
  .card-icon-box.cyan   { background: var(--cyan-light); }

  .card-label { font-size: 10.5px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 6px; }
  .card-value { font-size: 36px; font-weight: 800; line-height: 1; }
  .card-value.critical { color: var(--red); }
  .card-value.active-c { color: var(--amber); }
  .card-value.pending  { color: var(--purple); }
  .card-value.resolved { color: var(--green); }
  .card-value.teams    { color: var(--blue); }
  .card-value.total    { color: var(--text-primary); }
  .card-value.today    { color: var(--cyan); }
  .card-value.tteams   { color: var(--purple); }

  /* Quick nav */
  .quick-nav { background: var(--white); border-radius: var(--radius); padding: 36px 32px; text-align: center; box-shadow: var(--neu-card); border: 1px solid rgba(255,255,255,0.65); margin-top: 4px; }
  .quick-nav h3 { font-size: 17px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px; }
  .quick-nav p  { font-size: 13px; color: var(--text-secondary); margin-bottom: 24px; }
  .quick-nav-btns { display: flex; justify-content: center; gap: 12px; flex-wrap: wrap; }
  .qbtn { padding: 10px 22px; border-radius: 10px; font-family: 'DM Sans', sans-serif; font-weight: 600; font-size: 13px; cursor: pointer; border: 1.5px solid; transition: all 0.22s; box-shadow: var(--neu-card); }
  .qbtn:hover { transform: translateY(-2px); box-shadow: var(--neu-hover); }
  .qbtn-blue   { background: var(--blue-light);   color: var(--blue);   border-color: rgba(41,121,217,0.20); }
  .qbtn-green  { background: var(--green-light);  color: var(--green);  border-color: rgba(46,158,107,0.20); }
  .qbtn-purple { background: var(--purple-light); color: var(--purple); border-color: rgba(108,79,219,0.20); }

  /* Section header */
  .section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
  .section-title  { font-size: 15px; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
  .view-all { font-size: 12px; color: var(--indigo); font-weight: 600; background: var(--indigo-light); border: none; cursor: pointer; font-family: 'DM Sans', sans-serif; padding: 6px 14px; border-radius: 8px; transition: all 0.22s; text-decoration: none; }
  .view-all:hover { background: var(--indigo); color: white; }

  /* Table */
  .table-wrap { background: var(--white); border-radius: var(--radius); overflow: hidden; box-shadow: var(--neu-card); border: 1px solid rgba(255,255,255,0.65); }
  .table-wrap table { width: 100%; border-collapse: collapse; }
  .table-wrap thead th { padding: 12px 18px; text-align: left; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); background: var(--bg); border-bottom: 1px solid var(--border); white-space: nowrap; }
  .table-wrap tbody tr { border-bottom: 1px solid rgba(200,215,240,0.18); transition: background 0.15s; cursor: pointer; }
  .table-wrap tbody tr:last-child { border-bottom: none; }
  .table-wrap tbody tr:hover { background: rgba(238,241,255,0.50); }
  .table-wrap tbody td { padding: 13px 18px; font-size: 13px; color: var(--text-primary); vertical-align: middle; }

  .incident-id    { font-size: 11px; font-weight: 600; color: var(--text-muted); }
  .incident-title { font-weight: 600; font-size: 13px; color: var(--text-primary); line-height: 1.4; }
  .incident-addr  { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

  /* Type badges */
  .type-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 11px; font-weight: 600; padding: 4px 10px; border-radius: 8px; }
  .type-badge.fire     { background: #FFF3ED; color: #C2410C; }
  .type-badge.medical  { background: var(--cyan-light); color: var(--cyan); }
  .type-badge.rescue   { background: var(--blue-light); color: var(--blue); }
  .type-badge.accident { background: var(--purple-light); color: var(--purple); }

  /* Status badges */
  .status-badge { display: inline-block; padding: 4px 10px; border-radius: 99px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
  .status-badge.CRITICAL { background: var(--red-light);    color: var(--red);    box-shadow: inset 0 0 0 1px rgba(229,57,53,0.20); }
  .status-badge.ACTIVE   { background: var(--blue-light);   color: var(--blue);   box-shadow: inset 0 0 0 1px rgba(41,121,217,0.20); }
  .status-badge.PENDING  { background: var(--amber-light);  color: var(--amber);  box-shadow: inset 0 0 0 1px rgba(233,160,22,0.20); }
  .status-badge.RESOLVED { background: var(--green-light);  color: var(--green);  box-shadow: inset 0 0 0 1px rgba(46,158,107,0.20); }

  .priority-badge { font-size: 11px; font-weight: 700; }
  .priority-badge.CRITICAL { color: var(--red); }
  .priority-badge.HIGH     { color: var(--amber); }
  .priority-badge.MEDIUM   { color: var(--cyan); }
  .priority-badge.LOW      { color: var(--text-muted); }

  .team-text       { font-size: 12px; font-weight: 600; color: var(--text-primary); }
  .team-unassigned { font-size: 12px; color: var(--text-muted); font-style: italic; }

  /* Dashboard teams grid */
  .teams-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }

  .team-card { background: var(--white); border: 1px solid rgba(255,255,255,0.65); border-radius: var(--radius); padding: 20px; box-shadow: var(--neu-card); transition: all 0.22s; }
  .team-card:hover { box-shadow: var(--neu-hover); transform: translateY(-2px); }

  .team-card-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
  .team-name-row { display: flex; align-items: center; gap: 10px; }

  .team-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 19px; box-shadow: var(--neu-inset); }
  .team-icon.fire    { background: #FFF3ED; }
  .team-icon.medical { background: var(--cyan-light); }
  .team-icon.rescue  { background: var(--blue-light); }
  .team-icon.police  { background: var(--purple-light); }

  .team-card-name { font-weight: 700; font-size: 14px; color: var(--text-primary); }
  .team-card-type { font-size: 11px; color: var(--text-muted); margin-top: 2px; }

  .avail-pill { font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 99px; text-transform: uppercase; letter-spacing: 0.04em; }
  .avail-pill.available { background: var(--green-light); color: var(--green); box-shadow: inset 0 0 0 1px rgba(46,158,107,0.20); }
  .avail-pill.on-call   { background: var(--amber-light); color: var(--amber); box-shadow: inset 0 0 0 1px rgba(233,160,22,0.20); }
  .avail-pill.busy      { background: var(--red-light);   color: var(--red);   box-shadow: inset 0 0 0 1px rgba(229,57,53,0.20); }

  .team-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; border-top: 1px solid var(--border); padding-top: 14px; }
  .team-stat-label { font-size: 10px; color: var(--text-muted); margin-bottom: 3px; text-transform: uppercase; letter-spacing: 0.06em; }
  .team-stat-val   { font-size: 22px; font-weight: 800; }

  /* Map */
  .map-wrapper { border-radius: var(--radius); overflow: hidden; border: 1px solid var(--border); height: 500px; box-shadow: var(--neu-card); }
  .map-wrapper iframe { width: 100%; height: 100%; border: none; display: block; }

  .map-legend-bar { display: flex; gap: 14px; align-items: center; font-size: 12px; font-weight: 500; color: var(--text-secondary); }
  .map-legend-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }

  /* Responsive */
  @media (max-width: 1280px) { .cards-grid { grid-template-columns: repeat(2, 1fr); } .teams-grid { grid-template-columns: repeat(2, 1fr); } }
  @media (max-width: 700px)  { .cards-grid { grid-template-columns: 1fr; } .teams-grid { grid-template-columns: 1fr; } }
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
    <a class="nav-item active" href="dashboard.php">
      <span style="font-size:15px;">⬛</span> Dashboard
    </a>
    <a class="nav-item" href="incidents.php">
      <span style="font-size:15px;">🔔</span> Incidents
      <span class="nav-badge">6</span>
    </a>
    <a class="nav-item" href="teams.php">
      <span style="font-size:15px;">👥</span> Teams
    </a>
    <a class="nav-item" href="reports.php">
      <span style="font-size:15px;">📊</span> Reports
    </a>
    <a class="nav-item" href="settings.php">
      <span style="font-size:15px;">⚙️</span> Settings
    </a>
  </nav>

  <div class="sidebar-bottom">
    <div class="user-card">
      <div class="user-avatar">
        <?php echo strtoupper(substr($_SESSION['username'] ?? 'SA', 0, 2)); ?>
      </div>
      <div style="flex:1;">
        <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'System Admin'); ?></div>
        <div class="user-role"><?php echo ucfirst($_SESSION['role'] ?? 'Administrator'); ?></div>
      </div>
    </div>
    <a href="logout.php" class="logout-btn">
      <span>🚪</span> Log Out
    </a>
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
      <span id="clock"></span>
      <button class="notif-btn">🔔</button>
      <div class="user-avatar" style="width:36px;height:36px;font-size:12px;cursor:pointer;">SA</div>
    </div>
  </header>

  <!-- Tabs -->
  <div class="tabs-nav">
    <button class="tab-btn active" onclick="switchTab('dashboard', this)">📊 Overview</button>
    <button class="tab-btn" onclick="switchTab('incidents', this)">🔴 Recent Incidents</button>
    <button class="tab-btn" onclick="switchTab('map', this)">🗺 Live Map</button>
    <button class="tab-btn" onclick="switchTab('teams', this)">👥 Team Status</button>
  </div>

  <div class="content">

    <!-- ====== OVERVIEW TAB ====== -->
    <div class="tab-panel active" id="tab-dashboard">

      <div class="greeting">
        <h1 id="greeting-text">Good Day, System! ⚡</h1>
        <p>Here's what's happening with FlashCru right now. · <span style="color:var(--indigo);font-weight:600;">Wednesday, February 18, 2026</span></p>
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
        <div style="font-size:38px;margin-bottom:12px;opacity:0.7;">📡</div>
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
        <button class="view-all">View All →</button>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Title &amp; Location</th>
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
          <span><span class="map-legend-dot" style="background:var(--red);"></span>Critical</span>
          <span><span class="map-legend-dot" style="background:var(--amber);"></span>Active</span>
          <span><span class="map-legend-dot" style="background:var(--purple);"></span>Pending</span>
          <span><span class="map-legend-dot" style="background:var(--green);"></span>Resolved</span>
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
        <button class="view-all">View All →</button>
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
            <div><div class="team-stat-label">Members</div><div class="team-stat-val" style="color:var(--blue)">6</div></div>
            <div><div class="team-stat-label">On Scene</div><div class="team-stat-val" style="color:var(--amber)">2</div></div>
            <div><div class="team-stat-label">Resolved</div><div class="team-stat-val" style="color:var(--green)">12</div></div>
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
            <div><div class="team-stat-label">Members</div><div class="team-stat-val" style="color:var(--blue)">5</div></div>
            <div><div class="team-stat-label">On Scene</div><div class="team-stat-val" style="color:var(--amber)">1</div></div>
            <div><div class="team-stat-label">Resolved</div><div class="team-stat-val" style="color:var(--green)">8</div></div>
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
            <div><div class="team-stat-label">Members</div><div class="team-stat-val" style="color:var(--blue)">4</div></div>
            <div><div class="team-stat-label">On Scene</div><div class="team-stat-val" style="color:var(--amber)">1</div></div>
            <div><div class="team-stat-label">Resolved</div><div class="team-stat-val" style="color:var(--green)">15</div></div>
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
            <div><div class="team-stat-label">Members</div><div class="team-stat-val" style="color:var(--blue)">4</div></div>
            <div><div class="team-stat-label">On Scene</div><div class="team-stat-val" style="color:var(--amber)">1</div></div>
            <div><div class="team-stat-label">Resolved</div><div class="team-stat-val" style="color:var(--green)">9</div></div>
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
            <div><div class="team-stat-label">Members</div><div class="team-stat-val" style="color:var(--blue)">3</div></div>
            <div><div class="team-stat-label">On Scene</div><div class="team-stat-val" style="color:var(--amber)">0</div></div>
            <div><div class="team-stat-label">Resolved</div><div class="team-stat-val" style="color:var(--green)">6</div></div>
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
            <div><div class="team-stat-label">Members</div><div class="team-stat-val" style="color:var(--blue)">8</div></div>
            <div><div class="team-stat-label">On Scene</div><div class="team-stat-val" style="color:var(--amber)">0</div></div>
            <div><div class="team-stat-label">Resolved</div><div class="team-stat-val" style="color:var(--green)">21</div></div>
          </div>
        </div>

        <div class="team-card">
          <div class="team-card-header">
            <div class="team-name-row">
              <div class="team-icon rescue">🚁</div>
              <div>
                <div class="team-card-name">Rescue Team Alpha</div>
                <div class="team-card-type">Search &amp; Rescue</div>
              </div>
            </div>
            <span class="avail-pill available">Available</span>
          </div>
          <div class="team-stats">
            <div><div class="team-stat-label">Members</div><div class="team-stat-val" style="color:var(--blue)">6</div></div>
            <div><div class="team-stat-label">On Scene</div><div class="team-stat-val" style="color:var(--amber)">0</div></div>
            <div><div class="team-stat-label">Resolved</div><div class="team-stat-val" style="color:var(--green)">7</div></div>
          </div>
        </div>

        <div class="team-card">
          <div class="team-card-header">
            <div class="team-name-row">
              <div class="team-icon rescue">🚁</div>
              <div>
                <div class="team-card-name">Rescue Team Bravo</div>
                <div class="team-card-type">Search &amp; Rescue</div>
              </div>
            </div>
            <span class="avail-pill busy">On Scene</span>
          </div>
          <div class="team-stats">
            <div><div class="team-stat-label">Members</div><div class="team-stat-val" style="color:var(--blue)">6</div></div>
            <div><div class="team-stat-label">On Scene</div><div class="team-stat-val" style="color:var(--amber)">2</div></div>
            <div><div class="team-stat-label">Resolved</div><div class="team-stat-val" style="color:var(--green)">5</div></div>
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