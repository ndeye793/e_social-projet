<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';

$errors = [];
$success = "";

$pdo = getPDO();

$stmt = $pdo->prepare("SELECT id, titre FROM campagnes WHERE statut = 'en cours'");
$stmt->execute();
$campagnes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT id, nom_moyen FROM moyens_paiement");
$stmt->execute();
$moyens_paiement = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $montant = trim($_POST['montant'] ?? '');
    $campagne_id = intval($_POST['campagne_id'] ?? 0);
    $moyen_paiement_id = intval($_POST['moyen_paiement_id'] ?? 0);

    if (empty($nom)) $errors[] = "Le nom est obligatoire.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Un email valide est requis.";
    if (empty($montant) || !is_numeric($montant) || $montant <= 0) $errors[] = "Le montant doit être un nombre positif.";
    if ($campagne_id <= 0) $errors[] = "Veuillez choisir une campagne.";
    if ($moyen_paiement_id <= 0) $errors[] = "Veuillez choisir un moyen de paiement.";

    $preuve_paiement_path = null;
    if (isset($_FILES['preuve_paiement']) && $_FILES['preuve_paiement']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
        if ($_FILES['preuve_paiement']['error'] === UPLOAD_ERR_OK) {
            if (in_array($_FILES['preuve_paiement']['type'], $allowed_types)) {
                $ext = pathinfo($_FILES['preuve_paiement']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('preuve_') . '.' . $ext;
                $destination = __DIR__ . '/../uploads/preuves_dons/' . $filename;
                if (move_uploaded_file($_FILES['preuve_paiement']['tmp_name'], $destination)) {
                    $preuve_paiement_path = $filename;
                } else {
                    $errors[] = "Erreur lors de l'upload de la preuve de paiement.";
                }
            } else {
                $errors[] = "Type de fichier non autorisé pour la preuve de paiement.";
            }
        } else {
            $errors[] = "Erreur lors du téléchargement du fichier.";
        }
    }

    if (empty($errors)) {
        $utilisateur_id = $_SESSION['utilisateur_id'] ?? null;

       $stmt = $pdo->prepare("INSERT INTO dons (utilisateur_id, campagne_id, montant, moyen_paiement_id, statut, preuve_paiement) VALUES (?, ?, ?, ?, 'en attente', ?)");
$successInsert = $stmt->execute([$utilisateur_id, $campagne_id, $montant, $moyen_paiement_id, $preuve_paiement_path]);

if ($successInsert) {
    // Mise à jour du montant atteint dans la campagne
    $stmtUpdate = $pdo->prepare("UPDATE campagnes SET montant_atteint = montant_atteint + ? WHERE id = ?");
    $successUpdate = $stmtUpdate->execute([$montant, $campagne_id]);

    if ($successUpdate) {
        $success = "Merci pour votre don de $montant FCFA !";
        $_POST = [];
    } else {
        $errors[] = "Le don a été enregistré mais la mise à jour du montant atteint a échoué.";
    }
} else {
    $errors[] = "Erreur lors de l'enregistrement du don.";
}

    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Faire un don - E-social</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />

    <style>
        body {
            background: #f0f4ff;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .don-card {
            max-width: 480px;
            width: 100%;
            padding: 30px;
            border-radius: 15px;
            background: white;
            box-shadow: 0 12px 25px rgba(37,117,252,0.25);
            position: relative;
        }
        h1 {
            color: #2575fc;
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .form-label {
            font-weight: 600;
        }
        .input-group-text {
            background: #2575fc;
            color: white;
            border: none;
            border-radius: 0.375rem 0 0 0.375rem;
            transition: background-color 0.3s ease;
        }
        .form-control:focus {
            border-color: #2575fc;
            box-shadow: 0 0 8px rgba(37,117,252,0.6);
        }
        .input-group-text i {
            font-size: 1.3rem;
            transition: transform 0.3s ease;
        }
        /* Animation icon when input is focused */
        .form-control:focus + .input-group-text i,
        .form-control:valid + .input-group-text i {
            transform: rotate(20deg) scale(1.2);
            color: #1a5edb;
        }
        .btn-primary {
            background-color: #2575fc;
            border: none;
            font-weight: 600;
            font-size: 1.1rem;
            padding: 12px;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #1a5edb;
        }
        .alert {
            border-radius: 10px;
            box-shadow: 0 4px 18px rgba(37,117,252,0.15);
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }
        /* Animate alert fade in */
        .alert {
            animation: fadeIn 0.6s ease forwards;
        }
        @keyframes fadeIn {
            from {opacity: 0; transform: translateY(-10px);}
            to {opacity: 1; transform: translateY(0);}
        }
        .form-text {
            font-size: 0.85rem;
            color: #6c757d;
        }
        footer {
            text-align: center;
            margin-top: 2rem;
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }
    </style>
</head>
<body>

<div class="don-card shadow-sm">
    <h1>Faire un don</h1>

    <?php if ($success): ?>
        <div class="alert alert-success d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill me-2" style="font-size:1.5rem;"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>

        <!-- Nom -->
        <div class="mb-3 position-relative">
            <label for="nom" class="form-label">Nom complet *</label>
            <div class="input-group">
                <input type="text" class="form-control" id="nom" name="nom" placeholder="Votre nom complet"
                       value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required />
                <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                <div class="invalid-feedback">Veuillez entrer votre nom complet.</div>
            </div>
        </div>

        <!-- Email -->
        <div class="mb-3 position-relative">
            <label for="email" class="form-label">Adresse email *</label>
            <div class="input-group">
                <input type="email" class="form-control" id="email" name="email" placeholder="exemple@mail.com"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required />
                <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                <div class="invalid-feedback">Veuillez entrer une adresse email valide.</div>
            </div>
        </div>

        <!-- Téléphone -->
        <div class="mb-3 position-relative">
            <label for="telephone" class="form-label">Téléphone (facultatif)</label>
            <div class="input-group">
                <input type="tel" class="form-control" id="telephone" name="telephone" placeholder="77 123 45 67"
                       value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>" />
                <span class="input-group-text"><i class="bi bi-phone-fill"></i></span>
            </div>
        </div>

        <!-- Montant -->
        <div class="mb-3 position-relative">
            <label for="montant" class="form-label">Montant (FCFA) *</label>
            <div class="input-group">
                <input type="number" class="form-control" id="montant" name="montant" placeholder="1000"
                       min="100" step="100" required
                       value="<?= htmlspecialchars($_POST['montant'] ?? '') ?>" />
                <span class="input-group-text"><i class="bi bi-currency-exchange"></i></span>
                <div class="invalid-feedback">Veuillez entrer un montant valide (100 FCFA minimum).</div>
            </div>
        </div>

        <!-- Campagne -->
        <div class="mb-3 position-relative">
            <label for="campagne_id" class="form-label">Campagne *</label>
            <select class="form-select" id="campagne_id" name="campagne_id" required>
                <option value="" disabled selected>Choisir une campagne</option>
                <?php foreach ($campagnes as $campagne): ?>
                    <option value="<?= $campagne['id'] ?>" <?= (isset($_POST['campagne_id']) && $_POST['campagne_id'] == $campagne['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($campagne['titre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Veuillez choisir une campagne.</div>
        </div>

        <!-- Moyen de paiement -->
        <div class="mb-4 position-relative">
            <label for="moyen_paiement_id" class="form-label">Moyen de paiement *</label>
            <select class="form-select" id="moyen_paiement_id" name="moyen_paiement_id" required>
                <option value="" disabled selected>Choisir un moyen de paiement</option>
                <?php foreach ($moyens_paiement as $moyen): ?>
                    <option value="<?= $moyen['id'] ?>" <?= (isset($_POST['moyen_paiement_id']) && $_POST['moyen_paiement_id'] == $moyen['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($moyen['nom_moyen']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Veuillez choisir un moyen de paiement.</div>
        </div>

        <!-- Preuve de paiement -->
        <div class="mb-4 position-relative">
            <label for="preuve_paiement" class="form-label">Preuve de paiement (jpg, png, pdf) (optionnel)</label>
            <input class="form-control" type="file" id="preuve_paiement" name="preuve_paiement"
                   accept=".jpg,.jpeg,.png,.pdf" />
            <div class="form-text">Vous pouvez uploader une image ou un PDF comme preuve de paiement.</div>
        </div>

        <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-hand-heart-fill me-2"></i> Faire un don
        </button>
    </form>
</div>

<!-- Bootstrap JS + validation -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (() => {
        'use strict';

        // Form validation bootstrap native
        const forms = document.querySelectorAll('.needs-validation');

        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
</script>

</body>
</html>
