<?php
$page_title = "Modifier la Campagne";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';

define('CURRENCY_SYMBOL', 'FCFA'); // ou '₣', ou 'F CFA', selon votre préférence
define('ROOT_PATH', realpath(dirname(__DIR__)));
function redirect($url) {
    header("Location: $url");
    exit;
}

$pdo = getPDO();
$campagne_id_get = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$campagne_id_get) {
    set_flash_message("ID de campagne invalide ou manquant.", "danger");
    redirect('campagnes.php');
}

// Récupérer les données actuelles de la campagne
try {
    $stmt = $pdo->prepare("SELECT * FROM campagnes WHERE id = :id");
    $stmt->execute([':id' => $campagne_id_get]);
    $campagne = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$campagne) {
        set_flash_message("Campagne non trouvée.", "danger");
        redirect('campagnes.php');
    }

    $stmt_categories = $pdo->query("SELECT id, nom_categorie FROM categories ORDER BY nom_categorie ASC");
    $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

    $stmt_beneficiaires = $pdo->query("SELECT id, prenom, nom FROM beneficiaires ORDER BY nom ASC, prenom ASC");
    $beneficiaires = $stmt_beneficiaires->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    set_flash_message("Erreur lors de la récupération des données: " . $e->getMessage(), "danger");
    redirect('campagnes.php');
}

// Initialisation des variables avec les données existantes
$titre = $campagne['titre'];
$description = $campagne['description'];
$montant_vise = $campagne['montant_vise'];
// Formater les dates pour les champs input type="date"
$date_debut_raw = !empty($campagne['date_debut']) ? date('Y-m-d', strtotime($campagne['date_debut'])) : '';
$date_fin_raw = !empty($campagne['date_fin']) ? date('Y-m-d', strtotime($campagne['date_fin'])) : '';
$categorie_id = $campagne['categorie_id'];
$beneficiaire_id = $campagne['beneficiaire_id'];
$statut = $campagne['statut'];
$current_image = $campagne['image_campagne'];
$errors = [];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = sanitize($_POST['titre'] ?? $campagne['titre']);
    $description = sanitize($_POST['description'] ?? $campagne['description'], true);
    $montant_vise = filter_var($_POST['montant_vise'] ?? $campagne['montant_vise'], FILTER_VALIDATE_FLOAT);
    $date_debut_raw_post = sanitize($_POST['date_debut'] ?? '');
    $date_fin_raw_post = sanitize($_POST['date_fin'] ?? '');
    $categorie_id = filter_var($_POST['categorie_id'] ?? $campagne['categorie_id'], FILTER_VALIDATE_INT);
    $beneficiaire_id = filter_var($_POST['beneficiaire_id'] ?? $campagne['beneficiaire_id'], FILTER_VALIDATE_INT);
    // Si beneficiaire_id est vide dans le POST mais existait, il faut le mettre à NULL
    if (isset($_POST['beneficiaire_id']) && empty($_POST['beneficiaire_id'])) {
        $beneficiaire_id = null;
    }

    $statut = in_array($_POST['statut'] ?? $campagne['statut'], ['en cours', 'terminee', 'suspendue']) ? $_POST['statut'] : $campagne['statut'];

    // Validation (similaire à ajouter_campagne.php, mais adaptée)
    if (empty($titre)) $errors['titre'] = "Le titre est requis.";
    if (empty($description)) $errors['description'] = "La description est requise.";
    if ($montant_vise === false || $montant_vise <= 0) $errors['montant_vise'] = "Le montant visé doit être un nombre positif.";
    if (empty($categorie_id)) $errors['categorie_id'] = "Veuillez sélectionner une catégorie.";

    $date_debut = null;
    $date_fin = null;
    if (!empty($date_debut_raw_post)) {
        $date_debut = (new DateTime($date_debut_raw_post))->format('Y-m-d');
    }
    if (!empty($date_fin_raw_post)) {
        $date_fin = (new DateTime($date_fin_raw_post))->format('Y-m-d');
    }

    if ($date_debut && $date_fin && ($date_fin < $date_debut)) {
        $errors['date_fin'] = "La date de fin doit être postérieure à la date de début.";
    }
    // Actualiser les variables pour le réaffichage du formulaire
    $date_debut_raw = $date_debut_raw_post;
    $date_fin_raw = $date_fin_raw_post;


    // Gestion de l'upload de la NOUVELLE image
    $image_campagne_name = $current_image; // Garder l'ancienne image par défaut
    if (isset($_FILES['image_campagne']) && $_FILES['image_campagne']['error'] == UPLOAD_ERR_OK) {
        $file_info = $_FILES['image_campagne'];
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_extension = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
        $max_file_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_extension, $allowed_extensions)) {
            $errors['image_campagne'] = "Format de fichier non autorisé.";
        } elseif ($file_info['size'] > $max_file_size) {
            $errors['image_campagne'] = "Le fichier est trop volumineux (max 5MB).";
        } else {
            $new_image_filename = uniqid('campagne_', true) . '.' . $file_extension;
            $upload_path = ROOT_PATH . '/assets/images/campagnes/' . $new_image_filename;
            if (move_uploaded_file($file_info['tmp_name'], $upload_path)) {
                // Supprimer l'ancienne image si elle existe et est différente de la nouvelle
                if ($current_image && file_exists(ROOT_PATH . '/assets/images/campagnes/' . $current_image)) {
                    unlink(ROOT_PATH . '/assets/images/campagnes/' . $current_image);
                }
                $image_campagne_name = $new_image_filename; // Mettre à jour avec le nom de la nouvelle image
            } else {
                $errors['image_campagne'] = "Erreur lors du téléchargement de la nouvelle image.";
            }
        }
    } elseif (isset($_FILES['image_campagne']) && $_FILES['image_campagne']['error'] != UPLOAD_ERR_NO_FILE) {
         $errors['image_campagne'] = "Erreur de téléchargement de l'image (code: ".$_FILES['image_campagne']['error'].").";
    }


    if (empty($errors)) {
        try {
            $sql = "UPDATE campagnes SET
                        titre = :titre,
                        description = :description,
                        montant_vise = :montant_vise,
                        date_debut = :date_debut,
                        date_fin = :date_fin,
                        categorie_id = :categorie_id,
                        beneficiaire_id = :beneficiaire_id,
                        statut = :statut,
                        image_campagne = :image_campagne
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':titre' => $titre,
                ':description' => $description,
                ':montant_vise' => $montant_vise,
                ':date_debut' => $date_debut,
                ':date_fin' => $date_fin,
                ':categorie_id' => $categorie_id,
                ':beneficiaire_id' => $beneficiaire_id, // Sera NULL si vide
                ':statut' => $statut,
                ':image_campagne' => $image_campagne_name,
                ':id' => $campagne_id_get
            ]);

            set_flash_message("Campagne mise à jour avec succès !", "success");
            redirect('campagnes.php');
        } catch (PDOException $e) {
            set_flash_message("Erreur lors de la mise à jour de la campagne: " . $e->getMessage(), "danger");
        }
    } else {
        set_flash_message("Veuillez corriger les erreurs dans le formulaire.", "warning");
        
    }
}


?>
<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
    .page-title-container {
    margin-bottom: 2rem;
    background-color: #f0f8ff;
    padding: 1.5rem;
    border-radius: 0.75rem;
    box-shadow: 0 0.25rem 1rem rgba(0, 0, 0, 0.05);
}

.page-title-container h1 {
    font-size: 1.75rem;
    margin-bottom: 0.5rem;
    color: #0d6efd;
}

.card-admin {
    border: none;
    border-radius: 1rem;
    overflow: hidden;
    box-shadow: 0 0.5rem 1.25rem rgba(0, 0, 0, 0.1);
}

.card-admin-header {
    background: linear-gradient(to right, #0d6efd, #6610f2);
    color: #fff;
    padding: 1rem 1.5rem;
    font-weight: bold;
    font-size: 1.1rem;
}

.form-label-admin {
    font-weight: 600;
    color: #333;
}

.form-control-admin {
    border-radius: 0.5rem;
    box-shadow: none;
}

.btn-admin-success {
    background-color: #198754;
    border-color: #198754;
    color: #fff;
    font-weight: 500;
}

.btn-admin-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
    color: #fff;
}

.btn-admin-success:hover,
.btn-admin-secondary:hover {
    opacity: 0.9;
}

.invalid-feedback {
    font-size: 0.875rem;
    color: #dc3545;
}
.card-body img:hover {
    transform: scale(1.05);
    transition: transform 0.3s ease-in-out;
}

</style>
<div class="page-title-container">
    <h1><i class="fas fa-edit text-info"></i> Modifier la Campagne : <?= htmlspecialchars($campagne['titre']) ?></h1>
     <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/index.php">Tableau de bord</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/campagnes.php">Campagnes</a></li>
            <li class="breadcrumb-item active" aria-current="page">Modifier</li>
        </ol>
    </nav>
</div>

<?php display_flash_message(); ?>

<div class="card card-admin">
    <div class="card-admin-header">
        <i class="fas fa-clipboard-list"></i> Informations de la Campagne
    </div>
    <div class="card-body">
        <form method="POST" action="modifier_campagne.php?id=<?= (int)$campagne_id_get ?>" enctype="multipart/form-data">
            <div class="row g-3">
                <div class="col-md-8">
                    <label for="titre" class="form-label-admin">Titre de la campagne <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-admin <?= isset($errors['titre']) ? 'is-invalid' : '' ?>" id="titre" name="titre" value="<?= htmlspecialchars($titre) ?>" required>
                    <?php if (isset($errors['titre'])): ?><div class="invalid-feedback"><?= $errors['titre'] ?></div><?php endif; ?>
                </div>
                <div class="col-md-4">
                    <label for="montant_vise" class="form-label-admin">Montant Visé (<?= CURRENCY_SYMBOL ?>) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control form-control-admin <?= isset($errors['montant_vise']) ? 'is-invalid' : '' ?>" id="montant_vise" name="montant_vise" value="<?= htmlspecialchars($montant_vise) ?>" required>
                    <?php if (isset($errors['montant_vise'])): ?><div class="invalid-feedback"><?= $errors['montant_vise'] ?></div><?php endif; ?>
                </div>

                <div class="col-12">
                    <label for="description" class="form-label-admin">Description détaillée <span class="text-danger">*</span></label>
                    <textarea class="form-control form-control-admin <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="description" name="description" rows="6" required><?= htmlspecialchars($description) ?></textarea>
                    <?php if (isset($errors['description'])): ?><div class="invalid-feedback"><?= $errors['description'] ?></div><?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="categorie_id" class="form-label-admin">Catégorie <span class="text-danger">*</span></label>
                    <select class="form-select form-control-admin <?= isset($errors['categorie_id']) ? 'is-invalid' : '' ?>" id="categorie_id" name="categorie_id" required>
                        <option value="">Sélectionner une catégorie...</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int)$cat['id'] ?>" <?= ($categorie_id == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nom_categorie']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['categorie_id'])): ?><div class="invalid-feedback"><?= $errors['categorie_id'] ?></div><?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="beneficiaire_id" class="form-label-admin">Bénéficiaire (Optionnel)</label>
                    <select class="form-select form-control-admin <?= isset($errors['beneficiaire_id']) ? 'is-invalid' : '' ?>" id="beneficiaire_id" name="beneficiaire_id">
                        <option value="">Aucun bénéficiaire direct / Cause générale</option>
                        <?php foreach ($beneficiaires as $ben): ?>
                            <option value="<?= (int)$ben['id'] ?>" <?= ((!empty($beneficiaire_id) && $beneficiaire_id == $ben['id'])) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ben['prenom'] . ' ' . $ben['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['beneficiaire_id'])): ?><div class="invalid-feedback"><?= $errors['beneficiaire_id'] ?></div><?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="date_debut" class="form-label-admin">Date de début (Optionnel)</label>
                    <input type="date" class="form-control form-control-admin <?= isset($errors['date_debut']) ? 'is-invalid' : '' ?>" id="date_debut" name="date_debut" value="<?= htmlspecialchars($date_debut_raw) ?>">
                    <?php if (isset($errors['date_debut'])): ?><div class="invalid-feedback"><?= $errors['date_debut'] ?></div><?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="date_fin" class="form-label-admin">Date de fin (Optionnel)</label>
                    <input type="date" class="form-control form-control-admin <?= isset($errors['date_fin']) ? 'is-invalid' : '' ?>" id="date_fin" name="date_fin" value="<?= htmlspecialchars($date_fin_raw) ?>">
                    <?php if (isset($errors['date_fin'])): ?><div class="invalid-feedback"><?= $errors['date_fin'] ?></div><?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="statut" class="form-label-admin">Statut</label>
                    <select class="form-select form-control-admin" id="statut" name="statut">
                        <option value="en cours" <?= ($statut == 'en cours') ? 'selected' : '' ?>>En cours</option>
                        <option value="terminee" <?= ($statut == 'terminee') ? 'selected' : '' ?>>Terminée</option>
                        <option value="suspendue" <?= ($statut == 'suspendue') ? 'selected' : '' ?>>Suspendue</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="image_campagne" class="form-label-admin">Nouvelle Image (Max 5MB) (Optionnel)</label>
                    <input class="form-control form-control-admin <?= isset($errors['image_campagne']) ? 'is-invalid' : '' ?>" type="file" id="image_campagne" name="image_campagne" accept="image/png, image/jpeg, image/gif, image/webp">
                    <?php if (isset($errors['image_campagne'])): ?><div class="invalid-feedback"><?= $errors['image_campagne'] ?></div><?php endif; ?>
                    <?php if ($current_image): ?>
                        <div class="mt-2">
                            <small class="form-text text-muted">Image actuelle : </small>
                            <img src="<?= BASE_URL ?>assets/images/campagnes/<?= htmlspecialchars($current_image) ?>" alt="Image actuelle" style="max-width: 100px; max-height: 70px; object-fit: cover; border-radius: 4px;">
                            <br><small class="form-text text-muted">Laissez vide pour conserver l'image actuelle.</small>
                        </div>
                    <?php else: ?>
                        <small class="form-text text-muted">Aucune image actuelle.</small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-admin-success me-2"><i class="fas fa-save"></i> Enregistrer les Modifications</button>
                <a href="campagnes.php" class="btn btn-admin-secondary"><i class="fas fa-times-circle"></i> Annuler</a>
            </div>
        </form>
    </div>
</div>

<?php

?>