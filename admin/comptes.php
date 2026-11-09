<?php
require __DIR__ . '/inc.php';
$moi = require_login();
$users = users();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');
    $mdp = (string)($_POST['mdp'] ?? '');

    if ($action === 'ajouter') {
        $nom = trim((string)($_POST['nom'] ?? ''));
        $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
        if ($nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('Indiquez un nom et une adresse e-mail valide.', 'err');
        } elseif (in_array($email, array_column($users, 'email'), true)) {
            flash('Un compte existe déjà avec cette adresse.', 'err');
        } elseif (mb_strlen($mdp) < 10) {
            flash('Le mot de passe doit faire au moins 10 caractères.', 'err');
        } else {
            $users[] = ['id' => new_id(), 'nom' => $nom, 'email' => $email, 'hash' => password_hash($mdp, PASSWORD_DEFAULT), 'cree' => date('c')];
            data_save('utilisateurs', $users);
            flash('Compte créé pour ' . $nom . '. Transmettez-lui son mot de passe de vive voix.');
        }
    } elseif ($action === 'mdp') {
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
    } elseif ($action === 'supprimer') {
        $id = (string)($_POST['id'] ?? '');
        if ($id === $moi['id']) {
            flash('Vous ne pouvez pas supprimer votre propre compte.', 'err');
        } else {
            $users = array_values(array_filter($users, fn($u) => $u['id'] !== $id));
            data_save('utilisateurs', $users);
            flash('Compte supprimé.');
        }
    }
    redirect('comptes.php');
}

admin_start('Comptes d\'accès', 'comptes.php');
?>
<div class="deux">
  <section class="bloc">
    <h2>Personnes qui gèrent le site</h2>
    <ul class="simple emails">
<?php foreach ($users as $u): ?>
      <li><span><strong><?= e($u['nom']) ?></strong> <small><?= e($u['email']) ?><?= $u['id'] === $moi['id'] ? ' · vous' : '' ?></small></span>
<?php if ($u['id'] !== $moi['id']): ?>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= e($u['id']) ?>"><button class="ico danger" name="action" value="supprimer" title="Supprimer" data-confirmer="Supprimer le compte de <?= e($u['nom']) ?> ?"><?= icon('trash') ?><span class="sr-only">Supprimer le compte de <?= e($u['nom']) ?></span></button></form>
<?php endif ?>
      </li>
<?php endforeach ?>
    </ul>
    <h2 style="margin-top:32px">Ajouter une personne</h2>
    <form method="post" class="formulaire">
      <?= csrf_field() ?>
      <div class="champs">
        <div class="champ half"><label for="nom">Prénom et nom</label><input id="nom" name="nom" required></div>
        <div class="champ half"><label for="email">E-mail (identifiant)</label><input id="email" type="email" name="email" required></div>
        <div class="champ full"><label for="mdp">Mot de passe provisoire (10 caractères minimum)</label><input id="mdp" type="text" name="mdp" required minlength="10" autocomplete="off"></div>
      </div>
      <div class="barre-actions"><button class="btn" name="action" value="ajouter"><?= icon('plus') ?> Créer le compte</button></div>
    </form>
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
