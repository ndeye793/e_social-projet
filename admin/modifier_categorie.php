<?php
$page_title = "Modifier la Catégorie";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';

define('CURRENCY_SYMBOL', 'FCFA'); // ou '₣', ou 'F CFA', selon votre préférence

function redirect($url) {
    header("Location: $url");
    exit;
}

$pdo = getPDO();
$categorie_id_get = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$categorie_id_get) {
    set_flash_message("ID de catégorie invalide.", "danger");
    redirect('categories.php');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
    $stmt->execute([':id' => $categorie_id_get]);
    $categorie = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$categorie) {
        set_flash_message("Catégorie non trouvée.", "danger");
        redirect('categories.php');
    }
} catch (PDOException $e) {
    set_flash_message("Erreur: " . $e->getMessage(), "danger");
    redirect('categories.php');
}

$nom_categorie = $categorie['nom_categorie'];
$description = $categorie['description'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_categorie_post = sanitize($_POST['nom_categorie'] ?? $categorie['nom_categorie']);
    $description_post = sanitize($_POST['description'] ?? $categorie['description'], true);

    if (empty($nom_categorie_post)) $errors['nom_categorie'] = "Le nom est requis.";
    // Vérifier l'unicité si le nom a changé
    if ($nom_categorie_post !== $categorie['nom_categorie']) {
        $stmtCheck = $pdo->prepare("SELECT id FROM categories WHERE nom_categorie = :nom AND id != :current_id");
        $stmtCheck->execute([':nom' => $nom_categorie_post, ':current_id' => $categorie_id_get]);
        if ($stmtCheck->fetch()) {
            $errors['nom_categorie'] = "Une autre catégorie avec ce nom existe déjà.";
        }
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE categories SET nom_categorie = :nom, description = :desc WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom' => $nom_categorie_post,
                ':desc' => !empty($description_post) ? $description_post : null,
                ':id' => $categorie_id_get
            ]);
            set_flash_message("Catégorie mise à jour !", "success");
            redirect('categories.php');
        } catch (PDOException $e) {
            set_flash_message("Erreur: " . $e->getMessage(), "danger");
        }
    } else {
        // Réassigner les valeurs postées pour le réaffichage du formulaire en cas d'erreur
        $nom_categorie = $nom_categorie_post;
        $description = $description_post;
        set_flash_message("Veuillez corriger les erreurs.", "warning");
    }
}

?>
<style>
    /* Styles pour modifier_categorie.php (hérite beaucoup de ajouter_categorie.php) */
    .form-section-title {
        font-family: 'Poppins', sans-serif; font-size: 1.3rem; font-weight: 600;
        color: var(--category-icon-color); margin-top: 1.5rem; margin-bottom: 1rem;
        padding-bottom: 0.5rem; border-bottom: 2px solid #BE90D4;
        display: inline-block;
    }
    .form-section-title i { margin-right: 10px; }

    .card-form-category {
        background: #fff; border: 1px solid #e0d6e8; border-radius: 12px;
        box-shadow: 0 8px 25px rgba(142, 68, 173, 0.08);
    }
    .card-form-category .card-admin-header {
         color: var(--category-icon-color);
         border-bottom-color: #e0d6e8;
         background-color: var(--category-table-header-bg);
    }
    .card-form-category .form-control-admin:focus {
        border-color: var(--category-icon-color);
        box-shadow: 0 0 0 0.2rem rgba(142, 68, 173, .25);
    }
    .btn-submit-category {
        background: linear-gradient(45deg, var(--category-icon-color), #9B59B6);
        border: none; color: white; font-weight:600;
    }
    .btn-submit-category:hover { opacity: 0.9; color:white; transform: scale(1.02); }
</style>
<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<div class="page-title-container">
    <h1><i class="fas fa-edit" style="color:var(--category-icon-color);"></i> Modifier la Catégorie : <?= htmlspecialchars($categorie['nom_categorie']) ?></h1>
     <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/index.php">Tableau de bord</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/categories.php">Catégories</a></li>
            <li class="breadcrumb-item active" aria-current="page">Modifier</li>
        </ol>
    </nav>
</div>

<?php display_flash_message(); ?>

<div class="card card-admin card-form-category">
    <div class="card-admin-header">
        <i class="fas fa-pencil-alt"></i> Informations de la Catégorie
    </div>
    <div class="card-body p-4">
        <form method="POST" action="modifier_categorie.php?id=<?= (int)$categorie_id_get ?>">
            <h5 class="form-section-title"><i class="fas fa-tag"></i> Détails</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-12">
                    <label for="nom_categorie" class="form-label-admin">Nom de la Catégorie <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-admin <?= isset($errors['nom_categorie']) ? 'is-invalid' : '' ?>" id="nom_categorie" name="nom_categorie" value="<?= htmlspecialchars($nom_categorie) ?>" required>
                    <?php if (isset($errors['nom_categorie'])): ?><div class="invalid-feedback"><?= $errors['nom_categorie'] ?></div><?php endif; ?>
                </div>
                 <div class="col-12">
                    <label for="description" class="form-label-admin">Description (Optionnel)</label>
                    <textarea class="form-control form-control-admin <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="description" name="description" rows="3"><?= htmlspecialchars($description) ?></textarea>
                    <?php if (isset($errors['description'])): ?><div class="invalid-feedback"><?= $errors['description'] ?></div><?php endif; ?>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-submit-category btn-lg me-2"><i class="fas fa-save"></i> Enregistrer Modifications</button>
                <a href="categories.php" class="btn btn-admin-secondary btn-lg"><i class="fas fa-times-circle"></i> Annuler</a>
            </div>
        </form>
    </div>
</div>

