<?php
session_start();

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/fonctions.php';

$error = "";

// Traitement du formulaire en tout premier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['mot_de_passe'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];

            // Redirection avant tout affichage
            if ($user['role'] === 'admin') {
                header("Location: ../admin/index.php");
            } else {
                header("Location: ../utilisateur/dashboard.php");
            }
            exit;
        } else {
            $error = "Email ou mot de passe incorrect.";
        }
    }
}

// Inclusion de la navbar après les redirections
require_once __DIR__ . '/../includes/navbar.php';

?>
<!-- Hero Section -->
<section class="login-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 d-none d-lg-block">
                <img src="../assets/images/login.svg" alt="Connexion" class="img-fluid animate__animated animate__fadeInLeft">
            </div>
            <div class="col-lg-6">
                <div class="card shadow-lg border-0 animate__animated animate__fadeInRight">
                    <div class="card-header bg-primary text-white py-4">
                        <h2 class="h3 mb-0 text-center"><i class="fas fa-sign-in-alt me-2"></i> Connectez-vous</h2>
                    </div>
                    <div class="card-body p-4 p-md-5">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger animate__animated animate__shakeX">
                                <i class="fas fa-exclamation-circle me-2"></i> <?= $error ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-4">
                                <label for="email" class="form-label"><i class="fas fa-envelope me-2"></i> Adresse email</label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email" required>
                                <div class="invalid-feedback">
                                    Veuillez entrer une adresse email valide.
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="password" class="form-label"><i class="fas fa-lock me-2"></i> Mot de passe</label>
                                <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                                <div class="invalid-feedback">
                                    Veuillez entrer votre mot de passe.
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                    <label class="form-check-label" for="remember">Se souvenir de moi</label>
                                </div>
                                <a href="forgot-password.php" class="text-primary">Mot de passe oublié ?</a>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100 py-3 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i> Se connecter
                            </button>
                            <div class="text-center">
                                <p class="mb-0">Pas encore membre ? <a href="inscription.php" class="text-primary fw-bold">S'inscrire</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<style>
.login-hero {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    padding: 6rem 0;
    min-height: 100vh;
    display: flex;
    align-items: center;
}

.card {
    border-radius: 12px;
    overflow: hidden;
}

.card-header {
    border-bottom: none;
}

.form-control-lg {
    padding: 1rem 1.25rem;
    border-radius: 8px;
}

.btn-lg {
    border-radius: 8px;
}

/* Animation personnalisée */
@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.animate-float {
    animation: float 3s ease-in-out infinite;
}
</style>

<script>
// Validation du formulaire
(() => {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation')
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>