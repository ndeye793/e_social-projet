<?php
$page_title = "Ajouter un Bénéficiaire";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';

define('CURRENCY_SYMBOL', 'FCFA'); // ou '₣', ou 'F CFA', selon votre préférence

function redirect($url) {
    header("Location: $url");
    exit;
}

$pdo = getPDO();

$prenom = '';
$nom = '';
$telephone = '';
$email = '';
$adresse = '';
$situation = '';
$statut = 'en attente';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = sanitize($_POST['prenom'] ?? '');
    $nom = sanitize($_POST['nom'] ?? '');
    $telephone = sanitize($_POST['telephone'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $adresse = sanitize($_POST['adresse'] ?? '');
    $situation = sanitize($_POST['situation'] ?? '', true);
    $statut = in_array($_POST['statut'] ?? '', ['en attente', 'validé', 'aidé']) ? $_POST['statut'] : 'en attente';

    // Validation
    if (empty($prenom)) $errors['prenom'] = "Le prénom est requis.";
    if (empty($nom)) $errors['nom'] = "Le nom est requis.";
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Format d'email invalide.";
    // Autres validations (téléphone, etc.)


      ob_start();

define('ROOT_PATH', realpath(dirname(__DIR__))); // C:\wamp64\www\e_social
$destination_dir = ROOT_PATH . '/uploads/identites/';

// Crée le dossier si besoin
if (!is_dir($destination_dir)) {
    @mkdir($destination_dir, 0777, true); // supprime le warning
}

// Fonction d'upload
function upload_file($file, $destination_dir, $allowed_types, $max_size, $prefix = '') {
    $filename = preg_replace('/\s+/', '_', $file['name']); // Supprime les espaces
    $filepath = $destination_dir . $prefix . $filename;

    // Vérifie extension
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_types)) {
        return false;
    }

    // Vérifie taille
    if ($file['size'] > $max_size) {
        return false;
    }

    // Déplace le fichier
    return move_uploaded_file($file['tmp_name'], $filepath) ? $prefix . $filename : false;
}

// Exemple d’utilisation
$allowed_types = ['jpg', 'jpeg', 'png', 'pdf'];
$max_size = 2 * 1024 * 1024; // 2 Mo

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fichier_recto = upload_file($_FILES['identite_recto'], $destination_dir, $allowed_types, $max_size, 'identite_recto_');
    $fichier_verso = upload_file($_FILES['identite_verso'], $destination_dir, $allowed_types, $max_size, 'identite_verso_');

    if ($fichier_recto && $fichier_verso) {
        // OK : redirection
        header("Location: beneficiaires.php");
        exit;
    } else {
        echo "Erreur : l'envoi des fichiers a échoué.";
    }
}

ob_end_flush();




    // Gestion upload identité recto
    $identite_recto_name = null;
    if (isset($_FILES['identite_recto']) && $_FILES['identite_recto']['error'] == UPLOAD_ERR_OK) {
        $identite_recto_name = upload_file($_FILES['identite_recto'], ROOT_PATH . 'uploads/identites/', ['jpg', 'jpeg', 'png', 'pdf'], 2097152, 'identite_recto_'); // 2MB
        if (is_array($identite_recto_name)) { // Si upload_file retourne un tableau d'erreurs
            $errors['identite_recto'] = implode(', ', $identite_recto_name);
            $identite_recto_name = null;
        }
    } elseif (isset($_FILES['identite_recto']) && $_FILES['identite_recto']['error'] != UPLOAD_ERR_NO_FILE) {
        $errors['identite_recto'] = "Erreur upload recto (code: ".$_FILES['identite_recto']['error'].")";
    }

    // Gestion upload identité verso
    $identite_verso_name = null;
    if (isset($_FILES['identite_verso']) && $_FILES['identite_verso']['error'] == UPLOAD_ERR_OK) {
        $identite_verso_name = upload_file($_FILES['identite_verso'], ROOT_PATH . 'uploads/identites/', ['jpg', 'jpeg', 'png', 'pdf'], 2097152, 'identite_verso_');
        if (is_array($identite_verso_name)) {
            $errors['identite_verso'] = implode(', ', $identite_verso_name);
            $identite_verso_name = null;
        }
    } elseif (isset($_FILES['identite_verso']) && $_FILES['identite_verso']['error'] != UPLOAD_ERR_NO_FILE) {
        $errors['identite_verso'] = "Erreur upload verso (code: ".$_FILES['identite_verso']['error'].")";
    }

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO beneficiaires (prenom, nom, telephone, email, adresse, situation, statut, identite_recto, identite_verso, date_enregistrement)
                    VALUES (:prenom, :nom, :telephone, :email, :adresse, :situation, :statut, :identite_recto, :identite_verso, NOW())";
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
                ':identite_verso' => $identite_verso_name
            ]);
            set_flash_message("Nouveau bénéficiaire ajouté avec succès !", "success");
            redirect('beneficiaires.php');
        } catch (PDOException $e) {
            // Supprimer les fichiers uploadés en cas d'erreur BDD
            if ($identite_recto_name && file_exists(ROOT_PATH . 'uploads/identites/' . $identite_recto_name)) unlink(ROOT_PATH . 'uploads/identites/' . $identite_recto_name);
            if ($identite_verso_name && file_exists(ROOT_PATH . 'uploads/identites/' . $identite_verso_name)) unlink(ROOT_PATH . 'uploads/identites/' . $identite_verso_name);
            set_flash_message("Erreur lors de l'ajout du bénéficiaire: " . $e->getMessage(), "danger");
        }
    } else {
        // Supprimer les fichiers uploadés si erreurs de validation autres que l'upload lui-même
        if ($identite_recto_name && file_exists(ROOT_PATH . 'uploads/identites/' . $identite_recto_name) && !isset($errors['identite_recto'])) unlink(ROOT_PATH . 'uploads/identites/' . $identite_recto_name);
        if ($identite_verso_name && file_exists(ROOT_PATH . 'uploads/identites/' . $identite_verso_name) && !isset($errors['identite_verso'])) unlink(ROOT_PATH . 'uploads/identites/' . $identite_verso_name);
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
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    border-bottom: 3px solid #FFA726;
    padding-bottom: 15px;
}

.page-title-container h1 {
    font-size: 1.8rem;
    font-weight: bold;
    color: #333;
}

.breadcrumb {
    background: none;
    margin-bottom: 0;
    font-size: 0.9rem;
}

.card-admin {
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: none;
    border-radius: 15px;
    overflow: hidden;
    background-color: #fff;
}

.card-admin-header {
    background: #FFA726;
    color: white;
    font-weight: bold;
    font-size: 1.2rem;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-label-admin {
    font-weight: 600;
    color: #555;
}

.form-control-admin {
    border-radius: 10px;
    border: 1px solid #ccc;
    padding: 0.75rem;
}

.form-control-admin:focus {
    border-color: #FFA726;
    box-shadow: 0 0 0 0.2rem rgba(255,167,38,0.25);
}

.form-section-title {
    font-size: 1.1rem;
    font-weight: bold;
    margin-top: 30px;
    color: #FFA726;
    border-bottom: 2px solid #FFE0B2;
    padding-bottom: 5px;
}

.upload-preview-container {
    margin-top: 10px;
    min-height: 100px;
    border: 1px dashed #ddd;
    border-radius: 10px;
    padding: 10px;
    text-align: center;
    font-style: italic;
    color: #888;
    background: #fafafa;
}

.btn-submit-benef {
    background-color: #43A047;
    color: white;
    border-radius: 8px;
    padding: 10px 20px;
    font-weight: 600;
    transition: background-color 0.3s ease;
}

.btn-submit-benef:hover {
    background-color: #388E3C;
}

.btn-cancel-benef {
    background-color: #E53935;
    color: white;
    border-radius: 8px;
    padding: 10px 20px;
    font-weight: 600;
    transition: background-color 0.3s ease;
}

.btn-cancel-benef:hover {
    background-color: #C62828;
}

</style>

<div class="page-title-container">
    <h1><i class="fas fa-user-plus" style="color:var(--benef-primary-orange);"></i> Ajouter un Nouveau Bénéficiaire</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/index.php">Tableau de bord</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/beneficiaires.php">Bénéficiaires</a></li>
            <li class="breadcrumb-item active" aria-current="page">Ajouter</li>
        </ol>
    </nav>
</div>

<?php display_flash_message(); ?>

<div class="card card-admin card-form-beneficiaire">
    <div class="card-admin-header">
        <i class="fas fa-id-card"></i> Formulaire d'Enregistrement
    </div>
    <div class="card-body p-4">
        <form method="POST" action="ajouter_beneficiaire.php" enctype="multipart/form-data">

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
                    <small class="form-text text-muted">Expliquez brièvement pourquoi ce bénéficiaire a besoin d'aide.</small>
                </div>
                <div class="col-md-6">
                    <label for="identite_recto" class="form-label-admin">Pièce d'identité (Recto) <small>(JPG, PNG, PDF - Max 2MB)</small></label>
                    <input class="form-control form-control-admin <?= isset($errors['identite_recto']) ? 'is-invalid' : '' ?>" type="file" id="identite_recto" name="identite_recto" accept=".jpg,.jpeg,.png,.pdf" onchange="previewFile(this, 'preview_recto')">
                    <?php if (isset($errors['identite_recto'])): ?><div class="invalid-feedback"><?= $errors['identite_recto'] ?></div><?php endif; ?>
                    <div id="preview_recto" class="upload-preview-container"></div>
                </div>
                <div class="col-md-6">
                    <label for="identite_verso" class="form-label-admin">Pièce d'identité (Verso) <small>(JPG, PNG, PDF - Max 2MB)</small></label>
                    <input class="form-control form-control-admin <?= isset($errors['identite_verso']) ? 'is-invalid' : '' ?>" type="file" id="identite_verso" name="identite_verso" accept=".jpg,.jpeg,.png,.pdf" onchange="previewFile(this, 'preview_verso')">
                    <?php if (isset($errors['identite_verso'])): ?><div class="invalid-feedback"><?= $errors['identite_verso'] ?></div><?php endif; ?>
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


            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-submit-benef btn-lg me-2"><i class="fas fa-user-check"></i> Enregistrer Bénéficiaire</button>
                <a href="beneficiaires.php" class="btn btn-cancel-benef btn-lg"><i class="fas fa-times-circle"></i> Annuler</a>
            </div>
        </form>
    </div>
</div>

<script>
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

