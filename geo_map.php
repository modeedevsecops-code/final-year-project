<?php
// DB + session first, then guard, THEN header. This page plots donor names,
// emails and coordinates — it must never be reachable while logged out (BL-25).
include 'config/db.php';
bl_require_login();

$db_conn = $db->connection;
$ops = new operations();

// ---- Eligible donors with coordinates (reads blood_group, not the old status
//      column — BL-01). Eligibility is computed with the same 56-day rule the
//      matching engine uses, so the map and the officer queue agree. ----
$donors_arr = [];
$donors_res = mysqli_query($db_conn,
    "SELECT name, email, phone, blood_group, last_donation_date, latitude, longitude
     FROM students
     WHERE latitude IS NOT NULL AND longitude IS NOT NULL AND blood_group IS NOT NULL");
if ($donors_res) {
    while ($row = mysqli_fetch_assoc($donors_res)) {
        $row['eligible'] = $ops->is_donor_eligible($row['last_donation_date']);
        $donors_arr[] = $row;
    }
}

// ---- Partner hospitals / open requests, from blood_requests with coordinates ----
$hospitals_arr = [];
$hospitals_res = mysqli_query($db_conn,
    "SELECT hospital_name, location, blood_group, units_needed, urgency_level, status, latitude, longitude
     FROM blood_requests
     WHERE latitude IS NOT NULL AND longitude IS NOT NULL
     ORDER BY FIELD(urgency_level,'Critical Emergency','Urgent','Normal'), created_at DESC");
if ($hospitals_res) {
    while ($row = mysqli_fetch_assoc($hospitals_res)) {
        $hospitals_arr[] = $row;
    }
}

// Hand the data to JS as JSON rendered server-side (no AJAX endpoint needed at
// this scale). htmlspecialchars keeps popup text safe.
$donors_json    = json_encode($donors_arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
$hospitals_json = json_encode($hospitals_arr, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <title>Geo-Location Map | BloodLink Blood Bank System</title>
  <meta name="description" content="View blood donors and partner hospitals on an interactive live map. Find your nearest blood bank using BloodLink geo-location matching.">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/sidebar.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
  <style>
    #bl-map { height: 70vh; min-height: 460px; width: 100%; border-radius: 12px; border: 1px solid #eee; }
    .map-legend { display:flex; gap:18px; flex-wrap:wrap; margin:14px 0 4px; font-size:.9rem; }
    .map-legend span { display:inline-flex; align-items:center; gap:7px; }
    .lg-dot { width:14px; height:14px; border-radius:50%; display:inline-block; border:2px solid #fff; box-shadow:0 0 0 1px rgba(0,0,0,.15); }
    .lg-elig { background:#2c8a4a; } .lg-inelig { background:#9aa0a6; }
    .lg-crit { background:#c2172e; } .lg-urgent { background:#e08a00; } .lg-normal { background:#3f6b7a; }
    .bl-pop b { color:#7a0000; } .bl-pop .muted { color:#666; font-size:.85rem; }
    .bl-pop .pill { display:inline-block; background:#fdeaea; color:#7a0000; padding:1px 8px; border-radius:999px; font-weight:700; font-size:.8rem; }
  </style>
</head>
<body>
<main class="main" id="top">
  <?php include 'inc/navbar.php'; ?>

  <div class="main-content">
    <div class="welcome-banner">
      <span class="badge-pill">GEO-LOCATION</span>
      <h1>Nearby Donors &amp; Blood Banks</h1>
      <p>Live map of eligible donors and partner hospitals. Powered by OpenStreetMap — no API key, no tracking.</p>
    </div>

    <div class="map-legend">
      <span><i class="lg-dot lg-elig"></i> Eligible donor</span>
      <span><i class="lg-dot lg-inelig"></i> Donor in 56-day window</span>
      <span><i class="lg-dot lg-crit"></i> Critical request</span>
      <span><i class="lg-dot lg-urgent"></i> Urgent request</span>
      <span><i class="lg-dot lg-normal"></i> Normal request</span>
    </div>

    <div class="quick-card" style="padding:12px;">
      <div id="bl-map"></div>
      <p class="text-muted" style="font-size:.82rem; margin:10px 4px 0;">
        <?= count($donors_arr) ?> donor(s) and <?= count($hospitals_arr) ?> request/hospital location(s) shown.
        Records without coordinates are omitted.
      </p>
    </div>
  </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
  const DONORS    = <?= $donors_json ?: '[]' ?>;
  const HOSPITALS = <?= $hospitals_json ?: '[]' ?>;

  // Center on Kaduna; if we have points, fit to them instead.
  const map = L.map('bl-map').setView([10.5222, 7.4383], 7);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(map);

  const esc = s => (s == null ? '' : String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])));
  const dot = (color) => L.divIcon({
    className: '', iconSize: [18, 18], iconAnchor: [9, 9],
    html: `<span style="display:block;width:16px;height:16px;border-radius:50%;background:${color};border:2px solid #fff;box-shadow:0 0 0 1px rgba(0,0,0,.25)"></span>`
  });

  const donorLayer = L.layerGroup();
  const hospitalLayer = L.layerGroup();
  const bounds = [];

  DONORS.forEach(d => {
    const lat = parseFloat(d.latitude), lng = parseFloat(d.longitude);
    if (isNaN(lat) || isNaN(lng)) return;               // skip rows without coords, no error
    const color = d.eligible ? '#2c8a4a' : '#9aa0a6';
    const status = d.eligible ? 'Eligible to donate' : 'In 56-day window';
    L.marker([lat, lng], { icon: dot(color) })
      .bindPopup(`<div class="bl-pop"><b>${esc(d.name)}</b><br>
        <span class="pill">${esc(d.blood_group)}</span> &middot; ${esc(status)}<br>
        <span class="muted">${esc(d.phone)}<br>${esc(d.email)}</span></div>`)
      .addTo(donorLayer);
    bounds.push([lat, lng]);
  });

  const urgencyColor = u => u === 'Critical Emergency' ? '#c2172e' : (u === 'Urgent' ? '#e08a00' : '#3f6b7a');
  HOSPITALS.forEach(h => {
    const lat = parseFloat(h.latitude), lng = parseFloat(h.longitude);
    if (isNaN(lat) || isNaN(lng)) return;
    L.marker([lat, lng], { icon: dot(urgencyColor(h.urgency_level)) })
      .bindPopup(`<div class="bl-pop"><b>${esc(h.hospital_name)}</b><br>
        <span class="muted">${esc(h.location)}</span><br>
        Needs <span class="pill">${esc(h.blood_group)}</span> &times; ${esc(h.units_needed)} &middot; ${esc(h.urgency_level)}<br>
        <span class="muted">Status: ${esc(h.status)}</span></div>`)
      .addTo(hospitalLayer);
    bounds.push([lat, lng]);
  });

  donorLayer.addTo(map);
  hospitalLayer.addTo(map);
  L.control.layers(null, {
    'Donors': donorLayer,
    'Hospitals &amp; Requests': hospitalLayer
  }, { collapsed: false }).addTo(map);

  if (bounds.length) map.fitBounds(bounds, { padding: [40, 40], maxZoom: 12 });

  // "Locate me" control
  const locateBtn = L.control({ position: 'topleft' });
  locateBtn.onAdd = function () {
    const b = L.DomUtil.create('button', '');
    b.innerHTML = '📍';
    b.title = 'Find my location';
    b.style.cssText = 'width:34px;height:34px;font-size:16px;cursor:pointer;border:none;border-radius:6px;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.3)';
    L.DomEvent.on(b, 'click', (e) => {
      L.DomEvent.stop(e);
      map.locate({ setView: true, maxZoom: 13 });
    });
    return b;
  };
  locateBtn.addTo(map);
  map.on('locationfound', e => {
    L.circleMarker(e.latlng, { radius: 8, color: '#1a5fb4', fillColor: '#1a5fb4', fillOpacity: .6 })
      .addTo(map).bindPopup('You are here').openPopup();
  });
</script>
</body>
</html>
