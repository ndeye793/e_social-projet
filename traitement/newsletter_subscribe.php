<?php
// traitement/newsletter_subscribe.php
require_once '../config/db.php';
require_once '../config/constantes.php'; 
require_once '../includes/fonctions.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize($_POST['email_newsletter'] ?? ''); // Assurez-vous que le name de l'input est 'email_newsletter'

    $redirect_url = $_SERVER['HTTP_REFERER'] ?? SITE_URL; // Redirige vers la page précédente ou l'accueil

    if (empty($email)) {
        set_flash_message("Veuillez fournir une adresse email.", "warning");
        redirect($redirect_url);
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash_message("Format d'email invalide.", "warning");
        redirect($redirect_url);
    } else {
        try {
            $pdo = getPDO();
            // Vérifier si l'email n'est pas déjà abonné
            $stmt_check = $pdo->prepare("SELECT id FROM abonnements_newsletter WHERE email = :email");
            $stmt_check->bindParam(':email', $email);
            $stmt_check->execute();

            if ($stmt_check->fetch()) {
                set_flash_message("Vous êtes déjà abonné à notre newsletter avec cet email.", "info");
            } else {
                // Inscrire l'email
                $sql = "INSERT INTO abonnements_newsletter (email) VALUES (:email)";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                set_flash_message("Merci ! Vous êtes maintenant abonné à notre newsletter.", "success");
                // Optionnel: envoyer un email de confirmation d'abonnement
            }
        } catch (PDOException $e) {
            // Log $e->getMessage()
            set_flash_message("Une erreur est survenue lors de l'abonnement. Veuillez réessayer.", "danger");
            error_log("Erreur newsletter_subscribe.php: " . $e->getMessage());
        }
        redirect($redirect_url);
    }
} else {
    redirect(SITE_URL); // Rediriger vers l'accueil si pas de POST
}
?>