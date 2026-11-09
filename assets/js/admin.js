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
        var ok = o ? JSON.parse(li.dataset.origines).indexOf(o) > -1 : g ? JSON.parse(li.dataset.groupes).indexOf(g) > -1 : t === 'seuls-artistes' ? li.dataset.artiste === '1' : li.dataset.type === t;
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
  // ---- Éditeur de courrier ----
  var ed = f.querySelector('[data-editeur]'), cache = f.querySelector('[data-corps]');
  var sujet = f.querySelector('#sujet');
  var plage = null, modifie = false;
  function sync() { cache.value = ed.innerHTML; }
  function signaler() { sync(); f.dispatchEvent(new Event('input', { bubbles: true })); }
  try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (e) {}
  ed.addEventListener('input', function () { modifie = true; sync(); });
  function garder() {
    var sel = window.getSelection();
    if (sel.rangeCount && ed.contains(sel.anchorNode)) plage = sel.getRangeAt(0).cloneRange();
  }
  ['keyup', 'mouseup', 'blur'].forEach(function (ev) { ed.addEventListener(ev, garder); });
  function remettre() {
    ed.focus();
    if (plage) { var sel = window.getSelection(); sel.removeAllRanges(); sel.addRange(plage); }
    else { var r = document.createRange(); r.selectNodeContents(ed); r.collapse(false); var s2 = window.getSelection(); s2.removeAllRanges(); s2.addRange(r); }
  }
  // Coller : seulement le texte (évite les mises en forme parasites de Word ou des sites)
  ed.addEventListener('paste', function (e) {
    e.preventDefault();
    var t = (e.clipboardData || window.clipboardData).getData('text/plain');
    document.execCommand('insertText', false, t);
  });
  f.querySelectorAll('[data-cmd]').forEach(function (b) {
    b.addEventListener('mousedown', function (e) { e.preventDefault(); });
    b.addEventListener('click', function () {
      remettre();
      var c = b.getAttribute('data-cmd');
      if (c === 'titre') document.execCommand('formatBlock', false, 'H2');
      else if (c === 'para') document.execCommand('formatBlock', false, 'P');
      else if (c === 'lien') {
        var url = prompt('Adresse du lien (https://…) :', 'https://');
        if (url && url !== 'https://') document.execCommand('createLink', false, url);
      } else document.execCommand(c, false, null);
      modifie = true; garder(); signaler();
    });
  });
  f.querySelectorAll('[data-inserer]').forEach(function (b) {
    b.addEventListener('mousedown', function (e) { e.preventDefault(); });
    b.addEventListener('click', function () {
      var quoi = b.getAttribute('data-inserer');
      remettre();
      if (quoi === 'prenom') document.execCommand('insertText', false, '{prenom}');
      else {
        var tpl = document.querySelector('template[data-bloc="' + quoi + '"]');
        if (tpl) document.execCommand('insertHTML', false, tpl.innerHTML + '<p><br></p>');
      }
      modifie = true; garder(); signaler();
    });
  });
  // Modèles : remplissent l'objet et le message (et proposent les destinataires)
  f.querySelectorAll('[data-modele]').forEach(function (b) {
    b.addEventListener('click', function () {
      var k = b.getAttribute('data-modele');
      var tpl = document.querySelector('template[data-modele-tpl="' + k + '"]');
      if (!tpl) return;
      if (modifie && !confirm('Remplacer le message en cours par le modèle « ' + b.querySelector('strong').textContent + ' » ?')) return;
      sujet.value = tpl.getAttribute('data-sujet');
      ed.innerHTML = tpl.innerHTML;
      modifie = false; plage = null;
      f.querySelectorAll('[data-modele]').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
      if (tpl.getAttribute('data-pour') === 'newsletter' && !f.querySelector('.liste-dest input:checked')) {
        var nl = f.querySelector('[data-choisir="newsletter"]');
        if (nl) nl.click();
      }
      signaler();
      ed.focus();
    });
  });
  sujet.addEventListener('input', function () { modifie = true; });

  var envoi = f.querySelector('[data-confirmer-envoi]');
  envoi.addEventListener('click', function (e) {
    sync();
    var n = f.querySelectorAll('.liste-dest input:checked').length;
    if (!n) { e.preventDefault(); alert('Choisissez au moins un destinataire (étape 3).'); return; }
    if (!sujet.value.trim()) { e.preventDefault(); alert('Indiquez un objet.'); sujet.focus(); return; }
    if (!ed.innerText.trim()) { e.preventDefault(); alert('Le message est vide.'); ed.focus(); return; }
    if (!confirm('Envoyer ce message à ' + n + ' personne' + (n > 1 ? 's' : '') + ' ?')) e.preventDefault();
  });
  f.addEventListener('submit', sync);
  f.querySelector('[data-mailto]').addEventListener('click', function () {
    var em = [].map.call(f.querySelectorAll('.liste-dest input:checked'), function (c) { return c.dataset.email; });
    if (!em.length) { alert('Choisissez au moins un destinataire.'); return; }
    var corps = ed.innerText.replace(/\{prenom\}/g, '').replace(/Bonjour ,/g, 'Bonjour,').replace(/\n{3,}/g, '\n\n');
    var url = 'mailto:' + (em.length === 1 ? em[0] : '') + '?' + (em.length > 1 ? 'bcc=' + encodeURIComponent(em.join(',')) + '&' : '') +
      'subject=' + encodeURIComponent(sujet.value) + '&body=' + encodeURIComponent(corps);
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
    // le panneau reste collé juste sous la barre de titre, sans passer dessous
    var barre = document.querySelector('.barre');
    if (barre && window.matchMedia('(min-width: 1101px)').matches) {
      var top = barre.offsetHeight + 16;
      pan.style.top = top + 'px';
      pan.style.height = 'calc(100vh - ' + (top + 16) + 'px)';
    } else { pan.style.top = ''; pan.style.height = ''; }
    var W = cadre.clientWidth, H = cadre.clientHeight;
    var largeur = taille === 'mobile' ? 390 : parseInt(pan.getAttribute('data-largeur') || '1280', 10);
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
  // ---- L'aperçu suit le formulaire ----
  var cible = null;          // texte (ou image) du champ en cours de modification
  var dernierChamp = 0;      // moment de la dernière action dans un champ
  function norm(t) { return (t || '').replace(/[*_]/g, '').replace(/\s+/g, ' ').trim().toLowerCase(); }

  // Extrait reconnaissable du champ : le paragraphe où se trouve le curseur, ou le début du texte.
  function extrait(champ) {
    if (champ.type === 'file') return champ.files && champ.files[0] ? { image: true } : null;
    if (champ.type === 'checkbox' || champ.tagName === 'SELECT' && !champ.value) return null;
    var v = champ.value || '';
    if (champ.tagName === 'TEXTAREA') {
      var pos = champ.selectionStart || 0, debut = 0;
      v.split(/\n\s*\n/).some(function (para) {
        if (pos <= debut + para.length) { v = para; return true; }
        debut += para.length + 2; return false;
      });
    }
    v = norm(v);
    if (v.length < 3) return null;
    // un morceau du milieu évite les débuts trop communs (« la », « le »…)
    return { texte: v.length > 40 ? v.slice(0, 40) : v };
  }

  function chercher(doc, c) {
    if (c.image) {
      var imgs = [].slice.call(doc.querySelectorAll('img[src^="data:"]'));
      return imgs[imgs.length - 1] || null;
    }
    var meilleur = null, taille = Infinity;
    doc.querySelectorAll('h1,h2,h3,p,li,span,strong,em,a,td,th,blockquote,figcaption,b').forEach(function (el) {
      var t = norm(el.textContent);
      if (t.indexOf(c.texte) !== -1 && t.length < taille && el.offsetParent !== null) { meilleur = el; taille = t.length; }
    });
    return meilleur;
  }

  function allerA(c, surligner) {
    try {
      var w = iframe.contentWindow, doc = iframe.contentDocument;
      var el = c && chercher(doc, c);
      if (!el) return false;
      var r = el.getBoundingClientRect();
      w.scrollTo({ top: Math.max(0, r.top + w.scrollY - w.innerHeight / 3), behavior: 'smooth' });
      if (surligner) {
        el.style.outline = '3px solid #B5412C'; el.style.outlineOffset = '4px';
        setTimeout(function () { el.style.outline = ''; el.style.outlineOffset = ''; }, 1400);
      }
      return true;
    } catch (e) { return false; }
  }

  // Défilement proportionnel : le haut du formulaire montre le haut de la page, le bas montre le bas.
  function suivreDefilement() {
    if (Date.now() - dernierChamp < 2500) return; // on vient d'aller à un champ précis
    try {
      var w = iframe.contentWindow, d = iframe.contentDocument.documentElement;
      var haut = form.getBoundingClientRect().top + window.scrollY;
      var course = Math.max(1, form.offsetHeight - window.innerHeight * 0.6);
      var ratio = Math.min(1, Math.max(0, (window.scrollY - haut + 120) / course));
      w.scrollTo(0, ratio * Math.max(0, d.scrollHeight - w.innerHeight));
    } catch (e) {}
  }
  var raf = null;
  window.addEventListener('scroll', function () {
    if (pan.hidden || raf) return;
    raf = requestAnimationFrame(function () { raf = null; suivreDefilement(); });
  }, { passive: true });

  form.addEventListener('focusin', function (e) {
    var c = e.target.matches('input,textarea,select') ? extrait(e.target) : null;
    if (c && !c.image && allerA(c, true)) { cible = c; dernierChamp = Date.now(); }
  });

  // Après chaque rafraîchissement : on montre l'endroit modifié, sinon on garde la position.
  iframe.addEventListener('load', function () {
    try {
      var w = iframe.contentWindow;
      w.scrollTo(0, scroll);
      w.addEventListener('scroll', function () { scroll = w.scrollY; });
      setTimeout(function () {
        if (!(cible && Date.now() - dernierChamp < 4000 && allerA(cible, false))) w.scrollTo(0, scroll);
      }, 250);
    } catch (e) {}
  });

  function noterChamp(e) {
    if (!e.target.matches || !e.target.matches('input,textarea,select')) return;
    var c = extrait(e.target);
    if (c) { cible = c; dernierChamp = Date.now(); }
  }
  form.addEventListener('input', function (e) { noterChamp(e); rafraichir(); });
  form.addEventListener('change', function (e) { noterChamp(e); rafraichir(); });
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
