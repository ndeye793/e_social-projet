<?php
session_start(); // Toujours démarrer la session en tout début

require_once __DIR__ . '/../config/db.php';
$page_title = "Inscription - Rejoignez E-Social";
require_once __DIR__ . '/../includes/navbar.php';

// Connexion PDO
$pdo = getPDO(); // ✅ Ajout nécessaire ici

// Générer un token CSRF si non défini
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Fonction pour vérifier le token CSRF
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Si l'utilisateur est déjà connecté, le rediriger
if (isset($_SESSION['utilisateur_id'])) {
    header('Location: ' . SITE_URL . '/utilisateur/dashboard.php');
    exit;
}

// Récupérer la liste des pays pour le formulaire
try {
    $stmt_pays = $pdo->query("SELECT id, nom_pays FROM pays ORDER BY nom_pays ASC");
    $pays_liste = $stmt_pays->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pays_liste = []; // Gérer l'erreur si la table pays n'est pas accessible
}

$prenom = $nom = $email = $telephone = $pays_id = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors['csrf'] = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        $prenom = sanitize($_POST['prenom'] ?? '');
        $nom = sanitize($_POST['nom'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $telephone = sanitize($_POST['telephone'] ?? '');
        $mot_de_passe = $_POST['mot_de_passe'] ?? '';
        $mot_de_passe_conf = $_POST['mot_de_passe_conf'] ?? '';
        $pays_id = sanitize($_POST['pays_id'] ?? '');

        if (empty($prenom)) $errors['prenom'] = "Le prénom est requis.";
        if (empty($nom)) $errors['nom'] = "Le nom est requis.";
        if (empty($email)) {
            $errors['email'] = "L'email est requis.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Format d'email invalide.";
        } else {
            // Vérifier si l'email existe déjà
            $stmt_check_email = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $stmt_check_email->execute([$email]);
            if ($stmt_check_email->fetch()) {
                $errors['email'] = "Cette adresse email est déjà utilisée.";
            }
        }
        if (empty($telephone)) $errors['telephone'] = "Le numéro de téléphone est requis.";
        // Validation téléphone possible ici

        if (empty($mot_de_passe)) {
            $errors['mot_de_passe'] = "Le mot de passe est requis.";
        } elseif (strlen($mot_de_passe) < 6) {
            $errors['mot_de_passe'] = "Le mot de passe doit contenir au moins 6 caractères.";
        }
        if ($mot_de_passe !== $mot_de_passe_conf) {
            $errors['mot_de_passe_conf'] = "Les mots de passe ne correspondent pas.";
        }
        if (empty($pays_id)) $errors['pays_id'] = "Veuillez sélectionner votre pays.";

        if (empty($errors)) {
            $hashed_password = password_hash($mot_de_passe, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("INSERT INTO utilisateurs (prenom, nom, email, telephone, mot_de_passe, pays_id, role) VALUES (?, ?, ?, ?, ?, ?, 'donateur')");
                $stmt->execute([$prenom, $nom, $email, $telephone, $hashed_password, $pays_id]);

                $_SESSION['success_flash_message'] = "Inscription réussie ! Vous pouvez maintenant vous connecter.";
                header("Location: " . SITE_URL . "/public/connexion.php");
                exit;
            } catch (PDOException $e) {
                $errors['db'] = "Une erreur s'est produite lors de l'inscription. Veuillez réessayer.";
                // error_log("Erreur PDO inscription: " . $e->getMessage());
            }
        }
    }
}
?>

<style>
    .form-container-register {
        min-height: 90vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding-top: 2rem;
        padding-bottom: 2rem;
        background: url('<?= SITE_URL ?>/assets/images/bg-inscription.jpg') no-repeat center center fixed; /* TODO: Image de fond inspirante */
        background-size: cover;
    }
    .form-box-register {
        background: rgba(255, 255, 255, 0.97);
        padding: 2.5rem;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        width: 100%;
        max-width: 650px; /* Un peu plus large pour plus de champs */
    }
</style>

<div class="form-container-register animate__animated animate__fadeIn">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8">
                <div class="form-box-register animate__animated animate__bounceInUp">
                    <div class="text-center mb-4">
                         <a href="<?= SITE_URL ?>/public/index.php">
                           <img src="<?= SITE_URL ?>/assets/images/logoe.png" alt="E-Social Logo" height="60" class="mb-3">
                        </a>
                        <h1 class="h3 mb-3 fw-normal">Créez votre compte E-Social</h1>
                        <p class="text-muted">
                            <!-- TODO: Texte touchant -->
                            Rejoignez notre communauté de cœurs généreux et commencez à faire une différence dès aujourd'hui.
                        </p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger p-2 mb-3 animate__animated animate__shakeX" role="alert">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $error): ?>
                                    <li><small><i class="fas fa-exclamation-triangle me-1"></i> <?= htmlspecialchars($error) ?></small></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                     <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control <?= !empty($errors['prenom']) ? 'is-invalid' : '' ?>" id="prenom" name="prenom" placeholder="Votre prénom" value="<?= htmlspecialchars($prenom) ?>" required>
                                    <label for="prenom">Prénom</label>
                                    <?php if (!empty($errors['prenom'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['prenom']) ?></div><?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control <?= !empty($errors['nom']) ? 'is-invalid' : '' ?>" id="nom" name="nom" placeholder="Votre nom" value="<?= htmlspecialchars($nom) ?>" required>
                                    <label for="nom">Nom</label>
                                    <?php if (!empty($errors['nom'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['nom']) ?></div><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="email" class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" placeholder="name@example.com" value="<?= htmlspecialchars($email) ?>" required>
                            <label for="email">Adresse Email</label>
                            <?php if (!empty($errors['email'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div><?php endif; ?>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="tel" class="form-control <?= !empty($errors['telephone']) ? 'is-invalid' : '' ?>" id="telephone" name="telephone" placeholder="Numéro de téléphone" value="<?= htmlspecialchars($telephone) ?>" required>
                                    <label for="telephone">Téléphone</label>
                                    <?php if (!empty($errors['telephone'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['telephone']) ?></div><?php endif; ?>
                                </div>
                            </div>
                             <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <select class="form-select <?= !empty($errors['pays_id']) ? 'is-invalid' : '' ?>" id="pays_id" name="pays_id" required>
                                        <option value="">Sélectionnez votre pays</option>
                                        <?php foreach ($pays_liste as $pays_item): ?>
                                            <option value="<?= $pays_item['id'] ?>" <?= ($pays_id == $pays_item['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($pays_item['nom_pays']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="pays_id">Pays</label>
                                    <?php if (!empty($errors['pays_id'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['pays_id']) ?></div><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="password" class="form-control <?= !empty($errors['mot_de_passe']) ? 'is-invalid' : '' ?>" id="mot_de_passe" name="mot_de_passe" placeholder="Mot de passe" required>
                                    <label for="mot_de_passe">Mot de passe</label>
                                     <?php if (!empty($errors['mot_de_passe'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['mot_de_passe']) ?></div><?php endif; ?>
                                </div>
                            </div>
                             <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="password" class="form-control <?= !empty($errors['mot_de_passe_conf']) ? 'is-invalid' : '' ?>" id="mot_de_passe_conf" name="mot_de_passe_conf" placeholder="Confirmer le mot de passe" required>
                                    <label for="mot_de_passe_conf">Confirmer le mot de passe</label>
                                    <?php if (!empty($errors['mot_de_passe_conf'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['mot_de_passe_conf']) ?></div><?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" value="" id="terms" required>
                            <label class="form-check-label small" for="terms">
                                J'accepte les <a href="#" class="text-decoration-none">Termes et Conditions</a> et la <a href="#" class="text-decoration-none">Politique de Confidentialité</a> de E-Social.
                            </label>
                            <div class="invalid-feedback">Vous devez accepter les termes et conditions.</div>
                        </div>

                        <button class="w-100 btn btn-lg btn-primary" type="submit">
                           <i class="fas fa-user-plus me-2"></i> S'inscrire
                        </button>
                    </form>
                    <hr class="my-4">
                    <p class="text-center small">
                        Vous avez déjà un compte ? 
                        <a href="<?= SITE_URL ?>/public/connexion.php" class="fw-bold text-decoration-none">Connectez-vous ici</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
// Bootstrap client-side validation (optionnel, mais bon pour UX)
(function () {
  'use strict'
  var forms = document.querySelectorAll('.needs-validation')
  Array.prototype.slice.call(forms)
    .forEach(function (form) {
      form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) {
          event.preventDefault()
          event.stopPropagation()
        }
        form.classList.add('was-validated')
      }, false)
    })
})()
</script>