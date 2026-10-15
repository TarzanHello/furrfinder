<?php
// Intestazione e footer del sito, copiati dalle pagine statiche di FurrFinder.
// Se cambi il menu o il footer del sito, cambiali anche qui.
define('AC_CSS_V', '2026-10-15');
function ac_intestazione() { ?>
<a class="skip" href="#contenuto">Vai al contenuto</a>

<header class="site-header" id="header">
  <div class="wrap">
    <div class="bar">
      <a class="brand" href="/" aria-label="FurrFinder, home"><img src="/assets/logo-mark.png" alt="" width="38" height="38">FurrFinder</a>
      <nav class="nav" aria-label="Principale">
        <a href="/come-funziona.html">L’app</a>
        <a href="/strumenti.html" aria-current="page">Strumenti</a>
        <a href="/guide.html">Guide</a>
        <a href="/smarriti/">Animali smarriti</a>
        <a href="/#veterinari">Per i veterinari</a>
      </nav>
      <div class="bar-actions">
        <a class="search-btn" href="/guide.html#cerca" aria-label="Cerca nel sito"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></a>
        <a class="btn btn-ghost btn-sm" href="https://www.vetroom.it/app/?per=proprietario">Accedi</a>
        <a class="btn btn-primary btn-sm" href="https://www.vetroom.it/app/?per=proprietario">Crea il libretto</a>
        <button class="menu-btn" type="button" aria-expanded="false" aria-controls="mobile-nav" aria-label="Apri il menu" data-menu>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
      </div>
    </div>
    <nav class="mobile-nav" id="mobile-nav" aria-label="Menu">
      <a href="/guide.html#cerca">Cerca nel sito</a>
      <a href="/come-funziona.html">L’app</a>
      <a href="/strumenti.html" aria-current="page">Strumenti</a>
      <a href="/guide.html">Guide</a>
      <a href="/smarriti/">Animali smarriti</a>
      <a href="/#veterinari">Per i veterinari</a>
      <a href="https://www.vetroom.it/app/?per=proprietario">Accedi</a>
    </nav>
  </div>
</header>
<?php }
function ac_footer() { ?>
<footer class="site-footer">
  <div class="wrap">
    <div class="foot-cols">
      <div>
        <a class="brand" href="/" style="font-size:1.15rem"><img src="/assets/logo-mark.png" alt="" width="30" height="30" style="width:30px;height:30px">FurrFinder</a>
        <p style="margin-top:12px;max-width:34ch">Il libretto sanitario digitale di cane e gatto, collegato al tuo veterinario. Con guide riviste da un medico veterinario e strumenti gratuiti.</p>
        <p style="margin-top:12px"><a href="https://www.vetroom.it/">Sei un veterinario? Scopri Vetroom</a></p>
      </div>
      <div><h4>L’app</h4><ul><li><a href="/come-funziona.html">Come funziona</a></li><li><a href="/funzionalita.html">Funzionalità</a></li><li><a href="/sicurezza.html">Sicurezza dei dati</a></li><li><a href="/libretto-sanitario-cane.html">Libretto del cane</a></li><li><a href="/libretto-sanitario-gatto.html">Libretto del gatto</a></li><li><a href="/faq.html">Domande frequenti</a></li></ul></div>
      <div><h4>Strumenti</h4><ul><li><a href="/volantino-animale-smarrito.html">Volantino smarrito</a></li><li><a href="/eta-cane-anni-umani.html">Età in anni umani</a></li><li><a href="/costo-mantenimento-cane.html">Quanto costa un cane</a></li><li><a href="/gravidanza-cane.html">Data del parto</a></li><li><a href="/nomi-cani.html">Nomi per cani e gatti</a></li><li><a href="/per-cani-e-gatti.html">Libretto da stampare</a></li><li><a href="/strumenti.html"><strong>Tutti gli strumenti</strong></a></li></ul></div>
      <div><h4>Guide</h4><ul><li><a href="/guide.html#cosafare">Cosa fare se sta male</a></li><li><a href="/cibi-tossici-cani.html">Cibi tossici</a></li><li><a href="/calendario-vaccini-cane.html">Vaccini</a></li><li><a href="/passaporto-cane-gatto.html">Passaporto e viaggi</a></li><li><a href="/spese-veterinarie-730.html">Spese nel 730</a></li><li><a href="/guide.html"><strong>Tutte le guide</strong></a></li></ul></div>
      <div><h4>Aiuto</h4><ul><li><a href="/smarriti/">Animali smarriti</a></li><li><a href="/chi-siamo.html">Chi siamo</a></li><li><a href="/revisione-veterinaria.html">Revisione veterinaria</a></li><li><a href="/contatti.html">Contatti</a></li></ul></div>
    </div>
    <p class="legal"><a href="/privacy.html">Privacy</a> · <a href="/cookie-policy.html">Cookie</a> · <a href="/termini.html">Termini</a><br>FurrFinder è un servizio di AF&amp;B S.r.l.s. · Via Mario Ugo Guattari 48, 00173 Roma (RM) · P.IVA 13698751008 · REA RM-1466364 · Capitale sociale € 2.700,00 i.v. · info@vetroom.it<br>Le informazioni su questo sito non sostituiscono la visita del veterinario.</p>
  </div>
</footer>
<?php }
