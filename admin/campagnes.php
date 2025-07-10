<?php
$page_title = "Gestion des Campagnes";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';



$pdo = getPDO();

// Logique de suppression (si POST et action=delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_campagne') {
    if (isset($_POST['campagne_id']) && !empty($_POST['campagne_id'])) {
        $campagne_id = (int)$_POST['campagne_id'];
        try {
            // D'abord, vérifier s'il y a des dons associés pour éviter les erreurs de clé étrangère
            // Ou configurer ON DELETE SET NULL / CASCADE dans la BDD
            // Ici, on suppose que vous voulez supprimer même s'il y a des dons (à adapter)
            $stmtCheckDons = $pdo->prepare("SELECT COUNT(*) FROM dons WHERE campagne_id = :id");
            $stmtCheckDons->execute([':id' => $campagne_id]);
            if ($stmtCheckDons->fetchColumn() > 0) {
                 // Si vous voulez empêcher la suppression si des dons existent:
                 // set_flash_message("Impossible de supprimer la campagne car elle a des dons associés. Archivez-la plutôt.", "warning");
                 // Sinon, supprimez les dons ou mettez à jour leur campagne_id à NULL
                 $stmtDeleteDons = $pdo->prepare("UPDATE dons SET campagne_id = NULL WHERE campagne_id = :id"); // Ou DELETE
                 $stmtDeleteDons->execute([':id' => $campagne_id]);
            }

            // Récupérer le nom de l'image pour la supprimer du serveur
            $stmtImg = $pdo->prepare("SELECT image_campagne FROM campagnes WHERE id = :id");
            $stmtImg->execute([':id' => $campagne_id]);
            $image_file = $stmtImg->fetchColumn();

            $stmt = $pdo->prepare("DELETE FROM campagnes WHERE id = :id");
            $stmt->execute([':id' => $campagne_id]);

            if ($image_file && file_exists(ROOT_PATH . 'assets/images/campagnes/' . $image_file)) {
                unlink(ROOT_PATH . 'assets/images/campagnes/' . $image_file);
            }

            set_flash_message("Campagne supprimée avec succès.", "success");
        } catch (PDOException $e) {
            set_flash_message("Erreur lors de la suppression de la campagne: " . $e->getMessage(), "danger");
        }
    } else {
        set_flash_message("ID de campagne manquant pour la suppression.", "warning");
    }
    redirect('campagnes.php'); // Recharger pour voir les changements
}


// Récupération des campagnes
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$statut_filter = isset($_GET['statut']) ? sanitize($_GET['statut']) : '';

$sql = "SELECT c.*, cat.nom_categorie, b.nom AS nom_beneficiaire, b.prenom AS prenom_beneficiaire
        FROM campagnes c
        LEFT JOIN categories cat ON c.categorie_id = cat.id
        LEFT JOIN beneficiaires b ON c.beneficiaire_id = b.id";
$params = [];
$whereClauses = [];

if (!empty($search)) {
    $whereClauses[] = "(c.titre LIKE :search OR c.description LIKE :search OR cat.nom_categorie LIKE :search)";
    $params[':search'] = "%$search%";
}
if (!empty($statut_filter)) {
    $whereClauses[] = "c.statut = :statut";
    $params[':statut'] = $statut_filter;
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}
$sql .= " ORDER BY c.date_creation DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$campagnes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Créez un fichier `config/config.php` pour l'admin ou décommentez les require_*
// Pour cet exemple, je vais simuler `config.php`
// config/constants.php
define('CURRENCY_SYMBOL', 'FCFA'); // ou '₣', ou 'F CFA', selon votre préférence

if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__) . '/');
if (!defined('BASE_URL')) define('BASE_URL', '/e_social/'); // Adaptez
require_once ROOT_PATH . 'config/db.php';
require_once ROOT_PATH . 'includes/fonctions.php';

?>

<div class="page-title-container">
    <h1><i class="fas fa-bullhorn"></i> Gestion des Campagnes</h1>
    <a href="<?= BASE_URL ?>admin/ajouter_campagne.php" class="btn btn-admin-success">
        <i class="fas fa-plus-circle"></i> Ajouter une Campagne
    </a>
</div>

<?php display_flash_message(); ?>
<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<style>
    .page-title-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(90deg, #6a11cb 0%, #2575fc 100%);
    color: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.page-title-container h1 {
    font-size: 2rem;
    margin: 0;
}
.page-title-container .btn {
    font-weight: bold;
    background-color: white;
    color: #2575fc;
    border: none;
    transition: all 0.3s ease;
}
.page-title-container .btn:hover {
    background-color: #f0f0f0;
    color: #6a11cb;
}

.card-admin {
    border: 1px solid #ddd;
    border-radius: 12px;
    margin-bottom: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}
.card-admin-header {
    background-color: #f8f9fa;
    padding: 15px 20px;
    font-weight: bold;
    font-size: 1.2rem;
    border-bottom: 1px solid #ddd;
    display: flex;
    align-items: center;
}
.card-admin-header i {
    margin-right: 10px;
    color: #2575fc;
}
.form-label-admin {
    font-weight: bold;
    color: #555;
}
.form-control-admin {
    border-radius: 8px;
    border: 1px solid #ccc;
}
.btn-admin-primary {
    background-color: #2575fc;
    border: none;
    color: white;
    font-weight: bold;
    border-radius: 8px;
}
.btn-admin-primary:hover {
    background-color: #1a5bcc;
}
.btn-admin-success {
    background-color: #28a745;
    color: white;
    font-weight: bold;
    border-radius: 8px;
}
.btn-admin-success:hover {
    background-color: #218838;
}
.btn-admin-info {
    background-color: #17a2b8;
    border: none;
    color: white;
}
.btn-admin-danger {
    background-color: #dc3545;
    border: none;
    color: white;
}
.img-thumbnail-small {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
}
.badge-status {
    padding: 5px 10px;
    border-radius: 30px;
    font-weight: 500;
    text-transform: capitalize;
    font-size: 0.9rem;
}
.status-en-cours {
    background-color: #e3f2fd;
    color: #0d6efd;
}
.status-terminee {
    background-color: #e9f7ef;
    color: #198754;
}
.status-suspendue {
    background-color: #fce8e6;
    color: #dc3545;
}
.action-buttons .btn {
    margin-left: 5px;
}
.progress-bar {
    transition: width 0.6s ease;
}

</style>
<div class="card card-admin">
    <div class="card-admin-header">
        <i class="fas fa-filter"></i> Filtres et Recherche
    </div>
    <div class="card-body">
        <form method="GET" action="campagnes.php" class="row g-3">
            <div class="col-md-6">
                <label for="search" class="form-label-admin">Rechercher</label>
                <input type="text" class="form-control form-control-admin" id="search" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Titre, description, catégorie...">
            </div>
            <div class="col-md-4">
                <label for="statut" class="form-label-admin">Statut</label>
                <select class="form-select form-control-admin" id="statut" name="statut">
                    <option value="">Tous les statuts</option>
                    <option value="en cours" <?= ($statut_filter == 'en cours') ? 'selected' : '' ?>>En cours</option>
                    <option value="terminee" <?= ($statut_filter == 'terminee') ? 'selected' : '' ?>>Terminée</option>
                    <option value="suspendue" <?= ($statut_filter == 'suspendue') ? 'selected' : '' ?>>Suspendue</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-admin-primary w-100"><i class="fas fa-search"></i> Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-admin">
    <div class="card-admin-header">
        <i class="fas fa-list-ul"></i> Liste des Campagnes (<?= count($campagnes) ?>)
    </div>
    <div class="card-body p-0"> <?php // p-0 pour que la table colle aux bords de la card-body ?>
        <?php if (empty($campagnes)): ?>
            <div class="alert alert-info m-3 text-center">
                <i class="fas fa-info-circle me-2"></i> Aucune campagne trouvée
                <?= (!empty($search) || !empty($statut_filter)) ? ' avec les filtres actuels.' : '.' ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover table-admin mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Image</th>
                            <th>Titre</th>
                            <th>Catégorie</th>
                            <th>Bénéficiaire</th>
                            <th>Montant Visé</th>
                            <th>Montant Atteint</th>
                            <th>Statut</th>
                            <th>Date Création</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campagnes as $index => $campagne):
                            $pourcentage = ($campagne['montant_vise'] > 0) ? ($campagne['montant_atteint'] / $campagne['montant_vise']) * 100 : 0;
                            $pourcentage = min(100, $pourcentage); // Plafonner à 100%
                        ?>
                        <tr>
                            <td><?= $index + 1 ?></td>
                            <td>
                                <img src="<?= BASE_URL ?>assets/images/campagnes/<?= htmlspecialchars($campagne['image_campagne'] ?: 'default_campaign.png') ?>"
                                     alt="<?= htmlspecialchars($campagne['titre']) ?>" class="img-thumbnail-small"
                                     onerror="this.src='<?= BASE_URL ?>assets/images/campagnes/default_campaign.png'">
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>public/campagne.php?id=<?= (int)$campagne['id'] ?>" target="_blank" title="Voir la campagne">
                                    <?= htmlspecialchars($campagne['titre']) ?>
                                </a>
                            </td>
                            <td><?= htmlspecialchars($campagne['nom_categorie'] ?: 'N/A') ?></td>
                            <td><?= htmlspecialchars(($campagne['prenom_beneficiaire'] && $campagne['nom_beneficiaire']) ? $campagne['prenom_beneficiaire'] . ' ' . $campagne['nom_beneficiaire'] : 'N/A') ?></td>
                            <td><?= number_format($campagne['montant_vise'], 0, ',', ' ') ?> <?= CURRENCY_SYMBOL ?></td>
                            <td>
                                <?= number_format($campagne['montant_atteint'], 0, ',', ' ') ?> <?= CURRENCY_SYMBOL ?>
                                <div class="progress mt-1" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pourcentage ?>%;" aria-valuenow="<?= $pourcentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge-status status-<?= str_replace(' ', '-', strtolower($campagne['statut'] ?? '')) ?>">
    <?= ucfirst(str_replace('_', ' ', $campagne['statut'] ?? '')) ?>
</span>

                            </td>
                            <td><?= date("d/m/Y", strtotime($campagne['date_creation'])) ?></td>
                            <td class="text-end action-buttons">
                                <a href="<?= BASE_URL ?>admin/modifier_campagne.php?id=<?= (int)$campagne['id'] ?>" class="btn btn-admin-info btn-sm" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="campagnes.php" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette campagne ? Cette action est irréversible et supprimera les dons associés ou les déliera.');">
                                    <input type="hidden" name="action" value="delete_campagne">
                                    <input type="hidden" name="campagne_id" value="<?= (int)$campagne['id'] ?>">
                                    <button type="submit" class="btn btn-admin-danger btn-sm" title="Supprimer">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Ajouter une pagination ici si vous avez beaucoup de campagnes
?>

