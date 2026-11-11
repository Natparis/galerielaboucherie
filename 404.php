<?php
/** Page affichée quand une adresse n'existe pas (ou plus). */
require __DIR__ . '/inc/layout.php';
http_response_code(404);
$next = expos_a_venir();
page_start('Page introuvable', '', ['noindex' => true, 'base' => site_url()]); // liens relatifs depuis la racine, quelle que soit l'adresse demandée
?>
<section class="wrap tete section" style="max-width:860px">
  <p class="sur">Erreur 404</p>
  <h1 class="h-geant">Rien <span class="ital">sous ce store.</span></h1>
  <p class="lead">Cette page n'existe pas ou a changé d'adresse. La galerie, elle, est toujours au même endroit.</p>
  <div class="btns"><a class="btn" href="index.php">Retour à l'accueil</a><a class="btn btn-ligne" href="expo.php">L'expo du mois</a><a class="btn btn-ligne" href="programme.php">Le programme</a></div>
</section>
<?php page_end();
