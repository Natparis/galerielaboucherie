<?php
require __DIR__ . '/inc.php';
require_login();

$p = (string)($_GET['p'] ?? '');
$def = schema()['singletons'][$p] ?? null;
if (!$def) {
    http_response_code(404);
    exit('Page inconnue.');
}
$values = single($p);
$voir = ['accueil' => 'index.php', 'page_expo' => 'expo.php', 'programme' => 'programme.php', 'artistes' => 'artistes.php', 'exposer' => 'exposer.php', 'lieu' => 'lieu.php', 'page_presse' => 'presse.php'][$p] ?? 'index.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $new = collect_fields($def['fields'], (array)($_POST['data'] ?? []));
        foreach ($def['fields'] as $k => $f) {
            if (($f['type'] ?? '') === 'secret' && $new[$k] === '') {
                $new[$k] = $values[$k]; // champ laissé vide : on garde la clé actuelle
            }
        }
        data_save($p, $new);
        flash('C\'est enregistré et visible sur le site.');
        redirect('page.php?p=' . $p);
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'err');
        $values = array_replace($values, (array)($_POST['data'] ?? []));
    }
}

admin_start(($def['group'] ?? '') === 'Pages' ? 'Page « ' . $def['label'] . ' »' : $def['label'], 'page.php?p=' . $p);
?>
<?php if (!empty($def['help'])): ?><p class="aide"><?= e($def['help']) ?></p><?php endif ?>
<div class="avec-apercu">
<form method="post" enctype="multipart/form-data" class="formulaire" data-garde data-apercu="apercu.php?p=<?= e($p) ?>">
  <?= csrf_field() ?>
  <?php render_fields($def['fields'], $values) ?>
  <div class="barre-actions">
    <button class="btn" type="submit"><?= icon('save') ?> Enregistrer</button>
    <a class="btn ligne" href="../<?= e($voir) ?>" target="_blank" rel="noopener"><?= icon('eye') ?> Voir la page</a>
  </div>
</form>
<?= apercu_panneau() ?>
</div>
<?php admin_end();
