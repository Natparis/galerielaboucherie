<?php
require __DIR__ . '/inc.php';
$moi = require_login();
require_once __DIR__ . '/copains.php';

$s = settings();
$rep = repertoire();
$news = data_load('newsletter', []);

/** Tous les destinataires possibles, indexés par clé ('a:<id>' artiste, 'n:<id>' newsletter). */
$dest = [];
foreach ($rep as $a) {
    if ($a['email'] !== '') {
        $dest['a:' . $a['id']] = ['nom' => $a['nom'], 'email' => $a['email'], 'groupes' => groupes_de($a), 'origines' => origines_de($a), 'abonne' => (bool)$a['abonne'], 'type' => 'artiste'];
    }
}
foreach ($news as $n) {
    $dest['n:' . $n['id']] = ['nom' => '', 'email' => $n['email'], 'groupes' => [], 'origines' => [], 'abonne' => true, 'type' => 'newsletter'];
}

/** Texte du programme à venir, prêt à coller dans un message. */
function texte_programme(): string
{
    $s = settings();
    $t = '';
    $cur = expo_courante();
    if ($cur && expo_est_en_cours($cur)) {
        $t .= "EN CE MOMENT\n" . $cur['artiste'] . ($cur['titre'] ? ' — ' . $cur['titre'] : '') . "\n" . ucfirst(fr_periode($cur)) . "\n\n";
    }
    $next = expos_a_venir();
    if ($next) {
        $t .= "PROCHAINEMENT SOUS LE STORE\n";
        foreach ($next as $x) {
            $t .= '• ' . ucfirst(fr_date($x['date_debut'], 'long')) . ' à ' . fr_heure($x['heure_debut']) . ' : ' . $x['artiste']
                . ($x['technique'] ? ' (' . mb_strtolower($x['technique']) . ')' : '') . "\n";
        }
        $t .= "\n";
    }
    $t .= 'Vernissage ouvert à tous, entrée libre et gratuite.' . "\n" . $s['adresse'] . ', ' . $s['code_postal'] . ' ' . $s['ville'];
    return $t;
}

$historique = data_load('messages', []);

// Détail d'un envoi passé
if (isset($_GET['msg'])) {
    $m = null;
    foreach ($historique as $h) {
        if ($h['id'] === $_GET['msg']) $m = $h;
    }
    if (!$m) redirect('ecrire.php');
    $ok = count(array_filter($m['destinataires'], fn($d) => $d['ok']));
    admin_start('Message envoyé', 'ecrire.php');
    ?>
<p class="fil"><a href="ecrire.php">← Écrire aux artistes</a></p>
<div class="deux">
  <section class="bloc">
    <p class="aide">Envoyé le <?= fr_date(substr($m['date'], 0, 10), 'longy') ?> à <?= e(substr($m['date'], 11, 5)) ?> par <?= e($m['auteur']) ?></p>
    <h2><?= e($m['sujet']) ?></h2>
    <div class="message"><?= paras($m['corps']) ?></div>
    <p><a class="btn ligne petit" href="ecrire.php?reprendre=<?= e($m['id']) ?>"><?= icon('copy') ?> Réutiliser ce message</a></p>
  </section>
  <section class="bloc">
    <h2><?= $ok ?> / <?= count($m['destinataires']) ?> envoyé<?= $ok > 1 ? 's' : '' ?></h2>
    <ul class="simple emails">
<?php foreach ($m['destinataires'] as $d): ?>
      <li><span><?= e($d['nom'] ?: $d['email']) ?> <small><?= e($d['nom'] ? $d['email'] : '') ?></small></span><span class="etat <?= $d['ok'] ? 'encours' : 'brouillon' ?>"><?= $d['ok'] ? 'Envoyé' : 'Échec' ?></span></li>
<?php endforeach ?>
    </ul>
  </section>
</div>
<?php
    admin_end();
    exit;
}

$sujet = '';
$corps = "Bonjour {prenom},\n\n";
$avecSignature = true;
$copie = false;
$coches = [];

// Pré-sélection : ?ids[]=… (fiches du répertoire), ?groupe=…, ?tous=newsletter
foreach ((array)($_GET['ids'] ?? []) as $id) {
    if (isset($dest['a:' . $id])) $coches[] = 'a:' . $id;
}
if (isset($_GET['groupe'])) {
    foreach ($dest as $k => $d) {
        if ($d['abonne'] && in_array($_GET['groupe'], $d['groupes'], true)) $coches[] = $k;
    }
}
if (isset($_GET['origine'])) {
    foreach ($dest as $k => $d) {
        if ($d['abonne'] && in_array($_GET['origine'], $d['origines'], true)) $coches[] = $k;
    }
}
if (($_GET['tous'] ?? '') === 'newsletter') {
    foreach ($dest as $k => $d) {
        if ($d['type'] === 'newsletter') $coches[] = $k;
    }
    $sujet = 'Le programme de ' . $s['nom'];
    $corps = "Bonjour,\n\nVoici les prochains rendez-vous sous le store :\n\n" . texte_programme() . "\n\nAu plaisir de vous y retrouver !\n";
}
if (isset($_GET['reprendre'])) {
    foreach ($historique as $h) {
        if ($h['id'] === $_GET['reprendre']) {
            $sujet = $h['sujet'];
            $corps = $h['corps'];
            $avecSignature = false;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $sujet = trim((string)($_POST['sujet'] ?? ''));
    $corps = trim(str_replace("\r\n", "\n", (string)($_POST['corps'] ?? '')));
    $avecSignature = !empty($_POST['signature']);
    $copie = !empty($_POST['copie']);
    $coches = array_values(array_unique(array_filter((array)($_POST['dest'] ?? []), fn($k) => isset($dest[$k]))));
    $err = [];
    if (!$coches) $err[] = 'Choisissez au moins un destinataire.';
    if ($sujet === '') $err[] = 'Indiquez un objet.';
    if ($corps === '') $err[] = 'Le message est vide.';
    if ($err) {
        flash(implode(' ', $err), 'err');
    } else {
        @set_time_limit(300);
        $final = $corps . ($avecSignature && trim($s['signature']) ? "\n\n-- \n" . $s['signature'] : '');
        $log = [];
        foreach ($coches as $k) {
            $d = $dest[$k];
            $txt = personnaliser($final, ['nom' => $d['nom']]);
            $txt = preg_replace('/Bonjour ,/u', 'Bonjour,', $txt);
            if ($d['type'] === 'newsletter') {
                $txt .= "\n\nVous recevez ce message car vous êtes inscrit·e au programme de " . $s['nom'] . ".\nPour ne plus le recevoir : " . lien_desinscription($d['email']);
            }
            $ok = envoyer_mail($d['email'], personnaliser($sujet, ['nom' => $d['nom']]), $txt);
            $log[] = ['nom' => $d['nom'], 'email' => $d['email'], 'ok' => $ok];
            usleep(150000); // ne pas saturer le serveur d'envoi
        }
        if ($copie && filter_var($moi['email'], FILTER_VALIDATE_EMAIL)) {
            envoyer_mail($moi['email'], '[Copie] ' . $sujet, personnaliser($final, ['nom' => $moi['nom']]));
        }
        $m = ['id' => new_id(), 'date' => date('c'), 'auteur' => $moi['nom'], 'sujet' => $sujet, 'corps' => $final, 'destinataires' => $log];
        array_unshift($historique, $m);
        data_save('messages', array_slice($historique, 0, 200));
        $ok = count(array_filter($log, fn($l) => $l['ok']));
        if ($ok === count($log)) {
            flash("Message envoyé à $ok destinataire" . ($ok > 1 ? 's' : '') . '.');
        } else {
            flash("$ok envoi(s) réussi(s) sur " . count($log) . '. Les échecs viennent en général du serveur : vérifiez l\'adresse d\'expédition dans Coordonnées et réglages, ou utilisez « Ouvrir dans ma messagerie ».', 'err');
        }
        redirect('ecrire.php?msg=' . $m['id']);
    }
}

$groupes = tous_groupes($rep);
$coches = array_flip($coches);
admin_start('Écrire aux artistes', 'ecrire.php');
?>
<p class="aide">Chaque personne reçoit son propre e-mail (les autres destinataires restent invisibles). <code>{prenom}</code> et <code>{nom}</code> sont remplacés automatiquement. Les réponses arrivent à l'adresse de contact<?= $s['email'] ? ' (' . e($s['email']) . ')' : '' ?>.</p>
<?php if (!$s['email_expediteur']): ?><div class="flash err">Conseil : renseignez une « adresse d'expédition » dans <a href="page.php?p=reglages">Coordonnées et réglages</a> pour que les messages n'arrivent pas en indésirables.</div><?php endif ?>

<form method="post" class="formulaire ecrire" data-garde>
  <?= csrf_field() ?>
  <div class="deux">
    <section class="bloc dest">
      <h2>Destinataires <small data-compte-dest>(<?= count($coches) ?>)</small></h2>
<?php if (!$dest): ?>
      <p class="vide-etat">Aucun contact avec e-mail. Remplissez d'abord le <a href="repertoire.php">répertoire</a>.</p>
<?php else: ?>
      <div class="raccourcis" aria-label="Sélection rapide">
        <button type="button" class="puce-adm" data-choisir="artiste">Tous les artistes</button>
<?php $nbO = []; foreach ($dest as $d) foreach ($d['origines'] as $o) $nbO[$o] = ($nbO[$o] ?? 0) + 1;
      foreach (ORIGINES as $k => [$nom, $court, $coul]): if (empty($nbO[$k]) || count($nbO) < 2) continue; ?>        <button type="button" class="puce-adm origine-btn" style="--c:<?= $coul ?>" data-choisir-origine="<?= e($k) ?>"><?= e($nom) ?> (<?= $nbO[$k] ?>)</button>
<?php endforeach ?>
<?php foreach ($groupes as $g => $nb): ?>        <button type="button" class="puce-adm" data-choisir-groupe="<?= e($g) ?>"><?= e($g) ?> (<?= $nb ?>)</button>
<?php endforeach ?>
<?php if ($news): ?>        <button type="button" class="puce-adm" data-choisir="newsletter">Inscrits newsletter (<?= count($news) ?>)</button>
<?php endif ?>
        <button type="button" class="puce-adm vider" data-choisir="aucun">Aucun</button>
      </div>
      <input type="search" class="filtre-dest" placeholder="Chercher un nom…" aria-label="Chercher un destinataire" data-filtre-dest>
      <ul class="liste-dest">
<?php foreach ($dest as $k => $d): ?>
        <li data-type="<?= $d['type'] ?>" data-origines="<?= e(json_encode($d['origines'])) ?>" data-groupes="<?= e(json_encode($d['groupes'], JSON_UNESCAPED_UNICODE)) ?>" data-abonne="<?= $d['abonne'] ? '1' : '0' ?>">
          <label class="case"><input type="checkbox" name="dest[]" value="<?= e($k) ?>" data-email="<?= e($d['email']) ?>"<?= isset($coches[$k]) ? ' checked' : '' ?>>
          <span><?= $d['nom'] ? '<strong>' . e($d['nom']) . '</strong> ' : '' ?><?= $d['type'] === 'artiste' ? badges_origine(['projets' => $d['origines']]) : '' ?><small><?= e($d['email']) ?><?= $d['type'] === 'newsletter' ? ' · newsletter' : '' ?><?= !$d['abonne'] ? ' · ne veut plus d\'envois groupés' : '' ?></small></span></label>
        </li>
<?php endforeach ?>
      </ul>
<?php endif ?>
    </section>

    <section class="bloc">
      <div class="champs">
        <div class="champ full"><label for="sujet">Objet</label><input id="sujet" name="sujet" required value="<?= e($sujet) ?>" placeholder="Ex. : Vernissage du 14 novembre"></div>
        <div class="champ full"><label for="corps">Message</label>
          <div class="insertions"><button type="button" class="puce-adm" data-inserer="{prenom}">+ prénom</button><button type="button" class="puce-adm" data-inserer="<?= e("\n\n" . texte_programme() . "\n") ?>">+ programme des prochaines expos</button></div>
          <textarea id="corps" name="corps" rows="14" required><?= e($corps) ?></textarea></div>
        <div class="champ full"><label class="case"><input type="checkbox" name="signature" value="1"<?= $avecSignature ? ' checked' : '' ?>> <span>Ajouter la signature de la galerie</span></label>
          <?php if (trim($s['signature'])): ?><pre class="signature"><?= e($s['signature']) ?></pre><?php endif ?></div>
        <div class="champ full"><label class="case"><input type="checkbox" name="copie" value="1"<?= $copie ? ' checked' : '' ?>> <span>M'envoyer une copie (<?= e($moi['email']) ?>)</span></label></div>
      </div>
      <div class="barre-actions">
        <button class="btn" type="submit" data-confirmer-envoi><?= icon('mail') ?> Envoyer</button>
        <button class="btn ligne" type="button" data-mailto title="Ouvre votre logiciel de messagerie avec les adresses en copie cachée">Ouvrir dans ma messagerie</button>
      </div>
    </section>
  </div>
</form>

<?php if ($historique): ?>
<section class="bloc">
  <h2>Messages déjà envoyés</h2>
  <div class="table-wrap"><table class="tableau">
    <thead><tr><th scope="col">Date</th><th scope="col">Objet</th><th scope="col">Destinataires</th><th scope="col">Par</th></tr></thead>
    <tbody>
<?php foreach (array_slice($historique, 0, 30) as $h): $ok = count(array_filter($h['destinataires'], fn($d) => $d['ok'])); ?>
      <tr><td><?= fr_date(substr($h['date'], 0, 10), 'dmy') ?></td><td><a class="lien-fort" href="?msg=<?= e($h['id']) ?>"><?= e($h['sujet']) ?></a></td><td><?= count($h['destinataires']) === 1 ? e($h['destinataires'][0]['nom'] ?: $h['destinataires'][0]['email']) : count($h['destinataires']) . ' personnes' ?><?= $ok < count($h['destinataires']) ? ' <span class="etat brouillon">' . (count($h['destinataires']) - $ok) . ' échec(s)</span>' : '' ?></td><td><?= e($h['auteur']) ?></td></tr>
<?php endforeach ?>
    </tbody>
  </table></div>
</section>
<?php endif ?>
<?php admin_end();
