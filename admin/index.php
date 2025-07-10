<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';


// Récupération des statistiques
$pdo = getPDO();

// Nombre de campagnes
$stmt = $pdo->query("SELECT COUNT(*) as total FROM campagnes");
$campagnes = $stmt->fetch()['total'];

// Nombre de dons
$stmt = $pdo->query("SELECT COUNT(*) as total, SUM(montant) as montant FROM dons WHERE statut = 'confirmé'");
$dons = $stmt->fetch();

// Nombre d'utilisateurs
$stmt = $pdo->query("SELECT COUNT(*) as total FROM utilisateurs");
$utilisateurs = $stmt->fetch()['total'];

// Nombre de messages non lus
$stmt = $pdo->query("SELECT COUNT(*) as total FROM messages_contact WHERE est_lu = 0");
$messages = $stmt->fetch()['total'];

// Dons récents (5 derniers)
$stmt = $pdo->query("SELECT d.*, u.prenom, u.nom, c.titre as campagne 
                     FROM dons d
                     JOIN utilisateurs u ON d.utilisateur_id = u.id
                     JOIN campagnes c ON d.campagne_id = c.id
                     ORDER BY d.date_don DESC LIMIT 5");
$dons_recents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Campagnes actives
$stmt = $pdo->query("SELECT * FROM campagnes WHERE statut = 'en cours' ORDER BY date_creation DESC LIMIT 3");
$campagnes_actives = $stmt->fetchAll(PDO::FETCH_ASSOC);

$montant = $dons['montant'] ?? 0;  // Valeur par défaut 0 si null ou non défini
echo number_format($montant, 0, ',', ' ') . ' XOF';

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Admin - E-Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #f72585;
            --success-color: #4cc9f0;
            --warning-color: #f8961e;
            --danger-color: #ef233c;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }

        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }

        /* Sidebar */
        .sidebar {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
            height: 100vh;
            position: fixed;
            width: 250px;
            box-shadow: 2px 0 20px rgba(0,0,0,0.1);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 20px;
            background: rgba(0,0,0,0.1);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .sidebar-menu .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 5px 0;
            border-radius: 0;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .sidebar-menu .nav-link:hover, .sidebar-menu .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar-menu .nav-link i {
            width: 24px;
            text-align: center;
            margin-right: 10px;
            font-size: 1.1rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        /* Cards */
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.4s ease;
            overflow: hidden;
            position: relative;
            z-index: 1;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0) 70%);
            z-index: -1;
            opacity: 0;
            transition: all 0.6s ease;
        }

        .stat-card:hover::before {
            opacity: 1;
            animation: lightEffect 3s infinite linear;
        }

        @keyframes lightEffect {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .stat-card .card-body {
            padding: 25px;
            position: relative;
            z-index: 2;
        }

        .stat-card .icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            opacity: 0.8;
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-card .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Table */
        .recent-table {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .recent-table thead {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .recent-table th {
            border: none;
            font-weight: 600;
            padding: 15px 20px;
        }

        .recent-table td {
            padding: 12px 20px;
            vertical-align: middle;
            border-top: 1px solid #f1f1f1;
        }

        .badge-success {
            background-color: var(--success-color);
        }

        /* Campagnes cards */
        .campaign-card {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
        }

        .campaign-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .campaign-card .card-img-top {
            height: 150px;
            object-fit: cover;
        }

        .progress {
            height: 8px;
            border-radius: 4px;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .sidebar-toggler {
                display: block !important;
            }
        }

        /* Toggle button */
        .sidebar-toggler {
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 9999;
            display: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--accent-color);
            color: white;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .sidebar-toggler:hover {
            transform: scale(1.1);
        }

        /* Light effects */
        .light-spot {
            position: fixed;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(67,97,238,0.1) 0%, rgba(67,97,238,0) 70%);
            pointer-events: none;
            z-index: 0;
        }
    </style>
</head>
<body>
    <!-- Light effects -->
    <div class="light-spot" style="top: -100px; right: -100px;"></div>
    <div class="light-spot" style="bottom: -150px; left: -150px;"></div>

    <!-- Sidebar Toggler (mobile) -->
    <button class="sidebar-toggler animate__animated animate__pulse animate__infinite animate__slower">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header text-center">
            <h4 class="mb-0">E-Social Admin</h4>
            <small>Tableau de bord</small>
        </div>

        <div class="sidebar-menu">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="<?= SITE_URL ?>/admin/index.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/admin/campagnes.php">
                        <i class="fas fa-hands-helping"></i> Campagnes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/admin/dons.php">
                        <i class="fas fa-donate"></i> Dons
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/admin/beneficiaires.php">
                        <i class="fas fa-users"></i> Bénéficiaires
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/admin/utilisateurs.php">
                        <i class="fas fa-user-friends"></i> Utilisateurs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/admin/messages.php">
                        <i class="fas fa-envelope"></i> Messages
                        <?php if ($messages > 0): ?>
                        <span class="badge bg-danger float-end"><?= $messages ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/admin/transferts.php">
                        <i class="fas fa-exchange-alt"></i> Transferts
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/admin/partenaires.php">
                        <i class="fas fa-handshake"></i> Partenaires
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= SITE_URL ?>/admin/notifications.php">
                        <i class="fas fa-bell"></i> Notifications
                    </a>
                </li>
                <li class="nav-item mt-4">
                    <a class="nav-link" href="<?= SITE_URL ?>/admin/deconnexion.php">
                        <i class="fas fa-sign-out-alt"></i> Déconnexion
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <h1 class="mb-4 animate__animated animate__fadeIn">Tableau de bord</h1>
            
            <!-- Statistiques -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4 animate__animated animate__fadeInUp">
                    <div class="stat-card card bg-white">
                        <div class="card-body text-center">
                            <div class="icon text-primary">
                                <i class="fas fa-hands-helping"></i>
                            </div>
                            <h3 class="stat-value"><?= $campagnes ?></h3>
                            <p class="stat-label">Campagnes</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4 animate__animated animate__fadeInUp animate__delay-1s">
                    <div class="stat-card card bg-white">
                        <div class="card-body text-center">
                            <div class="icon text-success">
                                <i class="fas fa-donate"></i>
                            </div>
                            <h3 class="stat-value"><?= number_format($dons['montant'] ?? 0, 0, ',', ' ') ?> XOF</h3>

                            <p class="stat-label">Dons collectés</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4 animate__animated animate__fadeInUp animate__delay-2s">
                    <div class="stat-card card bg-white">
                        <div class="card-body text-center">
                            <div class="icon text-warning">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3 class="stat-value"><?= $utilisateurs ?></h3>
                            <p class="stat-label">Utilisateurs</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4 animate__animated animate__fadeInUp animate__delay-3s">
                    <div class="stat-card card bg-white">
                        <div class="card-body text-center">
                            <div class="icon text-danger">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h3 class="stat-value"><?= $messages ?></h3>
                            <p class="stat-label">Nouveaux messages</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Derniers dons et campagnes -->
            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="card bg-white border-0 shadow-sm animate__animated animate__fadeIn">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i> Derniers dons</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="recent-table">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Donateur</th>
                                            <th>Campagne</th>
                                            <th>Montant</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dons_recents as $don): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($don['prenom'] . ' ' . htmlspecialchars($don['nom'])) ?></td>
                                            <td><?= htmlspecialchars($don['campagne']) ?></td>
                                            <td><?= number_format($don['montant'], 0, ',', ' ') ?> XOF</td>
                                            <td><?= date('d/m/Y H:i', strtotime($don['date_don'])) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-end">
                            <a href="<?= SITE_URL ?>/admin/dons.php" class="btn btn-sm btn-primary">
                                Voir tous <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 mb-4">
                    <div class="card bg-white border-0 shadow-sm animate__animated animate__fadeIn">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="mb-0"><i class="fas fa-fire me-2"></i> Campagnes actives</h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($campagnes_actives as $campagne): 
                                $progress = min(100, ($campagne['montant_atteint'] / $campagne['montant_vise']) * 100);
                            ?>
                            <div class="campaign-card card mb-3">
                                <div class="card-body">
                                    <h6 class="card-title"><?= htmlspecialchars($campagne['titre']) ?></h6>
                                    <div class="progress mb-2">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?= $progress ?>%;" 
                                             aria-valuenow="<?= $progress ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100"></div>
                                    </div>
                                    <div class="d-flex justify-content-between small text-muted">
                                        <span><?= number_format($campagne['montant_atteint'], 0, ',', ' ') ?> XOF</span>
                                        <span><?= number_format($campagne['montant_vise'], 0, ',', ' ') ?> XOF</span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="card-footer bg-transparent border-0 text-end">
                            <a href="<?= SITE_URL ?>/admin/campagnes.php" class="btn btn-sm btn-primary">
                                Voir toutes <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.querySelector('.sidebar-toggler').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Light effect animation
        const lightSpots = document.querySelectorAll('.light-spot');
        document.addEventListener('mousemove', (e) => {
            lightSpots[0].style.transform = `translate(${e.clientX * 0.05}px, ${e.clientY * 0.05}px)`;
            lightSpots[1].style.transform = `translate(${-e.clientX * 0.03}px, ${-e.clientY * 0.03}px)`;
        });

        // Card hover effects
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.classList.add('animate__pulse');
            });
            
            card.addEventListener('mouseleave', () => {
                card.classList.remove('animate__pulse');
            });
        });
    </script>
</body>
</html>