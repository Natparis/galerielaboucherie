<?php
/**
 * La Boucherie — noyau commun au site et au back-office.
 * Stockage : fichiers JSON dans /data (aucune base de données nécessaire).
 */
declare(strict_types=1);

const APP_ROOT = __DIR__ . '/..';
const DATA_DIR = APP_ROOT . '/data';
const UPLOAD_DIR = APP_ROOT . '/uploads';
const UPLOAD_MAX = 8 * 1024 * 1024; // 8 Mo par fichier

date_default_timezone_set('Europe/Paris');
mb_internal_encoding('UTF-8');

if (session_status() === PHP_SESSION_NONE) {
    session_name('laboucherie');
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
}

/** Préfixe des URL relatives : '' pour le site, '../' pour le back-office. */
$GLOBALS['BASE'] = $GLOBALS['BASE'] ?? '';

/* ------------------------------------------------------------------ */
/* Échappement et texte                                                */
/* ------------------------------------------------------------------ */

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Mise en forme légère : **gras**, *italique*, retours à la ligne. */
function inline(?string $s): string
{
    $h = e($s);
    $h = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $h);
    $h = preg_replace('/(?<![*\w])\*(?!\s)(.+?)(?<!\s)\*(?![*\w])/u', '<em>$1</em>', $h);
    return nl2br($h, false);
}

/** Texte en paragraphes : une ligne vide = un nouveau paragraphe. */
function paras(?string $s, string $attrs = ''): string
{
    $s = trim(str_replace("\r\n", "\n", (string)$s));
    if ($s === '') {
        return '';
    }
    $out = '';
    foreach (preg_split("/\n\s*\n/u", $s) as $p) {
        $out .= '<p' . ($attrs ? ' ' . $attrs : '') . '>' . inline(trim($p)) . "</p>\n";
    }
    return $out;
}

function lines(?string $s): array
{
    return array_values(array_filter(array_map('trim', explode("\n", str_replace("\r\n", "\n", (string)$s))), 'strlen'));
}

function slugify(string $s): string
{
    $s = mb_strtolower(trim($s));
    $s = strtr($s, [
        'à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'œ' => 'oe', 'æ' => 'ae', 'ù' => 'u', 'û' => 'u',
        'ü' => 'u', 'ú' => 'u', 'ÿ' => 'y', 'ñ' => 'n',
    ]);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-') ?: 'element';
}

function new_id(): string
{
    return bin2hex(random_bytes(6));
}

/* ------------------------------------------------------------------ */
/* Stockage JSON                                                       */
/* ------------------------------------------------------------------ */

function data_path(string $name): string
{
    if (!preg_match('/^[a-z0-9_-]+$/', $name)) {
        throw new InvalidArgumentException('Nom de données invalide');
    }
    return DATA_DIR . '/' . $name . '.json';
}

function data_load(string $name, $default = [])
{
    $f = data_path($name);
    if (!is_file($f)) {
        // Contenu de départ livré avec le site, tant que la galerie n'a rien enregistré.
        $f = __DIR__ . '/seed/' . $name . '.json';
        if (!is_file($f)) {
            return $default;
        }
    }
    $d = json_decode((string)file_get_contents($f), true);
    return is_array($d) ? $d : $default;
}

function data_save(string $name, $data): void
{
    if (!is_dir(DATA_DIR)) {
        mkdir(DATA_DIR, 0775, true);
    }
    $f = data_path($name);
    $tmp = $f . '.' . bin2hex(random_bytes(4)) . '.tmp';
    file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
    rename($tmp, $f);
}

/* ------------------------------------------------------------------ */
/* Schéma et contenus                                                  */
/* ------------------------------------------------------------------ */

function schema(): array
{
    static $s = null;
    return $s ??= require __DIR__ . '/schema.php';
}

/** Valeurs par défaut d'une liste de champs. */
function defaults_of(array $fields): array
{
    $out = [];
    foreach ($fields as $k => $f) {
        $out[$k] = $f['default'] ?? (($f['type'] ?? 'text') === 'list' ? [] : (($f['type'] ?? '') === 'checkbox' ? false : ''));
    }
    return $out;
}

/** Contenu d'une page ou des réglages (singleton), complété par les valeurs par défaut. */
function single(string $name): array
{
    static $cache = [];
    if (!isset($cache[$name])) {
        $def = schema()['singletons'][$name] ?? null;
        if (!$def) {
            throw new InvalidArgumentException("Page inconnue : $name");
        }
        $cache[$name] = array_replace(defaults_of($def['fields']), data_load($name, []));
    }
    return $cache[$name];
}

function settings(): array
{
    return single('reglages');
}

function collection(string $name): array
{
    return data_load($name, []);
}

/* ------------------------------------------------------------------ */
/* Expositions                                                         */
/* ------------------------------------------------------------------ */

function today(): string
{
    return date('Y-m-d');
}

/** Expositions publiées, triées par date de vernissage croissante. */
function expositions(bool $all = false): array
{
    $def = defaults_of(schema()['collections']['expositions']['fields']);
    $list = array_map(fn($x) => array_replace($def, $x), collection('expositions'));
    $list = array_filter($list, fn($x) => $all || !empty($x['publie']));
    usort($list, fn($a, $b) => strcmp($a['date_debut'] ?? '', $b['date_debut'] ?? ''));
    return array_values($list);
}

function expo_fin(array $x): string
{
    return $x['date_fin'] ?: ($x['date_debut'] ?? '');
}

/** L'exposition en cours (ou, à défaut, la dernière passée). */
function expo_courante(): ?array
{
    $t = today();
    $cur = null;
    foreach (expositions() as $x) {
        if (($x['date_debut'] ?? '') <= $t) {
            $cur = $x; // la plus récente déjà ouverte
        }
    }
    return $cur;
}

function expo_est_en_cours(?array $x): bool
{
    return $x && ($x['date_debut'] ?? '') <= today() && expo_fin($x) >= today();
}

function expos_a_venir(): array
{
    $t = today();
    return array_values(array_filter(expositions(), fn($x) => ($x['date_debut'] ?? '') > $t));
}

function expo_par_slug(string $slug): ?array
{
    foreach (expositions() as $x) {
        if (($x['slug'] ?? '') === $slug) {
            return $x;
        }
    }
    return null;
}

/* ------------------------------------------------------------------ */
/* Dates en français                                                   */
/* ------------------------------------------------------------------ */

const FR_MOIS = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
const FR_MOIS_COURT = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
const FR_JOURS = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

/**
 * Formats : 'long' (samedi 14 novembre), 'longy' (samedi 14 novembre 2026), 'dm' (14 novembre),
 * 'dmy' (14 novembre 2026), 'court' (14 nov.), 'num' (14.11), 'my' (nov. 2026)
 */
function fr_date(?string $ymd, string $fmt = 'dm'): string
{
    if (!$ymd || !($ts = strtotime($ymd))) {
        return '';
    }
    $j = (int)date('j', $ts);
    $jj = $j === 1 ? '1er' : (string)$j;
    $m = (int)date('n', $ts);
    $y = date('Y', $ts);
    $w = FR_JOURS[(int)date('w', $ts)];
    return match ($fmt) {
        'long' => "$w $jj " . FR_MOIS[$m],
        'longy' => "$w $jj " . FR_MOIS[$m] . " $y",
        'dmy' => "$jj " . FR_MOIS[$m] . " $y",
        'court' => "$j " . FR_MOIS_COURT[$m],
        'num' => date('d.m', $ts),
        'my' => FR_MOIS_COURT[$m] . " $y",
        default => "$jj " . FR_MOIS[$m],
    };
}

/** « 18 h », « 19 h 50 » */
function fr_heure(?string $hm): string
{
    if (!$hm || !preg_match('/^(\d{1,2}):(\d{2})/', $hm, $m)) {
        return '';
    }
    return (int)$m[1] . ' h' . ($m[2] !== '00' ? ' ' . $m[2] : '');
}

function fr_plage(?string $a, ?string $b): string
{
    $a = fr_heure($a);
    $b = fr_heure($b);
    return $b ? "$a – $b" : $a;
}

/** Période d'une exposition : « du 17 octobre au 11 novembre 2026 » */
function fr_periode(array $x): string
{
    $d = $x['date_debut'] ?? '';
    $f = $x['date_fin'] ?? '';
    if (!$f || $f === $d) {
        return 'le ' . fr_date($d, 'dmy');
    }
    $same = substr($d, 0, 4) === substr($f, 0, 4);
    return 'du ' . fr_date($d, $same ? 'dm' : 'dmy') . ' au ' . fr_date($f, 'dmy');
}

/* ------------------------------------------------------------------ */
/* URLs, fichiers                                                      */
/* ------------------------------------------------------------------ */

function url(string $path): string
{
    if (preg_match('#^(https?:|mailto:|tel:|\#|/)#', $path)) {
        return $path;
    }
    return $GLOBALS['BASE'] . $path;
}

function tel_href(string $tel): string
{
    $n = preg_replace('/[^0-9+]/', '', $tel);
    if (str_starts_with($n, '0')) {
        $n = '+33' . substr($n, 1);
    }
    return 'tel:' . $n;
}

/** Enregistre un fichier téléversé dans /uploads. Retourne le chemin relatif ou null. */
function save_upload(array $file, array $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'], string $dir = UPLOAD_DIR, string $prefix = 'uploads/'): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Le fichier « ' . ($file['name'] ?? '') . ' » n\'a pas pu être envoyé (trop lourd ?).');
    }
    if ($file['size'] > UPLOAD_MAX) {
        throw new RuntimeException('Le fichier « ' . $file['name'] . ' » dépasse 8 Mo.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Format non accepté pour « ' . $file['name'] . ' ».');
    }
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $base = slugify(pathinfo((string)$file['name'], PATHINFO_FILENAME));
    $name = substr($base, 0, 50) . '-' . substr(bin2hex(random_bytes(3)), 0, 6) . '.' . $allowed[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        throw new RuntimeException('Impossible d\'enregistrer le fichier.');
    }
    return $prefix . $name;
}

/* ------------------------------------------------------------------ */
/* Sécurité : CSRF, messages flash                                     */
/* ------------------------------------------------------------------ */

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
        http_response_code(400);
        exit('Session expirée : rechargez la page et recommencez.');
    }
}

function flash(?string $msg = null, string $type = 'ok')
{
    if ($msg !== null) {
        $_SESSION['flash'][] = [$type, $msg];
        return null;
    }
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

/* ------------------------------------------------------------------ */
/* Désinscription de la newsletter                                     */
/* ------------------------------------------------------------------ */

function app_secret(): string
{
    $s = data_load('secret', []);
    if (empty($s['cle'])) {
        $s = ['cle' => bin2hex(random_bytes(32))];
        data_save('secret', $s);
    }
    return $s['cle'];
}

function jeton_desinscription(string $email): string
{
    return substr(hash_hmac('sha256', mb_strtolower($email), app_secret()), 0, 24);
}

/** Adresse absolue du site (ex. https://laboucherie.fr/). */
function site_url(): string
{
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    $dir = preg_replace('#/admin$#', '', $dir);
    return ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir . '/';
}

function lien_desinscription(string $email): string
{
    return site_url() . 'desinscription.php?e=' . rawurlencode($email) . '&t=' . jeton_desinscription($email);
}
