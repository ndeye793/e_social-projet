<?php
// traitement/contact_message.php
require_once '../config/db.php'; // db.php démarre la session et contient getPDO, sanitize, set_flash_message, redirect
require_once '../config/constantes.php'; 
require_once '../includes/fonctions.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = sanitize($_POST['nom'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $sujet = sanitize($_POST['sujet'] ?? '');
    $message_text = sanitize($_POST['message'] ?? ''); // Message est un mot clé SQL, donc _text

    $errors = [];

    // Validations
    if (empty($nom)) {
        $errors[] = "Le nom est requis.";
    }
    if (empty($email)) {
        $errors[] = "L'email est requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format d'email invalide.";
    }
    if (empty($sujet)) {
        $errors[] = "Le sujet est requis.";
    }
    if (empty($message_text)) {
        $errors[] = "Le message ne peut pas être vide.";
    }
    // Optionnel: Captcha ou Honeypot ici pour anti-spam

    $redirect_url = $_SERVER['HTTP_REFERER'] ?? SITE_URL . 'public/contact.php';


    if (empty($errors)) {
        try {
            $pdo = getPDO();
            $sql = "INSERT INTO messages_contact (nom, email, sujet, message) VALUES (:nom, :email, :sujet, :message)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nom', $nom);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':sujet', $sujet);
            $stmt->bindParam(':message', $message_text);
            
            $stmt->execute();

            set_flash_message("Votre message a été envoyé avec succès. Nous vous répondrons bientôt.", "success");
            // Optionnel: envoyer un email de notification à l'admin
            // mail('admin@e-social.sn', 'Nouveau message de contact: '.$sujet, $message_text, 'From: '.$email);

        } catch (PDOException $e) {
            // Log $e->getMessage()
            set_flash_message("Une erreur est survenue lors de l'envoi de votre message. Veuillez réessayer.", "danger");
             error_log("Erreur contact_message.php: " . $e->getMessage()); // Log l'erreur
        }
    } else {
        // Stocker les erreurs dans la session pour les réafficher sur la page de contact si besoin.
        // Ou simplement un message d'erreur générique.
        $_SESSION['contact_form_errors'] = $errors;
        $_SESSION['contact_form_data'] = $_POST; // Pour pré-remplir
        set_flash_message("Erreur(s) dans le formulaire : " . implode(' ', $errors), "danger");
    }
    redirect($redirect_url);

} else {
    // Si accès direct au script sans POST, rediriger
    redirect(SITE_URL . 'public/contact.php');
}
?>