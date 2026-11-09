<?php
require __DIR__ . '/inc.php';
require_login();

/** Ajoute un dossier à l'archive, récursivement. */
function zip_dir(ZipArchive $z, string $dir, string $prefix, array $skip = []): void
{
    if (!is_dir($dir)) {
        return;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $rel = substr($f->getPathname(), strlen($dir) + 1);
        if (in_array(basename($rel), $skip, true)) {
            continue;
        }
        $z->addFile($f->getPathname(), $prefix . str_replace('\\', '/', $rel));
    }
}

if (isset($_GET['telecharger'])) {
    if (!class_exists('ZipArchive')) {
        exit('L\'hébergement ne permet pas de créer une archive ZIP. Copiez les dossiers data/ et uploads/ par FTP.');
    }
    $tmp = tempnam(sys_get_temp_dir(), 'lbz');
    $z = new ZipArchive();
    $z->open($tmp, ZipArchive::OVERWRITE);
    zip_dir($z, DATA_DIR, 'data/', ['utilisateurs.json']);
    zip_dir($z, UPLOAD_DIR, 'uploads/');
    $z->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="sauvegarde-la-boucherie-' . date('Y-m-d') . '.zip"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    unlink($tmp);
    exit;
}

function taille_dossier(string $d): int
{
    $t = 0;
    if (is_dir($d)) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d, FilesystemIterator::SKIP_DOTS)) as $f) {
            $t += $f->getSize();
        }
    }
    return $t;
}
$mo = fn($o) => number_format($o / 1048576, 1, ',', ' ') . ' Mo';

admin_start('Sauvegarde', 'sauvegarde.php');
?>
<section class="bloc">
  <h2>Télécharger une sauvegarde complète</h2>
  <p>L'archive contient tous les contenus du site (expositions, pages, presse, candidatures, newsletter) et toutes les images. Les mots de passe n'y sont pas inclus.</p>
  <p>Contenus : <?= $mo(taille_dossier(DATA_DIR)) ?> · Images : <?= $mo(taille_dossier(UPLOAD_DIR)) ?></p>
  <p><a class="btn" href="?telecharger=1"><?= icon('download') ?> Télécharger la sauvegarde (.zip)</a></p>
  <p class="aide">Conseil : une sauvegarde par mois, rangée sur votre ordinateur ou une clé USB. Pour restaurer, il suffit de recopier les dossiers <code>data</code> et <code>uploads</code> sur l'hébergement (par FTP).</p>
</section>
<?php admin_end();
