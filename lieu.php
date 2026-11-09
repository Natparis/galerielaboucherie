<?php
require __DIR__ . '/inc/layout.php';

$p = single('lieu');
$s = settings();
$adresse = $s['adresse'] . ', ' . $s['code_postal'] . ' ' . $s['ville'];
$lat = (float)$s['latitude'];
$lon = (float)$s['longitude'];

page_start('Le lieu', 'lieu');
?>
<section class="wrap tete">
  <p class="sur"><?= e($p['surtitre']) ?></p>
  <h1 class="h-geant"><?= e($p['titre1']) ?><br><span class="ital"><?= e($p['titre2']) ?></span></h1>
</section>

<section class="wrap grille" style="padding-top:40px;padding-bottom:88px;gap:64px;align-items:center">
  <div class="article" style="font-size:19px;gap:20px"><?= paras($p['texte']) ?></div>
  <?php devanture(expo_courante(), expos_a_venir()) ?>
</section>

<section class="sombre section">
  <div class="wrap" style="display:flex;flex-direction:column;gap:32px">
<?php if ($p['frise']): ?>
    <ol class="frise">
<?php foreach ($p['frise'] as $f): ?>
      <li><span class="neon"><?= e($f['quand'] ?? '') ?></span><strong><?= e($f['titre'] ?? '') ?></strong><span><?= inline($f['texte'] ?? '') ?></span></li>
<?php endforeach ?>
    </ol>
<?php endif ?>
<?php $photos = array_filter($p['photos'], fn($ph) => !empty($ph['image'])); if ($photos): ?>
    <div class="photos-lieu">
<?php foreach ($photos as $ph): ?>
      <figure><img src="<?= e(url($ph['image'])) ?>" alt="<?= e(($ph['alt'] ?? '') ?: ($ph['legende'] ?? '')) ?>" loading="lazy" data-zoom><?php if (!empty($ph['legende'])): ?><figcaption><?= e($ph['legende']) ?></figcaption><?php endif ?></figure>
<?php endforeach ?>
    </div>
<?php endif ?>
  </div>
</section>

<section class="wrap section grille" style="gap:48px">
  <div style="display:flex;flex-direction:column;gap:16px">
    <h2 class="h-2"><?= e($p['venir_titre']) ?></h2>
    <p style="font-size:18px;line-height:1.8"><strong><?= e($adresse) ?></strong><br><?= inline($p['venir_texte']) ?><br><a href="<?= e(tel_href($s['telephone'])) ?>"><?= e($s['telephone']) ?></a></p>
    <div class="btns"><a class="btn" href="https://www.google.com/maps/dir/?api=1&amp;destination=<?= e(urlencode($s['nom'] . ', ' . $adresse)) ?>" rel="noopener" target="_blank">Itinéraire</a></div>
  </div>
<?php if ($lat && $lon): ?>
  <iframe class="carte-osm" title="Carte : <?= e($s['nom']) ?>, <?= e($adresse) ?>" loading="lazy" src="https://www.openstreetmap.org/export/embed.html?bbox=<?= $lon - .006 ?>%2C<?= $lat - .0035 ?>%2C<?= $lon + .006 ?>%2C<?= $lat + .0035 ?>&amp;layer=mapnik&amp;marker=<?= $lat ?>%2C<?= $lon ?>"></iframe>
<?php endif ?>
</section>
<?php page_end();
