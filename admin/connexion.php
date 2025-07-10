<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (!empty($email) && !empty($password)) {
        $pdo = getPDO();
        
        // Vérification des identifiants
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['mot_de_passe'])) {
            $_SESSION['admin_connected'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_nom'] = $user['nom'];
            $_SESSION['admin_prenom'] = $user['prenom'];
            
            // Redirection vers le tableau de bord
            header('Location: ' . SITE_URL . '/admin/index.php');
            exit();
        } else {
            $error = "Identifiants incorrects ou vous n'avez pas les droits d'administration";
        }
    } else {
        $error = "Veuillez remplir tous les champs";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin - E-Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --accent-color: #f72585;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
        }
        
        .login-card {
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            border: none;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--secondary-color), var(--primary-color));
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .login-body {
            padding: 30px;
            background: white;
        }
        
        .form-control {
            padding: 12px 15px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(67,97,238,0.25);
        }
        
        .btn-login {
            background: var(--accent-color);
            border: none;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border-radius: 8px;
        }
        
        .btn-login:hover {
            background: #d91a6d;
            transform: translateY(-2px);
        }
        
        .light-spot {
            position: fixed;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            pointer-events: none;
            z-index: 0;
        }
        
        .light-spot:nth-child(1) {
            top: -100px;
            right: -100px;
        }
        
        .light-spot:nth-child(2) {
            bottom: -150px;
            left: -150px;
        }
    </style>
</head>
<body>
    <!-- Light effects -->
    <div class="light-spot"></div>
    <div class="light-spot"></div>
    
    <div class="container">
        <div class="login-container animate__animated animate__fadeIn">
            <div class="login-card">
                <div class="login-header">
                    <h3><i class="fas fa-lock me-2"></i> Connexion Admin</h3>
                    <p class="mb-0">E-Social Plateforme</p>
                </div>
                
                <div class="login-body">
                    <?php if ($error): ?>
                    <div class="alert alert-danger animate__animated animate__shakeX">
                        <?= htmlspecialchars($error) ?>
                    </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <button type="submit" class="btn btn-login w-100 text-white">
                            <i class="fas fa-sign-in-alt me-2"></i> Se connecter
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Light effect animation
        const lightSpot = document.querySelector('.light-spot');
        document.addEventListener('mousemove', (e) => {
            lightSpot.style.transform = `translate(${e.clientX * 0.05}px, ${e.clientY * 0.05}px)`;
        });
    </script>
</body>
</html>