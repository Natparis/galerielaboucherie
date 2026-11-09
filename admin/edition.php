<?php
require __DIR__ . '/inc.php';
require_login();

$c = (string)($_GET['c'] ?? '');
$def = schema()['collections'][$c] ?? null;
if (!$def) {
    http_response_code(404);
    exit('Rubrique inconnue.');
}
$id = (string)($_GET['id'] ?? '');
$items = collection($c);
$idx = null;
foreach ($items as $i => $x) {
    if (($x['id'] ?? '') === $id) {
        $idx = $i;
        break;
    }
}
if ($id !== '' && $idx === null) {
    flash('Élément introuvable (peut-être déjà supprimé).', 'err');
    redirect('liste.php?c=' . $c);
}
$item = $idx !== null ? array_replace(defaults_of($def['fields']), $items[$idx]) : defaults_of($def['fields']);
$listUrl = 'liste.php?c=' . $c;

/** Adresse unique d'une exposition : nom de l'artiste + année. */
function unique_slug(array $items, string $base, string $selfId): string
{
    $slug = $base;
    $n = 2;
    $used = array_column(array_filter($items, fn($x) => ($x['id'] ?? '') !== $selfId), 'slug');
    while (in_array($slug, $used, true)) {
        $slug = $base . '-' . $n++;
    }
    return $slug;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? 'enregistrer');

    if ($action === 'supprimer' && $idx !== null) {
        array_splice($items, $idx, 1);
        data_save($c, array_values($items));
        flash('« ' . ($item[$def['title']] ?: 'Élément') . ' » a été supprimé.');
        redirect($listUrl);
    }

    if ($action === 'dupliquer' && $idx !== null) {
        $copy = $item;
        $copy['id'] = new_id();
        $copy['publie'] = false;
        $copy[$def['title']] = $copy[$def['title']] . ' (copie)';
        if ($c === 'expositions') {
            $copy['slug'] = unique_slug($items, slugify($copy['artiste'] . '-' . substr($copy['date_debut'], 0, 4)), $copy['id']);
        }
        $items[] = $copy;
        data_save($c, $items);
        flash('Copie créée en brouillon : modifiez-la puis publiez-la.');
        redirect('edition.php?c=' . $c . '&id=' . $copy['id']);
    }

    try {
        $new = collect_fields($def['fields'], (array)($_POST['data'] ?? []));
    } catch (RuntimeException $ex) {
        flash($ex->getMessage(), 'err');
        $new = null;
    }
    if ($new !== null) {
        $missing = missing_required($def['fields'], $new);
        if ($missing) {
            flash('À compléter : ' . implode(', ', $missing) . '.', 'err');
            $item = array_replace($item, $new);
        } else {
            $new['id'] = ($item['id'] ?? '') ?: new_id();
            if ($c === 'expositions') {
                if ($new['date_fin'] && $new['date_fin'] < $new['date_debut']) {
                    $new['date_fin'] = '';
                    flash('Le dernier jour était avant le vernissage : il a été effacé.', 'err');
                }
                $new['slug'] = unique_slug($items, slugify($new['artiste'] . '-' . substr($new['date_debut'], 0, 4)), $new['id']);
            }
            if ($idx !== null) {
                $items[$idx] = $new;
            } else {
                $items[] = $new;
            }
            data_save($c, array_values($items));
            flash('C\'est enregistré' . (empty($new['publie']) ? ' (en brouillon, non visible sur le site).' : ' et visible sur le site.'));
            redirect($action === 'enregistrer_fermer' ? $listUrl : 'edition.php?c=' . $c . '&id=' . $new['id']);
        }
    } else {
        $item = array_replace($item, (array)($_POST['data'] ?? []));
    }
}

$titre = $idx !== null ? ($item[$def['title']] ?: 'Sans titre') : 'Ajouter ' . $def['singular'];
admin_start($titre, $listUrl);
?>
<p class="fil"><a href="<?= e($listUrl) ?>">← <?= e($def['label']) ?></a></p>
<form method="post" enctype="multipart/form-data" class="formulaire" data-garde>
  <?= csrf_field() ?>
  <?php render_fields($def['fields'], $item) ?>
  <div class="barre-actions">
    <button class="btn" type="submit" name="action" value="enregistrer"><?= icon('save') ?> Enregistrer</button>
    <button class="btn ligne" type="submit" name="action" value="enregistrer_fermer">Enregistrer et revenir à la liste</button>
<?php if ($idx !== null): ?>
<?php if ($c === 'expositions' && !empty($item['slug'])): ?>
    <a class="btn ligne" href="../expo.php?slug=<?= e(urlencode($item['slug'])) ?>" target="_blank" rel="noopener"><?= icon('eye') ?> Voir</a>
<?php endif ?>
    <button class="btn ligne" type="submit" name="action" value="dupliquer" formnovalidate><?= icon('copy') ?> Dupliquer</button>
    <button class="btn danger" type="submit" name="action" value="supprimer" formnovalidate data-confirmer="Supprimer définitivement « <?= e($titre) ?> » ?"><?= icon('trash') ?> Supprimer</button>
<?php endif ?>
  </div>
</form>
<?php admin_end();
