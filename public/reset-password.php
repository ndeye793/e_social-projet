<?php
session_start();
require_once __DIR__ . '/../config/constantes.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';

$page_title = "Réinitialisation du mot de passe - E-Social";

$token = $_GET['token'] ?? '';
$new_password = $confirm_password = '';
$errors = [];
$success_message = '';

if (empty($token)) {
    die("Lien invalide ou expiré.");
}

$pdo = getPDO();

// Vérifie si le token existe et n'a pas expiré
$stmt = $pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$reset_request = $stmt->fetch();

if (!$reset_request) {
    die("Ce lien de réinitialisation est invalide ou a expiré.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || strlen($new_password) < 6) {
        $errors['new_password'] = "Le mot de passe doit comporter au moins 6 caractères.";
    }

    if ($new_password !== $confirm_password) {
        $errors['confirm_password'] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($errors)) {
        // Mettre à jour le mot de passe
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE email = ?");
        $stmt->execute([$hashed_password, $reset_request['email']]);

        // Supprimer la demande de réinitialisation
        $pdo->prepare("DELETE FROM password_resets WHERE email = ?")->execute([$reset_request['email']]);

        $_SESSION['success_flash_message'] = "Mot de passe mis à jour avec succès. Vous pouvez maintenant vous connecter.";
        header("Location: login.php");
        exit;
    }
}
?>

<?php include_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container mt-5" style="max-width: 500px;">
    <h2 class="mb-4">Nouveau mot de passe</h2>

    <form method="post">
        <div class="mb-3">
            <label for="new_password" class="form-label">Nouveau mot de passe</label>
            <input type="password" name="new_password" id="new_password" class="form-control <?= isset($errors['new_password']) ? 'is-invalid' : '' ?>" required>
            <?php if (isset($errors['new_password'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['new_password']) ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirmer le mot de passe</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" required>
            <?php if (isset($errors['confirm_password'])): ?>
                <div class="invalid-feedback"><?= htmlspecialchars($errors['confirm_password']) ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-success">Réinitialiser le mot de passe</button>
    </form>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
