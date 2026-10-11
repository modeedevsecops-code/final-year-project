<?php
// Blood Bank Locator (brief §5.5). Captures the user's location, ranks the
// registered blood_banks nearest-first (Haversine), shows them on a Leaflet map
// with a ranked list, and draws the shortest OSRM driving route (alternatives in
// grey) from the user's draggable pin to a selected bank, labelled with km and
// drive time. Proximity only; stock is managed separately.
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
    @media (max-width: 900px){
      .loc-grid{ grid-template-columns:1fr; }
      .loc-grid > div:last-child { order:-1; }          /* map first on phones */
      #bl-map { height:58vh; min-height:360px; }
      .rank-panel { height:auto; min-height:0; max-height:none; }
    }
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
    .lg-line { width:22px; height:0; border-top:5px solid #1a73e8; display:inline-block; border-radius:3px; }
    .lg-alt { width:22px; height:0; border-top:3px dashed #8a8f98; display:inline-block; }
    .you-pin { position:relative; width:34px; height:44px; }
    .you-pin i { font-size:34px; color:#1a73e8; filter:drop-shadow(0 2px 2px rgba(0,0,0,.35)); }
    .you-pin::after { content:''; position:absolute; left:50%; bottom:-4px; width:14px; height:14px; margin-left:-7px; border-radius:50%;
                      background:rgba(26,115,232,.35); animation:youPulse 1.8s ease-out infinite; }
    @keyframes youPulse { 0%{transform:scale(.6);opacity:1} 100%{transform:scale(2.6);opacity:0} }
    .dest-pin i { font-size:30px; color:#c2172e; filter:drop-shadow(0 2px 2px rgba(0,0,0,.35)); }
    .route-label { background:#1a73e8; color:#fff; border:none; border-radius:14px; padding:3px 10px; font-weight:700; font-size:.8rem; box-shadow:0 1px 4px rgba(0,0,0,.3); }
    .route-label::before { display:none; }
    .route-box .rb-note { font-size:.78rem; color:#666; margin-top:6px; }
    .route-steps { margin-top:8px; font-size:.8rem; }
    .route-steps summary { cursor:pointer; font-weight:600; color:#1a5fb4; }
    .route-steps ol { padding-left:18px; margin:6px 0 0; max-height:220px; overflow-y:auto; }
    .route-steps li { margin-bottom:4px; color:#333; }
    .route-steps li span { color:#888; }
    .map-hint { font-size:.8rem; color:#666; }
  </style>
</head>
<body>
<main class="main" id="top">
  <?php include 'inc/navbar.php'; ?>

  <div class="main-content">
    <div class="welcome-banner">
      <span class="badge-pill">BLOOD BANK LOCATOR</span>
      <h1>Find the Nearest Blood Bank</h1>
      <p>Drop your pin, and BloodLink ranks registered banks by distance and draws the shortest driving route, with kilometres and drive time. Powered by OpenStreetMap &amp; OSRM — no API key.</p>
    </div>

    <div class="legend2">
      <span><i class="fas fa-location-dot" style="color:#1a73e8;"></i> Your pin</span>
      <span><i class="fas fa-hospital" style="color:#7a0000;"></i> Blood bank</span>
      <span><i class="lg-line"></i> Shortest route</span>
      <span><i class="lg-alt"></i> Alternative route (click to use)</span>
      <span><i class="lg" style="background:#2c8a4a;"></i> ≤ 5 km</span>
      <span><i class="lg" style="background:#e08a00;"></i> 5–15 km</span>
      <span><i class="lg" style="background:#c2172e;"></i> &gt; 15 km</span>
    </div>

    <div style="margin-bottom:12px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
      <button id="locateBtn" class="btn btn-danger btn-sm"><i class="fas fa-location-crosshairs"></i> Use my location</button>
      <button id="kadunaBtn" class="btn btn-outline-secondary btn-sm">Use Kaduna city centre</button>
      <span class="map-hint"><i class="fas fa-hand-pointer"></i> or click anywhere on the map to drop your pin — drag it to adjust.</span>
      <span id="locStatus" style="color:#888; font-size:.85rem;"></span>
    </div>

    <div class="loc-grid">
      <div class="rank-panel">
        <div class="route-box" id="routeBox">
          <div style="font-weight:700; margin-bottom:6px;" id="rbTitle"></div>
          <div>
            <span class="rb-metric" id="rbDist">–<small>by road</small></span>
            <span class="rb-metric" id="rbTime">–<small>est. drive</small></span>
            <span class="rb-metric" id="rbAir">–<small>straight line</small></span>
          </div>
          <div class="rb-note" id="rbNote"></div>
          <details class="route-steps" id="rbStepsWrap"><summary>Turn-by-turn directions</summary><ol id="rbSteps"></ol></details>
          <div style="margin-top:8px; display:flex; gap:8px; flex-wrap:wrap;">
            <a class="btn btn-outline-primary btn-sm" id="rbExternal" target="_blank" rel="noopener">Open in Google Maps</a>
            <button class="btn btn-outline-secondary btn-sm" id="clearRoute">Clear route</button>
          </div>
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
    m.on('click', ()=>{ if (userLatLng) routeTo(b.bank_id); });
    bankMarkers[b.bank_id] = m;
    bounds.push([lat,lng]);
  });
  if (bounds.length) map.fitBounds(bounds, {padding:[50,50], maxZoom:12});

  let userLatLng = null, userMarker = null, accuracyCircle = null, connectorLayers = [];
  let routeGroup = L.layerGroup().addTo(map), destMarker = null, activeBankId = null, pickedBankId = null;
  const ROUTE_COLOR = '#1a73e8';
  const rankList = document.getElementById('rankList');
  const locStatus = document.getElementById('locStatus');
  const fmtKm = km => km < 1 ? `${Math.round(km*1000)} m` : `${km.toFixed(1)} km`;
  const fmtMin = s => { const m = Math.round(s/60); return m < 60 ? `${m} min` : `${Math.floor(m/60)} h ${m%60} min`; };

  const youIcon = L.divIcon({className:'', iconSize:[34,44], iconAnchor:[17,40], popupAnchor:[0,-36],
    html:'<div class="you-pin"><i class="fas fa-location-dot"></i></div>'});
  const destIcon = L.divIcon({className:'', iconSize:[30,40], iconAnchor:[15,38], popupAnchor:[0,-34],
    html:'<div class="dest-pin"><i class="fas fa-location-dot"></i></div>'});

  function clearConnectors(){ connectorLayers.forEach(l=>map.removeLayer(l)); connectorLayers=[]; }
  function clearRoute(){
    routeGroup.clearLayers();
    if (destMarker) { map.removeLayer(destMarker); destMarker = null; }
    activeBankId = null; pickedBankId = null;
    document.getElementById('routeBox').classList.remove('show');
    document.querySelectorAll('.rank-item').forEach(el=>el.classList.remove('active'));
    drawConnectors();
  }
  document.getElementById('clearRoute').onclick = clearRoute;

  function setUser(lat, lng, label, accuracy){
    userLatLng = [lat, lng];
    if (!userMarker) {
      userMarker = L.marker(userLatLng, {icon:youIcon, draggable:true, zIndexOffset:1000, title:'You are here'}).addTo(map);
      userMarker.on('dragend', e => { const p = e.target.getLatLng(); setUser(p.lat, p.lng, 'Pin moved'); });
    } else {
      userMarker.setLatLng(userLatLng);
    }
    userMarker.bindPopup(`<b>${esc(label || 'You are here')}</b><br><span style="color:#666">${lat.toFixed(5)}, ${lng.toFixed(5)}</span><br><small>Drag the pin to adjust.</small>`);
    if (accuracyCircle) { map.removeLayer(accuracyCircle); accuracyCircle = null; }
    if (accuracy) accuracyCircle = L.circle(userLatLng, {radius:accuracy, color:ROUTE_COLOR, weight:1, fillOpacity:.08}).addTo(map);
    locStatus.textContent = label || 'Location set';
    const ranked = rankBanks();
    // Keep a bank the user picked; otherwise route to the (new) nearest one.
    const target = pickedBankId || (ranked[0] && ranked[0].bank_id);
    if (target) routeTo(target, !!pickedBankId);
  }

  let lastRanked = [];
  function drawConnectors(){
    clearConnectors();
    if (!userLatLng || activeBankId) return;
    lastRanked.slice(0,3).forEach(b=>{
      connectorLayers.push(L.polyline([userLatLng,[parseFloat(b.latitude),parseFloat(b.longitude)]],
        {color:bandColor(b._km), weight:2, dashArray:'6,6', opacity:.7, interactive:false}).addTo(map));
    });
  }

  function rankBanks(){
    if(!userLatLng) return [];
    lastRanked = BANKS.map(b=>{
      const lat=parseFloat(b.latitude),lng=parseFloat(b.longitude);
      if(isNaN(lat)||isNaN(lng))return null;
      return {...b, _km: haversine(userLatLng[0],userLatLng[1],lat,lng)};
    }).filter(Boolean).sort((x,y)=>x._km-y._km);
    drawConnectors();
    rankList.innerHTML = lastRanked.map((b,i)=>`
      <div class="rank-item${+b.bank_id===+activeBankId?' active':''}" data-id="${b.bank_id}" onclick="routeTo(${b.bank_id})">
        <div class="rank-num" style="background:${bandColor(b._km)}">${i+1}</div>
        <div class="rank-body">
          <div class="rank-name">${esc(b.name)}</div>
          <div class="rank-meta">${esc(b.address)} · ${esc(b.total_units)} units</div>
          <div class="rank-meta" id="road-${b.bank_id}"></div>
          <div style="margin-top:5px;"><button onclick="event.stopPropagation();routeTo(${b.bank_id})" class="btn btn-danger btn-sm" style="padding:2px 10px;font-size:.78rem;">Directions</button></div>
        </div>
        <div class="dist-badge" style="color:${bandColor(b._km)}" title="Straight-line distance">${fmtKm(b._km)}</div>
      </div>`).join('');
    return lastRanked;
  }

  function selectBank(id){
    document.querySelectorAll('.rank-item').forEach(el=>el.classList.toggle('active', +el.dataset.id===+id));
    map.closePopup();
  }

  function stepText(st){
    const m = st.maneuver || {}; const road = st.name ? ` onto <b>${esc(st.name)}</b>` : '';
    const mod = m.modifier ? ` ${m.modifier}` : '';
    switch (m.type) {
      case 'depart': return `Head ${esc(m.modifier || 'out')}${st.name ? ' on <b>'+esc(st.name)+'</b>' : ''}`;
      case 'arrive': return 'Arrive at the blood bank';
      case 'roundabout': case 'rotary': return `At the roundabout take exit ${m.exit || ''}${road}`;
      case 'turn': case 'end of road': case 'fork': return `Turn${esc(mod)}${road}`;
      case 'merge': return `Merge${esc(mod)}${road}`;
      case 'new name': case 'continue': return `Continue${road || ' straight'}`;
      default: return `${esc((m.type||'Continue').replace(/^\w/,c=>c.toUpperCase()))}${esc(mod)}${road}`;
    }
  }

  // Draw one route as the highlighted "chosen" line, with alternatives in grey.
  function drawRoutes(routes, chosenIdx, b){
    routeGroup.clearLayers();
    routes.forEach((r, i) => {
      if (i === chosenIdx) return;
      const alt = L.polyline(r._coords, {color:'#8a8f98', weight:5, opacity:.75, dashArray:'8,8', bubblingMouseEvents:false}).addTo(routeGroup);
      alt.bindTooltip(`${fmtKm(r.distance/1000)} · ${fmtMin(r.duration)} — click to use`, {sticky:true});
      alt.on('click', () => drawRoutes(routes, i, b));
    });
    const r = routes[chosenIdx];
    L.polyline(r._coords, {color:'#ffffff', weight:11, opacity:.9, interactive:false}).addTo(routeGroup);   // casing
    const line = L.polyline(r._coords, {color:ROUTE_COLOR, weight:6, opacity:1, bubblingMouseEvents:false}).addTo(routeGroup);
    // OSRM snaps to the nearest road: link the pin and the bank to the line's ends.
    const walk = {color:ROUTE_COLOR, weight:3, opacity:.8, dashArray:'2,6', interactive:false};
    L.polyline([userLatLng, r._coords[0]], walk).addTo(routeGroup);
    L.polyline([r._coords[r._coords.length-1], [+b.latitude, +b.longitude]], walk).addTo(routeGroup);
    const mid = r._coords[Math.floor(r._coords.length/2)];
    L.tooltip({permanent:true, direction:'center', className:'route-label'})
      .setLatLng(mid).setContent(`${fmtKm(r.distance/1000)} · ${fmtMin(r.duration)}`).addTo(routeGroup);
    map.fitBounds(line.getBounds(), {padding:[50,50]});

    const km = r.distance/1000;
    const shortest = Math.min(...routes.map(x=>x.distance));
    document.getElementById('rbTitle').textContent = `Route to ${b.name}`;
    document.getElementById('rbDist').innerHTML = `${fmtKm(km)}<small>by road</small>`;
    document.getElementById('rbTime').innerHTML = `${fmtMin(r.duration)}<small>est. drive</small>`;
    document.getElementById('rbAir').innerHTML = `${fmtKm(haversine(userLatLng[0],userLatLng[1],+b.latitude,+b.longitude))}<small>straight line</small>`;
    document.getElementById('rbNote').textContent = routes.length > 1
      ? (r.distance === shortest ? `Shortest of ${routes.length} routes found.` : `Alternative route — ${fmtKm((r.distance-shortest)/1000)} longer than the shortest.`)
      : 'Shortest driving route.';
    const steps = (r.legs||[]).flatMap(l=>l.steps||[]);
    document.getElementById('rbSteps').innerHTML = steps.map(st=>`<li>${stepText(st)} <span>${st.distance ? '· '+fmtKm(st.distance/1000) : ''}</span></li>`).join('');
    document.getElementById('rbStepsWrap').style.display = steps.length ? '' : 'none';
    document.getElementById('rbExternal').href = `https://www.google.com/maps/dir/?api=1&origin=${userLatLng[0]},${userLatLng[1]}&destination=${b.latitude},${b.longitude}&travelmode=driving`;
    document.getElementById('routeBox').classList.add('show');
    const roadEl = document.getElementById('road-'+b.bank_id);
    if (roadEl) roadEl.innerHTML = `<b style="color:${ROUTE_COLOR}">${fmtKm(km)} by road · ${fmtMin(r.duration)}</b>`;
    if (destMarker) destMarker.setPopupContent(`<b>${esc(b.name)}</b><br>${fmtKm(km)} by road · ${fmtMin(r.duration)}`);
  }

  // OSRM driving routes (with alternatives); the shortest by distance is highlighted.
  let routeSeq = 0;
  // picked=false for automatic routing to the nearest bank.
  async function routeTo(id, picked = true){
    const b = BANKS.find(x=>+x.bank_id===+id); if(!b) return;
    if(!userLatLng){ locStatus.textContent='Set your location first — click the map or use the buttons above.'; return; }
    activeBankId = +id;
    if (picked) pickedBankId = +id;
    selectBank(id);
    clearConnectors();
    const [ulat,ulng]=userLatLng, blat=parseFloat(b.latitude), blng=parseFloat(b.longitude);
    if (destMarker) map.removeLayer(destMarker);
    destMarker = L.marker([blat,blng], {icon:destIcon, zIndexOffset:900}).addTo(map).bindPopup(`<b>${esc(b.name)}</b>`);
    const url=`https://router.project-osrm.org/route/v1/driving/${ulng},${ulat};${blng},${blat}?overview=full&geometries=geojson&alternatives=3&steps=true`;
    locStatus.textContent='Finding the shortest route…';
    const seq = ++routeSeq;
    try{
      const res=await fetch(url); const data=await res.json();
      if (seq !== routeSeq) return;   // a newer request superseded this one
      if(!data.routes||!data.routes.length) throw new Error('no route');
      const routes = data.routes.map(r => ({...r, _coords: r.geometry.coordinates.map(c=>[c[1],c[0]])}));
      let shortestIdx = 0;
      routes.forEach((r,i)=>{ if (r.distance < routes[shortestIdx].distance) shortestIdx = i; });
      drawRoutes(routes, shortestIdx, b);
      locStatus.textContent = `Shortest route to ${b.name}`;
    }catch(e){
      if (seq !== routeSeq) return;
      routeGroup.clearLayers();
      const km = haversine(ulat,ulng,blat,blng);
      const line = L.polyline([userLatLng,[blat,blng]],{color:ROUTE_COLOR,weight:4,dashArray:'8,8',interactive:false}).addTo(routeGroup);
      L.tooltip({permanent:true, direction:'center', className:'route-label'})
        .setLatLng([(ulat+blat)/2,(ulng+blng)/2]).setContent(`${fmtKm(km)} straight line`).addTo(routeGroup);
      map.fitBounds(line.getBounds(),{padding:[50,50]});
      document.getElementById('rbTitle').textContent = `Route to ${b.name}`;
      document.getElementById('rbDist').innerHTML = `–<small>by road</small>`;
      document.getElementById('rbTime').innerHTML = `–<small>est. drive</small>`;
      document.getElementById('rbAir').innerHTML = `${fmtKm(km)}<small>straight line</small>`;
      document.getElementById('rbNote').textContent = 'Road routing is unavailable right now — showing the straight-line distance.';
      document.getElementById('rbStepsWrap').style.display = 'none';
      document.getElementById('rbExternal').href = `https://www.google.com/maps/dir/?api=1&origin=${ulat},${ulng}&destination=${blat},${blng}&travelmode=driving`;
      document.getElementById('routeBox').classList.add('show');
      locStatus.textContent = 'Routing service unavailable — straight line shown.';
    }
  }
  window.routeTo=routeTo; window.selectBank=selectBank;

  document.getElementById('locateBtn').onclick = ()=>{
    locStatus.textContent='Locating…';
    map.locate({setView:false, enableHighAccuracy:true, timeout:10000});
  };
  document.getElementById('kadunaBtn').onclick = ()=> setUser(KADUNA[0],KADUNA[1],'Kaduna city centre');
  map.on('click', e => setUser(e.latlng.lat, e.latlng.lng, 'Pin dropped on the map'));
  map.on('locationfound', e=> setUser(e.latlng.lat, e.latlng.lng, 'Your location', e.accuracy));
  map.on('locationerror', ()=>{ locStatus.textContent='Location blocked — click the map to drop your pin, or use Kaduna centre.'; });
</script>
</body>
</html>
