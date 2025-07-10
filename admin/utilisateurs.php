<?php
session_start();
$page_title = "Gestion des Utilisateurs";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';

define('CURRENCY_SYMBOL', 'FCFA'); // ou '₣', ou 'F CFA', selon votre préférence

function redirect($url) {
    header("Location: $url");
    exit;
}

$pdo = getPDO();

// Logique de mise à jour du rôle ou statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id_to_update = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);

    if ($user_id_to_update && $user_id_to_update != $_SESSION['user_id']) { // Empêcher l'auto-modification de rôle/statut ici
        if ($_POST['action'] === 'update_role') {
            $new_role = ($_POST['new_role'] === 'admin' || $_POST['new_role'] === 'donateur') ? $_POST['new_role'] : null;
            if ($new_role) {
                try {
                    $stmt = $pdo->prepare("UPDATE utilisateurs SET role = :role WHERE id = :id");
                    $stmt->execute([':role' => $new_role, ':id' => $user_id_to_update]);
                    set_flash_message("Rôle de l'utilisateur mis à jour.", "success");
                } catch (PDOException $e) {
                    set_flash_message("Erreur lors de la mise à jour du rôle: " . $e->getMessage(), "danger");
                }
            } else {
                set_flash_message("Rôle invalide.", "warning");
            }
        }
        // Vous pourriez ajouter une action pour 'toggle_active_status' si vous avez un champ est_actif
        // elseif ($_POST['action'] === 'toggle_active_status') { ... }

    } elseif ($user_id_to_update == $_SESSION['user_id']) {
        set_flash_message("Vous ne pouvez pas modifier votre propre rôle ou statut via cette interface.", "warning");
    } else {
        set_flash_message("ID utilisateur invalide pour la mise à jour.", "warning");
    }
    redirect('utilisateurs.php');
}


// Logique de suppression d'utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_delete_user'])) {
    $user_id_to_delete = filter_input(INPUT_POST, 'user_id_delete', FILTER_VALIDATE_INT);

    if ($user_id_to_delete && $user_id_to_delete != $_SESSION['user_id']) { // Empêcher l'auto-suppression
        try {
            // Avant de supprimer, gérer les dépendances (par exemple, les dons de cet utilisateur)
            // Option 1: Mettre utilisateur_id à NULL dans la table dons
            $stmtDons = $pdo->prepare("UPDATE dons SET utilisateur_id = NULL WHERE utilisateur_id = :id");
            $stmtDons->execute([':id' => $user_id_to_delete]);

            // Option 2: Anonymiser ou supprimer les dons (plus complexe, dépend de vos besoins)

            // Supprimer l'utilisateur
            $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = :id");
            $stmt->execute([':id' => $user_id_to_delete]);
            set_flash_message("Utilisateur supprimé avec succès.", "success");
        } catch (PDOException $e) {
            set_flash_message("Erreur lors de la suppression de l'utilisateur: " . $e->getMessage(), "danger");
        }
    } elseif ($user_id_to_delete == $_SESSION['user_id']) {
        set_flash_message("Vous ne pouvez pas supprimer votre propre compte.", "danger");
    } else {
        set_flash_message("ID utilisateur invalide pour la suppression.", "warning");
    }
    redirect('utilisateurs.php');
}


// Récupération des utilisateurs
$search_user = isset($_GET['search_user']) ? sanitize($_GET['search_user']) : '';
$role_filter = isset($_GET['role_filter']) ? sanitize($_GET['role_filter']) : '';

$sql_users = "SELECT u.id, u.prenom, u.nom, u.email, u.telephone, u.role, u.date_inscription, p.nom_pays
              FROM utilisateurs u
              LEFT JOIN pays p ON u.pays_id = p.id";
$params_users = [];
$whereClauses_users = [];

if (!empty($search_user)) {
    $whereClauses_users[] = "(u.prenom LIKE :search OR u.nom LIKE :search OR u.email LIKE :search OR u.telephone LIKE :search)";
    $params_users[':search'] = "%$search_user%";
}
if (!empty($role_filter)) {
    $whereClauses_users[] = "u.role = :role";
    $params_users[':role'] = $role_filter;
}

if (!empty($whereClauses_users)) {
    $sql_users .= " WHERE " . implode(" AND ", $whereClauses_users);
}
$sql_users .= " ORDER BY u.date_inscription DESC";

$stmt_users = $pdo->prepare($sql_users);
$stmt_users->execute($params_users);
$utilisateurs = $stmt_users->fetchAll(PDO::FETCH_ASSOC);


?>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
   
</style>

<div class="page-title-container mb-4 d-flex justify-content-between align-items-center">
    <h1><i class="fas fa-users-cog me-2"></i> Gestion des Utilisateurs</h1>
</div>

<?php display_flash_message(); ?>

<!-- Filtres -->
<div class="card card-admin filter-bar-users mb-4">
    <div class="card-admin-header">
        <i class="fas fa-filter me-2"></i> Filtrer les Utilisateurs
    </div>
    <div class="card-body">
        <form method="GET" action="utilisateurs.php" class="row g-3">
            <div class="col-md-6">
                <label for="search_user" class="form-label-admin">Rechercher</label>
                <input type="text" class="form-control form-control-admin" id="search_user" name="search_user" value="<?= htmlspecialchars($search_user) ?>" placeholder="Nom, prénom, email...">
            </div>
            <div class="col-md-4">
                <label for="role_filter" class="form-label-admin">Rôle</label>
                <select class="form-select form-control-admin" id="role_filter" name="role_filter">
                    <option value="">Tous les rôles</option>
                    <option value="admin" <?= ($role_filter == 'admin') ? 'selected' : '' ?>>Administrateur</option>
                    <option value="donateur" <?= ($role_filter == 'donateur') ? 'selected' : '' ?>>Donateur</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-admin-primary w-100">
                    <i class="fas fa-search me-1"></i> Filtrer
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Liste des utilisateurs -->
<div class="user-table-container mt-4">
    <div class="card-admin-header d-flex justify-content-between align-items-center">
        <span><i class="fas fa-list me-2"></i> Liste des Utilisateurs (<?= count($utilisateurs) ?>)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-users mb-0">
            <thead class="table-dark text-center">
                <tr>
                    <th>ID</th>
                    <th>Nom Complet</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Pays</th>
                    <th>Rôle</th>
                    <th>Inscrit le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($utilisateurs)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <i class="fas fa-user-slash fa-2x text-muted mb-2"></i><br>
                            Aucun utilisateur trouvé <?= (!empty($search_user) || !empty($role_filter)) ? 'avec les filtres actuels.' : '.' ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($utilisateurs as $user):
                        $initials_user = strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1));
                        $role_class = 'role-' . strtolower($user['role']);
                        $avatar_class = strtolower($user['role']);
                    ?>
                    <tr class="align-middle text-center">
                        <td><?= (int)$user['id'] ?></td>
                        <td class="text-start">
                            <span class="user-avatar-placeholder <?= $avatar_class ?>"><?= htmlspecialchars($initials_user) ?></span>
                            <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
                        </td>
                        <td><a href="mailto:<?= htmlspecialchars($user['email']) ?>"><?= htmlspecialchars($user['email']) ?></a></td>
                        <td><?= htmlspecialchars($user['telephone'] ?: 'N/A') ?></td>
                        <td><?= htmlspecialchars($user['nom_pays'] ?: 'N/A') ?></td>
                        <td><span class="user-role-badge <?= $role_class ?>"><?= htmlspecialchars($user['role']) ?></span></td>
                        <td><?= date("d/m/Y H:i", strtotime($user['date_inscription'])) ?></td>
                        <td class="user-actions">
                            <!-- Formulaire de changement de rôle -->
                            <form method="POST" action="utilisateurs.php" class="d-inline me-2">
                                <input type="hidden" name="user_id" value="<?= (int)$user['id'] ?>">
                                <input type="hidden" name="action" value="update_role">
                                <select name="new_role" class="form-select form-select-sm d-inline w-auto">
                                    <option value="donateur" <?= $user['role'] == 'donateur' ? 'selected' : '' ?>>Donateur</option>
                                    <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                                </select>
                                <button type="submit" class="btn btn-sm btn-outline-success ms-1" title="Mettre à jour le rôle">
                                    <i class="fas fa-user-edit"></i>
                                </button>
                            </form>

                            <!-- Formulaire de suppression -->
                            <form method="POST" action="utilisateurs.php" class="d-inline"
                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Les dons associés seront déliés.');">
                                <input type="hidden" name="action_delete_user" value="delete">
                                <input type="hidden" name="user_id_delete" value="<?= (int)$user['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer l'utilisateur">
                                <?= (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id']) ? 'disabled title="Impossible de supprimer votre propre compte"' : '' ?>
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>



