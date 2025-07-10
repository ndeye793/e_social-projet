<?php
require_once '../config/db.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('ID de don invalide.');
}

$id = (int) $_GET['id'];
$pdo = getPDO();

$sql = "
    SELECT d.id, d.montant, d.date_don, d.statut,
           u.nom AS utilisateur_nom, u.email AS utilisateur_email,
           c.titre AS campagne_titre, c.description AS campagne_description
    FROM dons d
    LEFT JOIN utilisateurs u ON d.utilisateur_id = u.id
    LEFT JOIN campagnes c ON d.campagne_id = c.id
    WHERE d.id = :id
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id]);
$don = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$don) {
    die('Don introuvable.');
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Détail du don #<?= htmlspecialchars($don['id']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- CSS Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: #f9f9f9;
        }
        .container {
            max-width: 960px;
        }
        .card {
            border-radius: 12px;
        }
        .card-header i {
            margin-right: 8px;
        }
        .btn {
            border-radius: 8px;
        }
    </style>
</head>
<body>

<div class="container py-5">
    <h2 class="text-center mb-5"><i class="fas fa-donate text-primary"></i> Détail du don #<?= htmlspecialchars($don['id']) ?></h2>

    <!-- Informations sur le don -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informations sur le don</h5>
        </div>
        <div class="card-body">
            <p><strong><i class="fas fa-calendar-alt"></i> Date :</strong> <?= date('d/m/Y H:i', strtotime($don['date_don'])) ?></p>
            <p><strong><i class="fas fa-money-bill-wave"></i> Montant :</strong> <?= number_format($don['montant'], 0, ',', ' ') ?> FCFA</p>
            <p><strong><i class="fas fa-flag"></i> Statut :</strong>
                <?php
                switch ($don['statut']) {
                    case 'valide':
                        echo '<span class="badge bg-success">Validé</span>';
                        break;
                    case 'en_attente':
                        echo '<span class="badge bg-warning text-dark">En attente</span>';
                        break;
                    case 'annule':
                        echo '<span class="badge bg-danger">Annulé</span>';
                        break;
                    default:
                        echo '<span class="badge bg-secondary">Inconnu</span>';
                }
                ?>
            </p>
            <p><strong><i class="fas fa-comment-dots"></i> Commentaire :</strong> <?= nl2br(htmlspecialchars($don['commentaire'] ?? 'Aucun commentaire')) ?></p>
        </div>
    </div>

    <!-- Donateur -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-user"></i> Donateur</h5>
        </div>
        <div class="card-body">
            <p><strong><i class="fas fa-user-circle"></i> Nom :</strong> <?= htmlspecialchars($don['utilisateur_nom'] ?? 'Inconnu') ?></p>
            <p><strong><i class="fas fa-envelope"></i> Email :</strong> <?= htmlspecialchars($don['utilisateur_email'] ?? 'Inconnu') ?></p>
        </div>
    </div>

    <!-- Campagne -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-bullhorn"></i> Campagne associée</h5>
        </div>
        <div class="card-body">
            <p><strong><i class="fas fa-tag"></i> Titre :</strong> <?= htmlspecialchars($don['campagne_titre'] ?? 'Campagne supprimée') ?></p>
            <p><strong><i class="fas fa-align-left"></i> Description :</strong><br><?= nl2br(htmlspecialchars($don['campagne_description'] ?? 'Aucune description')) ?></p>
        </div>
    </div>

    <!-- Actions -->
    <div class="text-center">
        <a href="mes_dons.php" class="btn btn-outline-secondary me-2"><i class="fas fa-arrow-left"></i> Retour</a>
        <a href="don_recu.php?id=<?= $don['id'] ?>" class="btn btn-success" target="_blank"><i class="fas fa-print"></i> Imprimer le reçu</a>
    </div>
</div>

<!-- JS Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
