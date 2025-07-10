<?php
session_start();
require_once '../config/db.php';
require_once '../config/constantes.php';
require_once '../includes/fonctions.php';


function redirect($url) {
    header("Location: $url");
    exit;
}


$error_message = '';
$success_message = '';
$abonnements = [];
$pdo = getPDO();

// --- Action : Supprimer un abonné ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_subscriber' && isset($_POST['subscriber_id'])) {
    $subscriber_id = (int)$_POST['subscriber_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM abonnements_newsletter WHERE id = :id");
        $stmt->execute([':id' => $subscriber_id]);
        $success_message = "Abonné supprimé avec succès.";
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la suppression de l'abonné : " . $e->getMessage();
    }
}

// --- Récupération des abonnements ---
$searchTerm = isset($_GET['search']) && function_exists('sanitize') ? sanitize($_GET['search']) : (isset($_GET['search']) ? $_GET['search'] : '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 15; // Nombre d'abonnés par page
$offset = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*) FROM abonnements_newsletter";
$sql = "SELECT id, email, date_abonnement FROM abonnements_newsletter";
$whereClauses = [];
$params = [];

if (!empty($searchTerm)) {
    $whereClauses[] = "email LIKE :search";
    $params[':search'] = "%$searchTerm%";
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
    $countSql .= " WHERE " . implode(" AND ", $whereClauses);
}

$sql .= " ORDER BY date_abonnement DESC LIMIT :limit OFFSET :offset";

try {
    // Comptage total pour la pagination
    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute(empty($searchTerm) ? [] : [':search' => "%$searchTerm%"]); // Les params ne sont que pour la recherche ici
    $totalAbonnements = (int)$stmtCount->fetchColumn();
    $totalPages = ceil($totalAbonnements / $perPage);
    if ($totalPages == 0 && $totalAbonnements > 0) $totalPages = 1;
    if ($page > $totalPages && $totalPages > 0) $page = $totalPages;
    $offset = ($page - 1) * $perPage; // Recalculer l'offset

    // Récupération des abonnés pour la page actuelle
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) { // Lier le :search s'il existe
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $abonnements = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des abonnements : " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Abonnés Newsletter - E-Social Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --admin-primary: #343a40;
            --admin-secondary: #6c757d;
            --admin-light: #f8f9fa;
            --admin-accent: #17a2b8; /* Teal for newsletter, or keep Bootstrap blue */
        }
        body {
            font-family: 'Roboto', sans-serif;
            background-color: var(--admin-light);
            color: #212529;
        }
        .admin-navbar {
            background-color: var(--admin-primary);
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .admin-navbar .navbar-brand, .admin-navbar .nav-link {
            color: white;
            font-family: 'Poppins', sans-serif;
        }
        .admin-navbar .nav-link:hover { color: #e9ecef; }
        .admin-navbar .nav-link.active {
            font-weight: 600;
            border-bottom: 2px solid var(--admin-accent);
        }
        .page-header {
            background-color: #ffffff;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid #dee2e6;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .page-header h1 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: var(--admin-primary);
        }
        .page-header .breadcrumb-item a {
            color: var(--admin-accent);
            text-decoration: none;
        }
        .page-header .breadcrumb-item.active { color: var(--admin-secondary); }
        .search-bar {
            background-color: #fff;
            padding: 1rem;
            border-radius: .375rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .table th {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            background-color: #e9ecef;
        }
        .table td .btn { margin-right: 5px; }
        .btn-admin-danger {
            background-color: #dc3545; border-color: #dc3545; color: white;
        }
        .btn-admin-danger:hover { background-color: #c82333; border-color: #bd2130; }
         .btn-admin-primary {
            background-color: var(--admin-accent); border-color: var(--admin-accent); color: white;
        }
        .btn-admin-primary:hover { background-color: #138496; border-color: #117a8b; } /* Teal hover */
        .no-subscribers {
            text-align: center;
            padding: 3rem;
            background-color: #fff;
            border-radius: .375rem;
            color: var(--admin-secondary);
        }
        .no-subscribers i {
            font-size: 3rem;
            margin-bottom: 1rem;
            display: block;
        }
        .pagination .page-link { color: var(--admin-accent); }
        .pagination .page-item.active .page-link {
            background-color: var(--admin-accent);
            border-color: var(--admin-accent);
            color: white;
        }
        .pagination .page-item.disabled .page-link { color: #6c757d; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg admin-navbar">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php"><i class="fas fa-cogs"></i> E-Social Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon" style="background-image: url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.55%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e\");"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php"><i class="fas fa-users"></i> Utilisateurs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_campaigns.php"><i class="fas fa-bullhorn"></i> Campagnes</a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link" href="contact_view_messages.php"><i class="fas fa-envelope-open-text"></i> Messages Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="newsletter_list.php"><i class="fas fa-newspaper"></i> Newsletter</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                 <h1><i class="fas fa-users-cog"></i> Gestion des Abonnés Newsletter</h1>
                 <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="../dashboard.php">Admin</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Abonnés Newsletter</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="fas fa-times-circle me-2"></i>
                <div><?= htmlspecialchars($error_message) ?></div>
            </div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <div><?= htmlspecialchars($success_message) ?></div>
            </div>
        <?php endif; ?>
        <?php 
            if (function_exists('display_flash_message')) {
                display_flash_message();
            }
        ?>

        <div class="search-bar">
            <form method="GET" action="newsletter_list.php" class="row g-3 align-items-end">
                <div class="col-md-10">
                    <label for="search" class="form-label">Rechercher par email :</label>
                    <input type="text" class="form-control form-control-sm" id="search" name="search" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Entrez un email...">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-admin-primary btn-sm w-100"><i class="fas fa-search"></i> Rechercher</button>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i> Liste des Abonnés (Total: <?= $totalAbonnements ?>)
            </div>
            <div class="card-body">
                <?php if (empty($abonnements)): ?>
                    <div class="no-subscribers">
                        <i class="fas fa-user-slash"></i>
                        <p>
                            <?php if (!empty($searchTerm)): ?>
                                Aucun abonné ne correspond à votre recherche "<?= htmlspecialchars($searchTerm) ?>".
                            <?php else: ?>
                                Aucun abonné à la newsletter pour le moment.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Email</th>
                                    <th>Date d'abonnement</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($abonnements as $abo): ?>
                                    <tr>
                                        <td><?= (int)$abo['id'] ?></td>
                                        <td><?= htmlspecialchars($abo['email']) ?></td>
                                        <td><?= htmlspecialchars(date("d/m/Y à H:i", strtotime($abo['date_abonnement']))) ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-admin-danger" onclick="confirmDeleteSubscriber(<?= (int)$abo['id'] ?>, '<?= htmlspecialchars(addslashes($abo['email'])) ?>')">
                                                <i class="fas fa-trash-alt"></i> Supprimer
                                            </button>
                                            <form id="deleteSubscriberForm-<?= (int)$abo['id'] ?>" method="POST" action="newsletter_list.php?search=<?=urlencode($searchTerm)?>&page=<?=$page?>" style="display: none;">
                                                <input type="hidden" name="subscriber_id" value="<?= (int)$abo['id'] ?>">
                                                <input type="hidden" name="action" value="delete_subscriber">
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-3">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($searchTerm) ?>" aria-label="Précédent">«</a>
                            </li>
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($searchTerm) ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($searchTerm) ?>" aria-label="Suivant">»</a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="text-center p-4 mt-5 border-top bg-white">
        <p class="mb-0">© <?= date("Y") ?> E-Social Admin. Tous droits réservés.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDeleteSubscriber(subscriberId, email) {
            if (confirm("Êtes-vous sûr de vouloir supprimer l'abonné : " + email + " ? Cette action est irréversible.")) {
                document.getElementById('deleteSubscriberForm-' + subscriberId).submit();
            }
        }
    </script>
</body>
</html>