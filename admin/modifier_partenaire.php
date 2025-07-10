<?php
$page_title = "Modifier le Partenaire";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';

define('CURRENCY_SYMBOL', 'FCFA'); // ou '₣', ou 'F CFA', selon votre préférence

function redirect($url) {
    header("Location: $url");
    exit;
}

$pdo = getPDO();
$partenaire_id_get = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$partenaire_id_get) {
    set_flash_message("ID de partenaire invalide.", "danger");
    redirect('partenaires.php');
}

try {
    $stmt = $pdo->prepare("SELECT * FROM partenaires WHERE id = :id");
    $stmt->execute([':id' => $partenaire_id_get]);
    $partenaire = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$partenaire) {
        set_flash_message("Partenaire non trouvé.", "danger");
        redirect('partenaires.php');
    }
} catch (PDOException $e) {
    set_flash_message("Erreur: " . $e->getMessage(), "danger");
    redirect('partenaires.php');
}

$nom_partenaire = $partenaire['nom_partenaire'];
$site_web = $partenaire['site_web'];
$description = $partenaire['description'];
$current_logo = $partenaire['logo'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_partenaire = sanitize($_POST['nom_partenaire'] ?? $partenaire['nom_partenaire']);
    $site_web = sanitize($_POST['site_web'] ?? $partenaire['site_web']);
    $description = sanitize($_POST['description'] ?? $partenaire['description'], true);

    if (empty($nom_partenaire)) $errors['nom_partenaire'] = "Le nom est requis.";
    if (!empty($site_web) && !filter_var(add_http_if_needed($site_web), FILTER_VALIDATE_URL)) {
        $errors['site_web'] = "URL invalide.";
    }

    $logo_name = $current_logo;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
        $upload_result_logo = upload_file($_FILES['logo'], ROOT_PATH . 'assets/images/logos/', ['jpg', 'jpeg', 'png', 'svg', 'webp'], 1048576, 'partner_logo_');
        if (is_array($upload_result_logo)) {
            $errors['logo'] = implode(', ', $upload_result_logo);
        } else {
            if ($current_logo && file_exists(ROOT_PATH . 'assets/images/logos/' . $current_logo)) {
                unlink(ROOT_PATH . 'assets/images/logos/' . $current_logo);
            }
            $logo_name = $upload_result_logo;
        }
    } elseif (isset($_FILES['logo']) && $_FILES['logo']['error'] != UPLOAD_ERR_NO_FILE) {
        $errors['logo'] = "Erreur upload logo (code: ".$_FILES['logo']['error'].")";
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE partenaires SET nom_partenaire = :nom, logo = :logo, site_web = :site, description = :desc WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nom' => $nom_partenaire,
                ':logo' => $logo_name,
                ':site' => !empty($site_web) ? $site_web : null,
                ':desc' => !empty($description) ? $description : null,
                ':id' => $partenaire_id_get
            ]);
            set_flash_message("Partenaire mis à jour !", "success");
            redirect('partenaires.php');
        } catch (PDOException $e) {
            set_flash_message("Erreur: " . $e->getMessage(), "danger");
        }
    } else {
        if (isset($upload_result_logo) && is_array($upload_result_logo)) $logo_name = $current_logo; // Garder l'ancien si le nouveau a échoué
        set_flash_message("Veuillez corriger les erreurs.", "warning");
    }
}
// Assurer que la fonction add_http_if_needed est disponible
if (!function_exists('add_http_if_needed')) {
    function add_http_if_needed($url) {
         if (empty($url)) return $url;
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }
        return $url;
    }
}

?>
<style>
    /* Styles pour modifier_partenaire.php (similaires à ajouter) */
    .form-section-title {
        font-family: 'Poppins', sans-serif; font-size: 1.3rem; font-weight: 600;
        color: var(--partner-text-primary); margin-top: 1.5rem; margin-bottom: 1rem;
        padding-bottom: 0.5rem; border-bottom: 2px solid var(--partner-accent-color);
        display: inline-block;
    }
    .form-section-title i { margin-right: 10px; color: var(--partner-accent-color); }

    .card-form-partner {
        background: #fff; border: 1px solid #e9ecef; border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.05);
    }
     .card-form-partner .card-admin-header { color: var(--partner-text-primary); border-bottom-color: #e9ecef; }
    .card-form-partner .form-control-admin:focus {
        border-color: var(--partner-accent-color);
        box-shadow: 0 0 0 0.2rem rgba(76, 175, 80, .25);
    }
    .btn-submit-partner {
        background: linear-gradient(45deg, var(--partner-accent-color), #388E3C);
        border: none; color: white; font-weight:600;
    }
    .btn-submit-partner:hover { opacity: 0.9; color:white; transform: scale(1.02); }
    .current-logo-display img { max-width:120px; max-height:80px; border:1px solid #ddd; border-radius:4px; padding:2px;}
    .logo-preview-container { margin-top:10px; }
    .logo-preview-container img { max-width: 150px; max-height:100px; border-radius:4px; border:1px solid #ddd; padding:2px;}
</style>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">



<div class="page-title-container">
    <h1><i class="fas fa-edit"></i> Modifier le Partenaire : <?= htmlspecialchars($partenaire['nom_partenaire']) ?></h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/index.php">Tableau de bord</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>admin/partenaires.php">Partenaires</a></li>
            <li class="breadcrumb-item active" aria-current="page">Modifier</li>
        </ol>
    </nav>
</div>

<?php display_flash_message(); ?>

<div class="card card-admin card-form-partner">
    <div class="card-admin-header">
        <i class="fas fa-building"></i> Informations du Partenaire
    </div>
    <div class="card-body p-4">
        <form method="POST" action="modifier_partenaire.php?id=<?= (int)$partenaire_id_get ?>" enctype="multipart/form-data">
            <h5 class="form-section-title"><i class="fas fa-info-circle"></i> Détails Principaux</h5>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="nom_partenaire" class="form-label-admin">Nom du Partenaire <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-admin <?= isset($errors['nom_partenaire']) ? 'is-invalid' : '' ?>" id="nom_partenaire" name="nom_partenaire" value="<?= htmlspecialchars($nom_partenaire) ?>" required>
                    <?php if (isset($errors['nom_partenaire'])): ?><div class="invalid-feedback"><?= $errors['nom_partenaire'] ?></div><?php endif; ?>
                </div>
                <div class="col-md-6">
                    <label for="site_web" class="form-label-admin">Site Web (Optionnel)</label>
                    <input type="url" class="form-control form-control-admin <?= isset($errors['site_web']) ? 'is-invalid' : '' ?>" id="site_web" name="site_web" value="<?= htmlspecialchars((string) ($site_web ?? '')) ?>" placeholder="https://www.example.com">
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
                    <label for="logo" class="form-label-admin">Nouveau Logo <small>(Optionnel - JPG, PNG, SVG, WEBP - Max 1MB)</small></label>
                    <input class="form-control form-control-admin <?= isset($errors['logo']) ? 'is-invalid' : '' ?>" type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.svg,.webp" onchange="previewLogo(this, 'logo_preview_container')">
                    <?php if (isset($errors['logo'])): ?><div class="invalid-feedback"><?= $errors['logo'] ?></div><?php endif; ?>
                    <?php if ($current_logo): ?>
                        <div class="mt-2 current-logo-display">
                            <small>Logo actuel :</small><br>
                            <img src="<?= BASE_URL ?>assets/images/logos/<?= htmlspecialchars($current_logo) ?>" alt="Logo actuel" class="mt-1"
                                 onerror="this.style.display='none'; this.parentElement.innerHTML+='<small>Image non trouvée.</small>';">
                        </div>
                    <?php endif; ?>
                    <div id="logo_preview_container" class="logo-preview-container"></div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-top">
                <button type="submit" class="btn btn-submit-partner btn-lg me-2"><i class="fas fa-save"></i> Enregistrer Modifications</button>
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