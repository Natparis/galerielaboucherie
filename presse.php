<?php
require __DIR__ . '/inc/layout.php';

$p = single('page_presse');
$s = settings();
$def = defaults_of(schema()['collections']['presse']['fields']);
$articles = array_map(fn($a) => array_replace($def, $a), array_filter(collection('presse'), fn($a) => !empty($a['publie'])));
usort($articles, fn($a, $b) => strcmp($b['date'], $a['date']));
$une = null;
$enCours = $GLOBALS['APERCU_FICHE']['presse']['id'] ?? null; // aperçu du back-office
foreach ($articles as $a) {
    if ($enCours && $a['id'] === $enCours) { $une = $a; break; }
}
foreach ($une ? [] : $articles as $a) {
    if ($a['une']) { $une = $a; break; }
}
$une ??= $articles[0] ?? null;
$autres = array_filter($articles, fn($a) => !$une || $a['id'] !== $une['id']);

function lien_article(array $a): string
{
    return $a['fichier'] ?: ($a['lien'] ?: ($a['image'] ?: ''));
}

page_start('La presse en parle', 'presse');
?>
<section class="wrap tete">
  <p class="sur"><?= e($p['surtitre']) ?></p>
  <h1 class="h-geant"><?= e($p['titre1']) ?> <span class="ital"><?= e($p['titre2']) ?></span></h1>
</section>

<?php if ($une): $l = lien_article($une); ?>
<section class="wrap grille" style="padding-top:48px;padding-bottom:88px;gap:64px;align-items:center">
  <<?= $l ? 'a href="' . e(url($l)) . '" target="_blank" rel="noopener"' : 'div' ?> class="carte une-scan">
    <div class="store" aria-hidden="true"></div>
<?php if ($une['image']): ?>    <img src="<?= e(url($une['image'])) ?>" alt="Article de <?= e($une['journal']) ?> du <?= fr_date($une['date'], 'dmy') ?>">
<?php else: ?>    <div class="placeholder" aria-hidden="true"><?= e($une['journal']) ?></div>
<?php endif ?>
<?php if ($l): ?>    <span class="lire">Lire l'article →</span>
<?php endif ?>
  </<?= $l ? 'a' : 'div' ?>>
  <div class="une-txt">
    <span class="neon-clair">à la une</span>
    <h2><?= e($une['journal']) ?></h2>
    <p class="quand"><?= ucfirst(fr_date($une['date'], 'longy')) ?></p>
<?php if ($une['titre']): ?>    <p style="font-size:20px"><?= e($une['titre']) ?></p>
<?php endif ?>
<?php if ($une['citation']): ?>    <blockquote>« <?= inline($une['citation']) ?> »</blockquote>
<?php endif ?>
  </div>
</section>
<?php else: ?>
<section class="wrap" style="padding-top:24px;padding-bottom:88px"><p class="lead">Les articles arrivent bientôt.</p></section>
<?php endif ?>

<?php if ($autres): ?>
<section class="wrap" style="padding-bottom:88px;display:flex;flex-direction:column;gap:32px">
  <h2 class="h-2">Ils en ont aussi parlé</h2>
  <ul class="articles">
<?php foreach ($autres as $a): $l = lien_article($a); ?>
    <li class="lift"><<?= $l ? 'a href="' . e(url($l)) . '" target="_blank" rel="noopener"' : 'div' ?> class="carte"><div class="store" aria-hidden="true"></div><div class="corps"><span class="date"><?= fr_date($a['date'], 'dmy') ?></span><span class="journal"><?= e($a['journal']) ?></span><?php if ($a['titre']): ?><span><?= e($a['titre']) ?></span><?php endif ?><?php if ($a['citation']): ?><span style="font-family:var(--serif);font-style:italic;font-size:20px;color:var(--gris)">« <?= inline($a['citation']) ?> »</span><?php endif ?><?php if ($l): ?><span style="font-weight:700">Lire →</span><?php endif ?></div></<?= $l ? 'a' : 'div' ?>></li>
<?php endforeach ?>
  </ul>
</section>
<?php endif ?>

<section class="sombre lettre">
  <div class="grille">
    <div style="display:flex;flex-direction:column;gap:10px"><p class="neon" style="font-size:46px"><?= e($p['journaliste']) ?></p><p style="color:var(--sable);line-height:1.7"><?= inline($p['journaliste_texte']) ?></p></div>
    <div class="btns">
<?php if ($p['dossier']): ?>      <a class="btn" href="<?= e(url($p['dossier'])) ?>" target="_blank" rel="noopener">Dossier de presse</a>
<?php endif ?>
      <a class="btn btn-ligne" href="<?= e(tel_href($s['telephone'])) ?>"><?= e($s['telephone']) ?></a>
<?php if ($s['email']): ?>      <a class="btn btn-ligne" href="mailto:<?= e($s['email']) ?>">Écrire</a>
<?php endif ?>
    </div>
  </div>
</section>
<?php page_end();
