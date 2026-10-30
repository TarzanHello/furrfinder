/* FurrFinder home: menu su mobile e bordo dell'intestazione allo scorrimento. Nessuna libreria esterna. */
(function () {
  var header = document.getElementById('header');
  var btn = document.querySelector('[data-menu]');
  if (header && btn) {
    btn.addEventListener('click', function () {
      var open = header.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
      btn.setAttribute('aria-label', open ? 'Chiudi il menu' : 'Apri il menu');
    });
    header.querySelectorAll('.mobile-nav a').forEach(function (a) {
      a.addEventListener('click', function () {
        header.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
      });
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && header.classList.contains('open')) {
        header.classList.remove('open');
        btn.setAttribute('aria-expanded', 'false');
        btn.focus();
      }
    });
  }
  // Menu a tendina: il pulsante con la freccia apre e chiude (tastiera e schermi touch); Esc o un clic fuori chiudono
  document.querySelectorAll('[data-dd]').forEach(function (dd) {
    var b = dd.querySelector('.dd-btn');
    if (!b) return;
    var chiudi = function () { dd.classList.remove('open'); b.setAttribute('aria-expanded', 'false'); };
    b.addEventListener('click', function (e) {
      e.preventDefault();
      var aperto = !dd.classList.contains('open');
      document.querySelectorAll('[data-dd].open').forEach(function (x) { if (x !== dd) { x.classList.remove('open'); x.querySelector('.dd-btn').setAttribute('aria-expanded', 'false'); } });
      dd.classList.toggle('open', aperto); dd.classList.toggle('chiuso', !aperto);
      b.setAttribute('aria-expanded', aperto ? 'true' : 'false');
    });
    dd.addEventListener('mouseleave', function () { dd.classList.remove('chiuso'); if (dd.classList.contains('open') && !dd.contains(document.activeElement)) chiudi(); });
    dd.addEventListener('keydown', function (e) { if (e.key === 'Escape') { chiudi(); dd.classList.add('chiuso'); b.focus(); } });
    document.addEventListener('click', function (e) { if (!dd.contains(e.target)) { chiudi(); dd.classList.remove('chiuso'); } });
  });
  if (header) {
    var onScroll = function () { header.classList.toggle('scrolled', window.scrollY > 8); };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }
})();
