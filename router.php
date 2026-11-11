<?php
// Serveur de développement : php -S localhost:8000 router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/(data|inc|maquettes|docs)(/|$)|/\.#', $path)) {
    http_response_code(403);
    exit('Accès refusé');
}
if (preg_match('#^/uploads/.*\.php#i', $path)) {
    http_response_code(403);
    exit('Accès refusé');
}
if ($path === '/admin') {
    header('Location: /admin/');
    exit;
}
// Adresse inconnue : la page 404 du site (comme ErrorDocument sur le vrai serveur)
$fichier = __DIR__ . $path;
if ($path !== '/' && !is_file($fichier) && !(is_dir($fichier) && is_file(rtrim($fichier, '/') . '/index.php'))) {
    $_SERVER['SCRIPT_NAME'] = '/404.php';
    require __DIR__ . '/404.php';
    return true;
}
return false;
