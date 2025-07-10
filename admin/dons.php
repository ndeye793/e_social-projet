<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';

$pdo = getPDO();

// ... (votre code PHP existant pour récupérer les filtres, les dons, les stats reste le même) ...
// ... (votre code PHP pour le traitement POST reste le même) ...
// La seule chose à changer est la manière dont les actions sont présentées dans le tableau HTML

// Récupération des paramètres de filtrage
$campagne_id_filtre = $_GET['campagne_id'] ?? null;
$statut_filtre = $_GET['statut'] ?? null;
$date_debut_filtre = $_GET['date_debut'] ?? null;
$date_fin_filtre = $_GET['date_fin'] ?? null;
$page_actuelle_filtres = $_GET; // Pour conserver les filtres dans les liens

// Construction de la requête SQL avec filtres
$sql = "SELECT d.*, 
               u.prenom, u.nom, u.email, 
               c.titre as campagne_titre, 
               mp.nom_moyen 
        FROM dons d
        LEFT JOIN utilisateurs u ON d.utilisateur_id = u.id
        LEFT JOIN campagnes c ON d.campagne_id = c.id
        LEFT JOIN moyens_paiement mp ON d.moyen_paiement_id = mp.id
        WHERE 1=1"; 

$params = [];

if ($campagne_id_filtre) {
    $sql .= " AND d.campagne_id = ?";
    $params[] = $campagne_id_filtre;
}

if ($statut_filtre) {
    $sql .= " AND d.statut = ?";
    $params[] = $statut_filtre;
}

if ($date_debut_filtre && $date_fin_filtre) {
    $sql .= " AND d.date_don BETWEEN ? AND ?";
    $params[] = $date_debut_filtre . " 00:00:00";
    $params[] = $date_fin_filtre . " 23:59:59";
} elseif ($date_debut_filtre) {
    $sql .= " AND d.date_don >= ?";
    $params[] = $date_debut_filtre . " 00:00:00";
} elseif ($date_fin_filtre) {
    $sql .= " AND d.date_don <= ?";
    $params[] = $date_fin_filtre . " 23:59:59";
}

$sql .= " ORDER BY d.date_don DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dons = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt_campagnes = $pdo->query("SELECT id, titre FROM campagnes ORDER BY titre");
$campagnes_pour_filtre = $stmt_campagnes->fetchAll(PDO::FETCH_ASSOC);

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
     if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
         $_SESSION['error_message'] = "Erreur de sécurité (CSRF). Action annulée.";
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($page_actuelle_filtres));
        exit();
     }

    if (isset($_POST['modifier_statut'])) {
        $don_id = $_POST['don_id'] ?? null;
        $nouveau_statut = $_POST['nouveau_statut'] ?? null;

        if ($don_id && in_array($nouveau_statut, ['en attente', 'confirmé', 'rejeté'])) {
            $stmt_update_don = $pdo->prepare("UPDATE dons SET statut = ? WHERE id = ?");
            $stmt_update_don->execute([$nouveau_statut, $don_id]);
            
            // TODO: Mettre à jour montant_atteint de la campagne
            // (logique à ajouter si un don passe à 'confirmé' ou quitte 'confirmé')

            if (isset($_SESSION['user_id'])) {
                 $action = "Modification statut du don #$don_id en '$nouveau_statut'";
                 $log_stmt = $pdo->prepare("INSERT INTO logs_admin (admin_id, action) VALUES (?, ?)");
                 $log_stmt->execute([$_SESSION['user_id'], $action]);
            }
            $_SESSION['success_message'] = "Statut du don #$don_id mis à jour avec succès.";
        } else {
            $_SESSION['error_message'] = "Données invalides pour la modification du statut.";
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($page_actuelle_filtres));
        exit();
    }

    if (isset($_POST['supprimer_don_confirme'])) { // Notez le changement de nom ici
        $don_id = $_POST['don_id'] ?? null;

        if ($don_id) {
            $pdo->beginTransaction();
            try {
                $stmt_preuve = $pdo->prepare("SELECT preuve_paiement, montant, statut, campagne_id FROM dons WHERE id = ?");
                $stmt_preuve->execute([$don_id]);
                $don_details = $stmt_preuve->fetch();

                if ($don_details && $don_details['preuve_paiement']) {
                    $file_path = __DIR__ . '/../uploads/preuves_dons/' . $don_details['preuve_paiement'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }

                if ($don_details && $don_details['statut'] === 'confirmé' && $don_details['campagne_id']) {
                    $stmt_camp_update = $pdo->prepare("UPDATE campagnes SET montant_atteint = montant_atteint - ? WHERE id = ?");
                    $stmt_camp_update->execute([$don_details['montant'], $don_details['campagne_id']]);
                }

                $stmt_delete = $pdo->prepare("DELETE FROM dons WHERE id = ?");
                $stmt_delete->execute([$don_id]);

                if (isset($_SESSION['user_id'])) {
                    $action = "Suppression du don #$don_id";
                    $log_stmt = $pdo->prepare("INSERT INTO logs_admin (admin_id, action) VALUES (?, ?)");
                    $log_stmt->execute([$_SESSION['user_id'], $action]);
                }
                $pdo->commit();
                $_SESSION['success_message'] = "Don #$don_id supprimé avec succès.";

            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error_message'] = "Erreur lors de la suppression du don: " . $e->getMessage();
            }
        } else {
             $_SESSION['error_message'] = "ID de don invalide pour la suppression.";
        }
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($page_actuelle_filtres));
        exit();
    }
}

$query_stats_parts = ["SELECT 
    COUNT(*) AS total_dons,
    SUM(montant) AS montant_total,
    SUM(CASE WHEN statut = 'confirmé' THEN montant ELSE 0 END) AS montant_confirme,
    COUNT(CASE WHEN statut = 'en attente' THEN 1 ELSE NULL END) AS dons_attente
FROM dons WHERE 1=1"];
$params_stats = [];

if ($campagne_id_filtre) {
    $query_stats_parts[] = "AND campagne_id = ?";
    $params_stats[] = $campagne_id_filtre;
}
if ($statut_filtre) {
    $query_stats_parts[] = "AND statut = ?";
    $params_stats[] = $statut_filtre;
}
$stmt_stats = $pdo->prepare(implode(" ", $query_stats_parts));
$stmt_stats->execute($params_stats);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
$csrf_token = generate_csrf_token(); // Toujours utile si d'autres formulaires restent sur cette page

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des dons - E-Social Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #4361ee; 
            --secondary-color: #00a896; 
            --accent-color: #f7b731; 
            --success-color: #28a745; 
            --warning-color: #ffc107; 
            --danger-color: #dc3545; 
            --info-color: #17a2b8; 
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white; min-height: 100vh; width: 250px; position: fixed; top: 0; left: 0;
            /* z-index: 1030;  Peut être enlevé si plus de modals ici */
            box-shadow: 2px 0 10px rgba(0,0,0,0.1); transition: width 0.3s ease; 
        }
        .main-content { margin-left: 250px; padding: 25px; width: calc(100% - 250px); transition: margin-left 0.3s ease; }
        .stat-card { border-radius: 12px; border: none; box-shadow: 0 6px 25px rgba(0,0,0,0.07); transition: transform 0.3s ease, box-shadow 0.3s ease; background-color: #fff; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .stat-card .card-body { padding: 1.75rem; } .stat-card .icon { font-size: 2.2rem; margin-bottom: 1rem; opacity: 0.8; }
        .stat-card .stat-value { font-size: 2rem; font-weight: 700; } .stat-card .stat-label { color: #555; font-size: 0.95rem; font-weight: 500; }
        .table-responsive { background: white; border-radius: 12px; box-shadow: 0 6px 25px rgba(0,0,0,0.07); overflow: hidden; }
        .table thead { background: var(--primary-color); color: white; }
        .table th { border: none; font-weight: 600; padding: 0.9rem 1rem; }
        .table td { vertical-align: middle; padding: 0.9rem 1rem; border-top: 1px solid #e9ecef; }
        .table tbody tr:last-child td { border-bottom: none; }
        .badge { font-size: 0.85em; padding: .5em .75em !important; } 
        .badge-confirme { background-color: var(--success-color); color: white; }
        .badge-attente { background-color: var(--warning-color); color: white; }
        .badge-rejete { background-color: var(--danger-color); color: white; }
        .action-btn {
            width: 35px; height: 35px; display: inline-flex; align-items: center; justify-content: center;
            border-radius: 50%; border: none; transition: all 0.2s ease; font-size: 0.9rem; 
            text-decoration: none; /* Pour les liens stylés en boutons */
        }
        .action-btn:hover { transform: scale(1.1); box-shadow: 0 2px 5px rgba(0,0,0,0.15); }
        .btn-edit { background-color: var(--info-color); color: white; } 
        .btn-edit:hover { background-color: #138496; color: white; } 
        .btn-delete { background-color: var(--danger-color); color: white; }
        .btn-delete:hover { background-color: #bd2130; color: white; }
        .btn-view-proof { background-color: var(--secondary-color); color: white; }
        .btn-view-proof:hover { background-color: #008273; color: white; }
        .filter-card { background: white; border-radius: 12px; box-shadow: 0 6px 25px rgba(0,0,0,0.07); margin-bottom: 25px; padding: 1.75rem; }
        .pagination .page-item.active .page-link { background-color: var(--primary-color); border-color: var(--primary-color); }
        .pagination .page-link { color: var(--primary-color); }
        .pagination .page-link:hover { background-color: #e9ecef; }
        @media (max-width: 992px) { 
            .sidebar { width: 100%; height: auto; position: static; z-index: auto; box-shadow: none; border-bottom: 2px solid var(--primary-color); }
            .main-content { margin-left: 0; width: 100%; }
            .stat-card { margin-bottom: 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="d-flex flex-column flex-lg-row"> 
        <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
        
        <div class="main-content flex-grow-1"> 
            <h1 class="mb-4 animate__animated animate__fadeInDown"><i class="fas fa-donate me-2"></i> Gestion des dons</h1>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show animate__animated animate__fadeIn" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($_SESSION['error_message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Statistiques -->
            <div class="row mb-4 animate__animated animate__fadeInUp">
                <!-- Vos cards de statistiques ici, inchangées -->
                 <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card card h-100">
                        <div class="card-body text-center">
                            <div class="icon text-primary"><i class="fas fa-hand-holding-usd"></i></div>
                            <h3 class="stat-value"><?= number_format($stats['montant_total'] ?? 0, 0, ',', ' ') ?> XOF</h3>
                            <p class="stat-label">Total Collecté (filtré)</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card card h-100">
                        <div class="card-body text-center">
                            <div class="icon" style="color: var(--success-color);"><i class="fas fa-check-circle"></i></div>
                            <h3 class="stat-value"><?= number_format($stats['montant_confirme'] ?? 0, 0, ',', ' ') ?> XOF</h3>
                            <p class="stat-label">Dons Confirmés (filtré)</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card card h-100">
                        <div class="card-body text-center">
                            <div class="icon" style="color: var(--warning-color);"><i class="fas fa-hourglass-half"></i></div>
                            <h3 class="stat-value"><?= $stats['dons_attente'] ?? 0 ?></h3>
                            <p class="stat-label">Dons en Attente (filtré)</p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stat-card card h-100">
                        <div class="card-body text-center">
                            <div class="icon" style="color: var(--info-color);"><i class="fas fa-list-ol"></i></div>
                            <h3 class="stat-value"><?= $stats['total_dons'] ?? 0 ?></h3>
                            <p class="stat-label">Total des Dons (filtré)</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="filter-card mb-4 animate__animated animate__fadeIn">
                 <!-- Votre formulaire de filtres ici, inchangé -->
                <h5 class="mb-3"><i class="fas fa-filter me-2"></i> Filtres</h5>
                <form method="get" action="<?= $_SERVER['PHP_SELF'] ?>" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="campagne_id" class="form-label">Campagne</label>
                        <select id="campagne_id" name="campagne_id" class="form-select">
                            <option value="">Toutes les campagnes</option>
                            <?php foreach ($campagnes_pour_filtre as $campagne): ?>
                            <option value="<?= $campagne['id'] ?>" <?= $campagne_id_filtre == $campagne['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($campagne['titre'])) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="statut" class="form-label">Statut</label>
                        <select id="statut" name="statut" class="form-select">
                            <option value="">Tous les statuts</option>
                            <option value="en attente" <?= $statut_filtre == 'en attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="confirmé" <?= $statut_filtre == 'confirmé' ? 'selected' : '' ?>>Confirmé</option>
                            <option value="rejeté" <?= $statut_filtre == 'rejeté' ? 'selected' : '' ?>>Rejeté</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="date_debut" class="form-label">Date début</label>
                        <input type="date" id="date_debut" name="date_debut" class="form-control" value="<?= htmlspecialchars($date_debut_filtre ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label for="date_fin" class="form-label">Date fin</label>
                        <input type="date" id="date_fin" name="date_fin" class="form-control" value="<?= htmlspecialchars($date_fin_filtre ?? '') ?>">
                    </div>
                    <div class="col-md-2 d-flex">
                        <button type="submit" class="btn btn-primary w-100 me-2"><i class="fas fa-search me-1"></i> Filtrer</button>
                        <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-secondary" title="Réinitialiser les filtres"><i class="fas fa-undo"></i></a>
                    </div>
                </form>
            </div>
            
            <!-- Tableau des dons -->
            <div class="table-responsive animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                <table class="table table-hover align-middle"> 
                    <thead>
                        <tr>
                            <th>ID</th><th>Donateur</th><th>Campagne</th><th>Montant</th>
                            <th>Moyen Paiement</th><th>Date</th><th>Statut</th><th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dons)): ?>
                        <tr><td colspan="8" class="text-center p-5">
                            <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                            <p class="h5">Aucun don trouvé.</p>
                            <p class="text-muted">Veuillez ajuster vos filtres ou enregistrer de nouveaux dons.</p>
                        </td></tr>
                        <?php else: ?>
                            <?php foreach ($dons as $don): 
                                // Préparer les paramètres GET pour les liens d'action, incluant les filtres actuels
                                $query_params_action = array_merge(['id' => $don['id']], $page_actuelle_filtres);
                                $lien_modifier = 'modifier_don_statut.php?' . http_build_query($query_params_action);
                                $lien_supprimer = 'confirmer_suppression_don.php?' . http_build_query($query_params_action);
                            ?>
                            <tr>
                                <td><strong>#<?= $don['id'] ?></strong></td>
                                <td>
                                    <div><?= htmlspecialchars(($don['prenom'] ?? 'Donateur') . ' ' . ($don['nom'] ?? 'Anonyme')) ?></div>
                                    <small class="text-muted fst-italic"><?= htmlspecialchars($don['email'] ?? 'Email non fourni') ?></small>
                                </td>
                                <td><?= htmlspecialchars($don['campagne_titre'] ?? 'N/A - Don libre') ?></td>
                                <td class="fw-bold"><?= number_format($don['montant'], 0, ',', ' ') ?> XOF</td>
                                <td><?= htmlspecialchars($don['nom_moyen'] ?? 'Non spécifié') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($don['date_don'])) ?></td>
                                <td>
                                    <?php 
                                    $statut_text_display = ucfirst(htmlspecialchars($don['statut']));
                                    $badge_class_display = ''; $icon_class_display = '';
                                    switch ($don['statut']) {
                                        case 'confirmé': $badge_class_display = 'badge-confirme'; $icon_class_display = 'fas fa-check-circle'; break;
                                        case 'en attente': $badge_class_display = 'badge-attente'; $icon_class_display = 'fas fa-hourglass-half'; break; 
                                        case 'rejeté': $badge_class_display = 'badge-rejete'; $icon_class_display = 'fas fa-times-circle'; break;
                                        default: $badge_class_display = 'bg-secondary text-white'; $icon_class_display = 'fas fa-question-circle';
                                    } ?>
                                    <span class="badge <?= $badge_class_display ?> rounded-pill"><i class="<?= $icon_class_display ?> me-1"></i> <?= $statut_text_display ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <a href="<?= htmlspecialchars($lien_modifier) ?>" class="action-btn btn-edit" title="Modifier le statut">
                                            <i class="fas fa-pencil-alt"></i> 
                                        </a>
                                        <a href="<?= htmlspecialchars($lien_supprimer) ?>" class="action-btn btn-delete" title="Supprimer le don">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                        <?php if (!empty($don['preuve_paiement']) && defined('SITE_URL')): ?>
                                        <a href="<?= htmlspecialchars(SITE_URL . '/uploads/preuves_dons/' . $don['preuve_paiement']); ?>" 
                                           target="_blank" class="action-btn btn-view-proof" title="Voir la preuve de paiement">
                                            <i class="fas fa-receipt"></i> 
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <!-- LES MODALS SONT SUPPRIMÉES D'ICI -->
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if (!empty($dons)): ?>
            <nav aria-label="Page navigation" class="mt-4 d-flex justify-content-center">
                 <!-- Votre pagination ici, inchangée -->
                <ul class="pagination">
                    <li class="page-item disabled"><a class="page-link" href="#" tabindex="-1" aria-disabled="true">Précédent</a></li>
                    <li class="page-item active" aria-current="page"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">Suivant</a></li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Ce script n'est plus nécessaire si les modals sont supprimées
// document.querySelectorAll('.action-btn').forEach(btn => {
//     btn.addEventListener('mouseenter', () => btn.classList.add('animate__animated', 'animate__pulse'));
//     btn.addEventListener('mouseleave', () => btn.classList.remove('animate__animated', 'animate__pulse'));
// });
</script>
</body>
</html>