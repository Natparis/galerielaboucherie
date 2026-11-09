<?php
/**
 * Aperçu en direct : affiche la vraie page publique avec les valeurs en cours de saisie,
 * SANS rien enregistrer (les images choisies sont montrées en mémoire).
 * Appelé par le back-office (admin.js) à chaque modification du formulaire.
 */
require __DIR__ . '/inc.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
csrf_check();

$GLOBALS['APERCU'] = true;
$sc = schema();
$post = (array)($_POST['data'] ?? []);
$pages = ['accueil' => 'index.php', 'reglages' => 'index.php', 'page_expo' => 'expo.php', 'programme' => 'programme.php',
    'artistes' => 'artistes.php', 'exposer' => 'exposer.php', 'lieu' => 'lieu.php', 'page_presse' => 'presse.php'];
$fichier = null;
$query = [];

try {
    if (isset($_GET['p'], $sc['singletons'][$_GET['p']], $pages[$_GET['p']])) {
        $p = $_GET['p'];
        $vals = collect_fields($sc['singletons'][$p]['fields'], $post);
        foreach ($sc['singletons'][$p]['fields'] as $k => $f) {
            if (($f['type'] ?? '') === 'secret') {
                unset($vals[$k]); // jamais de clé secrète dans l'aperçu
            }
        }
        $GLOBALS['APERCU_PAGE'][$p] = $vals;
        $fichier = $pages[$p];
    } elseif (isset($_GET['c']) && in_array($_GET['c'], ['expositions', 'presse'], true)) {
        $c = $_GET['c'];
        $vals = collect_fields($sc['collections'][$c]['fields'], $post);
        $vals['id'] = preg_replace('/[^a-z0-9]/', '', (string)($_GET['id'] ?? '')) ?: 'apercu';
        $vals['publie'] = true; // un brouillon est montré comme s'il était publié
        if ($c === 'expositions') {
            $vals['slug'] = 'apercu-' . $vals['id'];
            if ($vals['date_debut'] === '') {
                $vals['date_debut'] = today();
            }
            $query = ['slug' => $vals['slug']];
            $fichier = 'expo.php';
        } else {
            $fichier = 'presse.php'; // l'article en cours est montré à la une (voir presse.php)
        }
        $GLOBALS['APERCU_FICHE'][$c] = $vals;
    }
} catch (RuntimeException $ex) {
    http_response_code(422);
    header('Content-Type: text/plain; charset=utf-8');
    exit($ex->getMessage());
}

if (!$fichier) {
    http_response_code(404);
    exit('Pas d\'aperçu pour cette page.');
}

// Rendu de la page publique comme depuis la racine du site
$GLOBALS['BASE'] = '';
$_GET = $query;
$base = site_url();
header_remove('X-Frame-Options');
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

ob_start(function (string $html) use ($base): string {
    // Liens relatifs résolus depuis la racine du site ; navigation et formulaires désactivés.
    $tete = '<base href="' . htmlspecialchars($base, ENT_QUOTES) . '">'
        . '<script>document.addEventListener("click",function(e){var a=e.target.closest("a");if(a&&!(a.getAttribute("href")||"").startsWith("#")){e.preventDefault();}},true);'
        . 'document.addEventListener("submit",function(e){e.preventDefault();},true);</script>'
        . '<style>.skip{display:none}</style>';
    return preg_replace('/<head>/i', '<head>' . $tete, $html, 1);
});
chdir(APP_ROOT);
include APP_ROOT . '/' . $fichier;
