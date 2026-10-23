<?php
// =============================================================
//  FURRFINDER · Aree cani per comune — strumenti comuni
//  I dati vengono da OpenStreetMap (licenza ODbL) tramite il servizio Overpass.
//  Ogni comune viene scaricato al massimo una volta alla settimana e salvato in una copia locale.
//  Compatibile con PHP 7.4 e successivi.
// =============================================================
require_once __DIR__ . '/layout.php';

define('AC_SITO', 'https://www.furrfinder.com');
define('AC_GIORNI', 7);                 // dopo quanti giorni i dati di un comune si riscaricano
define('AC_SERVER', [                   // servizi Overpass da provare, in ordine
  'https://overpass-api.de/api/interpreter',
  'https://overpass.kumi.systems/api/interpreter',
]);

if (!function_exists('h')) {
  function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}

function ac_comuni() { static $c = null; if ($c === null) $c = require __DIR__ . '/comuni.php'; return $c; }

// ---------- copia locale ----------
function ac_cache_dir() {
  $dir = __DIR__ . '/cache';
  if (!is_dir($dir)) @mkdir($dir, 0755);
  if (!is_writable($dir)) $dir = sys_get_temp_dir();
  return rtrim($dir, '/');
}
function ac_file($istat) { return ac_cache_dir() . '/aree_' . preg_replace('/\D/', '', $istat) . '.json'; }
function ac_leggi($istat) {
  $f = ac_file($istat);
  if (!is_file($f)) return null;
  $j = json_decode((string)file_get_contents($f), true);
  return is_array($j) && isset($j['aree']) ? $j : null;
}
function ac_vecchio($d) { return !$d || (time() - (int)$d['aggiornato']) > AC_GIORNI * 86400; }

// ---------- scaricamento ----------
function ac_overpass($query) {
  foreach (AC_SERVER as $url) {
    $body = 'data=' . rawurlencode($query);
    $headers = ['Content-Type: application/x-www-form-urlencoded', 'User-Agent: FurrFinder/1.0 (+https://www.furrfinder.com; info@vetroom.it)'];
    if (function_exists('curl_init')) {
      $c = curl_init($url);
      curl_setopt_array($c, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $body, CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 150, CURLOPT_CONNECTTIMEOUT => 10]);
      $out = curl_exec($c); $code = curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c);
    } else {
      $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => implode("\r\n", $headers), 'content' => $body, 'timeout' => 150, 'ignore_errors' => true]]);
      $out = @file_get_contents($url, false, $ctx);
      $code = 0; if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) $code = (int)$m[1];
    }
    if ($code === 200 && $out) {
      $j = json_decode($out, true);
      if (is_array($j) && isset($j['elements']) && empty($j['remark'])) return $j['elements'];
    }
  }
  return null;
}

function ac_query($area) {
  // Strade escluse quando cerchiamo la via più vicina all'area
  $no = '^(motorway|motorway_link|trunk|trunk_link|service|footway|path|cycleway|steps|track|bridleway|corridor|proposed|construction|platform)$';
  return '[out:json][timeout:140];' . $area .
    'rel(pivot.c);out tags center;' .
    'nwr["leisure"="dog_park"](area.c)->.tutte;' .
    'foreach.tutte->.p(.p out geom;way(around.p:80)["highway"]["name"]["highway"!~"' . $no . '"];out tags 1;);';
}

// Scarica le aree di un comune. Restituisce i dati pronti, oppure null se il servizio non risponde.
function ac_scarica($istat, $nome) {
  $el = ac_overpass(ac_query('area["boundary"="administrative"]["admin_level"="8"]["ref:ISTAT"="' . $istat . '"]->.c;'));
  if ($el === null) return null;
  $d = ac_analizza($el);
  if (!$d['trovato']) {   // codice ISTAT non trovato su OpenStreetMap: proviamo con il nome
    $re = '^' . preg_quote(str_replace('"', '', $nome), '/') . '( |$)';
    $el = ac_overpass(ac_query('rel["boundary"="administrative"]["admin_level"="8"]["ref:ISTAT"]["name"~"' . str_replace('\\', '\\\\', $re) . '"];map_to_area->.c;'));
    if ($el === null) return null;
    $d = ac_analizza($el);
  }
  $d['aggiornato'] = time();
  return $d;
}

// Aggiorna la copia locale di un comune (una richiesta alla volta per comune). true se ci è riuscito.
function ac_aggiorna($slug) {
  $c = ac_comuni()[$slug] ?? null; if (!$c) return false;
  $lock = @fopen(ac_file($c[1]) . '.lock', 'c');
  if ($lock && !flock($lock, LOCK_EX | LOCK_NB)) { fclose($lock); return false; }   // lo sta già facendo un'altra visita
  $d = ac_scarica($c[1], $c[0]);
  if ($d) @file_put_contents(ac_file($c[1]), json_encode($d, JSON_UNESCAPED_UNICODE), LOCK_EX);
  if ($lock) { flock($lock, LOCK_UN); fclose($lock); }
  return (bool)$d;
}

// Dati di un comune: copia locale se c'è, altrimenti li scarica subito.
function ac_dati($slug) {
  $c = ac_comuni()[$slug];
  $d = ac_leggi($c[1]);
  if (!$d) { ac_aggiorna($slug); $d = ac_leggi($c[1]); }
  return $d;
}

// ---------- analisi dei dati di OpenStreetMap ----------
function ac_mq($pts) {   // superficie approssimata di un poligono [[lat,lon],...] in metri quadrati
  $n = count($pts); if ($n < 4) return 0;
  $lat0 = deg2rad($pts[0][0]); $s = 0;
  for ($i = 0; $i < $n - 1; $i++) {
    $x1 = $pts[$i][1] * 111320 * cos($lat0); $y1 = $pts[$i][0] * 110540;
    $x2 = $pts[$i + 1][1] * 111320 * cos($lat0); $y2 = $pts[$i + 1][0] * 110540;
    $s += $x1 * $y2 - $x2 * $y1;
  }
  return abs($s) / 2;
}
function ac_punti($g) { $o = []; foreach ((array)$g as $p) if (isset($p['lat'], $p['lon'])) $o[] = [$p['lat'], $p['lon']]; return $o; }

function ac_analizza($elementi) {
  $centro = null; $trovato = false; $aree = []; $ultima = null;
  $FONDO = ['grass' => 'prato', 'dirt' => 'terra', 'ground' => 'terra', 'earth' => 'terra', 'gravel' => 'ghiaia', 'fine_gravel' => 'ghiaietto',
    'sand' => 'sabbia', 'woodchips' => 'trucioli di legno', 'artificial_turf' => 'erba sintetica', 'compacted' => 'terra battuta', 'unpaved' => 'sterrato',
    'paved' => 'pavimentato', 'asphalt' => 'asfalto', 'concrete' => 'cemento', 'paving_stones' => 'autobloccanti', 'mud' => 'terra'];
  foreach ($elementi as $e) {
    $t = $e['tags'] ?? [];
    if (($e['type'] ?? '') === 'relation' && ($t['boundary'] ?? '') === 'administrative' && !isset($t['leisure'])) {
      $trovato = true; if (isset($e['center'])) $centro = [round($e['center']['lat'], 5), round($e['center']['lon'], 5)];
      continue;
    }
    if (($t['leisure'] ?? '') === 'dog_park') {
      $ultima = null;
      if (in_array($t['access'] ?? '', ['private', 'no'], true)) continue;
      $pts = []; $mq = 0;
      if ($e['type'] === 'node') $pts = [[$e['lat'], $e['lon']]];
      elseif ($e['type'] === 'way') { $pts = ac_punti($e['geometry'] ?? []); $mq = ac_mq($pts); }
      else foreach ($e['members'] ?? [] as $m) {
        $mp = ac_punti($m['geometry'] ?? []); if (!$mp) continue;
        $pts = array_merge($pts, $mp);
        $a = ac_mq($mp); $mq += (($m['role'] ?? '') === 'inner') ? -$a : $a;
      }
      if (!$pts) continue;
      if (count($pts) > 1 && $pts[0] === end($pts)) array_pop($pts);
      $lat = array_sum(array_column($pts, 0)) / count($pts); $lon = array_sum(array_column($pts, 1)) / count($pts);
      $recinto = ($t['fenced'] ?? '') === 'yes' || ($t['barrier'] ?? '') === 'fence' || isset($t['fence_type']);
      $descr = trim((string)($t['description'] ?? ''));
      $via = trim(($t['addr:street'] ?? '') . (isset($t['addr:street'], $t['addr:housenumber']) ? ' ' . $t['addr:housenumber'] : ''));
      $id = substr($e['type'], 0, 1) . $e['id'];
      $aree[$id] = [
        'id' => $id, 'osm' => $e['type'] . '/' . $e['id'],
        'nome' => trim((string)($t['name'] ?? '')), 'via' => $via, 'viaVicina' => '',
        'lat' => round($lat, 6), 'lon' => round($lon, 6), 'mq' => (int)round(max(0, $mq) / 10) * 10,
        'recinto' => $recinto, 'luce' => $t['lit'] ?? '', 'acqua' => $t['drinking_water'] ?? '',
        'orari' => trim((string)($t['opening_hours'] ?? '')), 'fondo' => $FONDO[$t['surface'] ?? ''] ?? '',
        'pagamento' => ($t['fee'] ?? '') === 'yes', 'descr' => mb_strlen($descr) <= 160 ? $descr : '',
      ];
      $ultima = $id;
      continue;
    }
    if (isset($t['highway'], $t['name']) && $ultima && $aree[$ultima]['viaVicina'] === '') $aree[$ultima]['viaVicina'] = $t['name'];
  }
  $aree = array_values($aree);
  // Prima quelle con un nome, poi con l'indirizzo, poi le altre; in ordine alfabetico
  $peso = function ($a) { return $a['nome'] !== '' ? 0 : ($a['via'] !== '' ? 1 : ($a['viaVicina'] !== '' ? 2 : 3)); };
  usort($aree, function ($a, $b) use ($peso) { return ($peso($a) - $peso($b)) ?: strcasecmp(ac_titolo($a), ac_titolo($b)); });
  return ['trovato' => $trovato, 'centro' => $centro, 'aree' => $aree];
}

// ---------- testi ----------
function ac_titolo($a) {
  if ($a['nome'] !== '') return preg_match('/\b(cani|cane|dog|sgambamento|cinofil)/iu', $a['nome']) ? $a['nome'] : 'Area cani ' . $a['nome'];
  if ($a['via'] !== '') return 'Area cani in ' . $a['via'];
  if ($a['viaVicina'] !== '') return 'Area cani vicino a ' . $a['viaVicina'];
  return 'Area cani senza nome';
}
function ac_orari($o) {   // orari di OpenStreetMap in italiano ("Mo-Fr 08:00-20:00" → "lun-ven 08:00-20:00")
  if (trim($o) === '24/7') return 'sempre aperta';
  $o = strtr($o, ['Mo' => 'lun', 'Tu' => 'mar', 'We' => 'mer', 'Th' => 'gio', 'Fr' => 'ven', 'Sa' => 'sab', 'Su' => 'dom', 'PH' => 'festivi', 'off' => 'chiusa', 'closed' => 'chiusa', 'sunrise' => 'alba', 'sunset' => 'tramonto']);
  return preg_replace('/\s*;\s*/', '; ', $o);
}
function ac_num($n) { return number_format($n, 0, ',', '.'); }
function ac_data($ts) {
  $mesi = ['', 'gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio', 'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'];
  $d = new DateTime('@' . (int)$ts); $d->setTimezone(new DateTimeZone('Europe/Rome'));
  return $d->format('j') . ' ' . $mesi[(int)$d->format('n')] . ' ' . $d->format('Y');
}
function ac_a_comune($nome) { // "a Roma", "ad Ancona", "all'Aquila"
  if (stripos($nome, "L'") === 0) return "all'" . substr($nome, 2);
  if (stripos($nome, 'La ') === 0) return 'alla ' . substr($nome, 3);
  if (stripos($nome, 'Il ') === 0) return 'al ' . substr($nome, 3);
  if (stripos($nome, 'Lo ') === 0) return 'allo ' . substr($nome, 3);
  if (stripos($nome, 'I ') === 0) return 'ai ' . substr($nome, 2);
  if (stripos($nome, 'Le ') === 0) return 'alle ' . substr($nome, 3);
  return (preg_match('/^[AaÀà]/u', $nome) ? 'ad ' : 'a ') . $nome;
}

// ---------- pagina ----------
function ac_testa($o) {
  $canon = AC_SITO . $o['url'];
  header('Content-Type: text/html; charset=utf-8');
  if (!empty($o['stato'])) http_response_code($o['stato']);
  if (empty($o['indicizza'])) header('X-Robots-Tag: noindex, follow');
  header('Cache-Control: public, max-age=3600');
  ?><!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= h($o['title']) ?></title>
<meta name="description" content="<?= h($o['description']) ?>">
<link rel="canonical" href="<?= h($canon) ?>">
<meta name="robots" content="<?= empty($o['indicizza']) ? 'noindex, follow' : 'index, follow, max-image-preview:large' ?>">
<meta name="theme-color" content="#F4F8FC">
<meta name="color-scheme" content="only light">
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32.png">
<link rel="apple-touch-icon" href="/assets/apple-touch-icon.png">
<link rel="manifest" href="/manifest.webmanifest">
<meta property="og:type" content="website">
<meta property="og:locale" content="it_IT">
<meta property="og:site_name" content="FurrFinder">
<meta property="og:title" content="<?= h($o['og_title'] ?? $o['title']) ?>">
<meta property="og:description" content="<?= h($o['description']) ?>">
<meta property="og:url" content="<?= h($canon) ?>">
<meta property="og:image" content="<?= AC_SITO ?>/assets/og-image.png">
<meta name="twitter:card" content="summary_large_image">
<link rel="preload" href="/assets/fonts/nunito-latin-900-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/source-sans-3-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="/assets/site.css?v=<?= AC_CSS_V ?>">
<link rel="stylesheet" href="/smarriti/vendor/leaflet-1.9.4/leaflet.css">
<link rel="stylesheet" href="/aree-cani/aree.css?v=<?= AC_CSS_V ?>">
<?php if (!empty($o['ld'])) { ?><script type="application/ld+json"><?= json_encode($o['ld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?></script>
<?php } ?>
</head>
<body>
<?php ac_intestazione(); ?>
<main id="contenuto">
<?php
}
function ac_coda($dati = null) { ?>
</main>
<?php ac_footer(); ?>
<script src="/assets/site.js?v=<?= AC_CSS_V ?>" defer></script>
<?php if ($dati !== null) { ?><script>window.AREE_CANI=<?= json_encode($dati, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;</script>
<?php } ?>
<script src="/smarriti/vendor/leaflet-1.9.4/leaflet.js" defer></script>
<script src="/aree-cani/aree.js?v=<?= AC_CSS_V ?>" defer></script>
</body>
</html>
<?php }

function ac_ld_base($briciole) {
  $org = ['@type' => 'Organization', '@id' => AC_SITO . '/#org', 'name' => 'FurrFinder', 'legalName' => 'AF&B S.r.l.s.', 'url' => AC_SITO . '/',
    'logo' => AC_SITO . '/assets/logo.png', 'email' => 'info@vetroom.it', 'vatID' => 'IT13698751008', 'sameAs' => ['https://www.vetroom.it/']];
  $li = []; $i = 1;
  foreach ($briciole as $nome => $url) $li[] = ['@type' => 'ListItem', 'position' => $i++, 'name' => $nome, 'item' => AC_SITO . $url];
  return ['@context' => 'https://schema.org', '@graph' => [$org, ['@type' => 'BreadcrumbList', 'itemListElement' => $li]]];
}
function ac_briciole($briciole) {
  echo '<nav class="breadcrumb" aria-label="Percorso"><ol>';
  $n = count($briciole); $i = 0;
  foreach ($briciole as $nome => $url) { $i++; echo $i < $n ? '<li><a href="' . h($url) . '">' . h($nome) . '</a></li>' : '<li aria-current="page">' . h($nome) . '</li>'; }
  echo '</ol></nav>';
}
