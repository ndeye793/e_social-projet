<?php
$page_title = "Ajouter un Partenaire";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';

define('CURRENCY_SYMBOL', 'FCFA'); // ou '₣', ou 'F CFA', selon votre préférence
define('ROOT_PATH', __DIR__ . '/../'); // Racine du projet

session_start();

function redirect($url) {
    header("Location: $url");
    exit;
}

// ✅ Fonction pour ajouter http:// si nécessaire
function add_http_if_needed($url) {
    if (!empty($url) && !preg_match("~^(?:f|ht)tps?://~i", $url)) {
        return 'http://' . $url;
    }
    return $url;
}

// ✅ Fonction pour gérer l'upload de fichier
function upload_file($file, $target_dir, $allowed_extensions = ['jpg', 'jpeg', 'png', 'svg', 'webp'], $max_size = 1048576, $prefix = 'file_') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ["Erreur lors du téléchargement du fichier (code : {$file['error']})"];
    }

    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension']);

    if (!in_array($extension, $allowed_extensions)) {
        return ["Extension de fichier non autorisée."];
    }

    if ($file['size'] > $max_size) {
        return ["Le fichier dépasse la taille autorisée (max. " . ($max_size / 1048576) . " Mo)."];
    }

    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $new_filename = $prefix . uniqid() . '.' . $extension;
    $target_path = rtrim($target_dir, '/') . '/' . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        return $new_filename;
    }

    return ["Erreur lors du déplacement du fichier."];
}

// Variables par défaut
$site_web = add_http_if_needed($_POST['site_web'] ?? '');
$pdo = getPDO();
$nom_partenaire = '';
$site_web = '';
$description = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_partenaire = sanitize($_POST['nom_partenaire'] ?? '');
    $site_web = sanitize($_POST['site_web'] ?? '');
    $description = sanitize($_POST['description'] ?? '', true);

    if (empty($nom_partenaire)) $errors['nom_partenaire'] = "Le nom du partenaire est requis.";
    if (!empty($site_web) && !filter_var(add_http_if_needed($site_web), FILTER_VALIDATE_URL)) {
        $errors['site_web'] = "L'URL du site web n'est pas valide.";
    }

    $logo_name = null;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
        $upload_result_logo = upload_file($_FILES['logo'], ROOT_PATH . 'assets/images/logos/', ['jpg', 'jpeg', 'png', 'svg', 'webp'], 1048576, 'partner_logo_');

        if (is_array($upload_result_logo)) {
            $errors['logo'] = implode(', ', $upload_result_logo);
        } else {
            $logo_name = $upload_result_logo;
        }
    } elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] != UPLOAD_ERR_NO_FILE) {
        $errors['logo'] = "Erreur upload logo (code: " . $_FILES['logo']['error'] . ")";
    }

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO partenaires (nom_partenaire, logo, site_web, description) VALUES (:nom, :logo, :site, :desc)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom' => $nom_partenaire,
                ':logo' => $logo_name,
                ':site' => !empty($site_web) ? $site_web : null,
                ':desc' => !empty($description) ? $description : null
            ]);
            set_flash_message("Nouveau partenaire ajouté avec succès !", "success");
            redirect('partenaires.php');
        } catch (PDOException $e) {
            if ($logo_name && file_exists(ROOT_PATH . 'assets/images/logos/' . $logo_name)) {
                unlink(ROOT_PATH . 'assets/images/logos/' . $logo_name);
            }
            set_flash_message("Erreur lors de l'ajout du partenaire: " . $e->getMessage(), "danger");
        }
    } else {
        if ($logo_name && file_exists(ROOT_PATH . 'assets/images/logos/' . $logo_name) && !isset($errors['logo'])) {
            unlink(ROOT_PATH . 'assets/images/logos/' . $logo_name);
        }
        set_flash_message("Veuillez corriger les erreurs.", "warning");
    }
}
?>

<style>
   .title-with-icon i {
    animation: bounceIn 1s ease-in-out;
}

@keyframes bounceIn {
    0% { transform: scale(0.3); opacity: 0; }
    50% { transform: scale(1.05); opacity: 1; }
    70% { transform: scale(0.9); }
    100% { transform: scale(1); }
}

.card-form-partner {
    background: linear-gradient(to bottom right, #ffffff, #f7f9fc);
    border-radius: 16px;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.card-form-partner:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
}

.form-section-title {
    font-weight: bold;
    font-size: 1.25rem;
    margin-bottom: 1rem;
    position: relative;
    padding-left: 1.5rem;
}

.form-section-title i {
    position: absolute;
    left: 0;
    top: 0;
    color: #0d6efd;
}

.input-group-text {
    background-color: #f1f1f1;
    border: none;
}

.logo-preview-container img {
    max-width: 150px;
    margin-top: 10px;
    border-radius: 12px;
    border: 1px solid #ddd;
}

</style>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">


<div class="page-title-container">
    <h1><i class="fas fa-plus-square"></i> Ajouter un Nouveau Partenaire</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/index.php">Tableau de bord</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/partenaires.php">Partenaires</a></li>
            <li class="breadcrumb-item active" aria-current="page">Ajouter</li>
        </ol>
    </nav>
</div>

<?php display_flash_message(); ?>

<div class="card card-admin card-form-partner">
    <div class="card-admin-header">
        <i class="fas fa-building"></i> Informations du Partenaire
    </div>
    <div class="card-body p-4">
        <form method="POST" action="ajouter_partenaire.php" enctype="multipart/form-data">
            <h5 class="form-section-title"><i class="fas fa-info-circle"></i> Détails Principaux</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="nom_partenaire" class="form-label-admin">Nom du Partenaire <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-admin <?= isset($errors['nom_partenaire']) ? 'is-invalid' : '' ?>" id="nom_partenaire" name="nom_partenaire" value="<?= htmlspecialchars($nom_partenaire) ?>" required>
                    <?php if (isset($errors['nom_partenaire'])): ?><div class="invalid-feedback"><?= $errors['nom_partenaire'] ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="site_web" class="form-label-admin">Site Web (Optionnel)</label>
                    <input type="url" class="form-control form-control-admin <?= isset($errors['site_web']) ? 'is-invalid' : '' ?>" id="site_web" name="site_web" value="<?= htmlspecialchars($site_web) ?>" placeholder="https://www.example.com">
                    <?php if (isset($errors['site_web'])): ?><div class="invalid-feedback"><?= $errors['site_web'] ?></div><?php endif; ?>
                </div>
                 <div class="col-12">
                    <label for="description" class="form-label-admin">Description (Optionnel)</label>
                    <textarea class="form-control form-control-admin <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="description" name="description" rows="3"><?= htmlspecialchars($description) ?></textarea>
                    <?php if (isset($errors['description'])): ?><div class="invalid-feedback"><?= $errors['description'] ?></div><?php endif; ?>
                </div>
            </div>

            <h5 class="form-section-title"><i class="fas fa-image"></i> Logo</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="logo" class="form-label-admin">Fichier du Logo <small>(JPG, PNG, SVG, WEBP - Max 1MB)</small></label>
                    <input class="form-control form-control-admin <?= isset($errors['logo']) ? 'is-invalid' : '' ?>" type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.svg,.webp" onchange="previewLogo(this, 'logo_preview_container')">
                    <?php if (isset($errors['logo'])): ?><div class="invalid-feedback"><?= $errors['logo'] ?></div><?php endif; ?>
                    <div id="logo_preview_container" class="logo-preview-container"></div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-submit-partner btn-lg me-2"><i class="fas fa-save"></i> Enregistrer Partenaire</button>
                <a href="partenaires.php" class="btn btn-cancel-benef btn-lg"><i class="fas fa-times-circle"></i> Annuler</a>
            </div>
        </form>
    </div>
</div>
<script>
function previewLogo(input, previewId) {
    const file = input.files[0];
    const previewContainer = document.getElementById(previewId);
    previewContainer.innerHTML = '';
    if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            previewContainer.appendChild(img);
        }
        reader.readAsDataURL(file);
    }
}
</script>
