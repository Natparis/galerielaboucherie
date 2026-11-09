// Espace galerie : listes répétables, aperçus d'images, confirmations, menu mobile.
(function () {
  // Menu mobile
  var btn = document.querySelector('.menu-btn');
  var side = document.getElementById('side');
  if (btn && side) {
    btn.addEventListener('click', function () {
      var o = side.classList.toggle('ouvert');
      btn.setAttribute('aria-expanded', o ? 'true' : 'false');
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && side.classList.contains('ouvert')) { side.classList.remove('ouvert'); btn.setAttribute('aria-expanded', 'false'); btn.focus(); }
    });
  }

  // Listes répétables (photos, étapes…)
  document.addEventListener('click', function (e) {
    var t = e.target.closest('[data-ajout],[data-haut],[data-bas],[data-suppr]');
    if (!t) return;
    var liste = t.closest('[data-liste]');
    if (t.hasAttribute('data-ajout')) {
      var tpl = liste.querySelector(':scope > [data-template]');
      var idx = 'n' + Date.now().toString(36);
      var wrap = document.createElement('div');
      wrap.innerHTML = tpl.innerHTML.split('__I__').join(idx);
      var item = wrap.firstElementChild;
      liste.querySelector(':scope > [data-items]').appendChild(item);
      var first = item.querySelector('input:not([type=hidden]),textarea,select');
      if (first) first.focus();
      sale = true;
      return;
    }
    var it = t.closest('[data-item]');
    if (t.hasAttribute('data-suppr')) {
      if (confirm('Retirer cet élément ? (il disparaîtra à l\'enregistrement)')) { it.remove(); sale = true; }
    } else if (t.hasAttribute('data-haut') && it.previousElementSibling) {
      it.parentNode.insertBefore(it, it.previousElementSibling); t.focus(); sale = true;
    } else if (t.hasAttribute('data-bas') && it.nextElementSibling) {
      it.parentNode.insertBefore(it.nextElementSibling, it); t.focus(); sale = true;
    }
  });

  // Aperçu des images choisies
  document.addEventListener('change', function (e) {
    var inp = e.target;
    if (!inp.matches('input[type=file][data-preview]')) return;
    var f = inp.files && inp.files[0];
    var box = inp.closest('.media');
    var nom = box.querySelector('.nom-fichier');
    if (nom) nom.textContent = f ? '✓ ' + f.name + ' (sera envoyé à l\'enregistrement)' : '';
    if (f && f.size > 8 * 1024 * 1024) { alert('Ce fichier dépasse 8 Mo : réduisez-le avant de l\'envoyer.'); inp.value = ''; if (nom) nom.textContent = ''; return; }
    var ap = box.querySelector('.apercu');
    if (f && ap && f.type.indexOf('image/') === 0) {
      var img = document.createElement('img');
      img.className = 'apercu'; img.alt = '';
      img.src = URL.createObjectURL(f);
      ap.replaceWith(img);
    }
  });

  // Confirmations (supprimer…)
  document.addEventListener('click', function (e) {
    var b = e.target.closest('[data-confirmer]');
    if (b && !confirm(b.getAttribute('data-confirmer'))) { e.preventDefault(); return; }
    if (b) sale = false;
  });

  // Copier des adresses
  document.querySelectorAll('[data-copier]').forEach(function (b) {
    b.addEventListener('click', function () {
      var src = document.querySelector(b.getAttribute('data-copier'));
      var txt = src.value;
      var ok = function () { var t = b.textContent; b.textContent = 'Copié !'; setTimeout(function () { b.textContent = t; }, 1800); };
      if (navigator.clipboard) navigator.clipboard.writeText(txt).then(ok); else { src.classList.remove('sr-only'); src.select(); document.execCommand('copy'); src.classList.add('sr-only'); ok(); }
    });
  });

  // Prévenir avant de quitter une page modifiée sans enregistrer
  var sale = false;
  document.querySelectorAll('form[data-garde]').forEach(function (f) {
    f.addEventListener('input', function () { sale = true; });
    f.addEventListener('change', function () { sale = true; });
    f.addEventListener('submit', function () { sale = false; });
  });
  window.addEventListener('beforeunload', function (e) { if (sale) { e.preventDefault(); e.returnValue = ''; } });
})();
