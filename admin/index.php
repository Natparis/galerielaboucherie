<?php
require __DIR__ . '/inc.php';
$u = require_login();

$cur = expo_courante();
$next = array_slice(expos_a_venir(), 0, 4);
$cands = data_load('candidatures', []);
$nouvelles = array_filter($cands, fn($c) => ($c['statut'] ?? '') === 'nouvelle');
$inscrits = count(data_load('newsletter', []));
$brouillons = array_filter(expositions(true), fn($x) => empty($x['publie']));
$prenom = explode(' ', $u['nom'])[0];

admin_start('Tableau de bord', 'index.php');
?>
<p class="bonjour">Bonjour <?= e($prenom) ?> !</p>

<div class="tuiles">
  <a class="tuile" href="liste.php?c=expositions"><b><?= count(expositions()) ?></b><span>expositions publiées</span></a>
  <a class="tuile" href="candidatures.php"><b><?= count($nouvelles) ?></b><span>candidature<?= count($nouvelles) > 1 ? 's' : '' ?> à lire</span></a>
  <a class="tuile" href="newsletter.php"><b><?= $inscrits ?></b><span>inscrit<?= $inscrits > 1 ? 's' : '' ?> à la newsletter</span></a>
  <a class="tuile" href="liste.php?c=expositions"><b><?= count($brouillons) ?></b><span>brouillon<?= count($brouillons) > 1 ? 's' : '' ?></span></a>
</div>

<div class="deux">
  <section class="bloc">
    <h2><?= $cur && expo_est_en_cours($cur) ? 'En ce moment sous le store' : 'Dernière exposition' ?></h2>
<?php if ($cur): ?>
    <div class="expo-mini">
      <?php if ($cur['image']): ?><img src="<?= e(url($cur['image'])) ?>" alt=""><?php endif ?>
      <div>
        <p class="gros"><?= e($cur['artiste']) ?></p>
        <?php if ($cur['titre']): ?><p class="ital"><?= e($cur['titre']) ?></p><?php endif ?>
        <p><?= ucfirst(fr_periode($cur)) ?></p>
        <p class="actions"><a class="btn petit" href="edition.php?c=expositions&amp;id=<?= e($cur['id']) ?>">Modifier</a> <a class="btn ligne petit" href="../expo.php" target="_blank" rel="noopener">Voir la page</a></p>
      </div>
    </div>
<?php else: ?>
    <p>Aucune exposition pour l'instant.</p>
<?php endif ?>
  </section>

  <section class="bloc">
    <h2>Prochainement</h2>
<?php if ($next): ?>
    <ul class="simple">
<?php foreach ($next as $x): ?>
      <li><a href="edition.php?c=expositions&amp;id=<?= e($x['id']) ?>"><b><?= fr_date($x['date_debut'], 'court') ?></b> <?= e($x['artiste']) ?></a></li>
<?php endforeach ?>
    </ul>
<?php else: ?>
    <p>Aucune date à venir : pensez à remplir le programme.</p>
<?php endif ?>
    <p><a class="btn petit" href="edition.php?c=expositions"><?= icon('plus') ?> Nouvelle exposition</a></p>
  </section>
</div>

<?php if ($nouvelles): ?>
<section class="bloc">
  <h2>Candidatures à lire</h2>
  <ul class="simple">
<?php foreach (array_slice(array_reverse($nouvelles), 0, 5) as $c): ?>
    <li><a href="candidatures.php?id=<?= e($c['id']) ?>"><b><?= e($c['nom']) ?></b> · <?= e($c['discipline']) ?> · reçue le <?= fr_date(substr($c['date'], 0, 10), 'dmy') ?></a></li>
<?php endforeach ?>
  </ul>
</section>
<?php endif ?>

<section class="bloc aide-bloc">
  <h2>Mode d'emploi express</h2>
  <ul>
    <li><strong>Une nouvelle expo ?</strong> Menu <em>Expositions</em> → « Nouvelle exposition ». Renseignez l'artiste, la date du vernissage et l'affiche : l'accueil, « L'expo du mois », le programme et la page Artistes se mettent à jour tout seuls, selon les dates.</li>
    <li><strong>Changer un texte du site ?</strong> Menu <em>Modifier les pages du site</em>, choisissez la page : l'aperçu à droite montre le résultat en direct. Une ligne vide entre deux paragraphes ; <code>**gras**</code> et <code>*italique*</code> fonctionnent.</li>
    <li><strong>Un article de presse ?</strong> Menu <em>Articles de presse</em>. Cochez « à la une » pour l'afficher en grand.</li>
    <li><strong>Téléphone, adresse, réseaux sociaux ?</strong> Menu <em>Coordonnées et réglages</em>.</li>
  </ul>
</section>
<?php admin_end();
