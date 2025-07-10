<?php
require_once '../config/db.php';
require_once '../includes/fonctions.php';
require_once '../includes/navbar.php';

$pdo = getPDO();

// Paramètres pagination et recherche
$donneesParPage = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$debut = ($page - 1) * $donneesParPage;

// Filtres de recherche (optionnels)
$filtreCampagne = $_GET['campagne'] ?? '';
$filtreStatut = $_GET['statut'] ?? '';
$filtreDateDebut = $_GET['date_debut'] ?? '';
$filtreDateFin = $_GET['date_fin'] ?? '';

// Construction dynamique de la requête avec filtres sécurisés
$whereClauses = [];
$params = [];

if ($filtreCampagne !== '') {
    $whereClauses[] = 'c.titre LIKE :campagne';
    $params[':campagne'] = "%$filtreCampagne%";
}
if ($filtreStatut !== '') {
    $whereClauses[] = 'd.statut = :statut';
    $params[':statut'] = $filtreStatut;
}
if ($filtreDateDebut !== '') {
    $whereClauses[] = 'd.date_don >= :date_debut';
    $params[':date_debut'] = $filtreDateDebut . ' 00:00:00';
}
if ($filtreDateFin !== '') {
    $whereClauses[] = 'd.date_don <= :date_fin';
    $params[':date_fin'] = $filtreDateFin . ' 23:59:59';
}

$whereSql = '';
if (count($whereClauses) > 0) {
    $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
}

// Tri par colonnes (date, montant, statut)
$colonnesValides = ['date_don', 'montant', 'statut'];
$ordreColonne = in_array($_GET['tri'] ?? '', $colonnesValides) ? $_GET['tri'] : 'date_don';
$ordreSens = ($_GET['sens'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

// Récupération des dons avec filtres et tri
$sql = "
    SELECT d.id, d.montant, d.date_don, d.statut, c.titre AS campagne_titre
    FROM dons d
    LEFT JOIN campagnes c ON d.campagne_id = c.id
    $whereSql
    ORDER BY $ordreColonne $ordreSens
    LIMIT :debut, :nb
";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':debut', $debut, PDO::PARAM_INT);
$stmt->bindValue(':nb', $donneesParPage, PDO::PARAM_INT);
$stmt->execute();
$dons = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nombre total pour pagination
$sqlCount = "SELECT COUNT(*) FROM dons d LEFT JOIN campagnes c ON d.campagne_id = c.id $whereSql";
$stmtCount = $pdo->prepare($sqlCount);
foreach ($params as $key => $val) {
    $stmtCount->bindValue($key, $val);
}
$stmtCount->execute();
$totalDons = $stmtCount->fetchColumn();
$totalPages = ceil($totalDons / $donneesParPage);

function statutBadge($statut) {
    switch ($statut) {
        case 'valide':
            return '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Validé</span>';
        case 'en_attente':
            return '<span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half"></i> En attente</span>';
        case 'annule':
            return '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Annulé</span>';
        default:
            return '<span class="badge bg-secondary"><i class="fas fa-question-circle"></i> Inconnu</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Mes Dons | E-Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        th a { color: inherit; text-decoration: none; }
        th a:hover { text-decoration: underline; }
        .table-responsive { max-height: 600px; overflow-y: auto; }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fas fa-hand-holding-heart"></i> Historique des Dons</h2>
        <a href="mes_dons.php" class="btn btn-outline-primary" title="Rafraîchir la liste">
            <i class="fas fa-sync-alt"></i> Rafraîchir
        </a>
    </div>

    <!-- Formulaire de filtre -->
    <form method="GET" class="row g-3 mb-4">
        <div class="col-md-3">
            <input type="text" name="campagne" class="form-control" placeholder="Recherche campagne" value="<?php echo htmlspecialchars($filtreCampagne); ?>" />
        </div>
        <div class="col-md-2">
            <select name="statut" class="form-select">
                <option value="">Tous les statuts</option>
                <option value="valide" <?php if($filtreStatut==='valide') echo 'selected'; ?>>Validé</option>
                <option value="en_attente" <?php if($filtreStatut==='en_attente') echo 'selected'; ?>>En attente</option>
                <option value="annule" <?php if($filtreStatut==='annule') echo 'selected'; ?>>Annulé</option>
            </select>
        </div>
        <div class="col-md-2">
            <input type="date" name="date_debut" class="form-control" value="<?php echo htmlspecialchars($filtreDateDebut); ?>" />
        </div>
        <div class="col-md-2">
            <input type="date" name="date_fin" class="form-control" value="<?php echo htmlspecialchars($filtreDateFin); ?>" />
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filtrer</button>
            <a href="mes_dons.php" class="btn btn-secondary"><i class="fas fa-eraser"></i> Réinitialiser</a>
        </div>
    </form>

    <div class="mb-3">
        <strong>Total des dons affichés :</strong> <?php echo number_format(count($dons), 0, ',', ' '); ?> /
        <strong>Total général :</strong> <?php echo number_format($totalDons, 0, ',', ' '); ?>
    </div>

    <?php if (count($dons) > 0): ?>
    <div class="table-responsive">
    <table class="table table-bordered table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <?php
                // Fonction pour générer les liens de tri
                function triLien($colonne, $label, $ordreColonne, $ordreSens) {
                    $sensOppose = ($ordreColonne === $colonne && $ordreSens === 'ASC') ? 'desc' : 'asc';
                    $icone = '';
                    if ($ordreColonne === $colonne) {
                        $icone = $ordreSens === 'ASC' ? '↑' : '↓';
                    }
                    $url = "mes_dons.php?tri=$colonne&sens=$sensOppose";
                    // Conserver filtres dans l'URL
                    foreach (['campagne', 'statut', 'date_debut', 'date_fin', 'page'] as $f) {
                        if (isset($_GET[$f]) && $f !== 'page') {
                            $url .= '&' . $f . '=' . urlencode($_GET[$f]);
                        }
                    }
                    return "<a href=\"$url\">$label $icone</a>";
                }
                ?>
                <th><?= triLien('date_don', 'Date du don', $ordreColonne, $ordreSens) ?></th>
                <th>Campagne</th>
                <th><?= triLien('montant', 'Montant (FCFA)', $ordreColonne, $ordreSens) ?></th>
                <th><?= triLien('statut', 'Statut', $ordreColonne, $ordreSens) ?></th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dons as $don): ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($don['date_don'])) ?></td>
                <td><?= htmlspecialchars($don['campagne_titre'] ?? 'Campagne supprimée') ?></td>
                <td><?= number_format($don['montant'], 0, ',', ' ') ?></td>
                <td><?= statutBadge($don['statut']) ?></td>
                <td>
                    <a href="don_detail.php?id=<?= $don['id'] ?>" class="btn btn-sm btn-info" title="Voir le détail">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="don_recu.php?id=<?= $don['id'] ?>" class="btn btn-sm btn-success" title="Télécharger reçu PDF" target="_blank">
                        <i class="fas fa-file-pdf"></i>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <!-- Pagination avancée -->
    <nav aria-label="Pagination">
      <ul class="pagination justify-content-center">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
          <a class="page-link" href="?page=<?= $page-1 ?>&tri=<?= $ordreColonne ?>&sens=<?= strtolower($ordreSens) ?>" tabindex="-1">Précédent</a>
        </li>

        <?php 
        // Limiter le nombre de pages affichées autour de la page courante
        $start = max(1, $page - 3);
        $end = min($totalPages, $page + 3);
        if ($start > 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        for ($i = $start; $i <= $end; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?>&tri=<?= $ordreColonne ?>&sens=<?= strtolower($ordreSens) ?>"><?= $i ?></a>
          </li>
        <?php endfor; 
        if ($end < $totalPages) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
        ?>

        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
          <a class="page-link" href="?page=<?= $page+1 ?>&tri=<?= $ordreColonne ?>&sens=<?= strtolower($ordreSens) ?>">Suivant</a>
        </li>
      </ul>
    </nav>

    <?php else: ?>
    <div class="alert alert-info text-center">
        <i class="fas fa-info-circle"></i> Aucun don trouvé. Essayez de modifier les filtres ou revenez plus tard.
    </div>
    <?php endif; ?>

    <div class="text-center mt-5 text-muted">
        &copy; <?= date('Y') ?> E-Social - Tous droits réservés
    </div>
</div>

<?php require_once '../includes/footer.php';  ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
