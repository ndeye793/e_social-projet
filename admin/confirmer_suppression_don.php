<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';

$pdo = getPDO();

$don_id = $_GET['id'] ?? null;
$filtres_retour = $_GET; // Récupérer tous les params GET pour le lien Annuler et l'action du formulaire
unset($filtres_retour['id']);

if (!$don_id) {
    $_SESSION['error_message'] = "ID de don manquant.";
    header("Location: dons.php?" . http_build_query($filtres_retour));
    exit();
}

$stmt = $pdo->prepare("SELECT d.id, d.montant, u.prenom, u.nom FROM dons d LEFT JOIN utilisateurs u ON d.utilisateur_id = u.id WHERE d.id = ?");
$stmt->execute([$don_id]);
$don = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$don) {
    $_SESSION['error_message'] = "Don non trouvé.";
    header("Location: dons.php?" . http_build_query($filtres_retour));
    exit();
}

if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
$csrf_token = generate_csrf_token();

$page_title = "Confirmer Suppression Don #" . $don['id'];
$retour_url = "dons.php?" . http_build_query($filtres_retour);
$action_form_url = "dons.php?" . http_build_query($filtres_retour);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - E-Social Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <style>
        /* Réutiliser les styles de base de gestion_dons.php ou un fichier CSS commun */
        :root { /* ... vos variables CSS ... */ }
        body { background-color: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar { /* ... style sidebar ... */ }
        .main-content { /* ... style main-content ... */ }
        .card-form { background: white; border-radius: 12px; box-shadow: 0 6px 25px rgba(0,0,0,0.07); padding: 2rem; margin-top: 2rem;}
    </style>
</head>
<body>
    <div class="d-flex flex-column flex-lg-row">
     
        <div class="main-content flex-grow-1 p-4">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="gestion_dons.php?<?= http_build_query($filtres_retour) ?>">Gestion des Dons</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($page_title) ?></li>
                </ol>
            </nav>
            <h1 class="mb-4 text-danger"><i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($page_title) ?></h1>

            <div class="card card-form border-danger">
                <div class="card-body">
                    <p class="fs-5">Êtes-vous sûr de vouloir supprimer définitivement le don #<?= $don['id'] ?> ?</p>
                    <ul>
                        <li><strong>Donateur:</strong> <?= htmlspecialchars(($don['prenom'] ?? '') . ' ' . ($don['nom'] ?? 'Anonyme')) ?></li>
                        <li><strong>Montant:</strong> <?= number_format($don['montant'], 0, ',', ' ') ?> XOF</li>
                    </ul>
                    <p class="text-danger fw-bold"><i class="fas fa-info-circle"></i> Cette action est irréversible et affectera les montants collectés si le don était confirmé.</p>
                    
                    <form method="post" action="<?= htmlspecialchars($action_form_url) ?>" class="mt-4">
                        <input type="hidden" name="don_id" value="<?= $don['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <button type="submit" name="supprimer_don_confirme" class="btn btn-danger btn-lg"><i class="fas fa-trash-alt me-1"></i> Oui, supprimer ce don</button>
                        <a href="<?= htmlspecialchars($retour_url) ?>" class="btn btn-secondary btn-lg">Annuler</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>