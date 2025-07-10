<?php
session_start();
require_once __DIR__ . '/../config/constantes.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';

$page_title = "Mot de passe oublié - E-Social";

$email = "";
$errors = [];
$success_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');

    if (empty($email)) {
        $errors['email'] = "L'adresse email est requise.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Adresse email invalide.";
    } else {
        // Vérifier si l'utilisateur existe
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Générer un token de réinitialisation
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Stocker dans une table de reset (à créer si pas encore)
            $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)")
                ->execute([$email, $token, $expires]);

            // Lien de réinitialisation (ajuste le chemin si besoin)
            $reset_link = SITE_URL . "/public/reset-password.php?token=$token";

            // Envoyer l'email (remplace ceci par ta logique de mail réelle)
            // mail($email, "Réinitialisation du mot de passe", "Cliquez sur ce lien : $reset_link");
            
            $success_message = "Un lien de réinitialisation a été envoyé à votre adresse email.";
        } else {
            $errors['email'] = "Aucun compte trouvé avec cette adresse email.";
        }
    }
}
?>

<?php include_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container mt-5" style="max-width: 500px;">
    <h2 class="mb-4">Mot de passe oublié</h2>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <?php if (!empty($errors['email'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errors['email']) ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="mb-3">
            <label for="email" class="form-label">Adresse Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" id="email" value="<?= htmlspecialchars($email) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Envoyer le lien de réinitialisation</button>
    </form>
</div>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
