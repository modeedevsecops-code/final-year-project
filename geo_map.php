<?php
require_once 'inc/header.php';
include 'config/db.php';

$db_conn = $db->connection;

// ---- Real donors with coordinates (students table) ----
$donors_res = mysqli_query($db_conn, "SELECT name, email, phone, status AS blood_group, latitude, longitude
    FROM students WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
$donors_arr = [];
while ($row = mysqli_fetch_assoc($donors_res)) {
    $donors_arr[] = $row;
}

// ---- Real hospitals/blood banks (unique by name, from blood_requests) ----
$hospitals_res = mysqli_query($db_conn, "SELECT DISTINCT hospital_name, location, urgency_level, latitude, longitude
    FROM blood_requests WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
$hospitals_arr = [];
while ($row = mysqli_fetch_assoc($hospitals_res)) {
    $hospitals_arr[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">

<head>
  <title>Geo-Location Map | BloodLink Blood Bank System</title>
  <meta name="description"
    content="View blood donors and partner hospitals on an interactive live map. Find your nearest blood bank using BloodLink geo-location matching.">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />

  <style>
    :root {
      --brand: #E11D48;
      --brand-dark: #9F1239;
      --brand-soft: #FFF1F2;
      --blue: #2563EB;
      --blue-soft: #EFF4FF;
      --emergency: #F97316;
      --success: #16A34A;
      --ink: #101828;
      --muted: #667085;
      --line: #E4E7EC;
      --bg: #F5F7FA;
      --panel: #FFFFFF;
      --radius: 14px;
      --font-display: 'Sora', sans-serif;
      --font-body: 'Inter', sans-serif;
    }

    #geo-app * { box-sizing: border-box; font-family: var(--font-body); }

    #geo-app {
      position: relative;
      display: flex;
      width: 100%;
      height: calc(100vh - 90px);
      min-height: 600px;
      border-radius: var(--radius);
      overflow: hidden;
      border: 1px solid var(--line);
      box-shadow: 0 8px 30px rgba(16, 24, 40, .08);
      margin-top: 12px;
      background: var(--panel);
    }

    /* =========== Dashboard side panel =========== */
    #dashPanel {
      width: 340px;
      min-width: 340px;
      height: 100%;
      background: var(--panel);
      border-right: 1px solid var(--line);
      display: flex;
      flex-direction: column;
      z-index: 900;
      transition: margin-left .25s ease;
    }

    .panel-head {
      padding: 16px 18px 12px;
      border-bottom: 1px solid var(--line);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .panel-head .brand-icon {
      width: 34px;
      height: 34px;
      border-radius: 10px;
      background: var(--brand);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .panel-head h2 {
      font-family: var(--font-display);
      font-weight: 700;
      font-size: 1rem;
      color: var(--ink);
      margin: 0;
      letter-spacing: -.01em;
    }

    .panel-head .sub-stat {
      font-size: .72rem;
      color: var(--muted);
      margin-top: 1px;
    }

    .panel-close {
      display: none;
      margin-left: auto;
      background: none;
      border: none;
      font-size: 1.1rem;
      color: var(--muted);
      cursor: pointer;
    }

    .panel-scroll {
      flex: 1;
      overflow-y: auto;
      padding: 14px 18px 20px;
    }

    .field-label {
      font-size: .7rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .04em;
      color: var(--muted);
      margin: 14px 0 6px;
    }
    .field-label:first-child { margin-top: 0; }

    .search-box {
      position: relative;
    }

    .search-box input {
      width: 100%;
      border: 1.5px solid var(--line);
      border-radius: 10px;
      padding: 9px 12px 9px 34px;
      font-size: .85rem;
      outline: none;
      background: var(--bg) url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23667085' viewBox='0 0 16 16'><path d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001q.044.06.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1 1 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0'/></svg>") no-repeat 10px center;
      transition: border-color .15s;
    }
    .search-box input:focus { border-color: var(--brand); background-color: #fff; }

    #suggestions {
      position: absolute;
      top: 42px;
      left: 0;
      right: 0;
      z-index: 50;
      background: #fff;
      border-radius: 10px;
      box-shadow: 0 6px 20px rgba(16,24,40,.12);
      border: 1px solid var(--line);
      max-height: 200px;
      overflow-y: auto;
    }
    #suggestions .item {
      padding: 9px 12px;
      font-size: .8rem;
      border-bottom: 1px solid var(--line);
      cursor: pointer;
      color: var(--ink);
    }
    #suggestions .item:last-child { border-bottom: none; }
    #suggestions .item:hover { background: var(--brand-soft); }

    .filter-row {
      display: flex;
      gap: 8px;
    }
    .filter-row select {
      flex: 1;
      min-width: 0;
      border: 1.5px solid var(--line);
      border-radius: 10px;
      padding: 8px 8px;
      font-size: .78rem;
      background: var(--bg);
      color: var(--ink);
      outline: none;
    }
    .filter-row select:focus { border-color: var(--brand); }

    .action-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px;
      margin-top: 14px;
    }
    .action-row .full { grid-column: 1 / -1; }

    .btn {
      border: none;
      border-radius: 10px;
      font-weight: 600;
      font-size: .78rem;
      padding: 9px 10px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: transform .05s ease, background .15s ease;
    }
    .btn:active { transform: scale(.97); }
    .btn-brand { background: var(--brand); color: #fff; }
    .btn-brand:hover { background: var(--brand-dark); }
    .btn-ghost { background: var(--bg); color: var(--ink); border: 1.5px solid var(--line); }
    .btn-ghost:hover { border-color: var(--ink); }
    .btn-brand.live { background: var(--success); }

    /* ---- Nearest hospital signature card ---- */
    #nearestCard {
      margin-top: 16px;
      border-radius: 12px;
      background: linear-gradient(135deg, var(--blue-soft), #fff);
      border: 1px solid #DCE6FB;
      padding: 12px 14px;
      display: none;
    }
    #nearestCard.show { display: block; }

    .nearest-top {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: .68rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .05em;
      color: var(--blue);
    }

    .pulse-dot {
      position: relative;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--success);
      flex-shrink: 0;
    }
    .pulse-dot::after {
      content: '';
      position: absolute;
      inset: -4px;
      border-radius: 50%;
      background: var(--success);
      opacity: .5;
      animation: pulseRing 1.6s ease-out infinite;
    }
    @keyframes pulseRing {
      0% { transform: scale(.6); opacity: .55; }
      100% { transform: scale(2.2); opacity: 0; }
    }

    .nearest-name {
      font-family: var(--font-display);
      font-weight: 700;
      font-size: .92rem;
      color: var(--ink);
      margin-top: 4px;
    }
    .nearest-meta {
      font-size: .76rem;
      color: var(--muted);
      margin-top: 2px;
    }
    .nearest-meta b { color: var(--ink); }
    #nearestCard .btn { width: 100%; margin-top: 10px; }

    /* ---- Ranked list ---- */
    .list-head {
      display: flex;
      align-items: baseline;
      justify-content: space-between;
      margin-top: 18px;
    }
    .list-head h6 {
      font-family: var(--font-display);
      font-weight: 700;
      font-size: .82rem;
      color: var(--ink);
      margin: 0;
    }
    .list-head .sub { font-size: .68rem; color: var(--muted); }

    .rank-card {
      border: 1px solid var(--line);
      border-radius: 10px;
      padding: 9px 10px;
      margin-top: 8px;
      cursor: pointer;
      display: flex;
      gap: 8px;
      align-items: flex-start;
      transition: border-color .12s, background .12s;
    }
    .rank-card:hover { border-color: var(--brand); background: var(--brand-soft); }

    .rank-badge {
      width: 22px;
      height: 22px;
      border-radius: 50%;
      background: var(--ink);
      color: #fff;
      font-size: .68rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      margin-top: 1px;
    }
    .rank-name { font-weight: 600; font-size: .82rem; color: var(--ink); }
    .rank-meta { font-size: .72rem; color: var(--muted); margin-top: 1px; }
    .tag {
      display: inline-block;
      font-size: .64rem;
      font-weight: 700;
      padding: 2px 7px;
      border-radius: 10px;
      color: #fff;
      margin-top: 4px;
    }
    .tag.donor { background: var(--brand); }
    .tag.hospital { background: var(--blue); }
    .tag.emergency { background: var(--emergency); }

    /* ---- Legend, inline in panel footer ---- */
    .legend-inline {
      border-top: 1px solid var(--line);
      margin-top: 16px;
      padding-top: 12px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 6px;
      font-size: .72rem;
      color: var(--muted);
    }
    .legend-inline .row { display: flex; align-items: center; gap: 6px; }
    .legend-inline .dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }

    /* =========== Map area =========== */
    .dash-map {
      flex: 1;
      position: relative;
      height: 100%;
    }

    #panelToggle {
      display: none;
      position: absolute;
      top: 14px;
      left: 14px;
      z-index: 1000;
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: #fff;
      border: none;
      box-shadow: 0 2px 10px rgba(0,0,0,.2);
      cursor: pointer;
      font-size: 1.1rem;
    }

    #status {
      position: absolute;
      bottom: 16px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 1000;
      background: rgba(16, 24, 40, .9);
      color: #fff;
      padding: 8px 16px;
      border-radius: 8px;
      font-size: .8rem;
      display: none;
      max-width: 70vw;
      text-align: center;
    }

    .stat-badge {
      background: var(--brand);
      color: #fff;
      border-radius: 30px;
      padding: 4px 14px;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .leaflet-routing-container {
      max-height: 30vh;
      overflow-y: auto;
      border-radius: 10px !important;
      box-shadow: 0 2px 10px rgba(0, 0, 0, .25) !important;
    }

    /* ---- Responsive: off-canvas panel on small screens ---- */
    @media (max-width: 860px) {
      #dashPanel {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        margin-left: -340px;
        box-shadow: 0 8px 30px rgba(16,24,40,.2);
      }
      #dashPanel.open { margin-left: 0; }
      .panel-close { display: block; }
      #panelToggle { display: block; }
    }
  </style>
</head>

<body>
  <main class="main" id="top">
    <?php include 'inc/navbar.php'; ?>
    <br>

    <section class="py-2">
      <div class="container-lg">

        <div class="row justify-content-center mb-2 mt-3">
          <div class="col-lg-11 text-center">
            <h2 class="fw-bold">
              <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="#E11D48" class="me-2 mb-1"
                viewBox="0 0 16 16">
                <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z" />
              </svg>
              Geo-Location Map
            </h2>
            <p class="text-muted mb-2">
              Live map of registered donors and partner blood banks/hospitals. Search a location,
              use your live position, and get turn-by-turn directions to the nearest blood bank.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-2 mb-2">
              <span class="stat-badge">🩸 <?php echo count($donors_arr); ?> Donors</span>
              <span class="stat-badge" style="background:var(--blue)">🏥 <?php echo count($hospitals_arr); ?> Partner Hospitals</span>
              <span class="stat-badge" style="background:var(--ink)">📍 Kaduna, Nigeria</span>
              <span class="stat-badge" style="background:var(--success)">✅ Live Matching Active</span>
            </div>
          </div>
        </div>

        <div class="row justify-content-center">
          <div class="col-lg-11">
            <div id="geo-app">

              <!-- ============ Dashboard side panel ============ -->
              <aside id="dashPanel">
                <div class="panel-head">
                  <div class="brand-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="#fff" viewBox="0 0 16 16">
                      <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z" />
                    </svg>
                  </div>
                  <div>
                    <h2>BloodLink Dashboard</h2>
                    <div class="sub-stat">Donors, banks &amp; live routing</div>
                  </div>
                  <button class="panel-close" id="panelCloseBtn">✕</button>
                </div>

                <div class="panel-scroll">

                  <div class="field-label">Search a location</div>
                  <div class="search-box">
                    <input type="text" id="locationSearch" placeholder="e.g. Kaduna, Kano, Abuja…">
                    <div id="suggestions"></div>
                  </div>

                  <div class="field-label">Filters</div>
                  <div class="filter-row">
                    <select id="filterBloodType">
                      <option value="all">All blood types</option>
                      <option value="A+">A+</option>
                      <option value="A-">A-</option>
                      <option value="B+">B+</option>
                      <option value="B-">B-</option>
                      <option value="O+">O+ (Universal)</option>
                      <option value="O-">O- (Universal donor)</option>
                      <option value="AB+">AB+</option>
                      <option value="AB-">AB-</option>
                    </select>
                    <select id="filterType">
                      <option value="all">Donors &amp; hospitals</option>
                      <option value="donors">Donors only</option>
                      <option value="hospitals">Hospitals only</option>
                    </select>
                  </div>

                  <div class="action-row">
                    <button class="btn btn-brand full" id="locateBtn">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
                      Use my live location
                    </button>
                    <button class="btn btn-ghost" id="resetBtn">Reset view</button>
                    <button class="btn btn-ghost" id="clearRouteBtn">Clear route</button>
                  </div>

                  <!-- Nearest hospital — auto-updates live as position changes -->
                  <div id="nearestCard">
                    <div class="nearest-top"><span class="pulse-dot"></span> Nearest hospital · live</div>
                    <div class="nearest-name" id="nearestName">—</div>
                    <div class="nearest-meta" id="nearestMeta">—</div>
                    <button class="btn btn-brand" id="routeNearestBtn">Route me there</button>
                  </div>

                  <div class="list-head">
                    <h6>Nearby</h6>
                    <span class="sub" id="sidebarSub">Tap "Use my live location"</span>
                  </div>
                  <div id="rankedList"></div>

                  <div class="legend-inline">
                    <div class="row"><span class="dot" style="background:var(--brand)"></span>Donor</div>
                    <div class="row"><span class="dot" style="background:var(--blue)"></span>Hospital</div>
                    <div class="row"><span class="dot" style="background:var(--emergency)"></span>Emergency</div>
                    <div class="row"><span class="dot" style="background:#2563A6"></span>You</div>
                  </div>

                </div>
              </aside>

              <!-- ============ Map ============ -->
              <div class="dash-map">
                <button id="panelToggle">☰</button>
                <div id="bloodlink-map" style="position:absolute;inset:0;"></div>
                <div id="status"></div>
              </div>

            </div>
          </div>
        </div>

        <!-- Quick action cards below the map -->
        <div class="row justify-content-center mt-4 g-3">
          <div class="col-lg-11">
            <div class="row g-3">
              <div class="col-md-4">
                <div class="card border-danger h-100 text-center p-3">
                  <h6 class="fw-bold text-danger">🚨 Emergency Request</h6>
                  <p class="small text-muted mb-2">Post a blood request alert to notify nearby compatible donors.</p>
                  <a href="notices.php" class="btn btn-sm btn-danger">Post Alert</a>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card border-primary h-100 text-center p-3">
                  <h6 class="fw-bold text-primary">🏥 Find Nearest Bank</h6>
                  <p class="small text-muted mb-2">Locate the closest blood bank or hospital to your current location.</p>
                  <button class="btn btn-sm btn-primary" onclick="document.getElementById('locateBtn').click()">Use My Location</button>
                </div>
              </div>
              <div class="col-md-4">
                <div class="card border-success h-100 text-center p-3">
                  <h6 class="fw-bold text-success">🩸 Register as Donor</h6>
                  <p class="small text-muted mb-2">Not yet registered? Sign up to appear on the donor map and save lives.</p>
                  <a href="register_donor.php" class="btn btn-sm btn-success">Register Now</a>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </section>
  </main>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
  <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>

  <script>
    // ======================================================
    // Real data from database (PHP -> JS)
    // ======================================================
    const donors = <?php echo json_encode($donors_arr); ?>;
    const hospitals = <?php echo json_encode($hospitals_arr); ?>;

    const KADUNA_CENTER = [10.5105, 7.4165];

    let map, youMarker, watchId, lastLatLng = null;
    let routingControl = null;
    let selectedDestination = null;
    let followMe = true; // keep the map centered on the user as they move
    const allMarkers = []; // { marker, type, blood, emergency, lat, lng, name }

    // ---- Init map ----
    map = L.map('bloodlink-map', { zoomControl: false }).setView(KADUNA_CENTER, 12);
    L.control.zoom({ position: 'bottomleft' }).addTo(map);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
      maxZoom: 19
    }).addTo(map);

    // stop auto-follow the moment a person manually drags/zooms the map
    map.on('dragstart', () => followMe = false);

    const statusEl = document.getElementById('status');
    function showStatus(msg, ms) {
      statusEl.textContent = msg;
      statusEl.style.display = 'block';
      if (ms) { clearTimeout(showStatus._t); showStatus._t = setTimeout(() => statusEl.style.display = 'none', ms); }
    }

    function dropIcon(color, emoji, size) {
      size = size || 30;
      return L.divIcon({
        className: '',
        html: `<div style="
          width:${size}px;height:${size}px;border-radius:50% 50% 50% 0;
          background:${color};border:2px solid #fff;transform:rotate(-45deg);
          box-shadow:0 1px 5px rgba(0,0,0,.45);display:flex;align-items:center;justify-content:center;">
          <span style="transform:rotate(45deg);font-size:${size*0.5}px;">${emoji}</span>
        </div>`,
        iconSize: [size, size],
        iconAnchor: [size / 2, size]
      });
    }

    function haversineKm(a, b) {
      const R = 6371, dLat = (b[0] - a[0]) * Math.PI / 180, dLon = (b[1] - a[1]) * Math.PI / 180;
      const lat1 = a[0] * Math.PI / 180, lat2 = b[0] * Math.PI / 180;
      const h = Math.sin(dLat / 2) ** 2 + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLon / 2) ** 2;
      return R * 2 * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
    }

    function escapeHtml(s) {
      return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    function directionsLink(lat, lng, name) {
      return `<a href="#" class="route-link" data-lat="${lat}" data-lng="${lng}" data-name="${escapeHtml(name)}">Get directions</a>`;
    }

    function bindRouteLink(marker) {
      marker.on('popupopen', () => {
        document.querySelectorAll('.route-link').forEach(link => {
          link.onclick = (ev) => {
            ev.preventDefault();
            selectedDestination = { lat: parseFloat(link.dataset.lat), lng: parseFloat(link.dataset.lng), label: link.dataset.name };
            drawRoute();
          };
        });
      });
    }

    // ---- Plot donors ----
    donors.forEach(d => {
      const lat = parseFloat(d.latitude), lng = parseFloat(d.longitude);
      if (isNaN(lat) || isNaN(lng)) return;
      const marker = L.marker([lat, lng], { icon: dropIcon('#E11D48', '🩸') });
      marker.bindPopup(`
        <div style="min-width:180px">
          <strong style="color:#E11D48;">🩸 Blood Donor</strong><br>
          <b>${escapeHtml(d.name)}</b><br>
          Blood Type: <span style="background:#E11D48;color:#fff;padding:2px 8px;border-radius:10px;">${escapeHtml(d.blood_group || 'N/A')}</span><br>
          Phone: ${escapeHtml(d.phone || 'N/A')}<br>
          ${directionsLink(lat, lng, d.name)}
        </div>
      `);
      bindRouteLink(marker);
      marker.addTo(map);
      allMarkers.push({ marker, type: 'donor', blood: d.blood_group, lat, lng, name: d.name });
    });

    // ---- Plot hospitals ----
    hospitals.forEach(h => {
      const lat = parseFloat(h.latitude), lng = parseFloat(h.longitude);
      if (isNaN(lat) || isNaN(lng)) return;
      const isEmergency = h.urgency_level === 'Critical Emergency';
      const marker = L.marker([lat, lng], {
        icon: dropIcon(isEmergency ? '#F97316' : '#2563EB', isEmergency ? '🚨' : '🏥', isEmergency ? 34 : 30)
      });
      marker.bindPopup(`
        <div style="min-width:200px">
          <strong style="color:#2563EB;">${isEmergency ? '🚨 EMERGENCY ACTIVE' : '🏥 Blood Bank / Hospital'}</strong><br>
          <b>${escapeHtml(h.hospital_name)}</b><br>
          Location: ${escapeHtml(h.location)}<br>
          ${isEmergency ? '<span style="color:#E11D48;font-weight:bold;">⚠ Blood urgently needed!</span><br>' : ''}
          ${directionsLink(lat, lng, h.hospital_name)}<br>
          <a href="contact.php" style="color:#E11D48;">Contact for donation</a>
        </div>
      `);
      bindRouteLink(marker);
      marker.addTo(map);
      allMarkers.push({ marker, type: 'hospital', emergency: isEmergency, lat, lng, name: h.hospital_name });
    });

    // ======================================================
    // Live position (moving marker) + distance ranking + nearest hospital
    // ======================================================
    const youIcon = L.divIcon({
      className: '',
      html: `<div style="width:20px;height:20px;background:#2563A6;border:3px solid #fff;border-radius:50%;box-shadow:0 0 0 4px rgba(37,99,166,.3);"></div>`,
      iconSize: [20, 20]
    });

    function startWatching() {
      if (!navigator.geolocation) { showStatus('Geolocation is not supported by this browser.', 4000); return; }
      showStatus('Requesting location permission…');
      const locateBtn = document.getElementById('locateBtn');
      locateBtn.classList.add('live');
      watchId = navigator.geolocation.watchPosition(onPosition, onPosError, {
        enableHighAccuracy: true, maximumAge: 2000, timeout: 15000
      });
    }

    function onPosition(pos) {
      const latlng = [pos.coords.latitude, pos.coords.longitude];
      lastLatLng = latlng;
      if (!youMarker) {
        youMarker = L.marker(latlng, { icon: youIcon, zIndexOffset: 1000 }).addTo(map);
        map.setView(latlng, 14);
        showStatus('Live location active — tracking will update as you move', 3000);
      } else {
        youMarker.setLatLng(latlng);
        if (followMe) map.panTo(latlng, { animate: true });
      }
      // keep an active route glued to the live position
      if (routingControl) {
        const wps = routingControl.getWaypoints();
        wps[0] = L.Routing.waypoint(L.latLng(latlng[0], latlng[1]));
        routingControl.setWaypoints(wps);
      }
      updateRankedList();
      updateNearestHospital();
    }

    function onPosError(err) { showStatus('Location error: ' + err.message, 4000); }

    document.getElementById('locateBtn').onclick = () => {
      followMe = true;
      if (!watchId) startWatching(); else if (lastLatLng) map.setView(lastLatLng, 15);
      if (window.innerWidth <= 860) document.getElementById('dashPanel').classList.add('open');
    };

    // ---- Nearest hospital card, recalculated on every position update ----
    function updateNearestHospital() {
      if (!lastLatLng) return;
      const hospitalMarkers = allMarkers.filter(m => m.type === 'hospital');
      if (!hospitalMarkers.length) return;
      let nearest = null, best = Infinity;
      hospitalMarkers.forEach(m => {
        const d = haversineKm(lastLatLng, [m.lat, m.lng]);
        if (d < best) { best = d; nearest = m; }
      });
      if (!nearest) return;
      document.getElementById('nearestCard').classList.add('show');
      document.getElementById('nearestName').textContent = nearest.name;
      document.getElementById('nearestMeta').innerHTML =
        `<b>${best.toFixed(1)} km</b> away${nearest.emergency ? ' · <span style="color:#F97316;font-weight:700;">emergency active</span>' : ''}`;
      document.getElementById('routeNearestBtn').onclick = () => {
        selectedDestination = { lat: nearest.lat, lng: nearest.lng, label: nearest.name };
        drawRoute();
      };
    }

    // ---- Ranked nearby list, sorted by real distance ----
    function updateRankedList() {
      if (!lastLatLng) return;
      const listEl = document.getElementById('rankedList');
      const subEl = document.getElementById('sidebarSub');
      const ranked = allMarkers.map(m => ({ ...m, dist: haversineKm(lastLatLng, [m.lat, m.lng]) }))
        .sort((a, b) => a.dist - b.dist)
        .slice(0, 20);

      subEl.textContent = `${ranked.length} shown, nearest first`;
      listEl.innerHTML = '';
      ranked.forEach((m, i) => {
        const card = document.createElement('div');
        card.className = 'rank-card';
        const tag = m.type === 'donor'
          ? `<span class="tag donor">${escapeHtml(m.blood || 'Donor')}</span>`
          : (m.emergency ? `<span class="tag emergency">Emergency</span>` : `<span class="tag hospital">Hospital</span>`);
        card.innerHTML = `
          <div class="rank-badge">${i + 1}</div>
          <div>
            <div class="rank-name">${escapeHtml(m.name)}</div>
            <div class="rank-meta">${m.dist.toFixed(1)} km away</div>
            ${tag}
          </div>`;
        card.onclick = () => {
          followMe = false;
          map.setView([m.lat, m.lng], 15);
          m.marker.openPopup();
        };
        listEl.appendChild(card);
      });
    }

    // ======================================================
    // Routing / directions with distance + duration
    // ======================================================
    function drawRoute() {
      if (!selectedDestination) { showStatus('Pick a donor or hospital first.', 3000); return; }
      if (!lastLatLng) {
        showStatus('Getting your location for directions…');
        startWatching();
        setTimeout(drawRoute, 2500);
        return;
      }
      if (routingControl) { map.removeControl(routingControl); routingControl = null; }
      routingControl = L.Routing.control({
        waypoints: [L.latLng(lastLatLng[0], lastLatLng[1]), L.latLng(selectedDestination.lat, selectedDestination.lng)],
        routeWhileDragging: false,
        addWaypoints: false,
        lineOptions: { styles: [{ color: '#E11D48', weight: 5, opacity: 0.85 }] },
        createMarker: (i, wp) => i === 0 ? L.marker(wp.latLng, { icon: youIcon }) : null
      }).on('routesfound', (e) => {
        const r = e.routes[0];
        const km = (r.summary.totalDistance / 1000).toFixed(1);
        const mins = Math.round(r.summary.totalTime / 60);
        showStatus(`${selectedDestination.label}: ${km} km, about ${mins} min away`, 6000);
      }).addTo(map);
    }

    document.getElementById('clearRouteBtn').onclick = () => {
      if (routingControl) { map.removeControl(routingControl); routingControl = null; }
      selectedDestination = null;
      showStatus('Route cleared', 2000);
    };

    document.getElementById('resetBtn').onclick = () => {
      followMe = false;
      map.setView(KADUNA_CENTER, 12);
    };

    // ======================================================
    // Search (Nominatim via a small inline PHP geocode proxy below)
    // ======================================================
    const searchInput = document.getElementById('locationSearch');
    const suggestionsEl = document.getElementById('suggestions');
    let searchTimer = null;

    searchInput.addEventListener('input', () => {
      clearTimeout(searchTimer);
      const q = searchInput.value.trim();
      if (q.length < 3) { suggestionsEl.innerHTML = ''; return; }
      searchTimer = setTimeout(() => runSearch(q), 350);
    });

    function runSearch(q) {
      fetch('geocode.php?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(results => {
          suggestionsEl.innerHTML = '';
          if (!results || !results.length) { suggestionsEl.innerHTML = '<div class="item">No results found</div>'; return; }
          results.forEach(res => {
            const div = document.createElement('div');
            div.className = 'item';
            div.textContent = res.display_name;
            div.onclick = () => {
              followMe = false;
              const lat = parseFloat(res.lat), lng = parseFloat(res.lon);
              map.flyTo([lat, lng], 14);
              suggestionsEl.innerHTML = '';
              searchInput.value = res.display_name;
            };
            suggestionsEl.appendChild(div);
          });
        })
        .catch(() => showStatus('Search failed (geocode.php unreachable).', 4000));
    }

    // ======================================================
    // Filters
    // ======================================================
    document.getElementById('filterBloodType').addEventListener('change', function () {
      const val = this.value;
      allMarkers.forEach(item => {
        if (item.type === 'donor') item.marker.setOpacity(val === 'all' || item.blood === val ? 1 : 0);
      });
    });

    document.getElementById('filterType').addEventListener('change', function () {
      const val = this.value;
      allMarkers.forEach(item => {
        if (val === 'all') item.marker.setOpacity(1);
        else if (val === 'donors') item.marker.setOpacity(item.type === 'donor' ? 1 : 0);
        else if (val === 'hospitals') item.marker.setOpacity(item.type === 'hospital' ? 1 : 0);
      });
    });

    // ======================================================
    // Mobile off-canvas panel
    // ======================================================
    document.getElementById('panelToggle').onclick = () => document.getElementById('dashPanel').classList.toggle('open');
    document.getElementById('panelCloseBtn').onclick = () => document.getElementById('dashPanel').classList.remove('open');

    showStatus('Tip: tap "Use my live location" for live position, nearest hospital, and directions.', 6000);
  </script>

  <?php include 'inc/main_js.php'; ?>
</body>

</html>