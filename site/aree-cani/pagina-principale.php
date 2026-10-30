<?php
// Pagina principale delle aree cani (inclusa da index.php)
$briciole = ['Home' => '/', 'Strumenti' => '/strumenti.html', 'Aree cani' => '/aree-cani/'];
$faq = [
  ['Che cos’è un’area cani?', 'È uno spazio pubblico, di solito recintato, dove i cani possono correre senza guinzaglio sotto la sorveglianza del proprietario. Si chiama anche area di sgambamento. Orari e regole li decide il Comune e sono scritti sul cartello all’ingresso.'],
  ['Come trovo l’area cani più vicina?', 'Premi “Cerca vicino a me” e consenti al browser di usare la tua posizione: ti mostriamo le aree nel raggio di 5 km, dalla più vicina. La posizione serve solo per la ricerca: viene inviata al servizio di mappe di OpenStreetMap per trovare le aree intorno a te, e noi non la salviamo.'],
  ['Da dove vengono i dati?', 'Da OpenStreetMap, la mappa libera fatta dai volontari, con licenza ODbL. Le pagine dei comuni si aggiornano ogni settimana. Se un’area manca, puoi aggiungerla tu su openstreetmap.org o segnalarcela.'],
  ['Le aree sono tutte recintate?', 'Non sempre. Quando su OpenStreetMap è indicato, lo scriviamo accanto all’area insieme a illuminazione, fontanella, fondo e orari.'],
  ['Nella mia città non c’è la pagina: perché?', 'Per ora abbiamo le pagine dei capoluoghi e dei comuni più grandi. Per tutti gli altri usa la ricerca “vicino a me”, che funziona in tutta Italia.'],
];
$ld = ac_ld_base($briciole);
$ld['@graph'][] = ['@type' => 'FAQPage', 'mainEntity' => array_map(function ($f) { return ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]]]; }, $faq)];
$ld['@graph'][] = ['@type' => 'WebApplication', 'name' => 'Aree cani vicino a me', 'applicationCategory' => 'TravelApplication', 'operatingSystem' => 'Web',
  'url' => AC_SITO . '/aree-cani/', 'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR'], 'publisher' => ['@id' => AC_SITO . '/#org']];

function ac_ancora($r) { return trim(preg_replace('/[^a-z]+/', '-', strtolower($r)), '-'); }
// Comuni per regione, regioni in ordine alfabetico, comuni in ordine alfabetico
$perRegione = [];
foreach ($comuni as $s => $x) $perRegione[$x[4]][$s] = $x;
ksort($perRegione);
foreach ($perRegione as &$lista) uasort($lista, function ($a, $b) { return strcmp($a[0], $b[0]); });
unset($lista);

ac_testa(['url' => '/aree-cani/', 'title' => 'Aree cani vicino a me: le aree di sgambamento in Italia sulla mappa | FurrFinder',
  'og_title' => 'Aree cani vicino a me: trova l’area di sgambamento più vicina',
  'description' => 'Trova l’area cani più vicina a te o cerca il tuo comune: le aree di sgambamento di oltre 300 città italiane sulla mappa, con recinzione, orari e indicazioni stradali. Gratis.',
  'indicizza' => true, 'ld' => $ld]);
?>
<header class="page-header">
  <div class="container">
    <?php ac_briciole($briciole); ?>
    <h1>Aree cani vicino a me: trova l’area di sgambamento più vicina</h1>
  </div>
</header>
<section class="section">
  <div class="container">
    <p class="lead">Le aree dove il tuo cane può correre libero, sulla mappa: cerca vicino a dove sei o scegli il tuo comune. Con recinzione, orari e indicazioni stradali quando sono indicati.</p>

    <div class="tool" id="vicino">
      <h2 style="font-size:1.4rem">Cerca vicino a me</h2>
      <p class="muted" style="margin-top:6px">Ti mostriamo le aree nel raggio di 5 km. Usiamo la tua posizione solo per questa ricerca e non la salviamo.</p>
      <div class="vol-actions" style="margin-top:14px">
        <button class="btn btn-primary" type="button" id="acVicino">Cerca vicino a me</button>
      </div>
      <p class="vol-note" id="acStato" aria-live="polite"></p>
      <div class="ac-map" id="mappaVicino" hidden></div>
      <ol class="ac-list" id="acRisultati"></ol>
    </div>

    <form class="tool" id="cercaComune" action="/aree-cani/" onsubmit="return false" role="search">
      <h2 style="font-size:1.4rem">Cerca il tuo comune</h2>
      <div class="tool-grid" style="margin-top:12px">
        <label>Comune<input type="text" id="acComune" list="acElenco" autocomplete="off" placeholder="Es. Bologna"></label>
      </div>
      <datalist id="acElenco"><?php foreach ($comuni as $s => $x) echo '<option value="' . h($x[0]) . ' (' . h($x[2]) . ')" data-s="' . h($s) . '">'; ?></datalist>
      <p class="vol-note" id="acNonTrovato" hidden>Questo comune non ha ancora una pagina: usa “Cerca vicino a me”, funziona in tutta Italia.</p>
    </form>

    <h2 style="margin-top:44px">Le città</h2>
    <nav class="chip-nav" aria-label="Regioni"><?php foreach ($perRegione as $reg => $l) echo '<a class="chip-link" href="#r-' . ac_ancora($reg) . '">' . h($reg) . '</a>'; ?></nav>
<?php foreach ($perRegione as $reg => $lista) { ?>
    <section class="hub-sez" id="r-<?= ac_ancora($reg) ?>">
      <h3 style="font-size:1.3rem"><?= h($reg) ?></h3>
      <ul class="ac-citta">
<?php foreach ($lista as $s => $x) { $dx = ac_leggi($x[1]); ?>        <li><a href="/aree-cani/<?= h($s) ?>"><?= h($x[0]) ?></a><?= $dx ? ' <span class="muted">' . count($dx['aree']) . '</span>' : '' ?></li>
<?php } ?>
      </ul>
    </section>
<?php } ?>

    <article class="prose" style="margin-top:44px">
      <h2>Prima di andare all’area cani</h2>
      <ul class="check">
        <li><strong>Controlla orari e regole</strong> sul cartello all’ingresso: li decide il Comune.</li>
        <li><strong>Porta sacchetti e acqua</strong>: non tutte le aree hanno la fontanella.</li>
        <li><strong>Microchip e dati aggiornati</strong>: se scappa, è il modo più rapido per ritrovarlo. Leggi la guida all’<a href="/anagrafe-canina.html">anagrafe canina</a>.</li>
        <li><strong>Vai via presto</strong> se il tuo cane è stanco o nervoso, o se nell’area c’è tensione tra i cani.</li>
      </ul>
      <p>Parti per qualche giorno e lo lasci a qualcuno? Prepara la <a href="/scheda-pet-sitter.html">scheda per il pet sitter</a>. Lo porti con te? Leggi le guide per viaggiare con il <a href="/cane-in-treno.html">cane in treno</a> e in <a href="/cane-gatto-in-aereo.html">aereo</a>.</p>
      <h2>Domande frequenti</h2>
      <div class="faq">
<?php foreach ($faq as $f) echo '        <details><summary>' . h($f[0]) . '</summary><p>' . h($f[1]) . "</p></details>\n"; ?>
      </div>
      <p class="micro ac-attr" style="margin-top:22px">Dati delle aree cani © <a href="https://www.openstreetmap.org/copyright" rel="noopener">contributori di OpenStreetMap</a>, licenza ODbL.</p>
    </article>
  </div>
</section>
<?php ac_coda(['vicino' => true]);
