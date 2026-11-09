<?php
require __DIR__ . '/inc/layout.php';
$s = settings();
page_start('Mentions légales');
?>
<section class="wrap tete section" style="max-width:860px">
  <h1 class="h-1">Mentions légales</h1>
  <div class="article"><?= paras($s['mentions']) ?></div>
</section>
<?php page_end();
