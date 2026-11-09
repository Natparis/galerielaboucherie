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
    $full = $title ? $title . ' — ' . $s['nom'] . ', galerie à ' . $s['ville'] : $s['nom'] . ' — galerie d\'art éphémère à ' . $s['ville'];
    $desc = $opt['description'] ?? $s['meta_description'];
    $isHome = $current === 'accueil';
    ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($full) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta property="og:title" content="<?= e($full) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<?php if (!empty($opt['image'])): ?><meta property="og:image" content="<?= e($opt['image']) ?>">
<?php endif ?>
<meta name="theme-color" content="#1E2A36">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' fill='%23F8F2E8'/><path d='M0 0h6v20H0zM10 0h6v20h-6zM20 0h6v20h-6z' fill='%23B5412C'/><circle cx='3' cy='22' r='3' fill='%23B5412C'/><circle cx='13' cy='22' r='3' fill='%23B5412C'/><circle cx='23' cy='22' r='3' fill='%23B5412C'/></svg>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,700;1,500&family=DM+Sans:wght@400;500;700&family=Yellowtail&display=swap">
<link rel="stylesheet" href="assets/css/site.css?v=1">
<?php if (!empty($opt['jsonld'])): ?><script type="application/ld+json"><?= json_encode($opt['jsonld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
<?php endif ?>
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
      <img src="<?= e(url($courante['image'])) ?>" alt="">
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

function affiche(?array $x, string $altDefaut = ''): void
{
    ?>
<div class="affiche-store">
  <div class="store" aria-hidden="true"></div><div class="frange" aria-hidden="true"></div>
<?php if ($x && $x['image']): ?>
  <img src="<?= e(url($x['image'])) ?>" alt="<?= e($x['image_alt'] ?: $altDefaut) ?>">
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
