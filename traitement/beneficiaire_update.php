<?php
// traitement/beneficiaire_update.php
require_once '../config/db.php';
require_once '../config/constantes.php'; 
require_once '../includes/fonctions.php';

function redirect($url) {
    header("Location: $url");
    exit;
}

$user_prenom = $_SESSION['user_prenom'] ?? 'Utilisateur';
$page_title = "Modifier un Bénéficiaire - Traitement E-Social";
$pdo = getPDO();

$beneficiaire_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$beneficiaire_id) {
    set_flash_message("ID de bénéficiaire invalide.", "danger");
    redirect('beneficiaire_list.php');
}

// Récupérer les données actuelles du bénéficiaire
$stmt_beneficiaire = $pdo->prepare("SELECT * FROM beneficiaires WHERE id = :id");
$stmt_beneficiaire->execute([':id' => $beneficiaire_id]);
$beneficiaire = $stmt_beneficiaire->fetch();

if (!$beneficiaire) {
    set_flash_message("Bénéficiaire non trouvé.", "danger");
    redirect('beneficiaire_list.php');
}

// Pré-remplir les valeurs d'input
$input_values = $beneficiaire;
$current_identite_recto = $beneficiaire['identite_recto'];
$current_identite_verso = $beneficiaire['identite_verso'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_values['prenom'] = sanitize($_POST['prenom'] ?? $beneficiaire['prenom']);
    $input_values['nom'] = sanitize($_POST['nom'] ?? $beneficiaire['nom']);
    $input_values['telephone'] = sanitize($_POST['telephone'] ?? $beneficiaire['telephone']);
    $input_values['adresse'] = sanitize($_POST['adresse'] ?? $beneficiaire['adresse']);
    $input_values['situation'] = sanitize($_POST['situation'] ?? $beneficiaire['situation']);
    $input_values['justificatif'] = sanitize($_POST['justificatif'] ?? $beneficiaire['justificatif']);
    $input_values['statut'] = in_array($_POST['statut'], ['en attente', 'validé', 'aidé']) ? $_POST['statut'] : $beneficiaire['statut'];

    $identite_recto_file = $_FILES['identite_recto'] ?? null;
    $identite_verso_file = $_FILES['identite_verso'] ?? null;
    $identite_recto_path = $current_identite_recto; // Garder l'ancien par défaut
    $identite_verso_path = $current_identite_verso; // Garder l'ancien par défaut

    // Validations (similaires à beneficiaire_add.php)
    if (empty($input_values['prenom'])) $errors['prenom'] = "Le prénom est requis.";
    // ... autres validations ...

    $upload_dir_id = '../uploads/identites/';
    if (!is_dir($upload_dir_id)) mkdir($upload_dir_id, 0775, true); // 0775 est plus sécurisé

    // Gestion MAJ identite_recto
    if ($identite_recto_file && $identite_recto_file['error'] == UPLOAD_ERR_OK) {
        $allowed_types_id = ['image/jpeg', 'image/png', 'application/pdf', 'image/webp'];
        if (in_array(mime_content_type($identite_recto_file['tmp_name']), $allowed_types_id) && $identite_recto_file['size'] <= 2 * 1024 * 1024) {
            // Supprimer l'ancien fichier recto s'il existe et si on en uploade un nouveau
            if ($current_identite_recto && file_exists('../' . $current_identite_recto)) {
                unlink('../' . $current_identite_recto);
            }
            $safe_filename_recto = 'recto_upd_' . $beneficiaire_id . '_' . time() . '.' . pathinfo($identite_recto_file['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($identite_recto_file['tmp_name'], $upload_dir_id . $safe_filename_recto)) {
                $identite_recto_path = 'uploads/identites/' . $safe_filename_recto;
            } else $errors['identite_recto'] = "Erreur upload recto.";
        } else $errors['identite_recto'] = "Fichier recto invalide (type/taille).";
    } elseif ($identite_recto_file && $identite_recto_file['error'] != UPLOAD_ERR_NO_FILE) {
         $errors['identite_recto'] = "Erreur upload recto (code: ".$identite_recto_file['error'].").";
    }


    // Gestion MAJ identite_verso (similaire)
    if ($identite_verso_file && $identite_verso_file['error'] == UPLOAD_ERR_OK) {
         if (in_array(mime_content_type($identite_verso_file['tmp_name']), $allowed_types_id) && $identite_verso_file['size'] <= 2 * 1024 * 1024) {
            if ($current_identite_verso && file_exists('../' . $current_identite_verso)) {
                unlink('../' . $current_identite_verso);
            }
            $safe_filename_verso = 'verso_upd_' . $beneficiaire_id . '_' . time() . '.' . pathinfo($identite_verso_file['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($identite_verso_file['tmp_name'], $upload_dir_id . $safe_filename_verso)) {
                $identite_verso_path = 'uploads/identites/' . $safe_filename_verso;
            } else $errors['identite_verso'] = "Erreur upload verso.";
        } else $errors['identite_verso'] = "Fichier verso invalide (type/taille).";
    } elseif ($identite_verso_file && $identite_verso_file['error'] != UPLOAD_ERR_NO_FILE) {
         $errors['identite_verso'] = "Erreur upload verso (code: ".$identite_verso_file['error'].").";
    }

    if (empty($errors)) {
        try {
            $sql = "UPDATE beneficiaires SET 
                        prenom = :prenom, nom = :nom, telephone = :telephone, adresse = :adresse, 
                        situation = :situation, justificatif = :justificatif, 
                        identite_recto = :identite_recto, identite_verso = :identite_verso, statut = :statut
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':prenom' => $input_values['prenom'],
                ':nom' => $input_values['nom'],
                ':telephone' => $input_values['telephone'] ?: null,
                ':adresse' => $input_values['adresse'] ?: null,
                ':situation' => $input_values['situation'],
                ':justificatif' => $input_values['justificatif'] ?: null,
                ':identite_recto' => $identite_recto_path,
                ':identite_verso' => $identite_verso_path,
                ':statut' => $input_values['statut'],
                ':id' => $beneficiaire_id
            ]);
            set_flash_message("Bénéficiaire mis à jour avec succès !", "success");
            redirect('beneficiaire_list.php');
        } catch (PDOException $e) {
            $errors['db'] = "Erreur base de données : " . $e->getMessage(); // Pour dev
        }
    } else {
        set_flash_message("Veuillez corriger les erreurs dans le formulaire.", "danger");
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Copiez le CSS de traitement/index.php ou beneficiaire_add.php ici */
         :root { /* mêmes variables que index.php */
            --primary-color: #6a11cb; --secondary-color: #2575fc; --light-bg: #f8f9fa;
            --dark-text: #343a40; --light-text: #f8f9fa; --sidebar-bg: #2c3e50;
            --sidebar-link-color: #ecf0f1; --sidebar-link-hover-bg: #34495e;
            --content-glow-start: rgba(106, 17, 203, 0.15); --content-glow-end: rgba(37, 117, 252, 0.15);
        }
        body {font-family: 'Poppins', sans-serif; background-color: var(--light-bg); display: flex; min-height: 100vh; overflow-x: hidden;}
        #sidebar {min-width: 260px; max-width: 260px; background: var(--sidebar-bg); color: var(--light-text); transition: all 0.3s; position: fixed; top: 0; left: 0; height: 100vh; z-index: 1000; overflow-y: auto;}
        #sidebar.active {margin-left: -260px;}
        #sidebar .sidebar-header {padding: 20px; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); text-align: center;}
        #sidebar .sidebar-header h3 {color: white; margin-bottom: 0; font-weight: 600;}
        #sidebar .sidebar-header .small-text {font-size: 0.8em;color: #ddd;}
        #sidebar ul.components {padding: 20px 0;}
        #sidebar ul p {color: var(--light-text); padding: 10px; text-transform: uppercase; font-weight: 600;}
        #sidebar ul li a {padding: 12px 20px; font-size: 1.05em; display: block; color: var(--sidebar-link-color); border-left: 3px solid transparent; transition: all 0.3s ease;}
        #sidebar ul li a:hover {color: var(--light-text); background: var(--sidebar-link-hover-bg); border-left-color: var(--secondary-color); text-decoration: none;}
        #sidebar ul li.active > a, a[aria-expanded="true"] {color: var(--light-text); background: var(--primary-color);}
        #sidebar ul li a i {margin-right: 10px; width: 20px; text-align: center;}
        #content {width: calc(100% - 260px); margin-left: 260px; padding: 25px; min-height: 100vh; transition: all 0.3s; background-color: #f4f7f6;}
        #content.active {width: 100%; margin-left: 0;}
        .navbar-custom {background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 10px 15px; margin-bottom: 20px;}
        .navbar-custom .navbar-brand {color: var(--primary-color); font-weight: 600;}
        .sidebar-toggler {background: transparent; border: none; color: var(--primary-color); font-size: 1.5rem;}
        .sidebar-toggler:hover {color: var(--secondary-color);}
        .card-custom {border: none; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.07); overflow: hidden;}
        .card-custom .card-header-custom {background: linear-gradient(to right, var(--primary-color), var(--secondary-color)); color: white; font-weight: 500; padding: 0.8rem 1.2rem;}
        .btn-gradient { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border: none; color: white; padding: 10px 20px; border-radius: 25px; transition: all 0.3s ease; font-weight: 500;}
        .btn-gradient:hover { background: linear-gradient(135deg, var(--secondary-color), var(--primary-color)); transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,0,0,0.15); color: white;}
        .form-control:focus, .form-select:focus {border-color: var(--secondary-color); box-shadow: 0 0 0 0.2rem var(--content-glow-end);}
        .form-label { font-weight: 500; color: var(--dark-text); }
        .is-invalid { border-color: #dc3545 !important; }
        .invalid-feedback { display: block; }
        #sidebar ul.CTAs { padding: 20px; }
        #sidebar ul.CTAs a { text-align: center; font-size: 0.9em; display: block; border-radius: 5px; margin-bottom: 5px; padding: 10px; color: white; }
        #sidebar ul.CTAs a.download { background: var(--secondary-color); }
        #sidebar ul.CTAs a.article { background: var(--primary-color); }
        #sidebar ul.CTAs a:hover { opacity: 0.8; }
        .current-file-link { display: inline-block; margin-top: 5px; font-size: 0.9em; }
        @media (max-width: 768px) {
            #sidebar {margin-left: -260px;} #sidebar.active {margin-left: 0;}
            #content {width: 100%; margin-left: 0;}
        }
    </style>
</head>
<body>
    <nav id="sidebar">
        <!-- Copiez la sidebar de beneficiaire_list.php, en ajustant la classe active -->
        <div class="sidebar-header"><h3><i class="fas fa-hands-helping"></i> E-Social</h3><span class="small-text">Panneau de Traitement</span></div>
        <ul class="list-unstyled components">
            <p><i class="fas fa-user-circle"></i> Bienvenue, <?= htmlspecialchars($user_prenom) ?></p>
            <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Tableau de Bord</a></li>
            <li>
                <a href="#campagneSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-bullhorn"></i> Campagnes</a>
                <ul class="collapse list-unstyled" id="campagneSubmenu">
                    <li><a href="campagne_add.php"><i class="fas fa-plus-circle"></i> Ajouter Campagne</a></li>
                    <li><a href="campagne_list.php"><i class="fas fa-list-ul"></i> Gérer Campagnes</a></li>
                </ul>
            </li>
            <li class="active">
                <a href="#beneficiaireSubmenu" data-bs-toggle="collapse" aria-expanded="true" class="dropdown-toggle"><i class="fas fa-users"></i> Bénéficiaires</a>
                <ul class="collapse list-unstyled show" id="beneficiaireSubmenu">
                    <li><a href="beneficiaire_add.php"><i class="fas fa-user-plus"></i> Ajouter Bénéficiaire</a></li>
                    <li class="active"><a href="beneficiaire_list.php"><i class="fas fa-list-alt"></i> Liste des Bénéficiaires</a></li>
                </ul>
            </li>
            <!-- ... autres liens de la sidebar ... -->
            <li>
                <a href="#donSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-donate"></i> Dons</a>
                <ul class="collapse list-unstyled" id="donSubmenu">
                     <li><a href="don_form.php"><i class="fas fa-hand-holding-usd"></i> Enregistrer un Don</a></li>
                     <li><a href="preuve_upload.php"><i class="fas fa-file-upload"></i> Envoyer Preuve Don</a></li>
                </ul>
            </li>
            <li><a href="transfert_upload.php"><i class="fas fa-exchange-alt"></i> Justificatifs Transfert</a></li>
            <li>
                <a href="#contactSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-envelope"></i> Communication</a>
                <ul class="collapse list-unstyled" id="contactSubmenu">
                    <li><a href="contact_view_messages.php"><i class="fas fa-inbox"></i> Messages Reçus</a></li>
                    <li><a href="newsletter_list.php"><i class="fas fa-newspaper"></i> Abonnés Newsletter</a></li>
                </ul>
            </li>
            <li><a href="<?= SITE_URL ?>admin/index.php" target="_blank"><i class="fas fa-user-shield"></i> Panel Admin Complet</a></li>
        </ul>
        <ul class="list-unstyled CTAs">
            <li><a href="<?= SITE_URL ?>public/index.php" class="download" target="_blank"><i class="fas fa-globe"></i> Voir le site public</a></li>
            <li><a href="logout.php" class="article bg-danger"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </nav>

    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-custom">
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="sidebar-toggler">
                    <i class="fas fa-align-left"></i>
                </button>
                <span class="navbar-brand mb-0 h1 d-none d-md-block">Modifier un Bénéficiaire</span>
                <div class="d-flex align-items-center">
                    <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </div>
            </div>
        </nav>

        <?php display_flash_message(); ?>
        <?php if (!empty($errors['db'])): ?>
            <div class="alert alert-danger"><i class="fas fa-times-circle"></i> <?= htmlspecialchars($errors['db']) ?></div>
        <?php endif; ?>

        <div class="card card-custom">
            <div class="card-header-custom">
                <i class="fas fa-user-edit"></i> Formulaire de modification de bénéficiaire
            </div>
            <div class="card-body">
                <form method="POST" action="beneficiaire_update.php?id=<?= $beneficiaire_id ?>" enctype="multipart/form-data" novalidate>
                    <!-- Champs identiques à beneficiaire_add.php, mais pré-remplis avec $input_values -->
                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>" id="prenom" name="prenom" value="<?= htmlspecialchars($input_values['prenom']) ?>" required>
                            <?php if (isset($errors['prenom'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['prenom']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>" id="nom" name="nom" value="<?= htmlspecialchars($input_values['nom']) ?>" required>
                            <?php if (isset($errors['nom'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['nom']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="telephone" class="form-label">Téléphone</label>
                        <input type="tel" class="form-control <?= isset($errors['telephone']) ? 'is-invalid' : '' ?>" id="telephone" name="telephone" value="<?= htmlspecialchars($input_values['telephone']) ?>">
                        <?php if (isset($errors['telephone'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['telephone']) ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="adresse" class="form-label">Adresse</label>
                        <textarea class="form-control <?= isset($errors['adresse']) ? 'is-invalid' : '' ?>" id="adresse" name="adresse" rows="2"><?= htmlspecialchars($input_values['adresse']) ?></textarea>
                        <?php if (isset($errors['adresse'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['adresse']) ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="situation" class="form-label">Situation <span class="text-danger">*</span></label>
                        <textarea class="form-control <?= isset($errors['situation']) ? 'is-invalid' : '' ?>" id="situation" name="situation" rows="4" required><?= htmlspecialchars($input_values['situation']) ?></textarea>
                        <?php if (isset($errors['situation'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['situation']) ?></div><?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="justificatif" class="form-label">Justificatif (Texte)</label>
                        <textarea class="form-control <?= isset($errors['justificatif']) ? 'is-invalid' : '' ?>" id="justificatif" name="justificatif" rows="2"><?= htmlspecialchars($input_values['justificatif']) ?></textarea>
                        <?php if (isset($errors['justificatif'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['justificatif']) ?></div><?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="identite_recto" class="form-label">Pièce d'identité (Recto) (Optionnel, max 2MB)</label>
                            <input class="form-control <?= isset($errors['identite_recto']) ? 'is-invalid' : '' ?>" type="file" id="identite_recto" name="identite_recto" accept="image/png, image/jpeg, application/pdf, image/webp">
                            <?php if (isset($errors['identite_recto'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['identite_recto']) ?></div><?php endif; ?>
                            <?php if ($current_identite_recto): ?>
                                <small class="form-text">Actuel: <a href="<?= SITE_URL . htmlspecialchars($current_identite_recto) ?>" target="_blank" class="current-file-link">Voir recto</a></small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="identite_verso" class="form-label">Pièce d'identité (Verso) (Optionnel, max 2MB)</label>
                            <input class="form-control <?= isset($errors['identite_verso']) ? 'is-invalid' : '' ?>" type="file" id="identite_verso" name="identite_verso" accept="image/png, image/jpeg, application/pdf, image/webp">
                            <?php if (isset($errors['identite_verso'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['identite_verso']) ?></div><?php endif; ?>
                             <?php if ($current_identite_verso): ?>
                                <small class="form-text">Actuel: <a href="<?= SITE_URL . htmlspecialchars($current_identite_verso) ?>" target="_blank" class="current-file-link">Voir verso</a></small>
                            <?php endif; ?>
                        </div>
                    </div>
                     <div class="mb-3">
                        <label for="statut" class="form-label">Statut</label>
                        <select class="form-select <?= isset($errors['statut']) ? 'is-invalid' : '' ?>" id="statut" name="statut">
                            <option value="en attente" <?= $input_values['statut'] == 'en attente' ? 'selected' : '' ?>>En attente</option>
                            <option value="validé" <?= $input_values['statut'] == 'validé' ? 'selected' : '' ?>>Validé</option>
                            <option value="aidé" <?= $input_values['statut'] == 'aidé' ? 'selected' : '' ?>>Aidé</option>
                        </select>
                        <?php if (isset($errors['statut'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['statut']) ?></div><?php endif; ?>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-gradient"><i class="fas fa-save"></i> Enregistrer les modifications</button>
                        <a href="beneficiaire_list.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Annuler</a>
                    </div>
                </form>
            </div>
        </div>
        <footer class="mt-auto pt-3 text-center text-muted">
            <p>© <?= date('Y') ?> E-Social. Tous droits réservés.</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Copiez le script JS de beneficiaire_list.php pour la sidebar
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarCollapse = document.getElementById('sidebarCollapse');
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            function toggleSidebar() {
                sidebar.classList.toggle('active');
                content.classList.toggle('active');
                localStorage.setItem('sidebarActive', sidebar.classList.contains('active'));
            }
            if (localStorage.getItem('sidebarActive') === 'true') {
                sidebar.classList.add('active');
                content.classList.add('active');
            } else if (localStorage.getItem('sidebarActive') === 'false') {
                 sidebar.classList.remove('active');
                 content.classList.remove('active');
            } else {
                 if (window.innerWidth <= 768) {
                    sidebar.classList.add('active');
                    content.classList.add('active');
                }
            }
            if (sidebarCollapse) {
                sidebarCollapse.addEventListener('click', function () {
                    toggleSidebar();
                });
            }
        });
    </script>
</body>
</html>