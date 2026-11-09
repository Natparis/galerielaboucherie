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
    flash('Cette adresse e-mail ne semble pas valide.', 'err');
    redirect($retour);
}
$list = data_load('newsletter', []);
foreach ($list as $row) {
    if ($row['email'] === $email) {
        flash('Vous êtes déjà inscrit·e : à bientôt sous le store !');
        redirect($retour);
    }
}
$list[] = ['id' => new_id(), 'email' => $email, 'date' => date('c')];
data_save('newsletter', $list);
flash('Merci ! Vous recevrez le programme chaque mois.');
redirect($retour);
