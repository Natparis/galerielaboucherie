<?php
require_once __DIR__ . '/bootstrap.php';

const NAV = [
    'expo' => ['expo.php', 'L\'expo du mois'],
    'programme' => ['programme.php', 'Programme'],
    'artistes' => ['artistes.php', 'Artistes'],
    'exposer' => ['exposer.php', 'Exposer'],
    'lieu' => ['lieu.php', 'Le lieu'],
    'presse' => ['presse.php', 'Presse'],
];

function page_start(string $title, string $current = '', array $opt = []): void
{
    $s = settings();
    $isHome = $current === 'accueil';
    // Titre : celui réglé pour le référencement, sinon « Page — La Boucherie, galerie d'art à Léré »
    $suffixe = $s['nom'] . ', galerie d\'art à ' . $s['ville'];
    $full = $opt['titre_seo'] ?? ($title ? $title . ' — ' . $suffixe : $s['nom'] . ' — galerie d\'art éphémère à ' . $s['ville']);
    $desc = trim((string)($opt['description'] ?? '')) ?: $s['meta_description'];
    $apercu = !empty($GLOBALS['APERCU']);
    $site = site_url();
    // Image affichée quand on partage la page (réseaux sociaux, messageries) : celle de la page, sinon l'affiche en cours.
    $img = $opt['image'] ?? (expo_courante()['image'] ?? '');
    $img = $img && !preg_match('#^(https?:|data:)#', $img) ? $site . $img : $img;
    $ici = $site . ltrim(basename($_SERVER['SCRIPT_NAME'] ?? ''), '/') . (!empty($_GET['slug']) ? '?slug=' . urlencode((string)$_GET['slug']) : '');
    if (str_ends_with($ici, '/index.php')) {
        $ici = $site;
    }
    $indexable = !$apercu && http_response_code() === 200 && empty($opt['noindex']);
    // Données structurées : celles de la page + le fil d'Ariane
    $ld = $opt['jsonld'] ?? [];
    $ld = isset($ld['@type']) ? [$ld] : $ld;
    if (!$isHome && $title && $indexable) {
        $fil = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => $site]];
        if (!empty($opt['parent'])) {
            $fil[] = ['@type' => 'ListItem', 'position' => 2, 'name' => $opt['parent'][0], 'item' => $site . $opt['parent'][1]];
        }
        $fil[] = ['@type' => 'ListItem', 'position' => count($fil) + 1, 'name' => $title, 'item' => $ici];
        $ld[] = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $fil];
    }
    ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php if (!empty($opt['base'])): ?><base href="<?= e($opt['base']) ?>">
<?php endif ?>
<title><?= e($full) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<?php if ($indexable): ?><link rel="canonical" href="<?= e($ici) ?>">
<?php else: ?><meta name="robots" content="noindex">
<?php endif ?>
<meta property="og:type" content="website">
<meta property="og:locale" content="fr_FR">
<meta property="og:site_name" content="<?= e($s['nom']) ?> — galerie éphémère de <?= e($s['ville']) ?>">
<meta property="og:title" content="<?= e($full) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:url" content="<?= e($ici) ?>">
<?php if ($img && !$apercu): ?><meta property="og:image" content="<?= e($img) ?>">
<meta name="twitter:card" content="summary_large_image">
<?php endif ?>
<meta name="theme-color" content="#1E2A36">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' fill='%23F8F2E8'/><path d='M0 0h6v20H0zM10 0h6v20h-6zM20 0h6v20h-6z' fill='%23B5412C'/><circle cx='3' cy='22' r='3' fill='%23B5412C'/><circle cx='13' cy='22' r='3' fill='%23B5412C'/><circle cx='23' cy='22' r='3' fill='%23B5412C'/></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,700;1,500&family=DM+Sans:wght@400;500;700&family=Yellowtail&display=swap">
<link rel="stylesheet" href="assets/css/site.css?v=1">
<?php foreach ($apercu ? [] : $ld as $bloc): ?><script type="application/ld+json"><?= json_encode($bloc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endforeach ?>
</head>
<body class="<?= $isHome ? 'accueil' : '' ?>">
<a class="skip" href="#contenu">Aller au contenu</a>
<header class="entete">
  <div class="store lambrequin" aria-hidden="true"></div>
  <div class="store enseigne">
    <span class="cote" aria-hidden="true"><?= e($s['cote_gauche']) ?></span>
    <a class="nom" href="index.php"<?= $isHome ? ' aria-current="page"' : '' ?>><?= e($s['nom']) ?><span class="sr-only"> — accueil</span></a>
    <span class="cote" aria-hidden="true"><?= e($s['cote_droite']) ?></span>
  </div>
  <div class="frange" aria-hidden="true"></div>
  <button class="burger" type="button" aria-expanded="false" aria-controls="nav">
    <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M3 12h18M3 18h18" stroke="currentColor" stroke-width="2"/></svg>Menu
  </button>
  <nav class="nav" id="nav" aria-label="Navigation principale">
<?php foreach (NAV as $k => [$href, $label]): ?>
    <a href="<?= $href ?>"<?= $k === $current ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
<?php endforeach ?>
  </nav>
</header>
<main id="contenu">
<?php foreach (empty($GLOBALS['APERCU']) ? flash() : [] as [$type, $msg]): ?>
<div class="flash <?= e($type) ?>" role="status"><?= e($msg) ?></div>
<?php endforeach;
}

function page_end(): void
{
    $s = settings();
    $reseaux = array_filter(['Instagram' => $s['instagram'], 'Facebook' => $s['facebook'], 'TikTok' => $s['tiktok'], 'YouTube' => $s['youtube']]);
    ?>
</main>
<div class="chevrons" aria-hidden="true"></div>
<footer class="pied sombre">
  <span class="neon"><?= e($s['neon']) ?></span>
  <span><?= e($s['adresse']) ?> · <?= e($s['code_postal'] . ' ' . $s['ville']) ?> · <a href="<?= e(tel_href($s['telephone'])) ?>"><?= e($s['telephone']) ?></a><?php if ($s['email']): ?> · <a href="mailto:<?= e($s['email']) ?>"><?= e($s['email']) ?></a><?php endif ?></span>
<?php if ($reseaux): ?>
  <span class="reseaux"><?php foreach ($reseaux as $n => $u): ?><a href="<?= e($u) ?>" rel="noopener" target="_blank"><?= $n ?></a><?php endforeach ?></span>
<?php endif ?>
  <nav class="plan" aria-label="Plan du site">
<?php foreach (NAV as [$href, $label]): ?>    <a href="<?= $href ?>"><?= e($label) ?></a>
<?php endforeach ?>
    <a href="index.php#newsletter">Recevoir le programme</a>
  </nav>
  <div class="legal"><span>© <?= date('Y') ?> La galerie éphémère — <?= e($s['nom']) ?></span><a href="mentions.php">Mentions légales</a><a href="admin/">Espace galerie</a></div>
</footer>
<script src="assets/js/site.js?v=1" defer></script>
</body>
</html>
<?php
}

/** Illustration dessinée de la devanture (store, vitrines, porte au néon). */
function devanture(?array $courante, array $prochaines): void
{
    $s = settings();
    $label = 'Illustration de la devanture de ' . $s['nom'] . ' : store rayé, lambrequin, vitrines et porte au néon';
    ?>
<figure style="margin:0"><div class="devanture" role="img" aria-label="<?= e($label) ?>"><div aria-hidden="true">
  <div class="dv-store"></div><div class="dv-bande"></div><div class="dv-frange"></div>
  <div class="dv-vitrines">
    <div class="dv-vitre" style="align-items:center"><span class="n" style="align-self:flex-start">en vitrine</span>
<?php if ($courante && $courante['image']): ?>
      <img src="<?= e(url($courante['image'])) ?>"<?= dims($courante['image']) ?> alt="">
<?php endif ?>
    </div>
    <div class="dv-vitre dv-porte"><span class="grand"><?= nl2br(e(str_replace(' ', "\n", $s['neon'])), false) ?></span><span class="petit">Entrée libre</span><span class="bouton">Pousser la porte</span><span class="poignee"></span></div>
    <div class="dv-vitre"><span class="n">prochainement</span>
<?php foreach (array_slice($prochaines, 0, 4) as $x): ?>
      <span class="dv-ligne"><?= e($x['artiste_court'] ?: $x['artiste']) ?><b><?= fr_date($x['date_debut'], 'num') ?></b></span>
<?php endforeach ?>
    </div>
  </div>
  <div class="dv-soubass"><div></div><div></div><div></div></div>
</div></div></figure>
<?php
}

function affiche(?array $x, string $altDefaut = '', bool $prioritaire = false): void
{
    ?>
<div class="affiche-store">
  <div class="store" aria-hidden="true"></div><div class="frange" aria-hidden="true"></div>
<?php if ($x && $x['image']): ?>
  <img src="<?= e(url($x['image'])) ?>"<?= dims($x['image']) ?> alt="<?= e($x['image_alt'] ?: $altDefaut) ?>"<?= $prioritaire ? ' fetchpriority="high"' : ' loading="lazy"' ?>>
<?php else: ?>
  <div class="placeholder" aria-hidden="true">Visuel à venir</div>
<?php endif ?>
</div>
<?php
}

/** Message de confirmation affiché dans le bloc newsletter. */
function news_msg(): string
{
    $m = $_SESSION['news_msg'] ?? null;
    unset($_SESSION['news_msg']);
    return $m ? '<p class="news-msg ' . e($m[0]) . '" role="status">' . e($m[1]) . '</p>' : '';
}

/** Attributs width/height d'une image du site : évite que la page « saute » pendant le chargement. */
function dims(?string $chemin): string
{
    static $cache = [];
    if (!$chemin || preg_match('#^(https?:|data:)#', $chemin)) {
        return '';
    }
    if (!isset($cache[$chemin])) {
        $t = @getimagesize(APP_ROOT . '/' . $chemin);
        $cache[$chemin] = $t ? ' width="' . $t[0] . '" height="' . $t[1] . '"' : '';
    }
    return $cache[$chemin];
}

/** Données structurées d'une exposition (schema.org), pour Google. */
function ld_expo(array $x): array
{
    $s = settings();
    $site = site_url();
    $debut = date('c', strtotime($x['date_debut'] . ' ' . ($x['heure_debut'] ?: '18:00')));
    $d = [
        '@context' => 'https://schema.org', '@type' => 'ExhibitionEvent',
        'name' => $x['artiste'] . ($x['titre'] ? ' — ' . $x['titre'] : ''),
        'startDate' => $debut,
        'endDate' => expo_fin($x),
        'eventStatus' => 'https://schema.org/EventScheduled',
        'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
        'isAccessibleForFree' => true,
        'url' => $site . 'expo.php?slug=' . urlencode($x['slug']),
        'location' => ['@type' => 'ArtGallery', 'name' => $s['nom'], 'address' => ['@type' => 'PostalAddress', 'streetAddress' => $s['adresse'], 'postalCode' => $s['code_postal'], 'addressLocality' => $s['ville'], 'addressCountry' => 'FR']],
        'organizer' => ['@type' => 'Organization', 'name' => 'La galerie éphémère — ' . $s['nom'], 'url' => $site],
        'performer' => ['@type' => 'Person', 'name' => $x['artiste']],
        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR', 'availability' => 'https://schema.org/InStock', 'url' => $site . 'expo.php?slug=' . urlencode($x['slug']), 'validFrom' => $debut],
    ];
    $texte = trim(strip_tags($x['resume'] ?: $x['accroche']));
    if ($texte !== '') {
        $d['description'] = $texte;
    }
    if ($x['image']) {
        $d['image'] = $site . $x['image'];
    }
    return $d;
}

/** Coupe un texte proprement (au mot près) pour une description de 160 caractères au plus. */
function tronquer(string $t, int $max = 158): string
{
    $t = trim(preg_replace('/\s+/u', ' ', strip_tags($t)));
    if (mb_strlen($t) <= $max) {
        return $t;
    }
    $c = mb_substr($t, 0, $max - 1);
    return rtrim(mb_substr($c, 0, mb_strrpos($c, ' ') ?: $max - 1), ' ,;:.') . '…';
}

/** Titre (65 caractères au plus) et description d'une page d'exposition pour Google. */
function seo_expo(array $x): array
{
    $s = settings();
    $essais = [
        'Exposition ' . $x['artiste'] . ($x['titre'] ? ' — ' . $x['titre'] : '') . ' — ' . $s['nom'] . ', ' . $s['ville'],
        'Exposition ' . $x['artiste'] . ' — ' . $s['nom'] . ', galerie à ' . $s['ville'],
        'Exposition ' . $x['artiste'] . ' — ' . $s['nom'] . ', ' . $s['ville'],
        $x['artiste'] . ' — ' . $s['nom'] . ', ' . $s['ville'],
    ];
    $titre = end($essais);
    foreach ($essais as $t) {
        if (mb_strlen($t) <= 65) {
            $titre = $t;
            break;
        }
    }
    $quoi = $x['technique'] ?: $x['discipline'];
    $intro = 'Exposition de ' . $x['artiste'] . ($quoi ? ' (' . mb_strtolower($quoi) . ')' : '') . ' à la galerie ' . $s['nom'] . ', à ' . $s['ville'] . ' (Cher), ' . fr_periode($x) . '.';
    $texte = trim(strip_tags($x['resume'] ?: $x['accroche']));
    // Le résumé d'abord s'il est assez riche, sinon la phrase factuelle complétée par le résumé
    if (mb_strlen($texte) >= 110) {
        return [$titre, tronquer($texte)];
    }
    $desc = $intro;
    foreach ([$texte, 'Vernissage à ' . fr_heure($x['heure_debut'] ?: '18:00') . ', entrée libre et gratuite.', 'Entrée libre.'] as $suite) {
        if ($suite !== '' && mb_strlen($desc . ' ' . $suite) <= 158) { // on n'ajoute que ce qui tient en entier
            $desc .= ' ' . $suite;
            break;
        }
    }
    return [$titre, tronquer($desc)];
}
