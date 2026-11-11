<?php
require __DIR__ . '/inc/layout.php';

$p = single('page_expo');
$s = settings();
$slug = (string)($_GET['slug'] ?? '');
$x = $slug !== '' ? expo_par_slug($slug) : expo_courante();
if ($slug !== '' && !$x) {
    http_response_code(404);
}
$estCourante = $x && $slug === '' || ($x && expo_courante() && expo_courante()['id'] === $x['id']);
$apres = null;
if ($x) {
    foreach (expositions() as $y) {
        if ($y['date_debut'] > $x['date_debut']) {
            $apres = $y;
            break;
        }
    }
}

if (!$x) {
    page_start('L\'expo du mois', 'expo', ['noindex' => true]);
    ?>
<section class="wrap tete section">
  <p class="sur"><?= e($p['surtitre']) ?></p>
  <h1 class="h-geant">Bientôt <span class="ital">sous le store</span></h1>
  <p class="lead"><?= inline($p['aucune']) ?></p>
  <div class="btns"><a class="btn" href="programme.php">Le programme</a></div>
</section>
<?php
    page_end();
    exit;
}

$enCours = expo_est_en_cours($x);
$passe = expo_fin($x) < today();
$avenir = $x['date_debut'] > today();
$sur = $avenir ? 'Prochainement · vernissage le ' . fr_date($x['date_debut'], 'long') : ($passe ? 'Exposition passée · ' . fr_periode($x) : $p['surtitre'] . ' ' . fr_periode($x));

page_start($x['artiste'] . ($x['titre'] ? ' — ' . $x['titre'] : ''), $estCourante ? 'expo' : 'artistes', [
    'titre_seo' => seo_expo($x)[0],
    'description' => seo_expo($x)[1],
    'image' => $x['image'] ?: null,
    'jsonld' => ld_expo($x),
    'parent' => $estCourante ? null : ['Les artistes', 'artistes.php'],
]);
?>
<section class="wrap tete expo-tete">
  <p class="sur"><?= e($sur) ?></p>
  <h1><?= e($x['artiste']) ?></h1>
<?php if ($x['titre']): ?>  <p class="titre-expo"><?= e($x['titre']) ?></p>
<?php endif ?>
</section>

<section class="wrap grille expo-corps">
  <?php affiche($x, 'Affiche de l\'exposition de ' . $x['artiste'], true) ?>
  <article class="article">
<?php if ($x['accroche']): ?>    <p class="accroche"><?= inline($x['accroche']) ?></p>
<?php endif ?>
    <?= paras($x['texte'] ?: $x['resume']) ?>
<?php if ($x['chute']): ?>    <p class="chute"><?= inline($x['chute']) ?></p>
<?php endif ?>
<?php if ($x['lien']): ?>    <p><a href="<?= e($x['lien']) ?>" rel="noopener" target="_blank">Voir le travail de l'artiste →</a></p>
<?php endif ?>
  </article>
</section>

<?php $photos = array_values(array_filter($x['photos'] ?? [], fn($ph) => !empty($ph['image']))); if ($photos): ?>
<section class="pieces sombre">
  <div class="in">
    <div class="haut"><h2 class="h-1" style="font-size:56px"><?= e($p['pieces_titre']) ?></h2><?php if ($x['mots_neon']): ?><span class="neon"><?= e($x['mots_neon']) ?></span><?php endif ?></div>
    <ul>
<?php foreach ($photos as $ph): ?>
      <li class="lift"><figure><img src="<?= e(url($ph['image'])) ?>"<?= dims($ph['image']) ?> alt="<?= e($ph['legende'] ?: 'Œuvre de ' . $x['artiste']) ?>" loading="lazy" data-zoom><?php if ($ph['legende']): ?><figcaption><?= e($ph['legende']) ?></figcaption><?php endif ?></figure></li>
<?php endforeach ?>
    </ul>
  </div>
</section>
<?php endif ?>

<section class="wrap section grille infos">
  <div style="display:flex;flex-direction:column;gap:16px">
    <h2 class="h-2"><?= e($p['infos_titre']) ?></h2>
    <table>
      <tbody>
        <tr><th scope="row">Vernissage</th><td><?= ucfirst(fr_date($x['date_debut'], 'longy')) ?>, <?= fr_plage($x['heure_debut'], $x['heure_fin']) ?><?= $x['vernissage_note'] ? ' · ' . e($x['vernissage_note']) : '' ?></td></tr>
<?php if ($x['date_fin'] && $x['date_fin'] !== $x['date_debut']): ?>
        <tr><th scope="row">Dates</th><td><?= ucfirst(fr_periode($x)) ?></td></tr>
<?php endif ?>
<?php foreach (lines($x['permanences']) as $i => $l): ?>
        <tr><th scope="row"><?= $i ? '' : 'Permanence' ?></th><td><?= e($l) ?></td></tr>
<?php endforeach ?>
<?php if ($x['telephone_rdv']): ?>
        <tr><th scope="row">Rendez-vous</th><td><a href="<?= e(tel_href($x['telephone_rdv'])) ?>"><?= e($x['telephone_rdv']) ?></a></td></tr>
<?php endif ?>
        <tr><th scope="row">Adresse</th><td><?= e($s['adresse'] . ', ' . $s['code_postal'] . ' ' . $s['ville']) ?></td></tr>
        <tr><th scope="row">Entrée</th><td class="gratuit">Libre et gratuite</td></tr>
      </tbody>
    </table>
    <div class="btns">
      <a class="btn" href="<?= e(tel_href($x['telephone_rdv'] ?: $s['telephone'])) ?>">Prendre rendez-vous</a>
<?php if (!$passe): ?>      <a class="btn btn-ligne" href="agenda.php?slug=<?= e(urlencode($x['slug'])) ?>">Ajouter à mon agenda</a>
<?php endif ?>
    </div>
  </div>
<?php if ($apres): ?>
  <a class="carte prochain lift" href="expo.php?slug=<?= e(urlencode($apres['slug'])) ?>">
    <div class="store" aria-hidden="true" style="height:36px"></div>
    <div class="corps">
      <span class="quand">Ensuite · <?= fr_date($apres['date_debut'], 'long') ?></span>
      <span class="qui"><?= e($apres['artiste']) ?></span>
      <span class="detail"><?= e($apres['technique'] ?: $apres['discipline']) ?> · vernissage <?= fr_heure($apres['heure_debut']) ?></span>
      <span style="font-weight:700;margin-top:8px">Voir l'exposition →</span>
    </div>
  </a>
<?php else: ?>
  <a class="carte prochain lift" href="programme.php"><div class="store" aria-hidden="true" style="height:36px"></div><div class="corps"><span class="quand">La suite</span><span class="qui">Tout le programme</span><span style="font-weight:700;margin-top:8px">Voir le programme →</span></div></a>
<?php endif ?>
</section>
<?php page_end();
