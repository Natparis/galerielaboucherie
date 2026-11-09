<?php
require __DIR__ . '/inc.php';
require_login();

$c = (string)($_GET['c'] ?? '');
$def = schema()['collections'][$c] ?? null;
if (!$def) {
    http_response_code(404);
    exit('Rubrique inconnue.');
}
$defaults = defaults_of($def['fields']);
$items = array_map(fn($x) => array_replace($defaults, $x), collection($c));
$sortKey = $def['sort'];
usort($items, fn($a, $b) => strcmp((string)$b[$sortKey], (string)$a[$sortKey]));
$t = today();
$q = trim((string)($_GET['q'] ?? ''));
if ($q !== '') {
    $items = array_filter($items, fn($x) => mb_stripos(implode(' ', array_map(fn($v) => is_scalar($v) ? (string)$v : '', $x)), $q) !== false);
}

admin_start($def['label'], 'liste.php?c=' . $c);
?>
<div class="outils">
  <a class="btn" href="edition.php?c=<?= e($c) ?>"><?= icon('plus') ?> Ajouter <?= e($def['singular']) ?></a>
  <form class="recherche" role="search"><input type="hidden" name="c" value="<?= e($c) ?>"><label class="sr-only" for="q">Rechercher</label><input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Rechercher…"></form>
</div>
<?php if (!empty($def['help'])): ?><p class="aide"><?= e($def['help']) ?></p><?php endif ?>

<?php if (!$items): ?>
<p class="vide-etat">Rien ici pour l'instant.</p>
<?php else: ?>
<div class="table-wrap">
<table class="tableau">
  <thead><tr>
<?php if ($c === 'expositions'): ?><th scope="col" style="width:72px"><span class="sr-only">Visuel</span></th><?php endif ?>
<?php foreach ($def['list'] as $k => $lab): ?><th scope="col"><?= e($lab) ?></th><?php endforeach ?>
    <th scope="col">État</th><th scope="col"><span class="sr-only">Actions</span></th>
  </tr></thead>
  <tbody>
<?php foreach ($items as $x):
    $edit = 'edition.php?c=' . e($c) . '&amp;id=' . e($x['id']);
    if ($c === 'expositions') {
        $etat = !$x['publie'] ? ['brouillon', 'Brouillon'] : (expo_est_en_cours($x) ? ['encours', 'En cours'] : ($x['date_debut'] > $t ? ['avenir', 'À venir'] : ['passe', 'Passée']));
    } else {
        $etat = !$x['publie'] ? ['brouillon', 'Brouillon'] : (!empty($x['une']) ? ['encours', 'À la une'] : ['passe', 'Publié']);
    }
?>
    <tr>
<?php if ($c === 'expositions'): ?>
      <td><?php if ($x['image']): ?><img class="vignette" src="<?= e(url($x['image'])) ?>" alt=""><?php else: ?><span class="vignette vide"></span><?php endif ?></td>
<?php endif ?>
<?php foreach ($def['list'] as $k => $lab):
    $v = (string)$x[$k];
    if (in_array($schemaType = ($def['fields'][$k]['type'] ?? ''), ['date'], true)) {
        $v = fr_date($v, 'dmy');
    }
?>
      <td><?= $k === $def['title'] ? '<a class="lien-fort" href="' . $edit . '">' . e($v ?: '(sans nom)') . '</a>' : e($v) ?></td>
<?php endforeach ?>
      <td><span class="etat <?= $etat[0] ?>"><?= $etat[1] ?></span></td>
      <td class="actions-ligne">
        <a class="ico" href="<?= $edit ?>" title="Modifier"><?= icon('page') ?><span class="sr-only">Modifier</span></a>
<?php if ($c === 'expositions'): ?>
        <a class="ico" href="../expo.php?slug=<?= e(urlencode($x['slug'] ?? '')) ?>" target="_blank" rel="noopener" title="Voir sur le site"><?= icon('eye') ?><span class="sr-only">Voir sur le site</span></a>
<?php endif ?>
      </td>
    </tr>
<?php endforeach ?>
  </tbody>
</table>
</div>
<?php endif ?>
<?php admin_end();
