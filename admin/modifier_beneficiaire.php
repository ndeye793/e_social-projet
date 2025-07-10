<?php
$page_title = "Modifier le Bénéficiaire";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';

define('CURRENCY_SYMBOL', 'FCFA'); // ou '₣', ou 'F CFA', selon votre préférence

function redirect($url) {
    header("Location: $url");
    exit;
}
$pdo = getPDO();
$beneficiaire_id_get = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$beneficiaire_id_get) {
    set_flash_message("ID de bénéficiaire invalide ou manquant.", "danger");
    redirect('beneficiaires.php');
}

// Récupérer les données actuelles du bénéficiaire
try {
    $stmt = $pdo->prepare("SELECT * FROM beneficiaires WHERE id = :id");
    $stmt->execute([':id' => $beneficiaire_id_get]);
    $beneficiaire = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$beneficiaire) {
        set_flash_message("Bénéficiaire non trouvé.", "danger");
        redirect('beneficiaires.php');
    }
} catch (PDOException $e) {
    set_flash_message("Erreur lors de la récupération des données du bénéficiaire: " . $e->getMessage(), "danger");
    redirect('beneficiaires.php');
}

// Initialisation avec les données existantes
$prenom = $beneficiaire['prenom'];
$nom = $beneficiaire['nom'];
$telephone = $beneficiaire['telephone'];
$email = $beneficiaire['email'];
$adresse = $beneficiaire['adresse'];
$situation = $beneficiaire['situation'];
$statut = $beneficiaire['statut'];
$current_identite_recto = $beneficiaire['identite_recto'];
$current_identite_verso = $beneficiaire['identite_verso'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = sanitize($_POST['prenom'] ?? $beneficiaire['prenom']);
    $nom = sanitize($_POST['nom'] ?? $beneficiaire['nom']);
    $telephone = sanitize($_POST['telephone'] ?? $beneficiaire['telephone']);
    $email = sanitize($_POST['email'] ?? $beneficiaire['email']);
    $adresse = sanitize($_POST['adresse'] ?? $beneficiaire['adresse']);
    $situation = sanitize($_POST['situation'] ?? $beneficiaire['situation'], true);
    $statut = in_array($_POST['statut'] ?? $beneficiaire['statut'], ['en attente', 'validé', 'aidé']) ? $_POST['statut'] : $beneficiaire['statut'];

    // Validation
    if (empty($prenom)) $errors['prenom'] = "Le prénom est requis.";
    if (empty($nom)) $errors['nom'] = "Le nom est requis.";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Format d'email invalide.";

    // Gestion upload NOUVELLE identité recto
    $identite_recto_name = $current_identite_recto;
    if (isset($_FILES['identite_recto']) && $_FILES['identite_recto']['error'] == UPLOAD_ERR_OK) {
        $upload_result_recto = upload_file($_FILES['identite_recto'], ROOT_PATH . 'uploads/identites/', ['jpg', 'jpeg', 'png', 'pdf'], 2097152, 'identite_recto_');
        if (is_array($upload_result_recto)) {
            $errors['identite_recto'] = implode(', ', $upload_result_recto);
        } else {
            // Supprimer l'ancien fichier si un nouveau est uploadé avec succès
            if ($current_identite_recto && file_exists(ROOT_PATH . 'uploads/identites/' . $current_identite_recto)) {
                unlink(ROOT_PATH . 'uploads/identites/' . $current_identite_recto);
            }
            $identite_recto_name = $upload_result_recto;
        }
    } elseif (isset($_FILES['identite_recto']) && $_FILES['identite_recto']['error'] != UPLOAD_ERR_NO_FILE) {
        $errors['identite_recto'] = "Erreur upload recto (code: ".$_FILES['identite_recto']['error'].")";
    }

    // Gestion upload NOUVELLE identité verso
    $identite_verso_name = $current_identite_verso;
    if (isset($_FILES['identite_verso']) && $_FILES['identite_verso']['error'] == UPLOAD_ERR_OK) {
        $upload_result_verso = upload_file($_FILES['identite_verso'], ROOT_PATH . 'uploads/identites/', ['jpg', 'jpeg', 'png', 'pdf'], 2097152, 'identite_verso_');
        if (is_array($upload_result_verso)) {
            $errors['identite_verso'] = implode(', ', $upload_result_verso);
        } else {
            if ($current_identite_verso && file_exists(ROOT_PATH . 'uploads/identites/' . $current_identite_verso)) {
                unlink(ROOT_PATH . 'uploads/identites/' . $current_identite_verso);
            }
            $identite_verso_name = $upload_result_verso;
        }
    } elseif (isset($_FILES['identite_verso']) && $_FILES['identite_verso']['error'] != UPLOAD_ERR_NO_FILE) {
        $errors['identite_verso'] = "Erreur upload verso (code: ".$_FILES['identite_verso']['error'].")";
    }


    if (empty($errors)) {
        try {
            $sql = "UPDATE beneficiaires SET
                        prenom = :prenom, nom = :nom, telephone = :telephone, email = :email,
                        adresse = :adresse, situation = :situation, statut = :statut,
                        identite_recto = :identite_recto, identite_verso = :identite_verso
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':prenom' => $prenom,
                ':nom' => $nom,
                ':telephone' => !empty($telephone) ? $telephone : null,
                ':email' => !empty($email) ? $email : null,
                ':adresse' => !empty($adresse) ? $adresse : null,
                ':situation' => !empty($situation) ? $situation : null,
                ':statut' => $statut,
                ':identite_recto' => $identite_recto_name,
                ':identite_verso' => $identite_verso_name,
                ':id' => $beneficiaire_id_get
            ]);
            set_flash_message("Bénéficiaire mis à jour avec succès !", "success");
            redirect('beneficiaires.php');
        } catch (PDOException $e) {
            set_flash_message("Erreur lors de la mise à jour du bénéficiaire: " . $e->getMessage(), "danger");
        }
    } else {
        set_flash_message("Veuillez corriger les erreurs dans le formulaire.", "warning");
        // Re-assigner les noms de fichiers actuels si les nouveaux uploads ont échoué mais que les anciens existent
        // (upload_file retourne un tableau d'erreurs en cas d'échec, sinon le nom du fichier)
        if (is_array($upload_result_recto ?? null)) $identite_recto_name = $current_identite_recto;
        if (is_array($upload_result_verso ?? null)) $identite_verso_name = $current_identite_verso;
    }
}


?>


<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
    /* Styles spécifiques pour modifier_beneficiaire.php (similaires à ajouter) */
    /* On hérite beaucoup des styles de ajouter_beneficiaire.php via les classes communes */
    .form-section-title {
        font-family: 'Poppins', sans-serif;
        font-size: 1.3rem;
        font-weight: 600;
        color: var(--benef-primary-deep); /* Utilisation d'une couleur de la palette bénéficiaire */
        margin-top: 2rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid var(--benef-primary-orange);
        display: inline-block;
    }
    .form-section-title i { margin-right: 10px; }

    .card-form-beneficiaire {
        background: linear-gradient(135deg, #ffffff 0%, var(--benef-neutral-sand) 100%);
        border: none; border-radius: 15px;
        box-shadow: 0 10px 40px rgba(140, 58, 0, 0.1);
    }
    .card-form-beneficiaire .card-admin-header {
         background: transparent;
         border-bottom-color: rgba(140, 58, 0, 0.2);
         color: var(--benef-primary-deep);
    }
    .card-form-beneficiaire .form-control-admin:focus {
        border-color: var(--benef-primary-orange);
        box-shadow: 0 0 0 0.2rem rgba(255, 107, 0, .25);
    }

    .btn-submit-benef {
        background: linear-gradient(45deg, var(--benef-primary-orange), var(--benef-accent-gold));
        border: none; color: white; font-weight:600;
    }
    .btn-submit-benef:hover { opacity: 0.9; color:white; transform: scale(1.02); }
    .btn-cancel-benef {
        background-color: var(--admin-secondary); border-color: var(--admin-secondary); color:white;
    }
    .btn-cancel-benef:hover { background-color: #5a6268; border-color: #545b62; color:white;}

    .current-file-display {
        font-size: 0.85em;
        color: #666;
        margin-top: 5px;
    }
    .current-file-display a {
        color: var(--admin-info);
        text-decoration: none;
    }
    .current-file-display a:hover { text-decoration: underline; }
    .current-file-display .fa-file-pdf { color: #dc3545; }
    .current-file-display .fa-image { color: var(--admin-success); }

    .upload-preview-container {
        display: flex;
        align-items: center;
        margin-top: 10px;
    }
    .upload-preview-container img,
    .upload-preview-container .pdf-icon {
        max-width: 100px;
        max-height: 70px;
        border-radius: 4px;
        margin-right: 10px;
        border: 1px solid #ddd;
        padding: 2px;
    }
    .upload-preview-container .pdf-icon {
        font-size: 3rem;
        color: #dc3545;
        padding: 10px;
    }
</style>

<div class="page-title-container">
    <h1><i class="fas fa-user-edit" style="color:var(--benef-primary-orange);"></i> Modifier le Bénéficiaire : <?= htmlspecialchars($beneficiaire['prenom'] . ' ' . $beneficiaire['nom']) ?></h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/index.php">Tableau de bord</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/beneficiaires.php">Bénéficiaires</a></li>
            <li class="breadcrumb-item active" aria-current="page">Modifier</li>
        </ol>
    </nav>
</div>

<?php display_flash_message(); ?>

<div class="card card-admin card-form-beneficiaire">
    <div class="card-admin-header">
        <i class="fas fa-id-card"></i> Informations du Bénéficiaire
    </div>
    <div class="card-body p-4">
        <form method="POST" action="modifier_beneficiaire.php?id=<?= (int)$beneficiaire_id_get ?>" enctype="multipart/form-data">

            <h5 class="form-section-title"><i class="fas fa-user-check"></i> Informations Personnelles</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="prenom" class="form-label-admin">Prénom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-admin <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>" id="prenom" name="prenom" value="<?= htmlspecialchars($prenom) ?>" required>
                    <?php if (isset($errors['prenom'])): ?><div class="invalid-feedback"><?= $errors['prenom'] ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="nom" class="form-label-admin">Nom <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-admin <?= isset($errors['nom']) ? 'is-invalid' : '' ?>" id="nom" name="nom" value="<?= htmlspecialchars($nom) ?>" required>
                    <?php if (isset($errors['nom'])): ?><div class="invalid-feedback"><?= $errors['nom'] ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="telephone" class="form-label-admin">Téléphone</label>
                    <input type="tel" class="form-control form-control-admin <?= isset($errors['telephone']) ? 'is-invalid' : '' ?>" id="telephone" name="telephone" value="<?= htmlspecialchars($telephone) ?>">
                    <?php if (isset($errors['telephone'])): ?><div class="invalid-feedback"><?= $errors['telephone'] ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label-admin">Email (Optionnel)</label>
                    <input type="email" class="form-control form-control-admin <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= htmlspecialchars($email) ?>">
                    <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= $errors['email'] ?></div><?php endif; ?>
                </div>
                 <div class="col-12">
                    <label for="adresse" class="form-label-admin">Adresse Complète</label>
                    <textarea class="form-control form-control-admin <?= isset($errors['adresse']) ? 'is-invalid' : '' ?>" id="adresse" name="adresse" rows="2"><?= htmlspecialchars($adresse) ?></textarea>
                    <?php if (isset($errors['adresse'])): ?><div class="invalid-feedback"><?= $errors['adresse'] ?></div><?php endif; ?>
                </div>
            </div>

            <h5 class="form-section-title"><i class="fas fa-file-alt"></i> Situation et Justificatifs</h5>
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <label for="situation" class="form-label-admin">Description de la situation / Besoin</label>
                    <textarea class="form-control form-control-admin <?= isset($errors['situation']) ? 'is-invalid' : '' ?>" id="situation" name="situation" rows="4"><?= htmlspecialchars($situation) ?></textarea>
                    <?php if (isset($errors['situation'])): ?><div class="invalid-feedback"><?= $errors['situation'] ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="identite_recto" class="form-label-admin">Pièce d'identité (Recto) <small>(JPG, PNG, PDF - Max 2MB)</small></label>
                    <input class="form-control form-control-admin <?= isset($errors['identite_recto']) ? 'is-invalid' : '' ?>" type="file" id="identite_recto" name="identite_recto" accept=".jpg,.jpeg,.png,.pdf" onchange="previewFile(this, 'preview_recto')">
                    <?php if (isset($errors['identite_recto'])): ?><div class="invalid-feedback"><?= $errors['identite_recto'] ?></div><?php endif; ?>
                    <?php if ($current_identite_recto): ?>
                        <div class="current-file-display">
                            Actuel: <a href="<?= BASE_URL ?>uploads/identites/<?= htmlspecialchars($current_identite_recto) ?>" target="_blank">
                                <i class="fas <?= str_ends_with(strtolower($current_identite_recto), '.pdf') ? 'fa-file-pdf' : 'fa-image' ?>"></i> <?= htmlspecialchars(basename($current_identite_recto)) ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <div id="preview_recto" class="upload-preview-container"></div>
                </div>
                <div class="col-md-6">
                    <label for="identite_verso" class="form-label-admin">Pièce d'identité (Verso) <small>(JPG, PNG, PDF - Max 2MB)</small></label>
                    <input class="form-control form-control-admin <?= isset($errors['identite_verso']) ? 'is-invalid' : '' ?>" type="file" id="identite_verso" name="identite_verso" accept=".jpg,.jpeg,.png,.pdf" onchange="previewFile(this, 'preview_verso')">
                    <?php if (isset($errors['identite_verso'])): ?><div class="invalid-feedback"><?= $errors['identite_verso'] ?></div><?php endif; ?>
                    <?php if ($current_identite_verso): ?>
                        <div class="current-file-display">
                            Actuel: <a href="<?= BASE_URL ?>uploads/identites/<?= htmlspecialchars($current_identite_verso) ?>" target="_blank">
                                <i class="fas <?= str_ends_with(strtolower($current_identite_verso), '.pdf') ? 'fa-file-pdf' : 'fa-image' ?>"></i> <?= htmlspecialchars(basename($current_identite_verso)) ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <div id="preview_verso" class="upload-preview-container"></div>
                </div>
            </div>

            <h5 class="form-section-title"><i class="fas fa-cogs"></i> Statut</h5>
             <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="statut" class="form-label-admin">Statut du dossier</label>
                    <select class="form-select form-control-admin" id="statut" name="statut">
                        <option value="en attente" <?= ($statut == 'en attente') ? 'selected' : '' ?>>En attente de validation</option>
                        <option value="validé" <?= ($statut == 'validé') ? 'selected' : '' ?>>Validé</option>
                        <option value="aidé" <?= ($statut == 'aidé') ? 'selected' : '' ?>>Aidé / Dossier clos</option>
                    </select>
                </div>
            </div>

            
<!-- BOUTONS -->
<div class="text-end mt-4">
    <button type="submit" class="btn btn-submit-benef">
        <i class="fas fa-save"></i> Enregistrer les modifications
    </button>
    <a href="<?= BASE_URL ?>admin/beneficiaires.php" class="btn btn-cancel-benef ms-2">
        <i class="fas fa-times-circle"></i> Annuler
    </a>
</div>



<style>
    .btn-submit-benef {
        background: linear-gradient(135deg, #7f00ff, #00c6ff);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 30px;
        font-weight: bold;
        transition: all 0.4s ease;
        box-shadow: 0 4px 15px rgba(127, 0, 255, 0.4);
    }

    .btn-submit-benef:hover {
        background: linear-gradient(135deg, #00c6ff, #7f00ff);
        transform: scale(1.05);
        box-shadow: 0 6px 20px rgba(0, 198, 255, 0.6);
    }

    .btn-cancel-benef {
        background: linear-gradient(135deg, #ff416c, #ff4b2b);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 30px;
        font-weight: bold;
        transition: all 0.4s ease;
        box-shadow: 0 4px 15px rgba(255, 65, 108, 0.4);
    }

    .btn-cancel-benef:hover {
        background: linear-gradient(135deg, #ff4b2b, #ff416c);
        transform: scale(1.05);
        box-shadow: 0 6px 20px rgba(255, 75, 43, 0.6);
    }
</style>

    </div>
</div>

<script>
// Même fonction previewFile que dans ajouter_beneficiaire.php
function previewFile(input, previewId) {
    const file = input.files[0];
    const previewContainer = document.getElementById(previewId);
    previewContainer.innerHTML = ''; // Clear previous preview

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = e.target.result;
                previewContainer.appendChild(img);
            } else if (file.type === 'application/pdf') {
                const icon = document.createElement('i');
                icon.className = 'fas fa-file-pdf pdf-icon';
                previewContainer.appendChild(icon);
                const fileName = document.createElement('span');
                fileName.textContent = file.name;
                fileName.className = 'ms-2 small';
                previewContainer.appendChild(fileName);
            }
        }
        reader.readAsDataURL(file);
    }
}
</script>

