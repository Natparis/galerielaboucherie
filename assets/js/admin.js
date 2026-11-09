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

// Répertoire : sélection multiple
(function () {
  var f = document.querySelector('form[data-selection]');
  if (!f) return;
  var coches = f.querySelectorAll('input.coche:not(:disabled)');
  var tout = f.querySelector('[data-tout]'), compte = f.querySelector('[data-compte]'), btn = f.querySelector('[data-ecrire]');
  function maj() {
    var n = f.querySelectorAll('input.coche:checked').length;
    compte.textContent = n + (n > 1 ? ' sélectionnés' : ' sélectionné');
    btn.disabled = n === 0;
    tout.checked = n > 0 && n === coches.length;
  }
  tout.addEventListener('change', function () { coches.forEach(function (c) { c.checked = tout.checked; }); maj(); });
  coches.forEach(function (c) { c.addEventListener('change', maj); });
  maj();
})();

// Écrire : destinataires, insertions, messagerie
(function () {
  var f = document.querySelector('form.ecrire');
  if (!f) return;
  var items = f.querySelectorAll('.liste-dest li');
  var compte = f.querySelector('[data-compte-dest]');
  function boxes() { return f.querySelectorAll('.liste-dest input[type=checkbox]'); }
  function maj() { compte.textContent = '(' + f.querySelectorAll('.liste-dest input:checked').length + ')'; }
  f.addEventListener('change', function (e) { if (e.target.name === 'dest[]') maj(); });
  f.querySelectorAll('[data-choisir],[data-choisir-groupe],[data-choisir-origine]').forEach(function (b) {
    b.addEventListener('click', function () {
      var t = b.getAttribute('data-choisir'), g = b.getAttribute('data-choisir-groupe'), o = b.getAttribute('data-choisir-origine');
      items.forEach(function (li) {
        var cb = li.querySelector('input');
        if (t === 'aucun') { cb.checked = false; return; }
        var ok = o ? JSON.parse(li.dataset.origines).indexOf(o) > -1 : g ? JSON.parse(li.dataset.groupes).indexOf(g) > -1 : li.dataset.type === t;
        if (ok && li.dataset.abonne === '1') cb.checked = true;
      });
      maj();
    });
  });
  var filtre = f.querySelector('[data-filtre-dest]');
  if (filtre) filtre.addEventListener('input', function () {
    var q = filtre.value.toLowerCase();
    items.forEach(function (li) { li.hidden = q && li.textContent.toLowerCase().indexOf(q) === -1; });
  });
  var ta = f.querySelector('#corps');
  f.querySelectorAll('[data-inserer]').forEach(function (b) {
    b.addEventListener('click', function () {
      var v = b.getAttribute('data-inserer'), s = ta.selectionStart, e = ta.selectionEnd;
      ta.value = ta.value.slice(0, s) + v + ta.value.slice(e);
      ta.focus(); ta.selectionStart = ta.selectionEnd = s + v.length;
    });
  });
  var envoi = f.querySelector('[data-confirmer-envoi]');
  envoi.addEventListener('click', function (e) {
    var n = f.querySelectorAll('.liste-dest input:checked').length;
    if (n && !confirm('Envoyer ce message à ' + n + ' personne' + (n > 1 ? 's' : '') + ' ?')) e.preventDefault();
  });
  f.querySelector('[data-mailto]').addEventListener('click', function () {
    var em = [].map.call(f.querySelectorAll('.liste-dest input:checked'), function (c) { return c.dataset.email; });
    if (!em.length) { alert('Choisissez au moins un destinataire.'); return; }
    var corps = ta.value.replace(/\{prenom\}/g, em.length === 1 ? '' : '').replace(/Bonjour ,/g, 'Bonjour,');
    var url = 'mailto:' + (em.length === 1 ? em[0] : '') + '?' + (em.length > 1 ? 'bcc=' + encodeURIComponent(em.join(',')) + '&' : '') +
      'subject=' + encodeURIComponent(f.querySelector('#sujet').value) + '&body=' + encodeURIComponent(corps);
    window.location.href = url;
  });
})();

// Aperçu en direct : la vraie page du site, mise à jour pendant la saisie
(function () {
  var form = document.querySelector('form[data-apercu]');
  var pan = document.querySelector('[data-apercu-panneau]');
  if (!form || !pan) return;
  var iframe = pan.querySelector('[data-apercu-iframe]');
  var cadre = pan.querySelector('[data-apercu-cadre]');
  var etat = pan.querySelector('[data-apercu-etat]');
  var ouvrir = document.querySelector('[data-apercu-ouvrir]');
  var zone = form.closest('.avec-apercu');
  var taille = 'ordi', minuteur = null, enCours = null, scroll = 0, versionDemandee = 0, versionAffichee = 0;

  // Mise à l'échelle : la page est rendue en largeur réelle (1280 px ou 390 px) puis réduite pour tenir dans le cadre.
  function ajuster() {
    var W = cadre.clientWidth, H = cadre.clientHeight;
    var largeur = taille === 'mobile' ? 390 : 1280;
    var echelle = Math.min(1, W / largeur);
    iframe.style.width = largeur + 'px';
    iframe.style.height = (H / echelle) + 'px';
    iframe.style.transform = 'scale(' + echelle + ')';
    iframe.style.left = Math.max(0, (W - largeur * echelle) / 2) + 'px';
  }
  window.addEventListener('resize', ajuster);

  function rafraichir() {
    clearTimeout(minuteur);
    minuteur = setTimeout(envoyer, 450);
    etat.textContent = 'mise à jour…';
    etat.className = 'apercu-etat attente';
  }
  function envoyer() {
    if (pan.hidden) return;
    var v = ++versionDemandee;
    if (enCours) enCours.abort();
    enCours = new AbortController();
    var donnees = new FormData(form);
    donnees.delete('action');
    fetch(form.getAttribute('data-apercu'), { method: 'POST', body: donnees, credentials: 'same-origin', signal: enCours.signal })
      .then(function (r) { return r.text().then(function (t) { return { ok: r.ok, t: t }; }); })
      .then(function (r) {
        if (v < versionAffichee) return;
        if (!r.ok) { etat.textContent = r.t || 'aperçu indisponible'; etat.className = 'apercu-etat erreur'; return; }
        try { scroll = iframe.contentWindow.scrollY || 0; } catch (e) {}
        versionAffichee = v;
        iframe.srcdoc = r.t;
        etat.textContent = 'à jour';
        etat.className = 'apercu-etat';
      })
      .catch(function (e) { if (e.name !== 'AbortError') { etat.textContent = 'aperçu indisponible'; etat.className = 'apercu-etat erreur'; } });
  }
  // On garde l'endroit de la page où l'on regardait.
  iframe.addEventListener('load', function () {
    try {
      var w = iframe.contentWindow;
      w.scrollTo(0, scroll);
      w.addEventListener('scroll', function () { scroll = w.scrollY; });
      // les images arrivent après : on reste au même endroit
      setTimeout(function () { w.scrollTo(0, scroll); }, 300);
    } catch (e) {}
  });

  form.addEventListener('input', rafraichir);
  form.addEventListener('change', rafraichir);
  form.addEventListener('click', function (e) {
    if (e.target.closest('[data-ajout],[data-haut],[data-bas],[data-suppr]')) setTimeout(rafraichir, 50);
  });

  pan.querySelectorAll('[data-apercu-taille]').forEach(function (b) {
    b.addEventListener('click', function () {
      taille = b.getAttribute('data-apercu-taille');
      pan.querySelectorAll('[data-apercu-taille]').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
      ajuster();
    });
  });
  function montrer(oui) {
    pan.hidden = !oui;
    if (ouvrir) ouvrir.hidden = oui;
    zone.classList.toggle('sans-apercu', !oui);
    try { localStorage.setItem('lb_apercu', oui ? '1' : '0'); } catch (e) {}
    if (oui) { ajuster(); envoyer(); }
  }
  pan.querySelector('[data-apercu-fermer]').addEventListener('click', function () { montrer(false); });
  if (ouvrir) ouvrir.addEventListener('click', function () { montrer(true); });

  var pref = null;
  try { pref = localStorage.getItem('lb_apercu'); } catch (e) {}
  var petitEcran = window.matchMedia('(max-width: 1100px)').matches;
  montrer(pref === null ? !petitEcran : pref === '1');
})();
