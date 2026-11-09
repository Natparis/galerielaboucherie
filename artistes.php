<?php
require __DIR__ . '/inc/layout.php';

$p = single('artistes');
$all = array_reverse(expositions()); // les plus récentes d'abord
$cur = expo_courante();
$t = today();

// À venir en premier (dans l'ordre chronologique), puis en cours, puis passées.
$avenir = array_reverse(array_filter($all, fn($x) => $x['date_debut'] > $t));
$autres = array_filter($all, fn($x) => $x['date_debut'] <= $t);
$liste = array_merge(array_values($autres), array_values($avenir));
usort($liste, function ($a, $b) use ($t) {
    $fa = $a['date_debut'] > $t;
    $fb = $b['date_debut'] > $t;
    if ($fa !== $fb) {
        return $fa ? 1 : -1; // passées et en cours d'abord, à venir ensuite
    }
    return $fa ? strcmp($a['date_debut'], $b['date_debut']) : strcmp($b['date_debut'], $a['date_debut']);
});

$annees = array_values(array_unique(array_map(fn($x) => substr($x['date_debut'], 0, 4), array_filter($liste, fn($x) => $x['date_debut'] <= $t))));
rsort($annees);
$disc = array_values(array_unique(array_filter(array_map(fn($x) => $x['discipline'], $liste))));
sort($disc);

page_start('Les artistes', 'artistes');
?>
<section class="wrap tete">
  <p class="sur"><?= e($p['surtitre']) ?></p>
  <h1 class="h-geant"><?= e($p['titre']) ?></h1>
  <p class="lead"><?= inline($p['intro']) ?></p>
</section>

<section class="wrap filtres" data-filtres hidden>
  <div role="group" aria-label="Filtrer par période">
    <button class="puce" type="button" data-periode="" aria-pressed="true">Tous</button>
    <button class="puce" type="button" data-periode="avenir" aria-pressed="false">À venir</button>
<?php foreach ($annees as $a): ?>    <button class="puce" type="button" data-periode="<?= e($a) ?>" aria-pressed="false"><?= e($a) ?></button>
<?php endforeach ?>
  </div>
<?php if (count($disc) > 1): ?>
  <div role="group" aria-label="Filtrer par discipline">
<?php foreach ($disc as $d): ?>    <button class="puce disc" type="button" data-discipline="<?= e($d) ?>" aria-pressed="false"><?= e($d) ?></button>
<?php endforeach ?>
  </div>
<?php endif ?>
</section>

<section class="wrap" style="padding-top:40px;padding-bottom:96px">
  <ul class="artistes" data-liste>
<?php foreach ($liste as $x):
    $fut = $x['date_debut'] > $t;
    $now = $cur && $cur['id'] === $x['id'] && expo_est_en_cours($x);
?>
    <li class="lift" data-periode="<?= $fut ? 'avenir' : e(substr($x['date_debut'], 0, 4)) ?>" data-discipline="<?= e($x['discipline']) ?>">
      <a class="carte" href="expo.php?slug=<?= e(urlencode($x['slug'])) ?>">
        <div class="store" aria-hidden="true"></div>
<?php if ($x['image']): ?>        <img src="<?= e(url($x['image'])) ?>" alt="" loading="lazy">
<?php else: ?>        <div class="vide" aria-hidden="true"></div>
<?php endif ?>
        <div class="corps">
<?php if ($now): ?>          <span class="neon-clair">en ce moment</span>
<?php elseif ($fut): ?>          <span class="etiquette">À venir · <?= fr_date($x['date_debut'], 'num') ?></span>
<?php endif ?>
          <span class="nom"><?= e($x['artiste']) ?></span>
          <span class="tech"><?= e($x['technique'] ?: $x['discipline']) ?><?= $fut ? '' : ' · ' . fr_date($x['date_debut'], 'my') ?></span>
        </div>
      </a>
    </li>
<?php endforeach ?>
  </ul>
  <p class="aucun" data-aucun hidden>Aucun artiste ne correspond à ces filtres.</p>
</section>
<?php page_end();
