<?php
// traitement/beneficiaire_view.php
require_once '../config/db.php';
require_once '../includes/fonctions.php'; // adapte le chemin si nécessaire
require_once '../config/constantes.php'; 
function redirect($url) {
    header("Location: $url");
    exit;
}

$user_prenom = $_SESSION['user_prenom'] ?? 'Utilisateur';
$page_title = "Détails du Bénéficiaire - Traitement E-Social";
$pdo = getPDO();

$beneficiaire_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$beneficiaire_id) {
    set_flash_message("ID de bénéficiaire invalide.", "danger");
    redirect('beneficiaire_list.php');
}

$stmt = $pdo->prepare("SELECT * FROM beneficiaires WHERE id = :id");
$stmt->execute([':id' => $beneficiaire_id]);
$beneficiaire = $stmt->fetch();

if (!$beneficiaire) {
    set_flash_message("Bénéficiaire non trouvé.", "danger");
    redirect('beneficiaire_list.php');
}

function getStatutBadge($statut) {
    switch ($statut) {
        case 'validé':
            return '<span class="badge bg-success">Validé</span>';
        case 'en_attente':
            return '<span class="badge bg-warning text-dark">En attente</span>';
        case 'rejeté':
            return '<span class="badge bg-danger">Rejeté</span>';
        default:
            return '<span class="badge bg-secondary">Inconnu</span>';
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
        #sidebar ul.CTAs { padding: 20px; }
        #sidebar ul.CTAs a { text-align: center; font-size: 0.9em; display: block; border-radius: 5px; margin-bottom: 5px; padding: 10px; color: white; }
        #sidebar ul.CTAs a.download { background: var(--secondary-color); }
        #sidebar ul.CTAs a.article { background: var(--primary-color); }
        #sidebar ul.CTAs a:hover { opacity: 0.8; }
        .detail-label { font-weight: 600; color: #555; }
        .detail-value { color: #333; }
        .identite-preview { max-width: 100%; height: auto; max-height: 300px; border: 1px solid #ddd; padding: 5px; border-radius: 5px; margin-top: 10px; }
        .no-file { color: #888; font-style: italic; }
        @media (max-width: 768px) {
            #sidebar {margin-left: -260px;} #sidebar.active {margin-left: 0;}
            #content {width: 100%; margin-left: 0;}
        }
    </style>
</head>
<body>
    <nav id="sidebar">
        <!-- Copiez la sidebar de beneficiaire_list.php -->
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
            <!-- ... autres liens ... -->
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
                <span class="navbar-brand mb-0 h1 d-none d-md-block">Détails du Bénéficiaire</span>
                <div class="d-flex align-items-center">
                    <a href="logout.php" class="btn btn-outline-danger btn-sm"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </div>
            </div>
        </nav>

        <?php display_flash_message(); ?>

        <div class="card card-custom">
            <div class="card-header-custom d-flex justify-content-between align-items-center">
                <span><i class="fas fa-user-tag"></i> Fiche de <?= htmlspecialchars($beneficiaire['prenom'] . ' ' . $beneficiaire['nom']) ?></span>
                <a href="beneficiaire_list.php" class="btn btn-light btn-sm"><i class="fas fa-arrow-left"></i> Retour à la liste</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><span class="detail-label">ID:</span> <span class="detail-value"><?= htmlspecialchars($beneficiaire['id']) ?></span></p>
                        <p><span class="detail-label">Prénom:</span> <span class="detail-value"><?= htmlspecialchars($beneficiaire['prenom']) ?></span></p>
                        <p><span class="detail-label">Nom:</span> <span class="detail-value"><?= htmlspecialchars($beneficiaire['nom']) ?></span></p>
                        <p><span class="detail-label">Téléphone:</span> <span class="detail-value"><?= htmlspecialchars($beneficiaire['telephone'] ?: 'N/A') ?></span></p>
                        <p><span class="detail-label">Adresse:</span> <span class="detail-value"><?= nl2br(htmlspecialchars($beneficiaire['adresse'] ?: 'N/A')) ?></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><span class="detail-label">Statut:</span> <span class="detail-value"><?= getStatutBadge($beneficiaire['statut']) // Réutiliser la fonction de beneficiaire_list.php ?></span></p>
                        <p><span class="detail-label">Date d'enregistrement:</span> <span class="detail-value"><?= date('d/m/Y H:i', strtotime($beneficiaire['date_enregistrement'])) ?></span></p>
                        <p><span class="detail-label">Situation:</span></p>
                        <div class="detail-value p-2 bg-light border rounded" style="max-height: 150px; overflow-y: auto;">
                            <?= nl2br(htmlspecialchars($beneficiaire['situation'])) ?>
                        </div>
                        <p class="mt-2"><span class="detail-label">Justificatif (texte):</span></p>
                        <div class="detail-value p-2 bg-light border rounded" style="max-height: 100px; overflow-y: auto;">
                             <?= nl2br(htmlspecialchars($beneficiaire['justificatif'] ?: 'Aucun justificatif textuel fourni.')) ?>
                        </div>
                    </div>
                </div>
                <hr>
                <h5>Pièces d'Identité</h5>
                <div class="row">
                    <div class="col-md-6">
                        <h6>Recto</h6>
                        <?php if ($beneficiaire['identite_recto']): ?>
                            <?php $file_ext_recto = strtolower(pathinfo($beneficiaire['identite_recto'], PATHINFO_EXTENSION)); ?>
                            <?php if (in_array($file_ext_recto, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                <a href="<?= SITE_URL . htmlspecialchars($beneficiaire['identite_recto']) ?>" target="_blank">
                                    <img src="<?= SITE_URL . htmlspecialchars($beneficiaire['identite_recto']) ?>" alt="Identité Recto" class="identite-preview img-thumbnail">
                                </a>
                            <?php elseif ($file_ext_recto == 'pdf'): ?>
                                <a href="<?= SITE_URL . htmlspecialchars($beneficiaire['identite_recto']) ?>" target="_blank" class="btn btn-outline-danger"><i class="fas fa-file-pdf"></i> Voir PDF Recto</a>
                            <?php else: ?>
                                <a href="<?= SITE_URL . htmlspecialchars($beneficiaire['identite_recto']) ?>" target="_blank" class="btn btn-outline-secondary"><i class="fas fa-file-alt"></i> Voir Fichier Recto</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="no-file">Aucun fichier pour le recto.</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6>Verso</h6>
                        <?php if ($beneficiaire['identite_verso']): ?>
                            <?php $file_ext_verso = strtolower(pathinfo($beneficiaire['identite_verso'], PATHINFO_EXTENSION)); ?>
                             <?php if (in_array($file_ext_verso, ['jpg', 'jpeg', 'png', 'gif', 'webp'])): ?>
                                <a href="<?= SITE_URL . htmlspecialchars($beneficiaire['identite_verso']) ?>" target="_blank">
                                    <img src="<?= SITE_URL . htmlspecialchars($beneficiaire['identite_verso']) ?>" alt="Identité Verso" class="identite-preview img-thumbnail">
                                </a>
                            <?php elseif ($file_ext_verso == 'pdf'): ?>
                                <a href="<?= SITE_URL . htmlspecialchars($beneficiaire['identite_verso']) ?>" target="_blank" class="btn btn-outline-danger"><i class="fas fa-file-pdf"></i> Voir PDF Verso</a>
                            <?php else: ?>
                                 <a href="<?= SITE_URL . htmlspecialchars($beneficiaire['identite_verso']) ?>" target="_blank" class="btn btn-outline-secondary"><i class="fas fa-file-alt"></i> Voir Fichier Verso</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="no-file">Aucun fichier pour le verso.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="beneficiaire_update.php?id=<?= $beneficiaire_id ?>" class="btn btn-primary"><i class="fas fa-edit"></i> Modifier ce bénéficiaire</a>
                </div>
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
        // Pour la fonction getStatutBadge, si elle n'est pas déjà incluse via un fichier JS global
        function getStatutBadge(statut) {
            let badge_class = 'bg-secondary';
            switch (statut) {
                case 'en attente': badge_class = 'bg-warning text-dark'; break;
                case 'validé': badge_class = 'bg-success'; break;
                case 'aidé': badge_class = 'bg-info text-dark'; break;
            }
            return `<span class="badge ${badge_class}">${statut.charAt(0).toUpperCase() + statut.slice(1)}</span>`;
        }
        // Si vous voulez utiliser la fonction JS pour le statut sur cette page (si vous l'injectez dynamiquement par exemple)
        // document.querySelectorAll('.statut-placeholder').forEach(el => {
        //    el.innerHTML = getStatutBadge(el.dataset.statut);
        // });
    </style>
</body>
</html>