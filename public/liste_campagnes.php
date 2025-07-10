<?php
session_start();
require_once '../config/db.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categorie_filter = isset($_GET['categorie']) ? (int)$_GET['categorie'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 6;
$offset = ($page - 1) * $perPage;

$whereClauses = ["LOWER(c.statut) = 'en cours'"];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(c.titre LIKE :search OR c.description LIKE :search OR cat.nom_categorie LIKE :search)";
    $params[':search'] = "%" . strtolower($search) . "%";
}

if ($categorie_filter > 0) {
    $whereClauses[] = "c.categorie_id = :categorie_id";
    $params[':categorie_id'] = $categorie_filter;
}

$whereSql = implode(' AND ', $whereClauses);

try {
    $pdo = getPDO();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

$countSql = "SELECT COUNT(*) FROM campagnes c
             LEFT JOIN categories cat ON c.categorie_id = cat.id
             WHERE $whereSql";
$stmtCount = $pdo->prepare($countSql);
foreach ($params as $key => $value) {
    $stmtCount->bindValue($key, $value);
}
$stmtCount->execute();
$totalCampagnes = $stmtCount->fetchColumn();
$totalPages = ceil($totalCampagnes / $perPage);

$sql = "SELECT c.*, cat.nom_categorie, b.prenom AS prenom_beneficiaire, b.nom AS nom_beneficiaire
        FROM campagnes c
        LEFT JOIN categories cat ON c.categorie_id = cat.id
        LEFT JOIN beneficiaires b ON c.beneficiaire_id = b.id
        WHERE $whereSql
        ORDER BY c.date_creation DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$campagnes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$catSql = "SELECT id, nom_categorie FROM categories ORDER BY nom_categorie";
$catStmt = $pdo->query($catSql);
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Liste des campagnes</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        h1 {
            color: #0d6efd;
            text-shadow: 0 0 4px #6c757d;
        }
        .card {
            border-radius: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 123, 255, 0.3);
        }
        .progress {
            height: 25px;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgb(255 255 255 / 0.3);
        }
        .progress-bar {
            font-weight: 600;
            font-size: 1rem;
            line-height: 25px;
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.4);
        }
        .btn-primary, .btn-success {
            border-radius: 50px;
            font-weight: 600;
            padding: 0.5rem 1.5rem;
            font-size: 1.1rem;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #084298;
        }
        .btn-success:hover {
            background-color: #19692c;
        }
        .form-control, .form-select {
            border-radius: 50px;
            padding: 0.6rem 1.2rem;
            box-shadow: none !important;
            border: 2px solid #0d6efd;
            transition: border-color 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0a58ca;
            box-shadow: 0 0 8px rgba(13,110,253,0.5);
        }
        .pagination .page-link {
            border-radius: 50%;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #0d6efd;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            color: white;
            box-shadow: 0 0 8px #0d6efd;
        }
        .pagination .page-link:hover {
            background-color: #0a58ca;
            color: white;
        }
        /* Image campagne */
        .card-img-top {
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
            max-height: 180px;
            object-fit: cover;
        }
    </style>
</head>
<body>


<?php include_once '../includes/navbar.php'; ?>

<div class="container py-5">
    <h1 class="mb-4 text-center">Liste des campagnes en cours</h1>

    <form method="GET" class="row g-3 mb-5 justify-content-center">
        <div class="col-md-5">
            <input type="text" name="search" class="form-control" placeholder="Rechercher une campagne, catégorie..." value="<?= htmlspecialchars($search) ?>" />
        </div>
        <div class="col-md-3">
            <select name="categorie" class="form-select">
                <option value="0">Toutes les catégories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $categorie_filter ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nom_categorie']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-primary"><i class="bi bi-funnel-fill"></i> Filtrer</button>
        </div>
    </form>

    <?php if (!empty($campagnes)): ?>
    <div class="row g-4">
        <?php foreach ($campagnes as $campagne): 
            $imagePath = "../assets/images/campagnes/" . htmlspecialchars($campagne['image_campagne']);
            $imageExist = file_exists($imagePath);
            $atteint = (float) $campagne['montant_atteint'];
            $vise = (float) $campagne['montant_vise'];
            $pourcentage = ($vise > 0) ? min(100, ($atteint / $vise) * 100) : 0;

            if ($pourcentage < 30) {
                $barColor = "bg-danger";
            } elseif ($pourcentage < 70) {
                $barColor = "bg-warning";
            } else {
                $barColor = "bg-success";
            }
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100 d-flex flex-column">
                <img src="<?= $imageExist ? $imagePath : '../assets/images/default.jpg' ?>" alt="Image campagne" class="card-img-top" />
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title text-primary fw-bold"><?= htmlspecialchars($campagne['titre']) ?></h5>
                    <p class="mb-1"><small class="text-muted">Catégorie : <?= htmlspecialchars($campagne['nom_categorie']) ?></small></p>
                    <p class="mb-2"><small class="text-muted">Bénéficiaire : <?= htmlspecialchars($campagne['prenom_beneficiaire'] . ' ' . $campagne['nom_beneficiaire']) ?></small></p>
                    <p class="card-text mb-3"><?= nl2br(htmlspecialchars(substr($campagne['description'], 0, 140))) ?>...</p>
                    <div class="progress mb-3" role="progressbar" aria-valuenow="<?= $pourcentage ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar <?= $barColor ?>" style="width: <?= $pourcentage ?>%;">
                            <?= number_format($pourcentage, 0) ?>%
                        </div>
                    </div>
                    <p class="mb-1 fw-semibold text-secondary">Objectif : <?= number_format($vise, 0, ',', ' ') ?> FCFA</p>
                    <p class="mb-3 text-success fw-bold">Montant récolté : <?= number_format($atteint, 0, ',', ' ') ?> FCFA</p>

                    <div class="mt-auto d-grid gap-2">
                        <a href="campagne.php?id=<?= $campagne['id'] ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-eye"></i> Voir la campagne
                        </a>
                        <a href="dons.php?campagne_id=<?= $campagne['id'] ?>" class="btn btn-success btn-sm">
                            <i class="bi bi-heart-fill"></i> Faire un don
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <nav aria-label="Pagination" class="mt-5 d-flex justify-content-center">
        <ul class="pagination">
            <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&categorie=<?= $categorie_filter ?>" aria-label="Précédent">&laquo;</a>
                </li>
            <?php endif; ?>

            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <li class="page-item <?= $p == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&categorie=<?= $categorie_filter ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&categorie=<?= $categorie_filter ?>" aria-label="Suivant">&raquo;</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>

    <?php else: ?>
        <p class="alert alert-warning text-center fs-5">Aucune campagne trouvée pour vos critères.</p>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
