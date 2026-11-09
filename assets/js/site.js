// La Boucherie — petits comportements du site (menu mobile, filtres, visionneuse).
(function () {
  // Menu mobile
  var burger = document.querySelector('.burger');
  var nav = document.getElementById('nav');
  if (burger && nav) {
    burger.addEventListener('click', function () {
      var open = nav.classList.toggle('ouvert');
      burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // Filtres de la page Artistes
  var filtres = document.querySelector('[data-filtres]');
  var liste = document.querySelector('[data-liste]');
  if (filtres && liste) {
    filtres.hidden = false;
    var periode = '', discipline = '';
    var aucun = document.querySelector('[data-aucun]');
    function appliquer() {
      var n = 0;
      liste.querySelectorAll('li').forEach(function (li) {
        var ok = (!periode || li.dataset.periode === periode) && (!discipline || li.dataset.discipline === discipline);
        li.hidden = !ok;
        if (ok) n++;
      });
      if (aucun) aucun.hidden = n > 0;
    }
    filtres.addEventListener('click', function (ev) {
      var b = ev.target.closest('button');
      if (!b) return;
      if ('periode' in b.dataset) {
        periode = b.dataset.periode;
        filtres.querySelectorAll('[data-periode]').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
      } else {
        discipline = discipline === b.dataset.discipline ? '' : b.dataset.discipline;
        filtres.querySelectorAll('[data-discipline]').forEach(function (x) { x.setAttribute('aria-pressed', x.dataset.discipline === discipline ? 'true' : 'false'); });
      }
      appliquer();
    });
  }

  // Visionneuse d'images
  var zooms = document.querySelectorAll('img[data-zoom]');
  if (zooms.length && window.HTMLDialogElement) {
    var dlg = document.createElement('dialog');
    dlg.className = 'visionneuse';
    dlg.innerHTML = '<img alt=""><button type="button" aria-label="Fermer">×</button>';
    document.body.appendChild(dlg);
    var big = dlg.querySelector('img');
    dlg.querySelector('button').addEventListener('click', function () { dlg.close(); });
    dlg.addEventListener('click', function (e) { if (e.target === dlg) dlg.close(); });
    zooms.forEach(function (img) {
      img.tabIndex = 0;
      function ouvrir() { big.src = img.currentSrc || img.src; big.alt = img.alt; dlg.showModal(); }
      img.addEventListener('click', ouvrir);
      img.addEventListener('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); ouvrir(); } });
    });
  }
})();
