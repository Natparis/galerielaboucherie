<?php
require __DIR__ . '/inc/layout.php';

$p = single('exposer');
$s = settings();
$disciplines = schema()['collections']['expositions']['fields']['discipline']['options'];
$old = $_SESSION['cand_old'] ?? [];
unset($_SESSION['cand_old']);

page_start('Exposer chez nous', 'exposer', ['titre_seo' => $p['seo_titre'] ?: null, 'description' => $p['seo_description']]);
?>
<section class="wrap grille tete" style="align-items:end;gap:48px;padding-bottom:40px">
  <div style="display:flex;flex-direction:column;gap:14px">
    <p class="sur"><?= e($p['surtitre']) ?></p>
    <h1 class="h-geant" style="font-size:clamp(46px,7vw,92px)"><?= e($p['titre1']) ?><br><span class="ital"><?= e($p['titre2']) ?></span></h1>
  </div>
  <p class="lead"><?= inline($p['intro']) ?></p>
</section>

<section class="wrap" style="padding-top:40px;padding-bottom:88px;display:flex;flex-direction:column;gap:32px">
  <h2 class="h-2"><?= e($p['etapes_titre']) ?></h2>
  <ol class="etapes">
<?php foreach ($p['etapes'] as $i => $et): ?>
    <li class="carte"><div class="store" aria-hidden="true"></div><div class="corps"><span class="num" aria-hidden="true"><?= $i + 1 ?></span><h3><?= e($et['titre'] ?? '') ?></h3><p><?= inline($et['texte'] ?? '') ?></p></div></li>
<?php endforeach ?>
  </ol>
  <div class="cout">
    <div class="prix"><b><?= e($p['montant']) ?></b><span><strong><?= e($p['montant_titre']) ?></strong><br><?= inline($p['montant_texte']) ?></span></div>
    <div class="encart"><p><?= e($p['encart1']) ?><br><span class="ital"><?= e($p['encart2']) ?></span></p></div>
  </div>
</section>

<section class="sombre section" id="candidature" style="padding-left:0;padding-right:0">
  <div class="wrap grille">
    <div style="display:flex;flex-direction:column;gap:16px">
      <p class="neon" style="font-size:52px"><?= e($p['appel']) ?></p>
      <p style="color:var(--sable);line-height:1.7"><?= inline($p['appel_texte']) ?> <a href="<?= e(tel_href($s['telephone'])) ?>"><?= e($s['telephone']) ?></a></p>
    </div>
    <form class="form-cand" method="post" action="candidature.php" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <label class="hp" aria-hidden="true">Ne pas remplir<input type="text" name="site" tabindex="-1" autocomplete="off"></label>
      <label>Nom et prénom *<input type="text" name="nom" required autocomplete="name" value="<?= e($old['nom'] ?? '') ?>"></label>
      <label>E-mail *<input type="email" name="email" required autocomplete="email" value="<?= e($old['email'] ?? '') ?>"></label>
      <label>Téléphone<input type="tel" name="telephone" autocomplete="tel" value="<?= e($old['telephone'] ?? '') ?>"></label>
      <label>Discipline<select name="discipline">
<?php foreach ($disciplines as $d): ?>        <option<?= ($old['discipline'] ?? '') === $d ? ' selected' : '' ?>><?= e($d) ?></option>
<?php endforeach ?>
      </select></label>
      <label>Mois souhaité<input type="month" name="mois" min="<?= date('Y-m') ?>" value="<?= e($old['mois'] ?? '') ?>"></label>
      <label>Lien vers votre travail<input type="text" inputmode="url" name="lien" placeholder="site, Instagram…" value="<?= e($old['lien'] ?? '') ?>"></label>
      <label class="large">Photos de vos œuvres (3 maximum, 8 Mo chacune)<input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp"></label>
      <label class="large">Parlez-nous de votre travail *<textarea name="message" rows="5" required><?= e($old['message'] ?? '') ?></textarea></label>
      <label class="large rgpd"><input type="checkbox" name="rgpd" required> J'accepte que ces informations soient conservées par la galerie pour étudier ma candidature.</label>
      <button class="btn large" type="submit" style="justify-self:start">Envoyer ma candidature</button>
    </form>
  </div>
</section>
<?php page_end();
