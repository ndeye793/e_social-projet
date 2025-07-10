<?php
// includes/header_admin.php

require_once '../config/db.php';      // Pour getPDO() et session_start() si pas déjà fait
require_once '../config/constantes.php'; // Pour BASE_URL si utilisé
require_once '../includes/fonctions.php';  // Pour redirect(), etc.

$current_page = basename($_SERVER['PHP_SELF']); // Pour activer le lien dans la sidebar
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) : 'Admin E-Social' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Votre CSS admin personnalisé -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style_admin.css">
    <style>
        /* style_admin.css - Quelques idées de base */
        :root {
            --admin-primary: #2c3e50; /* Bleu nuit / Gris foncé */
            --admin-secondary: #34495e; /* Gris bleu */
            --admin-accent: #e74c3c; /* Rouge corail pour les accents (supprimer, actions importantes) */
            --admin-info: #3498db;   /* Bleu clair pour info */
            --admin-success: #2ecc71; /* Vert pour succès */
            --admin-warning: #f39c12; /* Orange pour avertissement */
            --admin-light: #ecf0f1;  /* Gris très clair (fond) */
            --admin-text: #566573;   /* Couleur de texte principale */
            --admin-sidebar-bg: var(--admin-primary);
            --admin-sidebar-link: #bdc3c7;
            --admin-sidebar-link-hover: #ffffff;
            --admin-sidebar-link-active: var(--admin-accent); /* Ou une autre couleur vive */
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: var(--admin-light);
            color: var(--admin-text);
            display: flex;
            min-height: 100vh;
        }

        #admin-wrapper {
            display: flex;
            width: 100%;
        }

        #sidebar-wrapper {
            min-width: 260px;
            max-width: 260px;
            background: var(--admin-sidebar-bg);
            color: var(--admin-sidebar-link);
            transition: all 0.3s;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            height: 100vh; /* Full height sidebar */
            position: fixed; /* Fixed Sidebar */
            top: 0;
            left: 0;
            overflow-y: auto; /* Scroll if content is too long */
        }
        #sidebar-wrapper.toggled {
            margin-left: -260px;
        }
        .sidebar-heading {
            padding: 1.5rem 1.25rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            font-family: 'Poppins', sans-serif;
        }
        .sidebar-heading .fa-hands-helping {
            margin-right: 10px;
            color: var(--admin-accent);
        }
        .list-group-item {
            background: var(--admin-sidebar-bg);
            border: none;
            color: var(--admin-sidebar-link);
            padding: 0.9rem 1.5rem;
            font-size: 0.95rem;
            transition: all 0.2s ease-in-out;
            border-left: 4px solid transparent; /* Pour l'indicateur actif */
        }
        .list-group-item:hover {
            background: var(--admin-secondary);
            color: var(--admin-sidebar-link-hover);
            border-left-color: var(--admin-sidebar-link-hover);
        }
        .list-group-item.active {
            background: var(--admin-secondary);
            color: #fff;
            font-weight: 600;
            border-left-color: var(--admin-sidebar-link-active); /* Indicateur actif */
        }
        .list-group-item i {
            margin-right: 12px;
            width: 20px; /* Alignement des icônes */
            text-align: center;
        }

        #page-content-wrapper {
            flex-grow: 1;
            padding: 0; /* Navbar will handle top padding */
            transition: all 0.3s;
            margin-left: 260px; /* Same as sidebar width */
            width: calc(100% - 260px); /* Full width minus sidebar */
        }
        #sidebar-wrapper.toggled + #page-content-wrapper {
            margin-left: 0;
            width: 100%;
        }

        .admin-top-navbar {
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 0.75rem 1.5rem;
            position: sticky; /* Sticky top navbar */
            top: 0;
            z-index: 1020; /* Above other content */
        }
        .admin-top-navbar .navbar-brand { display: none; } /* Hide brand if sidebar has it */
        .admin-top-navbar .nav-link { color: var(--admin-text); }
        .admin-top-navbar .nav-link:hover { color: var(--admin-primary); }
        .admin-top-navbar .dropdown-menu { border-radius: .375rem; box-shadow: 0 .5rem 1rem rgba(0,0,0,.15); }


        .page-main-content {
            padding: 2rem;
        }

        .page-title-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        .page-title-container h1 {
            font-family: 'Poppins', sans-serif;
            color: var(--admin-primary);
            font-size: 1.8rem;
            font-weight: 600;
        }
        .page-title-container h1 i {
            margin-right: 10px;
            color: var(--admin-info);
        }

        .card-admin {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        .card-admin-header {
            background-color: #fff;
            border-bottom: 1px solid #f0f0f0;
            padding: 1rem 1.5rem;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: var(--admin-primary);
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
        }
        .card-admin-header i { margin-right: 8px; color: var(--admin-info); }

        .table-admin {
            /* white-space: nowrap; Empêche le retour à la ligne, mais peut poser pb */
        }
        .table-admin th {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            background-color: var(--admin-light);
            color: var(--admin-secondary);
            border-bottom-width: 1px;
        }
        .table-admin td {
            vertical-align: middle;
        }
        .table-admin .action-buttons .btn {
            margin-right: 5px;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
        .table-admin .img-thumbnail-small {
            max-width: 60px;
            max-height: 40px;
            object-fit: cover;
        }

        /* Boutons */
        .btn-admin-primary, .btn-admin-success, .btn-admin-danger, .btn-admin-warning, .btn-admin-info {
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            border-radius: 20px; /* Plus arrondi */
            padding: 0.5rem 1.2rem;
            transition: all 0.2s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .btn-admin-primary { background-color: var(--admin-info); border-color: var(--admin-info); color: white; }
        .btn-admin-primary:hover { background-color: #2980b9; border-color: #2980b9; box-shadow: 0 4px 8px rgba(0,0,0,0.15); transform: translateY(-1px); }

        .btn-admin-success { background-color: var(--admin-success); border-color: var(--admin-success); color: white; }
        .btn-admin-success:hover { background-color: #27ae60; border-color: #27ae60; box-shadow: 0 4px 8px rgba(0,0,0,0.15); transform: translateY(-1px); }

        .btn-admin-danger { background-color: var(--admin-accent); border-color: var(--admin-accent); color: white; }
        .btn-admin-danger:hover { background-color: #c0392b; border-color: #c0392b; box-shadow: 0 4px 8px rgba(0,0,0,0.15); transform: translateY(-1px); }

        /* Statut badges */
        .badge-status { padding: 0.4em 0.7em; font-weight: 600; border-radius: 10px;}
        .status-en-cours { background-color: var(--admin-warning); color: #fff; }
        .status-terminee { background-color: var(--admin-success); color: #fff; }
        .status-suspendue { background-color: var(--admin-secondary); color: #fff; }

        /* Formulaires */
        .form-control-admin {
            border-radius: .375rem;
            border: 1px solid #ced4da;
            transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        }
        .form-control-admin:focus {
            border-color: var(--admin-info);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, .25);
        }
        .form-label-admin {
            font-weight: 600;
            color: var(--admin-text);
            margin-bottom: .5rem;
        }

        /* Sidebar Toggler */
        #sidebarToggle {
            color: var(--admin-text);
        }
        #sidebarToggle:hover {
            color: var(--admin-primary);
        }

        @media (max-width: 768px) {
            #sidebar-wrapper {
                margin-left: -260px;
            }
            #sidebar-wrapper.toggled {
                margin-left: 0;
            }
            #page-content-wrapper {
                margin-left: 0;
                width: 100%;
            }
            .admin-top-navbar .navbar-brand { display: block; } /* Show brand on small screens if sidebar is hidden */
        }

    </style>
</head>
<body>
    <div id="admin-wrapper">
        

        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light admin-top-navbar">
                <div class="container-fluid">
                    <button class="btn btn-link btn-sm" id="sidebarToggle"><i class="fas fa-bars fa-lg"></i></button>
                    <a class="navbar-brand d-lg-none" href="<?= BASE_URL ?>admin/index.php">E-Social Admin</a>

                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminTopNav" aria-controls="adminTopNav" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="adminTopNav">
                        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center">
                            <li class="nav-item">
                                <a class="nav-link" href="<?= BASE_URL ?>public/index.php" target="_blank" title="Voir le site public">
                                    <i class="fas fa-external-link-alt"></i> Site Public
                                </a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-circle"></i>
                                    <?= htmlspecialchars($_SESSION['user_prenom'] ?? 'Admin') ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownUser">
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>utilisateur/profil.php"><i class="fas fa-user-edit me-2"></i> Mon Profil</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/deconnexion.php"><i class="fas fa-sign-out-alt me-2"></i> Déconnexion</a></li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <main class="page-main-content">
                <!-- Le contenu spécifique de la page sera inséré ici -->