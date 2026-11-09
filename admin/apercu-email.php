<?php
/**
 * Aperçu en direct de l'e-mail en cours de rédaction (page « Écrire »).
 * Montre le message tel que le recevra le premier destinataire coché (ou « Marie » par défaut).
 */
require __DIR__ . '/inc.php';
require_login();
require_once __DIR__ . '/copains.php';
require_once __DIR__ . '/courriers.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
csrf_check();

$corps = nettoyer_html((string)($_POST['corps'] ?? ''));
$sujet = trim((string)($_POST['sujet'] ?? ''));
$nom = 'Marie Exemple';
$desinscription = '';
$premier = (string)(((array)($_POST['dest'] ?? []))[0] ?? '');
if (str_starts_with($premier, 'a:')) {
    foreach (repertoire() as $a) {
        if ('a:' . $a['id'] === $premier) {
            $nom = $a['nom'];
        }
    }
} elseif (str_starts_with($premier, 'n:')) {
    foreach (data_load('newsletter', []) as $n) {
        if ('n:' . $n['id'] === $premier) {
            $nom = trim(($n['prenom'] ?? '') . ' ' . ($n['nom'] ?? ''));
            $desinscription = lien_desinscription($n['email']);
        }
    }
}

header_remove('X-Frame-Options');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');
$html = email_html(personnaliser_html($corps !== '' ? $corps : '<p><em>Votre message apparaîtra ici.</em></p>', $nom), personnaliser($sujet, ['nom' => $nom]), !empty($_POST['signature']), $desinscription);
// En-tête façon messagerie, au-dessus du message
$entete = '<div style="max-width:600px;margin:0 auto;padding:14px 12px 0;font:13px/1.5 Arial,sans-serif;color:#4F5B66">'
    . '<div><strong style="color:#1E2A36">À :</strong> ' . e($nom) . '</div>'
    . '<div><strong style="color:#1E2A36">Objet :</strong> ' . e(personnaliser($sujet, ['nom' => $nom]) ?: '(sans objet)') . '</div></div>';
echo preg_replace('/(<body[^>]*>)/', '$1<div style="background:#F4F1EC">' . $entete . '</div>', $html, 1)
    . '<script>document.addEventListener("click",function(e){if(e.target.closest("a")){e.preventDefault();}},true);</script>';
