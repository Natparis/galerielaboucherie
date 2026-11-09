<?php
require __DIR__ . '/inc.php';
require_login();

const STATUTS = ['nouvelle' => 'Nouvelle', 'lue' => 'Lue', 'retenue' => 'Retenue', 'refusee' => 'Refusée', 'archivee' => 'Archivée'];

$list = data_load('candidatures', []);
$id = (string)($_GET['id'] ?? $_POST['id'] ?? '');
$idx = null;
foreach ($list as $i => $c) {
    if ($c['id'] === $id) {
        $idx = $i;
        break;
    }
}

// Photo jointe (stockée hors du dossier public)
if (isset($_GET['photo']) && $idx !== null) {
    $f = basename((string)$_GET['photo']);
    $path = DATA_DIR . '/candidatures/' . $list[$idx]['id'] . '/' . $f;
    if (!in_array($f, $list[$idx]['photos'] ?? [], true) || !is_file($path)) {
        http_response_code(404);
        exit('Photo introuvable.');
    }
    header('Content-Type: ' . (new finfo(FILEINFO_MIME_TYPE))->file($path));
    header('Content-Length: ' . filesize($path));
    header('Cache-Control: private, max-age=3600');
    readfile($path);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $idx !== null) {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'supprimer') {
        $dir = DATA_DIR . '/candidatures/' . $list[$idx]['id'];
        foreach (glob($dir . '/*') ?: [] as $f) {
            unlink($f);
        }
        if (is_dir($dir)) {
            rmdir($dir);
        }
        $nom = $list[$idx]['nom'];
        array_splice($list, $idx, 1);
        data_save('candidatures', array_values($list));
        flash('La candidature de ' . $nom . ' a été supprimée, photos comprises.');
        redirect('candidatures.php');
    }
    if ($action === 'maj') {
        $st = (string)($_POST['statut'] ?? '');
        if (isset(STATUTS[$st])) {
            $list[$idx]['statut'] = $st;
        }
        $list[$idx]['note'] = trim((string)($_POST['note'] ?? ''));
        data_save('candidatures', $list);
        flash('Candidature mise à jour.');
        redirect('candidatures.php?id=' . $id);
    }
    if ($action === 'repertoire') {
        $c = $list[$idx];
        $rep = collection('repertoire');
        foreach ($rep as $a) {
            if (mb_strtolower($a['email'] ?? '') === mb_strtolower($c['email'])) {
                flash($c['nom'] . ' est déjà dans le répertoire.');
                redirect('edition.php?c=repertoire&id=' . $a['id']);
            }
        }
        $opts = schema()['collections']['repertoire']['fields']['discipline']['options'];
        $a = array_replace(defaults_of(schema()['collections']['repertoire']['fields']), [
            'id' => new_id(), 'nom' => $c['nom'], 'email' => $c['email'], 'telephone' => $c['telephone'],
            'discipline' => in_array($c['discipline'], $opts, true) ? $c['discipline'] : '', 'lien' => $c['lien'], 'groupes' => 'Candidat',
            'notes' => 'Candidature du ' . fr_date(substr($c['date'], 0, 10), 'dmy') . ' : ' . mb_substr($c['message'], 0, 500),
        ]);
        $rep[] = $a;
        data_save('repertoire', $rep);
        flash($c['nom'] . ' a été ajouté·e au répertoire.');
        redirect('edition.php?c=repertoire&id=' . $a['id']);
    }
    if ($action === 'creer_expo') {
        $c = $list[$idx];
        $expos = collection('expositions');
        $x = defaults_of(schema()['collections']['expositions']['fields']);
        $date = $c['mois'] ? $c['mois'] . '-01' : date('Y-m-d', strtotime('+1 month'));
        // premier samedi du mois souhaité
        $ts = strtotime($date);
        while ((int)date('w', $ts) !== 6) {
            $ts = strtotime('+1 day', $ts);
        }
        $x = array_replace($x, [
            'id' => new_id(), 'artiste' => $c['nom'], 'discipline' => in_array($c['discipline'], schema()['collections']['expositions']['fields']['discipline']['options'], true) ? $c['discipline'] : '',
            'date_debut' => date('Y-m-d', $ts), 'resume' => mb_substr($c['message'], 0, 300), 'lien' => $c['lien'], 'publie' => false,
            'slug' => slugify($c['nom'] . '-' . date('Y', $ts) . '-' . substr(new_id(), 0, 4)),
        ]);
        // Copie la première photo comme visuel
        if (!empty($c['photos'][0])) {
            $src = DATA_DIR . '/candidatures/' . $c['id'] . '/' . $c['photos'][0];
            if (is_file($src)) {
                $dest = slugify($c['nom']) . '-' . substr(new_id(), 0, 6) . '.' . pathinfo($src, PATHINFO_EXTENSION);
                if (copy($src, UPLOAD_DIR . '/' . $dest)) {
                    $x['image'] = 'uploads/' . $dest;
                }
            }
        }
        $expos[] = $x;
        data_save('expositions', $expos);
        $list[$idx]['statut'] = 'retenue';
        data_save('candidatures', $list);
        flash('Exposition créée en brouillon à partir de la candidature. Vérifiez la date puis publiez-la.');
        redirect('edition.php?c=expositions&id=' . $x['id']);
    }
}

// Vue détaillée
if ($idx !== null) {
    $c = $list[$idx];
    if ($c['statut'] === 'nouvelle') {
        $list[$idx]['statut'] = $c['statut'] = 'lue';
        data_save('candidatures', $list);
    }
    admin_start('Candidature de ' . $c['nom'], 'candidatures.php');
    ?>
<p class="fil"><a href="candidatures.php">← Toutes les candidatures</a></p>
<div class="deux">
  <section class="bloc">
    <dl class="fiche">
      <dt>Reçue le</dt><dd><?= fr_date(substr($c['date'], 0, 10), 'longy') ?> à <?= e(substr($c['date'], 11, 5)) ?></dd>
      <dt>E-mail</dt><dd><a href="mailto:<?= e($c['email']) ?>?subject=<?= rawurlencode('Votre candidature à ' . settings()['nom']) ?>"><?= e($c['email']) ?></a></dd>
      <?php if ($c['telephone']): ?><dt>Téléphone</dt><dd><a href="<?= e(tel_href($c['telephone'])) ?>"><?= e($c['telephone']) ?></a></dd><?php endif ?>
      <dt>Discipline</dt><dd><?= e($c['discipline']) ?></dd>
      <?php if ($c['mois']): ?><dt>Mois souhaité</dt><dd><?= e(ucfirst(FR_MOIS[(int)substr($c['mois'], 5, 2)] . ' ' . substr($c['mois'], 0, 4))) ?></dd><?php endif ?>
      <?php if ($c['lien']): ?><dt>Son travail</dt><dd><a href="<?= e($c['lien']) ?>" target="_blank" rel="noopener noreferrer"><?= e($c['lien']) ?></a></dd><?php endif ?>
    </dl>
    <h2>Son message</h2>
    <div class="message"><?= paras($c['message']) ?></div>
  </section>
  <section class="bloc">
    <form method="post" class="formulaire">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($c['id']) ?>">
      <div class="champs">
        <div class="champ full"><label for="statut">Statut</label><select id="statut" name="statut"><?php foreach (STATUTS as $k => $l): ?><option value="<?= $k ?>"<?= $c['statut'] === $k ? ' selected' : '' ?>><?= $l ?></option><?php endforeach ?></select></div>
        <div class="champ full"><label for="note">Note interne</label><textarea id="note" name="note" rows="4"><?= e($c['note'] ?? '') ?></textarea></div>
      </div>
      <div class="barre-actions">
        <button class="btn" name="action" value="maj">Enregistrer</button>
        <button class="btn ligne" name="action" value="creer_expo">Créer l'exposition</button>
        <button class="btn ligne" name="action" value="repertoire">Ajouter au répertoire</button>
        <button class="btn danger" name="action" value="supprimer" data-confirmer="Supprimer cette candidature et ses photos ?"><?= icon('trash') ?> Supprimer</button>
      </div>
    </form>
  </section>
</div>
<?php if (!empty($c['photos'])): ?>
<section class="bloc">
  <h2>Photos envoyées</h2>
  <div class="photos-cand">
<?php foreach ($c['photos'] as $ph): $u = 'candidatures.php?id=' . e($c['id']) . '&amp;photo=' . e(urlencode($ph)); ?>
    <a href="<?= $u ?>" target="_blank" rel="noopener"><img src="<?= $u ?>" alt="Œuvre envoyée par <?= e($c['nom']) ?>"></a>
<?php endforeach ?>
  </div>
</section>
<?php endif ?>
<?php
    admin_end();
    exit;
}

// Liste
$filtre = (string)($_GET['statut'] ?? '');
$aff = array_reverse(array_filter($list, fn($c) => $filtre === '' ? $c['statut'] !== 'archivee' : $c['statut'] === $filtre));
admin_start('Candidatures', 'candidatures.php');
?>
<p class="aide">Les demandes envoyées depuis la page « Exposer chez nous ». Les photos ne sont visibles que d'ici.</p>
<nav class="onglets" aria-label="Filtrer par statut">
  <a href="candidatures.php"<?= $filtre === '' ? ' aria-current="page"' : '' ?>>En cours</a>
<?php foreach (STATUTS as $k => $l): ?>
  <a href="?statut=<?= $k ?>"<?= $filtre === $k ? ' aria-current="page"' : '' ?>><?= $l ?> (<?= count(array_filter($list, fn($c) => $c['statut'] === $k)) ?>)</a>
<?php endforeach ?>
</nav>
<?php if (!$aff): ?>
<p class="vide-etat">Aucune candidature ici.</p>
<?php else: ?>
<div class="table-wrap"><table class="tableau">
  <thead><tr><th scope="col">Reçue le</th><th scope="col">Nom</th><th scope="col">Discipline</th><th scope="col">Mois souhaité</th><th scope="col">Photos</th><th scope="col">Statut</th></tr></thead>
  <tbody>
<?php foreach ($aff as $c): ?>
    <tr<?= $c['statut'] === 'nouvelle' ? ' class="nouveau"' : '' ?>>
      <td><?= fr_date(substr($c['date'], 0, 10), 'dmy') ?></td>
      <td><a class="lien-fort" href="?id=<?= e($c['id']) ?>"><?= e($c['nom']) ?></a></td>
      <td><?= e($c['discipline']) ?></td>
      <td><?= $c['mois'] ? e(FR_MOIS[(int)substr($c['mois'], 5, 2)] . ' ' . substr($c['mois'], 0, 4)) : '—' ?></td>
      <td><?= count($c['photos'] ?? []) ?></td>
      <td><span class="etat <?= $c['statut'] === 'nouvelle' ? 'avenir' : ($c['statut'] === 'retenue' ? 'encours' : 'passe') ?>"><?= STATUTS[$c['statut']] ?? e($c['statut']) ?></span></td>
    </tr>
<?php endforeach ?>
  </tbody>
</table></div>
<?php endif ?>
<?php admin_end();
