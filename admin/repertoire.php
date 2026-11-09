<?php
require __DIR__ . '/inc.php';
require_login();

$rep = repertoire();

// Export CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="repertoire-artistes-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['nom', 'email', 'telephone', 'discipline', 'ville', 'adresse', 'lien', 'groupes', 'notes'], ';');
    foreach ($rep as $a) {
        fputcsv($out, [$a['nom'], $a['email'], $a['telephone'], $a['discipline'], $a['ville'], $a['adresse'], $a['lien'], $a['groupes'], $a['notes']], ';');
    }
    exit;
}

/** Ajoute ou complète une fiche (recherche par e-mail, sinon par nom). */
function fusionner(array &$list, array $data, string $groupe): string
{
    $def = defaults_of(schema()['collections']['repertoire']['fields']);
    foreach ($list as &$a) {
        $memeEmail = $data['email'] !== '' && mb_strtolower($a['email'] ?? '') === mb_strtolower($data['email']);
        $memeNom = slugify($a['nom'] ?? '') === slugify($data['nom']);
        if ($memeEmail || $memeNom) {
            foreach ($data as $k => $v) {
                if ($v !== '' && empty($a[$k])) {
                    $a[$k] = $v;
                }
            }
            $g = array_filter(array_map('trim', explode(',', (string)($a['groupes'] ?? ''))));
            if (!in_array($groupe, $g, true)) {
                $g[] = $groupe;
            }
            $a['groupes'] = implode(', ', $g);
            return 'maj';
        }
    }
    unset($a);
    $list[] = array_replace($def, $data, ['id' => new_id(), 'groupes' => $groupe]);
    return 'ajout';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'importer') {
    csrf_check();
    $list = collection('repertoire');
    $n = ['ajout' => 0, 'maj' => 0];
    $opts = schema()['collections']['repertoire']['fields']['discipline']['options'];
    foreach (expositions(true) as $x) {
        if (str_contains($x['artiste'], '[')) {
            continue; // fiches à compléter
        }
        // « Françoise Dubarry et Bernard Conte » → deux fiches
        foreach (preg_split('/\s+(?:et|&)\s+/u', $x['artiste']) as $nom) {
            $groupe = $x['date_debut'] > today() ? 'Programmé' : 'A exposé';
            $n[fusionner($list, ['nom' => trim($nom), 'email' => '', 'discipline' => in_array($x['discipline'], $opts, true) ? $x['discipline'] : '', 'lien' => $x['lien']], $groupe)]++;
        }
    }
    foreach (data_load('candidatures', []) as $c) {
        $n[fusionner($list, ['nom' => $c['nom'], 'email' => $c['email'], 'telephone' => $c['telephone'], 'discipline' => in_array($c['discipline'], $opts, true) ? $c['discipline'] : '', 'lien' => $c['lien']], 'Candidat')]++;
    }
    data_save('repertoire', $list);
    flash("Import terminé : {$n['ajout']} fiche(s) créée(s), {$n['maj']} complétée(s). Pensez à ajouter les e-mails manquants.");
    redirect('repertoire.php');
}

// Filtres
$q = trim((string)($_GET['q'] ?? ''));
$gr = (string)($_GET['groupe'] ?? '');
$di = (string)($_GET['discipline'] ?? '');
$aff = array_filter($rep, function ($a) use ($q, $gr, $di) {
    if ($q !== '' && mb_stripos($a['nom'] . ' ' . $a['email'] . ' ' . $a['ville'] . ' ' . $a['notes'] . ' ' . $a['groupes'], $q) === false) return false;
    if ($gr !== '' && !in_array($gr, groupes_de($a), true)) return false;
    if ($di !== '' && $a['discipline'] !== $di) return false;
    return true;
});
$groupes = tous_groupes($rep);
$disc = array_values(array_unique(array_filter(array_column($rep, 'discipline'))));
sort($disc);
$sansEmail = count(array_filter($rep, fn($a) => $a['email'] === ''));

admin_start('Répertoire des artistes', 'repertoire.php');
?>
<div class="outils">
  <div class="btns-l">
    <a class="btn" href="edition.php?c=repertoire"><?= icon('plus') ?> Ajouter un artiste</a>
    <form method="post" style="display:inline"><?= csrf_field() ?><button class="btn ligne" name="action" value="importer" data-confirmer="Créer ou compléter des fiches à partir des expositions et des candidatures ? (aucune fiche existante n'est effacée)">Importer expositions et candidatures</button></form>
    <a class="btn ligne" href="?export=1"><?= icon('download') ?> Exporter (CSV)</a>
  </div>
</div>
<p class="aide">Fiches privées, jamais affichées sur le site. <?= count($rep) ?> artiste<?= count($rep) > 1 ? 's' : '' ?><?= $sansEmail ? " · $sansEmail sans e-mail" : '' ?>.</p>

<form class="filtres-adm" method="get">
  <label class="sr-only" for="q">Rechercher</label><input id="q" type="search" name="q" value="<?= e($q) ?>" placeholder="Nom, ville, note…">
  <label class="sr-only" for="groupe">Groupe</label>
  <select id="groupe" name="groupe"><option value="">Tous les groupes</option><?php foreach ($groupes as $g => $nb): ?><option<?= $gr === $g ? ' selected' : '' ?> value="<?= e($g) ?>"><?= e($g) ?> (<?= $nb ?>)</option><?php endforeach ?></select>
  <label class="sr-only" for="discipline">Discipline</label>
  <select id="discipline" name="discipline"><option value="">Toutes disciplines</option><?php foreach ($disc as $d): ?><option<?= $di === $d ? ' selected' : '' ?>><?= e($d) ?></option><?php endforeach ?></select>
  <button class="btn ligne petit">Filtrer</button>
  <?php if ($q !== '' || $gr !== '' || $di !== ''): ?><a href="repertoire.php">Tout afficher</a><?php endif ?>
</form>

<?php if (!$rep): ?>
<div class="vide-etat">Le répertoire est vide. Ajoutez un artiste, ou importez d'un clic ceux des expositions et des candidatures.</div>
<?php elseif (!$aff): ?>
<p class="vide-etat">Aucun artiste ne correspond.</p>
<?php else: ?>
<form method="get" action="ecrire.php" data-selection>
<div class="barre-selection">
  <label class="case"><input type="checkbox" data-tout> <span>Tout sélectionner</span></label>
  <span data-compte aria-live="polite">0 sélectionné</span>
  <button class="btn petit" type="submit" disabled data-ecrire><?= icon('mail') ?> Écrire à la sélection</button>
<?php if ($gr !== ''): ?>  <a class="btn ligne petit" href="ecrire.php?groupe=<?= e(urlencode($gr)) ?>">Écrire à tout le groupe « <?= e($gr) ?> »</a><?php endif ?>
</div>
<div class="table-wrap"><table class="tableau">
  <thead><tr><th scope="col" style="width:44px"><span class="sr-only">Sélection</span></th><th scope="col">Nom</th><th scope="col">Discipline</th><th scope="col">Contact</th><th scope="col">Groupes</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
  <tbody>
<?php foreach ($aff as $a): ?>
    <tr>
      <td><input type="checkbox" class="coche" name="ids[]" value="<?= e($a['id']) ?>" aria-label="Sélectionner <?= e($a['nom']) ?>"<?= $a['email'] === '' ? ' disabled title="Pas d\'e-mail"' : '' ?>></td>
      <td><a class="lien-fort" href="edition.php?c=repertoire&amp;id=<?= e($a['id']) ?>"><?= e($a['nom']) ?></a><?php if ($a['ville']): ?><br><small class="gris"><?= e($a['ville']) ?></small><?php endif ?></td>
      <td><?= e($a['discipline']) ?></td>
      <td class="contact"><?php if ($a['email']): ?><a href="mailto:<?= e($a['email']) ?>"><?= e($a['email']) ?></a><?php else: ?><span class="gris">pas d'e-mail</span><?php endif ?><?php if ($a['telephone']): ?><br><a href="<?= e(tel_href($a['telephone'])) ?>"><?= e($a['telephone']) ?></a><?php endif ?><?php if (!$a['abonne']): ?><br><span class="etat brouillon">ne veut plus d'envois</span><?php endif ?></td>
      <td><?php foreach (groupes_de($a) as $g): ?><a class="puce-adm" href="?groupe=<?= e(urlencode($g)) ?>"><?= e($g) ?></a> <?php endforeach ?></td>
      <td class="actions-ligne">
<?php if ($a['email']): ?>        <a class="ico" href="ecrire.php?ids[]=<?= e($a['id']) ?>" title="Écrire à <?= e($a['nom']) ?>"><?= icon('mail') ?><span class="sr-only">Écrire à <?= e($a['nom']) ?></span></a>
<?php endif ?>
        <a class="ico" href="edition.php?c=repertoire&amp;id=<?= e($a['id']) ?>" title="Modifier"><?= icon('page') ?><span class="sr-only">Modifier</span></a>
      </td>
    </tr>
<?php endforeach ?>
  </tbody>
</table></div>
</form>
<?php endif ?>
<?php admin_end();
