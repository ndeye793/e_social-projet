<?php
$page_title = "Gestion des Catégories de Campagnes";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';

define('CURRENCY_SYMBOL', 'FCFA'); // ou '₣', ou 'F CFA', selon votre préférence

function redirect($url) {
    header("Location: $url");
    exit;
}

$pdo = getPDO();

// Logique de suppression de catégorie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_categorie') {
    if (isset($_POST['categorie_id']) && !empty($_POST['categorie_id'])) {
        $categorie_id_to_delete = (int)$_POST['categorie_id'];
        try {
            // Avant de supprimer, vérifier si des campagnes utilisent cette catégorie
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM campagnes WHERE categorie_id = :id");
            $stmtCheck->execute([':id' => $categorie_id_to_delete]);
            if ($stmtCheck->fetchColumn() > 0) {
                // Option 1: Empêcher la suppression
                set_flash_message("Impossible de supprimer la catégorie car elle est utilisée par des campagnes. Veuillez d'abord assigner ces campagnes à une autre catégorie ou les supprimer.", "warning");
                // Option 2: Mettre categorie_id à NULL dans les campagnes (si votre BDD le permet et que c'est logique)
                // $stmtUpdateCampagnes = $pdo->prepare("UPDATE campagnes SET categorie_id = NULL WHERE categorie_id = :id");
                // $stmtUpdateCampagnes->execute([':id' => $categorie_id_to_delete]);
                // $stmtDelete = $pdo->prepare("DELETE FROM categories WHERE id = :id");
                // $stmtDelete->execute([':id' => $categorie_id_to_delete]);
                // set_flash_message("Catégorie supprimée. Les campagnes associées ont été déliées.", "success");
            } else {
                // Aucune campagne n'utilise cette catégorie, on peut la supprimer
                $stmtDelete = $pdo->prepare("DELETE FROM categories WHERE id = :id");
                $stmtDelete->execute([':id' => $categorie_id_to_delete]);
                set_flash_message("Catégorie supprimée avec succès.", "success");
            }
        } catch (PDOException $e) {
            set_flash_message("Erreur lors de la suppression de la catégorie: " . $e->getMessage(), "danger");
        }
    } else {
        set_flash_message("ID de catégorie manquant pour la suppression.", "warning");
    }
    redirect('categories.php');
}


// Récupération des catégories
$search_categorie = isset($_GET['search_categorie']) ? sanitize($_GET['search_categorie']) : '';

$sql_categories = "SELECT c.id, c.nom_categorie, c.description, COUNT(camp.id) as nombre_campagnes
                   FROM categories c
                   LEFT JOIN campagnes camp ON c.id = camp.categorie_id";
$params_categories = [];

if (!empty($search_categorie)) {
    $sql_categories .= " WHERE (c.nom_categorie LIKE :search OR c.description LIKE :search)";
    $params_categories[':search'] = "%$search_categorie%";
}
$sql_categories .= " GROUP BY c.id, c.nom_categorie, c.description ORDER BY c.nom_categorie ASC";

$stmt_categories = $pdo->prepare($sql_categories);
$stmt_categories->execute($params_categories);
$categories_data = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);


?>
<style>
    /* Styles spécifiques pour categories.php */
    :root {
        --category-icon-color: #8E44AD; /* Violet pour les catégories */
        --category-table-header-bg: #f4f0f7; /* Violet très clair */
        --category-row-hover-bg: #faf7fc;
        --category-badge-bg: #e8daef;
        --category-badge-text: #5B2C6F;
    }

    .page-title-container h1 i { color: var(--category-icon-color); }

    .btn-add-category {
        background: linear-gradient(45deg, var(--category-icon-color), #9B59B6); /* Dégradé de violets */
        border: none;
        color: white;
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(142, 68, 173, 0.3);
        transition: all 0.3s ease;
    }
    .btn-add-category:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 6px 20px rgba(142, 68, 173, 0.5);
        color:white;
    }

    .category-table-container {
        background-color: #fff;
        border-radius: 10px;
        box-shadow: 0 6px 20px rgba(0,0,0,0.07);
        overflow: hidden;
    }

    .table-categories th {
        font-family: 'Poppins', sans-serif;
        font-weight: 600;
        background-color: var(--category-table-header-bg);
        color: var(--category-icon-color);
        border-bottom-width: 1px;
        border-color: #e0d6e8; /* Bordure assortie */
        padding: 0.9rem 1rem;
        text-transform: uppercase;
        font-size: 0.85em;
        letter-spacing: 0.5px;
    }
    .table-categories td {
        vertical-align: middle;
        padding: 0.9rem 1rem;
        color: #5a5c69;
    }
    .table-categories tbody tr:hover {
        background-color: var(--category-row-hover-bg);
    }
    .table-categories .category-name {
        font-weight: 500;
        color: var(--admin-primary); /* Couleur de texte admin */
    }
    .table-categories .category-description {
        font-size: 0.9em;
        color: #7f8c8d;
        max-width: 400px; /* Limiter la largeur pour la lisibilité */
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .table-categories .category-description:hover { /* Voir plus au survol */
        white-space: normal;
        overflow: visible;
    }
    .table-categories .campaign-count-badge {
        background-color: var(--category-badge-bg);
        color: var(--category-badge-text);
        padding: 0.3em 0.6em;
        border-radius: 50px; /* Badge rond/ovale */
        font-size: 0.8em;
        font-weight: 500;
    }

    .filter-bar-categories .form-control-admin:focus {
        border-color: var(--category-icon-color);
        box-shadow: 0 0 0 0.2rem rgba(142, 68, 173, .25);
    }
    .filter-bar-categories .btn-admin-primary {
        background-color: var(--category-icon-color);
        border-color: var(--category-icon-color);
    }
     .filter-bar-categories .btn-admin-primary:hover {
        background-color: #7D3C98;
        border-color: #7D3C98;
    }
</style>
<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<div class="page-title-container">
    <h1><i class="fas fa-tags"></i> Gestion des Catégories</h1>
    <a href="<?= BASE_URL ?>admin/ajouter_categorie.php" class="btn btn-add-category">
        <i class="fas fa-plus-circle"></i> Ajouter une Catégorie
    </a>
</div>

<?php display_flash_message(); ?>

<div class="card card-admin filter-bar-categories">
    <div class="card-admin-header">
        <i class="fas fa-search"></i> Rechercher une Catégorie
    </div>
    <div class="card-body">
        <form method="GET" action="categories.php" class="row g-3">
            <div class="col-md-10">
                <label for="search_categorie" class="form-label-admin">Nom ou description...</label>
                <input type="text" class="form-control form-control-admin" id="search_categorie" name="search_categorie" value="<?= htmlspecialchars($search_categorie) ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-admin-primary w-100"><i class="fas fa-search"></i> Filtrer</button>
            </div>
        </form>
    </div>
</div>

<div class="category-table-container mt-4">
    <div class="card-admin-header d-flex justify-content-between align-items-center" style="border-bottom:none; border-top-left-radius: 10px; border-top-right-radius: 10px;">
        <span><i class="fas fa-stream"></i> Liste des Catégories (<?= count($categories_data) ?>)</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-categories mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nom de la Catégorie</th>
                    <th>Description</th>
                    <th class="text-center">Campagnes Associées</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories_data)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4">
                            <i class="fas fa-tag fa-2x text-muted mb-2"></i><br>
                            Aucune catégorie trouvée <?= !empty($search_categorie) ? 'avec les filtres actuels.' : '.' ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($categories_data as $index => $cat): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>
                        <td class="category-name"><?= htmlspecialchars($cat['nom_categorie']) ?></td>
                        <td class="category-description" title="<?= htmlspecialchars($cat['description'] ?: 'Aucune description') ?>">
                            <?= htmlspecialchars($cat['description'] ?: 'N/A') ?>
                        </td>
                        <td class="text-center">
                            <span class="campaign-count-badge">
                                <?= (int)$cat['nombre_campagnes'] ?>
                            </span>
                        </td>
                        <td class="text-end action-buttons">
                            <a href="<?= BASE_URL ?>admin/modifier_categorie.php?id=<?= (int)$cat['id'] ?>" class="btn btn-admin-info btn-sm" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="categories.php" style="display:inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ? <?= ($cat['nombre_campagnes'] > 0) ? "Elle est utilisée par " . $cat['nombre_campagnes'] . " campagne(s) et ne pourra pas être supprimée si des campagnes l\'utilisent activement." : "" ?>');">
                                <input type="hidden" name="action" value="delete_categorie">
                                <input type="hidden" name="categorie_id" value="<?= (int)$cat['id'] ?>">
                                <button type="submit" class="btn btn-admin-danger btn-sm" title="Supprimer">
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

