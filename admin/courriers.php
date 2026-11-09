<?php
/**
 * Courriers : modèles pré-rédigés, blocs à insérer (prochain accrochage, programme),
 * mise en page des e-mails aux couleurs de la galerie, version texte, nettoyage du HTML.
 * Les e-mails n'utilisent que des styles « en ligne », seuls compris par les messageries.
 */

const C_NUIT = '#1E2A36';
const C_BRIQUE = '#B5412C';
const C_CREME = '#F8F2E8';
const C_GRIS = '#4F5B66';

/** Adresse absolue d'un fichier du site (images, pages), pour qu'elle marche dans un e-mail. */
function absolu(string $chemin): string
{
    if ($chemin === '' || preg_match('#^(https?:|data:)#', $chemin)) {
        return $chemin;
    }
    return site_url() . ltrim($chemin, '/');
}

/** La prochaine exposition à accrocher (sinon celle en cours). */
function prochain_accrochage(): ?array
{
    return expos_a_venir()[0] ?? expo_courante();
}

function bloc_accrochage(?array $x = null): string
{
    $x ??= prochain_accrochage();
    if (!$x) {
        return '<p><em>(Aucune exposition au programme pour l\'instant.)</em></p>';
    }
    $s = settings();
    $venir = $x['date_debut'] > today();
    $h = '<div style="margin:24px 0;border:1px solid #EADFCF;background:#FFFEFB;text-align:center">'
        . '<div style="height:12px;background:' . C_BRIQUE . '"></div>'
        . '<div style="padding:22px 20px 26px">'
        . '<p style="margin:0 0 12px;font:bold 12px Arial,sans-serif;letter-spacing:2px;text-transform:uppercase;color:' . C_BRIQUE . '">' . ($venir ? 'Prochain accrochage' : 'En ce moment sous le store') . '</p>';
    if ($x['image']) {
        $h .= '<img src="' . e(absolu($x['image'])) . '" alt="' . e($x['image_alt'] ?: 'Affiche de l\'exposition de ' . $x['artiste']) . '" width="260" style="display:block;margin:0 auto;width:260px;max-width:100%;height:auto;border:0">';
    }
    $h .= '<h2 style="margin:16px 0 4px;font:normal 30px/1.1 Georgia,serif;color:' . C_NUIT . '">' . e($x['artiste']) . '</h2>';
    if ($x['titre']) {
        $h .= '<p style="margin:0 0 12px;font:italic 20px Georgia,serif;color:' . C_BRIQUE . '">' . e($x['titre']) . '</p>';
    }
    $h .= '<p style="margin:0 0 4px;font:bold 15px Arial,sans-serif;color:' . C_NUIT . '">Vernissage ' . e(fr_date($x['date_debut'], 'long')) . ' à ' . e(fr_heure($x['heure_debut'] ?: '18:00')) . '</p>'
        . '<p style="margin:0 0 14px;font:14px Arial,sans-serif;color:' . C_GRIS . '">' . e(ucfirst(fr_periode($x))) . ' · ' . e($s['adresse'] . ', ' . $s['ville']) . ' · entrée libre</p>';
    $texte = trim($x['resume'] ?: $x['accroche']);
    if ($texte !== '') {
        $h .= '<p style="margin:0 0 18px;font:15px/1.6 Arial,sans-serif;color:' . C_NUIT . '">' . e($texte) . '</p>';
    }
    $h .= '<a href="' . e(absolu('expo.php?slug=' . urlencode($x['slug']))) . '" style="display:inline-block;padding:12px 26px;border-radius:30px;background:' . C_BRIQUE . ';color:#FFFFFF;font:bold 14px Arial,sans-serif;text-decoration:none">Découvrir l\'exposition</a>'
        . '</div></div>';
    return $h;
}

function bloc_programme(): string
{
    $next = expos_a_venir();
    if (!$next) {
        return '<p><em>(Le programme des prochains mois est en préparation.)</em></p>';
    }
    $h = '<div style="margin:22px 0;padding:18px 20px;background:' . C_CREME . '">'
        . '<p style="margin:0 0 10px;font:bold 12px Arial,sans-serif;letter-spacing:2px;text-transform:uppercase;color:' . C_BRIQUE . '">Prochainement sous le store</p><ul style="margin:0;padding:0 0 0 18px">';
    foreach ($next as $x) {
        $h .= '<li style="margin:0 0 8px;font:15px/1.5 Arial,sans-serif;color:' . C_NUIT . '"><strong>' . e(ucfirst(fr_date($x['date_debut'], 'long'))) . '</strong> · '
            . e($x['artiste']) . ($x['technique'] ? ' <span style="color:' . C_GRIS . '">— ' . e(mb_strtolower($x['technique'])) . '</span>' : '') . '</li>';
    }
    return $h . '</ul><p style="margin:10px 0 0;font:13px Arial,sans-serif;color:' . C_GRIS . '">Vernissages à 18 h, entrée libre et gratuite.</p></div>';
}

/** Modèles proposés en haut de l'éditeur. 'pour' = destinataires suggérés. */
function modeles(): array
{
    $s = settings();
    $x = prochain_accrochage();
    $quand = $x ? fr_date($x['date_debut'], 'dm') : '';
    $p = fn(string $t) => '<p>' . $t . '</p>';
    return [
        'libre' => [
            'nom' => 'Message libre', 'aide' => 'Une page blanche, pour tout type de courrier.',
            'sujet' => '', 'corps' => $p('Bonjour {prenom},') . $p('<br>'), 'pour' => '',
        ],
        'accrochage' => [
            'nom' => 'Newsletter · prochain accrochage', 'aide' => $x ? 'Déjà rédigée avec ' . $x['artiste'] . ($quand ? ' (' . $quand . ')' : '') . '.' : 'Aucune exposition à venir pour l\'instant.',
            'sujet' => $x ? 'Prochain accrochage : ' . $x['artiste'] . ' — vernissage le ' . $quand : 'Les nouvelles de ' . $s['nom'],
            'corps' => $p('Bonjour {prenom},')
                . $p($x ? 'Le store se lève bientôt sur une nouvelle exposition : nous avons le plaisir de vous présenter <strong>' . e($x['artiste']) . '</strong>.' : 'Voici les nouvelles de la galerie.')
                . bloc_accrochage($x)
                . $p('Le vernissage est ouvert à tous : venez nombreux, et n\'hésitez pas à faire suivre ce message.')
                . bloc_programme()
                . $p('Au plaisir de vous voir sous le store !'),
            'pour' => 'newsletter',
        ],
        'invitation' => [
            'nom' => 'Invitation au vernissage', 'aide' => 'Courte, centrée sur la date du vernissage.',
            'sujet' => $x ? 'Invitation · vernissage de ' . $x['artiste'] . ' le ' . $quand : 'Invitation',
            'corps' => $p('Bonjour {prenom},')
                . $p($x ? 'Nous serions heureux de vous accueillir au vernissage de l\'exposition de <strong>' . e($x['artiste']) . '</strong>, ' . e(fr_date($x['date_debut'], 'long')) . ' à ' . e(fr_heure($x['heure_debut'] ?: '18:00')) . '.' : 'Nous serions heureux de vous accueillir au prochain vernissage.')
                . bloc_accrochage($x)
                . $p('Entrée libre et gratuite. À très vite !'),
            'pour' => '',
        ],
        'programme' => [
            'nom' => 'Programme des prochaines expos', 'aide' => 'La liste des prochains vernissages.',
            'sujet' => 'Le programme de ' . $s['nom'],
            'corps' => $p('Bonjour {prenom},') . $p('Voici les prochains rendez-vous sous le store :') . bloc_programme() . $p('Au plaisir de vous y retrouver !'),
            'pour' => 'newsletter',
        ],
        'candidature' => [
            'nom' => 'Réponse à une candidature', 'aide' => 'Pour répondre à un artiste qui souhaite exposer.',
            'sujet' => 'Votre candidature à ' . $s['nom'],
            'corps' => $p('Bonjour {prenom},') . $p('Merci pour votre candidature et pour les photos de votre travail, que nous avons regardées avec attention.')
                . $p('[Votre réponse : proposition de mois, questions, ou suite à donner]')
                . $p('Pour en parler de vive voix : ' . e($s['telephone']) . '.') . $p('Bien cordialement,'),
            'pour' => '',
        ],
    ];
}

/* ------------------------------------------------------------------ */
/* Nettoyage, mise en page, version texte                              */
/* ------------------------------------------------------------------ */

/** Ne garde que des balises sûres pour un e-mail (rien qui puisse s'exécuter). */
function nettoyer_html(string $html): string
{
    $permis = ['p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 'h2' => [], 'h3' => [],
        'ul' => [], 'ol' => [], 'li' => [], 'blockquote' => [], 'hr' => [], 'div' => [], 'span' => [],
        'a' => ['href'], 'img' => ['src', 'alt', 'width', 'height']];
    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div id="racine">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $racine = $doc->getElementById('racine');
    if (!$racine) {
        return '';
    }
    $nettoie = function (DOMNode $n) use (&$nettoie, $permis, $doc) {
        foreach (iterator_to_array($n->childNodes) as $c) {
            if ($c instanceof DOMComment) {
                $n->removeChild($c);
                continue;
            }
            if (!($c instanceof DOMElement)) {
                continue;
            }
            $tag = strtolower($c->nodeName);
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'link', 'meta'], true)) {
                $n->removeChild($c);
                continue;
            }
            $nettoie($c);
            if (!isset($permis[$tag])) { // balise inconnue : on garde son contenu
                while ($c->firstChild) {
                    $n->insertBefore($c->firstChild, $c);
                }
                $n->removeChild($c);
                continue;
            }
            foreach (iterator_to_array($c->attributes) as $att) {
                $nom = strtolower($att->name);
                $val = trim($att->value);
                $garder = in_array($nom, $permis[$tag], true) || $nom === 'style';
                if ($nom === 'style' && preg_match('/expression|javascript:|url\s*\(|@import|behavior/i', $val)) {
                    $garder = false;
                }
                if ($nom === 'href' && !preg_match('#^(https?://|mailto:|tel:|\{)#i', $val)) {
                    $garder = false;
                }
                if ($nom === 'src' && !preg_match('#^https?://#i', $val)) {
                    $garder = false;
                }
                if (!$garder) {
                    $c->removeAttribute($att->name);
                }
            }
            if ($tag === 'img' && !$c->hasAttribute('src')) {
                $n->removeChild($c);
            }
            if ($tag === 'a' && $c->hasAttribute('href')) {
                $c->setAttribute('target', '_blank');
            }
        }
    };
    $nettoie($racine);
    $out = '';
    foreach ($racine->childNodes as $c) {
        $out .= $doc->saveHTML($c);
    }
    return trim($out);
}

/** Remplace {prenom} et {nom} dans du HTML (valeurs échappées). */
function personnaliser_html(string $html, string $nom): string
{
    $nom = trim($nom);
    $prenom = $nom !== '' ? explode(' ', $nom)[0] : '';
    $h = strtr($html, ['{prenom}' => e($prenom), '{Prenom}' => e($prenom), '{nom}' => e($nom), '{Nom}' => e($nom)]);
    return preg_replace('/Bonjour\s+,/u', 'Bonjour,', $h);
}

/** Habillage complet de l'e-mail : en-tête au store rayé, corps, signature, pied de page. */
function email_html(string $corps, string $sujet, bool $signature, string $desinscription = ''): string
{
    $s = settings();
    $rayures = '';
    for ($i = 0; $i < 20; $i++) {
        $rayures .= '<td style="height:16px;background:' . ($i % 2 ? C_CREME : C_BRIQUE) . '"></td>';
    }
    $sig = $signature && trim($s['signature']) !== ''
        ? '<p style="margin:26px 0 0;padding-top:14px;border-top:1px solid #EADFCF;font:13px/1.6 Arial,sans-serif;color:' . C_GRIS . '">' . nl2br(e($s['signature']), false) . '</p>' : '';
    $site = site_url();
    $pied = e($s['adresse'] . ', ' . $s['code_postal'] . ' ' . $s['ville']) . ' · ' . e($s['telephone'])
        . ' · <a href="' . e($site) . '" style="color:#FFE0CC">' . e(preg_replace('#^https?://|/$#', '', $site)) . '</a>';
    if ($desinscription !== '') {
        $pied .= '<br><span style="color:#B9C2CA">Vous recevez ce message car vous êtes inscrit·e au programme. <a href="' . e($desinscription) . '" style="color:#B9C2CA">Se désinscrire</a></span>';
    }
    // Styles par défaut des paragraphes et titres écrits dans l'éditeur
    $corps = preg_replace('/<p>/', '<p style="margin:0 0 14px;font:16px/1.6 Arial,sans-serif;color:' . C_NUIT . '">', $corps);
    $corps = preg_replace('/<h2>/', '<h2 style="margin:22px 0 10px;font:normal 26px/1.2 Georgia,serif;color:' . C_NUIT . '">', $corps);
    $corps = preg_replace('/<h3>/', '<h3 style="margin:18px 0 8px;font:bold 17px Arial,sans-serif;color:' . C_BRIQUE . '">', $corps);
    $corps = preg_replace('/<a (?![^>]*\bstyle=)/', '<a style="color:' . C_BRIQUE . '" ', $corps); // liens simples seulement
    return '<!doctype html><html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . e($sujet) . '</title></head>'
        . '<body style="margin:0;padding:0;background:#F4F1EC">'
        . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F4F1EC"><tr><td align="center" style="padding:24px 12px">'
        . '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:100%;background:#FFFFFF">'
        . '<tr><td><table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>' . $rayures . '</tr></table></td></tr>'
        . '<tr><td style="background:' . C_NUIT . ';padding:18px 28px;text-align:center"><span style="font:bold 26px Georgia,serif;letter-spacing:5px;text-transform:uppercase;color:#FFFFFF">' . e($s['nom']) . '</span><br><span style="font:12px Arial,sans-serif;letter-spacing:3px;text-transform:uppercase;color:#FFB58F">galerie éphémère · ' . e($s['ville']) . '</span></td></tr>'
        . '<tr><td style="padding:30px 32px 32px;font:16px/1.6 Arial,sans-serif;color:' . C_NUIT . '">' . $corps . $sig . '</td></tr>'
        . '<tr><td style="background:' . C_NUIT . ';padding:18px 28px;text-align:center;font:12px/1.7 Arial,sans-serif;color:#D9CFC2">' . $pied . '</td></tr>'
        . '</table></td></tr></table></body></html>';
}

/** Version texte, pour les messageries qui n'affichent pas le HTML. */
function html_vers_texte(string $html): string
{
    $t = preg_replace('#<br\s*/?>#i', "\n", $html);
    $t = preg_replace('#<li[^>]*>#i', "• ", $t);
    $t = preg_replace('#<a [^>]*href="([^"]+)"[^>]*>(.*?)</a>#is', '$2 ($1)', $t);
    $t = preg_replace('#</(p|div|h2|h3|li|ul|blockquote)>#i', "\n\n", $t);
    $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $t = preg_replace("/[ \t]+\n/", "\n", $t);
    $t = preg_replace("/\n{3,}/", "\n\n", $t);
    return trim($t);
}
