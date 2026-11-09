<?php
require __DIR__ . '/inc/layout.php';

$email = mb_strtolower(trim((string)($_GET['e'] ?? '')));
$ok = $email !== '' && hash_equals(jeton_desinscription($email), (string)($_GET['t'] ?? ''));
if ($ok) {
    data_save('newsletter', array_values(array_filter(data_load('newsletter', []), fn($r) => $r['email'] !== $email)));
}
page_start('Désinscription');
?>
<section class="wrap tete section" style="max-width:860px">
  <h1 class="h-1"><?= $ok ? 'C\'est fait.' : 'Lien non valable' ?></h1>
  <p class="lead"><?= $ok ? 'L\'adresse ' . e($email) . ' ne recevra plus le programme. Vous restez bien sûr le bienvenu sous le store !' : 'Ce lien de désinscription est incomplet. Écrivez-nous ou appelez la galerie et nous vous retirerons de la liste.' ?></p>
  <div class="btns"><a class="btn" href="index.php">Retour à l'accueil</a></div>
</section>
<?php page_end();
