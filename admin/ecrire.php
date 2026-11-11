<?php
/**
 * « Écrire » : éditeur de courriers (modèles pré-rédigés, mise en forme, blocs « prochain
 * accrochage » et « programme »), envoi individuel ou groupé, aperçu en direct, historique.
 */
require __DIR__ . '/inc.php';
$moi = require_login();
require_once __DIR__ . '/copains.php';
require_once __DIR__ . '/courriers.php';

$s = settings();
$rep = repertoire();
$news = data_load('newsletter', []);
$modeles = modeles();

/** Tous les destinataires possibles, indexés par clé ('a:<id>' répertoire, 'n:<id>' inscrit au programme). */
$dest = [];
foreach ($rep as $a) {
    if ($a['email'] !== '') {
        $dest['a:' . $a['id']] = ['nom' => $a['nom'], 'email' => $a['email'], 'groupes' => groupes_de($a), 'origines' => origines_de($a), 'artiste' => est_artiste($a), 'abonne' => (bool)$a['abonne'], 'type' => 'artiste'];
    }
}
foreach ($news as $n) {
    $dest['n:' . $n['id']] = ['nom' => trim(($n['prenom'] ?? '') . ' ' . ($n['nom'] ?? '')), 'email' => $n['email'], 'groupes' => [], 'origines' => [], 'abonne' => true, 'type' => 'newsletter'];
}

/** Corps d'un ancien message (texte brut avant l'éditeur, HTML ensuite). */
function corps_html(array $m): string
{
    return ($m['format'] ?? '') === 'html' ? nettoyer_html($m['corps']) : paras($m['corps']);
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
    admin_start('Écrire', 'ecrire.php');
    ?>
<?= onglets_ecrire('ecrire') ?>
<p class="fil"><a href="ecrire.php">← Nouveau message</a></p>
<div class="deux">
  <section class="bloc">
    <p class="aide">Envoyé le <?= fr_date(substr($m['date'], 0, 10), 'longy') ?> à <?= e(substr($m['date'], 11, 5)) ?> par <?= e($m['auteur']) ?></p>
    <h2><?= e($m['sujet']) ?></h2>
    <div class="message message-html"><?= corps_html($m) ?></div>
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

// Modèle de départ
$modele = (string)($_GET['modele'] ?? (($_GET['tous'] ?? '') === 'newsletter' ? 'accrochage' : 'libre'));
if (!isset($modeles[$modele])) {
    $modele = 'libre';
}
$sujet = $modeles[$modele]['sujet'];
$corps = $modeles[$modele]['corps'];
$avecSignature = true;
$copie = false;
$coches = [];

// Pré-sélection : ?ids[]=… (fiches du répertoire), ?groupe=…, ?origine=…, ?tous=newsletter
foreach ((array)($_GET['ids'] ?? []) as $id) {
    if (isset($dest['a:' . $id])) $coches[] = 'a:' . $id;
}
foreach ($dest as $k => $d) {
    if (isset($_GET['groupe']) && $d['abonne'] && in_array($_GET['groupe'], $d['groupes'], true)) $coches[] = $k;
    if (isset($_GET['origine']) && $d['abonne'] && in_array($_GET['origine'], $d['origines'], true)) $coches[] = $k;
    if (($_GET['tous'] ?? '') === 'newsletter' && $d['type'] === 'newsletter') $coches[] = $k;
}
if (isset($_GET['reprendre'])) {
    foreach ($historique as $h) {
        if ($h['id'] === $_GET['reprendre']) {
            $sujet = $h['sujet'];
            $corps = corps_html($h);
            $modele = '';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $sujet = trim((string)($_POST['sujet'] ?? ''));
    $corps = nettoyer_html((string)($_POST['corps'] ?? ''));
    $modele = '';
    $avecSignature = !empty($_POST['signature']);
    $copie = !empty($_POST['copie']);
    $coches = array_values(array_unique(array_filter((array)($_POST['dest'] ?? []), fn($k) => isset($dest[$k]))));
    $err = [];
    if (!$coches) $err[] = 'Choisissez au moins un destinataire.';
    if ($sujet === '') $err[] = 'Indiquez un objet.';
    if (trim(html_vers_texte($corps)) === '') $err[] = 'Le message est vide.';
    if ($err) {
        flash(implode(' ', $err), 'err');
    } else {
        @set_time_limit(300);
        $sig = $avecSignature && trim($s['signature']) !== '' ? "\n\n-- \n" . $s['signature'] : '';
        $log = [];
        $dejaEcrit = [];
        foreach ($coches as $k) {
            $d = $dest[$k];
            // Une même adresse (présente au répertoire ET inscrite au programme) ne reçoit qu'un message.
            if (isset($dejaEcrit[mb_strtolower($d['email'])])) {
                continue;
            }
            $dejaEcrit[mb_strtolower($d['email'])] = true;
            $perso = personnaliser_html($corps, $d['nom']);
            $objet = personnaliser($sujet, ['nom' => $d['nom']]);
            $desinscription = $d['type'] === 'newsletter' ? lien_desinscription($d['email']) : '';
            $texte = html_vers_texte($perso) . $sig
                . ($desinscription ? "\n\nVous recevez ce message car vous êtes inscrit·e au programme de " . $s['nom'] . ".\nPour ne plus le recevoir : " . $desinscription : '');
            $ok = envoyer_mail_html($d['email'], $objet, email_html($perso, $objet, $avecSignature, $desinscription), $texte);
            $log[] = ['nom' => $d['nom'], 'email' => $d['email'], 'ok' => $ok];
            usleep(150000); // ne pas saturer le serveur d'envoi
        }
        if ($copie && filter_var($moi['email'], FILTER_VALIDATE_EMAIL)) {
            $perso = personnaliser_html($corps, $moi['nom']);
            envoyer_mail_html($moi['email'], '[Copie] ' . $sujet, email_html($perso, $sujet, $avecSignature), html_vers_texte($perso) . $sig);
        }
        $m = ['id' => new_id(), 'date' => date('c'), 'auteur' => $moi['nom'], 'sujet' => $sujet, 'corps' => $corps, 'format' => 'html', 'destinataires' => $log];
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
admin_start('Écrire', 'ecrire.php');
?>
<?= onglets_ecrire('ecrire') ?>
<?php if (!$s['email_expediteur']): ?><div class="flash err">Conseil : renseignez une « adresse d'expédition » dans <a href="page.php?p=reglages">Coordonnées et réglages</a> pour que les messages n'arrivent pas en indésirables.</div><?php endif ?>

<div class="avec-apercu">
<form method="post" class="formulaire ecrire" data-garde data-apercu="apercu-email.php">
  <?= csrf_field() ?>

  <section class="bloc">
    <h2><span class="etape">1</span> Choisir un modèle</h2>
    <div class="modeles-liste" role="group" aria-label="Modèles de courrier">
<?php foreach ($modeles as $k => $md): ?>
      <button type="button" class="modele" data-modele="<?= e($k) ?>" aria-pressed="<?= $k === $modele ? 'true' : 'false' ?>"><strong><?= e($md['nom']) ?></strong><span><?= e($md['aide']) ?></span></button>
<?php endforeach ?>
    </div>
  </section>

  <section class="bloc">
    <h2><span class="etape">2</span> Rédiger</h2>
    <div class="champs">
      <div class="champ full"><label for="sujet">Objet</label><input id="sujet" name="sujet" value="<?= e($sujet) ?>" placeholder="Ex. : Vernissage du 14 novembre"></div>
      <div class="champ full">
        <span class="lbl" id="lbl-message">Message</span>
        <div class="outils-editeur" role="toolbar" aria-label="Mise en forme" data-outils>
          <button type="button" data-cmd="bold" title="Gras"><b>G</b></button>
          <button type="button" data-cmd="italic" title="Italique"><i>I</i></button>
          <button type="button" data-cmd="titre" title="Titre">Titre</button>
          <button type="button" data-cmd="para" title="Texte normal">Texte</button>
          <button type="button" data-cmd="insertUnorderedList" title="Liste à puces">• Liste</button>
          <button type="button" data-cmd="lien" title="Ajouter un lien">Lien</button>
          <button type="button" data-cmd="removeFormat" title="Enlever la mise en forme">Effacer le style</button>
          <span class="sep" aria-hidden="true"></span>
          <button type="button" data-inserer="prenom" title="Insère le prénom de chaque destinataire">+ Prénom</button>
          <button type="button" data-inserer="accrochage" title="Affiche, artiste, date du vernissage et texte de la prochaine expo">+ Prochain accrochage</button>
          <button type="button" data-inserer="programme" title="La liste des prochains vernissages">+ Programme</button>
        </div>
        <div class="editeur" contenteditable="true" role="textbox" aria-multiline="true" aria-labelledby="lbl-message" data-editeur><?= $corps ?></div>
        <textarea name="corps" hidden data-corps><?= e($corps) ?></textarea>
        <p class="aide"><code>{prenom}</code> est remplacé par le prénom de chaque destinataire. Les blocs insérés se modifient comme le reste du texte.</p>
      </div>
      <div class="champ full"><label class="case"><input type="checkbox" name="signature" value="1"<?= $avecSignature ? ' checked' : '' ?>> <span>Ajouter la signature de la galerie</span></label></div>
      <div class="champ full"><label class="case"><input type="checkbox" name="copie" value="1"<?= $copie ? ' checked' : '' ?>> <span>M'envoyer une copie (<?= e($moi['email']) ?>)</span></label></div>
    </div>
  </section>

  <section class="bloc dest">
    <h2><span class="etape">3</span> Destinataires <small data-compte-dest>(<?= count($coches) ?>)</small></h2>
    <p class="aide">Chaque personne reçoit son propre e-mail : les autres destinataires restent invisibles. Les réponses arrivent à l'adresse de contact<?= $s['email'] ? ' (' . e($s['email']) . ')' : '' ?>.</p>
<?php if (!$dest): ?>
    <p class="vide-etat">Aucun contact avec e-mail. Remplissez d'abord le <a href="repertoire.php">répertoire</a>.</p>
<?php else: ?>
    <div class="raccourcis" aria-label="Sélection rapide">
<?php if ($news): ?>      <button type="button" class="puce-adm" data-choisir="newsletter">Inscrits au programme (<?= count($news) ?>)</button>
<?php endif ?>
      <button type="button" class="puce-adm" data-choisir="seuls-artistes">Tous les artistes</button>
      <button type="button" class="puce-adm" data-choisir="artiste">Tous les contacts du répertoire</button>
<?php $nbO = []; foreach ($dest as $d) foreach ($d['origines'] as $o) $nbO[$o] = ($nbO[$o] ?? 0) + 1;
      foreach (ORIGINES as $k => [$nom, $court, $coul]): if (empty($nbO[$k]) || count($nbO) < 2) continue; ?>      <button type="button" class="puce-adm origine-btn" style="--c:<?= $coul ?>" data-choisir-origine="<?= e($k) ?>"><?= e($nom) ?> (<?= $nbO[$k] ?>)</button>
<?php endforeach ?>
<?php foreach ($groupes as $g => $nb): ?>      <button type="button" class="puce-adm" data-choisir-groupe="<?= e($g) ?>"><?= e($g) ?> (<?= $nb ?>)</button>
<?php endforeach ?>
      <button type="button" class="puce-adm vider" data-choisir="aucun">Aucun</button>
    </div>
    <input type="search" class="filtre-dest" placeholder="Chercher un nom…" aria-label="Chercher un destinataire" data-filtre-dest>
    <ul class="liste-dest">
<?php foreach ($dest as $k => $d): ?>
      <li data-type="<?= $d['type'] ?>" data-artiste="<?= !empty($d['artiste']) ? '1' : '0' ?>" data-origines="<?= e(json_encode($d['origines'])) ?>" data-groupes="<?= e(json_encode($d['groupes'], JSON_UNESCAPED_UNICODE)) ?>" data-abonne="<?= $d['abonne'] ? '1' : '0' ?>">
        <label class="case"><input type="checkbox" name="dest[]" value="<?= e($k) ?>" data-email="<?= e($d['email']) ?>"<?= isset($coches[$k]) ? ' checked' : '' ?>>
        <span><?= $d['nom'] ? '<strong>' . e($d['nom']) . '</strong> ' : '' ?><?= $d['type'] === 'artiste' ? badges_origine(['projets' => $d['origines'], 'cda_id' => 'x']) : '' ?><small><?= e($d['email']) ?><?= $d['type'] === 'newsletter' ? ' · inscrit au programme' : '' ?><?= !$d['abonne'] ? ' · ne veut plus d\'envois groupés' : '' ?></small></span></label>
      </li>
<?php endforeach ?>
    </ul>
<?php endif ?>
  </section>

  <div class="barre-actions">
    <button class="btn" type="submit" data-confirmer-envoi><?= icon('mail') ?> Envoyer</button>
    <button class="btn ligne" type="button" data-mailto title="Ouvre votre logiciel de messagerie avec les adresses en copie cachée">Ouvrir dans ma messagerie</button>
  </div>
</form>
<?= apercu_panneau(720, 'Aperçu de l\'e-mail', 'Tel que le recevra le premier destinataire coché. Rien n\'est envoyé tant que vous n\'avez pas cliqué sur « Envoyer ».') ?>
</div>

<?php foreach ($modeles as $k => $md): ?>
<template data-modele-tpl="<?= e($k) ?>" data-sujet="<?= e($md['sujet']) ?>" data-pour="<?= e($md['pour']) ?>"><?= $md['corps'] ?></template>
<?php endforeach ?>
<template data-bloc="accrochage"><?= bloc_accrochage() ?></template>
<template data-bloc="programme"><?= bloc_programme() ?></template>

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
