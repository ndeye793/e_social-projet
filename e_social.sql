-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 30 juin 2025 à 17:48
-- Version du serveur : 9.1.0
-- Version de PHP : 8.1.31

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `e_social`
--

-- --------------------------------------------------------

--
-- Structure de la table `abonnements_newsletter`
--

DROP TABLE IF EXISTS `abonnements_newsletter`;
CREATE TABLE IF NOT EXISTS `abonnements_newsletter` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_abonnement` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `abonnements_newsletter`
--

INSERT INTO `abonnements_newsletter` (`id`, `email`, `date_abonnement`) VALUES
(1, 'sarrn8800@gmail.com', '2025-05-31 22:31:39'),
(2, 'birame01@gmail.com', '2025-06-30 16:26:15'),
(3, 'sette.mbaye@unchk.edu.sn', '2025-06-30 16:27:01'),
(4, 'ndeyefatou.sarr12@unchk.edu.sn', '2025-06-30 16:28:03');

-- --------------------------------------------------------

--
-- Structure de la table `beneficiaires`
--

DROP TABLE IF EXISTS `beneficiaires`;
CREATE TABLE IF NOT EXISTS `beneficiaires` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prenom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `adresse` text COLLATE utf8mb4_general_ci,
  `situation` text COLLATE utf8mb4_general_ci,
  `justificatif` text COLLATE utf8mb4_general_ci,
  `identite_recto` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `identite_verso` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_enregistrement` datetime DEFAULT CURRENT_TIMESTAMP,
  `statut` enum('en attente','validé','aidé') COLLATE utf8mb4_general_ci DEFAULT 'en attente',
  `email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `beneficiaires`
--

INSERT INTO `beneficiaires` (`id`, `prenom`, `nom`, `telephone`, `adresse`, `situation`, `justificatif`, `identite_recto`, `identite_verso`, `date_enregistrement`, `statut`, `email`) VALUES
(2, 'Ibrahima', 'Faye', '773588475', 'Parcelle Assainies Unité 14', '????✨ Message de Tabaski 2025 ✨????\r\n\r\nEn cette fête bénie de l’Aïd el-Kébir, je te souhaite ainsi qu’à ta famille une Tabaski 2025 remplie de paix, de santé, de bonheur et de prospérité.\r\nQue ce moment de partage et de foi renforce nos liens, purifie nos cœurs et comble nos foyers de bénédictions divines.', NULL, '', '', '2025-05-31 22:49:46', 'aidé', 'ibsibzo97@gmail.com'),
(5, 'Ibrahima', 'Faye', '773588475', 'Cité soprim', '????✨ Message de Tabaski 2025 ✨????\r\n\r\nEn cette fête bénie de l’Aïd el-Kébir, je te souhaite ainsi qu’à ta famille une Tabaski 2025 remplie de paix, de santé, de bonheur et de prospérité.\r\nQue ce moment de partage et de foi renforce nos liens, purifie nos cœurs et comble nos foyers de bénédictions divines.', NULL, '', '', '2025-05-31 22:57:56', 'aidé', 'ibsibzo97@gmail.com');

-- --------------------------------------------------------

--
-- Structure de la table `campagnes`
--

DROP TABLE IF EXISTS `campagnes`;
CREATE TABLE IF NOT EXISTS `campagnes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci NOT NULL,
  `montant_vise` decimal(12,2) NOT NULL,
  `montant_atteint` decimal(12,2) DEFAULT '0.00',
  `date_debut` date DEFAULT NULL,
  `date_fin` date DEFAULT NULL,
  `statut` enum('en cours','terminée','suspendue') COLLATE utf8mb4_general_ci DEFAULT 'en cours',
  `categorie_id` int DEFAULT NULL,
  `beneficiaire_id` int DEFAULT NULL,
  `image_campagne` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `categorie_id` (`categorie_id`),
  KEY `beneficiaire_id` (`beneficiaire_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `campagnes`
--

INSERT INTO `campagnes` (`id`, `titre`, `description`, `montant_vise`, `montant_atteint`, `date_debut`, `date_fin`, `statut`, `categorie_id`, `beneficiaire_id`, `image_campagne`, `date_creation`) VALUES
(1, 'Des cartables pour les enfants de Kédougou', 'Aidez-nous à fournir des fournitures scolaires complètes à 200 enfants défavorisés de la région de Kédougou pour la prochaine rentrée scolaire.', 1500000.00, 751000.00, NULL, NULL, 'en cours', 1, NULL, 'campagne_683b9248a14119.83367104.jpg', '2025-05-28 00:49:48'),
(2, 'Un puits pour le village de Ndiagne', 'Construisons un puits d&amp;amp;#039;eau potable pour améliorer la santé et les conditions de vie des habitants du village de Ndiagne, luttant contre les maladies hydriques.', 3000000.00, 320000.00, '2024-06-15', '2025-10-31', 'en cours', 2, NULL, 'campagne_683b925f0aac61.80722635.jpg', '2025-05-28 00:49:48'),
(4, 'aide pour faire une formation', 'j&#039;ai de l&#039;argent pour faire une formation professionnele de deux ans', 1500000.00, 200000.00, '2025-06-01', '2025-07-30', 'en cours', 2, NULL, 'campagne_683b90667ec1e8.67221828.jpg', '2025-05-31 23:27:34');

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_categorie` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `nom_categorie`, `description`) VALUES
(1, 'Santé', 'Aide aux personnes malades ou hospitalisées'),
(2, 'Éducation', 'Aide pour frais de scolarité, fournitures'),
(3, 'Catastrophes', 'Soutien suite à incendies, inondations, etc.'),
(4, 'Alimentation des Personnes', 'Aide alimentaire pour familles en détresse');

-- --------------------------------------------------------

--
-- Structure de la table `dons`
--

DROP TABLE IF EXISTS `dons`;
CREATE TABLE IF NOT EXISTS `dons` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int DEFAULT NULL,
  `campagne_id` int DEFAULT NULL,
  `montant` decimal(12,2) NOT NULL,
  `date_don` datetime DEFAULT CURRENT_TIMESTAMP,
  `moyen_paiement_id` int DEFAULT NULL,
  `statut` enum('en attente','confirmé','rejeté') COLLATE utf8mb4_general_ci DEFAULT 'en attente',
  `preuve_paiement` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `campagne_id` (`campagne_id`),
  KEY `moyen_paiement_id` (`moyen_paiement_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `dons`
--

INSERT INTO `dons` (`id`, `utilisateur_id`, `campagne_id`, `montant`, `date_don`, `moyen_paiement_id`, `statut`, `preuve_paiement`) VALUES
(1, NULL, NULL, 2000.00, '2025-05-28 00:55:18', 2, 'en attente', 'preuve_68365ef6ce427.png'),
(2, NULL, 2, 1000000.00, '2025-05-28 18:42:45', 1, 'en attente', 'preuve_68375925eb472.pdf'),
(3, NULL, 2, 1000000.00, '2025-05-28 18:43:03', 1, 'en attente', 'preuve_68375937b344d.pdf'),
(4, NULL, 1, 1000.00, '2025-05-28 23:32:08', 3, 'en attente', 'preuve_68379cf8e4540.pdf'),
(5, NULL, 4, 200000.00, '2025-06-01 15:57:54', 2, 'en attente', 'preuve_683c7882120d3.jpg');

-- --------------------------------------------------------

--
-- Structure de la table `logs_admin`
--

DROP TABLE IF EXISTS `logs_admin`;
CREATE TABLE IF NOT EXISTS `logs_admin` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int DEFAULT NULL,
  `action` text COLLATE utf8mb4_general_ci,
  `date_action` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `logs_admin`
--

INSERT INTO `logs_admin` (`id`, `admin_id`, `action`, `date_action`) VALUES
(1, 2, 'Modification statut du don #1 en \'en attente\'', '2025-05-29 21:45:05');

-- --------------------------------------------------------

--
-- Structure de la table `messages_contact`
--

DROP TABLE IF EXISTS `messages_contact`;
CREATE TABLE IF NOT EXISTS `messages_contact` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `sujet` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_general_ci,
  `date_reception` datetime DEFAULT CURRENT_TIMESTAMP,
  `est_lu` tinyint(1) DEFAULT '0',
  `date_lecture` datetime DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `messages_contact`
--

INSERT INTO `messages_contact` (`id`, `nom`, `email`, `sujet`, `message`, `date_reception`, `est_lu`, `date_lecture`, `ip_address`, `user_agent`) VALUES
(2, 'Faye', 'ibsibzo97@gmail.com', 'TABASKI 2025', '????✨ Message de Tabaski 2025 ✨????\r\n\r\nEn cette fête bénie de l’Aïd el-Kébir, je te souhaite ainsi qu’à ta famille une Tabaski 2025 remplie de paix, de santé, de bonheur et de prospérité.\r\nQue ce moment de partage et de foi renforce nos liens, purifie nos cœurs et comble nos foyers de bénédictions divines.\r\n\r\nBonne fête de Tabaski !\r\nYalla na Yàlla may ñu barke ak jàmm !', '2025-05-30 20:31:43', 1, '2025-05-31 22:10:34', NULL, NULL),
(3, 'Faye', 'ibsibzo97@gmail.com', 'TABASKI 2025', '????✨ Message de Tabaski 2025 ✨????\r\n\r\nEn cette fête bénie de l’Aïd el-Kébir, je te souhaite ainsi qu’à ta famille une Tabaski 2025 remplie de paix, de santé, de bonheur et de prospérité.\r\nQue ce moment de partage et de foi renforce nos liens, purifie nos cœurs et comble nos foyers de bénédictions divines.\r\n\r\nBonne fête de Tabaski !\r\nYalla na Yàlla may ñu barke ak jàmm !', '2025-05-30 20:32:24', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `moyens_paiement`
--

DROP TABLE IF EXISTS `moyens_paiement`;
CREATE TABLE IF NOT EXISTS `moyens_paiement` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_moyen` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `details` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `moyens_paiement`
--

INSERT INTO `moyens_paiement` (`id`, `nom_moyen`, `details`) VALUES
(1, 'Orange Money', 'Numéro : 77 358 84 75'),
(2, 'Wave', 'Numéro : 70 336 29 64'),
(3, 'Free Money', 'Numéro : 78 000 00 00'),
(4, 'Virement bancaire', 'Compte Ecobank - N° SN123456789');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `lu` tinyint(1) DEFAULT '0',
  `date_notification` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `utilisateur_id`, `message`, `lu`, `date_notification`, `date_creation`) VALUES
(2, 1, 'Cher utilisateur 1, bienvenue dans votre espace de notifications.', 1, '2025-05-29 20:41:27', '2025-05-29 20:41:27'),
(3, NULL, 'Notification générale visible par tous les utilisateurs.', 0, '2025-05-29 20:41:27', '2025-05-29 20:41:27'),
(4, 1, 'Cher faye,\r\n\r\nNous avons le plaisir de vous informer que votre généreux don a bien été reçu. Grâce à votre solidarité, de nombreuses familles démunies pourront bénéficier d’un soutien essentiel lors des fêtes de Tabaski cette année.\r\n\r\nVotre contribution aide à apporter un peu de joie et d’espoir à ceux qui en ont le plus besoin. Ensemble, nous faisons la différence.\r\n\r\nMerci pour votre engagement et votre cœur généreux.\r\n\r\nQue cette fête vous apporte paix, bonheur et bénédictions.', 0, '2025-05-29 20:43:29', '2025-05-29 20:43:29');

-- --------------------------------------------------------

--
-- Structure de la table `partenaires`
--

DROP TABLE IF EXISTS `partenaires`;
CREATE TABLE IF NOT EXISTS `partenaires` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_partenaire` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `logo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `site_web` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `partenaires`
--

INSERT INTO `partenaires` (`id`, `nom_partenaire`, `logo`, `site_web`, `description`) VALUES
(1, 'Daba Faye', 'partner_logo_683b733eb63d8.jpg', NULL, 'je voulais etre un partenaire pour vous');

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(1, 'admin@esocial.sn', 'e05aa92e482824a8f845d887cd42b2073b1c30388f8ad4c9d4ef6d0ad3c66bc4', '2025-05-29 02:26:56', '2025-05-29 01:26:56'),
(2, 'ibsibzo97@gmail.com', 'eef06ef044739ec9ff7190a32fbb4612c09a41790720c639b543e7ad4e201247', '2025-05-29 15:54:36', '2025-05-29 14:54:36');

-- --------------------------------------------------------

--
-- Structure de la table `pays`
--

DROP TABLE IF EXISTS `pays`;
CREATE TABLE IF NOT EXISTS `pays` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_pays` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `pays`
--

INSERT INTO `pays` (`id`, `nom_pays`) VALUES
(1, 'Sénégal'),
(2, 'Mali'),
(3, 'Côte d\'Ivoire'),
(4, 'France');

-- --------------------------------------------------------

--
-- Structure de la table `preuves_transfert`
--

DROP TABLE IF EXISTS `preuves_transfert`;
CREATE TABLE IF NOT EXISTS `preuves_transfert` (
  `id` int NOT NULL AUTO_INCREMENT,
  `campagne_id` int DEFAULT NULL,
  `fichier_justificatif` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `commentaire` text COLLATE utf8mb4_general_ci,
  `date_transfert` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `campagne_id` (`campagne_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `preuves_transfert`
--

INSERT INTO `preuves_transfert` (`id`, `campagne_id`, `fichier_justificatif`, `commentaire`, `date_transfert`) VALUES
(2, 1, 'transfert_preuve_683b79f7e85ac.pdf', 'vous etes les meilleurs', '2025-05-31 21:51:51');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `prenom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mot_de_passe` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` enum('admin','donateur') COLLATE utf8mb4_general_ci DEFAULT 'donateur',
  `pays_id` int DEFAULT NULL,
  `date_inscription` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `pays_id` (`pays_id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `prenom`, `nom`, `email`, `telephone`, `mot_de_passe`, `role`, `pays_id`, `date_inscription`) VALUES
(1, 'Admin', 'Principal', 'admin@esocial.sn', NULL, 'admin123', 'admin', 1, '2025-05-27 20:41:00');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
