<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';

$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);

    if ($email) {
        // Vérifier si l'email est déjà abonné
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM abonnements_newsletter WHERE email = ?");
        $stmt->execute([$email]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            set_flash_message("Vous êtes déjà abonné à notre newsletter.", "warning");
        } else {
            $stmt = $pdo->prepare("INSERT INTO abonnements_newsletter (email) VALUES (?)");
            $stmt->execute([$email]);
            set_flash_message("Merci ! Vous êtes maintenant abonné à notre newsletter.", "success");
        }
    } else {
        set_flash_message("Adresse email invalide.", "danger");
    }
} else {
    set_flash_message("Une erreur est survenue.", "danger");
}

header("Location: ../index.php");
exit;



