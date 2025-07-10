<?php
// traitement/campagne_update.php
require_once '../config/db.php';
require_once '../config/constantes.php'; 
require_once '../includes/fonctions.php';

function redirect($url) {
    header("Location: $url");
    exit;
}
$user_prenom = $_SESSION['user_prenom'] ?? 'Utilisateur';
$page_title = "Modifier une Campagne - Traitement E-Social";
$pdo = getPDO();

$campagne_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$campagne_id) {
    set_flash_message("ID de campagne invalide.", "danger");
    redirect('campagne_list.php'); // Rediriger vers une page listant les campagnes
}

// Récupérer les données actuelles de la campagne
$stmt_campagne = $pdo->prepare("SELECT * FROM campagnes WHERE id = :id");
$stmt_campagne->execute([':id' => $campagne_id]);
$campagne = $stmt_campagne->fetch();

if (!$campagne) {
    set_flash_message("Campagne non trouvée.", "danger");
    redirect('campagne_list.php');
}

// Pré-remplir les valeurs d'input avec les données de la campagne
$input_values = $campagne;
// S'assurer que les dates sont au format Y-m-d pour les inputs type="date"
$input_values['date_debut'] = !empty($campagne['date_debut']) ? date('Y-m-d', strtotime($campagne['date_debut'])) : '';
$input_values['date_fin'] = !empty($campagne['date_fin']) ? date('Y-m-d', strtotime($campagne['date_fin'])) : '';


$categories = $pdo->query("SELECT id, nom_categorie FROM categories ORDER BY nom_categorie")->fetchAll();
$beneficiaires = $pdo->query("SELECT id, prenom, nom FROM beneficiaires WHERE statut = 'validé' ORDER BY nom, prenom")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_values['titre'] = sanitize($_POST['titre'] ?? $campagne['titre']);
    $input_values['description'] = sanitize($_POST['description'] ?? $campagne['description']);
    $input_values['montant_vise'] = filter_input(INPUT_POST, 'montant_vise', FILTER_VALIDATE_FLOAT) ?: $campagne['montant_vise'];
    $input_values['montant_atteint'] = filter_input(INPUT_POST, 'montant_atteint', FILTER_VALIDATE_FLOAT) ?: $campagne['montant_atteint'];
    $input_values['date_debut'] = sanitize($_POST['date_debut'] ?? $campagne['date_debut']);
    $input_values['date_fin'] = sanitize($_POST['date_fin'] ?? $campagne['date_fin']);
    $input_values['categorie_id'] = filter_input(INPUT_POST, 'categorie_id', FILTER_VALIDATE_INT) ?: $campagne['categorie_id'];
    $input_values['beneficiaire_id'] = filter_input(INPUT_POST, 'beneficiaire_id', FILTER_VALIDATE_INT) ?: $campagne['beneficiaire_id'];
    $input_values['statut'] = in_array($_POST['statut'], ['en cours', 'terminée', 'suspendue']) ? $_POST['statut'] : $campagne['statut'];
    
    $image_campagne_file = $_FILES['image_campagne'] ?? null;
    $image_path = $campagne['image_campagne']; // Garder l'ancienne image par défaut

    // Validations (similaires à campagne_add.php)
    if (empty($input_values['titre'])) $errors['titre'] = "Le titre est requis.";
    // ... autres validations ...

    // Gestion de la nouvelle image si fournie
    if ($image_campagne_file && $image_campagne_file['error'] == UPLOAD_ERR_OK) {
        $upload_dir = '../assets/images/campagnes/';
        // Supprimer l'ancienne image si elle existe et si on en uploade une nouvelle
        if ($image_path && file_exists('../' . $image_path)) {
            unlink('../' . $image_path);
        }
        // Logique d'upload (identique à campagne_add.php)
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = mime_content_type($image_campagne_file['tmp_name']);
        if (in_array($file_type, $allowed_types) && $image_campagne_file['size'] <= 5 * 1024 * 1024) {
            $safe_filename = uniqid('campagne_upd_', true) . '.' . pathinfo($image_campagne_file['name'], PATHINFO_EXTENSION);
            if (move_uploaded_file($image_campagne_file['tmp_name'], $upload_dir . $safe_filename)) {
                $image_path = 'assets/images/campagnes/' . $safe_filename;
            } else $errors['image_campagne'] = "Erreur déplacement fichier.";
        } else $errors['image_campagne'] = "Fichier image invalide (type/taille).";
    } elseif ($image_campagne_file && $image_campagne_file['error'] != UPLOAD_ERR_NO_FILE) {
        $errors['image_campagne'] = "Erreur upload image (code: ".$image_campagne_file['error'].").";
    }


    if (empty($errors)) {
        try {
            $sql = "UPDATE campagnes SET 
                        titre = :titre, description = :description, montant_vise = :montant_vise, montant_atteint = :montant_atteint, 
                        date_debut = :date_debut, date_fin = :date_fin, categorie_id = :categorie_id, 
                        beneficiaire_id = :beneficiaire_id, statut = :statut, image_campagne = :image_campagne
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':titre' => $input_values['titre'],
                ':description' => $input_values['description'],
                ':montant_vise' => $input_values['montant_vise'],
                ':montant_atteint' => $input_values['montant_atteint'],
                ':date_debut' => !empty($input_values['date_debut']) ? $input_values['date_debut'] : null,
                ':date_fin' => !empty($input_values['date_fin']) ? $input_values['date_fin'] : null,
                ':categorie_id' => $input_values['categorie_id'] ?: null,
                ':beneficiaire_id' => $input_values['beneficiaire_id'] ?: null,
                ':statut' => $input_values['statut'],
                ':image_campagne' => $image_path,
                ':id' => $campagne_id
            ]);
            set_flash_message("Campagne mise à jour avec succès !", "success");
            redirect('campagne_list.php?id=' . $campagne_id); // ou vers une page de détails de la campagne
        } catch (PDOException $e) {
            $errors['db'] = "Erreur base de données : " . $e->getMessage(); // Pour dev
        }
    } else {
        set_flash_message("Veuillez corriger les erreurs.", "danger");
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
    <!-- Copier le CSS de traitement/index.php (sidebar, content, etc.) -->
    <style>
        /* ... Collez le CSS de index.php (ou campagne_add.php) ici ... */
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
        #sidebar ul.components {padding: 20px 0; /*border-bottom: 1px solid #47748b;*/}
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
        .card-custom .card-body {padding: 1.5rem;}
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
        .current-image-preview { max-width: 200px; max-height: 150px; margin-top: 10px; border: 1px solid #ddd; padding: 5px; border-radius: 5px;}
        @media (max-width: 768px) {
            #sidebar {margin-left: -260px;} #sidebar.active {margin-left: 0;}
            #content {width: 100%; margin-left: 0;}
        }
    </style>
</head>
<body>
    <nav id="sidebar">
        <!-- Copier la sidebar de index.php ou campagne_add.php -->
        <div class="sidebar-header"><h3><i class="fas fa-hands-helping"></i> E-Social</h3><span class="small-text">Panneau de Traitement</span></div>
        <ul class="list-unstyled components">
            <p><i class="fas fa-user-circle"></i> Bienvenue, <?= htmlspecialchars($user_prenom) ?></p>
            <li><a href="index.php"><i class="fas fa-tachometer-alt"></i> Tableau de Bord</a></li>
            <li class="active">
                <a href="#campagneSubmenu" data-bs-toggle="collapse" aria-expanded="true" class="dropdown-toggle"><i class="fas fa-bullhorn"></i> Campagnes</a>
                <ul class="collapse list-unstyled show" id="campagneSubmenu">
                    <li><a href="campagne_add.php"><i class="fas fa-plus-circle"></i> Ajouter Campagne</a></li>
                    <li class="active"><a href="campagne_list.php"><i class="fas fa-list-ul"></i> Gérer Campagnes</a></li> <!-- Supposons que campagne_list.php existe -->
                </ul>
            </li>
            <li><a href="beneficiaire_add.php"><i class="fas fa-users"></i> Ajouter Bénéficiaire</a></li>
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
             <li><a href="../admin/index.php" target="_blank"><i class="fas fa-user-shield"></i> Panel Admin Complet</a></li>
        </ul>
        <ul class="list-unstyled CTAs">
            <li><a href="../public/index.php" class="download" target="_blank"><i class="fas fa-globe"></i> Voir le site public</a></li>
            <li><a href="logout.php" class="article bg-danger"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
        </ul>
    </nav>

    <div id="content">
        <nav class="navbar navbar-expand-lg navbar-custom">
            <!-- Copier la navbar interne de index.php ou campagne_add.php -->
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="sidebar-toggler"><i class="fas fa-align-left"></i></button>
                <span class="navbar-brand mb-0 h1 d-none d-md-block">Modifier la Campagne</span>
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
                <i class="fas fa-edit"></i> Formulaire de modification de campagne
            </div>
            <div class="card-body">
                <form method="POST" action="campagne_update.php?id=<?= $campagne_id ?>" enctype="multipart/form-data" novalidate>
                    <div class="mb-3">
                        <label for="titre" class="form-label">Titre de la campagne <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?= isset($errors['titre']) ? 'is-invalid' : '' ?>" id="titre" name="titre" value="<?= htmlspecialchars($input_values['titre']) ?>" required>
                        <?php if (isset($errors['titre'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['titre']) ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="description" name="description" rows="5" required><?= htmlspecialchars($input_values['description']) ?></textarea>
                        <?php if (isset($errors['description'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['description']) ?></div><?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="montant_vise" class="form-label">Montant visé (CFA) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control <?= isset($errors['montant_vise']) ? 'is-invalid' : '' ?>" id="montant_vise" name="montant_vise" value="<?= htmlspecialchars($input_values['montant_vise']) ?>" required>
                            <?php if (isset($errors['montant_vise'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['montant_vise']) ?></div><?php endif; ?>
                        </div>
                         <div class="col-md-6 mb-3">
                            <label for="montant_atteint" class="form-label">Montant atteint (CFA)</label>
                            <input type="number" step="0.01" class="form-control <?= isset($errors['montant_atteint']) ? 'is-invalid' : '' ?>" id="montant_atteint" name="montant_atteint" value="<?= htmlspecialchars($input_values['montant_atteint']) ?>">
                            <?php if (isset($errors['montant_atteint'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['montant_atteint']) ?></div><?php endif; ?>
                        </div>
                    </div>
                     <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="statut" class="form-label">Statut</label>
                            <select class="form-select <?= isset($errors['statut']) ? 'is-invalid' : '' ?>" id="statut" name="statut">
                                <option value="en cours" <?= $input_values['statut'] == 'en cours' ? 'selected' : '' ?>>En cours</option>
                                <option value="terminée" <?= $input_values['statut'] == 'terminée' ? 'selected' : '' ?>>Terminée</option>
                                <option value="suspendue" <?= $input_values['statut'] == 'suspendue' ? 'selected' : '' ?>>Suspendue</option>
                            </select>
                             <?php if (isset($errors['statut'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['statut']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="date_debut" class="form-label">Date de début <span class="text-danger">*</span></label>
                            <input type="date" class="form-control <?= isset($errors['date_debut']) ? 'is-invalid' : '' ?>" id="date_debut" name="date_debut" value="<?= htmlspecialchars($input_values['date_debut']) ?>" required>
                            <?php if (isset($errors['date_debut'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['date_debut']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="date_fin" class="form-label">Date de fin (Optionnel)</label>
                            <input type="date" class="form-control <?= isset($errors['date_fin']) ? 'is-invalid' : '' ?>" id="date_fin" name="date_fin" value="<?= htmlspecialchars($input_values['date_fin']) ?>">
                            <?php if (isset($errors['date_fin'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['date_fin']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="categorie_id" class="form-label">Catégorie</label>
                            <select class="form-select <?= isset($errors['categorie_id']) ? 'is-invalid' : '' ?>" id="categorie_id" name="categorie_id">
                                <option value="">-- Sélectionner --</option>
                                <?php foreach($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $input_values['categorie_id'] == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nom_categorie']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['categorie_id'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['categorie_id']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="beneficiaire_id" class="form-label">Bénéficiaire</label>
                            <select class="form-select <?= isset($errors['beneficiaire_id']) ? 'is-invalid' : '' ?>" id="beneficiaire_id" name="beneficiaire_id">
                                <option value="">-- Sélectionner --</option>
                                 <?php foreach($beneficiaires as $ben): ?>
                                <option value="<?= $ben['id'] ?>" <?= $input_values['beneficiaire_id'] == $ben['id'] ? 'selected' : '' ?>><?= htmlspecialchars($ben['prenom'] . ' ' . $ben['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['beneficiaire_id'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['beneficiaire_id']) ?></div><?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="image_campagne" class="form-label">Nouvelle image de campagne (Optionnel, max 5MB)</label>
                        <input class="form-control <?= isset($errors['image_campagne']) ? 'is-invalid' : '' ?>" type="file" id="image_campagne" name="image_campagne" accept="image/png, image/jpeg, image/gif, image/webp">
                        <?php if (isset($errors['image_campagne'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['image_campagne']) ?></div><?php endif; ?>
                        <?php if ($input_values['image_campagne']): ?>
                            <div class="mt-2">
                                <p class="mb-1">Image actuelle :</p>
                                <img src="../<?= htmlspecialchars($input_values['image_campagne']) ?>" alt="Image actuelle" class="current-image-preview img-thumbnail">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-gradient"><i class="fas fa-save"></i> Enregistrer les modifications</button>
                        <a href="campagne_list.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Annuler</a>
                    </div>
                </form>
            </div>
        </div>
        
        <footer class="mt-auto pt-3 text-center text-muted">
            <p>© <?= date('Y') ?> E-Social. Tous droits réservés.</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Copier le script JS de traitement/index.php pour la sidebar -->
     <script>
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