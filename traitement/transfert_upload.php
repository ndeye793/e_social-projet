<?php
// traitement/transfert_upload.php
require_once '../config/db.php';
require_once '../config/constantes.php'; 
require_once '../includes/fonctions.php';

function redirect($url) {
    header("Location: $url");
    exit;
}

$user_prenom = $_SESSION['user_prenom'] ?? 'Admin';
$page_title = "Envoyer Justificatif de Transfert - Traitement E-Social";
$pdo = getPDO();

// Récupérer les campagnes terminées ou celles où l'on pourrait effectuer un transfert
$stmt_campagnes = $pdo->query("SELECT id, titre FROM campagnes WHERE statut IN ('terminée', 'en cours') ORDER BY titre"); // En cours aussi pour transferts partiels
$campagnes_list = $stmt_campagnes->fetchAll();

$errors = [];
$input_values = ['campagne_id' => '', 'commentaire' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_values['campagne_id'] = filter_input(INPUT_POST, 'campagne_id', FILTER_VALIDATE_INT);
    $input_values['commentaire'] = sanitize($_POST['commentaire'] ?? '');
    $fichier_justificatif = $_FILES['fichier_justificatif'] ?? null;
    $justificatif_path = null;

    if (empty($input_values['campagne_id'])) $errors['campagne_id'] = "Veuillez sélectionner une campagne.";
    if (!$fichier_justificatif || $fichier_justificatif['error'] != UPLOAD_ERR_OK) {
        $errors['fichier_justificatif'] = "Veuillez sélectionner un fichier justificatif.";
         if ($fichier_justificatif && $fichier_justificatif['error'] != UPLOAD_ERR_NO_FILE && $fichier_justificatif['error'] != UPLOAD_ERR_OK) {
             $errors['fichier_justificatif'] .= " (Erreur code: ".$fichier_justificatif['error'].")";
        }
    } else {
        // Gestion de l'upload (similaire aux autres uploads)
        $upload_dir_transferts = '../uploads/transferts_admin/';
        if (!is_dir($upload_dir_transferts)) mkdir($upload_dir_transferts, 0777, true);
        
        $allowed_types_transferts = ['image/jpeg', 'image/png', 'application/pdf', 'image/webp'];
        $file_type = mime_content_type($fichier_justificatif['tmp_name']);

        if (in_array($file_type, $allowed_types_transferts)) {
            if ($fichier_justificatif['size'] <= 5 * 1024 * 1024) { // Max 5MB
                $safe_filename_transfert = uniqid('transfert_', true) . '.' . pathinfo($fichier_justificatif['name'], PATHINFO_EXTENSION);
                if (move_uploaded_file($fichier_justificatif['tmp_name'], $upload_dir_transferts . $safe_filename_transfert)) {
                    $justificatif_path = 'uploads/transferts_admin/' . $safe_filename_transfert;
                } else {
                    $errors['fichier_justificatif'] = "Erreur lors du déplacement du fichier.";
                }
            } else {
                $errors['fichier_justificatif'] = "Le fichier est trop lourd (max 5MB).";
            }
        } else {
            $errors['fichier_justificatif'] = "Type de fichier non autorisé.";
        }
    }

    if (empty($errors) && $justificatif_path) {
        try {
            $sql = "INSERT INTO preuves_transfert (campagne_id, fichier_justificatif, commentaire) 
                    VALUES (:campagne_id, :fichier, :commentaire)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':campagne_id' => $input_values['campagne_id'],
                ':fichier' => $justificatif_path,
                ':commentaire' => $input_values['commentaire'] ?: null
            ]);
            set_flash_message("Justificatif de transfert envoyé avec succès.", "success");
            redirect('index.php'); // ou vers une page listant les transferts
        } catch (PDOException $e) {
            $errors['db'] = "Erreur base de données : " . $e->getMessage();
        }
    } else {
         if (empty($errors['fichier_justificatif']) && !$justificatif_path && $fichier_justificatif && $fichier_justificatif['error'] == UPLOAD_ERR_OK) {
            $errors['fichier_justificatif'] = "Une erreur inconnue est survenue lors du traitement du fichier.";
        }
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
    <style>
        /* ... Collez le CSS de index.php ici ... */
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
        @media (max-width: 768px) {
            #sidebar {margin-left: -260px;} #sidebar.active {margin-left: 0;}
            #content {width: 100%; margin-left: 0;}
        }
    </style>
</head>
<body>
    <nav id="sidebar">
        <!-- Copier la sidebar -->
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
            <li><a href="beneficiaire_add.php"><i class="fas fa-users"></i> Ajouter Bénéficiaire</a></li>
            <li>
                <a href="#donSubmenu" data-bs-toggle="collapse" aria-expanded="false" class="dropdown-toggle"><i class="fas fa-donate"></i> Dons</a>
                <ul class="collapse list-unstyled" id="donSubmenu">
                     <li><a href="don_form.php"><i class="fas fa-hand-holding-usd"></i> Enregistrer un Don</a></li>
                     <li><a href="preuve_upload.php"><i class="fas fa-file-upload"></i> Envoyer Preuve Don</a></li>
                </ul>
            </li>
            <li class="active"><a href="transfert_upload.php"><i class="fas fa-exchange-alt"></i> Justificatifs Transfert</a></li>
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
            <!-- Copier la navbar interne -->
            <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="sidebar-toggler"><i class="fas fa-align-left"></i></button>
                <span class="navbar-brand mb-0 h1 d-none d-md-block">Envoyer Justificatif de Transfert</span>
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
                <i class="fas fa-file-invoice-dollar"></i> Formulaire d'envoi de justificatif de transfert
            </div>
            <div class="card-body">
                <?php if (empty($campagnes_list)): ?>
                    <div class="alert alert-info">Aucune campagne disponible pour un transfert.</div>
                <?php else: ?>
                <form method="POST" action="transfert_upload.php" enctype="multipart/form-data" novalidate>
                    <div class="mb-3">
                        <label for="campagne_id" class="form-label">Campagne concernée <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['campagne_id']) ? 'is-invalid' : '' ?>" id="campagne_id" name="campagne_id" required>
                            <option value="">-- Sélectionner une campagne --</option>
                            <?php foreach($campagnes_list as $camp): ?>
                                <option value="<?= $camp['id'] ?>" <?= ($input_values['campagne_id'] == $camp['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($camp['titre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['campagne_id'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['campagne_id']) ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="fichier_justificatif" class="form-label">Fichier justificatif (max 5MB: JPG, PNG, PDF, WEBP) <span class="text-danger">*</span></label>
                        <input class="form-control <?= isset($errors['fichier_justificatif']) ? 'is-invalid' : '' ?>" type="file" id="fichier_justificatif" name="fichier_justificatif" accept="image/jpeg,image/png,application/pdf,image/webp" required>
                        <?php if (isset($errors['fichier_justificatif'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['fichier_justificatif']) ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="commentaire" class="form-label">Commentaire (Optionnel)</label>
                        <textarea class="form-control <?= isset($errors['commentaire']) ? 'is-invalid' : '' ?>" id="commentaire" name="commentaire" rows="3"><?= htmlspecialchars($input_values['commentaire']) ?></textarea>
                        <?php if (isset($errors['commentaire'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['commentaire']) ?></div><?php endif; ?>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-gradient"><i class="fas fa-paper-plane"></i> Envoyer le justificatif</button>
                        <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i> Annuler</a>
                    </div>
                </form>
                <?php endif; ?>
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