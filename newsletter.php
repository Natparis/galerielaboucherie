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
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['news_msg'] = ['err', 'Cette adresse e-mail ne semble pas valide.'];
    redirect($retour);
}
$list = data_load('newsletter', []);
foreach ($list as $row) {
    if ($row['email'] === $email) {
        $_SESSION['news_msg'] = ['ok', 'Vous êtes déjà inscrit·e : à bientôt sous le store !'];
        redirect($retour);
    }
}
$list[] = ['id' => new_id(), 'email' => $email, 'date' => date('c')];
data_save('newsletter', $list);
$_SESSION['news_msg'] = ['ok', 'Merci ! Vous recevrez le programme des prochaines expositions.'];
redirect($retour);
