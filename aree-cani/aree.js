/* FurrFinder · aree cani: mappa della pagina del comune, ricerca "vicino a me" e ricerca del comune */
(function () {
  'use strict';
  var D = window.AREE_CANI || {};
  var $ = function (i) { return document.getElementById(i); };
  var OVERPASS = ['https://overpass-api.de/api/interpreter', 'https://overpass.kumi.systems/api/interpreter'];
  var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };

  function tiles(m) {
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>' }).addTo(m);
  }
  function icona(n) { return L.divIcon({ className: 'ac-pin', html: '<span>' + n + '</span>', iconSize: [30, 30], iconAnchor: [15, 15], popupAnchor: [0, -14] }); }
  function dirLink(lat, lon) { return 'https://www.google.com/maps/dir/?api=1&destination=' + lat + ',' + lon; }

  // ---------- pagina del comune ----------
  function mappaComune() {
    var box = $('mappa'); if (!box || !window.L || !D.aree) return;
    if (!D.aree.length && !D.centro) { box.hidden = true; return; }
    var m = L.map(box, { scrollWheelZoom: false }); tiles(m);
    var markers = D.aree.map(function (a, i) {
      return L.marker([a[0], a[1]], { icon: icona(i + 1), title: a[2] }).addTo(m)
        .bindPopup('<b>' + esc(a[2]) + '</b><br><a href="' + dirLink(a[0], a[1]) + '" target="_blank" rel="noopener">Indicazioni stradali</a> · <a href="#area-' + esc(a[3]) + '">Dettagli</a>');
    });
    if (markers.length) m.fitBounds(L.featureGroup(markers).getBounds().pad(0.08), { maxZoom: 15 });
    else m.setView(D.centro, 13);
    [].forEach.call(document.querySelectorAll('[data-vedi]'), function (a) {
      a.addEventListener('click', function (e) {
        e.preventDefault(); var mk = markers[+a.getAttribute('data-vedi')]; if (!mk) return;
        box.scrollIntoView({ behavior: 'smooth', block: 'center' }); m.setView(mk.getLatLng(), 16); mk.openPopup();
      });
    });
  }

  // ---------- vicino a me ----------
  function distanza(a, b, c, d) {
    var r = Math.PI / 180, x = (d - b) * r * Math.cos((a + c) / 2 * r), y = (c - a) * r; return Math.sqrt(x * x + y * y) * 6371000;
  }
  function orari(o) {
    if (o.trim() === '24/7') return 'sempre aperta';
    var T = { Mo: 'lun', Tu: 'mar', We: 'mer', Th: 'gio', Fr: 'ven', Sa: 'sab', Su: 'dom', PH: 'festivi', off: 'chiusa', closed: 'chiusa', sunrise: 'alba', sunset: 'tramonto' };
    return o.replace(/\b(Mo|Tu|We|Th|Fr|Sa|Su|PH|off|closed|sunrise|sunset)\b/g, function (k) { return T[k]; }).replace(/\s*;\s*/g, '; ');
  }
  function metri(m) { return m < 1000 ? 'a ' + Math.round(m / 10) * 10 + ' m' : 'a ' + (m / 1000).toLocaleString('it-IT', { maximumFractionDigits: 1 }) + ' km'; }
  function cerca(lat, lon, raggio) {
    var q = '[out:json][timeout:25];nwr["leisure"="dog_park"](around:' + raggio + ',' + lat + ',' + lon + ');out tags center;';
    var prova = function (i) {
      return fetch(OVERPASS[i], { method: 'POST', body: 'data=' + encodeURIComponent(q), headers: { 'Content-Type': 'application/x-www-form-urlencoded' } })
        .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
        .catch(function (e) { if (i + 1 < OVERPASS.length) return prova(i + 1); throw e; });
    };
    return prova(0).then(function (j) {
      return (j.elements || []).filter(function (e) { var t = e.tags || {}; return t.access !== 'private' && t.access !== 'no'; }).map(function (e) {
        var t = e.tags || {}, la = e.lat != null ? e.lat : e.center && e.center.lat, lo = e.lon != null ? e.lon : e.center && e.center.lon;
        var via = t['addr:street'] ? t['addr:street'] + (t['addr:housenumber'] ? ' ' + t['addr:housenumber'] : '') : '';
        var nome = t.name ? (/\b(cani|cane|dog|sgambamento|cinofil)/i.test(t.name) ? t.name : 'Area cani ' + t.name) : (via ? 'Area cani in ' + via : 'Area cani');
        var tag = [];
        if (t.fenced === 'yes' || t.barrier === 'fence' || t.fence_type) tag.push('Recintata');
        if (t.lit === 'yes') tag.push('Illuminata');
        if (t.drinking_water === 'yes') tag.push('Fontanella');
        if (t.fee === 'yes') tag.push('A pagamento');
        return { lat: la, lon: lo, nome: nome, tag: tag, orari: t.opening_hours || '', d: distanza(lat, lon, la, lo), osm: e.type + '/' + e.id };
      }).filter(function (a) { return a.lat != null; }).sort(function (a, b) { return a.d - b.d; });
    });
  }
  var mv = null;
  function mostra(lat, lon, aree, raggio) {
    var box = $('mappaVicino'), out = $('acRisultati'); box.hidden = false;
    if (!mv) { mv = L.map(box, { scrollWheelZoom: false }); tiles(mv); } else mv.eachLayer(function (l) { if (!(l instanceof L.TileLayer)) mv.removeLayer(l); });
    var tu = L.circleMarker([lat, lon], { radius: 9, color: '#fff', weight: 3, fillColor: '#0EA5E9', fillOpacity: 1 }).addTo(mv).bindPopup('Sei qui');
    var mk = aree.slice(0, 40).map(function (a, i) {
      return L.marker([a.lat, a.lon], { icon: icona(i + 1), title: a.nome }).addTo(mv)
        .bindPopup('<b>' + esc(a.nome) + '</b><br>' + metri(a.d) + ' · <a href="' + dirLink(a.lat, a.lon) + '" target="_blank" rel="noopener">Indicazioni</a>');
    });
    mv.fitBounds(L.featureGroup(mk.concat([tu])).getBounds().pad(0.1), { maxZoom: 15 });
    out.innerHTML = aree.slice(0, 40).map(function (a, i) {
      return '<li class="card ac-item"><h3><span class="ac-n">' + (i + 1) + '</span>' + esc(a.nome) + '</h3><p class="ac-via">' + metri(a.d) + ' in linea d’aria</p>' +
        (a.tag.length ? '<div class="tag-row" style="margin:10px 0 0">' + a.tag.map(function (t) { return '<span class="tag tag-tool">' + t + '</span>'; }).join('') + '</div>' : '') +
        (a.orari ? '<p class="ac-info">Orari: ' + esc(orari(a.orari)) + '</p>' : '') +
        '<p class="ac-links"><a href="' + dirLink(a.lat, a.lon) + '" target="_blank" rel="noopener nofollow">Indicazioni stradali</a> · <a href="https://www.openstreetmap.org/' + esc(a.osm) + '" target="_blank" rel="noopener nofollow">Correggi i dati</a></p></li>';
    }).join('');
    $('acStato').textContent = aree.length ? 'Abbiamo trovato ' + (aree.length === 1 ? 'un’area cani' : aree.length + ' aree cani') + ' nel raggio di ' + raggio / 1000 + ' km' + (aree.length > 40 ? ': ecco le 40 più vicine.' : '.') : '';
  }
  function vicino() {
    var btn = $('acVicino'), st = $('acStato'); if (!btn) return;
    btn.addEventListener('click', function () {
      if (!navigator.geolocation) { st.textContent = 'Il tuo browser non può usare la posizione: cerca il tuo comune qui sotto.'; return; }
      btn.disabled = true; st.textContent = 'Cerco la tua posizione…';
      navigator.geolocation.getCurrentPosition(function (p) {
        var lat = p.coords.latitude, lon = p.coords.longitude;
        st.textContent = 'Cerco le aree cani vicino a te…';
        cerca(lat, lon, 5000).then(function (a) {
          if (a.length) return mostra(lat, lon, a, 5000);
          st.textContent = 'Nessuna area entro 5 km: allargo la ricerca a 15 km…';
          return cerca(lat, lon, 15000).then(function (b) {
            if (b.length) mostra(lat, lon, b, 15000);
            else st.innerHTML = 'Non abbiamo trovato aree cani entro 15 km. Se ne conosci una, puoi aggiungerla su <a href="https://www.openstreetmap.org/" target="_blank" rel="noopener">OpenStreetMap</a> o scriverci a <a href="mailto:info@vetroom.it">info@vetroom.it</a>.';
          });
        }).catch(function () { st.textContent = 'Il servizio delle mappe è occupato. Riprova tra un minuto.'; })
          .then(function () { btn.disabled = false; });
      }, function () { btn.disabled = false; st.textContent = 'Non abbiamo il permesso di usare la posizione. Puoi darlo dalle impostazioni del browser, oppure cerca il tuo comune qui sotto.'; },
      { enableHighAccuracy: false, timeout: 15000, maximumAge: 300000 });
    });
  }

  // ---------- ricerca del comune ----------
  function comune() {
    var inp = $('acComune'), dl = $('acElenco'); if (!inp || !dl) return;
    var norm = function (s) { return s.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').replace(/\s*\([a-z]{2}\)\s*$/, '').trim(); };
    var vai = function (fine) {
      var v = norm(inp.value); if (!v) return;
      var o = [].filter.call(dl.options, function (x) { return norm(x.value) === v; })[0];
      if (o) location.href = '/aree-cani/' + o.getAttribute('data-s');
      else if (fine) $('acNonTrovato').hidden = false;
    };
    inp.addEventListener('change', function () { vai(true); });
    inp.addEventListener('input', function () { $('acNonTrovato').hidden = true; if (/\([A-Z]{2}\)$/.test(inp.value)) vai(false); });
    $('cercaComune').addEventListener('submit', function () { vai(true); });
  }

  function avvia() { if (!window.L) return; mappaComune(); vicino(); }
  comune();
  if (window.L) avvia(); else window.addEventListener('load', avvia);
  if (location.hash === '#vicino' && $('acVicino')) setTimeout(function () { $('vicino').scrollIntoView(); }, 50);
})();
