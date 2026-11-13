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
                    $u['provisoire'] = false;
                }
            }
            unset($u);
            data_save('utilisateurs', $users);
            flash('Votre mot de passe a été changé.');
        }
    } elseif ($action === 'moi') {
        // Chacun règle son nom, son identifiant et son e-mail
        $nom = trim((string)($_POST['nom'] ?? ''));
        $ident = slugify((string)($_POST['identifiant'] ?? ''));
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
        $autres = array_filter($users, fn($u) => $u['id'] !== $moi['id']);
        $pris = array_merge(array_map(fn($u) => mb_strtolower($u['identifiant'] ?? ''), $autres), array_column($autres, 'email'));
        if ($nom === '' || $ident === 'element') {
            flash('Indiquez votre nom et un identifiant.', 'err');
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Cette adresse e-mail ne semble pas valide.', 'err');
        } elseif (in_array($ident, $pris, true) || ($email !== '' && in_array($email, $pris, true))) {
            flash('Cet identifiant ou cet e-mail est déjà utilisé par un autre compte.', 'err');
        } else {
            foreach ($users as &$u) {
                if ($u['id'] === $moi['id']) {
                    $u['nom'] = $nom;
                    $u['identifiant'] = $ident;
                    $u['email'] = $email;
                }
            }
            unset($u);
            data_save('utilisateurs', $users);
            flash('Vos informations sont enregistrées. Vous pouvez vous connecter avec « ' . $ident . ' »' . ($email ? ' ou « ' . $email . ' »' : '') . '.');
        }
    } elseif (!$responsable) {
        // Tout le reste est réservé aux responsables
        flash('Seul un responsable peut donner ou retirer des accès.', 'err');
    } elseif ($action === 'ajouter') {
        $nom = trim((string)($_POST['nom'] ?? ''));
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
        $ident = slugify((string)($_POST['identifiant'] ?? '') ?: explode(' ', $nom)[0]);
        $pris = array_merge(array_map(fn($u) => mb_strtolower($u['identifiant'] ?? ''), $users), array_filter(array_column($users, 'email')));
        if ($nom === '' || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            flash('Indiquez un nom, et une adresse e-mail valide si vous en donnez une.', 'err');
        } elseif (in_array($ident, $pris, true) || ($email !== '' && in_array($email, $pris, true))) {
            flash('Un compte existe déjà avec cet identifiant ou cette adresse.', 'err');
        } elseif (mb_strlen($mdp) < 10) {
            flash('Le mot de passe doit faire au moins 10 caractères.', 'err');
        } else {
            $resp = !empty($_POST['admin']);
            $users[] = ['id' => new_id(), 'nom' => $nom, 'identifiant' => $ident, 'email' => $email, 'hash' => password_hash($mdp, PASSWORD_DEFAULT), 'admin' => $resp, 'provisoire' => true, 'cree' => date('c'), 'cree_par' => $moi['nom']];
            data_save('utilisateurs', $users);
            flash('Compte créé pour ' . $nom . ($resp ? ', qui pourra aussi donner des accès' : '') . '. Identifiant : « ' . $ident . ' ». Transmettez-lui son mot de passe provisoire de vive voix : le site lui demandera de le changer.');
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
            $users[$cible]['provisoire'] = true;
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
          <span><strong><?= e($u['nom']) ?></strong> <small><?= e(implode(' · ', array_filter([$u['identifiant'] ?? '', $u['email'] ?? '']))) ?><?= $lui ? ' · vous' : '' ?><?= !empty($u['provisoire']) ? ' · mot de passe provisoire' : '' ?></small></span>
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
        <div class="champ full"><label for="nom">Prénom et nom</label><input id="nom" name="nom" required></div>
        <div class="champ half"><label for="identifiant">Identifiant de connexion</label><p class="aide">Vide = le prénom, en minuscules.</p><input id="identifiant" name="identifiant" autocapitalize="none" autocomplete="off"></div>
        <div class="champ half"><label for="email">E-mail (facultatif)</label><p class="aide">Permet aussi de se connecter.</p><input id="email" type="email" name="email"></div>
        <div class="champ full"><label for="mdp">Mot de passe provisoire (10 caractères minimum)</label><input id="mdp" type="text" name="mdp" required minlength="10" autocomplete="off"></div>
        <div class="champ full"><label class="case"><input type="checkbox" name="admin" value="1"> <span>Responsable : cette personne pourra aussi donner et retirer des accès</span></label></div>
      </div>
      <div class="barre-actions"><button class="btn" name="action" value="ajouter"><?= icon('plus') ?> Créer le compte</button></div>
    </form>
<?php else: ?>
    <p class="aide">Pour ajouter une personne ou retrouver un mot de passe, adressez-vous à un responsable.</p>
<?php endif ?>
  </section>
  <div style="display:flex;flex-direction:column;gap:24px">
  <section class="bloc">
    <h2>Mon compte</h2>
    <form method="post" class="formulaire">
      <?= csrf_field() ?>
      <div class="champs">
        <div class="champ full"><label for="mon-nom">Prénom et nom</label><input id="mon-nom" name="nom" required value="<?= e($moi['nom']) ?>"></div>
        <div class="champ half"><label for="mon-ident">Identifiant de connexion</label><input id="mon-ident" name="identifiant" required autocapitalize="none" value="<?= e($moi['identifiant'] ?? '') ?>"></div>
        <div class="champ half"><label for="mon-email">E-mail</label><input id="mon-email" type="email" name="email" value="<?= e($moi['email'] ?? '') ?>"></div>
      </div>
      <div class="barre-actions"><button class="btn ligne" name="action" value="moi">Enregistrer</button></div>
    </form>
  </section>
  <section class="bloc" id="mon-mdp">
    <h2>Changer mon mot de passe</h2>
<?php if (!empty($moi['provisoire'])): ?>    <div class="flash err">Votre mot de passe actuel est provisoire : choisissez le vôtre, connu de vous seul.</div>
<?php endif ?>
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
</div>
<?php admin_end();
