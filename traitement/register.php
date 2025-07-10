<?php
// traitement/register.php
require_once '../config/db.php'; // db.php démarre la session
require_once '../config/constantes.php'; 
require_once '../includes/fonctions.php';
// Si l'utilisateur est déjà connecté, rediriger vers le tableau de bord
if (isset($_SESSION['user_id'])) {
    redirect('dashboard.php');
}

$errors = [];
$input_values = [
    'prenom' => '', 'nom' => '', 'email' => '', 'telephone' => '', 'pays_id' => ''
];

// Récupérer la liste des pays pour le formulaire
$pdo = getPDO();
$stmt_pays = $pdo->query("SELECT id, nom_pays FROM pays ORDER BY nom_pays ASC");
$pays_list = $stmt_pays->fetchAll();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and retrieve POST data
    $input_values['prenom'] = sanitize($_POST['prenom'] ?? '');
    $input_values['nom'] = sanitize($_POST['nom'] ?? '');
    $input_values['email'] = sanitize($_POST['email'] ?? '');
    $input_values['telephone'] = sanitize($_POST['telephone'] ?? '');
    $password = $_POST['password'] ?? ''; // Don't sanitize password before hashing
    $password_confirm = $_POST['password_confirm'] ?? '';
    $input_values['pays_id'] = filter_input(INPUT_POST, 'pays_id', FILTER_VALIDATE_INT);

    // Validations
    if (empty($input_values['prenom'])) $errors['prenom'] = "Le prénom est requis.";
    if (empty($input_values['nom'])) $errors['nom'] = "Le nom est requis.";
    if (empty($input_values['email'])) {
        $errors['email'] = "L'email est requis.";
    } elseif (!filter_var($input_values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Format d'email invalide.";
    } else {
        // Vérifier si l'email existe déjà
        $stmt_check_email = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = :email");
        $stmt_check_email->bindParam(':email', $input_values['email']);
        $stmt_check_email->execute();
        if ($stmt_check_email->fetch()) {
            $errors['email'] = "Cet email est déjà utilisé.";
        }
    }
    // Validation téléphone (optionnelle, exemple basique)
    if (!empty($input_values['telephone']) && !preg_match('/^[0-9\s\-\+\(\)]{7,20}$/', $input_values['telephone'])) {
        $errors['telephone'] = "Format de téléphone invalide.";
    }

    if (empty($password)) {
        $errors['password'] = "Le mot de passe est requis.";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif ($password !== $password_confirm) {
        $errors['password_confirm'] = "Les mots de passe ne correspondent pas.";
    }
    if (empty($input_values['pays_id'])) $errors['pays_id'] = "Veuillez sélectionner un pays.";


    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        try {
            $sql = "INSERT INTO utilisateurs (prenom, nom, email, telephone, mot_de_passe, pays_id, role) 
                    VALUES (:prenom, :nom, :email, :telephone, :mot_de_passe, :pays_id, 'donateur')";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':prenom', $input_values['prenom']);
            $stmt->bindParam(':nom', $input_values['nom']);
            $stmt->bindParam(':email', $input_values['email']);
            $stmt->bindParam(':telephone', $input_values['telephone']);
            $stmt->bindParam(':mot_de_passe', $hashed_password);
            $stmt->bindParam(':pays_id', $input_values['pays_id']);
            
            $stmt->execute();

            set_flash_message("Inscription réussie ! Vous pouvez maintenant vous connecter.", "success");
            redirect('login.php');

        } catch (PDOException $e) {
            // Log $e->getMessage()
            $errors['db'] = "Erreur lors de l'inscription. Veuillez réessayer. " . $e->getMessage(); // DEV only
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - E-Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea, #764ba2);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px 0;
            font-family: 'Poppins', sans-serif;
        }
        .register-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 15px 25px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px; /* Increased width for more fields */
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .register-container h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }
        .form-control:focus, .form-select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            color: white;
            padding: 10px 15px;
            border-radius: 25px;
            transition: all 0.3s ease;
            width: 100%;
            font-weight: 500;
        }
        .btn-register:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.15);
        }
        .form-floating label, .form-label {
            color: #6c757d;
        }
        .extra-links {
            margin-top: 15px;
            text-align: center;
            font-size: 0.9em;
        }
        .extra-links a {
            color: #555;
            text-decoration: none;
        }
        .extra-links a:hover {
            color: #764ba2;
            text-decoration: underline;
        }
        .is-invalid {
            border-color: #dc3545 !important;
        }
        .invalid-feedback {
            display: block; /* Ensure feedback is visible */
        }
         .logo-container {
            text-align: center;
            margin-bottom: 15px;
        }
        .logo-container img {
            max-width: 120px; /* Ajustez selon votre logo */
             animation: logoPulse 2s infinite ease-in-out;
        }
         @keyframes logoPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
    </style>
</head>
<body>
    <div class="register-container">
         <div class="logo-container">
            <img src="../assets/images/logos/logo_esocial.png" alt="E-Social Logo" onerror="this.style.display='none'; this.parentElement.innerHTML='<h3><i class=\'fas fa-hands-helping\'></i> E-Social</h3>';">
        </div>
        <h2><i class="fas fa-user-plus"></i> Créer un compte</h2>

        <?php if (!empty($errors['db'])): ?>
            <div class="alert alert-danger"><i class="fas fa-times-circle"></i> <?= htmlspecialchars($errors['db']) ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php" novalidate>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-floating">
                        <input type="text" class="form-control <?= isset($errors['prenom']) ? 'is-invalid' : '' ?>" id="prenom" name="prenom" placeholder="Votre prénom" value="<?= htmlspecialchars($input_values['prenom']) ?>" required>
                        <label for="prenom"><i class="fas fa-user"></i> Prénom</label>
                        <?php if (isset($errors['prenom'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['prenom']) ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-floating">
                        <input type="text" class="form-control <?= isset($errors['nom']) ? 'is-invalid' : '' ?>" id="nom" name="nom" placeholder="Votre nom" value="<?= htmlspecialchars($input_values['nom']) ?>" required>
                        <label for="nom"><i class="fas fa-user"></i> Nom</label>
                        <?php if (isset($errors['nom'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['nom']) ?></div><?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-floating mb-3">
                <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" placeholder="name@example.com" value="<?= htmlspecialchars($input_values['email']) ?>" required>
                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                <?php if (isset($errors['email'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['email']) ?></div><?php endif; ?>
            </div>

            <div class="form-floating mb-3">
                <input type="tel" class="form-control <?= isset($errors['telephone']) ? 'is-invalid' : '' ?>" id="telephone" name="telephone" placeholder="Votre numéro de téléphone" value="<?= htmlspecialchars($input_values['telephone']) ?>">
                <label for="telephone"><i class="fas fa-phone"></i> Téléphone (Optionnel)</label>
                <?php if (isset($errors['telephone'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['telephone']) ?></div><?php endif; ?>
            </div>
            
            <div class="mb-3">
                <label for="pays_id" class="form-label"><i class="fas fa-globe-africa"></i> Pays</label>
                <select class="form-select <?= isset($errors['pays_id']) ? 'is-invalid' : '' ?>" id="pays_id" name="pays_id" required>
                    <option value="">Sélectionnez votre pays</option>
                    <?php foreach ($pays_list as $pays_item): ?>
                        <option value="<?= $pays_item['id'] ?>" <?= ($input_values['pays_id'] == $pays_item['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($pays_item['nom_pays']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['pays_id'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['pays_id']) ?></div><?php endif; ?>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-floating">
                        <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" placeholder="Mot de passe" required>
                        <label for="password"><i class="fas fa-lock"></i> Mot de passe</label>
                        <?php if (isset($errors['password'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['password']) ?></div><?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="form-floating">
                        <input type="password" class="form-control <?= isset($errors['password_confirm']) ? 'is-invalid' : '' ?>" id="password_confirm" name="password_confirm" placeholder="Confirmez le mot de passe" required>
                        <label for="password_confirm"><i class="fas fa-lock"></i> Confirmez mot de passe</label>
                        <?php if (isset($errors['password_confirm'])): ?><div class="invalid-feedback"><?= htmlspecialchars($errors['password_confirm']) ?></div><?php endif; ?>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-register mt-2"><i class="fas fa-check-circle"></i> S'inscrire</button>
        </form>
        <div class="extra-links">
            Vous avez déjà un compte ? <a href="index.php">Connectez-vous ici</a>
        </div>
         <div class="text-center mt-3">
            <a href="../public/index.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-home"></i> Retour à l'accueil</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>