<?php
require __DIR__ . '/inc/layout.php';

$p = single('accueil');
$s = settings();
$cur = expo_courante();
$next = expos_a_venir();

page_start('', 'accueil', ['jsonld' => [
    '@context' => 'https://schema.org',
    '@type' => 'ArtGallery',
    'name' => $s['nom'],
    'description' => $s['meta_description'],
    'telephone' => $s['telephone'],
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => $s['adresse'], 'postalCode' => $s['code_postal'], 'addressLocality' => $s['ville'], 'addressCountry' => 'FR'],
    'geo' => ['@type' => 'GeoCoordinates', 'latitude' => $s['latitude'], 'longitude' => $s['longitude']],
    'isAccessibleForFree' => true,
]]);
?>
<section class="hero">
  <div class="txt">
    <p class="sur"><?= e($p['surtitre']) ?></p>
    <h1><?= e($p['titre1']) ?><br><span class="ital"><?= e($p['titre2']) ?></span></h1>
    <p class="intro"><?= inline($p['intro']) ?></p>
    <div class="btns"><a class="btn" href="expo.php">Voir l'expo du mois</a><a class="btn btn-ligne" href="programme.php">Le programme</a></div>
  </div>
<?php if ($p['photo']): ?>
  <figure style="margin:0"><img class="photo" src="<?= e(url($p['photo'])) ?>" alt="La devanture de <?= e($s['nom']) ?> au <?= e($s['adresse']) ?>"></figure>
<?php else: devanture($cur, $next); endif ?>
</section>

<?php if ($cur): ?>
<section class="encemoment sombre">
  <div class="grille">
    <?php affiche($cur, 'Affiche de l\'exposition de ' . $cur['artiste']) ?>
    <div class="txt">
      <p class="neon" style="font-size:46px"><?= expo_est_en_cours($cur) ? 'en ce moment' : 'dernièrement' ?></p>
      <h2 class="h-1" style="font-size:64px"><?= e($cur['artiste']) ?></h2>
<?php if ($cur['titre']): ?>      <p class="titre-expo"><?= e($cur['titre']) ?></p>
<?php endif ?>
<?php if ($cur['resume']): ?>      <p class="resume"><?= inline($cur['resume']) ?></p>
<?php endif ?>
      <p style="font-size:15px;line-height:1.7"><?= ucfirst(fr_periode($cur)) ?><?php foreach (lines($cur['permanences']) as $l): ?> · permanence <?= e(mb_strtolower(mb_substr($l, 0, 1)) . mb_substr($l, 1)) ?><?php endforeach ?>
<?php if ($cur['telephone_rdv']): ?><br>Rendez-vous : <a href="<?= e(tel_href($cur['telephone_rdv'])) ?>"><?= e($cur['telephone_rdv']) ?></a><?php endif ?></p>
      <div class="btns"><a class="btn" href="expo.php">Découvrir l'exposition</a></div>
    </div>
  </div>
</section>
<?php endif ?>

<?php if ($next): ?>
<section class="wrap section" style="display:flex;flex-direction:column;gap:40px;max-width:none">
  <h2 class="h-1"><?= e($p['prochainement']) ?> <span class="ital"><?= e($p['prochainement2']) ?></span></h2>
  <ol class="minis">
<?php foreach (array_slice($next, 0, 5) as $x): ?>
    <li class="carte lift"><a href="programme.php#<?= e($x['slug']) ?>" style="text-decoration:none"><div class="store" aria-hidden="true"></div><div class="frange-c" aria-hidden="true"></div><div class="corps"><span class="date"><?= fr_date($x['date_debut'], 'court') ?></span><span class="nom"><?= e($x['artiste_court'] ?: $x['artiste']) ?></span><span class="tech"><?= e($x['technique'] ?: $x['discipline']) ?></span></div></a></li>
<?php endforeach ?>
  </ol>
</section>
<?php endif ?>

<section class="lettre lettre-accueil" id="newsletter">
  <div class="grille">
    <div style="display:flex;flex-direction:column;gap:10px"><p class="neon-clair" style="font-size:46px"><?= e($p['newsletter_titre']) ?></p><p class="lead" style="font-size:18px"><?= inline($p['newsletter_texte']) ?></p></div>
    <form method="post" action="newsletter.php">
      <?= csrf_field() ?>
      <input type="hidden" name="retour" value="index.php#newsletter">
      <label class="hp" aria-hidden="true">Ne pas remplir<input type="text" name="site" tabindex="-1" autocomplete="off"></label>
      <label>Votre adresse e-mail<input type="email" name="email" required autocomplete="email" placeholder="vous@exemple.fr"></label>
      <?= news_msg() ?>
      <button class="btn" type="submit">Recevoir le programme</button>
    </form>
  </div>
</section>

<?php if (trim($p['galerie_texte'])): ?>
<section class="quoi sombre">
  <div class="grille">
<?php if ($p['galerie_photo']): ?>
    <figure class="photo-store"><div class="store" aria-hidden="true"></div><div class="frange" aria-hidden="true"></div><img src="<?= e(url($p['galerie_photo'])) ?>" alt="<?= e($p['galerie_photo_alt']) ?>" loading="lazy"></figure>
<?php elseif ($p['photo']): devanture($cur, $next); else: ?>
    <div></div>
<?php endif ?>
    <div class="txt">
      <h2 class="h-2" style="color:var(--papier)"><?= e($p['galerie_titre']) ?></h2>
      <?= paras($p['galerie_texte']) ?>
      <p>Ouverte aussi sur simple demande au <a href="<?= e(tel_href($s['telephone'])) ?>"><?= e($s['telephone']) ?></a>.</p>
      <div class="btns"><a class="btn" href="exposer.php">Exposer chez nous</a><a class="btn btn-ligne" href="lieu.php">Le lieu</a></div>
    </div>
  </div>
</section>
<?php endif ?>
<?php page_end();
