<?php
// traitement/index.php (Dashboard)
require_once '../config/db.php'; // db.php démarre la session et contient les fonctions utiles
require_once '../includes/fonctions.php'; // adapte le chemin si besoin

function redirect($url) {
    header("Location: $url");
    exit;
}
$user_prenom = $_SESSION['user_prenom'] ?? 'Utilisateur';
$user_role = $_SESSION['user_role'] ?? 'Non défini';

// Fonctions pour récupérer quelques statistiques (exemples)
function getTotalDons($pdo) {
    $stmt = $pdo->query("SELECT SUM(montant) as total FROM dons WHERE statut = 'confirmé'");
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

function getNombreCampagnesActives($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM campagnes WHERE statut = 'en cours'");
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

function getNombreUtilisateurs($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM utilisateurs");
    $result = $stmt->fetch();
    return $result['count'] ?? 0;
}

$pdo = getPDO();
$total_dons = getTotalDons($pdo);
$campagnes_actives = getNombreCampagnesActives($pdo);
$nombre_utilisateurs = getNombreUtilisateurs($pdo);

$page_title = "Tableau de Bord - Traitement E-Social";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6a11cb; /* Violet profond */
            --secondary-color: #2575fc; /* Bleu vibrant */
            --light-bg: #f8f9fa;
            --dark-text: #343a40;
            --light-text: #f8f9fa;
            --sidebar-bg: #2c3e50; /* Bleu nuit / Gris foncé */
            --sidebar-link-color: #ecf0f1; /* Blanc cassé */
            --sidebar-link-hover-bg: #34495e; /* Gris un peu plus clair */
            --content-glow-start: rgba(106, 17, 203, 0.15);
            --content-glow-end: rgba(37, 117, 252, 0.15);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-bg);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden; /* Empêche le défilement horizontal causé par la sidebar en transition */
        }

        #sidebar {
            min-width: 260px;
            max-width: 260px;
            background: var(--sidebar-bg);
            color: var(--light-text);
            transition: all 0.3s;
            position: fixed; /* Sidebar fixe */
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 1000;
            overflow-y: auto; /* Permet le scroll si le contenu dépasse */
        }
        #sidebar.active {
            margin-left: -260px;
        }

        #sidebar .sidebar-header {
            padding: 20px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            text-align: center;
        }
        #sidebar .sidebar-header h3 {
            color: white;
            margin-bottom: 0;
            font-weight: 600;
        }
        #sidebar .sidebar-header .small-text {
            font-size: 0.8em;
            color: #ddd;
        }

        #sidebar ul.components {
            padding: 20px 0;
            border-bottom: 1px solid #47748b;
        }

        #sidebar ul p {
            color: var(--light-text);
            padding: 10px;
            text-transform: uppercase;
            font-weight: 600;
        }

        #sidebar ul li a {
            padding: 12px 20px;
            font-size: 1.05em;
            display: block;
            color: var(--sidebar-link-color);
            border-left: 3px solid transparent;
            transition: all 0.3s ease;
        }
        #sidebar ul li a:hover {
            color: var(--light-text);
            background: var(--sidebar-link-hover-bg);
            border-left-color: var(--secondary-color);
            text-decoration: none;
        }
        #sidebar ul li.active > a, a[aria-expanded="true"] {
            color: var(--light-text);
            background: var(--primary-color);
        }
        #sidebar ul li a i {
            margin-right: 10px;
            width: 20px; /* Align icons */
            text-align: center;
        }

        #content {
            width: calc(100% - 260px); /* Prend en compte la sidebar fixe */
            margin-left: 260px; /* Décale le contenu */
            padding: 25px;
            min-height: 100vh;
            transition: all 0.3s;
            background-color: #f4f7f6; /* Un blanc légèrement cassé pour le contenu */
        }
        #content.active {
            width: 100%;
            margin-left: 0;
        }
        
        .navbar-custom {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 10px 15px;
            margin-bottom: 20px;
        }
        .navbar-custom .navbar-brand {
            color: var(--primary-color);
            font-weight: 600;
        }
         .sidebar-toggler {
            background: transparent;
            border: none;
            color: var(--primary-color);
            font-size: 1.5rem;
        }
        .sidebar-toggler:hover {
            color: var(--secondary-color);
        }

        .stat-card {
            background: #fff;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-left: 5px solid var(--primary-color);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.12);
        }
        .stat-card .stat-icon {
            font-size: 2.5rem;
            padding: 15px;
            border-radius: 50%;
            color: #fff;
            margin-right: 20px;
            width: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .stat-card .stat-info h5 {
            margin-bottom: 5px;
            color: var(--dark-text);
            font-weight: 600;
        }
        .stat-card .stat-info p {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0;
        }
        .icon-dons { background: linear-gradient(135deg, #28a745, #20c997); } /* Green shades */
        .icon-campagnes { background: linear-gradient(135deg, #17a2b8, #007bff); } /* Blue shades */
        .icon-utilisateurs { background: linear-gradient(135deg, #ffc107, #fd7e14); } /* Yellow/Orange shades */
        .icon-beneficiaires { background: linear-gradient(135deg, #6f42c1, #e83e8c); } /* Purple/Pink shades */

        .welcome-banner {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }
        .welcome-banner h3 { font-weight: 600; }
        .welcome-banner p { font-size: 1.1em; }
        
        .card-custom {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
            overflow: hidden; /* Pour les card-header colorés */
        }
        .card-custom .card-header-custom {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 500;
            padding: 0.8rem 1.2rem;
        }
        .card-custom .card-body {
            padding: 1.5rem;
        }

        .btn-gradient {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .btn-gradient:hover {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.15);
            color: white;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -260px; /* Cachée par défaut sur mobile */
            }
            #sidebar.active {
                margin-left: 0; /* Visible quand active */
            }
            #content {
                width: 100%;
                margin-left: 0;
            }
            #content.active { /* Si on veut pousser le contenu quand sidebar active sur mobile */
                /* margin-left: 260px; width: calc(100% - 260px); */
            }
            .stat-card { flex-direction: column; align-items: center; text-align: center; }
            .stat-card .stat-icon { margin-right: 0; margin-bottom: 15px; }
        }

        /* Pour les liens dans le contenu */
        a {
            color: var(--primary-color);
            text-decoration: none;
        }
        a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-hands-helping"></i> E-Social</h3>
            <span class="small-text">Panneau de Traitement</span>
        </div>

        <ul class="list-unstyled components">
            <p><i class="fas fa-user-circle"></i> Bienvenue, <?= htmlspecialchars($user_prenom) ?></p>
            <li class="active">
                <a href="index.php"><i class="fas fa-tachometer-alt"></i> Tableau de Bord</a>
            </li>
            <li>
                <a href="#campagneSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-bullhorn"></i> Campagnes</a>
                <ul class="collapse list-unstyled" id="campagneSubmenu">
                    <li><a href="campagne_add.php"><i class="fas fa-plus-circle"></i> Ajouter Campagne</a></li>
                    <li><a href="campagne_list.php"> Gérer Campagnes</a></li> <!-- Supposant un fichier pour lister/modifier/supprimer -->
                </ul>
            </li>
             <li class="nav-item">
                <a href="#beneficiaireSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="nav-link text-white dropdown-toggle">
                    <i class="fas fa-users me-2"></i> Bénéficiaires
                </a>
                <ul class="collapse list-unstyled ps-4" id="beneficiaireSubmenu">
                    <li><a class="nav-link text-white" href="beneficiaire_add.php"><i class="fas fa-user-plus me-2"></i> Ajouter</a></li>
                    <li><a class="nav-link text-white" href="beneficiaire_list.php"><i class="fas fa-list-alt me-2"></i> Liste</a></li>
                </ul>
            </li>
            <li>
                <a href="#donSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-donate"></i> Dons</a>
                <ul class="collapse list-unstyled" id="donSubmenu">
                     <li><a href="don_form.php"><i class="fas fa-hand-holding-usd"></i> Enregistrer un Don</a></li> <!-- Si admin peut enregistrer manuellement -->
                     <li><a href="preuve_upload.php"><i class="fas fa-file-upload"></i> Envoyer Preuve Don</a></li>
                </ul>
            </li>
            <li>
                <a href="transfert_upload.php"><i class="fas fa-exchange-alt"></i> Justificatifs Transfert</a>
            </li>
             <li>
                <a href="#contactSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-envelope"></i> Communication</a>
                <ul class="collapse list-unstyled" id="contactSubmenu">
                    <li><a href="contact_view_messages.php"><i class="fas fa-inbox"></i> Messages Reçus</a></li> <!-- Suppose un fichier pour voir les messages -->
                    <li><a href="newsletter_list.php"><i class="fas fa-newspaper"></i> Abonnés Newsletter</a></li><!-- Suppose un fichier pour voir les abonnés -->
                </ul>
            </li>
             <li>
                <a href="../admin/index.php" target="_blank"><i class="fas fa-user-shield"></i> Panel Admin Complet</a>
            </li>
        </ul>

        <ul class="list-unstyled CTAs">
            <li><a href="../index.php" class="download" target="_blank"><i class="fas fa-globe"></i> Voir le site public</a></li>
            <li><a href="logout.php" class="article bg-danger"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </nav>

    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-custom">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="sidebar-toggler">
                    <i class="fas fa-align-left"></i>
                </button>
                <span class="navbar-brand mb-0 h1 d-none d-md-block">Tableau de Bord</span>
                <div class="d-flex align-items-center">
                    <span class="me-3 d-none d-md-inline">Rôle: <?= htmlspecialchars(ucfirst($user_role)) ?></span>
                    <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </div>
            </div>
        </nav>

        <?php display_flash_message(); ?>

        <div class="welcome-banner">
            <h3>Bonjour, <?= htmlspecialchars($user_prenom) ?> !</h3>
            <p>Bienvenue sur le panneau de traitement de E-Social. Gérez efficacement les opérations et suivez les indicateurs clés.</p>
        </div>
        
        <div class="row">
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon icon-dons"><i class="fas fa-hand-holding-usd"></i></div>
                    <div class="stat-info">
                        <h5>Total Dons Confirmés</h5>
                        <p><?= number_format($total_dons, 0, ',', ' ') ?> CFA</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon icon-campagnes"><i class="fas fa-bullhorn"></i></div>
                    <div class="stat-info">
                        <h5>Campagnes Actives</h5>
                        <p><?= htmlspecialchars($campagnes_actives) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3 mb-4">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon icon-utilisateurs"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h5>Utilisateurs Inscrits</h5>
                        <p><?= htmlspecialchars($nombre_utilisateurs) ?></p>
                    </div>
                </div>
            </div>
             <div class="col-md-6 col-lg-3 mb-4">
                <div class="stat-card d-flex align-items-center">
                    <div class="stat-icon icon-beneficiaires"><i class="fas fa-street-view"></i></div>
                    <div class="stat-info">
                        <h5>Bénéficiaires Aidés</h5>
                        <p>12 <!-- À remplacer par une vraie donnée --></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-7 mb-4">
                <div class="card card-custom">
                    <div class="card-header-custom">
                        <i class="fas fa-tasks"></i> Actions Rapides
                    </div>
                    <div class="card-body">
                        <p>Accédez rapidement aux fonctionnalités clés :</p>
                        <a href="campagne_add.php" class="btn btn-gradient me-2 mb-2"><i class="fas fa-plus-circle"></i> Nouvelle Campagne</a>
                        <a href="beneficiaire_add.php" class="btn btn-gradient me-2 mb-2"><i class="fas fa-user-plus"></i> Nouveau Bénéficiaire</a>
                        <a href="don_form.php" class="btn btn-gradient me-2 mb-2"><i class="fas fa-donate"></i> Enregistrer un Don</a>
                        <!-- Ajoutez d'autres liens utiles ici -->
                    </div>
                </div>
            </div>
            <div class="col-lg-5 mb-4">
                 <div class="card card-custom">
                    <div class="card-header-custom">
                       <i class="far fa-bell"></i> Notifications Récentes
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">Nouveau don de 5000 CFA pour "Urgence Eau Potable".</li>
                            <li class="list-group-item">Campagne "Aide Scolaire Kolda" a atteint 75% de son objectif.</li>
                            <li class="list-group-item">Un nouveau bénéficiaire "Fatou Diop" en attente de validation.</li>
                            <!-- Dynamiser avec de vraies notifications -->
                        </ul>
                        <div class="text-end mt-2">
                             <a href="#" class="btn btn-sm btn-outline-primary">Voir toutes les notifications</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <footer class="mt-auto pt-3 text-center text-muted">
            <p>&copy; <?= date('Y') ?> E-Social. Tous droits réservés.</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarCollapse = document.getElementById('sidebarCollapse');
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');

            // Fonction pour gérer l'état de la sidebar (ouverte/fermée)
            function toggleSidebar() {
                sidebar.classList.toggle('active');
                content.classList.toggle('active'); // Pour ajuster le margin-left du contenu si besoin
                // Sauvegarder l'état dans localStorage
                localStorage.setItem('sidebarActive', sidebar.classList.contains('active'));
            }

            // Appliquer l'état sauvegardé au chargement
            if (localStorage.getItem('sidebarActive') === 'true') {
                sidebar.classList.add('active');
                content.classList.add('active');
            } else if (localStorage.getItem('sidebarActive') === 'false') { // Explicitement false
                 sidebar.classList.remove('active');
                 content.classList.remove('active');
            } else { // Comportement par défaut si rien n'est dans localStorage
                 if (window.innerWidth <= 768) { // Fermée par défaut sur mobile
                    sidebar.classList.add('active');
                    content.classList.add('active');
                }
            }


            if (sidebarCollapse) {
                sidebarCollapse.addEventListener('click', function () {
                    toggleSidebar();
                });
            }
            
            // Fermer la sidebar si on clique en dehors sur mobile
            // Cela peut être plus complexe si le contenu lui-même a des éléments cliquables
            // document.addEventListener('click', function(event) {
            //     if (window.innerWidth <= 768 && !sidebar.contains(event.target) && !sidebarCollapse.contains(event.target) && !sidebar.classList.contains('active')) {
            //         // toggleSidebar(); // This might be too aggressive
            //     }
            // });

        });
    </script>
</body>
</html>