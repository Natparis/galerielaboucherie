<?php
require __DIR__ . '/inc/layout.php';

$p = single('programme');
$next = expos_a_venir();

page_start('Programme', 'programme');
?>
<section class="wrap tete tete-flex">
  <div style="display:flex;flex-direction:column;gap:14px">
    <p class="sur"><?= e($p['surtitre']) ?></p>
    <h1 class="h-geant"><?= e($p['titre1']) ?><br><span class="ital"><?= e($p['titre2']) ?></span></h1>
  </div>
<?php if ($next): ?>  <a class="btn btn-ligne" href="agenda.php">Tout ajouter à mon agenda</a>
<?php endif ?>
</section>

<section class="wrap" style="padding-top:40px;padding-bottom:88px">
<?php if (!$next): ?>
  <p class="lead"><?= inline($p['vide']) ?></p>
<?php else: ?>
  <ol class="prog">
<?php foreach ($next as $x): ?>
    <li id="<?= e($x['slug']) ?>">
      <div class="visuel"><div class="store" aria-hidden="true"></div><div class="frange" aria-hidden="true"></div>
<?php if ($x['image']): ?>        <img src="<?= e(url($x['image'])) ?>" alt="<?= e($x['image_alt'] ?: 'Visuel de ' . $x['artiste']) ?>" loading="lazy">
<?php else: ?>        <div class="placeholder" aria-hidden="true"><?= e(mb_strtoupper($x['technique'] ?: $x['discipline'] ?: 'Visuel à venir')) ?></div>
<?php endif ?>
      </div>
      <div class="corps">
        <span class="date"><?= ucfirst(fr_date($x['date_debut'], 'long')) ?></span>
        <h2><?= e($x['artiste']) ?></h2>
<?php if ($x['titre']): ?>        <p style="font-family:var(--serif);font-style:italic;font-size:24px;color:var(--brique)"><?= e($x['titre']) ?></p>
<?php endif ?>
<?php if ($x['resume']): ?>        <p class="resume"><?= inline($x['resume']) ?></p>
<?php endif ?>
        <span class="heures">Vernissage <?= fr_plage($x['heure_debut'], $x['heure_fin']) ?></span>
        <div class="btns" style="margin-top:6px"><a href="expo.php?slug=<?= e(urlencode($x['slug'])) ?>">En savoir plus</a><span aria-hidden="true">·</span><a href="agenda.php?slug=<?= e(urlencode($x['slug'])) ?>">Ajouter à mon agenda</a></div>
      </div>
    </li>
<?php endforeach ?>
  </ol>
<?php endif ?>
</section>

<section class="lettre sombre" id="newsletter">
  <div class="grille">
    <div style="display:flex;flex-direction:column;gap:10px"><p class="neon"><?= e($p['newsletter_titre']) ?></p><p style="color:var(--sable)"><?= inline($p['newsletter_texte']) ?></p></div>
    <form method="post" action="newsletter.php">
      <?= csrf_field() ?>
      <input type="hidden" name="retour" value="programme.php#newsletter">
      <label class="hp" aria-hidden="true">Ne pas remplir<input type="text" name="site" tabindex="-1" autocomplete="off"></label>
      <label>Votre adresse e-mail<input type="email" name="email" required autocomplete="email" placeholder="vous@exemple.fr"></label>
      <button class="btn" type="submit">S'inscrire</button>
    </form>
  </div>
</section>
<?php page_end();
