<?php
/**
 * Synchronisation du Répertoire des contacts avec le Répertoire de Copains d'après (partage.php).
 * Le Répertoire de Copains est la référence commune ; la galerie en garde une copie
 * enrichie (discipline, groupes, photo…) et synchronise nom, e-mail, téléphone, notes.
 */

const COPAINS_INTERVALLE = 60; // secondes minimum entre deux synchronisations automatiques

/** Origines du Répertoire de Copains d'après (mêmes noms et couleurs que leur page Répertoire). */
const ORIGINES = [
    'membres' => ['Membres CdA', 'Memb. CdA', 'oklch(0.52 0.10 40)'],
    'sympathisants' => ['Sympathisants CdA', 'Symp. CdA', 'oklch(0.52 0.10 150)'],
    'galerie' => ['Galerie éphémère', 'Galerie', 'oklch(0.52 0.10 320)'],
    'grandangle' => ['Grand Angle Léré', 'G. Angle', 'oklch(0.52 0.10 250)'],
    'artistes' => ['Artistes', 'Artistes', 'oklch(0.55 0.11 75)'],
];

/** Origines d'une fiche : celles de Copains, ou « galerie » pour une fiche créée ici. */
function origines_de(array $a): array
{
    $p = array_values(array_filter((array)($a['projets'] ?? []), 'is_string'));
    if ($p || !empty($a['cda_id'])) {
        return $p; // fiche du Répertoire de Copains : ses origines telles quelles
    }
    return ['galerie']; // fiche créée par la galerie
}

function badges_origine(array $a): string
{
    $h = '';
    foreach (origines_de($a) as $p) {
        [$nom, $court, $coul] = ORIGINES[$p] ?? [$p, $p, '#4F5B66'];
        $h .= '<span class="origine" style="--c:' . $coul . '" title="' . e($nom) . '">' . e($court) . '</span> ';
    }
    return $h;
}

function copains_actif(): bool
{
    $s = settings();
    return trim($s['copains_url']) !== '' && strlen(trim($s['copains_cle'])) >= 20;
}

/** Appel HTTP vers partage.php. Retourne [code, données]. */
function copains_appel(string $methode, string $action, ?array $corps = null): array
{
    $s = settings();
    $url = rtrim(trim($s['copains_url']), '/') . '/partage.php?action=' . urlencode($action);
    $entetes = ['X-Cle-Partage: ' . trim($s['copains_cle']), 'Accept: application/json'];
    $data = $corps !== null ? json_encode($corps, JSON_UNESCAPED_UNICODE) : null;
    if ($data !== null) {
        $entetes[] = 'Content-Type: application/json';
    }
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 12, CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_HTTPHEADER => $entetes, CURLOPT_CUSTOMREQUEST => $methode, CURLOPT_FOLLOWLOCATION => true,
        ]);
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        $rep = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => ['method' => $methode, 'header' => implode("\r\n", $entetes), 'content' => $data ?? '', 'timeout' => 12, 'ignore_errors' => true]]);
        $rep = @file_get_contents($url, false, $ctx);
        $code = 0;
        foreach ($http_response_header ?? [] as $h) {
            if (preg_match('#^HTTP/\S+ (\d{3})#', $h, $m)) {
                $code = (int)$m[1];
            }
        }
    }
    $json = is_string($rep) ? json_decode($rep, true) : null;
    return [$code, is_array($json) ? $json : []];
}

function copains_erreur(int $code, array $d): string
{
    return match (true) {
        $code === 0 => 'le site Copains d\'après ne répond pas (adresse incorrecte ou site hors ligne)',
        $code === 401 => 'la clé de partage est refusée : vérifiez qu\'elle est identique des deux côtés',
        $code === 503 => 'le partage n\'est pas encore activé côté Copains d\'après (clé à renseigner dans config.php)',
        $code === 404 => 'partage.php est introuvable sur le site Copains d\'après (fichier pas encore envoyé ?)',
        default => 'erreur ' . $code . (isset($d['error']) ? ' (' . $d['error'] . ')' : ''),
    };
}

/** Empreinte des champs partagés, pour savoir ce qui a changé depuis la dernière synchro. */
function copains_sig(string $nom, string $email, string $tel, string $note): string
{
    return md5(json_encode([trim($nom), mb_strtolower(trim($email)), trim($tel), trim($note)], JSON_UNESCAPED_UNICODE));
}

function copains_sig_locale(array $a): string
{
    return copains_sig($a['nom'] ?? '', $a['email'] ?? '', $a['telephone'] ?? '', $a['notes'] ?? '');
}

/** Envoie une fiche locale à Copains. Modifie $a (cda_id, empreinte) en cas de succès. */
function copains_envoyer(array &$a): ?string
{
    $galerie = in_array('galerie', origines_de($a), true);
    $payload = ['contact' => ['id' => $a['cda_id'] ?? '', 'nom' => $a['nom'], 'email' => $a['email'], 'tel' => $a['telephone'], 'note' => $a['notes'], 'galerie' => $galerie]];
    [$code, $d] = copains_appel('POST', 'enregistrer', $payload);
    if ($code === 409 && !empty($d['contact']['id'])) {
        // L'e-mail appartient déjà à une fiche de Copains : on s'y rattache.
        $payload['contact']['id'] = $d['contact']['id'];
        [$code, $d] = copains_appel('POST', 'enregistrer', $payload);
    }
    if ($code !== 200 || empty($d['contact']['id'])) {
        $a['sync_attente'] = true;
        return copains_erreur($code, $d);
    }
    $c = $d['contact'];
    $a['cda_id'] = $c['id'];
    $a['projets'] = (array)($c['projets'] ?? []);
    $a['cda_sig'] = copains_sig($c['nom'], $c['email'], $c['tel'], $c['note']);
    $a['sync_attente'] = false;
    return null;
}

function copains_retirer(string $cdaId): ?string
{
    [$code, $d] = copains_appel('POST', 'retirer', ['id' => $cdaId]);
    return $code === 200 ? null : copains_erreur($code, $d);
}

function copains_etat(): array
{
    return data_load('synchro_copains', ['date' => null, 'ok' => null, 'message' => '', 'partagees' => 0]);
}

/**
 * Synchronisation complète, dans les deux sens.
 * 1. lit les fiches « galerie » du Répertoire de Copains et met à jour / crée les fiches locales ;
 * 2. envoie les fiches locales nouvelles ou modifiées.
 */
function copains_synchroniser(): array
{
    $stats = ['ajout' => 0, 'maj' => 0, 'envoyees' => 0, 'detachees' => 0, 'erreur' => null];
    if (!copains_actif()) {
        $stats['erreur'] = 'partage non configuré';
        return $stats;
    }
    [$code, $d] = copains_appel('GET', 'contacts');
    if ($code !== 200 || !isset($d['contacts']) || !is_array($d['contacts'])) {
        $stats['erreur'] = copains_erreur($code, $d);
        data_save('synchro_copains', ['date' => date('c'), 'ok' => false, 'message' => $stats['erreur'], 'partagees' => copains_etat()['partagees']]);
        return $stats;
    }
    $distants = $d['contacts'];
    $defaut = defaults_of(schema()['collections']['repertoire']['fields']);
    $list = array_map(fn($x) => array_replace($defaut, $x), collection('repertoire'));

    $parId = $parEmail = $parNom = [];
    foreach ($list as $i => $a) {
        if (!empty($a['cda_id'])) $parId[$a['cda_id']] = $i;
        if ($a['email'] !== '') $parEmail[mb_strtolower($a['email'])] = $i;
        $parNom[slugify($a['nom'])] = $i;
    }
    $vus = [];
    $ignores = array_flip(data_load('copains_ignores', []));
    foreach ($distants as $c) {
        if (empty($c['id'])) continue;
        if (isset($ignores[$c['id']])) {
            continue; // fiche supprimée ici par la galerie : on ne la recrée pas
        }
        $vus[$c['id']] = true;
        $sig = copains_sig($c['nom'] ?? '', $c['email'] ?? '', $c['tel'] ?? '', $c['note'] ?? '');
        $i = $parId[$c['id']] ?? null;
        if ($i === null && ($c['email'] ?? '') !== '') $i = $parEmail[mb_strtolower($c['email'])] ?? null;
        if ($i === null && ($c['nom'] ?? '') !== '') {
            $j = $parNom[slugify($c['nom'])] ?? null;
            if ($j !== null && empty($list[$j]['cda_id'])) $i = $j;
        }
        if ($i === null) {
            $list[] = array_replace($defaut, [
                'id' => new_id(), 'nom' => $c['nom'] ?: $c['email'], 'email' => $c['email'] ?? '', 'telephone' => $c['tel'] ?? '',
                'notes' => $c['note'] ?? '', 'groupes' => '', 'partage' => true, 'projets' => (array)($c['projets'] ?? []),
                'cda_id' => $c['id'], 'cda_sig' => $sig, 'sync_attente' => false,
            ]);
            $stats['ajout']++;
            continue;
        }
        $a = &$list[$i];
        $a['cda_id'] = $c['id'];
        $a['partage'] = true;
        $a['projets'] = (array)($c['projets'] ?? []);
        if (!empty($a['sync_attente'])) {
            unset($a);
            continue; // modification locale pas encore envoyée : elle part juste après
        }
        if (($a['cda_sig'] ?? '') !== $sig && copains_sig_locale($a) !== $sig) {
            $a['nom'] = $c['nom'] ?: $a['nom'];
            $a['email'] = $c['email'] ?? '';
            $a['telephone'] = $c['tel'] ?? '';
            $a['notes'] = $c['note'] ?? '';
            $stats['maj']++;
        }
        $a['cda_sig'] = $sig;
        unset($a);
    }
    // Fiches retirées du projet « galerie » (ou supprimées) côté Copains : on les garde ici, détachées.
    foreach ($list as &$a) {
        if (!empty($a['cda_id']) && !isset($vus[$a['cda_id']]) && empty($a['sync_attente'])) {
            $a['cda_id'] = '';
            $a['cda_sig'] = '';
            $a['partage'] = false;
            $a['projets'] = [];
            $stats['detachees']++;
        }
    }
    unset($a);
    // Envoi des fiches locales nouvelles ou modifiées
    foreach ($list as &$a) {
        if (empty($a['partage'])) continue;
        $change = empty($a['cda_id']) || !empty($a['sync_attente']) || copains_sig_locale($a) !== ($a['cda_sig'] ?? '');
        if (!$change) continue;
        $err = copains_envoyer($a);
        if ($err) {
            $stats['erreur'] = $err;
            break;
        }
        $stats['envoyees']++;
    }
    unset($a);
    data_save('repertoire', array_values($list));
    $partagees = count(array_filter($list, fn($a) => !empty($a['cda_id'])));
    data_save('synchro_copains', ['date' => date('c'), 'ok' => $stats['erreur'] === null, 'message' => $stats['erreur'] ?? '', 'partagees' => $partagees, 'origines' => array_keys((array)($d['origines'] ?? ['galerie' => 1]))]);
    return $stats;
}

/** Synchronise si la dernière synchro date de plus d'une minute. */
function copains_synchro_auto(): ?array
{
    if (!copains_actif()) return null;
    $e = copains_etat();
    if ($e['date'] && time() - strtotime($e['date']) < COPAINS_INTERVALLE) return null;
    return copains_synchroniser();
}

function copains_resume(array $st): string
{
    $parts = [];
    if ($st['ajout']) $parts[] = $st['ajout'] . ' fiche(s) reçue(s) de Copains';
    if ($st['maj']) $parts[] = $st['maj'] . ' mise(s) à jour depuis Copains';
    if ($st['envoyees']) $parts[] = $st['envoyees'] . ' envoyée(s) à Copains';
    if ($st['detachees']) $parts[] = $st['detachees'] . ' retirée(s) du partage côté Copains';
    return $parts ? implode(', ', $parts) . '.' : 'Tout est déjà à jour.';
}
