<?php
// Pagina delle aree cani di un comune (inclusa da index.php: $rel è lo slug, $comuni l'elenco)
$c = $comuni[$rel];
list($nome, $istat, $sigla, $prov, $regione) = $c;
$d = ac_dati($rel);
$url = '/aree-cani/' . $rel;
$briciole = ['Home' => '/', 'Strumenti' => '/strumenti.html', 'Aree cani' => '/aree-cani/', $nome => $url];

if (!$d) {   // servizio non raggiungibile e nessuna copia locale
  header('Retry-After: 3600');
  ac_testa(['url' => $url, 'title' => 'Aree cani ' . ac_a_comune($nome) . ' | FurrFinder', 'description' => 'Le aree cani ' . ac_a_comune($nome) . '.', 'stato' => 503]);
  echo '<header class="page-header"><div class="container">'; ac_briciole($briciole);
  echo '<h1>Aree cani ' . h(ac_a_comune($nome)) . '</h1></div></header><section class="section"><div class="container"><p class="lead">In questo momento non riusciamo a caricare la mappa. Riprova tra qualche minuto.</p><p style="margin-top:18px"><a class="btn btn-primary" href="/aree-cani/">Cerca le aree vicino a te</a></p></div></section>';
  ac_coda(); exit;
}

$aree = $d['aree']; $n = count($aree);
$recintate = count(array_filter($aree, function ($a) { return $a['recinto']; }));
$conMq = array_filter($aree, function ($a) { return $a['mq'] >= 100 && ($a['nome'] !== '' || $a['via'] !== '' || $a['viaVicina'] !== ''); });
usort($conMq, function ($a, $b) { return $b['mq'] - $a['mq']; });
$grande = $conMq[0] ?? null;
$aC = ac_a_comune($nome);

// Altri comuni: prima la stessa provincia, poi la stessa regione
$vicini = [];
foreach ($comuni as $s => $x) if ($s !== $rel && $x[2] === $sigla) $vicini[$s] = $x;
foreach ($comuni as $s => $x) if ($s !== $rel && $x[4] === $regione && !isset($vicini[$s]) && count($vicini) < 12) $vicini[$s] = $x;

$titolo = $n ? "Aree cani $aC: " . ($n === 1 ? "l'area di sgambamento" : "$n aree di sgambamento") . ' sulla mappa' : "Aree cani $aC";
$descr = $n
  ? "Le $n aree cani $aC ($sigla) sulla mappa, con indirizzo" . ($recintate ? ', recinzione' : '') . ', orari quando indicati e indicazioni stradali. Dati OpenStreetMap aggiornati ogni settimana.'
  : "Aree cani e di sgambamento $aC ($sigla): non ne risultano ancora sulla mappa. Scopri come segnalarne una e trova quelle vicino a te.";

$ld = ac_ld_base($briciole);
if ($n) {
  $items = []; $i = 1;
  foreach ($aree as $a) $items[] = ['@type' => 'ListItem', 'position' => $i++, 'item' => ['@type' => 'Place', 'name' => ac_titolo($a),
    'address' => ['@type' => 'PostalAddress', 'addressLocality' => $nome, 'addressRegion' => $sigla, 'addressCountry' => 'IT'] + ($a['via'] ? ['streetAddress' => $a['via']] : []),
    'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $a['lat'], 'longitude' => $a['lon']]]];
  $ld['@graph'][] = ['@type' => 'ItemList', 'name' => "Aree cani $aC", 'numberOfItems' => $n, 'itemListElement' => $items];
}

ac_testa(['url' => $url, 'title' => $titolo . ' | FurrFinder', 'og_title' => $titolo, 'description' => $descr, 'indicizza' => $n > 0, 'ld' => $ld]);
?>
<header class="page-header">
  <div class="container">
    <?php ac_briciole($briciole); ?>
    <h1><?= h($titolo) ?></h1>
  </div>
</header>
<section class="section">
  <div class="container">
<?php if ($n) { ?>
    <p class="lead"><?= h(ucfirst($aC) . " ($sigla) ci sono $n " . ($n === 1 ? 'area cani mappata' : 'aree cani mappate') . ' su OpenStreetMap') ?><?= $recintate ? h(', ' . ($recintate === $n ? ($n === 1 ? 'recintata' : 'tutte recintate') : "di cui $recintate recintat" . ($recintate === 1 ? 'a' : 'e'))) : '' ?>.<?php if ($grande && $n > 1) { ?> La più grande è <?= h(ac_titolo($grande)) ?>, con circa <?= ac_num($grande['mq']) ?> m².<?php } ?> Le trovi qui sotto sulla mappa, con le indicazioni per arrivarci.</p>
    <div class="ac-map" id="mappa" role="region" aria-label="Mappa delle aree cani <?= h($aC) ?>"></div>
    <p class="micro ac-attr">Dati © <a href="https://www.openstreetmap.org/copyright" rel="noopener">contributori di OpenStreetMap</a>, licenza ODbL. Aggiornati il <?= h(ac_data($d['aggiornato'])) ?>.</p>

    <h2 style="margin-top:34px">Tutte le aree cani <?= h($aC) ?></h2>
    <ol class="ac-list">
<?php foreach ($aree as $i => $a) {
  $info = [];
  if ($a['mq'] >= 100) $info[] = 'circa ' . ac_num($a['mq']) . ' m²';
  if ($a['fondo']) $info[] = 'fondo in ' . $a['fondo'];
  $tag = [];
  if ($a['recinto']) $tag[] = 'Recintata';
  if ($a['luce'] === 'yes') $tag[] = 'Illuminata'; elseif ($a['luce'] === 'no') $tag[] = 'Non illuminata';
  if ($a['acqua'] === 'yes') $tag[] = 'Fontanella';
  if ($a['pagamento']) $tag[] = 'A pagamento';
?>
      <li class="card ac-item" id="area-<?= h($a['id']) ?>" data-i="<?= $i ?>">
        <h3><span class="ac-n"><?= $i + 1 ?></span><?= h(ac_titolo($a)) ?></h3>
<?php if ($a['nome'] && ($a['via'] || $a['viaVicina'])) { ?>        <p class="ac-via"><?= h($a['via'] ?: 'Vicino a ' . $a['viaVicina']) ?></p>
<?php } ?>
<?php if ($tag) { ?>        <div class="tag-row" style="margin:10px 0 0"><?php foreach ($tag as $t) echo '<span class="tag tag-tool">' . h($t) . '</span>'; ?></div>
<?php } ?>
<?php if ($info || $a['orari']) { ?>        <p class="ac-info"><?= h(ucfirst(implode(', ', $info))) ?><?= $info && $a['orari'] ? '. ' : '' ?><?= $a['orari'] ? 'Orari: ' . h(ac_orari($a['orari'])) : '' ?></p>
<?php } ?>
<?php if ($a['descr']) { ?>        <p class="ac-info"><?= h($a['descr']) ?></p>
<?php } ?>
        <p class="ac-links"><a href="https://www.google.com/maps/dir/?api=1&amp;destination=<?= $a['lat'] ?>,<?= $a['lon'] ?>" rel="noopener nofollow" target="_blank">Indicazioni stradali</a> · <a href="#mappa" data-vedi="<?= $i ?>">Vedi sulla mappa</a> · <a href="https://www.openstreetmap.org/<?= h($a['osm']) ?>" rel="noopener nofollow" target="_blank">Correggi i dati</a></p>
      </li>
<?php } ?>
    </ol>
<?php } else { ?>
    <p class="lead"><?= h("Su OpenStreetMap non risultano ancora aree cani $aC ($sigla).") ?> Può darsi che ci siano ma che nessuno le abbia ancora aggiunte alla mappa: se ne conosci una, aggiungila (te lo spieghiamo qui sotto) e comparirà qui entro una settimana.</p>
    <div class="ac-map" id="mappa" role="region" aria-label="Mappa <?= h($aC) ?>"></div>
    <p class="micro ac-attr">Mappa © <a href="https://www.openstreetmap.org/copyright" rel="noopener">contributori di OpenStreetMap</a>.</p>
<?php } ?>

    <div class="callout">
      <p><strong>Cerchi l’area più vicina a dove sei adesso?</strong> Apri la ricerca “vicino a me”: ti mostriamo le aree cani nel raggio di qualche chilometro, in ordine di distanza.</p>
      <div class="cta-row"><a class="btn btn-primary" href="/aree-cani/#vicino">Aree cani vicino a me</a><a class="btn btn-secondary" href="/scheda-pet-sitter.html">Scheda per il pet sitter</a></div>
    </div>

    <article class="prose" style="margin-top:34px">
      <h2>Le regole di buon senso nell’area cani</h2>
      <ul class="check">
        <li><strong>Leggi il cartello all’ingresso</strong>: orari e regole li decide il Comune e cambiano da un’area all’altra.</li>
        <li><strong>Entra e esci con il cane al guinzaglio</strong>, e chiudi sempre il cancello dietro di te.</li>
        <li><strong>Raccogli sempre i bisogni</strong>, anche dentro l’area.</li>
        <li><strong>Resta presente</strong>: il cane è sotto la tua responsabilità anche quando corre libero.</li>
        <li><strong>Niente cibo e niente giochi contesi</strong> se ci sono altri cani: sono la prima causa di litigi.</li>
        <li><strong>Se il tuo cane non gioca bene con gli altri</strong>, scegli gli orari meno affollati.</li>
      </ul>
      <h2>Manca un’area o i dati sono sbagliati?</h2>
      <p>Le aree cani di questa pagina vengono da <a href="https://www.openstreetmap.org/" rel="noopener">OpenStreetMap</a>, la mappa libera fatta dai volontari, e si aggiornano da sole ogni settimana. Per aggiungerne una basta un account gratuito: su openstreetmap.org premi “Modifica”, disegna l’area e scegli il tipo “Area per cani”. Se preferisci, scrivici a <a href="mailto:info@vetroom.it?subject=<?= rawurlencode('Area cani ' . $aC) ?>">info@vetroom.it</a> con il punto esatto e la aggiungiamo noi.</p>
      <p>Se il tuo cane si allontana mentre siete fuori, pubblica subito un <a href="/smarriti/">avviso di smarrimento</a> e prepara il <a href="/volantino-animale-smarrito.html">volantino</a>.</p>
    </article>

<?php if ($vicini) { ?>
    <h2 style="margin-top:34px">Aree cani nei comuni vicini</h2>
    <div class="chip-nav">
<?php foreach ($vicini as $s => $x) { $dx = ac_leggi($x[1]); ?>      <a class="chip-link" href="/aree-cani/<?= h($s) ?>"><?= h($x[0]) ?><?= $dx && count($dx['aree']) ? ' (' . count($dx['aree']) . ')' : '' ?></a>
<?php } ?>
    </div>
<?php } ?>
  </div>
</section>
<?php
$mappa = ['centro' => $d['centro'], 'aree' => array_map(function ($a) { return [$a['lat'], $a['lon'], ac_titolo($a), $a['id']]; }, $aree)];
ac_coda($mappa);

// Dati vecchi di oltre una settimana: li riscarica dopo aver mandato la pagina, senza far aspettare chi visita
if (ac_vecchio($d) && function_exists('fastcgi_finish_request')) { fastcgi_finish_request(); @set_time_limit(170); ac_aggiorna($rel); }
