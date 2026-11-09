<?php
require __DIR__ . '/inc.php';
require_login();

$list = data_load('newsletter', []);

if (isset($_GET['export'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inscrits-programme-la-boucherie-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // pour Excel
    fputcsv($out, ['prenom', 'nom', 'email', 'date_inscription'], ';');
    foreach ($list as $r) {
        fputcsv($out, [$r['prenom'] ?? '', $r['nom'] ?? '', $r['email'], substr($r['date'], 0, 10)], ';');
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'ajouter') {
        $ajout = 0;
        foreach (preg_split('/[\s,;]+/', (string)($_POST['emails'] ?? '')) as $em) {
            $em = mb_strtolower(trim($em));
            if (filter_var($em, FILTER_VALIDATE_EMAIL) && !in_array($em, array_column($list, 'email'), true)) {
                $list[] = ['id' => new_id(), 'email' => $em, 'date' => date('c')];
                $ajout++;
            }
        }
        data_save('newsletter', $list);
        flash($ajout . ' adresse' . ($ajout > 1 ? 's ajoutées' : ' ajoutée') . '.');
    } elseif ($action === 'supprimer') {
        $id = (string)($_POST['id'] ?? '');
        $list = array_values(array_filter($list, fn($r) => $r['id'] !== $id));
        data_save('newsletter', $list);
        flash('Adresse retirée de la liste.');
    }
    redirect('newsletter.php');
}

$emails = implode(', ', array_column($list, 'email'));
admin_start('Écrire', 'ecrire.php');
?>
<?= onglets_ecrire('inscrits') ?>
<p class="aide">Les personnes inscrites pour recevoir le programme depuis le site (accueil et page Programme). Le bouton « Envoyer le programme aux inscrits » prépare un message avec les prochaines expos ; chaque inscrit le reçoit avec un lien de désinscription. Vous pouvez aussi exporter la liste vers un outil d'envoi (Brevo, Mailchimp…).</p>

<div class="outils">
  <a class="btn" href="?export=1"><?= icon('download') ?> Exporter (CSV pour Excel)</a>
<?php if ($list): ?>  <a class="btn" href="ecrire.php?tous=newsletter"><?= icon('mail') ?> Envoyer le programme aux inscrits</a>
  <button class="btn ligne" type="button" data-copier="#tous-emails">Copier toutes les adresses</button>
<?php endif ?>
</div>
<textarea id="tous-emails" class="sr-only" readonly><?= e($emails) ?></textarea>

<div class="deux">
  <section class="bloc">
    <h2><?= count($list) ?> inscrit<?= count($list) > 1 ? 's' : '' ?></h2>
<?php if (!$list): ?>
    <p class="vide-etat">Personne pour l'instant.</p>
<?php else: ?>
    <ul class="simple emails">
<?php foreach (array_reverse($list) as $r): ?>
      <li><span><?php if (!empty($r['prenom']) || !empty($r['nom'])): ?><strong><?= e(trim(($r['prenom'] ?? '') . ' ' . ($r['nom'] ?? ''))) ?></strong> <?php endif ?><?= e($r['email']) ?> <small>depuis le <?= fr_date(substr($r['date'], 0, 10), 'dmy') ?></small></span>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($r['id']) ?>"><button class="ico danger" name="action" value="supprimer" title="Retirer" data-confirmer="Retirer <?= e($r['email']) ?> de la liste ?"><?= icon('trash') ?><span class="sr-only">Retirer <?= e($r['email']) ?></span></button></form></li>
<?php endforeach ?>
    </ul>
<?php endif ?>
  </section>
  <section class="bloc">
    <h2>Ajouter des adresses</h2>
    <form method="post" class="formulaire">
      <?= csrf_field() ?>
      <div class="champs"><div class="champ full"><label for="emails">Une ou plusieurs adresses (séparées par des virgules ou des retours à la ligne)</label><textarea id="emails" name="emails" rows="5"></textarea></div></div>
      <div class="barre-actions"><button class="btn" name="action" value="ajouter"><?= icon('plus') ?> Ajouter</button></div>
    </form>
  </section>
</div>
<?php admin_end();
