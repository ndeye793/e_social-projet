<?php
require_once '../config/db.php';
require_once '../includes/navbar.php';

// Pour tests uniquement – ID utilisateur fixe
$utilisateur_id = 1;

$pdo = getPDO();

// Actions : marquer comme lu ou supprimer
if (isset($_GET['action']) && isset($_GET['id'])) {
    $notif_id = intval($_GET['id']);
    if ($_GET['action'] === 'lu') {
        // Marquer comme lu seulement si notification personnelle
        $pdo->prepare("UPDATE notifications SET lu = 1 WHERE id = :id AND utilisateur_id = :uid")
            ->execute([':id' => $notif_id, ':uid' => $utilisateur_id]);
    }
    if ($_GET['action'] === 'supprimer') {
        // Supprimer seulement si notification personnelle
        $pdo->prepare("DELETE FROM notifications WHERE id = :id AND utilisateur_id = :uid")
            ->execute([':id' => $notif_id, ':uid' => $utilisateur_id]);
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'tout_lu') {
    // Marquer toutes notifications perso comme lues
    $pdo->prepare("UPDATE notifications SET lu = 1 WHERE utilisateur_id = :uid")
        ->execute([':uid' => $utilisateur_id]);
}

// Recherche et filtre
$filtre = $_GET['filtre'] ?? 'toutes';
$mot_cle = $_GET['q'] ?? '';

// Conditions et paramètres pour la requête
$params = [':id' => $utilisateur_id];

if ($filtre === 'lues') {
    $conditions = "(utilisateur_id IS NULL OR (utilisateur_id = :id AND lu = 1))";
} elseif ($filtre === 'non_lues') {
    $conditions = "utilisateur_id = :id AND lu = 0";
} else {
    $conditions = "(utilisateur_id IS NULL OR utilisateur_id = :id)";
}

if (!empty($mot_cle)) {
    $conditions .= " AND message LIKE :mot";
    $params[':mot'] = "%$mot_cle%";
}

// Récupération notifications : notifications générales + perso selon filtre
$sql = "SELECT id, utilisateur_id, message, lu, date_notification FROM notifications WHERE $conditions ORDER BY date_notification DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// DEBUG temporaire – afficher les notifications récupérées (à supprimer en prod)
// echo '<pre>'; print_r($notifications); echo '</pre>';

// Nombre de notifications perso non lues uniquement
$stmt2 = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE utilisateur_id = :id AND lu = 0");
$stmt2->execute([':id' => $utilisateur_id]);
$non_lues_count = $stmt2->fetchColumn();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>🔔 Mes Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(to right, #e3f2fd, #ffffff);
            font-family: 'Segoe UI', sans-serif;
        }
        .notification-card {
            border-left: 6px solid #0d6efd;
            animation: fadeIn 0.5s ease-in-out;
        }
        .notification-card:hover {
            transform: scale(1.01);
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .non-lue { background-color: #eaf4fc; }
        .lue { background-color: #f8f9fa; border-left-color: #adb5bd; }
        .btn-action { margin-left: 5px; }
        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(20px);}
            to {opacity: 1; transform: translateY(0);}
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-bell text-primary"></i> Mes Notifications
            <?php if ($non_lues_count > 0): ?>
                <span class="badge bg-danger"><?= $non_lues_count ?> non lue<?= $non_lues_count > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </h2>
        <div>
            <a href="?action=tout_lu" class="btn btn-sm btn-success me-2">
                <i class="fas fa-check-double"></i> Tout lire
            </a>
            <a href="?filtre=non_lues" class="btn btn-outline-primary btn-sm">Non lues</a>
            <a href="?filtre=lues" class="btn btn-outline-secondary btn-sm">Lues</a>
            <a href="?" class="btn btn-outline-dark btn-sm">Toutes</a>
        </div>
    </div>

    <form method="GET" class="mb-4 d-flex">
        <input type="hidden" name="filtre" value="<?= htmlspecialchars($filtre) ?>">
        <input type="text" name="q" class="form-control me-2" placeholder="🔍 Rechercher une notification..." value="<?= htmlspecialchars($mot_cle) ?>">
        <button type="submit" class="btn btn-primary">Rechercher</button>
    </form>

    <?php if (count($notifications) === 0): ?>
        <div class="alert alert-warning"><i class="fas fa-info-circle"></i> Aucune notification trouvée.</div>
    <?php else: ?>
        <?php foreach ($notifications as $notif): ?>
            <div class="card mb-3 notification-card <?= $notif['lu'] ? 'lue' : 'non-lue' ?>">
                <div class="card-body d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-title text-dark">
                            <?= $notif['lu'] ? '<i class="fas fa-envelope-open text-muted"></i>' : '<i class="fas fa-envelope text-primary"></i>' ?>
                            <?= htmlspecialchars($notif['message']) ?>
                        </h6>
                        <small class="text-muted">📅 <?= date('d/m/Y H:i', strtotime($notif['date_notification'])) ?></small>
                    </div>
                    <div>
                        <?php if (!$notif['lu'] && $notif['utilisateur_id'] === $utilisateur_id): ?>
                            <a href="?action=lu&id=<?= $notif['id'] ?>" class="btn btn-outline-primary btn-sm btn-action" title="Marquer comme lu">
                                <i class="fas fa-eye"></i>
                            </a>
                        <?php endif; ?>
                        <?php if ($notif['utilisateur_id'] === $utilisateur_id): ?>
                            <a href="?action=supprimer&id=<?= $notif['id'] ?>" onclick="return confirm('Supprimer cette notification ?')" class="btn btn-outline-danger btn-sm btn-action" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php require_once '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
