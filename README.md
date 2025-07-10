e-social/
│
├── config/
│   ├── db.php                   # Connexion à la base de données
│   └── constantes.php           # Constantes globales (monnaie, chemins, etc.)
│
├── includes/
│   ├── header.php               # Header général (Bootstrap + menu)
│   ├── footer.php               # Footer
│   ├── navbar.php               # Barre de navigation publique
│   ├── sidebar_admin.php        # Menu latéral de l'admin
├   ├── header_admin.php         # Menu navig de l'admin
│   └── fonctions.php            # Fonctions PHP réutilisables
│
├── assets/
│   ├── css/
│   │   └── style.css            # Styles personnalisés
│   ├── js/
│   │   └── scripts.js           # JavaScript personnalisé
│   └── images/
│       ├── logos/               # Logos du site et partenaires
│       └── campagnes/           # Images des campagnes
│
├── uploads/
│   ├── identites/               # Pièces d'identité
│   ├── preuves_dons/            # Preuves de dons
│   └── transferts_admin/        # Justificatifs de transferts
│
├── public/                      # Pages accessibles aux visiteurs
│   ├── index.php                # Page d'accueil
│   ├── campagnes.php            # Liste des campagnes
│   ├── campagne.php?id=         # Détail d'une campagne
│   ├── contact.php              # Formulaire de contact
│   ├── apropos.php              # Page À propos
│   ├── connexion.php            # conexion
    ├── dons.php                 # Faire un don de l'argent
│   └── inscription.php          # Formulaire d'inscription
│
├── utilisateur/                 # Espace des donateurs
│   ├── dashboard.php            # Tableau de bord
│   ├── profil.php               # Gestion du profil
│   ├── mes_dons.php             # Historique des dons
│   ├── notification.php         # Notifications
│   └── deconnexion.php          # Déconnexion
│
├── admin/                       # Interface administrateur
│   ├── index.php                # Tableau de bord
│   ├── campagnes.php            # Gestion des campagnes
│   ├── ajouter_campagne.php     # Ajouter une campagne
│   ├── modifier_campagne.php    # Modifier une campagne
│   ├── beneficiaires.php        # Liste des bénéficiaires
│   ├── dons.php                 # Liste des dons
│   ├── notifications.php        #  un CRUD (Créer, Lire, Mettre à jour, Supprimer)
│   ├── transferts.php           # Preuves de transferts
│   ├── utilisateurs.php         # Liste des utilisateurs
│   ├── messages.php             # Messages de contact
│   ├── partenaires.php          # Gestion des partenaires
│   ├── categories.php           # Gestion des catégories
│   ├── newsletter.php           # Liste des abonnés
│   └── deconnexion.php          # Déconnexion admin
│
├── traitement/                  # Traitement des formulaires
│   ├── index.php(dashboard)     # contenire un barre lateral pour tout les fichiers de traitment
│   ├── login.php                # Connexion
│   ├── register.php             # Inscription
│   ├── don.php                  # Envoi d'un don
│   ├── campagne_add.php         # Ajouter une campagne
│   ├── campagne_update.php      # Modifier campagne
│   ├── campagne_delete.php      # Supprimer campagne
│   ├── beneficiaire_add.php     # Ajouter un bénéficiaire
│   ├── preuve_upload.php        # Envoi des preuves
│   ├── transfert_upload.php     # Justificatifs de virement
│   ├── contact_message.php      # Formulaire de contact
│   └── newsletter_subscribe.php # Abonnement newsletter
│
├── pdf/
│   ├── generate_recu.php              # Génère un reçu PDF
│   ├── generate_rapport_dons.php      # Rapport de dons
│   ├── generate_beneficiaire.php      # Fiche bénéficiaire
│   └── tcpdf/                         # Bibliothèque TCPDF
│
├── .htaccess                   # Réécriture d’URL
└── README.md                   # Documentation du projet


Voici une liste complète, détaillée et exhaustive de toutes les fonctionnalités nécessaires à développer pour ton projet web E-social : Conception et développement d'une application pour la collecte de fonds pour les personnes nécessiteuses, en prenant en compte tes contraintes techniques :

Backend : PHP brut (sans framework) + MySQL

Frontend : HTML, CSS, Bootstrap

Cible principale : Sénégal, mais ouverture à l'international

1. Page d’accueil (accueil.php)
Présentation du projet (Objectifs, mission, impact)

Statistiques en temps réel (total des dons collectés, nombre de bénéficiaires, etc.)

Témoignages de bénéficiaires

Bouton « Faire un don »

Bouton « Devenir bénévole » ou partenaire

Accès rapide à la liste des campagnes

2. Système d’authentification
a. Utilisateur (Donateurs / Visiteurs)
Inscription (nom, prénom, email, téléphone, mot de passe, pays)

Connexion / Déconnexion

Mot de passe oublié (email de récupération ou message personnalisé)

b. Administrateur
Interface de connexion admin

Gestion sécurisée des accès (niveau admin)

3. Profils utilisateurs
Tableau de bord personnalisé

Historique des dons

Modifier le profil (email, mot de passe, téléphone, etc.)

Statistiques personnelles : nombre de dons, montants donnés, campagnes soutenues

4. Gestion des campagnes de collecte
Frontend (public) :
Liste des campagnes en cours / terminées

Filtres (type de campagne, zone géographique, urgence, etc.)

Voir les détails d'une campagne :

Description détaillée

Montant visé / montant atteint (barre de progression)

Nombre de donateurs

Photos ou vidéos

Témoignage du bénéficiaire

Bouton "Faire un don"

Backend (admin) :
Ajouter une nouvelle campagne

Modifier / Supprimer une campagne

Statut (en cours / terminée / suspendue)

Lier une campagne à un bénéficiaire

5. Gestion des dons
Frontend (donateur) :
Formulaire de don (nom, email, montant, moyen de paiement)

Choix de campagne ou don libre

Moyens de paiement :

Paiement hors ligne (Orange Money, Wave, Free Money, dépôt bancaire — instructions fournies)

Paiement en ligne (optionnel à ajouter plus tard avec API si souhaité)

Génération de reçu PDF (simple, avec nom, montant, date, etc.)

Backend (admin) :
Tableau de tous les dons

Filtres : campagne, date, donateur, moyen de paiement

Validation des paiements hors ligne (si confirmation manuelle nécessaire)

Téléchargement ou export CSV des dons

6. Gestion des bénéficiaires
Ajouter un bénéficiaire (nom, contact, situation, justificatif)

Associer un bénéficiaire à une campagne

Historique des aides reçues

Statut (en attente / validé / aidé)

Option de masquer les informations sensibles en public

7. Système de vérification et transparence
Page de transparence des fonds :

Campagnes réussies

Montants transférés

Justificatifs (factures, photos, etc.)

Téléversement de documents (PDF, images, etc.) pour preuve de bonne gestion

8. Système de contact et support
Formulaire de contact

Adresse email / numéro WhatsApp

FAQ (Foire aux questions)

Chat en direct (optionnel ou via widget externe)

9. Newsletter / Notification
Inscription à la newsletter

Envoi automatique après un don (merci + reçu)

Notification lors de nouvelles campagnes ou objectifs atteints

10. Gestion des partenaires / sponsors
Ajout de logos et descriptions

Partenaires visibles sur la page d’accueil

Formulaire pour devenir partenaire

11. Interface d’administration (admin_panel.php)
Tableau de bord global (statistiques, utilisateurs, dons, campagnes)

Gestion des utilisateurs (bloquer/supprimer un compte)

Gestion des campagnes

Gestion des bénéficiaires

Gestion des dons

Gestion des documents justificatifs

Historique des actions

12. Design responsive avec Bootstrap
S’adapte aux écrans de téléphones, tablettes, ordinateurs

Navigation claire et fluide

Utilisation de composants modernes : carrousels, cartes, badges, alertes, modals

13. Sécurité
Protection contre l’injection SQL (requêtes préparées avec mysqli ou PDO)

Validation des formulaires côté client (JavaScript) et côté serveur (PHP)

Hachage des mots de passe (password_hash() et password_verify())

Limitation du spam (captcha simple ou honeypot)

Restriction d'accès à l'admin via session

14. Multilingue (optionnel)
Français (par défaut), possibilité d’ajouter l’anglais / wolof plus tard

15. Statistiques générales
Nombre total de dons

Montant total collecté

Nombre de bénéficiaires aidés

Nombre de campagnes actives / réussies

16. Pages supplémentaires
À propos de nous

Nos valeurs / mission

Nos résultats

Témoignages des donateurs et bénéficiaires

Mentions légales / Politique de confidentialité

