<?php
$page_title = "Ajouter une Nouvelle Campagne";
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

// Récupérer les catégories et les bénéficiaires pour les listes déroulantes
try {
    $stmt_categories = $pdo->query("SELECT id, nom_categorie FROM categories ORDER BY nom_categorie ASC");
    $categories = $stmt_categories->fetchAll(PDO::FETCH_ASSOC);

    // On ne récupère que les bénéficiaires 'validé' ou 'en attente' pour une nouvelle campagne
    $stmt_beneficiaires = $pdo->query("SELECT id, prenom, nom FROM beneficiaires WHERE statut IN ('en attente', 'validé') ORDER BY nom ASC, prenom ASC");
    $beneficiaires = $stmt_beneficiaires->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_flash_message("Erreur lors de la récupération des données pour le formulaire: " . $e->getMessage(), "danger");
    $categories = [];
    $beneficiaires = [];
}


// Initialisation des variables pour le formulaire
$titre = '';
$description = '';
$montant_vise = '';
$date_debut = '';
$date_fin = '';
$categorie_id = '';
$beneficiaire_id = '';
$statut = 'en cours'; // Statut par défaut
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et validation des données du formulaire
    $titre = sanitize($_POST['titre'] ?? '');
    $description = sanitize($_POST['description'] ?? '', true); // Permettre certaines balises HTML si éditeur riche
    $montant_vise = filter_var($_POST['montant_vise'] ?? '', FILTER_VALIDATE_FLOAT);
    $date_debut_raw = sanitize($_POST['date_debut'] ?? '');
    $date_fin_raw = sanitize($_POST['date_fin'] ?? '');
    $categorie_id = filter_var($_POST['categorie_id'] ?? '', FILTER_VALIDATE_INT);
    $beneficiaire_id = filter_var($_POST['beneficiaire_id'] ?? '', FILTER_VALIDATE_INT);
    $statut = in_array($_POST['statut'] ?? '', ['en cours', 'suspendue']) ? $_POST['statut'] : 'en cours';

    // Validation
    if (empty($titre)) $errors['titre'] = "Le titre est requis.";
    if (empty($description)) $errors['description'] = "La description est requise.";
    if ($montant_vise === false || $montant_vise <= 0) $errors['montant_vise'] = "Le montant visé doit être un nombre positif.";
    if (empty($categorie_id)) $errors['categorie_id'] = "Veuillez sélectionner une catégorie.";
    // $beneficiaire_id est optionnel au début, mais si sélectionné, il doit être valide.
    // La date de fin doit être après la date de début
    if (!empty($date_debut_raw) && !empty($date_fin_raw)) {
        $date_debut_obj = new DateTime($date_debut_raw);
        $date_fin_obj = new DateTime($date_fin_raw);
        if ($date_fin_obj <= $date_debut_obj) {
            $errors['date_fin'] = "La date de fin doit être postérieure à la date de début.";
        }
        $date_debut = $date_debut_obj->format('Y-m-d');
        $date_fin = $date_fin_obj->format('Y-m-d');
    } elseif (!empty($date_debut_raw)) {
        $date_debut = (new DateTime($date_debut_raw))->format('Y-m-d');
    } elseif (!empty($date_fin_raw)) {
         $errors['date_debut'] = "La date de début est requise si une date de fin est fournie.";
    }


    // Gestion de l'upload de l'image
    $image_campagne_name = null;
    if (isset($_FILES['image_campagne']) && $_FILES['image_campagne']['error'] == UPLOAD_ERR_OK) {
        $file_info = $_FILES['image_campagne'];
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_extension = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
        $max_file_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file_extension, $allowed_extensions)) {
            $errors['image_campagne'] = "Format de fichier non autorisé. Uniquement JPG, JPEG, PNG, GIF, WEBP.";
        } elseif ($file_info['size'] > $max_file_size) {
            $errors['image_campagne'] = "Le fichier est trop volumineux (max 5MB).";
        } else {
            $image_campagne_name = uniqid('campagne_', true) . '.' . $file_extension;
            $upload_path = ROOT_PATH . '/assets/images/campagnes/' . $image_campagne_name;
            if (!move_uploaded_file($file_info['tmp_name'], $upload_path)) {
                $errors['image_campagne'] = "Erreur lors du téléchargement de l'image.";
                $image_campagne_name = null; // Réinitialiser si l'upload échoue
            }
        }
    } elseif (isset($_FILES['image_campagne']) && $_FILES['image_campagne']['error'] != UPLOAD_ERR_NO_FILE) {
        $errors['image_campagne'] = "Erreur de téléchargement de l'image (code: ".$_FILES['image_campagne']['error'].").";
    }


    if (empty($errors)) {
        try {
            $sql = "INSERT INTO campagnes (titre, description, montant_vise, date_debut, date_fin, categorie_id, beneficiaire_id, statut, image_campagne, date_creation)
                    VALUES (:titre, :description, :montant_vise, :date_debut, :date_fin, :categorie_id, :beneficiaire_id, :statut, :image_campagne, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':titre' => $titre,
                ':description' => $description,
                ':montant_vise' => $montant_vise,
                ':date_debut' => !empty($date_debut) ? $date_debut : null,
                ':date_fin' => !empty($date_fin) ? $date_fin : null,
                ':categorie_id' => $categorie_id,
                ':beneficiaire_id' => !empty($beneficiaire_id) ? $beneficiaire_id : null,
                ':statut' => $statut,
                ':image_campagne' => $image_campagne_name
            ]);

            set_flash_message("Nouvelle campagne ajoutée avec succès !", "success");
            redirect('campagnes.php');
        } catch (PDOException $e) {
            // Si erreur BDD et image uploadée, la supprimer pour éviter les orphelins
            if ($image_campagne_name && file_exists(ROOT_PATH . 'assets/images/campagnes/' . $image_campagne_name)) {
                unlink(ROOT_PATH . 'assets/images/campagnes/' . $image_campagne_name);
            }
            set_flash_message("Erreur lors de l'ajout de la campagne: " . $e->getMessage(), "danger");
        }
    } else {
        // Si erreurs de validation et image uploadée, la supprimer
        if ($image_campagne_name && file_exists(ROOT_PATH . 'assets/images/campagnes/' . $image_campagne_name)) {
            unlink(ROOT_PATH . 'assets/images/campagnes/' . $image_campagne_name);
        }
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
        background-color: #f0f9f5;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 30px;
        border-left: 5px solid #198754;
    }

    .page-title-container h1 {
        font-size: 1.8rem;
        margin-bottom: 10px;
    }

    .card-admin {
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        border-radius: 12px;
    }

    .card-admin-header {
        background-color: #198754;
        color: #fff;
        font-weight: bold;
        padding: 15px 20px;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-label-admin {
        font-weight: 600;
        color: #333;
    }

    .form-control-admin {
        border-radius: 8px;
        padding: 10px;
        border-color: #ced4da;
        font-size: 0.95rem;
    }

    .btn-admin-success {
        background-color: #198754;
        border-color: #198754;
        color: #fff;
        font-weight: 600;
        border-radius: 8px;
    }

    .btn-admin-success:hover {
        background-color: #157347;
    }

    .btn-admin-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
        color: #fff;
        font-weight: 600;
        border-radius: 8px;
    }

    .btn-admin-secondary:hover {
        background-color: #5c636a;
    }

    .breadcrumb {
        background: transparent;
        padding: 0;
        margin-bottom: 0;
    }

    .breadcrumb-item a {
        color: #198754;
        font-weight: 500;
    }

    .breadcrumb-item.active {
        color: #6c757d;
    }

    .invalid-feedback {
        font-size: 0.85rem;
    }

    textarea.form-control-admin {
        resize: vertical;
    }
</style>


<div class="page-title-container">
    <h1><i class="fas fa-plus-circle text-success"></i> Ajouter une Nouvelle Campagne</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/index.php">Tableau de bord</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/campagnes.php">Campagnes</a></li>
            <li class="breadcrumb-item active" aria-current="page">Ajouter</li>
        </ol>
    </nav>
</div>

<?php display_flash_message(); ?>

<div class="card card-admin">
    <div class="card-admin-header">
        <i class="fas fa-clipboard-list"></i> Informations de la Campagne
    </div>
    <div class="card-body">
        <form method="POST" action="ajouter_campagne.php" enctype="multipart/form-data">
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
                    <small class="form-text text-muted">Décrivez clairement l'objectif, le besoin et l'impact attendu.</small>
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
                            <option value="<?= (int)$ben['id'] ?>" <?= ($beneficiaire_id == $ben['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ben['prenom'] . ' ' . $ben['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['beneficiaire_id'])): ?><div class="invalid-feedback"><?= $errors['beneficiaire_id'] ?></div><?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label for="date_debut" class="form-label-admin">Date de début (Optionnel)</label>
                    <input type="date" class="form-control form-control-admin <?= isset($errors['date_debut']) ? 'is-invalid' : '' ?>" 
       id="date_debut" name="date_debut" 
       value="<?= isset($date_debut_raw) ? htmlspecialchars($date_debut_raw) : '' ?>">
                </div>

                <div class="col-md-6">
                    <label for="date_fin" class="form-label-admin">Date de fin (Optionnel)</label>
                    <input type="date" class="form-control form-control-admin <?= isset($errors['date_fin']) ? 'is-invalid' : '' ?>" 
       id="date_fin" name="date_fin" 
       value="<?= isset($date_fin_raw) ? htmlspecialchars($date_fin_raw) : '' ?>">
                </div>

                <div class="col-md-6">
                    <label for="statut" class="form-label-admin">Statut initial</label>
                    <select class="form-select form-control-admin" id="statut" name="statut">
                        <option value="en cours" <?= ($statut == 'en cours') ? 'selected' : '' ?>>En cours</option>
                        <option value="suspendue" <?= ($statut == 'suspendue') ? 'selected' : '' ?>>Suspendue (brouillon)</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="image_campagne" class="form-label-admin">Image de la campagne (Max 5MB)</label>
                    <input class="form-control form-control-admin <?= isset($errors['image_campagne']) ? 'is-invalid' : '' ?>" type="file" id="image_campagne" name="image_campagne" accept="image/png, image/jpeg, image/gif, image/webp">
                    <?php if (isset($errors['image_campagne'])): ?><div class="invalid-feedback"><?= $errors['image_campagne'] ?></div><?php endif; ?>
                    <small class="form-text text-muted">Une image attrayante augmente l'engagement.</small>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-admin-success me-2"><i class="fas fa-save"></i> Enregistrer la Campagne</button>
                <a href="campagnes.php" class="btn btn-admin-secondary"><i class="fas fa-times-circle"></i> Annuler</a>
            </div>
        </form>
    </div>
</div>


