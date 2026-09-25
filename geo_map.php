<?php
// Blood Bank Locator (brief §5.5). Captures the user's location, ranks the
// registered blood_banks nearest-first (Haversine), shows them on a Leaflet map
// with a ranked list, and draws an OSRM turn-by-turn route — colour-coded by
// distance — to a selected bank. Proximity only; stock is managed separately.
include 'config/db.php';
bl_require_login();

$conn = $db->connection;
$ops  = new operations();

// Registered banks with a little aggregate stock for the popups.
$banks = [];
$res = mysqli_query($conn,
    "SELECT bb.bank_id, bb.name, bb.address, bb.contact_phone, bb.latitude, bb.longitude,
            COALESCE(SUM(bs.units_available),0) AS total_units
     FROM blood_banks bb
     LEFT JOIN blood_stock bs ON bs.blood_bank_id = bb.bank_id
     WHERE bb.latitude IS NOT NULL AND bb.longitude IS NOT NULL
     GROUP BY bb.bank_id
     ORDER BY bb.name ASC");
if ($res) { while ($r = mysqli_fetch_assoc($res)) { $banks[] = $r; } }
$banks_json = json_encode($banks, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="en-US" dir="ltr">
<head>
  <title>Blood Bank Locator | BloodLink</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/sidebar.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
  <style>
    .loc-grid { display:grid; grid-template-columns: 340px 1fr; gap:16px; }
    @media (max-width: 900px){ .loc-grid{ grid-template-columns:1fr; } }
    #bl-map { height: 74vh; min-height: 500px; border-radius:12px; border:1px solid #eee; }
    .rank-panel { background:#fff; border:1px solid #eee; border-radius:12px; padding:14px; height:74vh; min-height:500px; overflow-y:auto; }
    .rank-item { display:flex; gap:10px; align-items:flex-start; padding:11px 10px; border:1px solid #f0f0f0; border-radius:10px; margin-bottom:9px; cursor:pointer; transition:background .12s, border-color .12s; }
    .rank-item:hover { background:#fdf6f6; border-color:#f2caca; }
    .rank-item.active { background:#fdeaea; border-color:#e0a3a3; }
    .rank-num { width:26px; height:26px; flex:0 0 26px; border-radius:50%; color:#fff; font-weight:700; font-size:.8rem; display:flex; align-items:center; justify-content:center; }
    .rank-body { flex:1; min-width:0; }
    .rank-name { font-weight:700; font-size:.92rem; color:#1b1416; }
    .rank-meta { font-size:.8rem; color:#777; }
    .dist-badge { font-weight:700; font-size:.82rem; white-space:nowrap; }
    .route-box { background:#fff; border:1px solid #eee; border-radius:10px; padding:10px 12px; margin-bottom:10px; display:none; }
    .route-box.show { display:block; }
    .route-box .rb-metric { display:inline-block; margin-right:16px; font-weight:700; }
    .route-box .rb-metric small { display:block; font-weight:500; color:#888; font-size:.72rem; }
    .legend2 { display:flex; gap:14px; flex-wrap:wrap; font-size:.8rem; color:#555; margin:8px 0 12px; }
    .legend2 span { display:inline-flex; align-items:center; gap:6px; }
    .lg { width:12px; height:12px; border-radius:3px; display:inline-block; }
  </style>
</head>
<body>
<main class="main" id="top">
  <?php include 'inc/navbar.php'; ?>

  <div class="main-content">
    <div class="welcome-banner">
      <span class="badge-pill">BLOOD BANK LOCATOR</span>
      <h1>Find the Nearest Blood Bank</h1>
      <p>Registered banks ranked by distance from you, with driving directions. Powered by OpenStreetMap, Nominatim &amp; OSRM — no API key.</p>
    </div>

    <div class="legend2">
      <span><i class="lg" style="background:#2c8a4a;"></i> ≤ 5 km</span>
      <span><i class="lg" style="background:#e08a00;"></i> 5–15 km</span>
      <span><i class="lg" style="background:#c2172e;"></i> &gt; 15 km</span>
      <span><i class="lg" style="background:#1a5fb4;"></i> You</span>
    </div>

    <div style="margin-bottom:12px; display:flex; gap:10px; flex-wrap:wrap;">
      <button id="locateBtn" class="btn btn-danger btn-sm"><i class="fas fa-location-crosshairs"></i> Use my location</button>
      <button id="kadunaBtn" class="btn btn-outline-secondary btn-sm">Use Kaduna city centre</button>
      <span id="locStatus" style="align-self:center; color:#888; font-size:.85rem;"></span>
    </div>

    <div class="loc-grid">
      <div class="rank-panel">
        <div class="route-box" id="routeBox">
          <div>
            <span class="rb-metric" id="rbDist">–<small>distance by road</small></span>
            <span class="rb-metric" id="rbTime">–<small>est. drive</small></span>
          </div>
          <button class="btn btn-outline-secondary btn-sm" style="margin-top:8px;" id="clearRoute">Clear route</button>
        </div>
        <h3 style="font-size:1rem; margin:0 0 10px;">Nearest Banks</h3>
        <div id="rankList"><p style="color:#888; font-size:.9rem;">Set your location to rank banks by distance.</p></div>
      </div>
      <div><div id="bl-map"></div></div>
    </div>

    <p class="text-muted" style="font-size:.82rem; margin-top:10px;"><?= count($banks) ?> registered blood bank(s) with coordinates.</p>
  </div>
</main>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
  const BANKS = <?= $banks_json ?: '[]' ?>;
  const KADUNA = [10.5222, 7.4383];

  const map = L.map('bl-map').setView(KADUNA, 11);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
  }).addTo(map);

  const esc = s => (s==null?'':String(s).replace(/[&<>"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c])));
  const bandColor = km => km<=5 ? '#2c8a4a' : (km<=15 ? '#e08a00' : '#c2172e');
  function haversine(a,b,c,d){const R=6371,r=x=>x*Math.PI/180;const dLat=r(c-a),dLon=r(d-b);
    const x=Math.sin(dLat/2)**2+Math.cos(r(a))*Math.cos(r(c))*Math.sin(dLon/2)**2;return R*2*Math.atan2(Math.sqrt(x),Math.sqrt(1-x));}

  const bankIcon = L.divIcon({className:'', iconSize:[30,30], iconAnchor:[15,30], html:
    '<div style="font-size:26px;color:#7a0000;line-height:1;"><i class="fas fa-hospital"></i></div>'});
  const bankMarkers = {};
  const bounds = [];
  BANKS.forEach(b => {
    const lat=parseFloat(b.latitude), lng=parseFloat(b.longitude);
    if(isNaN(lat)||isNaN(lng)) return;
    const m = L.marker([lat,lng], {icon:bankIcon}).addTo(map);
    m.bindPopup(`<b>${esc(b.name)}</b><br><span style="color:#666">${esc(b.address)}</span><br>
      Units in stock: <b>${esc(b.total_units)}</b><br>${esc(b.contact_phone)||''}
      <br><button onclick="routeTo(${b.bank_id})" style="margin-top:6px;background:#7a0000;color:#fff;border:none;padding:5px 10px;border-radius:6px;cursor:pointer;">Directions</button>`);
    m.on('click', ()=>selectBank(b.bank_id));
    bankMarkers[b.bank_id] = m;
    bounds.push([lat,lng]);
  });
  if (bounds.length) map.fitBounds(bounds, {padding:[50,50], maxZoom:12});

  let userLatLng = null, userMarker = null, routeLayer = null, connectorLayers = [];
  const rankList = document.getElementById('rankList');
  const locStatus = document.getElementById('locStatus');

  function clearConnectors(){ connectorLayers.forEach(l=>map.removeLayer(l)); connectorLayers=[]; }
  function clearRoute(){ if(routeLayer){map.removeLayer(routeLayer);routeLayer=null;} document.getElementById('routeBox').classList.remove('show'); }
  document.getElementById('clearRoute').onclick = clearRoute;

  function setUser(lat,lng,label){
    userLatLng = [lat,lng];
    if (userMarker) map.removeLayer(userMarker);
    userMarker = L.circleMarker(userLatLng,{radius:9,color:'#1a5fb4',fillColor:'#1a5fb4',fillOpacity:.7})
      .addTo(map).bindPopup(label||'You are here');
    locStatus.textContent = label || 'Location set';
    rankBanks();
  }

  function rankBanks(){
    if(!userLatLng){return;}
    clearConnectors();
    const ranked = BANKS.map(b=>{
      const lat=parseFloat(b.latitude),lng=parseFloat(b.longitude);
      if(isNaN(lat)||isNaN(lng))return null;
      return {...b, _km: haversine(userLatLng[0],userLatLng[1],lat,lng)};
    }).filter(Boolean).sort((x,y)=>x._km-y._km);

    // draw thin colored connectors from user to the nearest 3
    ranked.slice(0,3).forEach(b=>{
      const line = L.polyline([userLatLng,[parseFloat(b.latitude),parseFloat(b.longitude)]],
        {color:bandColor(b._km), weight:2, dashArray:'6,6', opacity:.7}).addTo(map);
      connectorLayers.push(line);
    });

    rankList.innerHTML = ranked.map((b,i)=>`
      <div class="rank-item" data-id="${b.bank_id}" onclick="selectBank(${b.bank_id})">
        <div class="rank-num" style="background:${bandColor(b._km)}">${i+1}</div>
        <div class="rank-body">
          <div class="rank-name">${esc(b.name)}</div>
          <div class="rank-meta">${esc(b.address)} · ${esc(b.total_units)} units</div>
          <div style="margin-top:5px;"><button onclick="event.stopPropagation();routeTo(${b.bank_id})" class="btn btn-danger btn-sm" style="padding:2px 10px;font-size:.78rem;">Directions</button></div>
        </div>
        <div class="dist-badge" style="color:${bandColor(b._km)}">${b._km.toFixed(1)} km</div>
      </div>`).join('');
  }

  function selectBank(id){
    document.querySelectorAll('.rank-item').forEach(el=>el.classList.toggle('active', +el.dataset.id===id));
    const m = bankMarkers[id]; if(m){ map.panTo(m.getLatLng()); m.openPopup(); }
  }

  // OSRM driving route, colored by distance band.
  async function routeTo(id){
    const b = BANKS.find(x=>+x.bank_id===+id); if(!b) return;
    if(!userLatLng){ locStatus.textContent='Set your location first.'; return; }
    selectBank(id);
    clearRoute();
    const [ulat,ulng]=userLatLng, blat=parseFloat(b.latitude), blng=parseFloat(b.longitude);
    const url=`https://router.project-osrm.org/route/v1/driving/${ulng},${ulat};${blng},${blat}?overview=full&geometries=geojson`;
    locStatus.textContent='Fetching route…';
    try{
      const res=await fetch(url); const data=await res.json();
      if(!data.routes||!data.routes.length){ locStatus.textContent='No route found.'; return; }
      const r=data.routes[0];
      const coords=r.geometry.coordinates.map(c=>[c[1],c[0]]); // lng,lat -> lat,lng
      const km=r.distance/1000, color=bandColor(km);
      routeLayer=L.polyline(coords,{color,weight:6,opacity:.85}).addTo(map);
      map.fitBounds(routeLayer.getBounds(),{padding:[40,40]});
      document.getElementById('rbDist').innerHTML=`${km.toFixed(1)} km<small>distance by road</small>`;
      document.getElementById('rbTime').innerHTML=`${Math.round(r.duration/60)} min<small>est. drive</small>`;
      document.getElementById('routeBox').classList.add('show');
      locStatus.textContent=`Route to ${b.name}`;
    }catch(e){ locStatus.textContent='Routing service unavailable — showing straight-line only.';
      routeLayer=L.polyline([userLatLng,[blat,blng]],{color:bandColor(haversine(ulat,ulng,blat,blng)),weight:4,dashArray:'8,8'}).addTo(map);
      map.fitBounds(routeLayer.getBounds(),{padding:[40,40]});
    }
  }
  window.routeTo=routeTo; window.selectBank=selectBank;

  document.getElementById('locateBtn').onclick = ()=>{
    locStatus.textContent='Locating…';
    map.locate({setView:true, maxZoom:13});
  };
  document.getElementById('kadunaBtn').onclick = ()=>{ map.setView(KADUNA,12); setUser(KADUNA[0],KADUNA[1],'Kaduna city centre'); };
  map.on('locationfound', e=> setUser(e.latlng.lat, e.latlng.lng, 'Your location'));
  map.on('locationerror', ()=>{ locStatus.textContent='Location blocked — using Kaduna centre.'; setUser(KADUNA[0],KADUNA[1],'Kaduna city centre'); });
</script>
</body>
</html>
