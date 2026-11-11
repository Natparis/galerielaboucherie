<?php
/**
 * Description de tout ce qui se gère dans le back-office.
 * Types de champs : text, textarea, date, time, email, tel, url, number, checkbox,
 * select (options), image, file (PDF), list (sous-champs répétables).
 * « help » s'affiche sous le champ ; « default » sert de contenu initial.
 */

$disciplines = ['Peinture', 'Céramique', 'Sculpture', 'Photographie', 'Gravure', 'Collage', 'Mosaïque', 'Bois', 'Performance', 'Dessin', 'Textile', 'Autre'];

$schema = [

    /* ============================ COLLECTIONS ============================ */
    'collections' => [

        'expositions' => [
            'label' => 'Expositions',
            'singular' => 'une exposition',
            'title' => 'artiste',
            'icon' => 'calendar',
            'help' => 'Une exposition = un artiste (ou un duo) et un vernissage. L\'expo « du mois » et le programme se mettent à jour tout seuls selon les dates.',
            'list' => ['date_debut' => 'Vernissage', 'artiste' => 'Artiste', 'titre' => 'Titre', 'discipline' => 'Discipline'],
            'sort' => 'date_debut',
            'fields' => [
                'artiste' => ['label' => 'Artiste(s)', 'type' => 'text', 'required' => true, 'help' => 'Ex. : Béatrice Sermondadaz, ou « Françoise Dubarry et Bernard Conte »'],
                'artiste_court' => ['label' => 'Nom court', 'type' => 'text', 'help' => 'Facultatif. Utilisé dans les listes serrées (ex. « Dubarry et Conte »).'],
                'titre' => ['label' => 'Titre de l\'exposition', 'type' => 'text', 'help' => 'Ex. : Se confronter aux arts du feu'],
                'discipline' => ['label' => 'Discipline', 'type' => 'select', 'options' => $disciplines, 'help' => 'Sert aux filtres de la page Artistes.'],
                'technique' => ['label' => 'Technique en quelques mots', 'type' => 'text', 'help' => 'Ex. : Céramique, raku — Marbres et tesselles'],
                'date_debut' => ['label' => 'Date du vernissage', 'type' => 'date', 'required' => true, 'col' => 'third'],
                'heure_debut' => ['label' => 'Heure de début', 'type' => 'time', 'default' => '18:00', 'col' => 'third'],
                'heure_fin' => ['label' => 'Heure de fin', 'type' => 'time', 'default' => '23:00', 'col' => 'third'],
                'date_fin' => ['label' => 'Dernier jour de l\'exposition', 'type' => 'date', 'col' => 'half', 'help' => 'Laisser vide si l\'expo ne dure que le jour du vernissage.'],
                'telephone_rdv' => ['label' => 'Téléphone pour rendez-vous', 'type' => 'tel', 'col' => 'half'],
                'vernissage_note' => ['label' => 'Précision sur le vernissage', 'type' => 'text', 'default' => 'ouvert à tous', 'help' => 'Ex. : ouvert à tous, buffet servi'],
                'permanences' => ['label' => 'Permanences de l\'artiste', 'type' => 'textarea', 'rows' => 2, 'help' => 'Une par ligne. Ex. : Vendredi 6 novembre, 10 h – 17 h'],
                'image' => ['label' => 'Affiche ou visuel principal', 'type' => 'image', 'help' => 'Format portrait (2/3) idéal.'],
                'image_alt' => ['label' => 'Description de l\'image (accessibilité)', 'type' => 'text', 'help' => 'Ce que montre l\'image, pour les personnes malvoyantes.'],
                'resume' => ['label' => 'Résumé court', 'type' => 'textarea', 'rows' => 3, 'help' => 'Deux ou trois phrases, affichées sur l\'accueil et le programme.'],
                'accroche' => ['label' => 'Phrase d\'accroche', 'type' => 'textarea', 'rows' => 2, 'help' => 'Affichée en grand en tête du texte.'],
                'texte' => ['label' => 'Texte de présentation', 'type' => 'textarea', 'rows' => 10, 'help' => 'Laisser une ligne vide entre deux paragraphes. **gras**, *italique*.'],
                'chute' => ['label' => 'Phrase de conclusion', 'type' => 'textarea', 'rows' => 2, 'help' => 'Affichée en italique rouge à la fin.'],
                'mots_neon' => ['label' => 'Mots en néon', 'type' => 'text', 'help' => 'Ex. : raku · terre · fumée'],
                'photos' => ['label' => 'Les pièces exposées', 'type' => 'list', 'add' => 'Ajouter une photo', 'fields' => [
                    'image' => ['label' => 'Photo', 'type' => 'image'],
                    'legende' => ['label' => 'Légende', 'type' => 'text'],
                ]],
                'lien' => ['label' => 'Site ou Instagram de l\'artiste', 'type' => 'url'],
                'publie' => ['label' => 'Publiée sur le site', 'type' => 'checkbox', 'default' => true],
            ],
        ],

        'repertoire' => [
            'label' => 'Répertoire des contacts',
            'singular' => 'un contact',
            'title' => 'nom',
            'list_url' => 'repertoire.php',
            'private' => true,
            'help' => 'Fiches privées : rien de ce répertoire n\'apparaît sur le site public.',
            'list' => ['nom' => 'Nom', 'discipline' => 'Discipline', 'email' => 'E-mail', 'groupes' => 'Groupes'],
            'sort' => 'nom',
            'fields' => [
                'nom' => ['label' => 'Prénom et nom (ou nom d\'artiste)', 'type' => 'text', 'required' => true],
                'artiste' => ['label' => 'C\'est un artiste', 'type' => 'checkbox', 'help' => 'La fiche apparaît alors dans « Artistes » (les artistes de la galerie). Les fiches venues des expositions et des candidatures le sont automatiquement.'],
                'email' => ['label' => 'E-mail', 'type' => 'email', 'col' => 'half'],
                'telephone' => ['label' => 'Téléphone', 'type' => 'tel', 'col' => 'half'],
                'discipline' => ['label' => 'Discipline', 'type' => 'select', 'options' => $disciplines, 'col' => 'half'],
                'ville' => ['label' => 'Ville', 'type' => 'text', 'col' => 'half'],
                'adresse' => ['label' => 'Adresse postale', 'type' => 'textarea', 'rows' => 2],
                'style' => ['label' => 'Style / univers', 'type' => 'text', 'col' => 'half', 'help' => 'Ex. : aquarelle, raku, gravure sur bois.'],
                'lien' => ['label' => 'Site ou Instagram', 'type' => 'url', 'col' => 'half'],
                'groupes' => ['label' => 'Groupes', 'type' => 'text', 'help' => 'Séparés par des virgules. Ex. : A exposé, Saison 2027, Atelier céramique. Servent à écrire à tout un groupe.'],
                'photo' => ['label' => 'Photo ou œuvre', 'type' => 'image'],
                'notes' => ['label' => 'Notes', 'type' => 'textarea', 'rows' => 5, 'help' => 'Échanges, disponibilités, idées d\'exposition… Si la fiche est partagée, ces notes sont aussi visibles dans le Répertoire de Copains d\'après.'],
                'partage' => ['label' => 'Synchroniser cette fiche avec le Répertoire de Copains d\'après', 'type' => 'checkbox', 'default' => true, 'help' => 'Nom, e-mail, téléphone et notes sont synchronisés dans les deux sens. Discipline, ville, groupes et photo restent propres à la galerie.'],
                'abonne' => ['label' => 'Accepte de recevoir les messages de la galerie', 'type' => 'checkbox', 'default' => true, 'help' => 'Décocher si la personne ne souhaite plus recevoir d\'envois groupés.'],
            ],
        ],

        'presse' => [
            'label' => 'Articles de presse',
            'singular' => 'un article',
            'title' => 'journal',
            'icon' => 'news',
            'list' => ['date' => 'Date', 'journal' => 'Journal', 'titre' => 'Titre'],
            'sort' => 'date',
            'fields' => [
                'journal' => ['label' => 'Journal / média', 'type' => 'text', 'required' => true],
                'date' => ['label' => 'Date de parution', 'type' => 'date', 'required' => true, 'col' => 'half'],
                'une' => ['label' => 'Mettre à la une', 'type' => 'checkbox', 'col' => 'half', 'help' => 'L\'article à la une est affiché en grand.'],
                'titre' => ['label' => 'Titre de l\'article', 'type' => 'text'],
                'citation' => ['label' => 'Citation extraite', 'type' => 'textarea', 'rows' => 3],
                'image' => ['label' => 'Scan ou photo de l\'article', 'type' => 'image'],
                'fichier' => ['label' => 'Article en PDF', 'type' => 'file'],
                'lien' => ['label' => 'Lien vers l\'article en ligne', 'type' => 'url'],
                'publie' => ['label' => 'Publié sur le site', 'type' => 'checkbox', 'default' => true],
            ],
        ],
    ],

    /* ============================ PAGES ET RÉGLAGES ============================ */
    'singletons' => [

        'reglages' => [
            'label' => 'Coordonnées et réglages',
            'group' => 'Réglages',
            'fields' => [
                'nom' => ['label' => 'Nom de la galerie (enseigne)', 'type' => 'text', 'default' => 'La Boucherie'],
                'cote_gauche' => ['label' => 'Mot à gauche de l\'enseigne', 'type' => 'text', 'default' => 'Galerie', 'col' => 'half'],
                'cote_droite' => ['label' => 'Mot à droite de l\'enseigne', 'type' => 'text', 'default' => 'Léré', 'col' => 'half'],
                'neon' => ['label' => 'Signature en néon (pied de page)', 'type' => 'text', 'default' => 'La boucherie'],
                'adresse' => ['label' => 'Adresse', 'type' => 'text', 'default' => '39 grande rue'],
                'code_postal' => ['label' => 'Code postal', 'type' => 'text', 'default' => '18240', 'col' => 'half'],
                'ville' => ['label' => 'Ville', 'type' => 'text', 'default' => 'Léré', 'col' => 'half'],
                'telephone' => ['label' => 'Téléphone de la galerie', 'type' => 'tel', 'default' => '06 73 98 32 61', 'col' => 'half'],
                'email' => ['label' => 'E-mail de contact (affiché)', 'type' => 'email', 'col' => 'half'],
                'email_expediteur' => ['label' => 'Adresse d\'expédition des messages aux artistes', 'type' => 'email', 'help' => 'Idéalement une adresse du nom de domaine du site (ex. contact@galerielaboucherie.com), sinon les messages risquent d\'arriver en indésirables. Les réponses arrivent à l\'e-mail de contact.'],
                'signature' => ['label' => 'Signature des messages', 'type' => 'textarea', 'rows' => 3, 'default' => "La galerie éphémère — La Boucherie\n39 grande rue, 18240 Léré\n06 73 98 32 61"],
                'email_notification' => ['label' => 'E-mail qui reçoit les candidatures', 'type' => 'email', 'help' => 'Un message est envoyé à chaque nouvelle candidature (si l\'hébergeur autorise l\'envoi de mails).'],
                'copains_url' => ['label' => 'Adresse du site Copains d\'après', 'type' => 'url', 'help' => 'Pour synchroniser le Répertoire des contacts avec celui de Copains d\'après (rubrique Répertoire). Ex. : https://www.copainsdapres.org/ — laisser vide pour ne pas synchroniser.'],
                'copains_cle' => ['label' => 'Clé de partage', 'type' => 'secret', 'help' => 'La même clé que $CLE_PARTAGE dans le fichier config.php de Copains d\'après. Laisser vide pour garder la clé actuelle.'],
                'latitude' => ['label' => 'Latitude (carte)', 'type' => 'text', 'default' => '47.46866', 'col' => 'half'],
                'longitude' => ['label' => 'Longitude (carte)', 'type' => 'text', 'default' => '2.86980', 'col' => 'half'],
                'instagram' => ['label' => 'Instagram (lien)', 'type' => 'url', 'col' => 'half'],
                'facebook' => ['label' => 'Facebook (lien)', 'type' => 'url', 'col' => 'half'],
                'tiktok' => ['label' => 'TikTok (lien)', 'type' => 'url', 'col' => 'half'],
                'youtube' => ['label' => 'YouTube (lien)', 'type' => 'url', 'col' => 'half'],
                'meta_description' => ['label' => 'Description pour Google', 'type' => 'textarea', 'rows' => 2, 'default' => 'La Boucherie, galerie d\'art éphémère à Léré (Cher), près de Cosne-sur-Loire et de Sancerre : un artiste par mois, vernissage à 18 h, entrée libre.', 'help' => 'Utilisée pour l\'accueil et quand une page n\'a pas la sienne. Idéal : 140 à 160 caractères.'],
                'mentions' => ['label' => 'Mentions légales', 'type' => 'textarea', 'rows' => 6, 'default' => "Éditeur du site : La galerie éphémère — La Boucherie, 39 grande rue, 18240 Léré.\n\nLes données envoyées par les formulaires (newsletter, candidatures) servent uniquement à la galerie et ne sont jamais transmises à des tiers. Pour les consulter ou les faire effacer, contactez la galerie."],
            ],
        ],

        'accueil' => [
            'label' => 'Accueil',
            'group' => 'Pages',
            'fields' => [
                'surtitre' => ['label' => 'Surtitre', 'type' => 'text', 'default' => 'Galerie d\'art · 39 grande rue · Léré'],
                'titre1' => ['label' => 'Titre, 1re ligne', 'type' => 'text', 'default' => 'Sous le store,'],
                'titre2' => ['label' => 'Titre, 2e ligne (italique rouge)', 'type' => 'text', 'default' => 'un artiste par mois.'],
                'intro' => ['label' => 'Introduction', 'type' => 'textarea', 'rows' => 3, 'default' => 'Là où l\'on vendait entrecôtes et paupiettes, on accroche désormais peintures, céramiques et gravures. Entrée libre, vernissage à 18 h.'],
                'photo' => ['label' => 'Image en haut de l\'accueil', 'type' => 'image', 'help' => 'Laisser vide (conseillé) pour afficher l\'illustration dessinée de la vitrine, avec l\'affiche de l\'expo en cours et les prochaines dates.'],
                'prochainement' => ['label' => 'Titre « Prochainement »', 'type' => 'text', 'default' => 'Prochainement'],
                'prochainement2' => ['label' => 'Suite du titre (italique)', 'type' => 'text', 'default' => 'sous le store'],
                'newsletter_titre' => ['label' => 'Newsletter : titre néon', 'type' => 'text', 'default' => 'le programme dans votre boîte'],
                'newsletter_texte' => ['label' => 'Newsletter : texte', 'type' => 'text', 'default' => 'Recevez les prochaines expositions et les dates de vernissage, une fois par mois. Désinscription en un clic.'],
                'galerie_photo' => ['label' => 'Photo du bloc « Qu\'est-ce que c\'est ? »', 'type' => 'image', 'default' => 'uploads/vitrine-vernissage.webp', 'help' => 'Une photo d\'au moins 800 pixels de large sera plus nette.'],
                'galerie_photo_alt' => ['label' => 'Description de cette photo (accessibilité)', 'type' => 'text', 'default' => 'La vitrine de La Boucherie un soir de vernissage, vue de la rue'],
                'galerie_titre' => ['label' => 'Titre du bloc « Qu\'est-ce que c\'est ? »', 'type' => 'text', 'default' => 'Mais qu\'est-ce que c\'est ?'],
                'galerie_texte' => ['label' => 'Texte du bloc « Qu\'est-ce que c\'est ? »', 'type' => 'textarea', 'rows' => 8, 'default' => "*Une galerie d'art, ouverte au moins un samedi par mois et sur rendez-vous, entrée libre et gratuite.*\n\nCette **galerie éphémère** permet aux artistes, grands et petits, locaux ou distants, de présenter leur travail à leur public et aux visiteurs.\n\n*L'artiste ne paye rien. Le visiteur non plus !*\n\nL'artiste réalise son accrochage et participe à son vernissage. Il peut, s'il le souhaite, organiser des **journées ouvertes** ou des **ateliers**."],
            ],
        ],

        'page_expo' => [
            'label' => 'L\'expo du mois',
            'group' => 'Pages',
            'help' => 'Le contenu vient de l\'exposition en cours (menu Expositions). Ici, seulement les textes fixes.',
            'fields' => [
                'surtitre' => ['label' => 'Surtitre (avant les dates)', 'type' => 'text', 'default' => 'En vitrine'],
                'pieces_titre' => ['label' => 'Titre de la galerie photos', 'type' => 'text', 'default' => 'Les pièces exposées'],
                'infos_titre' => ['label' => 'Titre des infos pratiques', 'type' => 'text', 'default' => 'Infos pratiques'],
                'aucune' => ['label' => 'Message s\'il n\'y a aucune exposition', 'type' => 'textarea', 'rows' => 2, 'default' => 'La prochaine exposition se prépare. Revenez très vite, ou consultez le programme.'],
            ],
        ],

        'programme' => [
            'label' => 'Programme',
            'group' => 'Pages',
            'fields' => [
                'surtitre' => ['label' => 'Surtitre', 'type' => 'text', 'default' => 'Un artiste par mois · vernissage à 18 h'],
                'titre1' => ['label' => 'Titre, 1re ligne', 'type' => 'text', 'default' => 'Prochainement'],
                'titre2' => ['label' => 'Titre, 2e ligne (italique rouge)', 'type' => 'text', 'default' => 'sous le store'],
                'vide' => ['label' => 'Message si aucune date à venir', 'type' => 'textarea', 'rows' => 2, 'default' => 'Le programme des prochains mois est en préparation.'],
                'newsletter_titre' => ['label' => 'Newsletter : titre néon', 'type' => 'text', 'default' => 'ne ratez aucun vernissage'],
                'newsletter_texte' => ['label' => 'Newsletter : texte', 'type' => 'text', 'default' => 'Le programme du mois, une fois par mois, par e-mail.'],
            ],
        ],

        'artistes' => [
            'label' => 'Artistes',
            'group' => 'Pages',
            'help' => 'La liste des artistes est construite automatiquement à partir des expositions.',
            'fields' => [
                'surtitre' => ['label' => 'Surtitre', 'type' => 'text', 'default' => 'Grands et petits, locaux ou distants'],
                'titre' => ['label' => 'Titre', 'type' => 'text', 'default' => 'Les artistes'],
                'intro' => ['label' => 'Introduction', 'type' => 'textarea', 'rows' => 3, 'default' => 'Léré et ses environs regorgent d\'incroyables talents : artistes d\'un jour, amateurs éclairés, et certains déjà reconnus, voire professionnels. Tous sont passés, ou passeront, sous le store.'],
            ],
        ],

        'exposer' => [
            'label' => 'Exposer chez nous',
            'group' => 'Pages',
            'fields' => [
                'surtitre' => ['label' => 'Surtitre', 'type' => 'text', 'default' => 'Appel permanent aux artistes'],
                'titre1' => ['label' => 'Titre, 1re ligne', 'type' => 'text', 'default' => 'Il reste de la place'],
                'titre2' => ['label' => 'Titre, 2e ligne (italique rouge)', 'type' => 'text', 'default' => 'sous le store.'],
                'intro' => ['label' => 'Introduction', 'type' => 'textarea', 'rows' => 3, 'default' => 'On est tous des artistes ! Mais comment rencontrer son public, exposer ses œuvres, échanger ? Cette galerie est faite pour vous : artistes grands et petits, locaux ou distants.'],
                'etapes_titre' => ['label' => 'Titre des étapes', 'type' => 'text', 'default' => 'Comment ça se passe'],
                'etapes' => ['label' => 'Étapes', 'type' => 'list', 'add' => 'Ajouter une étape', 'default' => [
                    ['titre' => 'Vous candidatez', 'texte' => 'Quelques mots, quelques photos et le mois qui vous tente. Réponse sous une semaine.'],
                    ['titre' => 'Vous accrochez', 'texte' => 'L\'artiste réalise lui-même son accrochage.'],
                    ['titre' => 'On vernit', 'texte' => 'Vous participez à votre vernissage, un samedi à 18 h.'],
                    ['titre' => 'Et si vous le souhaitez', 'texte' => 'Vous organisez des journées ouvertes ou des ateliers pendant votre mois.'],
                ], 'fields' => [
                    'titre' => ['label' => 'Titre', 'type' => 'text'],
                    'texte' => ['label' => 'Texte', 'type' => 'textarea', 'rows' => 2],
                ]],
                'montant' => ['label' => 'Participation demandée', 'type' => 'text', 'default' => '100 €', 'col' => 'half'],
                'montant_titre' => ['label' => 'Intitulé de la participation', 'type' => 'text', 'default' => 'Seule participation demandée', 'col' => 'half'],
                'montant_texte' => ['label' => 'Détail de la participation', 'type' => 'text', 'default' => 'Frais du vernissage pour environ 50 personnes : vins, softs, canapés…'],
                'encart1' => ['label' => 'Encadré, 1re ligne', 'type' => 'text', 'default' => 'Pas de location de salle.', 'col' => 'half'],
                'encart2' => ['label' => 'Encadré, 2e ligne (italique)', 'type' => 'text', 'default' => 'Le visiteur ne paye rien.', 'col' => 'half'],
                'appel' => ['label' => 'Formulaire : titre néon', 'type' => 'text', 'default' => 'votre nom sous le store ?'],
                'appel_texte' => ['label' => 'Formulaire : texte', 'type' => 'text', 'default' => 'Réponse sous une semaine. Vous préférez en parler ? Appelez-nous.'],
                'merci' => ['label' => 'Message après envoi', 'type' => 'textarea', 'rows' => 2, 'default' => 'Merci ! Votre candidature est bien arrivée sous le store. Nous vous répondons sous une semaine.'],
            ],
        ],

        'lieu' => [
            'label' => 'Le lieu',
            'group' => 'Pages',
            'fields' => [
                'surtitre' => ['label' => 'Surtitre', 'type' => 'text', 'default' => '39 grande rue · Léré · galerie depuis janvier 2024'],
                'titre1' => ['label' => 'Titre, 1re ligne', 'type' => 'text', 'default' => 'Une boucherie'],
                'titre2' => ['label' => 'Titre, 2e ligne (italique rouge)', 'type' => 'text', 'default' => 'devenue galerie'],
                'texte' => ['label' => 'Histoire du lieu', 'type' => 'textarea', 'rows' => 8, 'default' => "Les anciens de Léré ont connu plusieurs générations de bouchers qui tenaient boutique au 39 grande rue, sous l'enseigne « Ets Fumey ».\n\nOn se régalait alors d'aller chercher une entrecôte amoureusement préparée, une bavette à se damner, des paupiettes préparées aux petits oignons.\n\nDepuis janvier 2024, le store rayé abrite une galerie d'art. On y vient par plaisir, par curiosité. On y croise des visages connus, ou pas. On y parle de tout, de rien."],
                'frise' => ['label' => 'Frise chronologique', 'type' => 'list', 'add' => 'Ajouter une étape', 'default' => [
                    ['quand' => 'hier', 'titre' => 'Ets Fumey, boucherie-charcuterie', 'texte' => 'Plusieurs générations de bouchers au 39 grande rue.'],
                    ['quand' => 'janvier 2024', 'titre' => 'La galerie ouvre', 'texte' => 'La boutique devient une galerie d\'art ouverte à tous les artistes.'],
                    ['quand' => 'aujourd\'hui', 'titre' => 'Un artiste par mois', 'texte' => 'Un samedi de vernissage, des permanences, des ateliers.'],
                ], 'fields' => [
                    'quand' => ['label' => 'Quand (en néon)', 'type' => 'text'],
                    'titre' => ['label' => 'Titre', 'type' => 'text'],
                    'texte' => ['label' => 'Texte', 'type' => 'text'],
                ]],
                'photos' => ['label' => 'Photos du lieu', 'type' => 'list', 'add' => 'Ajouter une photo', 'default' => [
                    ['image' => 'uploads/enseigne-fumey-nuit.webp', 'legende' => 'L\'enseigne d\'origine, « Ets Fumey », un soir d\'hiver', 'alt' => 'La vitrine de nuit sous l\'enseigne Boucherie Ets Fumey Charcuterie'],
                    ['image' => 'uploads/vitrine-vernissage.webp', 'legende' => 'Un soir de vernissage, vu de la rue', 'alt' => 'Six vues de la vitrine un soir de vernissage'],
                ], 'fields' => [
                    'image' => ['label' => 'Photo', 'type' => 'image'],
                    'legende' => ['label' => 'Légende', 'type' => 'text'],
                    'alt' => ['label' => 'Description (accessibilité)', 'type' => 'text'],
                ]],
                'venir_titre' => ['label' => 'Titre « Venir »', 'type' => 'text', 'default' => 'Venir à La Boucherie'],
                'venir_texte' => ['label' => 'Horaires et accès', 'type' => 'textarea', 'rows' => 4, 'default' => "Ouvert au moins un samedi par mois, toute la journée\nVernissage à 18 h · entrée libre et gratuite\nEt sur simple demande par téléphone"],
            ],
        ],

        'page_presse' => [
            'label' => 'Presse',
            'group' => 'Pages',
            'help' => 'Les articles se gèrent dans le menu « Articles de presse ».',
            'fields' => [
                'surtitre' => ['label' => 'Surtitre', 'type' => 'text', 'default' => 'Revue de presse'],
                'titre1' => ['label' => 'Titre', 'type' => 'text', 'default' => 'La presse'],
                'titre2' => ['label' => 'Titre, suite (italique rouge)', 'type' => 'text', 'default' => 'en parle'],
                'journaliste' => ['label' => 'Encadré : titre néon', 'type' => 'text', 'default' => 'journaliste ?'],
                'journaliste_texte' => ['label' => 'Encadré : texte', 'type' => 'textarea', 'rows' => 2, 'default' => 'Visuels en haute définition, programme de la saison et contact direct pour vos sujets.'],
                'dossier' => ['label' => 'Dossier de presse (PDF)', 'type' => 'file'],
            ],
        ],
    ],
];

/* ============================ RÉFÉRENCEMENT (Google) ============================
 * Chaque page a son titre et sa description pour les moteurs de recherche, modifiables
 * dans le back-office. Le titre s'affiche dans l'onglet et en bleu dans Google ;
 * la description est le petit texte en dessous.
 */
$seo = [
    'accueil' => ['La Boucherie — galerie d\'art éphémère à Léré (Cher)', ''],
    'programme' => ['Programme des expositions et vernissages — La Boucherie, Léré', 'Les prochaines expositions de la galerie La Boucherie à Léré (Cher) : un artiste par mois, vernissage le samedi à 18 h, entrée libre et gratuite.'],
    'artistes' => ['Les artistes exposés — La Boucherie, galerie d\'art à Léré', 'Peintres, céramistes, graveurs, photographes, sculpteurs : tous les artistes exposés ou attendus à la galerie La Boucherie, à Léré dans le Cher.'],
    'exposer' => ['Exposer à Léré : appel aux artistes — galerie La Boucherie', 'Artistes, exposez à La Boucherie, galerie éphémère de Léré (Cher), près de Cosne et Sancerre : un mois d\'exposition, sans location de salle. Candidature en ligne.'],
    'lieu' => ['Le lieu, son histoire et l\'accès — La Boucherie, galerie à Léré', 'Une ancienne boucherie devenue galerie d\'art, au 39 grande rue à Léré (Cher) : son histoire, les jours d\'ouverture et le plan d\'accès.'],
    'page_presse' => ['La presse en parle — La Boucherie, galerie d\'art à Léré', 'Articles et revue de presse de la galerie éphémère La Boucherie à Léré (Cher), et contact pour les journalistes.'],
];
foreach ($seo as $page => [$titre, $description]) {
    $schema['singletons'][$page]['fields']['seo_titre'] = ['label' => 'Référencement : titre de la page dans Google', 'type' => 'text', 'default' => $titre,
        'help' => 'Affiché dans l\'onglet du navigateur et en bleu dans Google. Idéal : 50 à 60 caractères, avec « Léré » dedans.'];
    $schema['singletons'][$page]['fields']['seo_description'] = ['label' => 'Référencement : description dans Google', 'type' => 'textarea', 'rows' => 2, 'default' => $description,
        'help' => 'Le petit texte sous le titre dans Google. Idéal : 140 à 160 caractères.' . ($page === 'accueil' ? ' Vide = la description générale des réglages.' : '')];
}

return $schema;
