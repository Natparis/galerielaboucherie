<?php
require __DIR__ . '/inc.php';

$users = users();
$premier = !$users; // aucun compte : création du compte principal
$r = (string)($_GET['r'] ?? $_POST['r'] ?? '');
$cible = preg_match('/^[a-z]+\.php(\?[\w=&%.-]*)?$/', $r) ? $r : 'index.php';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
    $mdp = (string)($_POST['mdp'] ?? '');
    if ($premier) {
        $nom = trim((string)($_POST['nom'] ?? ''));
        if ($nom === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erreur = 'Indiquez un nom et une adresse e-mail valide.';
        } elseif (mb_strlen($mdp) < 10) {
            $erreur = 'Le mot de passe doit faire au moins 10 caractères.';
        } elseif ($mdp !== (string)($_POST['mdp2'] ?? '')) {
            $erreur = 'Les deux mots de passe ne sont pas identiques.';
        } else {
            $u = ['id' => new_id(), 'nom' => $nom, 'identifiant' => slugify(explode(' ', $nom)[0]), 'email' => $email, 'hash' => password_hash($mdp, PASSWORD_DEFAULT), 'admin' => true, 'cree' => date('c')];
            data_save('utilisateurs', [$u]);
            session_regenerate_id(true);
            $_SESSION['uid'] = $u['id'];
            flash('Bienvenue ! Votre compte est créé. Tout se gère depuis ce menu.');
            redirect('index.php');
        }
    } else {
        $_SESSION['essais'] = ($_SESSION['essais'] ?? 0) + 1;
        if ($_SESSION['essais'] > 5) {
            sleep(min(10, $_SESSION['essais'] - 5)); // ralentit les tentatives répétées
        }
        foreach ($users as $u) {
            // On se connecte avec son identifiant (ex. son prénom) ou son adresse e-mail
            $bonCompte = $email !== '' && (($u['email'] ?? '') === $email || mb_strtolower($u['identifiant'] ?? '') === $email);
            if ($bonCompte && password_verify($mdp, $u['hash'])) {
                session_regenerate_id(true);
                $_SESSION['uid'] = $u['id'];
                $_SESSION['essais'] = 0;
                redirect($cible);
            }
        }
        $erreur = 'Identifiant ou mot de passe incorrect.';
    }
}

if (current_user()) {
    redirect('index.php');
}

admin_start($premier ? 'Créer le compte de la galerie' : 'Connexion');
?>
<div class="carte-login">
  <div class="store" aria-hidden="true"></div>
  <div class="frange" aria-hidden="true"></div>
  <form method="post" class="login">
    <p class="enseigne"><?= e(settings()['nom']) ?></p>
    <h1><?= $premier ? 'Bienvenue dans l\'espace galerie' : 'Espace galerie' ?></h1>
    <?php if ($premier): ?><p class="aide">Première visite : créez le compte qui gérera le site. Vous pourrez ajouter d'autres personnes ensuite.</p><?php endif ?>
    <?php if ($erreur): ?><div class="flash err" role="alert"><?= e($erreur) ?></div><?php endif ?>
    <?= csrf_field() ?>
    <input type="hidden" name="r" value="<?= e($r) ?>">
    <?php if ($premier): ?>
    <div class="champ"><label for="nom">Votre prénom et nom</label><input id="nom" name="nom" required autocomplete="name" value="<?= e($_POST['nom'] ?? '') ?>"></div>
    <?php endif ?>
    <div class="champ"><label for="email"><?= $premier ? 'E-mail' : 'Identifiant ou e-mail' ?></label><input id="email" type="<?= $premier ? 'email' : 'text' ?>" name="email" required autocomplete="username" autocapitalize="none" value="<?= e($_POST['email'] ?? '') ?>"></div>
    <div class="champ"><label for="mdp">Mot de passe<?= $premier ? ' (10 caractères minimum)' : '' ?></label><input id="mdp" type="password" name="mdp" required autocomplete="<?= $premier ? 'new-password' : 'current-password' ?>"<?= $premier ? ' minlength="10"' : '' ?>></div>
    <?php if ($premier): ?>
    <div class="champ"><label for="mdp2">Mot de passe, encore une fois</label><input id="mdp2" type="password" name="mdp2" required autocomplete="new-password" minlength="10"></div>
    <?php endif ?>
    <button class="btn" type="submit"><?= $premier ? 'Créer le compte' : 'Se connecter' ?></button>
    <a class="retour" href="../index.php">← Retour au site</a>
  </form>
</div>
<?php admin_end();
