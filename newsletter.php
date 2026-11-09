<?php
require __DIR__ . '/inc/bootstrap.php';

$retour = (string)($_POST['retour'] ?? 'programme.php');
if (!preg_match('/^[a-z]+\.php(#[a-z]+)?$/', $retour)) {
    $retour = 'programme.php';
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('programme.php');
}
csrf_check();
if (!empty($_POST['site'])) { // pot de miel anti-robots
    redirect($retour);
}
$email = mb_strtolower(trim((string)($_POST['email'] ?? '')));
$prenom = trim(mb_substr((string)($_POST['prenom'] ?? ''), 0, 80));
$nom = trim(mb_substr((string)($_POST['nom'] ?? ''), 0, 80));
if ($prenom === '' || $nom === '') {
    $_SESSION['news_msg'] = ['err', 'Indiquez votre prénom et votre nom.'];
    redirect($retour);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['news_msg'] = ['err', 'Cette adresse e-mail ne semble pas valide.'];
    redirect($retour);
}
$list = data_load('newsletter', []);
foreach ($list as $i => $row) {
    if ($row['email'] === $email) {
        // déjà inscrit : on complète le prénom et le nom s'ils manquaient
        if (empty($row['prenom']) || empty($row['nom'])) {
            $list[$i]['prenom'] = ($row['prenom'] ?? '') ?: $prenom;
            $list[$i]['nom'] = ($row['nom'] ?? '') ?: $nom;
            data_save('newsletter', $list);
        }
        $_SESSION['news_msg'] = ['ok', 'Vous êtes déjà inscrit·e : à bientôt sous le store !'];
        redirect($retour);
    }
}
$list[] = ['id' => new_id(), 'prenom' => $prenom, 'nom' => $nom, 'email' => $email, 'date' => date('c')];
data_save('newsletter', $list);
$_SESSION['news_msg'] = ['ok', 'Merci ' . $prenom . ' ! Vous recevrez le programme des prochaines expositions.'];
redirect($retour);
