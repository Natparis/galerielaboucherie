<?php
/**
 * Comptes d'accès au back-office.
 * Les « responsables » donnent, modifient et retirent les accès ; les autres comptes
 * gèrent le site mais ne voient ici que le changement de leur propre mot de passe.
 */
require __DIR__ . '/inc.php';
$moi = require_login();
$users = users();
$responsable = est_responsable($moi);
$nbResp = count(array_filter($users, 'est_responsable'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');
    $mdp = (string)($_POST['mdp'] ?? '');
    $id = (string)($_POST['id'] ?? '');
    $cible = null;
    foreach ($users as $i => $u) {
        if ($u['id'] === $id) {
            $cible = $i;
        }
    }

    if ($action === 'mdp') {
        // Chacun peut changer son propre mot de passe
        if (!password_verify((string)($_POST['actuel'] ?? ''), $moi['hash'])) {
            flash('Le mot de passe actuel est incorrect.', 'err');
        } elseif (mb_strlen($mdp) < 10) {
            flash('Le nouveau mot de passe doit faire au moins 10 caractères.', 'err');
        } else {
            foreach ($users as &$u) {
                if ($u['id'] === $moi['id']) {
                    $u['hash'] = password_hash($mdp, PASSWORD_DEFAULT);
                }
            }
            unset($u);
            data_save('utilisateurs', $users);
            flash('Votre mot de passe a été changé.');
        }
    } elseif (!$responsable) {
        // Tout le reste est réservé aux responsables
        flash('Seul un responsable peut donner ou retirer des accès.', 'err');
    } elseif ($action === 'ajouter') {
        $nom = trim((string)($_POST['nom'] ?? ''));
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
        if ($nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Indiquez un nom et une adresse e-mail valide.', 'err');
        } elseif (in_array($email, array_column($users, 'email'), true)) {
            flash('Un compte existe déjà avec cette adresse.', 'err');
        } elseif (mb_strlen($mdp) < 10) {
            flash('Le mot de passe doit faire au moins 10 caractères.', 'err');
        } else {
            $resp = !empty($_POST['admin']);
            $users[] = ['id' => new_id(), 'nom' => $nom, 'email' => $email, 'hash' => password_hash($mdp, PASSWORD_DEFAULT), 'admin' => $resp, 'cree' => date('c'), 'cree_par' => $moi['nom']];
            data_save('utilisateurs', $users);
            flash('Compte créé pour ' . $nom . ($resp ? ', qui pourra aussi donner des accès' : '') . '. Transmettez-lui son mot de passe de vive voix : il pourra le changer ensuite.');
        }
    } elseif ($cible === null) {
        flash('Compte introuvable.', 'err');
    } elseif ($action === 'role') {
        $devient = empty($users[$cible]['admin']);
        if (!$devient && $nbResp <= 1) {
            flash('Il doit rester au moins un responsable.', 'err');
        } else {
            $users[$cible]['admin'] = $devient;
            data_save('utilisateurs', $users);
            flash($users[$cible]['nom'] . ($devient ? ' peut maintenant donner et retirer des accès.' : ' ne peut plus donner d\'accès.'));
        }
    } elseif ($action === 'nouveau_mdp') {
        if (mb_strlen($mdp) < 10) {
            flash('Le mot de passe doit faire au moins 10 caractères.', 'err');
        } else {
            $users[$cible]['hash'] = password_hash($mdp, PASSWORD_DEFAULT);
            data_save('utilisateurs', $users);
            flash('Nouveau mot de passe enregistré pour ' . $users[$cible]['nom'] . '. Transmettez-le-lui de vive voix.');
        }
    } elseif ($action === 'supprimer') {
        if ($id === $moi['id']) {
            flash('Vous ne pouvez pas supprimer votre propre compte.', 'err');
        } elseif (est_responsable($users[$cible]) && $nbResp <= 1) {
            flash('Il doit rester au moins un responsable.', 'err');
        } else {
            $nom = $users[$cible]['nom'];
            array_splice($users, $cible, 1);
            data_save('utilisateurs', array_values($users));
            flash('Le compte de ' . $nom . ' est supprimé.');
        }
    }
    redirect('comptes.php');
}

admin_start('Comptes d\'accès', 'comptes.php');
?>
<div class="deux">
  <section class="bloc">
    <h2>Personnes qui gèrent le site</h2>
    <p class="aide">Les <strong>responsables</strong> peuvent donner, modifier et retirer les accès. Les autres comptes gèrent tout le reste du site.</p>
    <ul class="simple comptes">
<?php foreach ($users as $u): $lui = $u['id'] === $moi['id']; ?>
      <li>
        <div class="compte-ligne">
          <span><strong><?= e($u['nom']) ?></strong> <small><?= e($u['email']) ?><?= $lui ? ' · vous' : '' ?></small></span>
          <span class="etat <?= est_responsable($u) ? 'encours' : 'passe' ?>"><?= est_responsable($u) ? 'Responsable' : 'Accès simple' ?></span>
        </div>
<?php if ($responsable && !$lui): ?>
        <div class="compte-actions">
          <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($u['id']) ?>">
            <button class="btn ligne petit" name="action" value="role"><?= est_responsable($u) ? 'Retirer le droit de donner des accès' : 'Autoriser à donner des accès' ?></button></form>
          <details>
            <summary class="btn ligne petit">Nouveau mot de passe</summary>
            <form method="post" class="mdp-autre"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($u['id']) ?>">
              <label for="mdp-<?= e($u['id']) ?>">Nouveau mot de passe pour <?= e($u['nom']) ?> (10 caractères minimum)</label>
              <input id="mdp-<?= e($u['id']) ?>" type="text" name="mdp" required minlength="10" autocomplete="off">
              <button class="btn petit" name="action" value="nouveau_mdp">Enregistrer</button></form>
          </details>
          <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($u['id']) ?>">
            <button class="ico danger" name="action" value="supprimer" title="Supprimer ce compte" data-confirmer="Supprimer le compte de <?= e($u['nom']) ?> ?"><?= icon('trash') ?><span class="sr-only">Supprimer le compte de <?= e($u['nom']) ?></span></button></form>
        </div>
<?php endif ?>
      </li>
<?php endforeach ?>
    </ul>

<?php if ($responsable): ?>
    <h2 style="margin-top:32px">Donner un accès</h2>
    <form method="post" class="formulaire">
      <?= csrf_field() ?>
      <div class="champs">
        <div class="champ half"><label for="nom">Prénom et nom</label><input id="nom" name="nom" required></div>
        <div class="champ half"><label for="email">E-mail (identifiant)</label><input id="email" type="email" name="email" required></div>
        <div class="champ full"><label for="mdp">Mot de passe provisoire (10 caractères minimum)</label><input id="mdp" type="text" name="mdp" required minlength="10" autocomplete="off"></div>
        <div class="champ full"><label class="case"><input type="checkbox" name="admin" value="1"> <span>Responsable : cette personne pourra aussi donner et retirer des accès</span></label></div>
      </div>
      <div class="barre-actions"><button class="btn" name="action" value="ajouter"><?= icon('plus') ?> Créer le compte</button></div>
    </form>
<?php else: ?>
    <p class="aide">Pour ajouter une personne ou retrouver un mot de passe, adressez-vous à un responsable.</p>
<?php endif ?>
  </section>
  <section class="bloc">
    <h2>Changer mon mot de passe</h2>
    <form method="post" class="formulaire">
      <?= csrf_field() ?>
      <div class="champs">
        <div class="champ full"><label for="actuel">Mot de passe actuel</label><input id="actuel" type="password" name="actuel" required autocomplete="current-password"></div>
        <div class="champ full"><label for="nmdp">Nouveau mot de passe (10 caractères minimum)</label><input id="nmdp" type="password" name="mdp" required minlength="10" autocomplete="new-password"></div>
      </div>
      <div class="barre-actions"><button class="btn" name="action" value="mdp">Changer</button></div>
    </form>
  </section>
</div>
<?php admin_end();
