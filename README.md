# La Boucherie — galerie éphémère de Léré

Site de la galerie d'art **La Boucherie** (39 grande rue, 18240 Léré) et son **espace galerie** (back-office).
Design « Bleu nuit » (store rayé, néon, chevrons), repris des maquettes de l'artefact
*La Galerie Éphémère — analyse du design* (sources dans `maquettes/`).

## Ce que contient le site

| Page | Fichier | Contenu |
|---|---|---|
| Accueil | `index.php` | Photo de la devanture, expo en cours, prochaines dates, présentation |
| L'expo du mois | `expo.php` | L'exposition en cours (ou `expo.php?slug=…` pour n'importe laquelle) |
| Programme | `programme.php` | Les vernissages à venir + inscription à la newsletter |
| Artistes | `artistes.php` | Tous les artistes, filtrables par année et discipline |
| Exposer | `exposer.php` | Fonctionnement + formulaire de candidature avec photos |
| Le lieu | `lieu.php` | Histoire, frise, photos, carte, itinéraire |
| Presse | `presse.php` | Article à la une, autres articles, dossier de presse |
| Agenda | `agenda.php` | Fichier calendrier (.ics) d'un vernissage ou de tout le programme |

L'accueil, « L'expo du mois », le programme et la page Artistes **se mettent à jour tout seuls selon les dates** saisies.

## L'espace galerie (`/admin`)

À la première visite de `/admin`, on crée le compte principal. Ensuite tout se gère sans toucher au code :

- **Expositions** : artiste, dates, affiche, textes, photos des pièces, brouillon/publié, duplication
- **Articles de presse** : scan, PDF, lien, citation, « à la une »
- **Pages du site** : tous les textes de chaque page (titres, paragraphes, étapes, frise, photos…)
- **Candidatures** : lecture, photos jointes (privées), statut, note interne, création de l'exposition en un clic
- **Répertoire des artistes** : fiches privées (contact, discipline, groupes, notes), import automatique depuis les expositions et candidatures, export CSV
- **Répertoire partagé avec Copains d'après** : les fiches se synchronisent dans les deux sens avec le Répertoire de Copains d'après (fiches « Galerie éphémère »), via `partage.php` de leur site et une clé de partage commune. Nom, e-mail, téléphone et notes sont partagés ; discipline, groupes, photo restent propres à la galerie. Mise en route : *Coordonnées et réglages* → adresse du site Copains + clé (identique à `$CLE_PARTAGE` de leur config.php).
- **Écrire aux artistes** : un e-mail personnalisé (`{prenom}`) à une personne, à une sélection ou à tout un groupe ; historique des envois ; bouton « programme des prochaines expos » ; option « ouvrir dans ma messagerie »
- **Newsletter** : inscription depuis l'accueil et le programme, envoi du programme aux inscrits avec lien de désinscription, export CSV
- **Coordonnées et réglages** : adresse, téléphone, e-mails, réseaux sociaux, carte, mentions légales
- **Comptes d'accès** : plusieurs personnes, changement de mot de passe
- **Sauvegarde** : archive .zip de tous les contenus et images

## Installation chez un hébergeur

Il faut **PHP 8.1 ou plus** (extensions `fileinfo`, `mbstring` ; `zip` pour la sauvegarde). Aucune base de données.

1. Copier tous les fichiers par FTP à la racine du site (`www/` chez OVH, `public_html/` chez o2switch).
2. Vérifier que les dossiers `data/` et `uploads/` sont accessibles en écriture (droits 755 ou 775).
3. Ouvrir `https://votre-domaine.fr/admin/` et créer le compte de la galerie.
4. Dans *Coordonnées et réglages*, renseigner l'e-mail qui reçoit les candidatures et l'**adresse d'expédition** des messages
   (une adresse du nom de domaine du site, créée chez l'hébergeur, pour éviter les indésirables).
   Les envois utilisent la fonction mail de l'hébergeur : prévoir un outil dédié (Brevo…) au-delà de quelques centaines d'envois par mois.

Les contenus saisis sont stockés dans `data/` (fichiers JSON) et les images dans `uploads/`.
**Lors d'une mise à jour du code, ne pas écraser ces deux dossiers.**

Sécurité : `data/`, `inc/` et `maquettes/` sont interdits d'accès par les fichiers `.htaccess` (Apache).
Sur un serveur Nginx, ajouter l'équivalent : `location ~ ^/(data|inc|maquettes)/ { deny all; }`.

## Tester sur son ordinateur

```bash
php -S localhost:8000 router.php
```

Puis ouvrir http://localhost:8000 (site) et http://localhost:8000/admin/ (espace galerie).

## Organisation du code

```
index.php, expo.php…   pages publiques
candidature.php        réception du formulaire « Exposer »
newsletter.php         réception de l'inscription newsletter
inc/bootstrap.php      stockage JSON, dates en français, sécurité
inc/schema.php         description de TOUT ce qui se gère dans l'admin (champs, textes par défaut)
inc/layout.php         en-tête, pied de page, devanture dessinée
admin/copains.php      synchronisation du répertoire avec Copains d'après
inc/seed/              contenu de départ (utilisé tant que la galerie n'a rien enregistré)
admin/                 espace galerie
assets/                CSS et JavaScript
```

Pour ajouter un champ éditable, il suffit de le déclarer dans `inc/schema.php` puis de l'afficher dans la page concernée.
