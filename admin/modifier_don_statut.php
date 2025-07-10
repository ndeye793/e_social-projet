<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';
require_once __DIR__ . '/../config/constantes.php';

$pdo = getPDO();

$don_id = $_GET['id'] ?? null;
$filtres_retour = $_GET; // Récupérer tous les params GET pour le lien Annuler et l'action du formulaire
unset($filtres_retour['id']); // On n'a pas besoin de l'id dans les filtres de retour

if (!$don_id) {
    $_SESSION['error_message'] = "ID de don manquant.";
    header("Location: dons.php?" . http_build_query($filtres_retour));
    exit();
}

$stmt = $pdo->prepare("SELECT d.*, u.prenom, u.nom FROM dons d LEFT JOIN utilisateurs u ON d.utilisateur_id = u.id WHERE d.id = ?");
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

$page_title = "Modifier Statut Don #" . $don['id'];
$retour_url = "dons.php?" . http_build_query($filtres_retour);
$action_form_url = "dons.php?" . http_build_query($filtres_retour); // Le formulaire soumet à gestion_dons.php

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
        body {
            background: linear-gradient(to right, #f0f4f8, #d9e4f5);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .main-content {
            flex-grow: 1;
            padding: 3rem;
        }

        .card-form {
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.75);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: all 0.4s ease-in-out;
            padding: 2.5rem;
        }

        .card-form:hover {
            transform: scale(1.01);
            box-shadow: 0 12px 38px rgba(0,0,0,0.15);
        }

        .form-select, .btn {
            border-radius: 12px;
        }

        .breadcrumb {
            background: none;
            font-size: 1rem;
        }

        h1 i {
            color: #0d6efd;
        }

        .btn-lg {
            padding: 0.75rem 2rem;
            font-size: 1.1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            border: none;
        }

        .btn-outline-secondary:hover {
            background-color: #e2e6ea;
        }
    </style>
</head>
<body>
    <div class="d-flex flex-column flex-lg-row">
        <!-- Inclusion de la sidebar à gauche -->
        

        <!-- Contenu principal -->
        <div class="main-content">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="gestion_dons.php?<?= http_build_query($filtres_retour) ?>">Gestion des Dons</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($page_title) ?></li>
                </ol>
            </nav>

            <h1 class="mb-4"><i class="fas fa-edit me-2"></i><?= htmlspecialchars($page_title) ?></h1>

            <!-- Formulaire de modification -->
            <div class="card card-form mx-auto" style="max-width: 700px;">
                <div class="card-body">
                    <form method="post" action="<?= htmlspecialchars($action_form_url) ?>">
                        <input type="hidden" name="don_id" value="<?= $don['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">

                        <p class="mb-2"><strong>Donateur :</strong> <?= htmlspecialchars(($don['prenom'] ?? '') . ' ' . ($don['nom'] ?? 'Anonyme')) ?></p>
                        <p class="mb-2"><strong>Montant :</strong> <?= number_format($don['montant'], 0, ',', ' ') ?> XOF</p>
                        <p class="mb-4"><strong>Date du don :</strong> <?= date('d/m/Y H:i', strtotime($don['date_don'])) ?></p>

                        <div class="mb-4">
                            <label for="nouveau_statut" class="form-label fs-5">Nouveau statut :</label>
                            <select id="nouveau_statut" name="nouveau_statut" class="form-select form-select-lg" required>
                                <option value="en attente" <?= $don['statut'] == 'en attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="confirmé" <?= $don['statut'] == 'confirmé' ? 'selected' : '' ?>>Confirmé</option>
                                <option value="rejeté" <?= $don['statut'] == 'rejeté' ? 'selected' : '' ?>>Rejeté</option>
                            </select>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" name="modifier_statut" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Enregistrer
                            </button>
                            <a href="<?= htmlspecialchars($retour_url) ?>" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-times me-2"></i>Annuler
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <!-- Fin du formulaire -->
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
