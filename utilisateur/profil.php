<?php
session_start();
require_once '../config/db.php';
require_once '../includes/fonctions.php';

// Suppression de la vérification suivante :
// if (!isset($_SESSION['utilisateur'])) {
//     header('Location: ../public/connexion.php');
//     exit();
// }

// On récupère quand même la variable $utilisateur si elle existe, sinon un tableau vide
$utilisateur = $_SESSION['utilisateur'] ?? [
    'id' => 0,
    'prenom' => '',
    'nom' => '',
    'email' => '',
    'telephone' => '',
    'pays_id' => null
];

$pdo = getPDO();
$erreurs = [];
$success = '';

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom = sanitize($_POST['prenom']);
    $nom = sanitize($_POST['nom']);
    $email = sanitize($_POST['email']);
    $telephone = sanitize($_POST['telephone']);
    $pays_id = (int)$_POST['pays_id'];

    // Validation
    if (empty($prenom)) $erreurs[] = "Le prénom est obligatoire";
    if (empty($nom)) $erreurs[] = "Le nom est obligatoire";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = "L'email n'est pas valide";

    // Vérification email si utilisateur connecté (id > 0)
    if ($utilisateur['id'] > 0) {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
        $stmt->execute([$email, $utilisateur['id']]);
        if ($stmt->fetch()) $erreurs[] = "Cet email est déjà utilisé par un autre compte";
    }

    if (empty($erreurs) && $utilisateur['id'] > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE utilisateurs SET prenom = ?, nom = ?, email = ?, telephone = ?, pays_id = ? WHERE id = ?");
            $stmt->execute([$prenom, $nom, $email, $telephone, $pays_id, $utilisateur['id']]);

            // Mise à jour de la session
            $_SESSION['utilisateur']['prenom'] = $prenom;
            $_SESSION['utilisateur']['nom'] = $nom;
            $_SESSION['utilisateur']['email'] = $email;
            $_SESSION['utilisateur']['telephone'] = $telephone;
            $_SESSION['utilisateur']['pays_id'] = $pays_id;

            $success = "Votre profil a été mis à jour avec succès!";
            $utilisateur = $_SESSION['utilisateur'];
        } catch (PDOException $e) {
            $erreurs[] = "Erreur lors de la mise à jour: " . $e->getMessage();
        }
    }
}

// Récupérer la liste des pays
$pays = $pdo->query("SELECT * FROM pays ORDER BY nom_pays")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil | E-Social</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            position: relative;
            overflow: hidden;
        }
        .profile-header:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('../assets/images/pattern.png') repeat;
            opacity: 0.1;
        }
        .profile-pic {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border: 5px solid rgba(255,255,255,0.3);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }
        .glow-on-hover {
            transition: all 0.3s ease;
        }
        .glow-on-hover:hover {
            box-shadow: 0 0 15px rgba(102, 126, 234, 0.5);
        }

        .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100%;
    width: 200px; /* largeur de ta sidebar */
    background-color: #343a40;
    z-index: 1000;
}

.main-content {
    margin-left: 250px; /* même largeur que .sidebar */
    padding: 20px;
}

    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar_admin.php'; ?>

    <div class="main-content">
        <div class="container-fluid py-5">
            <div class="row mb-5">
                <div class="col-12">
                    <div class="profile-header p-4 text-white text-center animate__animated animate__fadeIn">
                        <div class="d-flex justify-content-center mb-3">
                            <img src="../assets/images/image<?= $utilisateur['id'] % 5 + 1 ?>.png" alt="Photo de profil" class="profile-pic rounded-circle animate__animated animate__zoomIn">
                        </div>
                        <h2 class="fw-bold mb-1"><?= htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']) ?></h2>
                        <p class="mb-0"><i class="fas fa-envelope me-2"></i> <?= htmlspecialchars($utilisateur['email']) ?></p>
                    </div>
                </div>
            </div>

            <div class="row animate__animated animate__fadeInUp">
                <div class="col-lg-4 mb-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0">
                            <h5 class="fw-bold mb-0"><i class="fas fa-user-cog me-2"></i> Menu Profil</h5>
                        </div>
                        <div class="card-body">
                            <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                                <button class="nav-link active text-start mb-2" id="v-pills-info-tab" data-bs-toggle="pill" data-bs-target="#v-pills-info" type="button" role="tab" aria-controls="v-pills-info" aria-selected="true">
                                    <i class="fas fa-info-circle me-2"></i> Informations personnelles
                                </button>
                                <button class="nav-link text-start mb-2" id="v-pills-password-tab" data-bs-toggle="pill" data-bs-target="#v-pills-password" type="button" role="tab" aria-controls="v-pills-password" aria-selected="false">
                                    <i class="fas fa-lock me-2"></i> Modifier le mot de passe
                                </button>
                                <button class="nav-link text-start mb-2" id="v-pills-notif-tab" data-bs-toggle="pill" data-bs-target="#v-pills-notif" type="button" role="tab" aria-controls="v-pills-notif" aria-selected="false">
                                    <i class="fas fa-bell me-2"></i> Préférences de notification
                                </button>
                                <button class="nav-link text-start" id="v-pills-delete-tab" data-bs-toggle="pill" data-bs-target="#v-pills-delete" type="button" role="tab" aria-controls="v-pills-delete" aria-selected="false">
                                    <i class="fas fa-trash-alt me-2"></i> Supprimer le compte
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0">
                            <h5 class="fw-bold mb-0"><i class="fas fa-user-edit me-2"></i> Modifier mon profil</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($success): ?>
                                <div class="alert alert-success animate__animated animate__fadeIn">
                                    <i class="fas fa-check-circle me-2"></i> <?= $success ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($erreurs)): ?>
                                <div class="alert alert-danger animate__animated animate__shakeX">
                                    <ul class="mb-0">
                                        <?php foreach ($erreurs as $erreur): ?>
                                            <li><?= $erreur ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <div class="tab-content" id="v-pills-tabContent">
    <div class="tab-pane fade show active" id="v-pills-info" role="tabpanel" aria-labelledby="v-pills-info-tab">
        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($erreurs)): ?>
            <div class="alert alert-danger">
                <ul>
                    <?php foreach ($erreurs as $erreur): ?>
                        <li><?= htmlspecialchars($erreur) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="prenom" class="form-label">Prénom</label>
                    <input type="text" class="form-control glow-on-hover" id="prenom" name="prenom" value="<?= htmlspecialchars($utilisateur['prenom']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" class="form-control glow-on-hover" id="nom" name="nom" value="<?= htmlspecialchars($utilisateur['nom']) ?>" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control glow-on-hover" id="email" name="email" value="<?= htmlspecialchars($utilisateur['email']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="telephone" class="form-label">Téléphone</label>
                <input type="tel" class="form-control glow-on-hover" id="telephone" name="telephone" value="<?= htmlspecialchars($utilisateur['telephone']) ?>">
            </div>
            <div class="mb-4">
                <label for="pays_id" class="form-label">Pays</label>
                <select class="form-select glow-on-hover" id="pays_id" name="pays_id">
                    <?php foreach ($pays as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= $p['id'] == $utilisateur['pays_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nom_pays']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
                                        <button type="submit" class="btn btn-primary px-4 py-2 glow-on-hover">
                                            <i class="fas fa-save me-2"></i> Enregistrer les modifications
                                        </button>
                                    </form>
                                </div>

                                <!-- Onglet Mot de passe -->
                                <div class="tab-pane fade" id="v-pills-password" role="tabpanel" aria-labelledby="v-pills-password-tab">
                                    <form id="passwordForm">
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Mot de passe actuel</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control glow-on-hover" id="current_password" name="current_password" required>
                                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control glow-on-hover" id="new_password" name="new_password" required>
                                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="form-text">Minimum 8 caractères, avec au moins une majuscule et un chiffre</div>
                                        </div>
                                        <div class="mb-4">
                                            <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control glow-on-hover" id="confirm_password" name="confirm_password" required>
                                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary px-4 py-2 glow-on-hover">
                                            <i class="fas fa-key me-2"></i> Changer le mot de passe
                                        </button>
                                    </form>
                                </div>

                                <!-- Onglet Notifications -->
                                <div class="tab-pane fade" id="v-pills-notif" role="tabpanel" aria-labelledby="v-pills-notif-tab">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> Configurez vos préférences de notification
                                    </div>
                                    <form>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="notifEmail" checked>
                                            <label class="form-check-label" for="notifEmail">Recevoir des notifications par email</label>
                                        </div>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="notifSMS" checked>
                                            <label class="form-check-label" for="notifSMS">Recevoir des notifications par SMS</label>
                                        </div>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="notifDons" checked>
                                            <label class="form-check-label" for="notifDons">Notifications sur mes dons</label>
                                        </div>
                                        <div class="form-check form-switch mb-4">
                                            <input class="form-check-input" type="checkbox" id="notifCampagnes" checked>
                                            <label class="form-check-label" for="notifCampagnes">Nouvelles campagnes</label>
                                        </div>
                                        <button type="submit" class="btn btn-primary px-4 py-2 glow-on-hover">
                                            <i class="fas fa-bell me-2"></i> Enregistrer les préférences
                                        </button>
                                    </form>
                                </div>

                                <!-- Onglet Suppression compte -->
                                <div class="tab-pane fade" id="v-pills-delete" role="tabpanel" aria-labelledby="v-pills-delete-tab">
                                    <div class="alert alert-danger">
                                        <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i> Attention !</h5>
                                        <p>La suppression de votre compte est irréversible. Toutes vos données seront définitivement perdues.</p>
                                        <hr>
                                        <p class="mb-0">Si vous souhaitez continuer, veuillez confirmer votre mot de passe.</p>
                                    </div>
                                    <form>
                                        <div class="mb-3">
                                            <label for="delete_password" class="form-label">Confirmez votre mot de passe</label>
                                            <input type="password" class="form-control" id="delete_password" required>
                                        </div>
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="confirmDelete" required>
                                            <label class="form-check-label" for="confirmDelete">Je comprends que cette action est irréversible</label>
                                        </div>
                                        <button type="submit" class="btn btn-danger px-4 py-2">
                                            <i class="fas fa-trash-alt me-2"></i> Supprimer définitivement mon compte
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/scripts.js"></script>
    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.previousElementSibling;
                const icon = this.querySelector('i');
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // Validation du formulaire de mot de passe
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                alert('Les mots de passe ne correspondent pas');
                return;
            }
            
            // Ici, vous devriez ajouter une requête AJAX pour envoyer les données au serveur
            alert('Mot de passe changé avec succès! (Cette fonctionnalité sera implémentée complètement plus tard)');
        });
    </script>
</body>
</html>