<?php
/** Back-office : authentification, mise en page et moteur de formulaires. */
$GLOBALS['BASE'] = '../';
require_once __DIR__ . '/../inc/bootstrap.php';

header('X-Frame-Options: DENY');
header('X-Robots-Tag: noindex, nofollow');

/* ------------------------------------------------------------------ */
/* Comptes                                                             */
/* ------------------------------------------------------------------ */

function users(): array
{
    $list = data_load('utilisateurs', []);
    // Il y a toujours au moins un responsable : à défaut, le premier compte créé.
    if ($list && !array_filter($list, fn($u) => !empty($u['admin']))) {
        $list[0]['admin'] = true;
    }
    return $list;
}

/** Un responsable peut donner, modifier et retirer les accès au back-office. */
function est_responsable(?array $u): bool
{
    return !empty($u['admin']);
}

function current_user(): ?array
{
    $id = $_SESSION['uid'] ?? null;
    if (!$id) {
        return null;
    }
    foreach (users() as $u) {
        if ($u['id'] === $id) {
            return $u;
        }
    }
    return null;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        redirect('login.php' . (isset($_SERVER['REQUEST_URI']) ? '?r=' . urlencode(basename($_SERVER['REQUEST_URI'])) : ''));
    }
    return $u;
}

/* ------------------------------------------------------------------ */
/* Mise en page                                                        */
/* ------------------------------------------------------------------ */

function icon(string $name): string
{
    $p = [
        'home' => '<path d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
        'news' => '<path d="M4 5h13v14H6a2 2 0 0 1-2-2zM17 9h3v8a2 2 0 0 1-2 2"/><path d="M8 9h5M8 13h5"/>',
        'page' => '<path d="M6 3h9l4 4v14H6z"/><path d="M14 3v5h5M9 13h7M9 17h7"/>',
        'inbox' => '<path d="M3 13l3-8h12l3 8v6H3z"/><path d="M3 13h5l1 3h6l1-3h5"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'users' => '<circle cx="9" cy="8" r="4"/><path d="M2 21c1-4 4-6 7-6s6 2 7 6M17 11a3 3 0 1 0 0-6M22 21c-.5-2.5-2-4-4-5"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M4.9 19.1L7 17M17 7l2.1-2.1"/>',
        'save' => '<path d="M5 3h11l3 3v15H5z"/><path d="M8 3v5h7V3M8 21v-7h8v7"/>',
        'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'out' => '<path d="M15 4h4a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1h-4M10 16l-4-4 4-4M6 12h11"/>',
        'up' => '<path d="M12 19V5M6 11l6-6 6 6"/>',
        'down' => '<path d="M12 5v14M6 13l6 6 6-6"/>',
        'trash' => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'copy' => '<rect x="8" y="8" width="13" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/>',
        'download' => '<path d="M12 3v12M7 10l5 5 5-5M4 21h16"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'palette' => '<path d="M12 3a9 9 0 1 0 0 18c1.1 0 1.6-.9 1.2-1.8-.5-1-.1-2.2 1.1-2.2H17a4 4 0 0 0 4-4c0-5-4-10-9-10z"/><circle cx="7.5" cy="11" r="1.2"/><circle cx="10" cy="7" r="1.2"/><circle cx="15" cy="7.5" r="1.2"/>',
    ][$name] ?? '';
    return '<svg class="ic" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

function admin_menu(): array
{
    $sc = schema();
    $nouvelles = count(array_filter(data_load('candidatures', []), fn($c) => ($c['statut'] ?? '') === 'nouvelle'));
    $m = [
        'Tableau de bord' => [['index.php', 'Accueil', 'home', 0]],
        'Contenus' => [
            ['liste.php?c=expositions', $sc['collections']['expositions']['label'], 'calendar', 0],
            ['liste.php?c=presse', $sc['collections']['presse']['label'], 'page', 0],
        ],
        'Modifier les pages du site' => [],
        'Contacts' => [
            ['repertoire.php?vue=artistes', 'Artistes', 'palette', 0],
            ['repertoire.php', 'Répertoire des contacts', 'users', 0],
            ['ecrire.php', 'Écrire', 'mail', 0],
            ['candidatures.php', 'Candidatures', 'inbox', $nouvelles],
        ],
        'Réglages' => [
            ['page.php?p=reglages', 'Coordonnées et réglages', 'settings', 0],
            ['comptes.php', 'Comptes d\'accès', 'users', 0],
            ['sauvegarde.php', 'Sauvegarde', 'save', 0],
        ],
    ];
    foreach ($sc['singletons'] as $k => $d) {
        if (($d['group'] ?? '') === 'Pages') {
            $m['Modifier les pages du site'][] = ['page.php?p=' . $k, $d['label'], 'page', 0];
        }
    }
    return $m;
}

function admin_start(string $title, string $active = ''): void
{
    $u = current_user();
    $s = settings();
    ?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — Espace galerie <?= e($s['nom']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;700&family=DM+Sans:wght@400;500;700&display=swap">
<link rel="stylesheet" href="../assets/css/admin.css?v=1">
</head>
<body>
<?php if ($u): ?>
<div class="shell">
  <aside class="side" id="side">
    <a class="marque" href="index.php"><span class="store" aria-hidden="true"></span><span><?= e($s['nom']) ?><small>Espace galerie</small></span></a>
    <nav aria-label="Menu du back-office">
<?php foreach (admin_menu() as $groupe => $items): ?>
      <p class="groupe"><?= e($groupe) ?></p>
<?php foreach ($items as [$href, $label, $ic, $badge]): ?>
      <a href="<?= e($href) ?>"<?= $href === $active ? ' aria-current="page"' : '' ?>><?= icon($ic) ?><span><?= e($label) ?></span><?php if ($badge): ?><b class="badge"><?= $badge ?></b><?php endif ?></a>
<?php endforeach ?>
<?php endforeach ?>
    </nav>
    <div class="moi"><span><?= e($u['nom']) ?></span><a href="logout.php"><?= icon('out') ?> Se déconnecter</a></div>
  </aside>
  <div class="main">
    <header class="barre">
      <button class="menu-btn" type="button" aria-controls="side" aria-expanded="false"><?= icon('menu') ?><span class="sr-only">Menu</span></button>
      <h1><?= e($title) ?></h1>
      <a class="voir" href="../index.php" target="_blank" rel="noopener"><?= icon('eye') ?> Voir le site</a>
    </header>
    <main class="contenu">
<?php if (!empty($u['provisoire']) && basename($_SERVER['SCRIPT_NAME'] ?? '') !== 'comptes.php'): ?>
      <div class="flash err" role="status">Votre mot de passe est provisoire : <a href="comptes.php#mon-mdp">choisissez le vôtre maintenant</a>, connu de vous seul.</div>
<?php endif ?>
<?php foreach (flash() as [$type, $msg]): ?>
      <div class="flash <?= e($type) ?>" role="status"><?= e($msg) ?></div>
<?php endforeach ?>
<?php else: ?>
<main class="seul">
<?php endif;
}

function admin_end(): void
{
    if (current_user()) {
        echo "    </main>\n  </div>\n</div>\n";
    } else {
        echo "</main>\n";
    }
    echo '<script src="../assets/js/admin.js?v=1" defer></script>' . "\n</body>\n</html>\n";
}

/* ------------------------------------------------------------------ */
/* Moteur de formulaires                                               */
/* ------------------------------------------------------------------ */

function field_name(array $path): string
{
    $n = array_shift($path);
    foreach ($path as $p) {
        $n .= '[' . $p . ']';
    }
    return $n;
}

function field_id(array $path): string
{
    return 'f-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', implode('-', $path));
}

/** Affiche une liste de champs. $path = chemin du nom (ex. ['data']). */
function render_fields(array $fields, array $values, array $path = ['data']): void
{
    echo '<div class="champs">';
    foreach ($fields as $key => $f) {
        render_field($key, $f, $values[$key] ?? ($f['default'] ?? null), $path);
    }
    echo '</div>';
}

function render_field(string $key, array $f, $value, array $path): void
{
    $type = $f['type'] ?? 'text';
    $p = array_merge($path, [$key]);
    $name = field_name($p);
    $id = field_id($p);
    $col = $f['col'] ?? 'full';
    $req = !empty($f['required']);
    $label = e($f['label']) . ($req ? ' <span class="req" aria-hidden="true">*</span>' : '');
    $help = !empty($f['help']) ? '<p class="aide" id="' . $id . '-aide">' . e($f['help']) . '</p>' : '';
    $describedby = !empty($f['help']) ? ' aria-describedby="' . $id . '-aide"' : '';
    $r = $req ? ' required' : '';

    echo '<div class="champ ' . e($col) . ' t-' . e($type) . '">';
    switch ($type) {
        case 'textarea':
            echo "<label for=\"$id\">$label</label>$help";
            echo "<textarea id=\"$id\" name=\"" . e($name) . "\" rows=\"" . (int)($f['rows'] ?? 4) . "\"$r$describedby>" . e((string)$value) . '</textarea>';
            break;
        case 'checkbox':
            echo "<label class=\"case\" for=\"$id\"><input type=\"hidden\" name=\"" . e($name) . "\" value=\"0\"><input type=\"checkbox\" id=\"$id\" name=\"" . e($name) . "\" value=\"1\"" . ($value ? ' checked' : '') . "$describedby> <span>" . e($f['label']) . "</span></label>$help";
            break;
        case 'select':
            echo "<label for=\"$id\">$label</label>$help<select id=\"$id\" name=\"" . e($name) . "\"$r$describedby><option value=\"\">—</option>";
            $opts = $f['options'];
            if ($value && !in_array($value, $opts, true)) {
                $opts[] = $value;
            }
            foreach ($opts as $o) {
                echo '<option' . ((string)$value === (string)$o ? ' selected' : '') . '>' . e($o) . '</option>';
            }
            echo '</select>';
            break;
        case 'image':
        case 'file':
            $isImg = $type === 'image';
            $up = field_name(array_merge(['up'], array_slice($p, 1)));
            $del = field_name(array_merge(['del'], array_slice($p, 1)));
            echo "<span class=\"lbl\">$label</span>$help<div class=\"media\">";
            if ($value) {
                if ($isImg) {
                    echo '<img class="apercu" src="' . e(url((string)$value)) . '" alt="">';
                } else {
                    echo '<a class="fichier" href="' . e(url((string)$value)) . '" target="_blank" rel="noopener">' . icon('download') . ' ' . e(basename((string)$value)) . '</a>';
                }
            } elseif ($isImg) {
                echo '<span class="apercu vide" aria-hidden="true"></span>';
            }
            echo '<div class="media-actions"><input type="hidden" name="' . e($name) . '" value="' . e((string)$value) . '">';
            echo "<label class=\"btn-fichier\" for=\"$id\">" . ($value ? 'Remplacer…' : 'Choisir un fichier…') . "</label>";
            echo "<input class=\"sr-only\" type=\"file\" id=\"$id\" name=\"" . e($up) . "\" accept=\"" . ($isImg ? 'image/jpeg,image/png,image/webp,image/gif' : 'application/pdf') . "\" data-preview>";
            echo '<span class="nom-fichier" aria-live="polite"></span>';
            if ($value) {
                echo "<label class=\"case petite\"><input type=\"checkbox\" name=\"" . e($del) . "\" value=\"1\"> Retirer</label>";
            }
            echo '</div></div>';
            break;
        case 'list':
            $items = is_array($value) ? array_values($value) : [];
            echo "<fieldset class=\"liste\" data-liste><legend>" . e($f['label']) . "</legend>$help<div class=\"items\" data-items>";
            foreach ($items as $i => $item) {
                render_list_item($f, (array)$item, array_merge($p, [(string)$i]));
            }
            echo '</div><template data-template>';
            render_list_item($f, [], array_merge($p, ['__I__']));
            echo '</template><button type="button" class="btn ligne petit" data-ajout>' . icon('plus') . ' ' . e($f['add'] ?? 'Ajouter') . '</button></fieldset>';
            break;
        case 'secret':
            $etat = $value ? 'Une clé est enregistrée.' : 'Aucune clé enregistrée.';
            echo "<label for=\"$id\">$label</label>$help<input type=\"password\" id=\"$id\" name=\"" . e($name) . "\" value=\"\" autocomplete=\"new-password\" placeholder=\"" . e($etat) . "\"$describedby>";
            break;
        default:
            $html = ['date' => 'date', 'time' => 'time', 'email' => 'email', 'tel' => 'tel', 'number' => 'number'][$type] ?? 'text';
            $extra = $type === 'url' ? ' inputmode="url" placeholder="https://…"' : '';
            echo "<label for=\"$id\">$label</label>$help<input type=\"$html\" id=\"$id\" name=\"" . e($name) . "\" value=\"" . e((string)$value) . "\"$r$describedby$extra>";
    }
    echo '</div>';
}

function render_list_item(array $f, array $item, array $path): void
{
    echo '<div class="item" data-item><div class="item-outils">';
    echo '<button type="button" class="ico" data-haut title="Monter">' . icon('up') . '<span class="sr-only">Monter</span></button>';
    echo '<button type="button" class="ico" data-bas title="Descendre">' . icon('down') . '<span class="sr-only">Descendre</span></button>';
    echo '<button type="button" class="ico danger" data-suppr title="Supprimer">' . icon('trash') . '<span class="sr-only">Supprimer</span></button>';
    echo '</div>';
    render_fields($f['fields'], $item, $path);
    echo '</div>';
}

/** Récupère le fichier téléversé correspondant au chemin (hors 'data'). */
function upload_at(array $path): ?array
{
    if (empty($_FILES['up'])) {
        return null;
    }
    $out = [];
    foreach (['name', 'type', 'tmp_name', 'error', 'size'] as $attr) {
        $v = $_FILES['up'][$attr] ?? null;
        foreach ($path as $k) {
            if (!is_array($v) || !array_key_exists($k, $v)) {
                return null;
            }
            $v = $v[$k];
        }
        if (is_array($v)) {
            return null;
        }
        $out[$attr] = $v;
    }
    return $out;
}

function del_at(array $path): bool
{
    $v = $_POST['del'] ?? null;
    foreach ($path as $k) {
        if (!is_array($v) || !isset($v[$k])) {
            return false;
        }
        $v = $v[$k];
    }
    return !empty($v);
}

/**
 * Lit les valeurs envoyées pour une liste de champs.
 * $path = chemin relatif sous 'data' (sert à retrouver fichiers et cases « Retirer »).
 */
function collect_fields(array $fields, array $post, array $path = []): array
{
    $out = [];
    foreach ($fields as $key => $f) {
        $type = $f['type'] ?? 'text';
        $p = array_merge($path, [$key]);
        $raw = $post[$key] ?? null;
        switch ($type) {
            case 'checkbox':
                $out[$key] = !empty($raw);
                break;
            case 'list':
                $items = [];
                foreach ((array)$raw as $idx => $item) {
                    if ($idx === '__I__') {
                        continue;
                    }
                    $row = collect_fields($f['fields'], (array)$item, array_merge($p, [(string)$idx]));
                    if (array_filter($row, fn($v) => $v !== '' && $v !== false && $v !== [])) {
                        $items[] = $row;
                    }
                }
                $out[$key] = $items;
                break;
            case 'image':
            case 'file':
                $val = trim((string)$raw);
                if (del_at($p)) {
                    $val = '';
                }
                $file = upload_at($p);
                if ($file) {
                    $allowed = $type === 'image'
                        ? ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif']
                        : ['application/pdf' => 'pdf'];
                    $saved = save_upload($file, $allowed);
                    if ($saved) {
                        $val = $saved;
                    }
                }
                $out[$key] = $val;
                break;
            case 'url':
                $v = trim((string)$raw);
                if ($v !== '' && !preg_match('#^(https?://|mailto:)#i', $v)) {
                    $v = 'https://' . $v;
                }
                $out[$key] = $v;
                break;
            case 'textarea':
                $out[$key] = trim(str_replace("\r\n", "\n", (string)$raw));
                break;
            default:
                $out[$key] = trim((string)$raw);
        }
    }
    return $out;
}

function missing_required(array $fields, array $values): array
{
    $m = [];
    foreach ($fields as $k => $f) {
        if (!empty($f['required']) && ($values[$k] ?? '') === '') {
            $m[] = $f['label'];
        }
    }
    return $m;
}

/* ------------------------------------------------------------------ */
/* Répertoire et envoi de messages                                     */
/* ------------------------------------------------------------------ */

function repertoire(): array
{
    $def = defaults_of(schema()['collections']['repertoire']['fields']);
    $list = array_map(fn($x) => array_replace($def, $x), collection('repertoire'));
    usort($list, fn($a, $b) => strcoll(mb_strtolower($a['nom']), mb_strtolower($b['nom'])));
    return $list;
}

// Artistes de la GALERIE (jamais ceux de Copains d'après, qui ont leurs propres « Artistes »)
const GROUPES_ARTISTES = ['A exposé', 'Programmé', 'Candidat'];

/** Une fiche du répertoire est-elle un artiste ? */
function est_artiste(array $a): bool
{
    if (!empty($a['artiste']) || trim((string)($a['discipline'] ?? '')) !== '') {
        return true;
    }
    return (bool)array_intersect(groupes_de($a), GROUPES_ARTISTES);
}

function groupes_de(array $a): array
{
    return array_values(array_filter(array_map('trim', explode(',', (string)$a['groupes'])), 'strlen'));
}

function tous_groupes(array $rep): array
{
    $g = [];
    foreach ($rep as $a) {
        foreach (groupes_de($a) as $x) {
            $g[$x] = ($g[$x] ?? 0) + 1;
        }
    }
    uksort($g, 'strcoll');
    return $g;
}

/** Remplace {prenom}, {nom} dans un texte. */
function personnaliser(string $txt, array $a): string
{
    $nom = trim($a['nom']);
    $prenom = explode(' ', $nom)[0];
    return strtr($txt, ['{prenom}' => $prenom, '{nom}' => $nom, '{Prenom}' => $prenom, '{Nom}' => $nom]);
}

function header_mot(string $s): string
{
    return preg_match('/[^\x20-\x7E]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
}

/**
 * Envoie un e-mail mis en page (HTML) avec sa version texte (multipart/alternative).
 * Retourne true si le serveur l'a accepté.
 */
function envoyer_mail_html(string $to, string $sujet, string $html, string $texte): bool
{
    $s = settings();
    $from = $s['email_expediteur'] ?: ($s['email'] ?: 'no-reply@' . preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
    $reply = $s['email'] ?: $from;
    $clean = fn($v) => str_replace(["\r", "\n"], '', $v);
    $frontiere = 'lb-' . bin2hex(random_bytes(8));
    $headers = [
        'From: ' . header_mot($s['nom']) . ' <' . $clean($from) . '>',
        'Reply-To: ' . $clean($reply),
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $frontiere . '"',
    ];
    $corps = "--$frontiere\r\nContent-Type: text/plain; charset=utf-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($texte))
        . "--$frontiere\r\nContent-Type: text/html; charset=utf-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($html))
        . "--$frontiere--\r\n";
    if (!function_exists('mail')) {
        return false;
    }
    return @mail($clean($to), header_mot($clean($sujet)), $corps, implode("\r\n", $headers), '-f' . $clean($from));
}

/** Envoie un e-mail texte en UTF-8. Retourne true si le serveur l'a accepté. */
function envoyer_mail(string $to, string $sujet, string $corps): bool
{
    $s = settings();
    $from = $s['email_expediteur'] ?: ($s['email'] ?: 'no-reply@' . preg_replace('/^www\./', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
    $reply = $s['email'] ?: $from;
    $clean = fn($v) => str_replace(["\r", "\n"], '', $v);
    $headers = [
        'From: ' . header_mot($s['nom']) . ' <' . $clean($from) . '>',
        'Reply-To: ' . $clean($reply),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=utf-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    if (!function_exists('mail')) {
        return false;
    }
    return @mail($clean($to), header_mot($clean($sujet)), str_replace("\n", "\r\n", $corps), implode("\r\n", $headers), '-f' . $clean($from));
}

/* ------------------------------------------------------------------ */
/* Aperçu en direct (formulaire à gauche, page du site à droite)       */
/* ------------------------------------------------------------------ */

function apercu_panneau(int $largeur = 1280, string $titre = 'Aperçu de la page', string $note = 'Rien n\'est publié tant que vous n\'avez pas cliqué sur « Enregistrer ».'): string
{
    return '<aside class="apercu-direct" data-apercu-panneau data-largeur="' . $largeur . '" aria-label="' . e($titre) . '">
  <div class="apercu-barre">
    <strong>Aperçu en direct</strong>
    <span class="apercu-etat" data-apercu-etat aria-live="polite">à jour</span>
    <span class="apercu-outils" role="group" aria-label="Taille de l\'aperçu">
      <button type="button" class="ico" data-apercu-taille="ordi" aria-pressed="true" title="Comme sur un ordinateur"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="4" width="18" height="12" rx="1.5"/><path d="M8 20h8M12 16v4"/></svg><span class="sr-only">Ordinateur</span></button>
      <button type="button" class="ico" data-apercu-taille="mobile" aria-pressed="false" title="Comme sur un téléphone"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="7" y="2.5" width="10" height="19" rx="2"/><path d="M11 18.5h2"/></svg><span class="sr-only">Téléphone</span></button>
      <button type="button" class="ico" data-apercu-fermer title="Masquer l\'aperçu"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg><span class="sr-only">Masquer l\'aperçu</span></button>
    </span>
  </div>
  <div class="apercu-cadre" data-apercu-cadre><iframe title="Aperçu de la page publique" data-apercu-iframe sandbox="allow-same-origin allow-scripts"></iframe></div>
  <p class="apercu-note">' . e($note) . '</p>
</aside>
<button type="button" class="btn ligne apercu-ouvrir" data-apercu-ouvrir hidden>Voir l\'aperçu</button>';
}

/** Onglets communs de la rubrique « Écrire ». */
function onglets_ecrire(string $actif): string
{
    $n = count(data_load('newsletter', []));
    $o = ['ecrire' => ['ecrire.php', 'Écrire un message'], 'inscrits' => ['newsletter.php', 'Inscrits au programme (' . $n . ')']];
    $h = '<nav class="onglets" aria-label="Rubrique Écrire">';
    foreach ($o as $k => [$href, $label]) {
        $h .= '<a href="' . $href . '"' . ($k === $actif ? ' aria-current="page"' : '') . '>' . e($label) . '</a>';
    }
    return $h . '</nav>';
}
