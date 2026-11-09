<?php
/** Fichier calendrier (.ics) : une exposition (?slug=) ou tout le programme à venir. */
require __DIR__ . '/inc/bootstrap.php';

$s = settings();
$slug = (string)($_GET['slug'] ?? '');
$list = $slug !== '' ? array_filter([expo_par_slug($slug)]) : expos_a_venir();
if (!$list) {
    http_response_code(404);
    exit('Événement introuvable.');
}

function ics_esc(string $s): string
{
    return str_replace(["\\", ';', ',', "\r\n", "\n"], ["\\\\", '\;', '\,', '\n', '\n'], $s);
}
function ics_dt(string $date, string $time): string
{
    return date('Ymd\THis', strtotime($date . ' ' . ($time ?: '18:00')));
}

$host = $_SERVER['HTTP_HOST'] ?? 'laboucherie';
$out = ["BEGIN:VCALENDAR", "VERSION:2.0", "PRODID:-//La Boucherie//Galerie//FR", "CALSCALE:GREGORIAN", "METHOD:PUBLISH",
    "BEGIN:VTIMEZONE", "TZID:Europe/Paris",
    "BEGIN:DAYLIGHT", "TZOFFSETFROM:+0100", "TZOFFSETTO:+0200", "TZNAME:CEST", "DTSTART:19700329T020000", "RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU", "END:DAYLIGHT",
    "BEGIN:STANDARD", "TZOFFSETFROM:+0200", "TZOFFSETTO:+0100", "TZNAME:CET", "DTSTART:19701025T030000", "RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU", "END:STANDARD",
    "END:VTIMEZONE"];
foreach ($list as $x) {
    $fin = $x['heure_fin'] ?: date('H:i', strtotime(($x['heure_debut'] ?: '18:00') . ' +2 hours'));
    $out[] = "BEGIN:VEVENT";
    $out[] = "UID:" . $x['id'] . "@" . $host;
    $out[] = "DTSTAMP:" . gmdate('Ymd\THis\Z');
    $out[] = "DTSTART;TZID=Europe/Paris:" . ics_dt($x['date_debut'], $x['heure_debut']);
    $out[] = "DTEND;TZID=Europe/Paris:" . ics_dt($x['date_debut'], $fin);
    $out[] = "SUMMARY:" . ics_esc('Vernissage ' . $x['artiste'] . ' — ' . $s['nom']);
    $out[] = "LOCATION:" . ics_esc($s['nom'] . ', ' . $s['adresse'] . ', ' . $s['code_postal'] . ' ' . $s['ville']);
    $out[] = "DESCRIPTION:" . ics_esc(trim(($x['titre'] ? $x['titre'] . "\n" : '') . $x['resume'] . "\nEntrée libre et gratuite."));
    $out[] = "END:VEVENT";
}
$out[] = "END:VCALENDAR";

header('Content-Type: text/calendar; charset=utf-8');
header('Content-Disposition: attachment; filename="' . ($slug !== '' ? 'vernissage-' . slugify($slug) : 'programme-la-boucherie') . '.ics"');
echo implode("\r\n", $out) . "\r\n";
