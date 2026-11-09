<?php
// Serveur de développement : php -S localhost:8000 router.php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('#^/(data|inc|maquettes)(/|$)|/\.#', $path)) {
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
return false;
