<?php
// traitement/beneficiaire_list.php
require_once '../config/db.php'; // Contient getPDO, sanitize, set_flash_message, display_flash_message, SITE_URL
// require_once '../includes/fonctions.php'; // Si vous avez d'autres fonctions utiles
require_once '../config/constantes.php'; 
require_once '../includes/fonctions.php'; // adapte le chemin si nécessaire

function redirect($url) {
    header("Location: $url");
    exit;
}

$user_prenom = $_SESSION['user_prenom'] ?? 'Utilisateur';
$page_title = "Liste des Bénéficiaires - Traitement E-Social";
$pdo = getPDO();

// Pagination (simple exemple)
$beneficiaires_par_page = 10;
$page_actuelle = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($page_actuelle - 1) * $beneficiaires_par_page;

// Compter le nombre total de bénéficiaires pour la pagination
$total_beneficiaires_stmt = $pdo->query("SELECT COUNT(*) FROM beneficiaires");
$total_beneficiaires = $total_beneficiaires_stmt->fetchColumn();
$total_pages = ceil($total_beneficiaires / $beneficiaires_par_page);

// Récupérer les bénéficiaires pour la page actuelle
$stmt = $pdo->prepare("SELECT id, prenom, nom, telephone, statut, date_enregistrement FROM beneficiaires ORDER BY date_enregistrement DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $beneficiaires_par_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$beneficiaires = $stmt->fetchAll();

// Fonction pour afficher un badge de statut coloré
function getStatutBadge($statut) {
    $badge_class = 'bg-secondary';
    switch ($statut) {
        case 'en attente': $badge_class = 'bg-warning text-dark'; break;
        case 'validé': $badge_class = 'bg-success'; break;
        case 'aidé': $badge_class = 'bg-info text-dark'; break;
    }
    return '<span class="badge ' . $badge_class . '">' . htmlspecialchars(ucfirst($statut)) . '</span>';
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
        .card-custom .card-body {padding: 1.5rem;}
        .table-custom thead { background-color: var(--primary-color); color: white; }
        .table-custom tbody tr:hover { background-color: #f1f3f5; }
        .table-custom .btn-action { margin-right: 5px; padding: 0.3rem 0.6rem; font-size:0.85rem; }
        .btn-gradient { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border: none; color: white; padding: 10px 20px; border-radius: 25px; transition: all 0.3s ease; font-weight: 500;}
        .btn-gradient:hover { background: linear-gradient(135deg, var(--secondary-color), var(--primary-color)); transform: translateY(-2px); box-shadow: 0 8px 15px rgba(0,0,0,0.15); color: white;}
        #sidebar ul.CTAs { padding: 20px; }
        #sidebar ul.CTAs a { text-align: center; font-size: 0.9em; display: block; border-radius: 5px; margin-bottom: 5px; padding: 10px; color: white; }
        #sidebar ul.CTAs a.download { background: var(--secondary-color); }
        #sidebar ul.CTAs a.article { background: var(--primary-color); }
        #sidebar ul.CTAs a:hover { opacity: 0.8; }
        .pagination .page-link { color: var(--primary-color); }
        .pagination .page-item.active .page-link { background-color: var(--primary-color); border-color: var(--primary-color); color:white; }
        .pagination .page-item.disabled .page-link { color: #6c757d; }
        @media (max-width: 768px) {
            #sidebar {margin-left: -260px;} #sidebar.active {margin-left: 0;}
            #content {width: 100%; margin-left: 0;}
            .table-responsive .btn-action { margin-bottom: 5px; display:block; width:100%;}
        }
    </style>
</head>
<body>
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-hands-helping"></i> E-Social</h3>
            <span class="small-text">Panneau de Traitement</span>
        </div>
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
            <li class="active"> <!-- Lien actif pour la gestion des bénéficiaires -->
                <a href="#beneficiaireSubmenu" data-bs-toggle="collapse" aria-expanded="true" class="dropdown-toggle"><i class="fas fa-users"></i> Bénéficiaires</a>
                <ul class="collapse list-unstyled show" id="beneficiaireSubmenu">
                    <li><a href="beneficiaire_add.php"><i class="fas fa-user-plus"></i> Ajouter Bénéficiaire</a></li>
                    <li class="active"><a href="beneficiaire_list.php"><i class="fas fa-list-alt"></i> Liste des Bénéficiaires</a></li>
                </ul>
            </li>
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
                <span class="navbar-brand mb-0 h1 d-none d-md-block">Liste des Bénéficiaires</span>
                <div class="d-flex align-items-center">
                    <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </div>
            </div>
        </nav>

        <?php display_flash_message(); ?>

        <div class="card card-custom">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <span><i class="fas fa-list-alt"></i> Bénéficiaires Enregistrés (<?= $total_beneficiaires ?>)</span>
                <a href="beneficiaire_add.php" class="btn btn-light btn-sm"><i class="fas fa-user-plus"></i> Ajouter un bénéficiaire</a>
            </div>
            <div class="card-body">
                <?php if (empty($beneficiaires)): ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-2x mb-2"></i><br>
                        Aucun bénéficiaire enregistré pour le moment.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-custom table-striped">
                            <thead>
                                <tr>
                                    <th>#ID</th>
                                    <th>Prénom</th>
                                    <th>Nom</th>
                                    <th>Téléphone</th>
                                    <th>Statut</th>
                                    <th>Date Enreg.</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($beneficiaires as $beneficiaire): ?>
                                <tr>
                                    <td><?= htmlspecialchars($beneficiaire['id']) ?></td>
                                    <td><?= htmlspecialchars($beneficiaire['prenom']) ?></td>
                                    <td><?= htmlspecialchars($beneficiaire['nom']) ?></td>
                                    <td><?= htmlspecialchars($beneficiaire['telephone'] ?: 'N/A') ?></td>
                                    <td><?= getStatutBadge($beneficiaire['statut']) ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($beneficiaire['date_enregistrement'])) ?></td>
                                    <td class="text-center">
                                        <a href="beneficiaire_view.php?id=<?= $beneficiaire['id'] ?>" class="btn btn-info btn-sm btn-action" title="Voir détails"><i class="fas fa-eye"></i></a>
                                        <a href="beneficiaire_update.php?id=<?= $beneficiaire['id'] ?>" class="btn btn-primary btn-sm btn-action" title="Modifier"><i class="fas fa-edit"></i></a>
                                        <a href="beneficiaire_delete.php?id=<?= $beneficiaire['id'] ?>" class="btn btn-danger btn-sm btn-action" title="Supprimer"><i class="fas fa-trash-alt"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center mt-4">
                            <li class="page-item <?= ($page_actuelle <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page_actuelle - 1 ?>" aria-label="Précédent">
                                    <span aria-hidden="true">«</span>
                                </a>
                            </li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($i == $page_actuelle) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                            <?php endfor; ?>
                            <li class="page-item <?= ($page_actuelle >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page_actuelle + 1 ?>" aria-label="Suivant">
                                    <span aria-hidden="true">»</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
        <footer class="mt-auto pt-3 text-center text-muted">
            <p>© <?= date('Y') ?> E-Social. Tous droits réservés.</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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