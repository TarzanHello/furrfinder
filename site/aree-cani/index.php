<?php
// =============================================================
//  FURRFINDER · Aree cani
//  /aree-cani/                 → pagina principale: vicino a me, ricerca del comune, elenco dei comuni
//  /aree-cani/<comune>         → le aree cani di un comune, con mappa ed elenco
//  /aree-cani/sitemap.xml      → elenco delle pagine per Google
//  /aree-cani/aggiorna         → scarica i comuni che mancano (da aprire una volta dopo il caricamento)
// =============================================================
require __DIR__ . '/funzioni.php';

$path = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'] ?? '/aree-cani/', PHP_URL_PATH));
$rel = trim(preg_replace('#^/aree-cani/?#', '', $path), '/');
$comuni = ac_comuni();

// Sempre https://www.furrfinder.com (la regola del sito principale non vale dentro questa cartella, che ha un suo .htaccess)
$host = strtolower($_SERVER['HTTP_HOST'] ?? '');
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
if (strpos($host, 'furrfinder.com') !== false && ($host !== 'www.furrfinder.com' || !$https)) {
  header('Location: ' . AC_SITO . $path, true, 301); exit;
}
// Indirizzo con la barra finale o in maiuscolo → indirizzo giusto
if ($rel !== '' && (substr($path, -1) === '/' || $rel !== strtolower($rel))) {
  header('Location: ' . AC_SITO . '/aree-cani/' . strtolower($rel), true, 301); exit;
}

// ---------- sitemap ----------
if ($rel === 'sitemap.xml') {
  header('Content-Type: application/xml; charset=utf-8');
  header('Cache-Control: public, max-age=3600');
  echo '<?xml version="1.0" encoding="UTF-8"?>', "\n", '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
  echo '  <url><loc>', AC_SITO, "/aree-cani/</loc></url>\n";
  foreach ($comuni as $slug => $c) {
    $d = ac_leggi($c[1]);
    if ($d && count($d['aree']) > 0) echo '  <url><loc>', AC_SITO, '/aree-cani/', h($slug), '</loc><lastmod>', date('Y-m-d', (int)$d['aggiornato']), "</lastmod></url>\n";
  }
  echo "</urlset>\n";
  exit;
}

// ---------- aggiornamento dei dati (pagina di servizio) ----------
if ($rel === 'aggiorna') {
  @set_time_limit(170);
  header('Content-Type: text/html; charset=utf-8'); header('X-Robots-Tag: noindex, nofollow'); header('Cache-Control: no-store');
  $inizio = time(); $fatti = []; $errori = 0;
  foreach ($comuni as $slug => $c) {
    if (time() - $inizio > 100 || count($fatti) >= 4) break;
    if (!ac_vecchio(ac_leggi($c[1]))) continue;
    if (ac_aggiorna($slug)) $fatti[] = $c[0]; else { $errori++; if ($errori >= 2) break; }
  }
  $pronti = 0; foreach ($comuni as $c) if (!ac_vecchio(ac_leggi($c[1]))) $pronti++;
  $tot = count($comuni); $finito = $pronti >= $tot;
  echo '<!doctype html><html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex">';
  if (!$finito) echo '<meta http-equiv="refresh" content="' . ($errori ? 60 : 3) . '">';
  echo '<title>Aggiornamento aree cani</title><style>body{font-family:system-ui,sans-serif;max-width:640px;margin:40px auto;padding:0 16px;line-height:1.5}progress{width:100%;height:22px}</style></head><body>';
  echo '<h1>Aggiornamento delle aree cani</h1><progress max="' . $tot . '" value="' . $pronti . '"></progress>';
  echo '<p><strong>' . $pronti . ' comuni pronti su ' . $tot . '.</strong></p>';
  if ($fatti) echo '<p>Appena scaricati: ' . h(implode(', ', $fatti)) . '.</p>';
  if ($finito) echo '<p>Finito. Puoi chiudere questa pagina.</p>';
  elseif ($errori) echo '<p>Il servizio di OpenStreetMap è occupato: riprovo da solo tra un minuto. Lascia la pagina aperta.</p>';
  else echo '<p>Lascia questa pagina aperta: continua da sola finché non ha finito.</p>';
  echo '</body></html>';
  exit;
}

// ---------- pagina di un comune ----------
if ($rel !== '') {
  if (!isset($comuni[$rel])) {
    ac_testa(['url' => '/aree-cani/', 'title' => 'Comune non trovato | FurrFinder', 'description' => 'Questa pagina non esiste.', 'stato' => 404]);
    echo '<section class="section"><div class="container"><h1>Non abbiamo ancora questa pagina</h1><p class="lead" style="margin-top:14px">Cerca il comune o le aree cani vicino a te.</p><p style="margin-top:18px"><a class="btn btn-primary" href="/aree-cani/">Cerca le aree cani</a></p></div></section>';
    ac_coda(); exit;
  }
  require __DIR__ . '/pagina-comune.php';
  exit;
}

// ---------- pagina principale ----------
require __DIR__ . '/pagina-principale.php';
