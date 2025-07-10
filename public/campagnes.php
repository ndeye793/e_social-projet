<?php
session_start();
require_once '../config/db.php';  // Assure-toi que $pdo est défini ici
require_once '../includes/fonctions.php';
include_once '../includes/navbar.php';
$pdo = getPDO(); // Récupérer la connexion PDO

// Initialisation des variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categorie_filter = isset($_GET['categorie']) ? (int)$_GET['categorie'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 6; // Nombre de campagnes par page
$offset = ($page - 1) * $perPage;

// Construire la requête de base avec filtre statut 'en cours'
$whereClauses = ["c.statut = 'en cours'"];
$params = [];

// Recherche
if ($search !== '') {
    $whereClauses[] = "(c.titre LIKE :search OR c.description LIKE :search OR cat.nom_categorie LIKE :search)";
    $params['search'] = "%$search%";
}

// Filtre catégorie
if ($categorie_filter > 0) {
    $whereClauses[] = "c.categorie_id = :categorie_id";
    $params['categorie_id'] = $categorie_filter;
}

$whereSql = implode(" AND ", $whereClauses);

// Compter le total des campagnes pour pagination
$countSql = "SELECT COUNT(*) as total FROM campagnes c
             JOIN categories cat ON c.categorie_id = cat.id
             WHERE $whereSql";

$stmtCount = $pdo->prepare($countSql);
$stmtCount->execute($params);
$totalCampagnes = $stmtCount->fetchColumn();
$totalPages = ceil($totalCampagnes / $perPage);

// Récupérer les campagnes avec limites et filtres
$sql = "SELECT c.*, cat.nom_categorie, b.prenom AS prenom_beneficiaire, b.nom AS nom_beneficiaire
        FROM campagnes c
        JOIN categories cat ON c.categorie_id = cat.id
        JOIN beneficiaires b ON c.beneficiaire_id = b.id
        WHERE $whereSql
        ORDER BY c.date_creation DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);

// Ajouter paramètres limit et offset
// Attention : avec PDO il faut binder limit et offset en tant qu'entiers
foreach ($params as $key => &$val) {
    $stmt->bindParam(":$key", $val);
}
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

$stmt->execute();
$campagnes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer toutes les catégories pour filtre
$catSql = "SELECT id, nom_categorie FROM categories ORDER BY nom_categorie";
$catStmt = $pdo->query($catSql);
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour afficher un message flash
function flashMessage() {
    if (isset($_SESSION['flash_message'])) {
        echo '<div class="alert alert-info text-center">'.htmlspecialchars($_SESSION['flash_message']).'</div>';
        unset($_SESSION['flash_message']);
    }
}
?>


<div class="container py-5">
    <div class="text-center mb-5 animate__animated animate__fadeInDown">
        <h1 class="display-4 fw-bold text-success">Campagnes de solidarité au Sénégal</h1>
        <p class="lead">Unissons nos cœurs, tendons la main à ceux qui en ont besoin.</p>

        <!-- Formulaire recherche et filtres -->
        <form class="row g-3 justify-content-center mt-4" method="GET" action="">
            <div class="col-md-5">
                <input class="form-control" type="search" name="search" placeholder="Rechercher une campagne..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3">
                <select name="categorie" class="form-select">
                    <option value="0">Toutes les catégories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $categorie_filter ? 'selected' : '' ?>><?= htmlspecialchars($cat['nom_categorie']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-success w-100" type="submit">Filtrer</button>
            </div>
        </form>
    </div>

    <?php flashMessage(); ?>

    <?php if (!empty($campagnes)): ?>
    <div class="row gy-4">
        <?php foreach ($campagnes as $campagne): ?>
            <div class="col-md-6 col-lg-4 animate__animated animate__zoomIn">
                <div class="card shadow-lg border-0 h-100 d-flex flex-column">
                    <?php
                        $imagePath = "../assets/images/campagnes/" . htmlspecialchars($campagne['image_campagne']);
                        $imageExist = file_exists($imagePath);
                    ?>
                    <img src="<?= $imageExist ? $imagePath : '../assets/images/default.jpg' ?>" class="card-img-top" alt="Image campagne">

                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title text-primary fw-bold"><?= htmlspecialchars($campagne['titre']) ?></h5>
                        <p class="card-text small text-muted mb-1">Catégorie : <?= htmlspecialchars($campagne['nom_categorie']) ?></p>
                        <p class="card-text small text-muted mb-2">Bénéficiaire : <?= htmlspecialchars($campagne['prenom_beneficiaire'] . ' ' . $campagne['nom_beneficiaire']) ?></p>
                        <p class="card-text"><?= nl2br(htmlspecialchars(substr($campagne['description'], 0, 150))) ?>...</p>

                        <?php
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
                            <div class="progress mb-2" style="height: 22px;">
                                <div class="progress-bar <?= $barColor ?> fw-bold" role="progressbar" style="width: <?= $pourcentage ?>%">
                                    <?= number_format($pourcentage, 0) ?>%
                                </div>
                            </div>

                            <p class="fw-bold text-secondary mb-1">Objectif : <?= number_format($vise, 0, ',', ' ') ?> FCFA</p>
                            <p class="text-muted small mb-3">Montant récolté : <strong><?= number_format($atteint, 0, ',', ' ') ?> FCFA</strong></p>

                            <!-- Boutons d'action -->
                            ...
                            <div class="mt-auto">
                                <a href="campagne.php?id=<?= $campagne['id'] ?>" class="btn btn-outline-success w-100 mb-2">Voir la campagne</a>
                                <a href="donner.php?campagne_id=<?= $campagne['id'] ?>" class="btn btn-success w-100">Faire un don</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>


        <!-- Pagination -->
        <nav aria-label="Pagination des campagnes" class="mt-5">
            <ul class="pagination justify-content-center">
                <!-- Bouton page précédente -->
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" tabindex="-1">Précédent</a>
                </li>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>

                <!-- Bouton page suivante -->
                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Suivant</a>
                </li>
            </ul>
        </nav>

    <?php else: ?>
        <div class="alert alert-warning text-center animate__animated animate__fadeIn">
            <h4 class="alert-heading">Aucune campagne trouvée</h4>
            <p>Essayez de modifier vos filtres ou votre recherche pour afficher des campagnes.</p>
            <a href="liste_campagnes.php" class="btn btn-outline-warning">Voir toutes les campagnes</a>
        </div>
    <?php endif; ?>

    <div class="mt-5 text-center animate__animated animate__fadeInUp">
        <h3 class="fw-bold text-warning">"Un geste, un sourire, un avenir."</h3>
        <p class="fst-italic">Participez à changer des vies aujourd’hui. Ensemble, bâtissons un Sénégal plus solidaire.</p>
        <a href="../public/contact.php" class="btn btn-warning btn-lg">Nous contacter</a>
    </div>
</div>

<!-- Animate.css CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
<?php include_once '../includes/footer.php'; ?>
