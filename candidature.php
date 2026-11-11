<?php
require __DIR__ . '/inc/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('exposer.php');
}
csrf_check();
if (!empty($_POST['site'])) {
    redirect('exposer.php');
}

$in = [];
foreach (['nom', 'email', 'telephone', 'discipline', 'mois', 'lien', 'message'] as $k) {
    $in[$k] = trim(mb_substr((string)($_POST[$k] ?? ''), 0, $k === 'message' ? 5000 : 300));
}
$err = [];
if ($in['nom'] === '') $err[] = 'Indiquez votre nom.';
if (!filter_var($in['email'], FILTER_VALIDATE_EMAIL)) $err[] = 'L\'adresse e-mail ne semble pas valide.';
if ($in['message'] === '') $err[] = 'Dites-nous quelques mots de votre travail.';
if ($in['lien'] !== '' && !preg_match('#^https?://#i', $in['lien'])) $in['lien'] = 'https://' . $in['lien'];
// Une vraie adresse a un nom de domaine (instagram.com, mon-site.fr…) ; sinon on l'ignore.
if ($in['lien'] !== '' && !preg_match('/^[a-z0-9-]+(\.[a-z0-9-]+)*\.[a-z]{2,}$/i', (string)parse_url($in['lien'], PHP_URL_HOST))) $in['lien'] = '';
if (empty($_POST['rgpd'])) $err[] = 'Merci d\'accepter la conservation de vos informations.';

$id = date('Ymd-His') . '-' . substr(new_id(), 0, 6);
$photos = [];
if (!$err && !empty($_FILES['photos']['name'][0])) {
    $n = min(3, count($_FILES['photos']['name']));
    for ($i = 0; $i < $n; $i++) {
        $f = ['name' => $_FILES['photos']['name'][$i], 'type' => $_FILES['photos']['type'][$i], 'tmp_name' => $_FILES['photos']['tmp_name'][$i], 'error' => $_FILES['photos']['error'][$i], 'size' => $_FILES['photos']['size'][$i]];
        try {
            $saved = save_upload($f, ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'], DATA_DIR . '/candidatures/' . $id, '');
            if ($saved) $photos[] = $saved;
        } catch (RuntimeException $ex) {
            $err[] = $ex->getMessage();
        }
    }
}

if ($err) {
    $_SESSION['cand_old'] = $in;
    flash(implode(' ', $err), 'err');
    redirect('exposer.php');
}

$list = data_load('candidatures', []);
$list[] = $in + ['id' => $id, 'date' => date('c'), 'statut' => 'nouvelle', 'photos' => $photos, 'note' => ''];
data_save('candidatures', $list);

$s = settings();
if ($s['email_notification'] && function_exists('mail')) {
    $body = "Nouvelle candidature reçue sur le site :\n\n"
        . "Nom : {$in['nom']}\nE-mail : {$in['email']}\nTéléphone : {$in['telephone']}\nDiscipline : {$in['discipline']}\n"
        . "Mois souhaité : {$in['mois']}\nLien : {$in['lien']}\nPhotos : " . count($photos) . "\n\n{$in['message']}\n\n"
        . "À consulter dans l'espace galerie, rubrique Candidatures.";
    $headers = 'Content-Type: text/plain; charset=utf-8' . "\r\n" . 'Reply-To: ' . str_replace(["\r", "\n"], '', $in['email']);
    @mail($s['email_notification'], '=?UTF-8?B?' . base64_encode('Candidature : ' . $in['nom']) . '?=', $body, $headers);
}

flash(single('exposer')['merci']);
redirect('exposer.php');
