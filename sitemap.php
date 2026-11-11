<?php
/** Plan du site pour les moteurs de recherche (toujours à jour avec les expositions publiées). */
require __DIR__ . '/inc/bootstrap.php';
header('Content-Type: application/xml; charset=utf-8');
$base = site_url();
$urls = ['' => 'weekly', 'expo.php' => 'weekly', 'programme.php' => 'weekly', 'artistes.php' => 'monthly', 'exposer.php' => 'yearly', 'lieu.php' => 'yearly', 'presse.php' => 'monthly'];
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $u => $freq) {
    echo '  <url><loc>' . e($base . $u) . '</loc><changefreq>' . $freq . '</changefreq></url>' . "\n";
}
foreach (expositions() as $x) {
    echo '  <url><loc>' . e($base . 'expo.php?slug=' . urlencode($x['slug'])) . '</loc></url>' . "\n";
}
echo '</urlset>' . "\n";
