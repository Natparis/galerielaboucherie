<?php
require __DIR__ . '/inc/layout.php';

// Le lien reçu par e-mail affiche d'abord une confirmation : certaines messageries « visitent »
// les liens automatiquement, et ne doivent pas pouvoir désinscrire quelqu'un à sa place.
$email = mb_strtolower(trim((string)($_REQUEST['e'] ?? '')));
$jeton = (string)($_REQUEST['t'] ?? '');
$valide = $email !== '' && hash_equals(jeton_desinscription($email), $jeton);
$fait = false;
if ($valide && $_SERVER['REQUEST_METHOD'] === 'POST') {
    data_save('newsletter', array_values(array_filter(data_load('newsletter', []), fn($r) => $r['email'] !== $email)));
    $fait = true;
}
page_start('Désinscription', '', ['noindex' => true]);
?>
<section class="wrap tete section" style="max-width:860px">
<?php if ($fait): ?>
  <h1 class="h-1">C'est fait.</h1>
  <p class="lead">L'adresse <?= e($email) ?> ne recevra plus le programme. Vous restez bien sûr le bienvenu sous le store !</p>
  <div class="btns"><a class="btn" href="index.php">Retour à l'accueil</a></div>
<?php elseif ($valide): ?>
  <h1 class="h-1">Ne plus recevoir le programme ?</h1>
  <p class="lead">L'adresse <?= e($email) ?> sera retirée de la liste d'envoi de la galerie.</p>
  <form method="post" class="btns">
    <input type="hidden" name="e" value="<?= e($email) ?>"><input type="hidden" name="t" value="<?= e($jeton) ?>">
    <button class="btn" type="submit">Oui, me désinscrire</button>
    <a class="btn btn-ligne" href="index.php">Non, je reste inscrit·e</a>
  </form>
<?php else: ?>
  <h1 class="h-1">Lien non valable</h1>
  <p class="lead">Ce lien de désinscription est incomplet. Écrivez-nous ou appelez la galerie et nous vous retirerons de la liste.</p>
  <div class="btns"><a class="btn" href="index.php">Retour à l'accueil</a></div>
<?php endif ?>
</section>
<?php page_end();
