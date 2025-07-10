<?php
session_start();
require_once '../config/db.php';
require_once '../config/constantes.php'; // Doit définir BASE_URL, et idéalement CHEMIN_IMAGES_CAMPAGNES, URL_IMAGES_CAMPAGNES
require_once '../includes/fonctions.php';  // Doit contenir redirect(), sanitize(), display_flash_message()



$error_message = '';
$success_message = '';
$campagnes = [];
$pdo = getPDO();

// --- Définitions des chemins si non présents dans constantes.php (MAUVAISE PRATIQUE, à mettre dans constantes.php) ---
if (!defined('CHEMIN_RACINE')) {
    define('CHEMIN_RACINE', dirname(__DIR__)); // Suppose que ce script est dans traitement/, donc config/ est à ../config
}
if (!defined('CHEMIN_IMAGES_CAMPAGNES')) {
    define('CHEMIN_IMAGES_CAMPAGNES', CHEMIN_RACINE . '/assets/images/campagnes/');
}
if (!defined('URL_IMAGES_CAMPAGNES')) {
    // BASE_URL doit se terminer par un /
    define('URL_IMAGES_CAMPAGNES', rtrim(BASE_URL, '/') . '/assets/images/campagnes/');
}
$defaultImageLocalPath = CHEMIN_IMAGES_CAMPAGNES . 'default.jpg';
$defaultImageUrl = URL_IMAGES_CAMPAGNES . 'default.jpg';


// --- Action : Supprimer une campagne ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_campaign' && isset($_POST['campaign_id'])) {
    $campaign_id = (int)$_POST['campaign_id'];
    try {
        $stmt_img = $pdo->prepare("SELECT image_campagne FROM campagnes WHERE id = :id");
        $stmt_img->execute([':id' => $campaign_id]);
        $image_file = $stmt_img->fetchColumn();
        if ($image_file && file_exists(CHEMIN_IMAGES_CAMPAGNES . $image_file)) {
            unlink(CHEMIN_IMAGES_CAMPAGNES . $image_file);
        }

        $stmt = $pdo->prepare("DELETE FROM campagnes WHERE id = :id");
        $stmt->execute([':id' => $campaign_id]);
        $success_message = "Campagne supprimée avec succès.";
    } catch (PDOException $e) {
        $error_message = "Erreur lors de la suppression de la campagne : " . $e->getMessage();
    }
}

// --- Récupération des campagnes ---
$searchTerm = isset($_GET['search']) && function_exists('sanitize') ? sanitize($_GET['search']) : (isset($_GET['search']) ? $_GET['search'] : '');
$filterStatut = isset($_GET['statut']) && function_exists('sanitize') ? sanitize($_GET['statut']) : (isset($_GET['statut']) ? $_GET['statut'] : '');
$sortOrder = isset($_GET['sort']) && function_exists('sanitize') ? sanitize($_GET['sort']) : (isset($_GET['sort']) ? $_GET['sort'] : 'date_creation_desc');

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 9; // Ajusté pour 3 cartes par ligne
$offset = ($page - 1) * $perPage;

$baseSql = "SELECT c.id, c.titre, c.montant_vise, c.montant_atteint, c.date_fin, c.statut, c.image_campagne, c.date_creation, 
            cat.nom_categorie, CONCAT(b.prenom, ' ', b.nom) AS nom_beneficiaire
            FROM campagnes c
            LEFT JOIN categories cat ON c.categorie_id = cat.id
            LEFT JOIN beneficiaires b ON c.beneficiaire_id = b.id";
$countBaseSql = "SELECT COUNT(c.id) FROM campagnes c LEFT JOIN categories cat ON c.categorie_id = cat.id LEFT JOIN beneficiaires b ON c.beneficiaire_id = b.id";

$whereClauses = [];
$params = [];

if (!empty($searchTerm)) {
    $whereClauses[] = "(c.titre LIKE :search OR cat.nom_categorie LIKE :search OR CONCAT(b.prenom, ' ', b.nom) LIKE :search)";
    $params[':search'] = "%$searchTerm%";
}
if (!empty($filterStatut)) {
    $whereClauses[] = "c.statut = :statut";
    $params[':statut'] = $filterStatut;
}

$whereSql = "";
if (!empty($whereClauses)) {
    $whereSql = " WHERE " . implode(" AND ", $whereClauses);
}

$countSql = $countBaseSql . $whereSql;
$sql = $baseSql . $whereSql;

switch ($sortOrder) {
    case 'titre_asc': $sql .= " ORDER BY c.titre ASC"; break;
    case 'titre_desc': $sql .= " ORDER BY c.titre DESC"; break;
    case 'date_creation_asc': $sql .= " ORDER BY c.date_creation ASC"; break;
    case 'montant_vise_asc': $sql .= " ORDER BY c.montant_vise ASC"; break;
    case 'montant_vise_desc': $sql .= " ORDER BY c.montant_vise DESC"; break;
    default: $sql .= " ORDER BY c.date_creation DESC";
}

$sql .= " LIMIT :limit OFFSET :offset";

try {
    $stmtCount = $pdo->prepare($countSql);
    $stmtCount->execute($params);
    $totalCampagnes = (int)$stmtCount->fetchColumn();
    $totalPages = ceil($totalCampagnes / $perPage);
     if ($totalPages == 0 && $totalCampagnes > 0) $totalPages = 1;
     if ($page > $totalPages && $totalPages > 0) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
     }


    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $campagnes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Erreur lors de la récupération des campagnes : " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Campagnes - E-Social Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto+Condensed:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --admin-primary: #2c3e50; /* Bleu nuit profond */
            --admin-secondary: #34495e; /* Gris bleuté */
            --admin-light: #f0f2f5;    /* Gris très clair (un peu plus doux) */
            --admin-accent: #3498db;   /* Bleu vif pour accents */
            --admin-success: #2ecc71; /* Vert */
            --admin-warning: #f39c12; /* Orange */
            --admin-danger: #e74c3c;  /* Rouge */
            --card-bg: #ffffff;
            --card-shadow: rgba(0, 0, 0, 0.08); /* Ombre plus subtile */
            --card-hover-shadow: rgba(0, 0, 0, 0.15);
        }
        body {
            font-family: 'Roboto Condensed', sans-serif;
            background-color: var(--admin-light);
            color: var(--admin-secondary);
        }
        .admin-navbar {
            background-color: var(--admin-primary);
            box-shadow: 0 4px 12px rgba(0,0,0,.15);
        }
        .admin-navbar .navbar-brand, .admin-navbar .nav-link {
            color: white;
            font-family: 'Poppins', sans-serif;
            transition: color 0.3s ease;
        }
        .admin-navbar .nav-link:hover, .admin-navbar .navbar-brand:hover {
            color: var(--admin-accent);
        }
        .admin-navbar .nav-link.active {
            font-weight: 700;
            color: var(--admin-accent);
            /* border-bottom: 3px solid var(--admin-accent); */
            background-color: rgba(255,255,255,0.1);
            border-radius: 0.25rem;
        }
        .page-header {
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-secondary) 100%);
            color: white;
            padding: 2.5rem 1rem; /* Padding horizontal pour mobile */
            margin-bottom: 2.5rem;
            text-align: center;
            border-bottom: 5px solid var(--admin-accent);
            position: relative;
            overflow: hidden;
        }
        /* Vagues décoratives pour le header */
        .page-header::before {
            content: "";
            position: absolute;
            bottom: 0; left: 0;
            width: 100%; height: 80px;
            background: url('data:image/svg+xml;utf8,<svg viewBox="0 0 1200 120" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none"><path d="M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z" fill="rgba(255,255,255,0.1)"/></svg>');
            background-repeat: no-repeat;
            background-position: bottom;
            background-size: cover; /* S'assurer que ça couvre */
        }

        .page-header h1 {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 2.5rem; /* Un peu réduit pour mobile-first */
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
            position: relative; z-index: 1;
        }
        @media (min-width: 768px) {
            .page-header h1 { font-size: 2.8rem; }
        }

        .filter-section {
            background-color: var(--card-bg);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 6px 18px var(--card-shadow);
        }
        .filter-section .form-label { font-weight: 600; color: var(--admin-primary); }
        .filter-section .btn-primary { background-color: var(--admin-accent); border-color: var(--admin-accent); }

        .campaign-card {
            background-color: var(--card-bg);
            border-radius: 15px;
            box-shadow: 0 8px 25px var(--card-shadow);
            margin-bottom: 2rem;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%; /* Pour que les cartes sur une même ligne aient la même hauteur */
        }
        .campaign-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 35px var(--card-hover-shadow);
        }
        .campaign-image-wrapper {
            height: 200px; /* Hauteur d'image fixe */
            overflow: hidden;
            position: relative;
            background-color: #e9ecef; /* Fond si l'image ne charge pas */
        }
        .campaign-image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease, opacity 0.5s ease;
            opacity: 0; /* Commence invisible, devient visible quand chargée */
        }
        .campaign-image-wrapper img.loaded {
            opacity: 1;
        }

        .campaign-status-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: .35em .7em;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 700;
            color: white;
            text-shadow: 1px 1px 1px rgba(0,0,0,0.3);
            z-index: 1;
        }
        .statut-en-cours { background-color: var(--admin-success); }
        .statut-terminee { background-color: var(--admin-secondary); }
        .statut-suspendue { background-color: var(--admin-warning); color: #333 !important; }

        .campaign-card-body {
            padding: 1.25rem; /* Padding légèrement réduit */
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .campaign-card-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600; /* Un peu moins gras */
            color: var(--admin-primary);
            margin-bottom: 0.6rem;
            font-size: 1.15rem; /* Un peu réduit */
            line-height: 1.3;
            min-height: 2.6em; /* Espace pour 2 lignes de titre */
        }
        .campaign-details p { margin-bottom: 0.4rem; font-size: 0.85rem; color: #555; }
        .campaign-details strong { color: var(--admin-secondary); }
        .progress-container { margin: 0.8rem 0; }
        .progress { height: 10px; border-radius: 5px; background-color: #e0e0e0;}
        .progress-bar { font-weight: 600; background-color: var(--admin-accent); font-size: 0.7em; line-height: 10px; }
        
        .campaign-actions {
            margin-top: auto;
            padding-top: 0.8rem;
            border-top: 1px solid #eee; /* Ligne de séparation plus subtile */
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }
        .campaign-actions .btn {
            font-size: 0.8rem;
            padding: .35rem .7rem;
            border-radius: 18px;
            transition: all 0.2s ease; /* Transition plus rapide */
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
         .campaign-actions .btn:hover {
            transform: translateY(-1px) scale(1.05); /* Effet de survol plus subtil */
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .btn-edit { background-color: var(--admin-warning); color: #fff; border: none; }
        .btn-edit:hover { background-color: #e67e22; }
        .btn-delete { background-color: var(--admin-danger); color: #fff; border: none; }
        .btn-delete:hover { background-color: #c0392b; }
        .btn-view { background-color: var(--admin-accent); color: #fff; border: none; }
        .btn-view:hover { background-color: #2980b9; }

        .btn-add-campaign {
            background: linear-gradient(135deg, var(--admin-accent) 0%, var(--admin-success) 100%);
            color: white;
            font-weight: 600;
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4); /* Ombre colorée */
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-add-campaign:hover {
            transform: translateY(-3px) scale(1.03);
            box-shadow: 0 6px 20px rgba(46, 204, 113, 0.5);
        }
        .no-campaigns {
            text-align: center;
            padding: 3rem 1rem; /* Padding ajusté */
            background-color: var(--card-bg);
            border-radius: 12px;
            color: var(--admin-secondary);
            box-shadow: 0 6px 18px var(--card-shadow);
        }
        .no-campaigns i { font-size: 3.5rem; margin-bottom: 1rem; display: block; color: var(--admin-accent); opacity: 0.7; }
        .pagination .page-link { color: var(--admin-accent); border-radius:50%; margin: 0 2px; width: 38px; height: 38px; display:flex; align-items:center; justify-content:center;}
        .pagination .page-item.active .page-link {
            background-color: var(--admin-accent);
            border-color: var(--admin-accent);
            color: white;
            box-shadow: 0 2px 8px rgba(52,152,219,0.4);
        }
        .pagination .page-item.disabled .page-link { color: #aaa; }
        .pagination .page-link:hover { background-color: #e9ecef; }
        .pagination .page-item.active .page-link:hover { background-color: var(--admin-accent); }

    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg admin-navbar sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= htmlspecialchars(BASE_URL . 'public/dashboard.php') ?>"><i class="fas fa-hands-helping"></i> E-Social Admin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon" style="background-image: url(\"data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.8%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e\");"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(BASE_URL . 'public/dashboard.php') ?>"><i class="fas fa-tachometer-alt fa-fw me-1"></i>Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(BASE_URL . 'traitement/manage_users.php') ?>"><i class="fas fa-users fa-fw me-1"></i>Utilisateurs</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="<?= htmlspecialchars(BASE_URL . 'traitement/campagne_list.php') ?>"><i class="fas fa-bullhorn fa-fw me-1"></i>Campagnes</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(BASE_URL . 'traitement/contact_view_messages.php') ?>"><i class="fas fa-envelope-open-text fa-fw me-1"></i>Messages</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(BASE_URL . 'traitement/newsletter_list.php') ?>"><i class="fas fa-newspaper fa-fw me-1"></i>Newsletter</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars(BASE_URL . 'traitement/logout.php') ?>"><i class="fas fa-sign-out-alt fa-fw me-1"></i>Déconnexion</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="page-header">
        <div class="container">
            <h1><i class="fas fa-stream"></i> Gestion des Campagnes</h1>
        </div>
    </header>

    <div class="container mt-4">
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger d-flex align-items-center alert-dismissible fade show" role="alert">
                <i class="fas fa-times-circle fa-fw me-2"></i><?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success d-flex align-items-center alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle fa-fw me-2"></i><?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (function_exists('display_flash_message')) display_flash_message(); ?>

        <div class="d-flex justify-content-end mb-4">
             <a href="<?= htmlspecialchars(BASE_URL . 'traitement/campagne_form.php') ?>" class="btn btn-add-campaign">
                <i class="fas fa-plus-circle fa-fw me-2"></i>Ajouter une Campagne
            </a>
        </div>

        <div class="filter-section">
            <form method="GET" action="campagne_list.php" class="row g-3 align-items-center">
                <div class="col-md-4 col-sm-12">
                    <label for="search" class="form-label">Rechercher:</label>
                    <input type="text" class="form-control form-control-sm" id="search" name="search" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Titre, catégorie, bénéficiaire...">
                </div>
                <div class="col-md-3 col-sm-6">
                    <label for="statut" class="form-label">Statut:</label>
                    <select class="form-select form-select-sm" id="statut" name="statut">
                        <option value="">Tous</option>
                        <option value="en cours" <?= $filterStatut === 'en cours' ? 'selected' : '' ?>>En cours</option>
                        <option value="terminee" <?= $filterStatut === 'terminee' ? 'selected' : '' ?>>Terminée</option>
                        <option value="suspendue" <?= $filterStatut === 'suspendue' ? 'selected' : '' ?>>Suspendue</option>
                    </select>
                </div>
                <div class="col-md-3 col-sm-6">
                    <label for="sort" class="form-label">Trier par:</label>
                    <select class="form-select form-select-sm" id="sort" name="sort">
                        <option value="date_creation_desc" <?= $sortOrder === 'date_creation_desc' ? 'selected' : '' ?>>Plus récentes</option>
                        <option value="date_creation_asc" <?= $sortOrder === 'date_creation_asc' ? 'selected' : '' ?>>Plus anciennes</option>
                        <option value="titre_asc" <?= $sortOrder === 'titre_asc' ? 'selected' : '' ?>>Titre (A-Z)</option>
                        <option value="titre_desc" <?= $sortOrder === 'titre_desc' ? 'selected' : '' ?>>Titre (Z-A)</option>
                        <option value="montant_vise_desc" <?= $sortOrder === 'montant_vise_desc' ? 'selected' : '' ?>>Objectif (Décroissant)</option>
                        <option value="montant_vise_asc" <?= $sortOrder === 'montant_vise_asc' ? 'selected' : '' ?>>Objectif (Croissant)</option>
                    </select>
                </div>
                <div class="col-md-2 col-sm-12 d-grid">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-filter fa-fw me-1"></i>Filtrer</button>
                </div>
            </form>
        </div>

        <?php if (empty($campagnes)): ?>
            <div class="no-campaigns">
                <i class="fas fa-folder-open"></i>
                <h4>Aucune campagne trouvée.</h4>
                <p>
                    <?php if (!empty($searchTerm) || !empty($filterStatut)): ?>
                        Essayez d'ajuster vos filtres ou <a href="campagne_list.php" class="text-decoration-underline">réinitialisez-les</a>.
                    <?php else: ?>
                        Commencez par <a href="<?= htmlspecialchars(BASE_URL . 'traitement/campagne_form.php') ?>" class="text-decoration-underline">ajouter une nouvelle campagne</a> !
                    <?php endif; ?>
                </p>
            </div>
        <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($campagnes as $camp):
                    $pourcentage = ($camp['montant_vise'] > 0) ? min(100, (floatval($camp['montant_atteint']) / floatval($camp['montant_vise'])) * 100) : 0;
                    
                    $imageToDisplay = $defaultImageUrl; // Fallback image par défaut URL
                    if (!empty($camp['image_campagne']) && file_exists(CHEMIN_IMAGES_CAMPAGNES . $camp['image_campagne'])) {
                        $imageToDisplay = URL_IMAGES_CAMPAGNES . htmlspecialchars($camp['image_campagne']);
                    } else {
                        // Générer une image depuis Unsplash Source si pas d'image locale
                        $keywords = ['Senegal', 'solidarity', 'community', 'help', 'children', 'education', 'health']; // Mots-clés de base
                        $categoryKeyword = !empty($camp['nom_categorie']) ? strtolower(preg_replace('/[^a-z0-9,]/i', '', str_replace(' ', ',', $camp['nom_categorie']))) : '';
                        $titleFirstWord = !empty($camp['titre']) ? strtolower(preg_replace('/[^a-z0-9]/i', '', explode(' ', $camp['titre'])[0])) : '';
                        
                        $finalKeywords = 'Senegal';
                        if (!empty($categoryKeyword)) $finalKeywords .= ',' . $categoryKeyword;
                        if (!empty($titleFirstWord) && $titleFirstWord !== $categoryKeyword) $finalKeywords .= ',' . $titleFirstWord;
                        
                        // Choisir aléatoirement un des mots-clés de base à ajouter pour varier
                        $finalKeywords .= ',' . $keywords[array_rand($keywords)];

                        $imageToDisplay = "https://source.unsplash.com/400x200/?" . urlencode(strtolower($finalKeywords));
                    }
                    
                    $statutClass = '';
                    $statutText = ucfirst(htmlspecialchars($camp['statut']));
                    switch ($camp['statut']) {
                        case 'en cours': $statutClass = 'statut-en-cours'; break;
                        case 'terminee': $statutClass = 'statut-terminee'; break;
                        case 'suspendue': $statutClass = 'statut-suspendue'; break;
                    }
                ?>
                <div class="col d-flex align-items-stretch">
                    <div class="campaign-card">
                        <div class="campaign-image-wrapper">
                            <img src="<?= $imageToDisplay ?>" alt="Image de <?= htmlspecialchars($camp['titre']) ?>" loading="lazy" onload="this.classList.add('loaded')">
                            <span class="campaign-status-badge <?= $statutClass ?>"><?= $statutText ?></span>
                        </div>
                        <div class="campaign-card-body">
                            <h5 class="campaign-card-title" title="<?= htmlspecialchars($camp['titre']) ?>"><?= htmlspecialchars(mb_strimwidth($camp['titre'], 0, 50, "...")) ?></h5>
                            <div class="campaign-details">
                                <p><i class="fas fa-sitemap fa-fw me-1 text-muted opacity-75"></i><strong>Cat:</strong> <?= htmlspecialchars($camp['nom_categorie'] ?? 'N/A') ?></p>
                                <p><i class="fas fa-user-shield fa-fw me-1 text-muted opacity-75"></i><strong>Bénéf:</strong> <?= htmlspecialchars($camp['nom_beneficiaire'] ?? 'N/A') ?></p>
                                <p><i class="far fa-calendar-times fa-fw me-1 text-muted opacity-75"></i><strong>Fin:</strong> <?= $camp['date_fin'] ? htmlspecialchars(date("d M Y", strtotime($camp['date_fin']))) : 'N/D' ?></p>
                                <div class="progress-container">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span class="text-success fw-bold"><?= number_format(floatval($camp['montant_atteint']), 0, ',', ' ') ?> F</span>
                                        <span class="text-muted">Objectif: <?= number_format(floatval($camp['montant_vise']), 0, ',', ' ') ?> F</span>
                                    </div>
                                    <div class="progress" role="progressbar" aria-label="Progression de la campagne <?= htmlspecialchars($camp['titre']) ?>" aria-valuenow="<?= $pourcentage ?>" aria-valuemin="0" aria-valuemax="100">
                                        <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: <?= $pourcentage ?>%;"><?= round($pourcentage) ?>%</div>
                                    </div>
                                </div>
                            </div>
                            <div class="campaign-actions">
                                <a href="<?= htmlspecialchars(BASE_URL . 'public/campagne.php?id=' . (int)$camp['id']) ?>" class="btn btn-view" title="Voir la campagne publiquement" target="_blank"><i class="fas fa-eye fa-fw"></i></a>
                                <a href="<?= htmlspecialchars(BASE_URL . 'traitement/campagne_update.php?id=' . (int)$camp['id']) ?>" class="btn btn-edit" title="Modifier la campagne"><i class="fas fa-edit fa-fw"></i></a>
                                <button type="button" class="btn btn-delete" title="Supprimer la campagne" onclick="confirmDeleteCampaign(<?= (int)$camp['id'] ?>, '<?= htmlspecialchars(addslashes($camp['titre'])) ?>')">
                                    <i class="fas fa-trash-alt fa-fw"></i>
                                </button>
                                <form id="deleteCampaignForm-<?= (int)$camp['id'] ?>" method="POST" action="campagne_list.php?<?= http_build_query($_GET) // Conserver les filtres actuels après suppression ?>" style="display:none;">
                                    <input type="hidden" name="campaign_id" value="<?= (int)$camp['id'] ?>">
                                    <input type="hidden" name="action" value="delete_campaign">
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation des campagnes" class="mt-4 pt-3 border-top">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" aria-label="Précédent"><span aria-hidden="true">«</span></a>
                    </li>
                    <?php 
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    if ($startPage > 1) echo '<li class="page-item"><a class="page-link" href="?'.http_build_query(array_merge($_GET, ['page' => 1])).'">1</a></li>';
                    if ($startPage > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    for ($p = $startPage; $p <= $endPage; $p++): ?>
                        <li class="page-item <?= ($p == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"><?= $p ?></a>
                        </li>
                    <?php endfor; 
                    if ($endPage < $totalPages -1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    if ($endPage < $totalPages) echo '<li class="page-item"><a class="page-link" href="?'.http_build_query(array_merge($_GET, ['page' => $totalPages])).'">'.$totalPages.'</a></li>';
                    ?>
                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" aria-label="Suivant"><span aria-hidden="true">»</span></a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <footer class="text-center p-4 mt-5 border-top bg-white text-muted">
        <p class="mb-0 small">© <?= date("Y") ?> E-Social Admin Panel. Tous droits réservés.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDeleteCampaign(campaignId, campaignTitle) {
            if (confirm("Êtes-vous sûr de vouloir supprimer la campagne : \"" + campaignTitle + "\" ?\nCette action est irréversible et supprimera également son image associée si elle existe sur le serveur.")) {
                document.getElementById('deleteCampaignForm-' + campaignId).submit();
            }
        }
        // Lazy loading effect for images
        document.addEventListener("DOMContentLoaded", function() {
            var lazyImages = [].slice.call(document.querySelectorAll("img[loading='lazy']"));
            if ("IntersectionObserver" in window) {
                let lazyImageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            let lazyImage = entry.target;
                            // lazyImage.src = lazyImage.dataset.src; // Si on utilisait data-src
                            // lazyImage.classList.remove("lazy"); // Si on utilisait une classe lazy
                            lazyImageObserver.unobserve(lazyImage);
                        }
                    });
                });
                lazyImages.forEach(function(lazyImage) {
                    lazyImageObserver.observe(lazyImage);
                });
            } else {
                // Fallback pour les navigateurs plus anciens (charger tout de suite)
                lazyImages.forEach(function(lazyImage) {
                    // lazyImage.src = lazyImage.dataset.src;
                    lazyImage.classList.add('loaded'); // Pour l'effet d'opacité, si pas d'IntersectionObserver on les met directement à 'loaded'
                });
            }
        });
    </script>
</body>
</html>