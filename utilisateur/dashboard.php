<?php
session_start();
require_once '../config/db.php';
require_once '../includes/fonctions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$pdo = getPDO();

$utilisateur = getUtilisateurById($pdo, $userId);
$totalDons = getTotalDonsByUser($pdo, $userId);
$campagnesSoutenues = getCampagnesSoutenues($pdo, $userId);
$notifications = getNotifications($pdo, $userId);
$mesDonsRecents = getDonsRecentsByUser($pdo, $userId);
$campagnesPopulaires = getCampagnesPopulaires($pdo);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord | E-Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f4f6f9;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            color: #fff;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .card-hover:hover {
            transform: scale(1.02);
            transition: 0.3s;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        .section-title {
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav class="col-md-3 col-lg-2 d-md-block sidebar py-4">
            <div class="position-sticky">
                <h4 class="text-center mb-4">👤 Bonjour, <?php echo htmlspecialchars($utilisateur['prenom']); ?></h4>
                <ul class="nav flex-column px-3">
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="profil.php"><i class="bi bi-person-circle me-2"></i> Mon Profil</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="mes_dons.php"><i class="bi bi-gift me-2"></i> Mes Dons</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link" href="notification.php"><i class="bi bi-bell me-2"></i> Notifications</a>
                    </li>
                    <li class="nav-item mt-4">
                        <a class="nav-link text-danger" href="deconnexion.php"><i class="bi bi-box-arrow-right me-2"></i> Déconnexion</a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <h2 class="mb-4">📊 Tableau de Bord</h2>
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card text-white bg-primary card-hover">
                        <div class="card-body">
                            <h5 class="card-title">Total de mes dons</h5>
                            <p class="card-text display-6"><?php echo number_format($totalDons, 0, ',', ' '); ?> FCFA</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-success card-hover">
                        <div class="card-body">
                            <h5 class="card-title">Campagnes soutenues</h5>
                           <p class="card-text display-6">
                           <?= count($campagnesSoutenues) ?> campagne(s) soutenue(s)</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-white bg-warning card-hover">
                        <div class="card-body">
                            <h5 class="card-title">Notifications</h5>
                            <p class="card-text display-6"><?php echo count($notifications); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dons Récents -->
            <div class="section-title">📌 Mes derniers dons</div>
            <div class="table-responsive mb-4">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Campagne</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($mesDonsRecents): ?>
                            <?php foreach ($mesDonsRecents as $don): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($don['date_don'])); ?></td>
                                    <td><?php echo number_format($don['montant'], 0, ',', ' '); ?> FCFA</td>
                                    <td><?php echo htmlspecialchars($don['titre_campagne']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3">Aucun don récent.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Campagnes Populaires -->
            <div class="section-title">🔥 Campagnes populaires</div>
            <div class="row">
                <?php foreach ($campagnesPopulaires as $campagne): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($campagne['titre']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($campagne['description']); ?></p>
                                <a href="campagne.php?id=<?php echo $campagne['id']; ?>" class="btn btn-outline-primary">Voir la campagne</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Notifications -->
            <div class="card shadow mt-5">
                <div class="card-header">
                    🔔 Notifications récentes
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (count($notifications) > 0): ?>
                        <?php foreach ($notifications as $notif): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?php echo htmlspecialchars($notif['message']); ?></span>
                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notif['date_notification'])); ?></small>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item">Aucune notification pour le moment.</li>
                    <?php endif; ?>
                </ul>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>