<?php
// public/dons.php
require_once '../config/db.php'; // Pour fonctions getPDO, sanitize, display_flash_message, SITE_URL
// session_start(); // db.php le fait déjà
require_once '../config/constantes.php'; 
require_once '../includes/fonctions.php'; // adapte le chemin si nécessaire


// Récupérer les données du formulaire en cas de redirection avec erreurs
$form_data = $_SESSION['don_form_data'] ?? [];
$form_errors = $_SESSION['don_form_errors'] ?? [];
unset($_SESSION['don_form_data'], $_SESSION['don_form_errors']); // Nettoyer après usage

$pdo = getPDO();

// Récupérer les campagnes actives pour le sélecteur (si don général)
$campagnes_actives = [];
try {
    $stmt_campagnes = $pdo->query("SELECT id, titre FROM campagnes WHERE statut = 'en cours' ORDER BY titre ASC");
    $campagnes_actives = $stmt_campagnes->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur récupération campagnes pour dons.php: " . $e->getMessage());
    // Gérer l'erreur, peut-être afficher un message à l'utilisateur
}

// Récupérer les moyens de paiement
$moyens_paiement = [];
try {
    $stmt_moyens = $pdo->query("SELECT id, nom_moyen, details FROM moyens_paiement ORDER BY nom_moyen ASC");
    $moyens_paiement = $stmt_moyens->fetchAll();
} catch (PDOException $e) {
    error_log("Erreur récupération moyens de paiement pour dons.php: " . $e->getMessage());
}

// Si un ID de campagne est passé en GET (par exemple, depuis un bouton "Faire un don" sur la page de la campagne)
$id_campagne_selectionnee = filter_input(INPUT_GET, 'campagne_id', FILTER_VALIDATE_INT);
if ($id_campagne_selectionnee && empty($form_data['campagne_id'])) { // Priorité aux données du formulaire en cas d'erreur
    $form_data['campagne_id'] = $id_campagne_selectionnee;
}

$page_title = "Faire un Don - E-Social";
// Supposons que vous ayez un header et footer globaux
// include_once '../includes/header.php'; // Si vous en avez un global pour les pages publiques
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css"> <!-- Votre CSS personnalisé -->
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5; /* Un gris clair pour le fond */
            color: #333;
        }

        .navbar-esocial { /* Adaptez à votre navbar si besoin */
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .navbar-esocial .navbar-brand, .navbar-esocial .nav-link {
            color: #fff !important;
            font-weight: 500;
        }
        .navbar-esocial .nav-link:hover {
            color: #e0e0e0 !important;
        }


        .donation-form-container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            margin-top: 50px;
            margin-bottom: 50px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .donation-form-container h2 {
            color: #6a11cb; /* Violet E-Social */
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .donation-form-container h2 .fa-hand-holding-heart {
            font-size: 2.5rem;
            margin-right: 15px;
            animation: pulseIcon 2s infinite;
        }

        @keyframes pulseIcon {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .form-label {
            font-weight: 600;
            color: #555;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 12px 15px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #8c52ff; /* Violet plus clair */
            box-shadow: 0 0 0 0.25rem rgba(106, 17, 203, 0.2);
        }

        .btn-donate {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            font-weight: 600;
            padding: 12px 30px;
            border-radius: 50px; /* Bouton pilule */
            border: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            font-size: 1.1rem;
        }
        .btn-donate:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(106, 17, 203, 0.4);
            color: white;
        }
        .btn-donate .fa-paper-plane {
            margin-right: 8px;
        }
        
        .payment-instructions {
            background-color: #e9ecef;
            border-left: 5px solid #6a11cb;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 0.9em;
            animation: slideInLeft 0.5s ease-out;
        }
        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .payment-instructions p { margin-bottom: 5px; }
        .payment-instructions strong { color: #495057; }

        .alert-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            min-width: 300px;
        }
        .quick-amount-buttons .btn {
            margin: 5px;
            border-radius: 20px;
            font-weight: 500;
            background-color: #e9ecef;
            color: #495057;
            border: 1px solid #ced4da;
        }
        .quick-amount-buttons .btn:hover, .quick-amount-buttons .btn.active {
            background-color: #6a11cb;
            color: white;
            border-color: #6a11cb;
        }
        .form-check-label {
            font-weight: 500;
        }
        .footer-esocial { /* Adaptez à votre footer */
            background-color: #343a40;
            color: #f8f9fa;
            padding: 40px 0;
            margin-top: 50px;
        }
    </style>
</head>
<body>

    <!-- Barre de navigation (exemple, à adapter ou inclure via ../includes/navbar.php) -->
    <nav class="navbar navbar-expand-lg navbar-esocial sticky-top">
        <div class="container">
            <a class="navbar-brand" href="<?= SITE_URL ?>public/index.php">
                <i class="fas fa-hands-helping"></i> E-Social
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon" style="color: white; background-image: var(--bs-navbar-toggler-icon-bg);"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>public/index.php">Accueil</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>public/campagnes.php">Campagnes</a></li>
                    <li class="nav-item"><a class="nav-link active" aria-current="page" href="<?= SITE_URL ?>public/dons.php">Faire un Don</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>public/contact.php">Contact</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdownUser" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['user_prenom'] ?? 'Mon Compte') ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdownUser">
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="<?= SITE_URL ?>admin/index.php">Panel Admin</a></li>
                                <?php else: ?>
                                    <li><a class="dropdown-item" href="<?= SITE_URL ?>utilisateur/dashboard.php">Tableau de bord</a></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item" href="<?= SITE_URL ?>utilisateur/profil.php">Mon Profil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= SITE_URL ?>utilisateur/deconnexion.php">Déconnexion</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>public/connexion.php">Connexion</a></li>
                        <li class="nav-item"><a class="nav-link btn btn-light btn-sm px-3 ms-2" style="color: #6a11cb !important;" href="<?= SITE_URL ?>public/inscription.php">Inscription</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="donation-form-container">
            <h2><i class="fas fa-hand-holding-heart"></i> Faites la Différence Aujourd'hui</h2>
            
            <div class="alert-container">
                <?php display_flash_message(); // Pour les messages de traitement/don.php ?>
            </div>

            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="alert alert-warning text-center" role="alert">
                    <i class="fas fa-info-circle"></i> Vous devez être <a href="<?= SITE_URL ?>public/connexion.php?redirect_to=<?= urlencode(SITE_URL . 'public/dons.php' . ($id_campagne_selectionnee ? '?campagne_id='.$id_campagne_selectionnee : '')) ?>" class="alert-link">connecté</a>
                    ou <a href="<?= SITE_URL ?>public/inscription.php" class="alert-link">créer un compte</a> pour faire un don.
                </div>
            <?php else: ?>
                <?php if (!empty($form_errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Erreurs détectées !</h4>
                        <p>Veuillez corriger les points suivants avant de soumettre à nouveau :</p>
                        <hr>
                        <ul>
                            <?php foreach ($form_errors as $field => $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="<?= SITE_URL ?>traitement/don.php" method="POST" enctype="multipart/form-data" id="donationForm" novalidate>
                    
                    <?php if (!$id_campagne_selectionnee || empty($campagnes_actives)): // Affiche le sélecteur de campagne si ce n'est pas pour une campagne spécifique ou si on ne trouve pas de campagne spécifique ?>
                        <div class="mb-4">
                            <label for="campagne_id" class="form-label">Choisir une campagne (Optionnel)</label>
                            <select class="form-select <?= isset($form_errors['campagne_id']) ? 'is-invalid' : '' ?>" id="campagne_id" name="campagne_id">
                                <option value="">-- Don général / Choisir une campagne --</option>
                                <?php foreach ($campagnes_actives as $campagne): ?>
                                    <option value="<?= $campagne['id'] ?>" <?= (($form_data['campagne_id'] ?? '') == $campagne['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($campagne['titre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($form_errors['campagne_id'])): ?>
                                <div class="invalid-feedback"><?= htmlspecialchars($form_errors['campagne_id']) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php // Si une campagne est présélectionnée via GET, on cherche son titre pour l'afficher
                        $titre_campagne_sel = "Campagne Spécifique"; // Valeur par défaut
                        if($id_campagne_selectionnee) {
                            foreach($campagnes_actives as $camp) {
                                if($camp['id'] == $id_campagne_selectionnee) {
                                    $titre_campagne_sel = $camp['titre'];
                                    break;
                                }
                            }
                        }
                        ?>
                        <div class="mb-4 p-3 bg-light border rounded">
                            <p class="mb-1"><strong>Vous faites un don pour la campagne :</strong></p>
                            <h4><i class="fas fa-bullhorn text-primary me-2"></i><?= htmlspecialchars($titre_campagne_sel) ?></h4>
                            <input type="hidden" name="campagne_id" value="<?= htmlspecialchars($id_campagne_selectionnee) ?>">
                        </div>
                    <?php endif; ?>


                    <div class="mb-4">
                        <label for="montant" class="form-label">Montant de votre don (CFA) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-coins"></i></span>
                            <input type="number" class="form-control <?= isset($form_errors['montant']) ? 'is-invalid' : '' ?>" id="montant" name="montant" 
                                   value="<?= htmlspecialchars($form_data['montant'] ?? '5000') ?>" min="500" step="100" required>
                        </div>
                         <?php if (isset($form_errors['montant'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($form_errors['montant']) ?></div>
                        <?php endif; ?>
                        <div class="mt-2 quick-amount-buttons">
                            <button type="button" class="btn btn-sm quick-amount" data-amount="1000">1000</button>
                            <button type="button" class="btn btn-sm quick-amount" data-amount="2500">2500</button>
                            <button type="button" class="btn btn-sm quick-amount" data-amount="5000">5000</button>
                            <button type="button" class="btn btn-sm quick-amount" data-amount="10000">10000</button>
                            <button type="button" class="btn btn-sm quick-amount" data-amount="25000">25000</button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="moyen_paiement_id" class="form-label">Moyen de Paiement <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($form_errors['moyen_paiement_id']) ? 'is-invalid' : '' ?>" id="moyen_paiement_id" name="moyen_paiement_id" required>
                            <option value="">-- Choisissez votre moyen de paiement --</option>
                            <?php foreach ($moyens_paiement as $moyen): ?>
                                <option value="<?= $moyen['id'] ?>" 
                                        data-details="<?= htmlspecialchars($moyen['details']) ?>"
                                        <?= (($form_data['moyen_paiement_id'] ?? '') == $moyen['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($moyen['nom_moyen']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="payment-details" class="payment-instructions mt-2" style="display:none;"></div>
                        <?php if (isset($form_errors['moyen_paiement_id'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($form_errors['moyen_paiement_id']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4" id="preuve-paiement-div" style="display:none;">
                        <label for="preuve_paiement" class="form-label">Preuve de Paiement (Optionnel mais recommandé pour paiements hors ligne)</label>
                        <input type="file" class="form-control <?= isset($form_errors['preuve_paiement']) ? 'is-invalid' : '' ?>" id="preuve_paiement" name="preuve_paiement" accept="image/*,application/pdf">
                        <div class="form-text">Formats acceptés: JPG, PNG, PDF, WEBP (max 3MB). Une preuve accélère la confirmation de votre don.</div>
                        <?php if (isset($form_errors['preuve_paiement'])): ?>
                            <div class="invalid-feedback d-block"><?= htmlspecialchars($form_errors['preuve_paiement']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" value="1" id="anonyme" name="anonyme" <?= isset($form_data['anonyme']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="anonyme">
                            Je souhaite que mon don reste anonyme (ne sera pas affiché publiquement).
                        </label>
                    </div>

                    <p class="text-muted small mb-4">
                        <i class="fas fa-shield-alt"></i> Vos informations personnelles sont protégées. En cliquant sur "Valider mon don", vous acceptez nos <a href="#">termes et conditions</a>.
                    </p>

                    <button type="submit" class="btn btn-donate w-100">
                        <i class="fas fa-paper-plane"></i> Valider mon Don
                    </button>
                </form>
            <?php endif; // Fin de la vérification de connexion utilisateur ?>
        </div>
    </div>

    <!-- Footer (exemple, à adapter ou inclure via ../includes/footer.php) -->
    <footer class="footer-esocial text-center py-4">
        <div class="container">
            <p class="mb-0">© <?= date('Y') ?> E-Social. Tous droits réservés. Solidarité en action.</p>
            <p><a href="#" class="text-light small">Mentions Légales</a> | <a href="#" class="text-light small">Politique de confidentialité</a></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const moyenSelect = document.getElementById('moyen_paiement_id');
            const paymentDetailsDiv = document.getElementById('payment-details');
            const preuveDiv = document.getElementById('preuve-paiement-div');
            const preuveInput = document.getElementById('preuve_paiement');
            const montantInput = document.getElementById('montant');
            const quickAmountButtons = document.querySelectorAll('.quick-amount');

            function updatePaymentForm() {
                if (!moyenSelect) return;
                const selectedOption = moyenSelect.options[moyenSelect.selectedIndex];
                
                if (!selectedOption || !selectedOption.value) { // Si l'option "-- Choisir --" est sélectionnée
                    paymentDetailsDiv.style.display = 'none';
                    preuveDiv.style.display = 'none';
                    if(preuveInput) preuveInput.required = false;
                    return;
                }

                const details = selectedOption.dataset.details;
                const nomMoyen = selectedOption.text.toLowerCase();

                if (details && details.trim() !== "") {
                    paymentDetailsDiv.innerHTML = `<p class="mb-0">${details.replace(/\n/g, '<br>')}</p>`;
                    paymentDetailsDiv.style.display = 'block';
                } else {
                    paymentDetailsDiv.style.display = 'none';
                }

                // Logique améliorée pour afficher le champ de preuve
                const offlinePaymentKeywords = ['orange money', 'wave', 'free money', 'dépôt bancaire', 'virement', 'chèque'];
                let isOffline = offlinePaymentKeywords.some(keyword => nomMoyen.includes(keyword));

                if (isOffline) {
                    preuveDiv.style.display = 'block';
                    if(preuveInput) preuveInput.required = true; // Rendre requis pour les paiements hors ligne
                } else {
                    preuveDiv.style.display = 'none';
                    if(preuveInput) preuveInput.required = false;
                }
            }
            
            if(moyenSelect) {
                moyenSelect.addEventListener('change', updatePaymentForm);
                updatePaymentForm(); // Appel initial pour l'état au chargement
            }

            quickAmountButtons.forEach(button => {
                button.addEventListener('click', function() {
                    montantInput.value = this.dataset.amount;
                    // Optionally, remove 'active' class from other buttons and add to this one
                    quickAmountButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Dismiss alerts after a while
            const alerts = document.querySelectorAll('.alert-container .alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    new bootstrap.Alert(alert).close();
                }, 7000); // 7 secondes
            });
        });
    </script>
</body>
</html>