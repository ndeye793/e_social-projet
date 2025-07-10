<?php
$page_title = "Ajouter une Catégorie";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';

define('CURRENCY_SYMBOL', 'FCFA'); // ou '₣', ou 'F CFA', selon votre préférence

function redirect($url) {
    header("Location: $url");
    exit;
}
$pdo = getPDO();

$nom_categorie = '';
$description = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_categorie = sanitize($_POST['nom_categorie'] ?? '');
    $description = sanitize($_POST['description'] ?? '', true);

    if (empty($nom_categorie)) $errors['nom_categorie'] = "Le nom de la catégorie est requis.";
    // Vérifier l'unicité du nom de catégorie (optionnel mais recommandé)
    if (empty($errors['nom_categorie'])) {
        $stmtCheck = $pdo->prepare("SELECT id FROM categories WHERE nom_categorie = :nom");
        $stmtCheck->execute([':nom' => $nom_categorie]);
        if ($stmtCheck->fetch()) {
            $errors['nom_categorie'] = "Une catégorie avec ce nom existe déjà.";
        }
    }


    if (empty($errors)) {
        try {
            $sql = "INSERT INTO categories (nom_categorie, description) VALUES (:nom, :desc)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom' => $nom_categorie,
                ':desc' => !empty($description) ? $description : null
            ]);
            set_flash_message("Nouvelle catégorie ajoutée avec succès !", "success");
            redirect('categories.php');
        } catch (PDOException $e) {
            set_flash_message("Erreur lors de l'ajout de la catégorie: " . $e->getMessage(), "danger");
        }
    } else {
        set_flash_message("Veuillez corriger les erreurs.", "warning");
    }
}

require_once ROOT_PATH . 'includes/header_admin.php';
?>
<style>
    /* Styles pour ajouter_categorie.php */
    .form-section-title {
        font-family: 'Poppins', sans-serif; font-size: 1.3rem; font-weight: 600;
        color: var(--category-icon-color); margin-top: 1.5rem; margin-bottom: 1rem;
        padding-bottom: 0.5rem; border-bottom: 2px solid #BE90D4; /* Violet plus clair pour la bordure */
        display: inline-block;
    }
    .form-section-title i { margin-right: 10px; }

    .card-form-category {
        background: #fff; border: 1px solid #e0d6e8; border-radius: 12px;
        box-shadow: 0 8px 25px rgba(142, 68, 173, 0.08); /* Ombre violette subtile */
    }
    .card-form-category .card-admin-header {
         color: var(--category-icon-color);
         border-bottom-color: #e0d6e8;
         background-color: var(--category-table-header-bg); /* Fond assorti au tableau */
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
    <h1><i class="fas fa-folder-plus" style="color:var(--category-icon-color);"></i> Ajouter une Nouvelle Catégorie</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/index.php">Tableau de bord</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/categories.php">Catégories</a></li>
            <li class="breadcrumb-item active" aria-current="page">Ajouter</li>
        </ol>
    </nav>
</div>

<?php display_flash_message(); ?>

<div class="card card-admin card-form-category">
    <div class="card-admin-header">
        <i class="fas fa-pencil-alt"></i> Informations de la Catégorie
    </div>
    <div class="card-body p-4">
        <form method="POST" action="ajouter_categorie.php">
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
                    <small class="form-text text-muted">Une brève description pour clarifier le type de campagnes concernées.</small>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-submit-category btn-lg me-2"><i class="fas fa-save"></i> Enregistrer Catégorie</button>
                <a href="categories.php" class="btn btn-admin-secondary btn-lg"><i class="fas fa-times-circle"></i> Annuler</a>
            </div>
        </form>
    </div>
</div>

