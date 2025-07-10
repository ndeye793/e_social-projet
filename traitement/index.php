<?php
// traitement/login.php

require_once '../config/db.php';
require_once '../config/constantes.php'; 
require_once '../includes/fonctions.php';

// Rediriger si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    redirect('../dashboard.php');
}

$error_message = '';
$email_value = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $email_value = $email;

    if (empty($email) || empty($password)) {
        $error_message = "Veuillez remplir tous les champs.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Format d'email invalide.";
    } else {
        try {
            $pdo = getPDO();
            $stmt = $pdo->prepare("SELECT id, prenom, nom, email, mot_de_passe, role FROM utilisateurs WHERE email = :email AND est_actif = 1");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['mot_de_passe'])) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_prenom'] = $user['prenom'];
                $_SESSION['user_nom'] = $user['nom'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                redirect('../dashboard.php');
            } else {
                $error_message = "Email ou mot de passe incorrect, ou compte inactif.";
            }
        } catch (PDOException $e) {
            error_log("Erreur PDO lors de la connexion : " . $e->getMessage());
            $error_message = "Une erreur technique est survenue. Veuillez réessayer plus tard.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - E-Social</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Styles et polices -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: #fff;
            padding: 40px 30px;
            border-radius: 15px;
            box-shadow: 0 15px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: 25px;
            font-weight: 600;
            color: #333;
        }
        .btn-login {
            background: linear-gradient(135deg, #71b7e6, #9b59b6);
            color: #fff;
            border: none;
            width: 100%;
            padding: 10px;
            border-radius: 25px;
            font-weight: 500;
            font-size: 1.1em;
            transition: 0.3s ease;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #9b59b6, #71b7e6);
            transform: scale(1.03);
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
        }
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo-container img {
            max-width: 140px;
            animation: logoPulse 2s infinite ease-in-out;
        }
        @keyframes logoPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .extra-links {
            text-align: center;
            margin-top: 15px;
        }
        .extra-links a {
            color: #555;
            text-decoration: none;
            font-size: 0.9em;
        }
        .extra-links a:hover {
            color: #9b59b6;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="logo-container">
        <img src="../assets/images/logos/logo_esocial.png" alt="E-Social Logo"
             onerror="this.style.display='none'; this.parentElement.innerHTML='<h1><i class=\'fas fa-hands-helping\'></i> E-Social</h1>';">
    </div>

    <h2><i class="fas fa-sign-in-alt"></i> Connexion</h2>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <?php if (function_exists('display_flash_message')) display_flash_message(); ?>

    <form method="POST" action="">
        <div class="form-floating mb-3">
            <input type="email" name="email" class="form-control" id="email" placeholder="name@example.com" value="<?= htmlspecialchars($email_value) ?>" required>
            <label for="email"><i class="fas fa-envelope"></i> Email</label>
        </div>
        <div class="form-floating mb-3">
            <input type="password" name="password" class="form-control" id="password" placeholder="Mot de passe" required>
            <label for="password"><i class="fas fa-lock"></i> Mot de passe</label>
        </div>
      <button type="button" class="btn btn-login" onclick="window.location.href='dashboard.php'">
  <i class="fas fa-paper-plane"></i> Se connecter
</button>

    </form>

    <div class="extra-links mt-3">
        <a href="forgot_password.php">Mot de passe oublié ?</a><br>
        <span>Vous n'avez pas de compte ? <a href="register.php">Inscrivez-vous ici</a></span><br>
        <a href="../index.php" class="btn btn-sm btn-outline-secondary mt-2"><i class="fas fa-home"></i> Retour à l'accueil</a>
    </div>
</div>

</body>
</html>
