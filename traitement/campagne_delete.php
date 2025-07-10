<?php
// traitement/campagne_delete.php
require_once '../config/db.php';
require_once '../config/constantes.php'; 
require_once '../includes/fonctions.php';

function redirect($url) {
    header("Location: $url");
    exit;
}

$user_prenom = $_SESSION['user_prenom'] ?? 'Utilisateur';
$page_title = "Supprimer une Campagne - Traitement E-Social";
$pdo = getPDO();

$campagne_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$campagne_id) {
    set_flash_message("ID de campagne invalide.", "danger");
    redirect('campagne_list.php'); // Suppose que campagne_list.php existe
}

$stmt_campagne = $pdo->prepare("SELECT id, titre, image_campagne FROM campagnes WHERE id = :id");
$stmt_campagne->execute([':id' => $campagne_id]);
$campagne = $stmt_campagne->fetch();

if (!$campagne) {
    set_flash_message("Campagne non trouvée.", "danger");
    redirect('campagne_list.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_delete'])) {
        try {
            // Avant de supprimer la campagne, vérifier s'il y a des dons associés.
            // Décider de la politique : interdire la suppression, supprimer les dons, ou les désassocier.
            // Pour cet exemple, on va juste supprimer. En production, une réflexion plus poussée est nécessaire.
            // $stmt_check_dons = $pdo->prepare("SELECT COUNT(*) FROM dons WHERE campagne_id = :id");
            // $stmt_check_dons->execute([':id' => $campagne_id]);
            // if ($stmt_check_dons->fetchColumn() > 0) {
            //    set_flash_message("Impossible de supprimer : des dons sont associés à cette campagne.", "warning");
            //    redirect('campagne_list.php');
            // }


            // Supprimer l'image de la campagne si elle existe
            if ($campagne['image_campagne'] && file_exists('../' . $campagne['image_campagne'])) {
                unlink('../' . $campagne['image_campagne']);
            }

            $stmt_delete = $pdo->prepare("DELETE FROM campagnes WHERE id = :id");
            $stmt_delete->execute([':id' => $campagne_id]);

            set_flash_message("La campagne \"" . htmlspecialchars($campagne['titre']) . "\" a été supprimée avec succès.", "success");
            redirect('campagne_list.php');

        } catch (PDOException $e) {
            // Gérer les contraintes de clé étrangère si des dons y sont liés par exemple.
            if ($e->getCode() == '23000') { // Integrity constraint violation
                 set_flash_message("Impossible de supprimer la campagne car elle est liée à d'autres enregistrements (ex: dons).", "danger");
            } else {
                set_flash_message("Erreur lors de la suppression de la campagne: " . $e->getMessage(), "danger"); // DEV
                // set_flash_message("Erreur lors de la suppression de la campagne.", "danger"); // PROD
            }
            redirect('campagne_list.php?id=' . $campagne_id);
        }
    } else {
        redirect('campagne_list.php');
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
        .card-custom .card-header-custom {background: linear-gradient(to right, #dc3545, #c82333); color: white; font-weight: 500; padding: 0.8rem 1.2rem;} /* Red for delete */
        .card-custom .card-body {padding: 1.5rem;}
        .btn-gradient { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border: none; color: white; padding: 10px 20px; border-radius: 25px; transition: all 0.3s ease; font-weight: 500;}
        .btn-gradient:hover { background: linear-gradient(135deg, var(--secondary-color), var(--primary-color)); transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,0,0,0.15); color: white;}
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
            <li class="active">
                <a href="#campagneSubmenu" data-bs-toggle="collapse" aria-expanded="true" class="dropdown-toggle"><i class="fas fa-bullhorn"></i> Campagnes</a>
                <ul class="collapse list-unstyled show" id="campagneSubmenu">
                    <li><a href="campagne_add.php"><i class="fas fa-plus-circle"></i> Ajouter Campagne</a></li>
                    <li class="active"><a href="campagne_list.php"><i class="fas fa-list-ul"></i> Gérer Campagnes</a></li>
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
            <!-- Copier la navbar interne -->
             <div class="container-fluid">
                <button type="button" id="sidebarCollapse" class="sidebar-toggler"><i class="fas fa-align-left"></i></button>
                <span class="navbar-brand mb-0 h1 d-none d-md-block">Supprimer la Campagne</span>
                <div class="d-flex align-items-center">
                    <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </div>
            </div>
        </nav>

        <?php display_flash_message(); ?>

        <div class="card card-custom">
            <div class="card-header-custom">
                <i class="fas fa-trash-alt"></i> Confirmation de suppression
            </div>
            <div class="card-body">
                <p>Êtes-vous sûr de vouloir supprimer la campagne suivante ?</p>
                <h5><?= htmlspecialchars($campagne['titre']) ?></h5>
                <p class="text-danger"><i class="fas fa-exclamation-triangle"></i> Cette action est irréversible. L'image associée sera également supprimée.</p>
                <p class="text-warning">Si des dons sont liés à cette campagne, la suppression pourrait échouer pour maintenir l'intégrité des données.</p>
                
                <form method="POST" action="campagne_delete.php?id=<?= $campagne_id ?>">
                    <input type="hidden" name="confirm_delete" value="1">
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Oui, supprimer</button>
                    <a href="campagne_list.php" class="btn btn-secondary"><i class="fas fa-times"></i> Non, annuler</a>
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