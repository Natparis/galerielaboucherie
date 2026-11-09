<?php
require __DIR__ . '/inc.php';
require_login();
require_once __DIR__ . '/copains.php';

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
$listUrl = $def['list_url'] ?? 'liste.php?c=' . $c;

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
        if ($c === 'repertoire' && !empty($items[$idx]['cda_id']) && copains_actif()) {
            // La fiche reste dans le Répertoire de Copains : on lui retire seulement le projet « galerie ».
            if ($err = copains_retirer($items[$idx]['cda_id'])) {
                flash('Attention : Copains d\'après n\'a pas pu être prévenu (' . $err . ').', 'err');
            }
        }
        array_splice($items, $idx, 1);
        data_save($c, array_values($items));
        flash('« ' . ($item[$def['title']] ?: 'Élément') . ' » a été supprimé.');
        redirect($listUrl);
    }

    if ($action === 'dupliquer' && $idx !== null) {
        $copy = $item;
        $copy['id'] = new_id();
        $copy['publie'] = false;
        unset($copy['cda_id'], $copy['cda_sig'], $copy['sync_attente']);
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
            // Conserve les informations techniques non affichées (lien avec Copains d'après…)
            if ($idx !== null) {
                $new += array_diff_key($items[$idx], $def['fields']);
            }
            $msgCopains = null;
            if ($c === 'repertoire' && copains_actif()) {
                if (!empty($new['partage'])) {
                    $new['sync_attente'] = true;
                    $msgCopains = copains_envoyer($new) ? 'err' : 'ok';
                } elseif (!empty($new['cda_id'])) {
                    copains_retirer($new['cda_id']);
                    $new['cda_id'] = $new['cda_sig'] = '';
                    $new['sync_attente'] = false;
                    $msgCopains = 'retire';
                }
            }
            if ($idx !== null) {
                $items[$idx] = $new;
            } else {
                $items[] = $new;
            }
            data_save($c, array_values($items));
            if (!empty($def['private'])) {
                flash('C\'est enregistré' . ['ok' => ', et à jour dans le Répertoire de Copains d\'après.', 'retire' => ' et retiré du partage avec Copains d\'après.', 'err' => '. Copains d\'après n\'a pas pu être mis à jour pour l\'instant : ce sera refait automatiquement.', '' => '.'][$msgCopains ?? '']);
            } else {
                flash('C\'est enregistré' . (empty($new['publie']) ? ' (en brouillon, non visible sur le site).' : ' et visible sur le site.'));
            }
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
<?php if ($c === 'repertoire' && $idx !== null): ?>
<p class="origines-fiche">Origine : <?= badges_origine($item) ?><?= !empty($item['cda_id']) ? ' · <span class="lie">⇄</span> fiche commune avec le Répertoire de Copains d\'après' : '' ?></p>
<?php endif ?>
<?php $apercu = in_array($c, ['expositions', 'presse'], true); ?>
<?php if ($apercu): ?><div class="avec-apercu"><?php endif ?>
<form method="post" enctype="multipart/form-data" class="formulaire" data-garde<?= $apercu ? ' data-apercu="apercu.php?c=' . e($c) . '&amp;id=' . e($item['id'] ?? '') . '"' : '' ?>>
  <?= csrf_field() ?>
  <?php render_fields($def['fields'], $item) ?>
  <div class="barre-actions">
    <button class="btn" type="submit" name="action" value="enregistrer"><?= icon('save') ?> Enregistrer</button>
    <button class="btn ligne" type="submit" name="action" value="enregistrer_fermer">Enregistrer et revenir à la liste</button>
<?php if ($idx !== null): ?>
<?php if ($c === 'expositions' && !empty($item['slug'])): ?>
    <a class="btn ligne" href="../expo.php?slug=<?= e(urlencode($item['slug'])) ?>" target="_blank" rel="noopener"><?= icon('eye') ?> Voir</a>
<?php endif ?>
<?php if ($c === 'repertoire' && !empty($item['email'])): ?>
    <a class="btn ligne" href="ecrire.php?ids[]=<?= e($item['id']) ?>"><?= icon('mail') ?> Écrire</a>
<?php endif ?>
    <button class="btn ligne" type="submit" name="action" value="dupliquer" formnovalidate><?= icon('copy') ?> Dupliquer</button>
    <button class="btn danger" type="submit" name="action" value="supprimer" formnovalidate data-confirmer="Supprimer définitivement « <?= e($titre) ?> » ?"><?= icon('trash') ?> Supprimer</button>
<?php endif ?>
  </div>
</form>
<?php if ($apercu): ?><?= apercu_panneau() ?></div><?php endif ?>
<?php admin_end();
